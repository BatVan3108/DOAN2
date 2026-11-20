<?php $title='Trang chủ'; require 'header.php';
$tb=$pdo->query("SELECT * FROM announcements WHERE type='thongbao' ORDER BY created_at DESC, id DESC LIMIT 5")->fetchAll();
$km=$pdo->query("SELECT * FROM announcements WHERE type='khuyenmai' ORDER BY created_at DESC, id DESC LIMIT 5")->fetchAll();
$new=$pdo->query(PRODUCT_SQL.' ORDER BY p.is_new DESC, p.created_at DESC, p.id DESC LIMIT 18')->fetchAll();
$sale=$pdo->query(PRODUCT_SQL.' WHERE p.old_price IS NOT NULL AND p.old_price>p.price ORDER BY (p.old_price-p.price)/p.old_price DESC, p.id DESC LIMIT 5')->fetchAll();
$allb=$pdo->query('SELECT * FROM brands ORDER BY name')->fetchAll();
$slides=array_slice($km,0,3);
if(!$slides) $slides=[['title'=>'Máy tính chính hãng, giá rõ ràng','content'=>'Laptop, PC, màn hình và phụ kiện từ các hãng uy tín. Đăng ký mua online, nhân viên gọi xác nhận trong ngày.']]; ?>
<?php $arts=['art_laptop','art_pc','art_head']; $badges=['Laptop cao cấp','PC Gaming','Phụ kiện chính hãng']; ?>
<div class="hero2"><div class="slider">
<?php foreach($slides as $si=>$sv): $fn=$arts[$si%3]; ?><div class="slide <?= $si===0?'on':'' ?>"><div class="stxt"><span class="pill-b"><?= e($badges[$si%3]) ?></span><h1><?= e($sv['title']) ?></h1><p><?= e($sv['content']) ?></p><div class="sbtn"><a class="btn" href="products.php">Mua ngay <?= ico('arrow') ?></a><a class="btn ghost" href="products.php?sort=sale">Xem chi tiết</a></div></div>
<div class="sart"><?= $fn('h'.$si) ?></div>
<ul class="sfeat"><li><?= ico('cpu') ?><span>CPU thế hệ mới<small>Intel / AMD</small></span></li><li><?= ico('screen') ?><span>Màn hình sắc nét<small>FHD / 2.5K / 4K</small></span></li><li><?= ico('shield') ?><span>Bảo hành chính hãng<small>12 – 36 tháng</small></span></li></ul></div><?php endforeach; ?>
<?php if(count($slides)>1): ?><button class="arr prev" type="button" aria-label="Trước">‹</button><button class="arr next" type="button" aria-label="Sau">›</button>
<div class="dots"><?php foreach($slides as $si=>$sv): ?><button type="button" class="<?= $si===0?'on':'' ?>" aria-label="Slide <?= $si+1 ?>"></button><?php endforeach; ?></div><?php endif; ?>
</div></div>
<div class="banners">
<a class="bn bn1" href="products.php?cat=2"><div><span class="pill-b">PC Gaming – Build cấu hình theo yêu cầu</span><h3>Chiến game mượt mà<br>Đồ họa đỉnh cao</h3><span class="btn sm">Khám phá ngay <?= ico('arrow') ?></span></div><div class="bart"><?= art_pc('p1') ?></div></a>
<a class="bn bn2" href="products.php?cat=5"><div><span class="pill-b">Phụ kiện chính hãng</span><h3>Phụ kiện chất lượng<br>Nâng tầm trải nghiệm</h3><span class="btn sm">Xem sản phẩm <?= ico('arrow') ?></span></div><div class="bart"><?= art_head('p2') ?></div></a>
</div>
<div class="feat"><div><i><?= ico('truck') ?></i><span><b>Giao hàng toàn quốc</b><small>Nhận hàng rồi trả tiền</small></span></div><div><i><?= ico('shield') ?></i><span><b>Bảo hành chính hãng</b><small>Từ 12 – 36 tháng</small></span></div><div><i><?= ico('head') ?></i><span><b>Hỗ trợ nhanh chóng</b><small>Hotline <?= e(SHOP_PHONE) ?></small></span></div><div><i><?= ico('wallet') ?></i><span><b>Thanh toán linh hoạt</b><small>COD, chuyển khoản, thẻ</small></span></div></div>

<div class="sec-h" style="margin-top:30px"><h2>Danh mục nổi bật</h2><div class="pills"><button type="button" class="on" data-cat="all">Tất cả</button><?php foreach($h_cats as $hc): ?><button type="button" data-cat="<?= (int)$hc['id'] ?>"><?= e($hc['name']) ?></button><?php endforeach; ?></div></div>
<div class="sec-h"><h2>Sản phẩm mới nhất</h2><a class="more" href="products.php">Xem tất cả <?= ico('arrow') ?></a></div>
<div class="grid" id="fgrid"><?php foreach($new as $p) echo card($p); ?></div><p class="mut" id="fempty" hidden>Chưa có sản phẩm mới trong danh mục này.</p>

<?php if($sale): ?><div class="sec-h"><h2>Đang giảm giá</h2><a class="more" href="products.php?sort=sale">Xem tất cả <?= ico('arrow') ?></a></div>
<div class="grid"><?php foreach($sale as $p) echo card($p); ?></div><?php endif; ?>

<?php foreach($h_cats as $hc):
  $st=$pdo->prepare(PRODUCT_SQL.' WHERE p.category_id=? ORDER BY p.is_new DESC, p.id DESC LIMIT 5'); $st->execute([$hc['id']]); $rows=$st->fetchAll();
  if(!$rows) continue; ?>
<div class="sec-h"><h2><?= e($hc['name']) ?></h2><div class="tabs"><?php foreach($h_cb[$hc['id']]??[] as $hb): ?><a href="products.php?cat=<?= (int)$hc['id'] ?>&amp;brand=<?= (int)$hb['bid'] ?>"><?= e($hb['name']) ?></a><?php endforeach; ?><a class="all" href="products.php?cat=<?= (int)$hc['id'] ?>">Xem tất cả ›</a></div></div>
<div class="grid"><?php foreach($rows as $p) echo card($p); ?></div>
<?php endforeach; ?>

<div class="sec-h"><h2>Thương hiệu nổi bật</h2><a class="more" href="products.php">Xem tất cả <?= ico('arrow') ?></a></div>
<div class="brands"><?php foreach($allb as $b): $bi=brand_img($b['name']); ?><a href="products.php?brand=<?= (int)$b['id'] ?>" title="<?= e($b['name']) ?>"><?= $bi?:'<span>'.e($b['name']).'</span>' ?></a><?php endforeach; ?></div>

<?php require 'footer.php'; ?>
