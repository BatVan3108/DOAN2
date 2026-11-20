<?php $title='Đặt hàng'; require 'header.php'; ensure_orders($pdo); ensure_customers($pdo);
if(!is_customer()||!($cu=cur_customer($pdo))){ header('Location: login.php?next=checkout.php'); exit; }
$sel=array_key_exists('co_sel',$_SESSION)?$_SESSION['co_sel']:null;
$lines=cart_lines($pdo,$sel); if(!$lines){ header('Location: cart.php'); exit; }
foreach($lines as $l){ if($l['oos']||$l['want']>$l['qty']){ $_SESSION['cart_msg']='Sản phẩm “'.$l['name'].'” '.($l['oos']?'đã hết hàng':'chỉ còn '.(int)$l['stock'].' sản phẩm trong kho').'. Vui lòng kiểm tra lại giỏ hàng.'; header('Location: cart.php'); exit; } }
$tot=cart_totals($lines); $ex=ord_extras(); $err='';
$step=(gs('step')==='3')?3:2; $info=$_SESSION['co_info']??null;
if($step===3&&!$info){ header('Location: checkout.php'); exit; }
$v=$info?:['name'=>$cu['full_name'],'phone'=>(string)$cu['phone'],'email'=>(string)$cu['email'],'receive'=>'home','addr'=>'','pay'=>'cod','ex'=>[],'note'=>'','invoice'=>0];
if($_SERVER['REQUEST_METHOD']==='POST'){ check_csrf(); $act=$_POST['act']??'';
  if($act==='info'){
    $v=['name'=>trim(preg_replace('/\s+/',' ',(string)($_POST['name']??''))),'phone'=>preg_replace('/[\s.\-]/','',(string)($_POST['phone']??'')),'email'=>trim((string)($_POST['email']??'')),
        'receive'=>(($_POST['receive']??'')==='store')?'store':'home','addr'=>trim((string)($_POST['addr']??'')),'pay'=>(($_POST['pay']??'')==='bank')?'bank':'cod',
        'ex'=>[],'note'=>mb_substr(trim((string)($_POST['note']??'')),0,500),'invoice'=>empty($_POST['invoice'])?0:1];
    foreach((array)($_POST['ex']??[]) as $k) if(isset($ex[$k])) $v['ex'][]=$k;
    if(mb_strlen($v['name'])<2||mb_strlen($v['name'])>100) $err='Vui lòng nhập họ và tên người đặt hàng (2–100 ký tự).';
    elseif(!preg_match('/^0\d{9}$/',$v['phone'])) $err='Số điện thoại phải gồm 10 số và bắt đầu bằng 0.';
    elseif($v['email']!==''&&(!filter_var($v['email'],FILTER_VALIDATE_EMAIL)||strlen($v['email'])>120)) $err='Email không hợp lệ.';
    elseif($v['receive']==='home'&&(mb_strlen($v['addr'])<5||mb_strlen($v['addr'])>255)) $err='Vui lòng nhập địa chỉ nhận hàng (5–255 ký tự).';
    else{ $_SESSION['co_info']=$v; header('Location: checkout.php?step=3'); exit; } }
  elseif($act==='place'&&$info){
    $addr=$info['receive']==='store'?'Nhận tại cửa hàng '.SHOP_NAME:$info['addr']; $first=$lines[0];
    $exs=implode('; ',array_map(function($k) use($ex){ return $ex[$k]; },$info['ex']));
    $stt=($info['pay']==='bank')?'cho_tt':'moi';
    try{ $pdo->beginTransaction();
      $pdo->prepare('INSERT INTO orders(product_id,qty,customer,phone,email,address,note,customer_id,total,receive,ship,pay,extras,invoice,status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
          ->execute([$first['id'],$first['qty'],$info['name'],$info['phone'],$info['email'],$addr,$info['note'],$cu['id'],$tot['total'],$info['receive'],$info['receive']==='home'?'standard':null,$info['pay'],$exs,$info['invoice'],$stt]);
      $oid=(int)$pdo->lastInsertId();
      $ins=$pdo->prepare('INSERT INTO order_items(order_id,product_id,qty,price,old_price,name) VALUES(?,?,?,?,?,?)');
      foreach($lines as $l) $ins->execute([$oid,$l['id'],$l['qty'],$l['price'],$l['old_price']?:null,$l['name']]);
      if($stt==='moi') order_stock_take($pdo,$oid);   // COD: trừ kho ngay; chuyển khoản: trừ khi admin xác nhận đã nhận tiền
      $pdo->commit();
    }catch(Exception $x){ if($pdo->inTransaction()) $pdo->rollBack(); $err=($x instanceof PDOException)?'Không thể lưu đơn hàng, vui lòng thử lại.':$x->getMessage(); }
    if(!$err){ $c=cart_all(); foreach($lines as $l) unset($c[(int)$l['id']]); $_SESSION['cart']=$c; unset($_SESSION['co_sel'],$_SESSION['co_info']); $_SESSION['last_order']=$oid; header('Location: order_done.php?id='.$oid); exit; } } }
function co_items($lines){ $h='<div class="panel"><h3>Sản phẩm trong đơn ('.count($lines).')</h3>'; foreach($lines as $l){ $h.='<div class="oit"><span class="cth">'.product_visual($l).'</span><div class="cinfo"><a href="product.php?id='.(int)$l['id'].'">'.e($l['name']).'</a><small>'.e($l['brand']).'</small></div><span class="mut">x'.(int)$l['qty'].'</span><div class="cprice">'.((int)$l['old_price']>(int)$l['price']?'<s>'.vnd($l['old_price']*$l['qty']).'</s>':'').'<b>'.vnd($l['price']*$l['qty']).'</b></div></div>'; } return $h.'</div>'; }
?>
<?= steps($step) ?>
<?php if($err) echo '<div class="msg err">'.e($err).'</div>'; ?>
<?php if($step===2): ?>
<div class="cart2"><form id="coform" method="post" class="coform"><input type="hidden" name="t" value="<?= csrf() ?>"><input type="hidden" name="act" value="info">
<?= co_items($lines) ?>
<div class="panel"><h3>Thông tin người đặt hàng</h3><p class="mut" style="margin:0 0 6px;font-size:13px"><i class="req">*</i> Trường thông tin bắt buộc điền</p>
<label for="c1">Họ và tên <i class="req">*</i></label><input id="c1" name="name" maxlength="100" value="<?= e($v['name']) ?>" required>
<label for="c2">Số điện thoại <i class="req">*</i></label><input id="c2" name="phone" type="tel" inputmode="numeric" placeholder="Ví dụ: 0901234567" value="<?= e($v['phone']) ?>" required>
<label for="c3">Email</label><input id="c3" name="email" type="email" maxlength="120" value="<?= e($v['email']) ?>"></div>
<div class="panel"><h3>Hình thức nhận hàng</h3>
<label class="opt"><input type="radio" name="receive" value="home" <?= $v['receive']==='home'?'checked':'' ?>> Giao hàng tận nơi</label>
<label class="opt"><input type="radio" name="receive" value="store" <?= $v['receive']==='store'?'checked':'' ?>> Nhận tại cửa hàng (<?= e(SHOP_NAME) ?>)</label>
<div id="addrbox"><label for="c4">Địa chỉ nhận hàng <i class="req">*</i></label><input id="c4" name="addr" maxlength="255" placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành phố" value="<?= e($v['addr']) ?>"></div></div>
<div class="panel" id="shipbox"><h3>Phương thức vận chuyển</h3><label class="opt"><input type="radio" name="ship" value="standard" checked> Vận chuyển tiêu chuẩn <b class="fr">Miễn phí</b></label></div>
<div class="panel"><h3>Yêu cầu đặc biệt</h3><?php foreach($ex as $k=>$t): ?><label class="opt plain"><input type="checkbox" name="ex[]" value="<?= e($k) ?>" <?= in_array($k,$v['ex'],true)?'checked':'' ?>> <?= e($t) ?></label><?php endforeach; ?></div>
<div class="panel"><h3>Ghi chú đơn hàng</h3><p class="mut" style="margin:0 0 8px;font-size:13px">Thêm yêu cầu giao hàng, xuất kho hoặc thời gian liên hệ nếu cần.</p><textarea name="note" rows="4" maxlength="500" placeholder="Ví dụ: Gọi trước khi giao, giao sau 18h..."><?= e($v['note']) ?></textarea></div>
<div class="panel"><h3>Chọn hình thức thanh toán</h3>
<label class="opt"><input type="radio" name="pay" value="cod" <?= $v['pay']==='cod'?'checked':'' ?>> <span>COD<br><small class="mut">Thu tiền khi giao hàng</small></span></label>
<label class="opt"><input type="radio" name="pay" value="bank" <?= $v['pay']==='bank'?'checked':'' ?>> <span>Chuyển khoản ngân hàng<br><small class="mut">Nhân viên sẽ liên hệ hướng dẫn chuyển khoản</small></span></label></div>
<p class="cnext"><a class="btn dark" href="cart.php">‹ Quay lại</a><button class="btn red" type="submit">Tiếp theo <?= ico('arrow') ?></button></p></form>
<div><div class="panel invbox"><label class="opt plain" style="margin:0"><input type="checkbox" name="invoice" value="1" form="coform" <?= $v['invoice']?'checked':'' ?>> Yêu cầu xuất hóa đơn điện tử</label></div><?= sum_panel($tot) ?></div></div>
<script>(function(){var r=document.querySelectorAll('input[name=receive]'),a=document.getElementById('addrbox'),s=document.getElementById('shipbox'),i=document.getElementById('c4');
function u(){var h=document.querySelector('input[name=receive]:checked').value==='home';a.hidden=!h;s.hidden=!h;i.required=h}
for(var k=0;k<r.length;k++)r[k].onchange=u;u()})();</script>
<?php else: ?>
<div class="cart2"><div class="coform">
<?= co_items($lines) ?>
<div class="panel rv"><h3>Thông tin người đặt hàng</h3><dl><dt>Họ và tên</dt><dd><?= e($info['name']) ?></dd><dt>Số điện thoại</dt><dd><?= e($info['phone']) ?></dd><dt>Email</dt><dd><?= $info['email']!==''?e($info['email']):'—' ?></dd></dl></div>
<div class="panel rv"><h3>Nhận hàng và thanh toán</h3><dl><dt>Hình thức nhận hàng</dt><dd><?= e(ord_label($info['receive'])) ?></dd><dt>Địa chỉ</dt><dd><?= $info['receive']==='store'?e('Nhận tại cửa hàng '.SHOP_NAME):e($info['addr']) ?></dd>
<?php if($info['receive']==='home'): ?><dt>Vận chuyển</dt><dd><?= e(ord_label('standard')) ?> (Miễn phí)</dd><?php endif; ?>
<dt>Thanh toán</dt><dd><?= e(ord_label($info['pay'])) ?></dd><dt>Yêu cầu đặc biệt</dt><dd><?= $info['ex']?e(implode('; ',array_map(function($k) use($ex){return $ex[$k];},$info['ex']))):'Không' ?></dd><dt>Hóa đơn điện tử</dt><dd><?= $info['invoice']?'Có':'Không' ?></dd><dt>Ghi chú</dt><dd><?= $info['note']!==''?nl2br(e($info['note'])):'—' ?></dd></dl></div>
<p class="cnext"><a class="btn dark" href="checkout.php">‹ Quay lại</a><span></span></p></div>
<div><?= sum_panel($tot,'<form method="post"><input type="hidden" name="t" value="'.csrf().'"><input type="hidden" name="act" value="place"><button class="abtn" type="submit">Đặt mua hàng</button></form><p class="mut" style="font-size:12.5px;margin:10px 0 0">Nhấn “Đặt mua hàng” là bạn xác nhận thông tin đơn hàng ở trên.'.($info['pay']==='bank'?' Với chuyển khoản, sau khi đặt bạn sẽ thấy mã QR; đơn được xác nhận khi cửa hàng nhận được tiền.':' Nhân viên sẽ gọi xác nhận trong ngày.').'</p>') ?></div></div>
<?php endif; ?>
<?php require 'footer.php'; ?>
