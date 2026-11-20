<?php require_once 'config.php'; ensure_customers($pdo);
$st=(string)($_SESSION['g_state']??''); $nonce=(string)($_SESSION['g_nonce']??''); $next=(string)($_SESSION['g_next']??'');
unset($_SESSION['g_state'],$_SESSION['g_nonce'],$_SESSION['g_next']);
function gfail($c,$next,$detail=''){ if($detail!=='') $_SESSION['g_detail']=$detail; header('Location: login.php?g='.$c.($next?'&next='.urlencode($next):'')); exit; }
if(!google_ready()) gfail('cfg',$next);
if(gs('error')!=='') gfail('denied',$next);
if($st===''||!hash_equals($st,gs('state'))||gs('code')==='') gfail('state',$next);   // chống giả mạo yêu cầu (CSRF)
// Đổi mã tạm (code) lấy id_token trực tiếp từ Google qua HTTPS
[$tok,$why]=g_http_post('https://oauth2.googleapis.com/token',['code'=>gs('code'),'client_id'=>GOOGLE_CLIENT_ID,'client_secret'=>GOOGLE_CLIENT_SECRET,'redirect_uri'=>google_redirect_uri(),'grant_type'=>'authorization_code']);
if(!is_array($tok)||empty($tok['id_token'])) gfail('fail',$next,$why!==''?$why:trim(($tok['error']??'').' '.($tok['error_description']??'')));
$p=explode('.',(string)$tok['id_token']); $c=(count($p)===3)?json_decode(g_b64d($p[1]),true):null;
// Kiểm tra các thông tin trong id_token: đúng nơi cấp, đúng ứng dụng, chưa hết hạn, đúng nonce
if(!is_array($c)||!in_array($c['iss']??'',['https://accounts.google.com','accounts.google.com'],true)||($c['aud']??'')!==GOOGLE_CLIENT_ID||(int)($c['exp']??0)<=time()||!hash_equals($nonce,(string)($c['nonce']??''))||empty($c['sub'])||empty($c['email'])) gfail('fail',$next,'id_token không hợp lệ');
if(!in_array($c['email_verified']??false,[true,'true',1],true)) gfail('unverified',$next);
$email=strtolower(trim((string)$c['email'])); $sub=substr((string)$c['sub'],0,40);
$name=mb_substr(trim((string)($c['name']??'')),0,100); if($name==='') $name=(string)strstr($email,'@',true);
$s=$pdo->prepare('SELECT * FROM '.T_CUSTOMERS.' WHERE google_sub=? OR email=? LIMIT 1'); $s->execute([$sub,$email]); $cu=$s->fetch();
if(!$cu){
  $a=$pdo->prepare('SELECT 1 FROM '.T_USERS.' WHERE LOWER(email)=?'); $a->execute([$email]); if($a->fetch()) gfail('admin',$next);   // email của tài khoản quản trị: không cho vào bằng Google
  $pdo->prepare('INSERT INTO '.T_CUSTOMERS.'(full_name,email,password_hash,google_sub) VALUES(?,?,?,?)')->execute([$name,$email,password_hash(bin2hex(random_bytes(16)),PASSWORD_DEFAULT),$sub]);
  $cid=(int)$pdo->lastInsertId();
} else{ $cid=(int)$cu['id']; if((string)($cu['google_sub']??'')!==$sub) $pdo->prepare('UPDATE '.T_CUSTOMERS.' SET google_sub=? WHERE id=?')->execute([$sub,$cid]); }
session_regenerate_id(true); $_SESSION['cid']=$cid;
header('Location: '.($next?:'index.php')); exit;
