<?php $title='Khuyến mãi'; require 'header.php'; ensure_ann($pdo); $adm=is_admin();
$msg=$adm?(['add'=>'Đã thêm.','save'=>'Đã lưu thay đổi.','del'=>'Đã xóa.'][gs('ok')]??''):'';
$id=(int)gs('id');
if($id){ $s=$pdo->prepare("SELECT * FROM announcements WHERE id=? AND type='khuyenmai'"); $s->execute([$id]); $r=$s->fetch();
  if(!$r){ header('Location: khuyenmai.php'); exit; } ?>
<p class="crumb"><a href="index.php">Trang chủ</a> › <a href="khuyenmai.php">Khuyến mãi</a> › <b><?= e(ann_excerpt($r['title'],60)) ?></b></p>
<article class="panel adetail"><?= ann_banner($r) ?><div class="adb"><div class="acrow"><span class="achip"><?= e(ann_cat($r)) ?></span><span class="akind"><?= e($r['kind']) ?></span></div>
<h1><?= e($r['title']) ?></h1><?= ann_meta($r) ?><div class="atext"><?= e($r['content']) ?></div>
<?php if($r['applies']): ?><p class="aapp big">Áp dụng: <b><?= e($r['applies']) ?></b></p><?php endif; ?>
<p><a class="btn" href="products.php?sort=sale">Mua ngay <?= ico('arrow') ?></a> <a class="btn ghost" href="khuyenmai.php">← Tất cả khuyến mãi</a></p><?= $adm?adm_tools($r):'' ?></div></article>
<?php require 'footer.php'; exit; }
$all=$pdo->query("SELECT * FROM announcements WHERE type='khuyenmai' ORDER BY created_at DESC, id DESC")->fetchAll();
$q=mb_strtolower(gs('q'),'UTF-8'); $cat=gs('cat'); $kind=gs('kind'); $sort=gs('sort')==='old'?'old':'new';
$cats=[]; $kinds=[]; foreach($all as $r){ $c=ann_cat($r); $cats[$c]=($cats[$c]??0)+1; if($r['kind']) $kinds[$r['kind']]=($kinds[$r['kind']]??0)+1; }
$list=array_values(array_filter($all,function($r) use($q,$cat,$kind){
  if($cat!==''&&ann_cat($r)!==$cat) return false; if($kind!==''&&(string)$r['kind']!==$kind) return false;
  return $q===''||mb_strpos(mb_strtolower($r['title'].' '.$r['content'].' '.$r['applies'].' '.ann_cat($r),'UTF-8'),$q)!==false; }));
if($sort==='old') $list=array_reverse($list);
function km_url($o){ $p=array_merge(['q'=>gs('q'),'cat'=>gs('cat'),'kind'=>gs('kind'),'sort'=>gs('sort')],$o); $p=array_filter($p,function($v){ return $v!==''; }); return 'khuyenmai.php'.($p?'?'.http_build_query($p):''); } ?>
<p class="crumb"><a href="index.php">Trang chủ</a> › <b>Khuyến mãi</b></p>
<div class="ahead"><div><h1>Chương trình khuyến mãi</h1><p class="asub2">Tổng hợp ưu đãi đang áp dụng tại <?= e(SHOP_NAME) ?>.</p></div><span class="kcount"><?= count($all) ?> chương trình</span></div>
<?php if($msg) echo '<div class="msg" style="margin-top:14px">'.e($msg).'</div>'; ?>
<form class="kmbar" method="get"><input type="hidden" name="cat" value="<?= e($cat) ?>">
<label class="ksearch"><input type="search" name="q" value="<?= e(gs('q')) ?>" placeholder="Tìm khuyến mãi, sản phẩm..." aria-label="Tìm khuyến mãi"><button type="submit" aria-label="Tìm"><?= ico('search') ?></button></label>
<div class="kchips"><a class="kchip <?= $cat===''?'on':'' ?>" href="<?= e(km_url(['cat'=>''])) ?>">Tất cả (<?= count($all) ?>)</a><?php foreach($cats as $c=>$n): ?><a class="kchip <?= $cat===$c?'on':'' ?>" href="<?= e(km_url(['cat'=>$c])) ?>"><?= e($c) ?> (<?= $n ?>)</a><?php endforeach; ?></div>
<select name="kind" class="ksel" onchange="this.form.submit()" aria-label="Loại ưu đãi"><option value="">Mọi ưu đãi (<?= count($all) ?>)</option><?php foreach($kinds as $k=>$n): ?><option value="<?= e($k) ?>" <?= $kind===$k?'selected':'' ?>><?= e($k) ?> (<?= $n ?>)</option><?php endforeach; ?></select>
<select name="sort" class="ksel" onchange="this.form.submit()" aria-label="Sắp xếp"><option value="new" <?= $sort==='new'?'selected':'' ?>>Mới nhất</option><option value="old" <?= $sort==='old'?'selected':'' ?>>Cũ nhất</option></select></form>
<?php if($adm): ?><p class="tabs" style="margin:0 0 14px"><a href="admin_announcements.php?back=1&amp;type=khuyenmai#form">+ Thêm khuyến mãi</a><a class="all" href="admin_announcements.php?ft=khuyenmai">Trang quản lý</a></p><?php endif; ?>
<?php if($list): ?><div class="agrid"><?php foreach($list as $r) echo ann_card_km($r,$adm); ?></div>
<?php else: ?><div class="panel" style="text-align:center;padding:40px"><p class="mut">Không có khuyến mãi phù hợp.</p><a class="btn" href="khuyenmai.php">Xem tất cả</a></div><?php endif; ?>
<?php require 'footer.php'; ?>
