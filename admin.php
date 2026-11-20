<?php $title='Quản trị'; require 'header.php'; require_admin(); ensure_orders($pdo);
$err=''; $msg=''; $acts=['confirm'=>'Đã xác nhận thanh toán và trừ kho.','done'=>'Đã chuyển đơn sang hoàn tất.','cancel'=>'Đã hủy đơn (kho được hoàn lại nếu đã trừ).'];
if($_SERVER['REQUEST_METHOD']==='POST'){ check_csrf(); $a=$_POST['act']??'';
  $e=order_change($pdo,(int)($_POST['id']??0),$a);
  if($e===''){ header('Location: admin.php'.(gs('st')!==''?'?st='.urlencode(gs('st')).'&':'?').'ok='.urlencode($a)); exit; } $err=$e; }
if(isset($acts[gs('ok')])) $msg=$acts[gs('ok')];
$flt=gs('st'); if(!in_array($flt,['cho_tt','moi','daxuly','huy'],true)) $flt='';
$stat=$pdo->query("SELECT COUNT(*) AS total, COALESCE(SUM(status='cho_tt'),0) AS wait, COALESCE(SUM(status='moi'),0) AS moi FROM orders")->fetch();
$low=(int)$pdo->query('SELECT COUNT(*) FROM products WHERE stock<=5')->fetchColumn();
$qs=$pdo->prepare('SELECT o.*, COALESCE(p.name,\'(sản phẩm đã xóa)\') AS product, COALESCE(p.price,0) AS price FROM orders o LEFT JOIN products p ON p.id=o.product_id'.($flt?' WHERE o.status=?':'').' ORDER BY o.id DESC'); $qs->execute($flt?[$flt]:[]); $o=$qs->fetchAll();
$its=[]; if($o){ $in=implode(',',array_map('intval',array_column($o,'id'))); foreach($pdo->query("SELECT oi.order_id,oi.qty,oi.price,COALESCE(oi.name,p.name) AS name FROM order_items oi LEFT JOIN products p ON p.id=oi.product_id WHERE oi.order_id IN ($in)") as $x) $its[$x['order_id']][]=$x; }
function adm_btn($id,$act,$label,$cls='ghost',$ask=''){ return '<form method="post"'.($ask?' onsubmit="return confirm(\''.$ask.'\')"':'').'><input type="hidden" name="t" value="'.csrf().'"><input type="hidden" name="id" value="'.(int)$id.'"><button class="btn '.$cls.'" name="act" value="'.$act.'">'.$label.'</button></form>'; } ?>
<?= admin_nav('admin.php') ?>
<h2>Đơn hàng</h2>
<?php if($msg) echo '<div class="msg">'.e($msg).'</div>'; if($err) echo '<div class="msg err">'.e($err).'</div>'; ?>
<div class="stats stats4"><div><b><?= (int)$stat['total'] ?></b><span>Tổng đơn</span></div><div><b><?= (int)$stat['wait'] ?></b><span>Chờ xác nhận thanh toán</span></div><div><b><?= (int)$stat['moi'] ?></b><span>Đã tiếp nhận, chưa hoàn tất</span></div><div class="<?= $low?'warn':'' ?>"><b><?= $low ?></b><span><a href="admin_products.php?low=1">Sản phẩm sắp hết hàng (≤5)</a></span></div></div>
<p class="fchips"><a class="chip <?= $flt===''?'on':'' ?>" href="admin.php">Tất cả</a><a class="chip <?= $flt==='cho_tt'?'on':'' ?>" href="admin.php?st=cho_tt">Chờ thanh toán</a><a class="chip <?= $flt==='moi'?'on':'' ?>" href="admin.php?st=moi">Đã tiếp nhận</a><a class="chip <?= $flt==='daxuly'?'on':'' ?>" href="admin.php?st=daxuly">Hoàn tất</a><a class="chip <?= $flt==='huy'?'on':'' ?>" href="admin.php?st=huy">Đã hủy</a></p>
<div class="panel scroll">
<?php if(!$o): ?>Chưa có đơn nào.
<?php else: ?>
<table><tr><th>Mã</th><th>Khách</th><th>SĐT / Email</th><th>Địa chỉ / Thanh toán</th><th>Sản phẩm</th><th>SL</th><th>Tổng</th><th>Ngày</th><th>Trạng thái</th><th></th></tr>
<?php foreach($o as $r): $ln=$its[$r['id']]??[]; ?>
<tr><td><?= e(order_code($r['id'])) ?></td><td><?= e($r['customer']) ?></td><td><?= e($r['phone']) ?><?php if($r['email']): ?><br><small class="mut"><?= e($r['email']) ?></small><?php endif; ?></td>
<td><?= e($r['address']) ?><?php if($r['receive']): ?><br><small class="mut"><?= e(ord_label($r['receive'])) ?> · <?= e(ord_label($r['pay'])) ?><?= $r['extras']?' · '.e($r['extras']):'' ?><?= $r['invoice']?' · Xuất hóa đơn':'' ?></small><?php endif; ?><?php if($r['note']): ?><br><small class="mut"><?= e($r['note']) ?></small><?php endif; ?></td>
<td><?php if($ln): foreach($ln as $x) echo e($x['name']).' × '.(int)$x['qty'].'<br>'; else: echo e($r['product']); endif; ?></td><td><?= $ln?array_sum(array_column($ln,'qty')):(int)$r['qty'] ?></td><td><?= $r['total']!==null?vnd($r['total']):vnd($r['price']*$r['qty']) ?></td><td><?= e($r['created_at']) ?><?php if($r['paid_at']): ?><br><small class="mut">Nhận tiền: <?= e($r['paid_at']) ?></small><?php endif; ?></td>
<td><?= ord_badge($r['status']) ?><?php if((int)$r['stock_deducted']): ?><br><small class="mut">Đã trừ kho</small><?php endif; ?></td>
<td class="act"><?php if($r['status']==='cho_tt'): echo adm_btn($r['id'],'confirm','Đã nhận tiền','red','Xác nhận đã nhận tiền? Hệ thống sẽ trừ kho cho đơn này.'), adm_btn($r['id'],'cancel','Hủy','alt','Hủy đơn này?');
 elseif($r['status']==='moi'): echo adm_btn($r['id'],'done','Hoàn tất'), adm_btn($r['id'],'cancel','Hủy','alt','Hủy đơn này? Kho sẽ được hoàn lại.');
 elseif($r['status']==='daxuly'): echo adm_btn($r['id'],'cancel','Hủy','alt','Hủy đơn đã hoàn tất? Kho sẽ được hoàn lại.');
 else: echo '—'; endif; ?></td></tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</div>
<?php require 'footer.php'; ?>
