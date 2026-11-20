<?php $title='Tài khoản & liên kết Gmail'; require 'header.php'; require_admin(); ensure_user_cols($pdo);
$uid=(int)($_SESSION['uid']??0); if(!$uid){ header('Location: logout.php'); exit; }
$err=''; $msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){ check_csrf(); $act=$_POST['act']??'';
  if($act==='send'){
    $em=strtolower(trim($_POST['email']??''));
    if(!filter_var($em,FILTER_VALIDATE_EMAIL)||strlen($em)>120) $err='Địa chỉ Gmail không hợp lệ.';
    elseif(!smtp_ready()) $err='Chưa cấu hình gửi mail. Hãy điền SMTP_USER và SMTP_PASS trong config.php (xem HUONG_DAN.txt).';
    elseif(rl_count('linksend')>=5) $err='Bạn đã yêu cầu quá nhiều lần. Vui lòng thử lại sau 3 phút.';
    else{ $code=(string)random_int(100000,999999); rl_hit('linksend');
      [$ok,$why]=smtp_send($em,'Mã xác nhận liên kết Gmail - '.SHOP_NAME,"Mã xác nhận liên kết Gmail cho tài khoản quản trị ".SHOP_NAME.":\n\n    $code\n\nMã có hiệu lực 10 phút. Nếu không phải bạn yêu cầu, hãy bỏ qua email này.");
      if($ok){ $_SESSION['link']=['email'=>$em,'h'=>password_hash($code,PASSWORD_DEFAULT),'exp'=>time()+600,'tries'=>0]; $msg='Đã gửi mã xác nhận 6 số tới '.$em.'. Hãy nhập mã bên dưới.'; }
      else $err='Không gửi được email: '.$why; } }
  elseif($act==='confirm'){
    $l=$_SESSION['link']??null; $c=preg_replace('/\D/','',(string)($_POST['code']??''));
    if(!$l||time()>$l['exp']) { unset($_SESSION['link']); $err='Mã đã hết hạn. Hãy gửi lại mã mới.'; }
    elseif($l['tries']>=5){ unset($_SESSION['link']); $err='Nhập sai quá nhiều lần. Hãy gửi lại mã mới.'; }
    elseif(!password_verify($c,$l['h'])){ $_SESSION['link']['tries']++; $err='Mã không đúng.'; }
    else{ $pdo->prepare('UPDATE '.T_USERS.' SET email=? WHERE id=?')->execute([$l['email'],$uid]); unset($_SESSION['link']); $msg='Đã liên kết Gmail thành công.'; } }
  elseif($act==='unlink'){ $pdo->prepare('UPDATE '.T_USERS.' SET email=NULL,reset_hash=NULL,reset_exp=NULL WHERE id=?')->execute([$uid]); unset($_SESSION['link']); $msg='Đã hủy liên kết Gmail.'; } }
$me=$pdo->prepare('SELECT username,email FROM '.T_USERS.' WHERE id=?'); $me->execute([$uid]); $me=$me->fetch();
$pending=!empty($_SESSION['link'])&&time()<$_SESSION['link']['exp']; ?>
<?= admin_nav('admin_account.php') ?>
<h2>Tài khoản &amp; liên kết Gmail</h2>
<?php if($msg) echo '<div class="msg">'.e($msg).'</div>'; if($err) echo '<div class="msg err">'.e($err).'</div>'; ?>
<div class="two">
<div class="panel"><h3>Tài khoản: <?= e($me['username']) ?></h3>
<p>Gmail đã liên kết: <b><?= $me['email']?e($me['email']):'chưa có' ?></b></p>
<p class="mut">Khi quên mật khẩu, mã khôi phục dùng một lần sẽ được gửi tới Gmail này.</p>
<?php if(!smtp_ready()): ?><div class="msg err">Chưa cấu hình gửi mail: điền <code>SMTP_USER</code> và <code>SMTP_PASS</code> trong config.php (xem HUONG_DAN.txt) rồi tải lại trang.</div><?php endif; ?>
<?php if($me['email']): ?><form method="post" onsubmit="return confirm('Hủy liên kết Gmail?')"><input type="hidden" name="t" value="<?= csrf() ?>"><button class="btn alt" name="act" value="unlink" type="submit">Hủy liên kết</button></form><?php endif; ?></div>
<div class="panel"><h3><?= $me['email']?'Đổi Gmail':'Liên kết Gmail' ?></h3>
<form method="post"><input type="hidden" name="t" value="<?= csrf() ?>"><input type="hidden" name="act" value="send">
<label for="em">Địa chỉ Gmail nhận mã khôi phục</label><input id="em" name="email" type="email" maxlength="120" placeholder="ten@gmail.com" required>
<p><button class="btn" type="submit">Gửi mã xác nhận</button></p></form>
<?php if($pending): ?><form method="post"><input type="hidden" name="t" value="<?= csrf() ?>"><input type="hidden" name="act" value="confirm">
<label for="cd">Mã xác nhận 6 số (đã gửi tới <?= e($_SESSION['link']['email']) ?>)</label><input id="cd" name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" required>
<p><button class="btn" type="submit">Xác nhận liên kết</button></p></form><?php endif; ?></div>
</div>
<?php require 'footer.php'; ?>
