<?php $title='Quên mật khẩu'; $bare=true; require 'header.php';
if(is_admin()){ header('Location: admin.php'); exit; } if(is_customer()){ header('Location: index.php'); exit; }
ensure_user_cols($pdo); ensure_customers($pdo);
$err=''; $msg=''; $act=''; $local=in_array($_SERVER['REMOTE_ADDR']??'',['127.0.0.1','::1'],true);
$smtp=smtp_ready(); $keyok=(strlen(RECOVERY_KEY)>=12);
// Tìm tài khoản theo email / tên đăng nhập (admin) / email / số điện thoại (khách hàng)
function fp_find($pdo,$id){ $id=strtolower(trim($id)); if($id==='') return null; $ph=preg_replace('/[\s.\-]/','',$id);
  $q=$pdo->prepare('SELECT id,email,reset_hash,reset_exp FROM '.T_USERS.' WHERE username=? OR LOWER(email)=? LIMIT 1'); $q->execute([$id,$id]); $r=$q->fetch(); if($r){ $r['tbl']=T_USERS; return $r; }
  $q=$pdo->prepare('SELECT id,email,reset_hash,reset_exp FROM '.T_CUSTOMERS.' WHERE LOWER(email)=? OR phone=? LIMIT 1'); $q->execute([$id,$ph]); $r=$q->fetch(); if($r){ $r['tbl']=T_CUSTOMERS; return $r; }
  return null; }
if($_SERVER['REQUEST_METHOD']==='POST'){ check_csrf(); $act=$_POST['act']??''; $u=trim($_POST['u']??''); $row=fp_find($pdo,$u);
  // ---- Gửi mã OTP về email của tài khoản ----
  if($act==='send'){
    if(!$smtp) $err='Chưa cấu hình gửi mail (SMTP_USER / SMTP_PASS trong config.php).';
    elseif($u==='') $err='Vui lòng nhập email hoặc tên đăng nhập.';
    elseif(rl_count('fpsend')>=3) $err='Bạn đã yêu cầu quá nhiều lần. Vui lòng thử lại sau 3 phút.';
    else{ rl_hit('fpsend'); usleep(400000);
      if($row && $row['email']){
        $code=sprintf('%06d',random_int(0,999999));
        [$ok,$why]=smtp_send($row['email'],'Mã OTP khôi phục mật khẩu - '.SHOP_NAME,"Mã OTP khôi phục mật khẩu ".SHOP_NAME.":\n\n    $code\n\nMã dùng một lần, có hiệu lực 10 phút. Nếu không phải bạn yêu cầu, hãy bỏ qua email này; mật khẩu của bạn vẫn an toàn.");
        if($ok) $pdo->prepare('UPDATE '.$row['tbl'].' SET reset_hash=?,reset_exp=? WHERE id=?')->execute([password_hash($code,PASSWORD_DEFAULT),date('Y-m-d H:i:s',time()+600),$row['id']]);
        elseif($local) $err='Không gửi được email (chỉ hiện khi chạy localhost): '.$why; }
      if(!$err) $msg='Nếu tài khoản tồn tại và đã có email, mã OTP vừa được gửi tới hộp thư đó (kiểm tra cả mục Spam). Nhập mã vào ô bên dưới.'; } }
  // ---- Khôi phục tại máy (thay cho reset.php cũ): chỉ chạy khi truy cập từ localhost, không cần mã ----
  elseif($act==='local' && $local){
    $q=$pdo->prepare('SELECT id FROM '.T_USERS.' WHERE username=?'); $q->execute([$u]); $lr=$q->fetch();
    $p1=(string)($_POST['p1']??''); $p2=(string)($_POST['p2']??''); $none=((int)$pdo->query('SELECT COUNT(*) FROM '.T_USERS.'')->fetchColumn()===0);
    if(!$lr && !($none && $u!=='')) $err='Không có tài khoản quản trị này.';
    elseif(strlen($p1)<8) $err='Mật khẩu mới phải có ít nhất 8 ký tự.';
    elseif($p1!==$p2) $err='Hai lần nhập mật khẩu không khớp.';
    elseif($lr){ $pdo->prepare('UPDATE '.T_USERS.' SET password_hash=?,reset_hash=NULL,reset_exp=NULL WHERE id=?')->execute([password_hash($p1,PASSWORD_DEFAULT),$lr['id']]); header('Location: login.php?ok=pw'); exit; }
    else{ $pdo->prepare('INSERT INTO '.T_USERS.'(username,password_hash) VALUES(?,?)')->execute([$u,password_hash($p1,PASSWORD_DEFAULT)]); header('Location: login.php?ok=pw'); exit; } }
  // ---- Nhập mã OTP + mật khẩu mới ----
  elseif($act==='reset'){
    $raw=(string)($_POST['k']??''); $kd=preg_replace('/\D/','',$raw); $ku=strtoupper(preg_replace('/[\s-]/','',$raw));
    $p1=(string)($_POST['p1']??''); $p2=(string)($_POST['p2']??'');
    if(rl_count('fpverify')>=5){ $err='Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau 3 phút.'; }
    else{ $good=false;
      if($row && $kd!=='' && $row['reset_hash'] && $row['reset_exp'] && strtotime($row['reset_exp'])>time() && password_verify($kd,$row['reset_hash'])) $good=true;
      // RECOVERY_KEY trong config.php: chỉ cho tài khoản quản trị khi KHÔNG thể gửi mail (chưa cấu hình SMTP) hoặc chưa liên kết Gmail
      elseif($row && $row['tbl']===T_USERS && $keyok && (!$smtp||!$row['email']) && hash_equals(strtoupper(preg_replace('/[\s-]/','',RECOVERY_KEY)),$ku)) $good=true;
      if(!$good){ rl_hit('fpverify'); usleep(600000); $err='Thông tin hoặc mã không đúng / đã hết hạn.'; }
      elseif(strlen($p1)<8) $err='Mật khẩu mới phải có ít nhất 8 ký tự.';
      elseif($p1!==$p2) $err='Hai lần nhập mật khẩu không khớp.';
      else{ $pdo->prepare('UPDATE '.$row['tbl'].' SET password_hash=?,reset_hash=NULL,reset_exp=NULL WHERE id=?')->execute([password_hash($p1,PASSWORD_DEFAULT),$row['id']]); rl_clear('fpverify'); header('Location: login.php?ok=pw'); exit; } } } }
$uv=e($_POST['u']??'');
auth_open('Quên mật khẩu','Nhập email hoặc tên đăng nhập, chúng tôi sẽ gửi mã OTP để đặt lại mật khẩu.'); ?>
<form method="post"><input type="hidden" name="t" value="<?= csrf() ?>">
<button type="submit" name="act" value="reset" hidden tabindex="-1" aria-hidden="true"></button>
<?php if($msg) echo '<div class="msg">'.e($msg).'</div>'; if($err&&$act!=='local') echo '<div class="msg err">'.e($err).'</div>'; ?>
<?php if(!$smtp&&!$keyok): ?><div class="msg err">Chưa thiết lập cách khôi phục. Hãy điền SMTP_USER/SMTP_PASS (gửi mã qua Gmail) hoặc RECOVERY_KEY (tối thiểu 12 ký tự) trong config.php.</div><?php endif; ?>
<label for="u" style="margin-top:0">Email hoặc tên đăng nhập <i class="req">*</i></label>
<div class="inrow"><input id="u" name="u" value="<?= $uv ?>" placeholder="Ví dụ: you@gmail.com" autocomplete="username" required><button class="abtn sm" type="submit" name="act" value="send" formnovalidate>Gửi mã</button></div>
<?php if(!$smtp): ?><p class="ahint">Chưa cấu hình Gmail gửi thư nên nút <b>Gửi mã</b> chưa dùng được. Điền <code>SMTP_USER</code> và <code>SMTP_PASS</code> trong config.php (xem HUONG_DAN.txt). Trong lúc chờ, quản trị viên có thể nhập <code>RECOVERY_KEY</code> vào ô mã bên dưới.</p><?php endif; ?>
<label for="k">Mã OTP <i class="req">*</i></label><input id="k" name="k" placeholder="<?= $smtp?'Nhập mã OTP 6 số nhận qua email':'Nhập mã OTP / RECOVERY_KEY' ?>" autocomplete="one-time-code" required>
<label for="p1">Mật khẩu mới <i class="req">*</i></label><?= pw_field('p1','p1','Nhập mật khẩu mới (tối thiểu 8 ký tự)','new-password',8) ?>
<label for="p2">Nhập lại mật khẩu mới <i class="req">*</i></label><?= pw_field('p2','p2','Nhập lại mật khẩu mới','new-password',8) ?>
<button class="abtn" type="submit" name="act" value="reset">Đặt lại mật khẩu</button>
<p class="afine"><a class="alink" href="login.php">← Quay lại đăng nhập</a></p></form>
<?php if($local): ?>
<details class="loc" <?= $act==='local'?'open':'' ?>><summary>Khôi phục tại máy (localhost)</summary>
<form method="post"><input type="hidden" name="t" value="<?= csrf() ?>"><input type="hidden" name="act" value="local">
<p class="ahint" style="margin-top:0">Chỉ hiện khi bạn mở trang từ chính máy chạy XAMPP. Đặt mật khẩu quản trị mới ngay, không cần mã hay Gmail. Nếu bảng users đang trống, tài khoản sẽ được tạo mới.</p>
<?php if($err&&$act==='local') echo '<div class="msg err">'.e($err).'</div>'; ?>
<label for="u3">Tên đăng nhập quản trị</label><input id="u3" name="u" value="<?= $uv?:'admin' ?>" required>
<label for="q1">Mật khẩu mới</label><?= pw_field('q1','p1','Tối thiểu 8 ký tự','new-password',8) ?>
<label for="q2">Nhập lại mật khẩu mới</label><?= pw_field('q2','p2','Nhập lại mật khẩu mới','new-password',8) ?>
<button class="abtn dark" type="submit">Đặt mật khẩu tại máy</button></form></details>
<?php endif; ?>
<?php auth_close(); require 'footer.php'; ?>
