<?php $title='Đăng nhập'; $bare=true; require 'header.php'; ensure_customers($pdo); $err='';
$next=((($_POST['next']??gs('next'))==='checkout.php')?'checkout.php':'');
if(is_admin()){ header('Location: admin.php'); exit; } if(is_customer()){ header('Location: '.($next?:'index.php')); exit; }
if($_SERVER['REQUEST_METHOD']==='POST'){ check_csrf();
  $id=trim($_POST['u']??''); $pw=(string)($_POST['p']??'');
  if(rl_count('login')>=8) $err='Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau 3 phút.';
  else{
    $st=$pdo->prepare('SELECT * FROM '.T_USERS.' WHERE username=?'); $st->execute([$id]); $u=$st->fetch();
    if($u&&password_verify($pw,$u['password_hash'])){ rl_clear('login'); session_regenerate_id(true); $_SESSION['admin']=1; $_SESSION['uid']=$u['id']; header('Location: admin.php'); exit; }
    $st=$pdo->prepare('SELECT * FROM '.T_CUSTOMERS.' WHERE email=? OR phone=? LIMIT 1'); $st->execute([strtolower($id),preg_replace('/[\s.\-]/','',$id)]); $c=$st->fetch();
    if($c&&password_verify($pw,$c['password_hash'])){ rl_clear('login'); session_regenerate_id(true); $_SESSION['cid']=$c['id']; header('Location: '.($next?:'index.php')); exit; }
    rl_hit('login'); usleep(400000); $err='Sai thông tin đăng nhập hoặc mật khẩu.'; } }
$gm=['cfg'=>'Chưa cấu hình Đăng nhập bằng Google (GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET trong config.local.php, xem HUONG_DAN.txt).','denied'=>'Bạn đã hủy đăng nhập bằng Google.','state'=>'Phiên đăng nhập Google không hợp lệ hoặc đã hết hạn. Vui lòng thử lại.','fail'=>'Không đăng nhập được bằng Google. Vui lòng thử lại.','unverified'=>'Email Google của bạn chưa được xác minh.','admin'=>'Email này thuộc tài khoản quản trị. Hãy đăng nhập bằng tên đăng nhập và mật khẩu quản trị.'];
if($err===''&&isset($gm[gs('g')])){ $err=$gm[gs('g')]; if(!empty($_SESSION['g_detail'])&&in_array($_SERVER['REMOTE_ADDR']??'',['127.0.0.1','::1'],true)) $err.=' (Chi tiết, chỉ hiện trên localhost: '.$_SESSION['g_detail'].')'; } unset($_SESSION['g_detail']);
auth_open($next?'Đăng nhập để tiếp tục đặt hàng':'Đăng nhập',$next?'Giỏ hàng của bạn vẫn được giữ nguyên sau khi đăng nhập.':'Đăng nhập để đặt hàng nhanh hơn và nhận thông tin từ cửa hàng.'); ?>
<form method="post"><input type="hidden" name="t" value="<?= csrf() ?>"><?php if($next): ?><input type="hidden" name="next" value="<?= e($next) ?>"><?php endif; ?>
<?php if(gs('ok')==='pw') echo '<div class="msg">Đã đổi mật khẩu. Hãy đăng nhập bằng mật khẩu mới.</div>'; if(gs('ok')==='reg') echo '<div class="msg">Đăng ký thành công. Hãy đăng nhập.</div>'; if($err) echo '<div class="msg err">'.e($err).'</div>'; ?>
<label for="u">Số điện thoại, Email hoặc Tên đăng nhập <i class="req">*</i></label><input id="u" name="u" value="<?= e($_POST['u']??'') ?>" placeholder="Nhập số điện thoại, email hoặc tên đăng nhập" autocomplete="username" required>
<label for="p">Mật khẩu <i class="req">*</i></label><?= pw_field('p','p','Nhập mật khẩu','current-password') ?>
<div class="arow"><span></span><a class="alink" href="forgot.php">Quên mật khẩu?</a></div>
<button class="abtn" type="submit">Đăng nhập</button><?= google_button($next) ?>
<p class="afine">Chưa có tài khoản? <a class="alink" href="register.php<?= $next?'?next='.$next:'' ?>">Đăng ký ngay</a></p></form>
<?php auth_close(); require 'footer.php'; ?>
