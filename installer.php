<?php
// ===== Tự động nhập CSDL lần đầu =====
// sql/doan2.sql           -> CSDL DB_NAME            (sản phẩm, đơn hàng, thông báo...)
// sql/doan2_accounts.sql  -> CSDL DB_NAME_accounts   (tài khoản quản trị + khách hàng)
// Chỉ nhập khi CSDL hoặc bảng cốt lõi CHƯA có. Dữ liệu đang có sẽ không bao giờ bị ghi đè.

// Tách một tệp SQL (kiểu phpMyAdmin xuất ra) thành từng câu lệnh, bỏ qua dấu ; nằm trong chuỗi / chú thích
function sql_split($sql){
  $out=[]; $buf=''; $n=strlen($sql); $i=0;
  while($i<$n){ $c=$sql[$i];
    if($c==='#' || ($c==='-' && ($sql[$i+1]??'')==='-' && in_array($sql[$i+2]??"\n",[' ',"\t","\r","\n"],true))){   // chú thích một dòng
      $e=strpos($sql,"\n",$i); if($e===false) break; $i=$e; continue; }
    if($c==='/' && ($sql[$i+1]??'')==='*'){                                                                        // chú thích khối
      $e=strpos($sql,'*/',$i+2); if($e===false) break;
      if(($sql[$i+2]??'')==='!') $buf.=substr($sql,$i,$e+2-$i);                                                     // /*! ... */ là lệnh có điều kiện, giữ lại cho MySQL thực thi
      $i=$e+2; continue; }
    if($c==="'" || $c==='"' || $c==='`'){                                                                          // chuỗi hoặc tên có dấu `
      $q=$c; $buf.=$c; $i++;
      while($i<$n){ $d=$sql[$i]; $buf.=$d;
        if($d==='\\' && $q!=='`'){ $i++; if($i<$n) $buf.=$sql[$i]; $i++; continue; }
        if($d===$q){ if(($sql[$i+1]??'')===$q){ $buf.=$q; $i+=2; continue; } $i++; break; }
        $i++; }
      continue; }
    if($c===';'){ $t=trim($buf); if($t!=='') $out[]=$t; $buf=''; $i++; continue; }
    $buf.=$c; $i++; }
  $t=trim($buf); if($t!=='') $out[]=$t; return $out; }
function sql_import($pdo,$file){
  $pdo->exec('SET NAMES utf8mb4'); $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
  foreach(sql_split((string)file_get_contents($file)) as $st){
    if(preg_match('/^(START\s+TRANSACTION|COMMIT)$/i',$st)) continue;
    $pdo->exec($st); }
  $pdo->exec('SET FOREIGN_KEY_CHECKS=1'); }
function installer_ensure(){
  if(($_SESSION['inst_ok']??0)===1) return;
  $jobs=[[DB_NAME,__DIR__.'/sql/doan2.sql','products'],[ACC_DB,__DIR__.'/sql/doan2_accounts.sql','users']];
  try{ $root=new PDO('mysql:host='.DB_HOST.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); }
  catch(PDOException $x){ exit('Không kết nối được MySQL. Hãy bật MySQL trong XAMPP và kiểm tra DB_USER / DB_PASS trong config.local.php. Chi tiết: '.htmlspecialchars($x->getMessage())); }
  foreach($jobs as $j){ $db=$j[0]; $file=$j[1]; $core=$j[2];
    $exists=(bool)$root->query('SHOW DATABASES LIKE '.$root->quote($db))->fetch();
    if($exists && $root->query('SHOW TABLES FROM `'.$db.'` LIKE '.$root->quote($core))->fetch()) continue;   // đã có dữ liệu: không đụng tới
    if(!is_file($file)) continue;                                                                             // không có tệp SQL: các bước sau tự tạo bảng như trước
    try{
      if(!$exists) $root->exec('CREATE DATABASE `'.$db.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
      $root->exec('USE `'.$db.'`'); sql_import($root,$file);
    }catch(Exception $x){
      if(!$exists){ try{ $root->exec('DROP DATABASE `'.$db.'`'); }catch(Exception $y){} }                     // để lần mở sau thử lại từ đầu
      exit('Không nhập được '.basename($file).' vào CSDL '.htmlspecialchars($db).'. Bạn có thể nhập thủ công bằng phpMyAdmin (tab Import). Chi tiết: '.htmlspecialchars($x->getMessage())); } }
  $_SESSION['inst_ok']=1; }
