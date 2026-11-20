<?php require_once 'config.php'; $st=$pdo->prepare(PRODUCT_SQL.' WHERE p.id=?'); $st->execute([(int)gs('id')]); $p=$st->fetch();
$title=$p['name']??'Không tìm thấy'; if(!$p) http_response_code(404); require 'header.php';
if(!$p){ echo '<div class="panel">Không tìm thấy sản phẩm. <a href="products.php">Xem danh sách sản phẩm</a></div>'; require 'footer.php'; exit; }
$pc=discount_pct($p); $specs=spec_chips($p['description'],12,200);
$rs=$pdo->prepare(PRODUCT_SQL.' WHERE p.category_id=? AND p.id<>? ORDER BY ABS(p.price-?), p.id DESC LIMIT 5'); $rs->execute([(int)$p['category_id'],(int)$p['id'],(int)$p['price']]); $rel=$rs->fetchAll(); ?>
<p class="crumb"><a href="index.php">Trang chủ</a> › <a href="products.php?cat=<?= (int)$p['category_id'] ?>"><?= e($p['category']) ?></a> › <b><?= e($p['name']) ?></b></p>
<div class="two pd"><div class="thumb big"><?= product_flip($p,true) ?></div>
<div class="panel"><small class="mut"><?= e($p['brand']) ?> · <?= e($p['category']) ?></small><h1><?= e($p['name']) ?></h1>
<div class="pbox"><span class="price sale"><?= vnd($p['price']) ?></span><?php if($pc): ?><span class="old"><?= vnd($p['old_price']) ?></span><span class="tag inl">-<?= $pc ?>%</span><?php endif; ?></div>
<?php if($pc): ?><p class="save">Tiết kiệm <?= vnd($p['old_price']-$p['price']) ?></p><?php endif; ?>
<?php $si=stock_info($p); $oos=($si[0]==='out'); ?><p class="stk big <?= $si[0] ?>"><?= e($si[1]) ?><?php if($si[0]==='ok'||$si[0]==='low'): ?> <span class="mut" style="font-weight:400">(kho: <?= (int)$p['stock'] ?>)</span><?php endif; ?></p>
<?php if($specs): ?><h3>Cấu hình nổi bật</h3><ul class="specs"><?php foreach($specs as $s): ?><li><?= e($s) ?></li><?php endforeach; ?></ul><?php endif; ?>
<p class="pbtns"><?php if($oos): ?><button type="button" class="btn" disabled>Hết hàng</button><?php else: ?><button type="button" class="btn red cbuy" data-id="<?= (int)$p['id'] ?>">Mua ngay</button> <button type="button" class="btn ghost cadd" data-id="<?= (int)$p['id'] ?>"><?= ico('cartplus') ?> Thêm vào giỏ</button><?php endif; ?> <a class="btn ghost" href="tel:<?= e(preg_replace('/\D/','',SHOP_PHONE)) ?>">Gọi tư vấn: <?= e(SHOP_PHONE) ?></a></p>
<ul class="pol"><li>Hàng chính hãng</li><li>Bảo hành 12–36 tháng</li><li>1 đổi 1 trong 30 ngày đầu</li><li>Giao hàng toàn quốc</li></ul></div></div>
<div class="panel" style="margin-top:16px"><h2>Mô tả sản phẩm</h2><p><?= nl2br(e($p['description'])) ?></p></div>
<?php if($rel): ?><div class="sec-h"><h2>Sản phẩm cùng loại</h2><a href="products.php?cat=<?= (int)$p['category_id'] ?>">Xem tất cả ›</a></div><div class="grid"><?php foreach($rel as $r) echo card($r); ?></div><?php endif; ?>
<?php require 'footer.php'; ?>
