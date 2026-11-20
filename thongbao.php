<?php $title='Thông báo'; require 'header.php'; ensure_ann($pdo); $adm=is_admin();
$msg=$adm?(['add'=>'Đã thêm.','save'=>'Đã lưu thay đổi.','del'=>'Đã xóa.'][gs('ok')]??''):'';
$id=(int)gs('id');
if($id){ $s=$pdo->prepare("SELECT * FROM announcements WHERE id=? AND type='thongbao'"); $s->execute([$id]); $r=$s->fetch();
  if(!$r){ header('Location: thongbao.php'); exit; } ?>
<p class="crumb"><a href="index.php">Trang chủ</a> › <a href="thongbao.php">Thông báo</a> › <b><?= e(ann_excerpt($r['title'],60)) ?></b></p>
<article class="panel adetail"><?= ann_banner($r) ?><div class="adb"><span class="achip"><?= e(ann_cat($r)) ?></span>
<h1><?= e($r['title']) ?></h1><?= ann_meta($r) ?><div class="atext"><?= e($r['content']) ?></div>
<p><a class="btn ghost" href="thongbao.php">← Tất cả thông báo</a></p><?= $adm?adm_tools($r):'' ?></div></article>
<?php require 'footer.php'; exit; }
$all=$pdo->query("SELECT * FROM announcements WHERE type='thongbao' ORDER BY created_at DESC, id DESC")->fetchAll();
$cats=[]; foreach($all as $r) $cats[ann_cat($r)]=true; $tab=gs('tab'); if($tab!==''&&!isset($cats[$tab])) $tab='';
function tb_url($t){ return 'thongbao.php'.($t!==''?'?tab='.urlencode($t):''); } ?>
<p class="crumb"><a href="index.php">Trang chủ</a> › <b>Thông báo</b></p>
<nav class="ntabs" aria-label="Danh mục thông báo"><a class="ntab <?= $tab===''?'on':'' ?>" href="thongbao.php">Nổi Bật</a><?php foreach($cats as $c=>$_): ?><a class="ntab <?= $tab===$c?'on':'' ?>" href="<?= e(tb_url($c)) ?>"><?= e($c) ?></a><?php endforeach; ?></nav>
<?php if($msg) echo '<div class="msg">'.e($msg).'</div>'; ?>
<?php if($adm): ?><p class="tabs" style="margin:0 0 14px"><a href="admin_announcements.php?back=1&amp;type=thongbao#form">+ Thêm thông báo</a><a class="all" href="admin_announcements.php?ft=thongbao">Trang quản lý</a></p><?php endif; ?>
<?php if(!$all): ?><div class="panel" style="text-align:center;padding:40px"><p class="mut">Chưa có thông báo nào.</p></div>
<?php elseif($tab===''): $top=array_slice($all,0,5); $rest=array_slice($all,5); ?>
<h2 class="nh">Nổi Bật Nhất</h2>
<div class="nfeat"><?= ann_card_big($top[0],$adm) ?><?php if(count($top)>1): ?><div class="nside"><?php foreach(array_slice($top,1) as $r) echo ann_card_news($r,$adm); ?></div><?php endif; ?></div>
<?php if($rest): ?><h2 class="nh">Thông báo khác</h2><div class="agrid"><?php foreach($rest as $r) echo ann_card_news($r,$adm); ?></div><?php endif; ?>
<?php else: $sel=array_values(array_filter($all,function($r) use($tab){ return ann_cat($r)===$tab; })); ?>
<h2 class="nh"><?= e($tab) ?></h2><div class="agrid"><?php foreach($sel as $r) echo ann_card_news($r,$adm); ?></div>
<?php endif; ?>
<?php require 'footer.php'; ?>
