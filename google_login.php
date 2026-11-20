<?php require_once 'config.php';
if(is_admin()){ header('Location: admin.php'); exit; }
if(!google_ready()){ header('Location: login.php?g=cfg'); exit; }
$_SESSION['g_state']=bin2hex(random_bytes(16)); $_SESSION['g_nonce']=bin2hex(random_bytes(16)); $_SESSION['g_next']=(gs('next')==='checkout.php')?'checkout.php':'';
header('Location: https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query(['client_id'=>GOOGLE_CLIENT_ID,'redirect_uri'=>google_redirect_uri(),'response_type'=>'code','scope'=>'openid email profile','state'=>$_SESSION['g_state'],'nonce'=>$_SESSION['g_nonce'],'prompt'=>'select_account']));
exit;
