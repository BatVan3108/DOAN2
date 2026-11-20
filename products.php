<?php require_once 'config.php';
$cat=(int)gs('cat'); $brand=(int)gs('brand'); $pr=gs('price'); $q=gs('q'); $ranges=PRICE_RANGES;
$sorts=['new'=>['Mới nhất','p.id DESC'],'asc'=>['Giá thấp đến cao','p.price ASC'],'desc'=>['Giá cao đến thấp','p.price DESC'],
  'sale'=>['Giảm giá nhiều','(CASE WHEN p.old_price IS NULL THEN 0 ELSE (p.old_price-p.price)/p.old_price END) DESC']];
$sort=gs('sort'); if(!isset($sorts[$sort])) $sort='new';
$w=[];$a=[];
if($cat){$w[]='p.category_id=?';$a[]=$cat;} if($brand){$w[]='p.brand_id=?';$a[]=$brand;}
if($pr!==''&&isset($ranges[(int)$pr])){$r=$ranges[(int)$pr];$w[]='p.price>=? AND p.price<?';$a[]=$r[1];$a[]=$r[2];}
if($q!==''){$w[]='p.name LIKE ?';$a[]='%'.$q.'%';}
$cats=$pdo->query('SELECT * FROM categories')->fetchAll(); $brands=$pdo->query('SELECT * FROM brands')->fetchAll();
$cname=''; foreach($cats as $c) if($c['id']==$cat) $cname=$c['name'];
$bname=''; foreach($brands as $b) if($b['id']==$brand) $bname=$b['name'];
$title=$cname?:'Sản phẩm';
$per=12; $pg=max(1,(int)gs('page'));
$cs=$pdo->prepare('SELECT COUNT(*) FROM products p'.($w?' WHERE '.implode(' AND ',$w):'')); $cs->execute($a); $total=(int)$cs->fetchColumn();
$pages=max(1,(int)ceil($total/$per)); if($pg>$pages) $pg=$pages; $off=($pg-1)*$per;
$st=$pdo->prepare(PRODUCT_SQL.($w?' WHERE '.implode(' AND ',$w):'').' ORDER BY '.$sorts[$sort][1].', p.id DESC LIMIT '.$per.' OFFSET '.$off); $st->execute($a); $list=$st->fetchAll();
$pri=['ASUS','Apple','Dell','HP','Lenovo','Acer'];
usort($brands,function($x,$y)use($pri){ $i=array_search($x['name'],$pri); $j=array_search($y['name'],$pri); $i=($i===false)?99:$i; $j=($j===false)?99:$j; return ($i<=>$j) ?: strcmp($x['name'],$y['name']); });
$first=array_slice($brands,0,6); $more=array_slice($brands,6);
function brand_tile($b,$on,$href){ $img=brand_img($b['name']);
  return '<a class="bt'.($on?' on':'').'" href="'.e($href).'" title="'.e($b['name']).'">'.($img?:'<span>'.e($b['name']).'</span>').'</a>'; }
function lk($k,$v){ $g=$_GET; unset($g['page']); if(($g[$k]??null)==$v) unset($g[$k]); else $g[$k]=$v; return 'products.php'.($g?'?'.http_build_query($g):''); }
function pg_link($n){ $g=$_GET; if($n<=1) unset($g['page']); else $g['page']=$n; return 'products.php'.($g?'?'.http_build_query($g):''); }
require 'header.php'; ?>
<p class="crumb"><a href="index.php">Trang chủ</a> › <?php if($cname): ?><a href="products.php">Sản phẩm</a> › <b><?= e($cname) ?></b><?php else: ?><b>Sản phẩm</b><?php endif; ?></p>
<h2><?= e($title) ?></h2>
<div class="layout"><aside>
<div class="panel"><h4>Theo loại</h4><?php foreach($cats as $c): ?><a class="<?= $cat==$c['id']?'on':'' ?>" href="<?= e(lk('cat',$c['id'])) ?>"><?= e($c['name']) ?></a><?php endforeach; ?></div>
<div class="panel"><h4>Hãng sản xuất</h4><div class="bgrid"><?php foreach($first as $b) echo brand_tile($b,$brand==$b['id'],lk('brand',$b['id'])); ?></div>
<?php if($more): $open=false; foreach($more as $b) if($brand==$b['id']) $open=true; ?>
<div class="bgrid" id="bm" <?= $open?'':'hidden' ?>><?php foreach($more as $b) echo brand_tile($b,$brand==$b['id'],lk('brand',$b['id'])); ?></div>
<button type="button" class="xt" onclick="var m=document.getElementById('bm');m.hidden=!m.hidden;this.textContent=m.hidden?'Xem thêm':'Thu gọn'"><?= $open?'Thu gọn':'Xem thêm' ?></button><?php endif; ?></div>
<div class="panel"><h4>Theo giá</h4><?php foreach($ranges as $i=>$r): ?><a class="<?= $pr!==''&&(int)$pr===$i?'on':'' ?>" href="<?= e(lk('price',$i)) ?>"><?= e($r[0]) ?></a><?php endforeach; ?></div>
<a class="btn ghost" href="products.php">Xóa bộ lọc</a></aside>
<div>
<form method="get" class="tools"><?php foreach(['cat','brand','price'] as $k) if(gs($k)!=='') echo '<input type="hidden" name="'.$k.'" value="'.e(gs($k)).'">'; ?>
<input type="search" name="q" placeholder="Tìm theo tên sản phẩm" value="<?= e($q) ?>" aria-label="Tìm sản phẩm">
<select name="sort" aria-label="Sắp xếp" onchange="this.form.submit()"><?php foreach($sorts as $k=>$s): ?><option value="<?= $k ?>" <?= $sort===$k?'selected':'' ?>><?= e($s[0]) ?></option><?php endforeach; ?></select>
<button class="btn" type="submit">Lọc</button></form>
<div class="fchips"><span class="mut"><?= $total ?> sản phẩm</span>
<?php if($cname): ?><a class="chip" href="<?= e(lk('cat',$cat)) ?>"><?= e($cname) ?> ✕</a><?php endif; ?>
<?php if($bname): ?><a class="chip" href="<?= e(lk('brand',$brand)) ?>"><?= e($bname) ?> ✕</a><?php endif; ?>
<?php if($pr!==''&&isset($ranges[(int)$pr])): ?><a class="chip" href="<?= e(lk('price',(int)$pr)) ?>"><?= e($ranges[(int)$pr][0]) ?> ✕</a><?php endif; ?>
<?php if($q!==''): $g=$_GET; unset($g['q'],$g['page']); ?><a class="chip" href="<?= e('products.php'.($g?'?'.http_build_query($g):'')) ?>">“<?= e($q) ?>” ✕</a><?php endif; ?></div>
<?php if($list): ?><div class="grid g4"><?php foreach($list as $p) echo card($p); ?></div>
<?php if($pages>1): ?><nav class="pager" aria-label="Phân trang"><?php if($pg>1): ?><a href="<?= e(pg_link($pg-1)) ?>">‹</a><?php endif; ?>
<?php for($n=max(1,$pg-2);$n<=min($pages,$pg+2);$n++): ?><a class="<?= $n===$pg?'on':'' ?>" href="<?= e(pg_link($n)) ?>"><?= $n ?></a><?php endfor; ?>
<?php if($pg<$pages): ?><a href="<?= e(pg_link($pg+1)) ?>">›</a><?php endif; ?></nav><?php endif; ?>
<?php else: ?><div class="panel">Không có sản phẩm phù hợp. Hãy bỏ bớt bộ lọc.</div><?php endif; ?></div></div>
<?php require 'footer.php'; ?>
