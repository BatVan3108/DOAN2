<?php
require_once __DIR__.'/art.php';
require_once __DIR__.'/auth.php';
require_once __DIR__.'/cartlib.php';
require_once __DIR__.'/annlib.php';
require_once __DIR__.'/googlelib.php';
require_once __DIR__.'/installer.php';
require_once __DIR__.'/mail.php';
// Cài đặt riêng của máy bạn (MySQL, thông tin cửa hàng, Gmail gửi mã, mã khôi phục) nên đặt trong file config.local.php
// (sao chép từ config.local.php.example). File đó KHÔNG nằm trong bản zip nên chép đè bản mới không làm mất cài đặt.
if(is_file(__DIR__.'/config.local.php')) require __DIR__.'/config.local.php';
// Giá trị mặc định bên dưới chỉ dùng khi config.local.php chưa khai báo
defined('DB_HOST')||define('DB_HOST','localhost'); defined('DB_NAME')||define('DB_NAME','doan2'); defined('DB_USER')||define('DB_USER','root'); defined('DB_PASS')||define('DB_PASS','');
defined('SHOP_NAME')||define('SHOP_NAME','ZeroNightComputer'); defined('SHOP_PHONE')||define('SHOP_PHONE','0900 000 000'); defined('SHOP_EMAIL')||define('SHOP_EMAIL','lienhe@zeronightcomputer.vn'); defined('SHOP_HOURS')||define('SHOP_HOURS','8:00 – 21:00 mỗi ngày');
// Mã khôi phục dùng ở forgot.php khi chưa cấu hình Gmail. HÃY ĐỔI thành chuỗi bí mật của riêng bạn (tối thiểu 12 ký tự).
defined('RECOVERY_KEY')||define('RECOVERY_KEY','ZN-CCBF-EC58-F28D-1A05');
// Gmail dùng để GỬI mã khôi phục. SMTP_PASS là "Mật khẩu ứng dụng" 16 ký tự của Google (không phải mật khẩu Gmail).
defined('SMTP_USER')||define('SMTP_USER',''); defined('SMTP_PASS')||define('SMTP_PASS',''); defined('SMTP_VERIFY_SSL')||define('SMTP_VERIFY_SSL',true);
// Thông tin nhận tiền mặc định (có thể sửa ở Quản trị > Thanh toán hoặc ghi đè trong config.local.php). PAY_QR_DEFAULT là ảnh mã QR đi kèm website.
defined('PAY_BANK_NAME')||define('PAY_BANK_NAME','MB Bank'); defined('PAY_BANK_BIN')||define('PAY_BANK_BIN','970422'); defined('PAY_BANK_ACC')||define('PAY_BANK_ACC','0989891671'); defined('PAY_BANK_OWNER')||define('PAY_BANK_OWNER','TRUONG HOANG QUY'); defined('PAY_QR_DEFAULT')||define('PAY_QR_DEFAULT','images/qr_mb.png');
// Đăng nhập bằng Google: lấy Client ID / Client Secret trong Google Cloud (xem HUONG_DAN.txt). GOOGLE_REDIRECT_URI để trống = tự suy ra từ địa chỉ web.
defined('GOOGLE_CLIENT_ID')||define('GOOGLE_CLIENT_ID',''); defined('GOOGLE_CLIENT_SECRET')||define('GOOGLE_CLIENT_SECRET',''); defined('GOOGLE_REDIRECT_URI')||define('GOOGLE_REDIRECT_URI',''); defined('GOOGLE_VERIFY_SSL')||define('GOOGLE_VERIFY_SSL',true);
// Tài khoản (quản trị + khách hàng) lưu ở CSDL RIÊNG, nên xóa / import lại CSDL chính cũng không mất mật khẩu
const ACC_DB=DB_NAME.'_accounts'; const T_USERS='`'.DB_NAME.'_accounts`.`users`'; const T_CUSTOMERS='`'.DB_NAME.'_accounts`.`customers`';
const PRICE_RANGES=[['Dưới 10 triệu',0,10000000],['10 – 20 triệu',10000000,20000000],['20 – 30 triệu',20000000,30000000],['Trên 30 triệu',30000000,2000000000]];

ob_start();
if(PHP_VERSION_ID>=70300) session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax']);
session_start();
installer_ensure();   // lần đầu: tự nhập sql/doan2.sql và sql/doan2_accounts.sql nếu CSDL chưa có
$pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS,
  [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
acc_boot($pdo);
shop_boot($pdo);

// Logo: nếu có file images/logo.png|svg|webp|jpg thì dùng file đó, nếu không thì vẽ logo chữ Z + tên cửa hàng
function shop_logo(){
  $name=SHOP_NAME; $a=preg_replace('/Computer$/','',$name); $b=($a!==$name)?'Computer':'';
  foreach(['svg','png','webp','jpg'] as $x){ if(is_file(__DIR__.'/images/logo.'.$x)) return '<a class="logo" href="index.php"><img class="logoimg" src="images/logo.'.$x.'" alt="'.e($name).'"></a>'; }
  return '<a class="logo" href="index.php"><span class="lg"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 5h14L5 19h14"/></svg></span><span class="lt"><b>'.e($a).'<i>'.e($b).'</i></b><small>Công nghệ cho cuộc sống tốt đẹp hơn</small></span></a>';
}
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function vnd($n){ return number_format((int)$n, 0, ',', '.').'đ'; }
function gs($k){ return (isset($_GET[$k]) && is_string($_GET[$k])) ? trim($_GET[$k]) : ''; }
function is_admin(){ return !empty($_SESSION['admin']); }
function require_admin(){ if(!is_admin()){ header('Location: login.php'); exit; } }
function csrf(){ if(empty($_SESSION['t'])) $_SESSION['t']=bin2hex(random_bytes(16)); return $_SESSION['t']; }
function check_csrf(){ if(!hash_equals($_SESSION['t']??'', (string)($_POST['t']??''))){ http_response_code(400); exit('Phiên làm việc không hợp lệ, hãy tải lại trang.'); } }

function admin_nav($active){
  $items=['admin.php'=>'Đơn hàng','admin_stats.php'=>'Thống kê','admin_products.php'=>'Sản phẩm','admin_announcements.php'=>'Thông báo & khuyến mãi','prices.php'=>'Bảng giá','admin_payment.php'=>'Thanh toán','admin_account.php'=>'Tài khoản & Gmail'];
  $h='<div class="anav">'; foreach($items as $f=>$t) $h.='<a class="'.($active===$f?'on':'').'" href="'.$f.'">'.e($t).'</a>';
  return $h.'</div>';
}
function discount_pct($p){ return ($p['old_price'] && $p['old_price']>$p['price']) ? (int)round((1-$p['price']/$p['old_price'])*100) : 0; }
// Tách mô tả "Core i5, RAM 16GB, SSD 512GB..." thành các nhãn cấu hình ngắn
function spec_chips($d,$n=4,$w=26){
  $d=trim((string)$d); if($d==='') return [];
  $out=[]; foreach(preg_split('/\s*[,;\n]\s*/u', rtrim($d,". \n\r")) as $x){ $x=trim($x); if($x==='') continue;
    $out[]=mb_strimwidth($x,0,$w,'…','UTF-8'); if(count($out)>=$n) break; }
  return $out;
}
function brand_slug($n){ return strtolower(preg_replace('/[^a-z0-9]/i','',$n)); }
function brand_img($name){
  foreach(['svg','png','webp','jpg'] as $x){ $f='images/brands/'.brand_slug($name).'.'.$x;
    if(is_file(__DIR__.'/'.$f)) return '<img src="'.$f.'" alt="'.e($name).'" loading="lazy">'; }
  return '';
}
// Ảnh thứ 2 (hiện khi rê chuột): images/<id>_2.jpg|png|webp
function product_alt($p){
  foreach(['jpg','jpeg','png','webp'] as $x){ $f='images/'.(int)$p['id'].'_2.'.$x;
    if(is_file(__DIR__.'/'.$f)) return '<img src="'.$f.'" alt="'.e($p['name']).' - ảnh 2" loading="lazy">'; }
  return '';
}
// Khối lật 2 mặt: mặt trước = ảnh chính, mặt sau = ảnh thứ 2 (nếu có) hoặc bảng cấu hình nổi bật
function product_flip($p,$full=false){
  $alt=product_alt($p);
  if(!$alt && $full) return product_visual($p);
  if(!$alt){
    $li=''; foreach(spec_chips($p['description']??'',4,34) as $c) $li.='<li>'.e($c).'</li>';
    $alt='<div class="bk"><b>Cấu hình nổi bật</b><ul>'.$li.'</ul><span class="go">Xem chi tiết ›</span></div>';
  }
  return '<div class="flip"><div class="fi"><div class="ff">'.product_visual($p).'</div><div class="fb">'.$alt.'</div></div></div>';
}
function card($p){
  $pc=discount_pct($p); $si=stock_info($p); $oos=($si[0]==='out');
  $new=!empty($p['is_new'])?'<span class="tag new">MỚI</span>':'';
  $old=$pc?'<span class="old">'.vnd($p['old_price']).'</span>':'';
  $off=$pc?'<span class="off">-'.$pc.'%</span>':'';
  $btn=$oos?'<button type="button" class="cardadd" disabled>Hết hàng</button>':'<button type="button" class="cadd cardadd" data-id="'.(int)$p['id'].'" aria-label="Thêm '.e($p['name']).' vào giỏ hàng">'.ico('cartplus').'<span>Thêm vào giỏ hàng</span></button>';
  return '<div class="card'.($oos?' oos':'').'" data-cat="'.(int)$p['category_id'].'"><a class="cardlink" href="product.php?id='.(int)$p['id'].'"><div class="thumb">'.$new.product_flip($p).'</div><div class="cb"><small>'.e($p['brand']).'</small><h3>'.e($p['name']).'</h3><div class="stk '.$si[0].'">'.e($si[1]).'</div><div class="pr"><div><span class="price">'.vnd($p['price']).'</span>'.($old?'<br>'.$old:'').'</div>'.$off.'</div></div></a>'.$btn.'</div>';
}
const PRODUCT_SQL = 'SELECT p.*, c.name AS category, b.name AS brand FROM products p JOIN categories c ON c.id=p.category_id JOIN brands b ON b.id=p.brand_id';

// Ảnh sản phẩm: nếu có images/<id>.jpg|png|webp thì dùng ảnh thật, nếu không thì vẽ hình minh họa theo loại hàng.
function product_visual($p){
  foreach(['jpg','jpeg','png','webp'] as $x){ $f='images/'.(int)$p['id'].'.'.$x;
    if(is_file(__DIR__.'/'.$f)) return '<img src="'.$f.'" alt="'.e($p['name']).'" loading="lazy">'; }
  $n=mb_strtolower($p['name']); $c=(int)$p['category_id'];
  if($c==1)$k='laptop'; elseif($c==2)$k='pc'; elseif($c==3)$k='monitor';
  elseif($c==4)$k=(strpos($n,'ram')!==false)?'ram':'ssd';
  else $k=(strpos($n,'chuột')!==false)?'mouse':((strpos($n,'phím')!==false)?'keyboard':'headset');
  $Y='#98dbaf'; $s='<svg viewBox="0 0 200 140" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="'.e($p['name']).'"><rect width="200" height="140" fill="#fff6f6"/><circle cx="100" cy="70" r="62" fill="#f5bbba" opacity=".55"/>';
  switch($k){
    case 'laptop': $s.='<rect x="42" y="18" width="116" height="76" rx="5" fill="#161616" stroke="#777" stroke-width="2"/><rect x="48" y="24" width="104" height="64" fill="'.$Y.'"/><path d="M48 88L110 24h42v64z" fill="#111" opacity=".5"/><path d="M26 98h148l10 14H16z" fill="#bdbdbd"/><rect x="84" y="98" width="32" height="4" rx="2" fill="#8a8a8a"/>'; break;
    case 'pc': $s.='<rect x="66" y="10" width="68" height="120" rx="6" fill="#1b1b1b" stroke="#777" stroke-width="2"/><rect x="74" y="18" width="52" height="84" rx="3" fill="#000" stroke="'.$Y.'"/><circle cx="100" cy="42" r="16" fill="none" stroke="'.$Y.'" stroke-width="3"/><circle cx="100" cy="42" r="4" fill="'.$Y.'"/><circle cx="100" cy="80" r="16" fill="none" stroke="'.$Y.'" stroke-width="3"/><circle cx="100" cy="80" r="4" fill="'.$Y.'"/><circle cx="100" cy="116" r="5" fill="'.$Y.'"/>'; break;
    case 'monitor': $s.='<rect x="22" y="14" width="156" height="92" rx="6" fill="#111" stroke="#777" stroke-width="2"/><rect x="28" y="20" width="144" height="80" rx="3" fill="'.$Y.'"/><path d="M28 100L120 20h52v80z" fill="#111" opacity=".55"/><path d="M92 106h16l6 18H86z" fill="#555"/><rect x="70" y="122" width="60" height="6" rx="3" fill="#888"/>'; break;
    case 'ssd': $s.='<rect x="40" y="34" width="120" height="72" rx="8" fill="#1d1d1d" stroke="#777"/><rect x="40" y="34" width="22" height="72" rx="8" fill="'.$Y.'"/><text x="76" y="64" fill="#fff" font-family="Arial" font-weight="700" font-size="16">NVMe</text><text x="76" y="84" fill="'.$Y.'" font-family="Arial" font-size="11">PCIe 4.0</text>'; for($i=0;$i<8;$i++)$s.='<rect x="'.(112+$i*5).'" y="106" width="3" height="8" fill="#c9a227"/>'; break;
    case 'ram': $s.='<rect x="22" y="40" width="156" height="56" rx="4" fill="#222" stroke="#777"/><rect x="22" y="40" width="156" height="10" fill="'.$Y.'"/>'; for($i=0;$i<4;$i++)$s.='<rect x="'.(34+$i*35).'" y="58" width="26" height="26" rx="2" fill="#333"/>'; for($i=0;$i<20;$i++)$s.='<rect x="'.(26+$i*7.5).'" y="96" width="4" height="10" fill="#c9a227"/>'; break;
    case 'mouse': $s.='<path d="M100 22c-24 0-38 18-38 44v22c0 20 14 34 38 34s38-14 38-34V66c0-26-14-44-38-44z" fill="#222" stroke="#777" stroke-width="2"/><path d="M100 22v40M62 66h76" stroke="#555"/><rect x="95" y="32" width="10" height="20" rx="5" fill="'.$Y.'"/>'; break;
    case 'keyboard': $s.='<rect x="16" y="46" width="168" height="64" rx="6" fill="#222" stroke="#777" stroke-width="2"/>'; for($r=0;$r<4;$r++)for($q=0;$q<11;$q++)$s.='<rect x="'.(24+$q*14.2).'" y="'.(54+$r*13).'" width="12" height="10" rx="2" fill="'.(($r==3&&$q>1&&$q<8)?$Y:'#3a3a3a').'"/>'; break;
    default: $s.='<path d="M52 84V70a48 48 0 0 1 96 0v14" fill="none" stroke="'.$Y.'" stroke-width="7" stroke-linecap="round"/><rect x="40" y="78" width="26" height="42" rx="12" fill="#222" stroke="#777"/><rect x="134" y="78" width="26" height="42" rx="12" fill="#222" stroke="#777"/><path d="M48 118c0 12 14 14 30 14" fill="none" stroke="#999" stroke-width="3"/>';
  }
  return $s.'</svg>';
}
