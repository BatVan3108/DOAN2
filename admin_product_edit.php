<?php require_once 'config.php'; require_admin();
$id=(int)($_GET['id']??$_POST['id']??0); $err='';
$cats=$pdo->query('SELECT * FROM categories')->fetchAll(); $brands=$pdo->query('SELECT * FROM brands')->fetchAll();
$p=['id'=>0,'name'=>'','category_id'=>$cats[0]['id'],'brand_id'=>$brands[0]['id'],'price'=>'','old_price'=>'','description'=>'','is_new'=>0,'stock'=>10];
if($id){ $st=$pdo->prepare('SELECT * FROM products WHERE id=?'); $st->execute([$id]); $row=$st->fetch(); if(!$row){ header('Location: admin_products.php'); exit; } $p=$row; }
if($_SERVER['REQUEST_METHOD']==='POST'){ check_csrf();
  $name=trim($_POST['name']); $cat=(int)$_POST['category_id']; $brand=(int)$_POST['brand_id'];
  $price=(int)preg_replace('/\D/','',$_POST['price']); $oldRaw=preg_replace('/\D/','',$_POST['old_price']??''); $old=$oldRaw===''?null:(int)$oldRaw;
  $desc=trim($_POST['description']); $new=isset($_POST['is_new'])?1:0; $stock=max(0,min(1000000,(int)($_POST['stock']??0)));
  $p=array_merge($p,['name'=>$name,'category_id'=>$cat,'brand_id'=>$brand,'price'=>$price,'old_price'=>$old,'description'=>$desc,'is_new'=>$new,'stock'=>$stock]);
  $ext=null; $f=$_FILES['image']??null; $ext2=null; $f2=$_FILES['image2']??null;
  if($name==='') $err='Vui lòng nhập tên sản phẩm.';
  elseif($price<1) $err='Giá bán phải lớn hơn 0.';
  elseif($old!==null && $old<=$price) $err='Giá cũ phải lớn hơn giá bán (hoặc để trống).';
  elseif($f && $f['error']!==UPLOAD_ERR_NO_FILE){
    if($f['error']!==UPLOAD_ERR_OK) $err='Tải ảnh thất bại (có thể ảnh quá lớn so với cấu hình PHP).';
    elseif($f['size']>5*1024*1024) $err='Ảnh quá lớn (tối đa 5MB).';
    else{ $info=@getimagesize($f['tmp_name']); $map=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
      if(!$info||!isset($map[$info['mime']])) $err='Chỉ nhận ảnh JPG, PNG hoặc WEBP.'; else $ext=$map[$info['mime']]; } }
  if(!$err && $f2 && $f2['error']!==UPLOAD_ERR_NO_FILE){
    if($f2['error']!==UPLOAD_ERR_OK) $err='Tải ảnh thứ 2 thất bại.';
    elseif($f2['size']>5*1024*1024) $err='Ảnh thứ 2 quá lớn (tối đa 5MB).';
    else{ $info2=@getimagesize($f2['tmp_name']); $map2=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
      if(!$info2||!isset($map2[$info2['mime']])) $err='Ảnh thứ 2 chỉ nhận JPG, PNG hoặc WEBP.'; else $ext2=$map2[$info2['mime']]; } }
  if(!$err){
    if($id){ $pdo->prepare('UPDATE products SET name=?,category_id=?,brand_id=?,price=?,old_price=?,description=?,is_new=?,stock=? WHERE id=?')->execute([$name,$cat,$brand,$price,$old,$desc,$new,$stock,$id]); }
    else{ $pdo->prepare('INSERT INTO products(name,category_id,brand_id,price,old_price,description,is_new,stock) VALUES(?,?,?,?,?,?,?,?)')->execute([$name,$cat,$brand,$price,$old,$desc,$new,$stock]); $id=(int)$pdo->lastInsertId(); }
    if($ext||isset($_POST['del_image'])) foreach(glob(__DIR__.'/images/'.$id.'.*') ?: [] as $old_f) @unlink($old_f);
    if($ext2||isset($_POST['del_image2'])) foreach(glob(__DIR__.'/images/'.$id.'_2.*') ?: [] as $old_g) @unlink($old_g);
    if($ext2) @move_uploaded_file($f2['tmp_name'],__DIR__.'/images/'.$id.'_2.'.$ext2);
    if($ext && !move_uploaded_file($f['tmp_name'],__DIR__.'/images/'.$id.'.'.$ext)){ $err='Đã lưu sản phẩm nhưng không ghi được ảnh vào thư mục images/ (kiểm tra quyền ghi).'; $p['id']=$id; }
    else{ header('Location: admin_products.php?ok='.($_POST['id']?'edit':'add')); exit; } } }
$title=$p['id']?'Sửa sản phẩm':'Thêm sản phẩm'; require 'header.php';
$hasPhoto=$p['id'] && glob(__DIR__.'/images/'.(int)$p['id'].'.*'); $hasAlt=$p['id'] && glob(__DIR__.'/images/'.(int)$p['id'].'_2.*'); ?>
<?= admin_nav('admin_products.php') ?>
<h2><?= $title ?></h2><p><a href="admin_products.php">← Danh sách sản phẩm</a></p>
<form class="panel" method="post" enctype="multipart/form-data"><input type="hidden" name="t" value="<?= csrf() ?>"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
<?php if($err) echo '<div class="msg err">'.e($err).'</div>'; ?>
<div class="two"><div>
<label for="n">Tên sản phẩm</label><input id="n" name="name" value="<?= e($p['name']) ?>" required>
<label for="c">Loại</label><select id="c" name="category_id"><?php foreach($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $p['category_id']==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
<label for="b">Nhà sản xuất</label><select id="b" name="brand_id"><?php foreach($brands as $b): ?><option value="<?= $b['id'] ?>" <?= $p['brand_id']==$b['id']?'selected':'' ?>><?= e($b['name']) ?></option><?php endforeach; ?></select>
<label for="pr">Giá bán (đồng)</label><input id="pr" name="price" inputmode="numeric" placeholder="vd: 15990000" value="<?= e($p['price']) ?>" required>
<label for="op">Giá cũ (để trống nếu không giảm giá)</label><input id="op" name="old_price" inputmode="numeric" value="<?= e($p['old_price']) ?>">
<label for="st">Số lượng tồn kho</label><input id="st" name="stock" type="number" min="0" max="1000000" value="<?= (int)$p['stock'] ?>" required>
<label><input type="checkbox" name="is_new" value="1" <?= $p['is_new']?'checked':'' ?> style="width:auto"> Hiện ở mục "Sản phẩm mới nhất"</label></div>
<div><label for="d">Mô tả / cấu hình</label><textarea id="d" name="description" rows="6"><?= e($p['description']) ?></textarea>
<label for="im">Ảnh sản phẩm (JPG, PNG, WEBP, tối đa 5MB)</label><input id="im" type="file" name="image" accept="image/jpeg,image/png,image/webp">
<label for="im2">Ảnh thứ 2 — hiện khi rê chuột (tùy chọn)</label><input id="im2" type="file" name="image2" accept="image/jpeg,image/png,image/webp">
<?php if($hasAlt): ?><label><input type="checkbox" name="del_image2" value="1" style="width:auto"> Xóa ảnh thứ 2</label><?php endif; ?>
<?php if($p['id']): ?><p class="mut">Ảnh hiện tại:</p><div class="thumb mini big2"><?= product_visual($p) ?></div>
<?php if($hasPhoto): ?><label><input type="checkbox" name="del_image" value="1" style="width:auto"> Xóa ảnh đã tải lên (quay về hình minh họa)</label><?php endif; endif; ?></div></div>
<p><button class="btn" type="submit"><?= $p['id']?'Lưu thay đổi':'Thêm sản phẩm' ?></button> <a class="btn ghost" href="admin_products.php">Hủy</a></p></form>
<?php require 'footer.php'; ?>
