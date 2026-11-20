<?php $title='Đơn hàng của tôi'; require 'header.php'; ensure_orders($pdo); ensure_customers($pdo);
if(!is_customer()){ header('Location: login.php'); exit; }
$st=$pdo->prepare('SELECT * FROM orders WHERE customer_id=? ORDER BY id DESC LIMIT 100'); $st->execute([(int)$_SESSION['cid']]); $rows=$st->fetchAll();
$items=[]; if($rows){ $in=implode(',',array_map('intval',array_column($rows,'id')));
  foreach($pdo->query("SELECT oi.order_id,oi.qty,COALESCE(oi.name,p.name) AS name FROM order_items oi LEFT JOIN products p ON p.id=oi.product_id WHERE oi.order_id IN ($in) ORDER BY oi.id") as $x) $items[$x['order_id']][]=$x; } ?>
<p class="crumb"><a href="index.php">Trang chủ</a> › <b>Đơn hàng của tôi</b></p>
<h2>Đơn hàng của tôi</h2>
<?php if(!$rows): ?><div class="panel" style="text-align:center;padding:40px"><p class="mut">Bạn chưa có đơn hàng nào.</p><a class="btn" href="products.php">Mua sắm ngay</a></div>
<?php else: foreach($rows as $r): $li=$items[$r['id']]??[]; ?>
<div class="panel ohist"><div class="oh"><div><b class="code"><?= e(order_code($r['id'])) ?></b> <span class="mut">· <?= e(date('d/m/Y H:i',strtotime($r['created_at']))) ?></span></div><?= ord_badge($r['status']) ?></div>
<ul class="oli"><?php foreach(array_slice($li,0,3) as $x): ?><li><?= e($x['name']) ?> <span class="mut">× <?= (int)$x['qty'] ?></span></li><?php endforeach; ?><?php if(count($li)>3): ?><li class="mut">và <?= count($li)-3 ?> sản phẩm khác</li><?php endif; ?><?php if(!$li): ?><li class="mut">Đơn đặt trước khi có lịch sử chi tiết</li><?php endif; ?></ul>
<div class="oh"><span><?= e(ord_label($r['pay'])) ?></span><span>Tổng: <b class="price"><?= vnd($r['total']) ?></b> <a class="btn ghost sm" href="my_order.php?id=<?= (int)$r['id'] ?>" style="margin-left:10px">Xem chi tiết</a></span></div></div>
<?php endforeach; endif; ?>
<?php require 'footer.php'; ?>
