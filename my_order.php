<?php $title='Chi tiết đơn hàng'; require 'header.php'; ensure_orders($pdo); ensure_customers($pdo);
if(!is_customer()){ header('Location: login.php'); exit; }
$id=(int)gs('id'); $cid=(int)$_SESSION['cid']; $msg=''; $err='';
$q=$pdo->prepare('SELECT * FROM orders WHERE id=? AND customer_id=?'); $q->execute([$id,$cid]); $o=$q->fetch(); if(!$o){ header('Location: my_orders.php'); exit; }
if($_SERVER['REQUEST_METHOD']==='POST'){ check_csrf();
  // Khách chỉ được tự hủy đơn chuyển khoản chưa được xác nhận thanh toán
  if(($_POST['act']??'')==='cancel'&&$o['status']==='cho_tt'){ $err=order_change($pdo,$id,'cancel'); if($err==='') $msg='Đã hủy đơn hàng.'; $q->execute([$id,$cid]); $o=$q->fetch(); } }
$it=$pdo->prepare('SELECT oi.qty,oi.price,oi.old_price,COALESCE(oi.name,p.name) AS name FROM order_items oi LEFT JOIN products p ON p.id=oi.product_id WHERE oi.order_id=? ORDER BY oi.id'); $it->execute([$id]); $items=$it->fetchAll();
$list=0; foreach($items as $x) $list+=max((int)$x['old_price'],(int)$x['price'])*$x['qty']; ?>
<p class="crumb"><a href="index.php">Trang chủ</a> › <a href="my_orders.php">Đơn hàng của tôi</a> › <b><?= e(order_code($o['id'])) ?></b></p>
<div class="sec-h" style="margin-top:4px"><h2>Đơn hàng <span class="code"><?= e(order_code($o['id'])) ?></span></h2><?= ord_badge($o['status']) ?></div>
<?php if($msg) echo '<div class="msg">'.e($msg).'</div>'; if($err) echo '<div class="msg err">'.e($err).'</div>'; ?>
<div class="cart2"><div class="coform">
<div class="panel"><h3>Sản phẩm</h3><table class="dt"><tr><th>Sản phẩm</th><th>SL</th><th>Đơn giá</th><th>Thành tiền</th></tr><?php foreach($items as $x): ?><tr><td><?= e($x['name']) ?></td><td><?= (int)$x['qty'] ?></td><td><?= vnd($x['price']) ?></td><td><?= vnd($x['price']*$x['qty']) ?></td></tr><?php endforeach; ?><?php if(!$items): ?><tr><td colspan="4" class="mut">Đơn đặt trước khi có lịch sử chi tiết.</td></tr><?php endif; ?></table></div>
<div class="panel rv"><h3>Thông tin đơn hàng</h3><dl><dt>Ngày đặt</dt><dd><?= e(date('d/m/Y H:i',strtotime($o['created_at']))) ?></dd><dt>Người nhận</dt><dd><?= e($o['customer']) ?> · <?= e($o['phone']) ?></dd><?php if($o['email']): ?><dt>Email</dt><dd><?= e($o['email']) ?></dd><?php endif; ?>
<dt>Hình thức nhận hàng</dt><dd><?= e(ord_label($o['receive'])) ?></dd><dt>Địa chỉ</dt><dd><?= e($o['address']) ?></dd><dt>Thanh toán</dt><dd><?= e(ord_label($o['pay'])) ?><?= $o['paid_at']?' · đã nhận tiền '.e(date('d/m/Y H:i',strtotime($o['paid_at']))):'' ?></dd>
<?php if($o['extras']): ?><dt>Yêu cầu đặc biệt</dt><dd><?= e($o['extras']) ?></dd><?php endif; ?><?php if($o['invoice']): ?><dt>Hóa đơn điện tử</dt><dd>Có yêu cầu</dd><?php endif; ?><?php if($o['note']): ?><dt>Ghi chú</dt><dd><?= nl2br(e($o['note'])) ?></dd><?php endif; ?></dl></div>
<?php if($o['pay']==='bank'&&$o['status']==='cho_tt') echo pay_panel($pdo,$o); ?>
<p class="cnext"><a class="btn dark" href="my_orders.php">‹ Tất cả đơn hàng</a><?php if($o['status']==='cho_tt'): ?><form method="post" onsubmit="return confirm('Hủy đơn hàng này?')" style="margin:0"><input type="hidden" name="t" value="<?= csrf() ?>"><input type="hidden" name="act" value="cancel"><button class="btn ghost del" type="submit">Hủy đơn</button></form><?php else: ?><span></span><?php endif; ?></p></div>
<div><?= sum_panel(['list'=>$list?:(int)$o['total'],'disc'=>max(0,($list?:(int)$o['total'])-(int)$o['total']),'pay'=>(int)$o['total'],'ship'=>0,'total'=>(int)$o['total']]) ?></div></div>
<?php require 'footer.php'; ?>
