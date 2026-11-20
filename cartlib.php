<?php
// ===== Giỏ hàng lưu theo phiên: $_SESSION['cart'] = [mã sản phẩm => số lượng] =====
function cart_all(){ return (isset($_SESSION['cart'])&&is_array($_SESSION['cart']))?$_SESSION['cart']:[]; }
function cart_count(){ return (int)array_sum(cart_all()); }
// Các dòng giỏ hàng kèm thông tin sản phẩm. Số lượng bị chặn theo tồn kho; hết hàng thì đánh dấu oos.
function cart_lines($pdo,$only=null){
  $c=cart_all(); if(!$c) return []; $ids=array_map('intval',array_keys($c));
  if($only!==null) $ids=array_values(array_intersect($ids,array_map('intval',(array)$only)));
  if(!$ids) return [];
  $q=$pdo->prepare(PRODUCT_SQL.' WHERE p.id IN ('.implode(',',array_fill(0,count($ids),'?')).')'); $q->execute($ids);
  $rows=[]; foreach($q->fetchAll() as $p){
    $want=max(1,min(50,(int)$c[$p['id']])); $st=(int)($p['stock']??0);
    $p['want']=$want; $p['oos']=($st<=0); $p['qty']=$st>0?min($want,$st):$want; $rows[(int)$p['id']]=$p; }
  $out=[]; foreach($ids as $id){ if(isset($rows[$id])) $out[]=$rows[$id]; } return $out; }
function cart_totals($lines){ $list=0;$pay=0;$qty=0;
  foreach($lines as $l){ $q=(int)$l['qty']; $pay+=(int)$l['price']*$q; $list+=max((int)$l['old_price'],(int)$l['price'])*$q; $qty+=$q; }
  return ['list'=>$list,'disc'=>$list-$pay,'pay'=>$pay,'ship'=>0,'total'=>$pay,'qty'=>$qty,'n'=>count($lines)]; }
function cart_payload($pdo){
  $lines=cart_lines($pdo); $c=[]; foreach($lines as $l) $c[(int)$l['id']]=(int)$l['qty']; $_SESSION['cart']=$c; // bỏ sản phẩm đã xóa, chặn theo tồn kho
  $items=[]; foreach($lines as $l) $items[]=['id'=>(int)$l['id'],'name'=>$l['name'],'brand'=>$l['brand'],'price'=>(int)$l['price'],'old'=>(int)$l['old_price'],'qty'=>(int)$l['qty'],'stock'=>(int)$l['stock'],'thumb'=>product_visual($l),'url'=>'product.php?id='.(int)$l['id']];
  $t=cart_totals($lines); return ['ok'=>true,'count'=>$t['qty'],'items'=>$items]; }

// ===== Cập nhật CSDL tự động (một lần mỗi phiên): tồn kho, đơn hàng nhiều sản phẩm, cài đặt thanh toán =====
function shop_boot($pdo){
  if(!empty($_SESSION['shop_ok'])) return;
  if(!$pdo->query("SHOW COLUMNS FROM products LIKE 'stock'")->fetch()) $pdo->exec('ALTER TABLE products ADD stock INT NOT NULL DEFAULT 20');
  foreach(['customer_id'=>'INT NULL','total'=>'INT NULL','receive'=>'VARCHAR(20) NULL','ship'=>'VARCHAR(40) NULL','pay'=>'VARCHAR(20) NULL','extras'=>'VARCHAR(255) NULL','invoice'=>'TINYINT(1) NOT NULL DEFAULT 0','stock_deducted'=>'TINYINT(1) NOT NULL DEFAULT 0','paid_at'=>'DATETIME NULL'] as $c=>$d){
    if(!$pdo->query("SHOW COLUMNS FROM orders LIKE '$c'")->fetch()) $pdo->exec("ALTER TABLE orders ADD $c $d"); }
  $st=$pdo->query("SHOW COLUMNS FROM orders LIKE 'status'")->fetch();
  if($st && strpos((string)$st['Type'],'cho_tt')===false) $pdo->exec("ALTER TABLE orders MODIFY status ENUM('moi','daxuly','cho_tt','huy') NOT NULL DEFAULT 'moi'");
  $pdo->exec("CREATE TABLE IF NOT EXISTS order_items(id INT AUTO_INCREMENT PRIMARY KEY, order_id INT NOT NULL, product_id INT NOT NULL, qty INT NOT NULL, price INT NOT NULL, old_price INT NULL, name VARCHAR(200) NULL, INDEX(order_id)) DEFAULT CHARSET=utf8mb4");
  if(!$pdo->query("SHOW COLUMNS FROM order_items LIKE 'name'")->fetch()) $pdo->exec('ALTER TABLE order_items ADD name VARCHAR(200) NULL');
  $pdo->exec("CREATE TABLE IF NOT EXISTS settings(k VARCHAR(50) PRIMARY KEY, v TEXT) DEFAULT CHARSET=utf8mb4");
  $_SESSION['shop_ok']=1; }
function ensure_orders($pdo){ shop_boot($pdo); }
function setting_get($pdo,$k,$d=''){ $q=$pdo->prepare('SELECT v FROM settings WHERE k=?'); $q->execute([$k]); $v=$q->fetchColumn(); return $v===false?$d:(string)$v; }
function setting_set($pdo,$k,$v){ $pdo->prepare('INSERT INTO settings(k,v) VALUES(?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)')->execute([$k,(string)$v]); }

// ===== Nhãn, mã đơn, trạng thái =====
function order_code($id){ return 'DH'.str_pad((int)$id,4,'0',STR_PAD_LEFT); }
function ord_label($k){ $m=['home'=>'Giao hàng tận nơi','store'=>'Nhận tại cửa hàng','standard'=>'Vận chuyển tiêu chuẩn','cod'=>'Thanh toán khi nhận hàng (COD)','bank'=>'Chuyển khoản ngân hàng']; return $m[$k]??(string)$k; }
function ord_extras(){ return ['setup'=>'Hỗ trợ lắp đặt/cài đặt','proxy'=>'Nhờ người khác nhận hàng','tech'=>'Hỗ trợ kỹ thuật']; }
function ord_status_label($s){ $m=['cho_tt'=>'Chờ xác nhận thanh toán','moi'=>'Đã tiếp nhận','daxuly'=>'Hoàn tất','huy'=>'Đã hủy']; return $m[$s]??(string)$s; }
function ord_badge($s){ $c=['cho_tt'=>'wait','moi'=>'new','daxuly'=>'done','huy'=>'cancel']; return '<span class="stb '.($c[$s]??'new').'">'.e(ord_status_label($s)).'</span>'; }
// Tồn kho: [lớp CSS, chữ hiển thị]
function stock_info($p){ $n=(int)($p['stock']??0); if($n<=0) return ['out','Hết hàng']; if($n<=5) return ['low','Chỉ còn '.$n.' sản phẩm']; return ['ok','Còn hàng']; }

// ===== Trừ / hoàn kho (gọi bên trong một giao dịch của người gọi; lỗi thì ném Exception) =====
function order_stock_take($pdo,$oid){
  $q=$pdo->prepare('SELECT stock_deducted FROM orders WHERE id=? FOR UPDATE'); $q->execute([$oid]); if((int)$q->fetchColumn()===1) return;
  $it=$pdo->prepare('SELECT product_id,qty,name FROM order_items WHERE order_id=?'); $it->execute([$oid]);
  $up=$pdo->prepare('UPDATE products SET stock=stock-? WHERE id=? AND stock>=?');
  foreach($it->fetchAll() as $x){ $up->execute([$x['qty'],$x['product_id'],$x['qty']]);
    if($up->rowCount()!==1){ $s=$pdo->prepare('SELECT stock FROM products WHERE id=?'); $s->execute([$x['product_id']]); $left=(int)$s->fetchColumn();
      throw new Exception('Không đủ hàng cho "'.($x['name']?:('mã '.$x['product_id'])).'": cần '.(int)$x['qty'].', trong kho còn '.$left.'.'); } }
  $pdo->prepare('UPDATE orders SET stock_deducted=1 WHERE id=?')->execute([$oid]); }
function order_stock_return($pdo,$oid){
  $q=$pdo->prepare('SELECT stock_deducted FROM orders WHERE id=? FOR UPDATE'); $q->execute([$oid]); if((int)$q->fetchColumn()!==1) return;
  $it=$pdo->prepare('SELECT product_id,qty FROM order_items WHERE order_id=?'); $it->execute([$oid]);
  $up=$pdo->prepare('UPDATE products SET stock=stock+? WHERE id=?'); foreach($it->fetchAll() as $x) $up->execute([$x['qty'],$x['product_id']]);
  $pdo->prepare('UPDATE orders SET stock_deducted=0 WHERE id=?')->execute([$oid]); }
// Đổi trạng thái đơn: 'confirm' (đã nhận tiền chuyển khoản → trừ kho), 'done' (hoàn tất), 'cancel' (hủy → hoàn kho nếu đã trừ). Trả về '' nếu ổn, ngược lại là thông báo lỗi.
function order_change($pdo,$oid,$action){
  try{ $pdo->beginTransaction();
    $q=$pdo->prepare('SELECT status FROM orders WHERE id=? FOR UPDATE'); $q->execute([$oid]); $s=$q->fetchColumn(); if($s===false) throw new Exception('Đơn hàng không tồn tại.');
    if($action==='confirm'){ if($s!=='cho_tt') throw new Exception('Đơn này không ở trạng thái chờ xác nhận thanh toán.'); order_stock_take($pdo,$oid); $pdo->prepare("UPDATE orders SET status='moi', paid_at=NOW() WHERE id=?")->execute([$oid]); }
    elseif($action==='done'){ if($s!=='moi') throw new Exception('Chỉ đơn đã tiếp nhận mới chuyển được sang hoàn tất.'); $pdo->prepare("UPDATE orders SET status='daxuly' WHERE id=?")->execute([$oid]); }
    elseif($action==='cancel'){ if($s==='huy') throw new Exception('Đơn này đã được hủy trước đó.'); order_stock_return($pdo,$oid); $pdo->prepare("UPDATE orders SET status='huy' WHERE id=?")->execute([$oid]); }
    else throw new Exception('Thao tác không hợp lệ.');
    $pdo->commit(); return '';
  }catch(Exception $x){ if($pdo->inTransaction()) $pdo->rollBack(); return ($x instanceof PDOException)?'Lỗi cơ sở dữ liệu, vui lòng thử lại.':$x->getMessage(); } }

// ===== Thanh toán chuyển khoản + mã QR =====
function qr_ok($f){ return $f!==''&&preg_match('/^qr_[a-f0-9]+\.(jpg|png|webp)$/',$f)&&is_file(__DIR__.'/uploads/'.$f); }
function pay_panel($pdo,$o){
  $bank=setting_get($pdo,'bank_name')?:PAY_BANK_NAME; $acc=setting_get($pdo,'bank_acc')?:PAY_BANK_ACC; $own=setting_get($pdo,'bank_owner')?:PAY_BANK_OWNER; $bin=setting_get($pdo,'bank_bin')?:PAY_BANK_BIN; $img=setting_get($pdo,'qr_image'); $dyn=(setting_get($pdo,'qr_dynamic')==='1');
  $code=order_code($o['id']); $amt=(int)$o['total']; $qr=''; $note='';
  $dynimg=($bin!==''&&$acc!=='')?'<img class="qrimg" src="https://img.vietqr.io/image/'.rawurlencode($bin).'-'.rawurlencode($acc).'-compact2.png?amount='.$amt.'&amp;addInfo='.rawurlencode($code).'&amp;accountName='.rawurlencode($own).'" alt="Mã VietQR">':'';
  // Thứ tự ưu tiên: QR động (nếu quản trị bật) > ảnh QR quản trị tải lên > ảnh QR mặc định đi kèm website > QR động
  if($dyn&&$dynimg){ $qr=$dynimg; $note='Mã đã điền sẵn số tiền và nội dung chuyển khoản.'; }
  elseif(qr_ok($img)){ $qr='<img class="qrimg" src="uploads/'.e($img).'" alt="Mã QR chuyển khoản">'; $note='Sau khi quét mã, hãy nhập đúng số tiền và nội dung chuyển khoản bên cạnh.'; }
  elseif(PAY_QR_DEFAULT!==''&&is_file(__DIR__.'/'.PAY_QR_DEFAULT)){ $qr='<img class="qrimg" src="'.e(PAY_QR_DEFAULT).'" alt="Mã QR chuyển khoản '.e($bank).'">'; $note='Sau khi quét mã, hãy nhập đúng số tiền và nội dung chuyển khoản bên cạnh.'; }
  elseif($dynimg){ $qr=$dynimg; $note='Mã đã điền sẵn số tiền và nội dung chuyển khoản.'; }
  $h='<div class="panel payp"><h3>Thanh toán chuyển khoản</h3>';
  if($qr==='' && $acc===''){ return $h.'<p class="mut">Cửa hàng chưa cập nhật thông tin chuyển khoản. Vui lòng liên hệ hotline <b>'.e(SHOP_PHONE).'</b> để được hướng dẫn.</p></div>'; }
  $h.='<div class="payg">'.($qr?'<div class="qrbox">'.$qr.'<small>Quét bằng ứng dụng ngân hàng</small></div>':'').'<dl>';
  if($bank!=='') $h.='<dt>Ngân hàng</dt><dd>'.e($bank).'</dd>';
  if($acc!=='') $h.='<dt>Số tài khoản</dt><dd><b>'.e($acc).'</b></dd>';
  if($own!=='') $h.='<dt>Chủ tài khoản</dt><dd>'.e($own).'</dd>';
  $h.='<dt>Số tiền</dt><dd><b class="price">'.vnd($amt).'</b></dd><dt>Nội dung chuyển khoản</dt><dd><b class="code">'.e($code).'</b></dd></dl></div>';
  return $h.'<p class="mut" style="margin:10px 0 0;font-size:13px">'.($note?e($note).' ':'').'Đơn hàng được xác nhận sau khi cửa hàng nhận được tiền; hàng sẽ được giữ và trừ kho tại thời điểm đó.</p></div>'; }

// ===== Thanh tiến trình & khung tổng tiền =====
function steps($n){ $s=['Giỏ hàng','Thông tin giao hàng','Xác nhận đơn hàng']; $h='<ol class="steps">';
  foreach($s as $i=>$t){ $c=($i+1===$n)?'on':(($i+1<$n)?'done':''); $h.='<li class="'.$c.'"><i></i><span>'.e($t).'</span></li>'; } return $h.'</ol>'; }
function sum_panel($t,$btn=''){
  return '<div class="panel sumbox"><h3>Chi tiết thanh toán</h3><dl><dt>Tổng giá sản phẩm</dt><dd id="s_list">'.vnd($t['list']).'</dd><dt>Giảm giá sản phẩm</dt><dd class="neg" id="s_disc">-'.vnd($t['disc']).'</dd><dt>Tạm tính sau giảm</dt><dd id="s_sub">'.vnd($t['pay']).'</dd><dt>Phí vận chuyển</dt><dd id="s_ship">'.vnd($t['ship']).'</dd></dl><div class="stot"><span>Tổng tiền thanh toán</span><b id="s_total">'.vnd($t['total']).'</b></div><p class="save" id="s_save" '.($t['disc']>0?'':'hidden').'>Tiết kiệm '.vnd($t['disc']).'</p>'.$btn.'</div>'; }
