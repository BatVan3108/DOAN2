<?php require_once 'config.php'; header('Content-Type: application/json; charset=utf-8');
function jout($a,$code=200){ http_response_code($code); echo json_encode($a,JSON_UNESCAPED_UNICODE); exit; }
if($_SERVER['REQUEST_METHOD']!=='POST') jout(['ok'=>false],405);
if(!hash_equals($_SESSION['t']??'',(string)($_POST['t']??''))) jout(['ok'=>false,'msg'=>'Phiên làm việc không hợp lệ, hãy tải lại trang.'],400);
$act=$_POST['act']??''; $id=(int)($_POST['id']??0); $qty=(int)($_POST['qty']??1); $cart=cart_all(); $msg='';
if($act==='add'||$act==='set'){
  $q=$pdo->prepare('SELECT stock FROM products WHERE id=?'); $q->execute([$id]); $stock=$q->fetchColumn();
  if($stock===false) jout(['ok'=>false,'msg'=>'Sản phẩm không tồn tại.'],404);
  $stock=(int)$stock;
  if($stock<=0 && $act==='add') jout(['ok'=>false,'msg'=>'Sản phẩm này đã hết hàng.'],409);
  $n=($act==='add')?(($cart[$id]??0)+max(1,$qty)):$qty;
  if($n>$stock){ $n=$stock; $msg='Chỉ còn '.$stock.' sản phẩm trong kho.'; }
  if($n<1) unset($cart[$id]); else $cart[$id]=min(50,$n); }
elseif($act==='del') unset($cart[$id]);
elseif($act==='clear') $cart=[];
elseif($act==='sel'){ $ids=array_filter(array_map('intval',explode(',',(string)($_POST['ids']??'')))); $_SESSION['cart']=$cart; $ok=[];
  foreach(cart_lines($pdo,$ids) as $l) if(!$l['oos']) $ok[]=(int)$l['id']; $_SESSION['co_sel']=$ok; unset($_SESSION['co_info']); }
$_SESSION['cart']=$cart; $r=cart_payload($pdo); if($msg!=='') $r['msg']=$msg; jout($r);
