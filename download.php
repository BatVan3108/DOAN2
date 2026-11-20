<?php require_once 'config.php';
$cur=$pdo->query('SELECT * FROM pricelists ORDER BY id DESC LIMIT 1')->fetch();
if($cur && is_file(__DIR__.'/uploads/'.$cur['stored_name'])){
  header('Content-Type: application/octet-stream'); header('Content-Length: '.filesize(__DIR__.'/uploads/'.$cur['stored_name']));
  header('Content-Disposition: attachment; filename="'.str_replace('"','',$cur['original_name']).'"'); readfile(__DIR__.'/uploads/'.$cur['stored_name']); exit; }
header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="bang-gia-doan2.csv"');
$o=fopen('php://output','w'); fwrite($o,"\xEF\xBB\xBF"); fputcsv($o,['Mã','Tên sản phẩm','Loại','Hãng','Giá (VND)']);
foreach($pdo->query(PRODUCT_SQL.' ORDER BY p.id') as $r) fputcsv($o,[$r['id'],$r['name'],$r['category'],$r['brand'],$r['price']]);
