<?php
// ===== Thông báo / Khuyến mãi: cột mở rộng, ảnh bìa, thẻ hiển thị =====
function ensure_ann($pdo){ static $done=false; if($done) return; $done=true;
  foreach(['category'=>'VARCHAR(50) NULL','kind'=>'VARCHAR(30) NULL','applies'=>'VARCHAR(255) NULL','image'=>'VARCHAR(120) NULL'] as $c=>$d){
    if(!$pdo->query("SHOW COLUMNS FROM announcements LIKE '$c'")->fetch()) $pdo->exec("ALTER TABLE announcements ADD $c $d"); }
  // Dữ liệu cũ chưa có danh mục: gán tự động theo từ khóa một lần (admin có thể sửa lại; để trống danh mục = '' thì không bị gán lại)
  $rows=$pdo->query("SELECT id,type,title,content FROM announcements WHERE category IS NULL")->fetchAll();
  if($rows){ $up=$pdo->prepare('UPDATE announcements SET category=?,kind=? WHERE id=?');
    foreach($rows as $r){ $t=mb_strtolower($r['title'].' '.$r['content'],'UTF-8'); $has=function($ws) use($t){ foreach($ws as $w) if(mb_strpos($t,$w)!==false) return true; return false; };
      if($r['type']==='khuyenmai'){
        $kind=$has(['trả góp'])?'Trả góp':($has(['tặng'])?'Quà tặng':($has(['giảm'])?'Giảm giá':'Ưu đãi'));
        $cat=$has(['laptop'])?'Laptop':($has(['chuột','bàn phím','tai nghe','phụ kiện'])?'Phụ kiện':($has(['màn hình'])?'Màn hình':($has(['pc','máy tính để bàn'])?'PC':'Ưu đãi chung')));
      } else{ $kind='';
        $cat=$has(['bảo hành'])?'Bảo hành':($has(['bảng giá'])?'Bảng giá':($has(['mở cửa','cửa hàng'])?'Cửa hàng':'Thông báo chung')); }
      $up->execute([$cat,$kind,$r['id']]); } } }
function ann_cat($r){ return ($r['category']!==null&&$r['category']!=='')?$r['category']:(($r['type']==='khuyenmai')?'Ưu đãi':'Thông báo'); }
function ann_excerpt($s,$n){ return mb_strimwidth(trim(preg_replace('/\s+/',' ',(string)$s)),0,$n,'…','UTF-8'); }
function ann_img_ok($f){ return $f!==null&&$f!==''&&preg_match('/^ann_[a-f0-9]+\.(jpg|png|webp)$/',$f)&&is_file(__DIR__.'/uploads/'.$f); }
// Ảnh bìa: dùng ảnh đã tải lên, nếu không có thì vẽ nền gradient theo danh mục
function ann_banner($r){
  if(ann_img_ok($r['image']??null)) return '<div class="abn"><img src="uploads/'.e($r['image']).'" alt="" loading="lazy"></div>';
  $g=abs(crc32(ann_cat($r).$r['type']))%6; $k=($r['type']==='khuyenmai');
  return '<div class="abn g'.$g.'"><span class="abi">'.ico($k?'tag':'shield').'</span><b class="abk">'.e($k&&$r['kind']?$r['kind']:ann_cat($r)).'</b></div>'; }
function ann_url($r){ return ($r['type']==='khuyenmai'?'khuyenmai.php':'thongbao.php').'?id='.(int)$r['id']; }
function adm_tools($r){ $id=(int)$r['id'];
  return '<div class="adm-act"><a class="btn ghost sm" href="admin_announcements.php?edit='.$id.'&amp;back=1#form">Sửa</a><form method="post" action="admin_announcements.php" onsubmit="return confirm(\'Xóa mục này?\')"><input type="hidden" name="t" value="'.csrf().'"><input type="hidden" name="back" value="1"><button class="btn ghost sm del" name="del" value="'.$id.'" type="submit">Xóa</button></form></div>'; }
function ann_card_km($r,$adm){
  return '<article class="acard"><a class="acl" href="'.ann_url($r).'">'.ann_banner($r).'<div class="acb"><div class="acrow"><span class="achip">'.e(ann_cat($r)).'</span><span class="akind">'.e($r['kind']).'</span></div><h3>'.e($r['title']).'</h3><p>'.e(ann_excerpt($r['content'],110)).'</p>'
   .($r['applies']?'<p class="aapp">Áp dụng: <b>'.e(ann_excerpt($r['applies'],90)).'</b></p>':'').'<small class="adate">'.e(date('d/m/Y',strtotime($r['created_at']))).'</small></div></a>'.($adm?adm_tools($r):'').'</article>'; }
function ann_meta($r){ return '<div class="nmeta">'.ico('clock').'<span>'.e(date('d/m/Y',strtotime($r['created_at']))).'</span><i>•</i><span>'.e(ann_cat($r)).'</span></div>'; }
function ann_card_news($r,$adm){
  return '<article class="ncard"><a class="acl" href="'.ann_url($r).'">'.ann_banner($r).'<div class="acb"><h3>'.e($r['title']).'</h3>'.ann_meta($r).'</div></a>'.($adm?adm_tools($r):'').'</article>'; }
function ann_card_big($r,$adm){
  return '<article class="nbig"><a class="acl" href="'.ann_url($r).'">'.ann_banner($r).'<div class="nov"><h3>'.e($r['title']).'</h3><p>'.e(ann_excerpt($r['content'],150)).'</p>'.ann_meta($r).'</div></a>'.($adm?adm_tools($r):'').'</article>'; }
