<?php $title='Cài đặt thanh toán'; require 'header.php'; require_admin(); ensure_orders($pdo);
$err=''; $msg=(gs('ok')==='1')?'Đã lưu cài đặt thanh toán.':'';
$v=['bank_name'=>setting_get($pdo,'bank_name')?:PAY_BANK_NAME,'bank_bin'=>setting_get($pdo,'bank_bin')?:PAY_BANK_BIN,'bank_acc'=>setting_get($pdo,'bank_acc')?:PAY_BANK_ACC,'bank_owner'=>setting_get($pdo,'bank_owner')?:PAY_BANK_OWNER,'qr_image'=>setting_get($pdo,'qr_image'),'qr_dynamic'=>(setting_get($pdo,'qr_dynamic')==='1')];
if($_SERVER['REQUEST_METHOD']==='POST'){ check_csrf();
  $v['bank_name']=mb_substr(trim($_POST['bank_name']??''),0,60); $v['bank_bin']=preg_replace('/[^A-Za-z0-9]/','',(string)($_POST['bank_bin']??'')); $v['bank_acc']=preg_replace('/\D/','',(string)($_POST['bank_acc']??'')); $v['bank_owner']=mb_substr(trim($_POST['bank_owner']??''),0,60); $v['qr_dynamic']=!empty($_POST['qr_dynamic']);
  $f=$_FILES['qr']??null; $ext=null;
  if($f && $f['error']!==UPLOAD_ERR_NO_FILE){
    if($f['error']!==UPLOAD_ERR_OK) $err='Tải ảnh thất bại (có thể ảnh quá lớn so với cấu hình PHP).';
    elseif($f['size']>5*1024*1024) $err='Ảnh quá lớn (tối đa 5MB).';
    else{ $info=@getimagesize($f['tmp_name']); $map=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp']; if(!$info||!isset($map[$info['mime']])) $err='Chỉ nhận ảnh JPG, PNG hoặc WEBP.'; else $ext=$map[$info['mime']]; } }
  if(!$err){ $old=$v['qr_image']; $new=$old;
    if($ext){ $new='qr_'.bin2hex(random_bytes(8)).'.'.$ext; if(!@move_uploaded_file($f['tmp_name'],__DIR__.'/uploads/'.$new)){ $err='Không lưu được ảnh (thư mục uploads cần cho phép ghi).'; $new=$old; } }
    elseif(!empty($_POST['rmqr'])) $new='';
    if(!$err){ foreach(['bank_name','bank_bin','bank_acc','bank_owner'] as $k) setting_set($pdo,$k,$v[$k]); setting_set($pdo,'qr_image',$new); setting_set($pdo,'qr_dynamic',$v['qr_dynamic']?'1':'0');
      if($new!==$old && preg_match('/^qr_[a-f0-9]+\.(jpg|png|webp)$/',$old)) @unlink(__DIR__.'/uploads/'.$old);
      header('Location: admin_payment.php?ok=1'); exit; } } }
$banks=['970436'=>'Vietcombank','970415'=>'VietinBank','970418'=>'BIDV','970405'=>'Agribank','970422'=>'MB Bank','970407'=>'Techcombank','970416'=>'ACB','970432'=>'VPBank','970423'=>'TPBank','970403'=>'Sacombank'];
$demo=['id'=>0,'total'=>1000000]; ?>
<?= admin_nav('admin_payment.php') ?>
<h2>Cài đặt thanh toán &amp; mã QR</h2>
<?php if($msg) echo '<div class="msg">'.e($msg).'</div>'; if($err) echo '<div class="msg err">'.e($err).'</div>'; ?>
<div class="two">
<form class="panel" method="post" enctype="multipart/form-data"><input type="hidden" name="t" value="<?= csrf() ?>"><h3>Thông tin nhận tiền</h3>
<label for="bn">Tên ngân hàng (hiển thị cho khách)</label><input id="bn" name="bank_name" maxlength="60" value="<?= e($v['bank_name']) ?>" placeholder="Ví dụ: Vietcombank">
<label for="ba">Số tài khoản</label><input id="ba" name="bank_acc" inputmode="numeric" value="<?= e($v['bank_acc']) ?>">
<label for="bo">Tên chủ tài khoản</label><input id="bo" name="bank_owner" maxlength="60" value="<?= e($v['bank_owner']) ?>" placeholder="NGUYEN VAN A">
<label for="bb">Mã ngân hàng cho VietQR <small class="mut">(chọn từ gợi ý hoặc nhập mã BIN; chỉ cần khi muốn web tự tạo QR theo từng đơn)</small></label><input id="bb" name="bank_bin" list="binl" value="<?= e($v['bank_bin']) ?>" placeholder="Ví dụ: 970436"><datalist id="binl"><?php foreach($banks as $b=>$n) echo '<option value="'.$b.'">'.e($n).'</option>'; ?></datalist>
<h3 style="margin-top:22px">Ảnh mã QR của bạn</h3>
<p class="mut" style="margin:0 0 8px;font-size:13.5px">Mở ứng dụng ngân hàng, vào mục <b>Nhận tiền / Mã QR của tôi</b>, lưu ảnh (hoặc chụp màn hình rồi cắt gọn) và tải lên đây. Khi có ảnh này, web sẽ hiện đúng ảnh của bạn trong trang đặt hàng thành công và trang chi tiết đơn.</p>
<?php if(qr_ok($v['qr_image'])): ?><div class="qrbox" style="margin:6px 0"><img class="qrimg" src="uploads/<?= e($v['qr_image']) ?>" alt="QR hiện tại"></div><label class="achk" style="margin:0 0 8px!important"><input type="checkbox" name="rmqr" value="1"> <span>Xóa ảnh QR hiện tại (quay về mã VietQR tự tạo)</span></label><?php endif; ?>
<?php if(!qr_ok($v['qr_image'])&&PAY_QR_DEFAULT!==''&&is_file(__DIR__.'/'.PAY_QR_DEFAULT)): ?><div class="qrbox" style="margin:6px 0"><img class="qrimg" src="<?= e(PAY_QR_DEFAULT) ?>" alt="QR mặc định"><small>Đang dùng ảnh QR mặc định đi kèm website. Tải ảnh khác lên để thay thế.</small></div><?php endif; ?>
<input type="file" name="qr" accept="image/jpeg,image/png,image/webp">
<label class="achk" style="margin:12px 0 0!important"><input type="checkbox" name="qr_dynamic" value="1" <?= $v['qr_dynamic']?'checked':'' ?>> <span>Dùng mã VietQR tạo tự động theo từng đơn (điền sẵn số tiền và nội dung) thay cho ảnh QR. <b>Chỉ bật sau khi bạn quét thử một lần và thấy đúng tên chủ tài khoản.</b></span></label>
<p><button class="btn" type="submit">Lưu cài đặt</button></p></form>
<div><p class="mut" style="margin:0 0 8px"><b>Xem thử</b> (đơn mẫu 1.000.000đ, mã DH0000):</p><?= pay_panel($pdo,$demo) ?>
<div class="panel" style="margin-top:14px;font-size:14px"><h3>Cách hoạt động</h3><ul style="margin:0;padding-left:18px"><li>Mặc định web hiện <b>ảnh QR đi kèm</b> (hoặc ảnh bạn tải lên). Đây là mã tĩnh nên khách tự nhập số tiền và nội dung chuyển khoản; web hiển thị sẵn hai thông tin này bên cạnh.</li><li>Khi bật <b>mã VietQR tự động</b>, mỗi đơn có mã riêng đã điền sẵn số tiền và nội dung (cần Internet để tải ảnh mã).</li><li>Khách chọn <b>chuyển khoản</b> → đơn ở trạng thái <i>Chờ xác nhận thanh toán</i>; bạn kiểm tra tiền về rồi bấm <b>Đã nhận tiền</b> ở trang Đơn hàng để xác nhận và trừ kho.</li></ul></div></div>
</div>
<?php require 'footer.php'; ?>
