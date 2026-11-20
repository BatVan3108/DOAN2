<?php $title='Quản lý sản phẩm'; require 'header.php'; require_admin(); $msg=''; $err='';
if(gs('ok')!=='') $msg=gs('ok')==='add'?'Đã thêm sản phẩm.':'Đã lưu thay đổi.';
if($_SERVER['REQUEST_METHOD']==='POST'){ check_csrf(); $id=(int)($_POST['id']??0);
  try{ $pdo->prepare('DELETE FROM products WHERE id=?')->execute([$id]);
    foreach(glob(__DIR__.'/images/'.$id.'.*') ?: [] as $f) @unlink($f);
    foreach(glob(__DIR__.'/images/'.$id.'_2.*') ?: [] as $f) @unlink($f);
    $msg='Đã xóa sản phẩm.';
  }catch(PDOException $ex){ $err='Không xóa được: sản phẩm này đã có đơn đăng ký mua. Hãy sửa thông tin thay vì xóa.'; } }
$rows=$pdo->query(PRODUCT_SQL.' ORDER BY p.id DESC')->fetchAll();
$lowc=0; foreach($rows as $r0) if((int)$r0['stock']<=5) $lowc++;
$onlyLow=(gs('low')==='1'); if($onlyLow) $rows=array_values(array_filter($rows,function($r){ return (int)$r['stock']<=5; })); ?>
<?= admin_nav('admin_products.php') ?>
<h2>Quản lý sản phẩm</h2>
<p><a class="btn" href="admin_product_edit.php">+ Thêm sản phẩm</a></p>
<?php if($lowc): ?><div class="msg err"><b><?= $lowc ?> sản phẩm sắp hết hoặc đã hết hàng</b> (tồn kho ≤ 5). <?php if($onlyLow): ?><a href="admin_products.php">Xem tất cả sản phẩm</a><?php else: ?><a href="admin_products.php?low=1">Chỉ hiện các sản phẩm này</a><?php endif; ?></div><?php endif; ?>
<?php if($msg) echo '<div class="msg">'.e($msg).'</div>'; if($err) echo '<div class="msg err">'.e($err).'</div>'; ?>
<div class="panel scroll"><table><tr><th>Mã</th><th>Ảnh</th><th>Tên sản phẩm</th><th>Loại</th><th>Hãng</th><th>Giá</th><th>Tồn kho</th><th>Mới</th><th></th></tr>
<?php foreach($rows as $r): ?>
<tr><td><?= $r['id'] ?></td><td><div class="thumb mini"><?= product_visual($r) ?></div></td><td><?= e($r['name']) ?></td><td><?= e($r['category']) ?></td><td><?= e($r['brand']) ?></td>
<td><?= vnd($r['price']) ?><?php if($r['old_price']): ?><br><span class="old" style="margin:0"><?= vnd($r['old_price']) ?></span><?php endif; ?></td><td><b class="stk <?= stock_info($r)[0] ?>"><?= (int)$r['stock'] ?></b><br><small class="mut"><?= e(stock_info($r)[1]) ?></small></td><td><?= $r['is_new']?'✔':'' ?></td>
<td class="act"><a class="btn ghost" href="admin_product_edit.php?id=<?= (int)$r['id'] ?>">Sửa</a>
<form method="post" onsubmit="return confirm('Xóa sản phẩm này?')"><input type="hidden" name="t" value="<?= csrf() ?>"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn alt" type="submit">Xóa</button></form></td></tr>
<?php endforeach; ?></table></div>
<?php require 'footer.php'; ?>
