<?php $title='Bảng giá'; require 'header.php'; $msg=''; $err='';
if($_SERVER['REQUEST_METHOD']==='POST'){ require_admin(); check_csrf();
  $f=$_FILES['file']??null; $ext=strtolower(pathinfo($f['name']??'',PATHINFO_EXTENSION));
  if(!$f||$f['error']!==UPLOAD_ERR_OK) $err='Tải lên thất bại. Hãy thử lại.';
  elseif(!in_array($ext,['xlsx','xls','csv','pdf','doc','docx'])) $err='Chỉ nhận tệp xlsx, xls, csv, pdf, doc, docx.';
  elseif($f['size']>5*1024*1024) $err='Tệp quá lớn (tối đa 5MB).';
  else{ $stored=bin2hex(random_bytes(8)).'.'.$ext;
    if(move_uploaded_file($f['tmp_name'],__DIR__.'/uploads/'.$stored)){ $pdo->prepare('INSERT INTO pricelists(original_name,stored_name,uploaded_by) VALUES(?,?,?)')->execute([basename($f['name']),$stored,$_SESSION['uid']??null]); $msg='Đã tải lên bảng giá mới.'; }
    else $err='Không ghi được tệp vào thư mục uploads/. Kiểm tra quyền ghi.'; } }
$cur=$pdo->query('SELECT * FROM pricelists ORDER BY id DESC LIMIT 1')->fetch();
$rows=$pdo->query(PRODUCT_SQL.' ORDER BY p.id')->fetchAll(); ?>
<?php if(is_admin()) echo admin_nav('prices.php'); ?>
<h2>Bảng giá sản phẩm</h2><div class="two"><div class="panel"><h3>Tải xuống bảng giá</h3>
<?php if($cur): ?><p>Bảng giá hiện hành: <b><?= e($cur['original_name']) ?></b><br><small class="mut">Cập nhật <?= e($cur['uploaded_at']) ?></small></p>
<?php else: ?><p>Chưa có bảng giá do quản trị tải lên. Bạn có thể tải bảng giá tự động theo dữ liệu hiện tại (CSV, mở được bằng Excel).</p><?php endif; ?>
<a class="btn" href="download.php">Tải xuống bảng giá</a></div>
<div class="panel"><h3>Tải lên bảng giá</h3><?php if($msg) echo '<div class="msg">'.e($msg).'</div>'; if($err) echo '<div class="msg err">'.e($err).'</div>'; ?>
<?php if(is_admin()): ?><form method="post" enctype="multipart/form-data"><input type="hidden" name="t" value="<?= csrf() ?>"><label for="up">Chọn tệp (Excel, CSV, PDF, Word — tối đa 5MB)</label><input id="up" type="file" name="file" required><p><button class="btn" type="submit">Tải lên</button></p></form>
<?php else: ?><p>Chỉ quản trị viên được tải lên. Vui lòng đăng nhập.</p><a class="btn alt" href="login.php">Đăng nhập quản trị</a><?php endif; ?></div></div>
<div class="panel scroll" style="margin-top:16px"><table><tr><th>Mã</th><th>Sản phẩm</th><th>Loại</th><th>Hãng</th><th>Giá</th></tr>
<?php foreach($rows as $r): ?><tr><td><?= $r['id'] ?></td><td><a href="product.php?id=<?= (int)$r['id'] ?>"><?= e($r['name']) ?></a></td><td><?= e($r['category']) ?></td><td><?= e($r['brand']) ?></td><td><?= vnd($r['price']) ?></td></tr><?php endforeach; ?></table></div>
<?php require 'footer.php'; ?>
