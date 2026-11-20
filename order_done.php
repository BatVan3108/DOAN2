<?php $title='Đặt hàng thành công'; require 'header.php'; ensure_orders($pdo); ensure_customers($pdo);
if(!is_customer()){ header('Location: login.php'); exit; }
$id=(int)gs('id'); $q=$pdo->prepare('SELECT * FROM orders WHERE id=? AND customer_id=?'); $q->execute([$id,(int)$_SESSION['cid']]); $o=$q->fetch();
if(!$o){ header('Location: index.php'); exit; }
$it=$pdo->prepare('SELECT oi.qty,oi.price,COALESCE(oi.name,p.name) AS name FROM order_items oi LEFT JOIN products p ON p.id=oi.product_id WHERE oi.order_id=?'); $it->execute([$id]); $items=$it->fetchAll();
$bank=($o['pay']==='bank'&&$o['status']==='cho_tt'); ?>
<?= steps(4) ?>
<div class="panel done"><div class="okc"><?= ico('check') ?></div><h2>Đặt hàng thành công!</h2>
<p>Mã đơn hàng của bạn: <b class="code"><?= e(order_code($o['id'])) ?></b> · <?= ord_badge($o['status']) ?></p>
<p class="mut"><?= $bank?'Vui lòng chuyển khoản theo hướng dẫn bên dưới. Đơn sẽ được xác nhận khi cửa hàng nhận được tiền.':'Nhân viên sẽ gọi điện xác nhận đơn trong ngày.' ?> Hotline hỗ trợ: <b><?= e(SHOP_PHONE) ?></b></p>
<table class="dt"><tr><th>Sản phẩm</th><th>SL</th><th>Thành tiền</th></tr><?php foreach($items as $x): ?><tr><td><?= e($x['name']) ?></td><td><?= (int)$x['qty'] ?></td><td><?= vnd($x['price']*$x['qty']) ?></td></tr><?php endforeach; ?><tr><td colspan="2"><b>Tổng thanh toán</b></td><td><b class="price"><?= vnd($o['total']) ?></b></td></tr></table>
<dl class="rvd"><dt>Người nhận</dt><dd><?= e($o['customer']) ?> · <?= e($o['phone']) ?></dd><dt>Nhận hàng</dt><dd><?= e(ord_label($o['receive'])) ?> — <?= e($o['address']) ?></dd><dt>Thanh toán</dt><dd><?= e(ord_label($o['pay'])) ?></dd></dl>
<?php if($bank) echo '<div style="text-align:left">'.pay_panel($pdo,$o).'</div>'; ?>
<p><a class="btn red" href="my_order.php?id=<?= (int)$o['id'] ?>">Xem đơn hàng của tôi</a> <a class="btn ghost" href="products.php">Tiếp tục mua sắm</a></p></div>
<?php require 'footer.php'; ?>
