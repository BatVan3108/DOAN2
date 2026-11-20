<?php
// Giới hạn số lần thử theo IP (lưu file tạm, khóa 3 phút)
function rl_file($k){ return sys_get_temp_dir().'/doan2_'.$k.'_'.md5($_SERVER['REMOTE_ADDR']??'x'); }
function rl_count($k){ $f=rl_file($k); if(!is_file($f)) return 0; $j=json_decode((string)@file_get_contents($f),true); if(!is_array($j)||time()-(int)($j['t']??0)>=180) return 0; return (int)$j['n']; }
function rl_hit($k){ $f=rl_file($k); $n=rl_count($k); $t=time(); if($n>0){ $j=json_decode((string)@file_get_contents($f),true); $t=(int)$j['t']; } @file_put_contents($f,json_encode(['n'=>$n+1,'t'=>$t])); }
function rl_clear($k){ @unlink(rl_file($k)); }
// Giữ lại tên hàm cũ cho các trang đang gọi; việc tạo bảng do acc_boot() lo
function ensure_user_cols($pdo){ acc_boot($pdo); }
// ----- Tài khoản khách hàng (tách riêng khỏi bảng users của quản trị) -----
function ensure_customers($pdo){ acc_boot($pdo); }
// Tạo CSDL/bảng tài khoản riêng (một lần mỗi phiên) và chuyển tài khoản từ bảng cũ trong CSDL chính sang nếu có
function acc_boot($pdo){
  if(($_SESSION['acc_ok']??0)>=2) return;
  try{ $pdo->exec('CREATE DATABASE IF NOT EXISTS `'.ACC_DB.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'); }catch(PDOException $x){ /* có thể không đủ quyền: sẽ báo bên dưới nếu bảng cũng không tạo được */ }
  try{
    $pdo->exec('CREATE TABLE IF NOT EXISTS '.T_USERS.'(id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50) UNIQUE NOT NULL, password_hash VARCHAR(255) NOT NULL, email VARCHAR(120) NULL, reset_hash VARCHAR(255) NULL, reset_exp DATETIME NULL) DEFAULT CHARSET=utf8mb4');
    $pdo->exec('CREATE TABLE IF NOT EXISTS '.T_CUSTOMERS.'(id INT AUTO_INCREMENT PRIMARY KEY, full_name VARCHAR(100) NOT NULL, email VARCHAR(120) NULL UNIQUE, phone VARCHAR(20) NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, reset_hash VARCHAR(255) NULL, reset_exp DATETIME NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) DEFAULT CHARSET=utf8mb4');
    if(!$pdo->query('SHOW COLUMNS FROM '.T_CUSTOMERS." LIKE 'google_sub'")->fetch()) $pdo->exec('ALTER TABLE '.T_CUSTOMERS.' ADD google_sub VARCHAR(40) NULL UNIQUE');
  }catch(PDOException $x){ exit('Không tạo được CSDL tài khoản "'.ACC_DB.'". Hãy tạo thủ công CSDL tên '.ACC_DB.' (utf8mb4_unicode_ci) trong phpMyAdmin, hoặc cấp quyền CREATE cho tài khoản MySQL, rồi tải lại trang.'); }
  foreach([['users',T_USERS],['customers',T_CUSTOMERS]] as $p){ $old=$p[0]; $new=$p[1];
    if(!$pdo->query("SHOW TABLES LIKE '$old'")->fetch()) continue;                       // chưa từng có bảng cũ
    if((int)$pdo->query('SELECT COUNT(*) FROM '.$new)->fetchColumn()>0) continue;        // kho mới đã có dữ liệu
    $oc=array_column($pdo->query("SHOW COLUMNS FROM `$old`")->fetchAll(),'Field'); $nc=array_column($pdo->query('SHOW COLUMNS FROM '.$new)->fetchAll(),'Field');
    $cols=array_values(array_intersect($oc,$nc)); if(!$cols) continue; $l='`'.implode('`,`',$cols).'`';
    $pdo->exec("INSERT INTO $new($l) SELECT $l FROM `$old`"); }
  $_SESSION['acc_ok']=2; }
function is_customer(){ return !empty($_SESSION['cid']); }
function cur_customer($pdo){ static $c=false; if($c!==false) return $c; if(!is_customer()) return $c=null;
  $q=$pdo->prepare('SELECT id,full_name,email,phone FROM '.T_CUSTOMERS.' WHERE id=?'); $q->execute([(int)$_SESSION['cid']]); $c=$q->fetch()?:null;
  if(!$c) unset($_SESSION['cid']); return $c; }
// ----- Bố cục trang đăng nhập / đăng ký / quên mật khẩu (2 cột) -----
function auth_open($h,$sub){
  $li=[['tag','Điền sẵn thông tin đặt hàng','Họ tên, số điện thoại, email tự điền khi đăng ký mua'],['shield','Bảo hành chính hãng','12–36 tháng, 1 đổi 1 trong 30 ngày đầu'],['truck','Giao hàng toàn quốc','Thanh toán khi nhận hàng (COD) hoặc chuyển khoản'],['head','Hỗ trợ nhanh chóng','Mở cửa '.SHOP_HOURS]];
  echo '<div class="authwrap"><aside class="authside"><div>'.shop_logo().'</div><div class="amid"><small>THÀNH VIÊN '.e(mb_strtoupper(SHOP_NAME)).'</small><h2>Mua sắm thông minh,<br>ưu đãi tối đa</h2><p>Tạo tài khoản để đặt hàng nhanh hơn, thông tin được điền sẵn mỗi lần mua.</p><ul>';
  foreach($li as $x) echo '<li><i>'.ico($x[0]).'</i><span><b>'.e($x[1]).'</b><small>'.e($x[2]).'</small></span></li>';
  echo '</ul></div><div class="afoot"><small>CẦN HỖ TRỢ?</small><b>Hotline: '.e(SHOP_PHONE).'</b></div></aside><main class="authmain"><div class="authbox"><a class="aback" href="index.php">← Về trang chủ</a><h1>'.e($h).'</h1><p class="asub">'.e($sub).'</p>'; }
function auth_close(){ echo '</div></main></div>'; }
// Ô mật khẩu có nút hiện/ẩn
function pw_field($id,$name,$ph,$auto,$min=0){
  return '<div class="pw"><input id="'.$id.'" name="'.$name.'" type="password" placeholder="'.$ph.'" autocomplete="'.$auto.'"'.($min?' minlength="'.$min.'"':'').' required><button type="button" class="eye" aria-label="Hiện / ẩn mật khẩu" onclick="var i=this.previousElementSibling;i.type=i.type===\'password\'?\'text\':\'password\'">'.ico('eye').'</button></div>'; }
