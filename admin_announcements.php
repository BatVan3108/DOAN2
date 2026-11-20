<?php $title='Quản lý thông báo & khuyến mãi'; require 'header.php'; require_admin(); ensure_ann($pdo);
$err=''; $msg=''; $form=null;
$back=(($_POST['back']??gs('back'))==='1');
$okmap=['add'=>'Đã thêm.','save'=>'Đã lưu thay đổi.','del'=>'Đã xóa.'];
function ann_go($ok,$back,$type){ header('Location: '.($back?(($type==='khuyenmai')?'khuyenmai.php?':'thongbao.php?'):'admin_announcements.php?').'ok='.$ok); exit; }
function ann_unlink($f){ if(preg_match('/^ann_[a-f0-9]+\.(jpg|png|webp)$/',(string)$f)) @unlink(__DIR__.'/uploads/'.$f); }
if(gs('ok')!=='') $msg=$okmap[gs('ok')]??'';
if($_SERVER['REQUEST_METHOD']==='POST'){ check_csrf();
  // ----- XÓA -----
  if(isset($_POST['del'])){ $s=$pdo->prepare('SELECT type,image FROM announcements WHERE id=?'); $s->execute([(int)$_POST['del']]); $o=$s->fetch();
    $pdo->prepare('DELETE FROM announcements WHERE id=?')->execute([(int)$_POST['del']]); if($o) ann_unlink($o['image']); ann_go('del',$back,$o['type']??'thongbao'); }
  // ----- THÊM / CẬP NHẬT -----
  $pid=(int)($_POST['id']??0); $type=(($_POST['type']??'')==='khuyenmai')?'khuyenmai':'thongbao';
  $ti=mb_substr(trim($_POST['title']??''),0,200); $co=trim($_POST['content']??'');
  $cat=mb_substr(trim($_POST['category']??''),0,50); $kind=($type==='khuyenmai')?mb_substr(trim($_POST['kind']??''),0,30):''; $apl=mb_substr(trim($_POST['applies']??''),0,255);
  $d=trim($_POST['date']??''); $dt=DateTime::createFromFormat('Y-m-d',$d); $okd=$dt && $dt->format('Y-m-d')===$d;
  $f=$_FILES['image']??null; $ext=null;
  if($ti===''){ $err='Vui lòng nhập tiêu đề.'; }
  elseif($d!=='' && !$okd){ $err='Ngày đăng không hợp lệ.'; }
  elseif($f && $f['error']!==UPLOAD_ERR_NO_FILE){
    if($f['error']!==UPLOAD_ERR_OK) $err='Tải ảnh thất bại (có thể ảnh quá lớn so với cấu hình PHP).';
    elseif($f['size']>5*1024*1024) $err='Ảnh quá lớn (tối đa 5MB).';
    else{ $info=@getimagesize($f['tmp_name']); $map=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp']; if(!$info||!isset($map[$info['mime']])) $err='Chỉ nhận ảnh JPG, PNG hoặc WEBP.'; else $ext=$map[$info['mime']]; } }
  if($err){ $form=['id'=>$pid,'type'=>$type,'title'=>$ti,'content'=>$co,'category'=>$cat,'kind'=>$kind,'applies'=>$apl,'image'=>'','created_at'=>($okd?$d:date('Y-m-d'))]; }
  else{
    $oldImg=''; $old=false;
    if($pid){ $st=$pdo->prepare('SELECT created_at,image FROM announcements WHERE id=?'); $st->execute([$pid]); $o=$st->fetch(); if($o){ $old=$o['created_at']; $oldImg=(string)$o['image']; } }
    if($pid && $old===false){ $err='Mục này không còn tồn tại.'; $form=['id'=>0,'type'=>$type,'title'=>$ti,'content'=>$co,'category'=>$cat,'kind'=>$kind,'applies'=>$apl,'image'=>'','created_at'=>date('Y-m-d')]; }
    else{
      $img=$oldImg; $newName=null;
      if($ext){ $newName='ann_'.bin2hex(random_bytes(8)).'.'.$ext;
        if(!@move_uploaded_file($f['tmp_name'],__DIR__.'/uploads/'.$newName)){ $err='Không lưu được ảnh (thư mục uploads cần cho phép ghi).'; $newName=null; } else $img=$newName; }
      elseif(!empty($_POST['rmimg'])) $img='';
      if($err){ $form=['id'=>$pid,'type'=>$type,'title'=>$ti,'content'=>$co,'category'=>$cat,'kind'=>$kind,'applies'=>$apl,'image'=>$oldImg,'created_at'=>($okd?$d:date('Y-m-d'))]; }
      elseif($pid){
        $when=($d===''||$d===date('Y-m-d',strtotime($old)))?$old:$d.' '.date('H:i:s',strtotime($old));
        $pdo->prepare('UPDATE announcements SET type=?,title=?,content=?,category=?,kind=?,applies=?,image=?,created_at=? WHERE id=?')->execute([$type,$ti,$co,$cat,$kind,$apl,$img,$when,$pid]);
        if($img!==$oldImg) ann_unlink($oldImg); ann_go('save',$back,$type);
      } else{
        $when=($d===''?date('Y-m-d'):$d).' '.date('H:i:s');
        $pdo->prepare('INSERT INTO announcements(type,title,content,category,kind,applies,image,created_at) VALUES(?,?,?,?,?,?,?,?)')->execute([$type,$ti,$co,$cat,$kind,$apl,$img,$when]);
        ann_go('add',$back,$type); } } } }
if(!$form){ $form=['id'=>0,'type'=>(gs('type')==='khuyenmai'?'khuyenmai':'thongbao'),'title'=>'','content'=>'','category'=>'','kind'=>'','applies'=>'','image'=>'','created_at'=>date('Y-m-d')];
  $eid=(int)gs('edit'); if($eid){ $st=$pdo->prepare('SELECT * FROM announcements WHERE id=?'); $st->execute([$eid]); $row=$st->fetch(); if($row) $form=$row; } }
$ft=gs('ft'); if($ft!=='thongbao'&&$ft!=='khuyenmai') $ft='';
$q=$pdo->prepare('SELECT * FROM announcements'.($ft?' WHERE type=?':'').' ORDER BY created_at DESC, id DESC'); $q->execute($ft?[$ft]:[]); $list=$q->fetchAll();
$cnt=$pdo->query("SELECT COALESCE(SUM(type='thongbao'),0) AS tb, COALESCE(SUM(type='khuyenmai'),0) AS km FROM announcements")->fetch();
$cl=$pdo->query("SELECT DISTINCT category FROM announcements WHERE category<>'' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$kl=$pdo->query("SELECT DISTINCT kind FROM announcements WHERE kind<>'' ORDER BY kind")->fetchAll(PDO::FETCH_COLUMN);
foreach(['Giảm giá','Quà tặng','Trả góp','Combo','Flash sale'] as $k) if(!in_array($k,$kl,true)) $kl[]=$k;
$backType=$form['type']; ?>
<?= admin_nav('admin_announcements.php') ?>
<h2>Quản lý thông báo &amp; khuyến mãi</h2>
<?php if($msg) echo '<div class="msg">'.e($msg).'</div>'; if($err) echo '<div class="msg err">'.e($err).'</div>'; ?>
<div class="two">
<form class="panel" id="form" method="post" enctype="multipart/form-data"><h3><?= $form['id']?'Sửa nội dung':'Thêm mới' ?></h3>
<input type="hidden" name="t" value="<?= csrf() ?>"><input type="hidden" name="id" value="<?= (int)$form['id'] ?>"><?php if($back): ?><input type="hidden" name="back" value="1"><?php endif; ?>
<label for="at">Loại</label><select id="at" name="type" onchange="document.getElementById('kbox').hidden=this.value!=='khuyenmai'"><option value="thongbao" <?= $form['type']==='thongbao'?'selected':'' ?>>Thông báo (trang Thông báo)</option><option value="khuyenmai" <?= $form['type']==='khuyenmai'?'selected':'' ?>>Khuyến mãi (trang Khuyến mãi + slider trang chủ)</option></select>
<label for="ati">Tiêu đề</label><input id="ati" name="title" maxlength="200" value="<?= e($form['title']) ?>" required>
<label for="acat">Danh mục <small class="mut">(ví dụ: Laptop, Phụ kiện / Bảo hành, Cửa hàng)</small></label><input id="acat" name="category" maxlength="50" list="catlist" value="<?= e($form['category']) ?>"><datalist id="catlist"><?php foreach($cl as $x) echo '<option value="'.e($x).'">'; ?></datalist>
<div id="kbox" <?= $form['type']==='khuyenmai'?'':'hidden' ?>><label for="akind">Loại ưu đãi <small class="mut">(Giảm giá, Quà tặng, Trả góp...)</small></label><input id="akind" name="kind" maxlength="30" list="kindlist" value="<?= e($form['kind']) ?>"><datalist id="kindlist"><?php foreach($kl as $x) echo '<option value="'.e($x).'">'; ?></datalist>
<label for="aapp">Áp dụng cho <small class="mut">(tùy chọn, ví dụ: Laptop gaming Acer, Chuột Logitech)</small></label><input id="aapp" name="applies" maxlength="255" value="<?= e($form['applies']) ?>"></div>
<label for="ad">Ngày đăng</label><input id="ad" type="date" name="date" value="<?= e(date('Y-m-d',strtotime($form['created_at']))) ?>">
<label for="ac">Nội dung</label><textarea id="ac" name="content" rows="5"><?= e($form['content']) ?></textarea>
<label for="aim">Ảnh bìa <small class="mut">(tùy chọn, JPG/PNG/WEBP ≤ 5MB; để trống sẽ dùng nền màu tự động)</small></label>
<?php if(ann_img_ok($form['image']??null)): ?><div class="thumb big2" style="margin:6px 0"><img src="uploads/<?= e($form['image']) ?>" alt=""></div><label class="achk" style="margin:0 0 8px!important"><input type="checkbox" name="rmimg" value="1"> <span>Xóa ảnh hiện tại</span></label><?php endif; ?>
<input id="aim" type="file" name="image" accept="image/jpeg,image/png,image/webp">
<p><button class="btn" type="submit"><?= $form['id']?'Lưu thay đổi':'Thêm mới' ?></button><?php if($form['id']): ?> <a class="btn ghost" href="<?= $back?(($backType==='khuyenmai')?'khuyenmai.php':'thongbao.php'):'admin_announcements.php' ?>">Hủy</a><?php endif; ?></p></form>
<div class="panel">
<p class="fchips" style="margin-top:0"><a class="chip <?= $ft===''?'on':'' ?>" href="admin_announcements.php">Tất cả (<?= (int)$cnt['tb']+(int)$cnt['km'] ?>)</a><a class="chip <?= $ft==='thongbao'?'on':'' ?>" href="admin_announcements.php?ft=thongbao">Thông báo (<?= (int)$cnt['tb'] ?>)</a><a class="chip <?= $ft==='khuyenmai'?'on':'' ?>" href="admin_announcements.php?ft=khuyenmai">Khuyến mãi (<?= (int)$cnt['km'] ?>)</a></p>
<div class="scroll"><table><tr><th>Loại</th><th>Tiêu đề</th><th>Ngày</th><th></th></tr>
<?php foreach($list as $r): ?><tr><td><?= $r['type']==='khuyenmai'?'Khuyến mãi':'Thông báo' ?><br><small class="mut"><?= e(ann_cat($r)) ?></small></td><td><b><?= e($r['title']) ?></b><?php if($r['content']): ?><br><small class="mut"><?= e(ann_excerpt($r['content'],80)) ?></small><?php endif; ?></td><td><?= e(date('d/m/Y',strtotime($r['created_at']))) ?></td>
<td class="act"><a class="btn ghost" href="admin_announcements.php?edit=<?= (int)$r['id'] ?>#form">Sửa</a>
<form method="post" onsubmit="return confirm('Xóa mục này?')"><input type="hidden" name="t" value="<?= csrf() ?>"><button class="btn alt" name="del" value="<?= (int)$r['id'] ?>" type="submit">Xóa</button></form></td></tr><?php endforeach; ?>
<?php if(!$list): ?><tr><td colspan="4" class="mut">Chưa có mục nào.</td></tr><?php endif; ?></table></div></div></div>
<?php require 'footer.php'; ?>
