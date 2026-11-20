<?php
// Gửi email qua SMTP của Gmail (smtp.gmail.com:465, SSL) bằng mã PHP thuần, không cần thư viện ngoài.
function smtp_ready(){ return SMTP_USER!=='' && SMTP_PASS!==''; }
function smtp_read($fp){ $r=''; while(($l=fgets($fp,515))!==false){ $r.=$l; if(strlen($l)<4||$l[3]===' ') break; } return $r; }
function smtp_cmd($fp,$cmd,$ok){ if($cmd!==null) fwrite($fp,$cmd."\r\n"); $r=smtp_read($fp);
  if(!in_array(substr($r,0,3),(array)$ok,true)) throw new Exception(trim($r)!==''?trim($r):'Máy chủ mail không phản hồi.'); return $r; }
function smtp_send($to,$subject,$body){
  if(!filter_var($to,FILTER_VALIDATE_EMAIL)) return [false,'Địa chỉ email không hợp lệ.'];
  if(!smtp_ready()) return [false,'Chưa cấu hình SMTP_USER / SMTP_PASS trong config.php.'];
  $fp=null;
  try{
    $v=SMTP_VERIFY_SSL; $ctx=stream_context_create(['ssl'=>['verify_peer'=>$v,'verify_peer_name'=>$v]]);
    $fp=@stream_socket_client('ssl://smtp.gmail.com:465',$en,$es,15,STREAM_CLIENT_CONNECT,$ctx);
    if(!$fp) throw new Exception('Không kết nối được smtp.gmail.com:465 ('.$es.'). Kiểm tra Internet/tường lửa; nếu lỗi chứng chỉ SSL trên localhost, đặt SMTP_VERIFY_SSL=false trong config.php.');
    stream_set_timeout($fp,15);
    smtp_cmd($fp,null,'220'); smtp_cmd($fp,'EHLO localhost','250');
    smtp_cmd($fp,'AUTH LOGIN','334'); smtp_cmd($fp,base64_encode(SMTP_USER),'334');
    smtp_cmd($fp,base64_encode(str_replace(' ','',SMTP_PASS)),'235');
    smtp_cmd($fp,'MAIL FROM:<'.SMTP_USER.'>','250'); smtp_cmd($fp,'RCPT TO:<'.$to.'>',['250','251']); smtp_cmd($fp,'DATA','354');
    $h="Date: ".date('r')."\r\nFrom: =?UTF-8?B?".base64_encode(SHOP_NAME)."?= <".SMTP_USER.">\r\nTo: <$to>\r\nSubject: =?UTF-8?B?".base64_encode($subject)."?=\r\nMessage-ID: <".bin2hex(random_bytes(8))."@zeronight.local>\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
    fwrite($fp,$h.chunk_split(base64_encode($body))."\r\n.\r\n"); smtp_cmd($fp,null,'250');
    @fwrite($fp,"QUIT\r\n"); fclose($fp); return [true,''];
  }catch(Exception $e){ if(is_resource($fp)) @fclose($fp);
    $m=$e->getMessage(); if(strpos($m,'535')===0) $m.=' (Sai Gmail hoặc Mật khẩu ứng dụng. Phải dùng "Mật khẩu ứng dụng" 16 ký tự, không phải mật khẩu Gmail.)';
    return [false,$m]; } }
