<?php
// ===== Đăng nhập bằng Google (OAuth 2.0 / OpenID Connect, luồng "authorization code") =====
function google_ready(){ return GOOGLE_CLIENT_ID!=='' && GOOGLE_CLIENT_SECRET!==''; }
// Địa chỉ Google gọi lại sau khi đăng nhập; PHẢI khai báo đúng y hệt trong Google Cloud (Authorized redirect URIs)
function google_redirect_uri(){ if(GOOGLE_REDIRECT_URI!=='') return GOOGLE_REDIRECT_URI;
  $https=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')||(($_SERVER['SERVER_PORT']??'')==='443');
  $dir=rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/')),'/');
  return ($https?'https':'http').'://'.($_SERVER['HTTP_HOST']??'localhost').$dir.'/google_callback.php'; }
function g_b64d($s){ return base64_decode(strtr($s,'-_','+/').str_repeat('=',(4-strlen($s)%4)%4)); }
function g_http_post($url,$data){ $body=http_build_query($data);
  if(function_exists('curl_init')){ $ch=curl_init($url);
    curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$body,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>15,CURLOPT_SSL_VERIFYPEER=>GOOGLE_VERIFY_SSL,CURLOPT_SSL_VERIFYHOST=>GOOGLE_VERIFY_SSL?2:0,CURLOPT_HTTPHEADER=>['Content-Type: application/x-www-form-urlencoded']]);
    $r=curl_exec($ch); $err=curl_error($ch); curl_close($ch); return $r===false?[null,$err]:[json_decode($r,true),'']; }
  $ctx=stream_context_create(['http'=>['method'=>'POST','header'=>"Content-Type: application/x-www-form-urlencoded\r\n",'content'=>$body,'timeout'=>15,'ignore_errors'=>true],'ssl'=>['verify_peer'=>GOOGLE_VERIFY_SSL,'verify_peer_name'=>GOOGLE_VERIFY_SSL]]);
  $r=@file_get_contents($url,false,$ctx); return $r===false?[null,'Không kết nối được tới Google (bật extension curl hoặc allow_url_fopen trong php.ini).']:[json_decode($r,true),'']; }
function google_button($next=''){
  return '<div class="odiv"><span>HOẶC</span></div><a class="gbtn" href="google_login.php'.($next?'?next='.urlencode($next):'').'"><svg viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg><span>Tiếp tục sử dụng dịch vụ bằng Google</span></a>'; }
