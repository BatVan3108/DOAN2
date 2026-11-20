<?php $title='Đăng ký tài khoản'; $bare=true; require 'header.php'; ensure_user_cols($pdo); ensure_customers($pdo); $err='';
$next=((($_POST['next']??gs('next'))==='checkout.php')?'checkout.php':'');
if(is_admin()){ header('Location: admin.php'); exit; } if(is_customer()){ header('Location: '.($next?:'index.php')); exit; }
if($_SERVER['REQUEST_METHOD']==='POST'){ check_csrf();
  $name=trim(preg_replace('/\s+/',' ',(string)($_POST['name']??''))); $c=strtolower(trim($_POST['c']??'')); $p1=(string)($_POST['p1']??''); $p2=(string)($_POST['p2']??'');
  $email=null; $phone=null;
  if(filter_var($c,FILTER_VALIDATE_EMAIL)&&strlen($c)<=120) $email=$c; else{ $d=preg_replace('/[\s.\-]/','',$c); if(preg_match('/^0\d{9}$/',$d)) $phone=$d; }
  if(rl_count('reg')>=5) $err='Bạn thao tác quá nhiều lần. Vui lòng thử lại sau 3 phút.';
  elseif(mb_strlen($name)<2||mb_strlen($name)>100) $err='Vui lòng nhập họ và tên (2–100 ký tự).';
  elseif(!$email&&!$phone) $err='Hãy nhập email hợp lệ hoặc số điện thoại 10 số bắt đầu bằng 0.';
  elseif(strlen($p1)<8) $err='Mật khẩu phải có ít nhất 8 ký tự.';
  elseif($p1!==$p2) $err='Hai lần nhập mật khẩu không khớp.';
  elseif(empty($_POST['terms'])) $err='Bạn cần đồng ý với Điều khoản sử dụng và Chính sách bảo mật.';
  else{ rl_hit('reg');
    if($email){ $q=$pdo->prepare('SELECT 1 FROM '.T_CUSTOMERS.' WHERE email=? UNION SELECT 1 FROM '.T_USERS.' WHERE LOWER(email)=?'); $q->execute([$email,$email]); }
    else{ $q=$pdo->prepare('SELECT 1 FROM '.T_CUSTOMERS.' WHERE phone=?'); $q->execute([$phone]); }
    if($q->fetch()) $err='Email hoặc số điện thoại này đã được đăng ký. Hãy đăng nhập hoặc dùng "Quên mật khẩu".';
    else{ try{
        $pdo->prepare('INSERT INTO '.T_CUSTOMERS.'(full_name,email,phone,password_hash) VALUES(?,?,?,?)')->execute([$name,$email,$phone,password_hash($p1,PASSWORD_DEFAULT)]);
        session_regenerate_id(true); $_SESSION['cid']=(int)$pdo->lastInsertId(); header('Location: '.($next?:'index.php')); exit;
      }catch(PDOException $x){ $err='Email hoặc số điện thoại này đã được đăng ký.'; } } } }
auth_open('Đăng ký tài khoản','Tạo tài khoản '.SHOP_NAME.' để mua sắm nhanh hơn.'); ?>
<form method="post"><input type="hidden" name="t" value="<?= csrf() ?>"><?php if($next): ?><input type="hidden" name="next" value="<?= e($next) ?>"><?php endif; ?>
<?php if($err) echo '<div class="msg err">'.e($err).'</div>'; ?>
<label for="n" style="margin-top:0">Họ và tên <i class="req">*</i></label><input id="n" name="name" maxlength="100" value="<?= e($_POST['name']??'') ?>" placeholder="Ví dụ: Nguyễn Ngọc Vy" autocomplete="name" required>
<label for="c">Email hoặc số điện thoại <i class="req">*</i></label><input id="c" name="c" maxlength="120" value="<?= e($_POST['c']??'') ?>" placeholder="Ví dụ: you@gmail.com hoặc 0901234567" autocomplete="username" required>
<p class="ahint">Nên dùng email để có thể nhận mã OTP khi quên mật khẩu.</p>
<label for="p1">Mật khẩu <i class="req">*</i></label><?= pw_field('p1','p1','Nhập mật khẩu (tối thiểu 8 ký tự)','new-password',8) ?>
<label for="p2">Nhập lại mật khẩu <i class="req">*</i></label><?= pw_field('p2','p2','Nhập lại mật khẩu','new-password',8) ?>
<label class="achk"><input type="checkbox" name="terms" value="1" required> <span>Tôi đồng ý với <b class="alink">Điều khoản sử dụng</b> và <b class="alink">Chính sách bảo mật</b> của <?= e(SHOP_NAME) ?>.</span></label>
<button class="abtn" type="submit">Đăng ký</button><?= google_button($next) ?>
<p class="afine">Đã có tài khoản? <a class="alink" href="login.php<?= $next?'?next='.$next:'' ?>">Đăng nhập</a></p></form>
<?php auth_close(); require 'footer.php'; ?>
