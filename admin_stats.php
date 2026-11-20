<?php require_once 'config.php'; require_admin(); ensure_orders($pdo);
// ----- Tham số (kiểm tra chặt) -----
$mode=gs('by'); if(!in_array($mode,['month','quarter','year'],true)) $mode='month';
$crit=(gs('crit')==='done')?'done':'valid'; $stIn=($crit==='done')?"('daxuly')":"('moi','daxuly')";
$yr=$pdo->query('SELECT MIN(YEAR(created_at)) AS a, MAX(YEAR(created_at)) AS b FROM orders')->fetch();
$cy=(int)date('Y'); $y0=min((int)($yr['a']?:$cy),$cy); $y1=max((int)($yr['b']?:$cy),$cy);
$year=(int)gs('year'); if($year<$y0||$year>$y1) $year=$y1;
$month=(int)gs('month'); if($month<0||$month>12) $month=0;
// ----- Doanh thu của một đơn: tổng tiền đơn (đơn cũ chỉ có 1 sản phẩm thì lấy giá x số lượng) -----
$REV='COALESCE(o.total,o.qty*COALESCE(p.price,0))'; $UNITS='COALESCE((SELECT SUM(oi.qty) FROM order_items oi WHERE oi.order_id=o.id),o.qty)'; $FROM='FROM orders o LEFT JOIN products p ON p.id=o.product_id';
$mn=['','Tháng 1','Tháng 2','Tháng 3','Tháng 4','Tháng 5','Tháng 6','Tháng 7','Tháng 8','Tháng 9','Tháng 10','Tháng 11','Tháng 12']; $qn=['','Quý I','Quý II','Quý III','Quý IV'];
if($mode==='month'&&$month>0){ $gx='DAY(o.created_at)'; $where='YEAR(o.created_at)=? AND MONTH(o.created_at)=?'; $args=[$year,$month]; $keys=range(1,(int)date('t',mktime(0,0,0,$month,1,$year))); $unit='Ngày'; $ptitle='Tháng '.$month.'/'.$year.' (theo ngày)'; }
elseif($mode==='month'){ $gx='MONTH(o.created_at)'; $where='YEAR(o.created_at)=?'; $args=[$year]; $keys=range(1,12); $unit='Tháng'; $ptitle='Năm '.$year.' (theo tháng)'; }
elseif($mode==='quarter'){ $gx='QUARTER(o.created_at)'; $where='YEAR(o.created_at)=?'; $args=[$year]; $keys=range(1,4); $unit='Quý'; $ptitle='Năm '.$year.' (theo quý)'; }
else{ $gx='YEAR(o.created_at)'; $where='1=1'; $args=[]; $keys=range($y0,$y1); $unit='Năm'; $ptitle='Tất cả các năm'; }
$q=$pdo->prepare("SELECT $gx AS k, COUNT(*) AS n, SUM($UNITS) AS u, SUM($REV) AS r $FROM WHERE o.status IN $stIn AND $where GROUP BY k"); $q->execute($args);
$got=[]; foreach($q->fetchAll() as $r) $got[(int)$r['k']]=$r;
$rows=[]; $tn=$tu=$tr=0;
foreach($keys as $k){ $r=$got[$k]??null; $n=(int)($r['n']??0); $u=(int)($r['u']??0); $v=(int)($r['r']??0);
  $lab=($mode==='month'&&$month>0)?(string)$k:(($mode==='month')?$mn[$k]:(($mode==='quarter')?$qn[$k]:(string)$k)); $rows[]=['lab'=>$lab,'n'=>$n,'u'=>$u,'r'=>$v]; $tn+=$n; $tu+=$u; $tr+=$v; }
$avg=$tn?(int)round($tr/$tn):0;
// ----- Xuất CSV (Excel mở được, có BOM UTF-8) -----
if(gs('export')==='1'){ while(ob_get_level()) ob_end_clean();
  header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="doanh-thu-'.$mode.'-'.$year.'.csv"'); echo "\xEF\xBB\xBF"; $f=fopen('php://output','w');
  fputcsv($f,[$ptitle]); fputcsv($f,[$unit,'Số đơn','Sản phẩm bán ra','Doanh thu (đồng)','Giá trị TB/đơn (đồng)']);
  foreach($rows as $r) fputcsv($f,[$r['lab'],$r['n'],$r['u'],$r['r'],$r['n']?(int)round($r['r']/$r['n']):0]);
  fputcsv($f,['Tổng',$tn,$tu,$tr,$avg]); fclose($f); exit; }
// ----- Các số liệu phụ trong cùng kỳ -----
$q=$pdo->prepare("SELECT o.status AS s, COUNT(*) AS n, SUM($REV) AS r $FROM WHERE $where GROUP BY o.status"); $q->execute($args); $bySt=[]; foreach($q->fetchAll() as $r) $bySt[$r['s']]=$r;
$q=$pdo->prepare("SELECT COALESCE(o.pay,'khac') AS s, COUNT(*) AS n, SUM($REV) AS r $FROM WHERE o.status IN $stIn AND $where GROUP BY s"); $q->execute($args); $byPay=$q->fetchAll();
$q=$pdo->prepare("SELECT COALESCE(oi.name,p.name,'(đã xóa)') AS name, SUM(oi.qty) AS q, SUM(oi.qty*oi.price) AS r FROM order_items oi JOIN orders o ON o.id=oi.order_id LEFT JOIN products p ON p.id=oi.product_id WHERE o.status IN $stIn AND $where GROUP BY oi.product_id, COALESCE(oi.name,p.name,'(đã xóa)') ORDER BY q DESC, r DESC LIMIT 5"); $q->execute($args); $top=$q->fetchAll();
// ----- Hàm vẽ -----
function fshort($v){ $v=(float)$v; if($v>=1e9) return rtrim(rtrim(number_format($v/1e9,1,',',''),'0'),',').' tỷ'; if($v>=1e6) return rtrim(rtrim(number_format($v/1e6,1,',',''),'0'),',').' tr'; if($v>=1e3) return round($v/1e3).'k'; return (string)(int)$v; }
function svg_bars($labels,$vals){ $n=count($vals); $W=900;$H=300;$pl=64;$pr=12;$pt=26;$pb=34; $max=max($vals); if($max<=0) $max=1;
  $mag=pow(10,floor(log10($max))); $step=$mag/2; $nm=ceil($max/$step)*$step; $ph=$H-$pt-$pb; $bw=($W-$pl-$pr)/$n; $bar=$bw*0.62; $s='<svg class="schart" viewBox="0 0 '.$W.' '.$H.'" role="img" aria-label="Biểu đồ doanh thu">';
  for($i=0;$i<=4;$i++){ $y=$pt+$ph*(1-$i/4); $s.='<line x1="'.$pl.'" x2="'.($W-$pr).'" y1="'.round($y,1).'" y2="'.round($y,1).'" stroke="#e2e8f2"/><text x="'.($pl-8).'" y="'.round($y+4,1).'" text-anchor="end" font-size="12" fill="#6b7a90">'.fshort($nm*$i/4).'</text>'; }
  $skip=(int)ceil($n/16);
  foreach($vals as $i=>$v){ $h=$ph*$v/$nm; $x=$pl+$i*$bw+($bw-$bar)/2; $y=$H-$pb-$h;
    $s.='<g><title>'.e($labels[$i]).': '.e(vnd($v)).'</title><rect x="'.round($x,1).'" y="'.round($y,1).'" width="'.round($bar,1).'" height="'.round(max($h,$v>0?1:0),1).'" rx="3" fill="'.($v>0?'#2f7df6':'#d5def0').'"/></g>';
    if($v>0&&$n<=16) $s.='<text x="'.round($x+$bar/2,1).'" y="'.round($y-5,1).'" text-anchor="middle" font-size="11" fill="#1f2d48">'.fshort($v).'</text>';
    if($i%$skip===0) $s.='<text x="'.round($x+$bar/2,1).'" y="'.($H-12).'" text-anchor="middle" font-size="12" fill="#33415c">'.e($labels[$i]).'</text>'; }
  return $s.'</svg>'; }
function sturl($o){ $p=array_merge($_GET,$o); unset($p['export']); return 'admin_stats.php?'.http_build_query($p); }
$title='Thống kê doanh thu'; require 'header.php'; $maxr=max(array_column($rows,'r')?:[0]); ?>
<?= admin_nav('admin_stats.php') ?>
<h2>Thống kê doanh thu</h2>
<form class="panel sfilter" method="get" id="sf">
<label>Xem theo<select name="by" id="by"><option value="month" <?= $mode==='month'?'selected':'' ?>>Tháng</option><option value="quarter" <?= $mode==='quarter'?'selected':'' ?>>Quý</option><option value="year" <?= $mode==='year'?'selected':'' ?>>Năm</option></select></label>
<label id="ywrap" <?= $mode==='year'?'hidden':'' ?>>Năm<select name="year"><?php for($y=$y1;$y>=$y0;$y--) echo '<option value="'.$y.'" '.($y===$year?'selected':'').'>'.$y.'</option>'; ?></select></label>
<label id="mwrap" <?= $mode==='month'?'':'hidden' ?>>Tháng<select name="month"><option value="0">Cả năm</option><?php for($m=1;$m<=12;$m++) echo '<option value="'.$m.'" '.($m===$month?'selected':'').'>Tháng '.$m.'</option>'; ?></select></label>
<label>Tính doanh thu từ<select name="crit"><option value="valid" <?= $crit==='valid'?'selected':'' ?>>Đơn đã tiếp nhận + hoàn tất</option><option value="done" <?= $crit==='done'?'selected':'' ?>>Chỉ đơn hoàn tất</option></select></label>
<button class="btn" type="submit">Xem thống kê</button></form>
<p class="fchips"><a class="chip" href="admin_stats.php?by=month&amp;year=<?= $cy ?>&amp;month=<?= (int)date('n') ?>&amp;crit=<?= $crit ?>">Tháng này</a><a class="chip" href="admin_stats.php?by=quarter&amp;year=<?= $cy ?>&amp;crit=<?= $crit ?>">Quý này</a><a class="chip" href="admin_stats.php?by=month&amp;year=<?= $cy ?>&amp;crit=<?= $crit ?>">Năm nay</a><a class="chip" href="admin_stats.php?by=year&amp;crit=<?= $crit ?>">Tất cả các năm</a></p>
<h3 style="margin:6px 0 10px"><?= e($ptitle) ?></h3>
<div class="stats stats4"><div><b><?= vnd($tr) ?></b><span>Tổng doanh thu</span></div><div><b><?= $tn ?></b><span>Số đơn hợp lệ</span></div><div><b><?= vnd($avg) ?></b><span>Giá trị trung bình / đơn</span></div><div><b><?= $tu ?></b><span>Sản phẩm bán ra</span></div></div>
<p class="mut" style="font-size:13px;margin:0 0 12px">Chỉ tính <?= $crit==='done'?'đơn <b>Hoàn tất</b>':'đơn <b>Đã tiếp nhận</b> và <b>Hoàn tất</b>' ?>; đơn Chờ xác nhận thanh toán và đơn Đã hủy không được tính. Ngày tính theo ngày đặt đơn. <?php if(!empty($bySt['cho_tt']['n'])): ?>Đang chờ thanh toán trong kỳ: <b><?= (int)$bySt['cho_tt']['n'] ?> đơn, <?= vnd((int)$bySt['cho_tt']['r']) ?></b>.<?php endif; ?></p>
<div class="panel"><?= svg_bars(array_column($rows,'lab'),array_map('intval',array_column($rows,'r'))) ?></div>
<div class="panel scroll" style="margin-top:14px"><div class="sec-h" style="margin:0 0 8px"><h3 style="margin:0">Chi tiết theo <?= e(mb_strtolower($unit)) ?></h3><a class="btn ghost sm" href="<?= e(sturl(['export'=>'1'])) ?>">Xuất CSV</a></div>
<table><tr><th><?= e($unit) ?></th><th>Số đơn</th><th>Sản phẩm bán ra</th><th>Doanh thu</th><th>TB / đơn</th><th style="width:22%">Tỷ trọng</th></tr>
<?php foreach($rows as $r): $pc=$tr?round($r['r']*100/$tr,1):0; ?><tr><td><?= e($r['lab']) ?></td><td><?= $r['n'] ?></td><td><?= $r['u'] ?></td><td><b><?= vnd($r['r']) ?></b></td><td><?= $r['n']?vnd((int)round($r['r']/$r['n'])):'—' ?></td><td><span class="pbar"><i style="width:<?= $maxr?round($r['r']*100/$maxr):0 ?>%"></i></span> <small class="mut"><?= $pc ?>%</small></td></tr><?php endforeach; ?>
<tr class="trow"><td><b>Tổng</b></td><td><b><?= $tn ?></b></td><td><b><?= $tu ?></b></td><td><b><?= vnd($tr) ?></b></td><td><b><?= $tn?vnd($avg):'—' ?></b></td><td></td></tr></table></div>
<div class="two" style="margin-top:14px">
<div class="panel"><h3>Sản phẩm bán chạy trong kỳ</h3><?php if(!$top): ?><p class="mut">Chưa có dữ liệu.</p><?php else: ?><table><tr><th>Sản phẩm</th><th>SL</th><th>Doanh thu</th></tr><?php foreach($top as $t): ?><tr><td><?= e($t['name']) ?></td><td><?= (int)$t['q'] ?></td><td><?= vnd((int)$t['r']) ?></td></tr><?php endforeach; ?></table><?php endif; ?></div>
<div class="panel"><h3>Cơ cấu đơn hàng trong kỳ</h3><table><tr><th>Trạng thái</th><th>Số đơn</th><th>Giá trị</th></tr><?php foreach(['cho_tt','moi','daxuly','huy'] as $s): $x=$bySt[$s]??null; ?><tr><td><?= ord_badge($s) ?></td><td><?= (int)($x['n']??0) ?></td><td><?= vnd((int)($x['r']??0)) ?></td></tr><?php endforeach; ?></table>
<h3 style="margin-top:16px">Theo hình thức thanh toán</h3><?php if(!$byPay): ?><p class="mut">Chưa có dữ liệu.</p><?php else: ?><table><tr><th>Hình thức</th><th>Số đơn</th><th>Doanh thu</th></tr><?php foreach($byPay as $x): ?><tr><td><?= $x['s']==='khac'?'Không rõ (đơn cũ)':e(ord_label($x['s'])) ?></td><td><?= (int)$x['n'] ?></td><td><?= vnd((int)$x['r']) ?></td></tr><?php endforeach; ?></table><?php endif; ?></div></div>
<script>(function(){var b=document.getElementById('by'),y=document.getElementById('ywrap'),m=document.getElementById('mwrap');b.onchange=function(){y.hidden=b.value==='year';m.hidden=b.value!=='month'}})();</script>
<?php require 'footer.php'; ?>
