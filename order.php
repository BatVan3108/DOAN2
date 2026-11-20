<?php // Trang đăng ký mua cũ đã được thay bằng giỏ hàng. Liên kết order.php?id=<mã> sẽ thêm sản phẩm vào giỏ rồi mở giỏ hàng.
require_once 'config.php'; $id=(int)gs('id');
if($id){ $q=$pdo->prepare('SELECT 1 FROM products WHERE id=?'); $q->execute([$id]); if($q->fetch()){ $c=cart_all(); $c[$id]=min(50,($c[$id]??0)+1); $_SESSION['cart']=$c; } }
header('Location: cart.php'); exit;
