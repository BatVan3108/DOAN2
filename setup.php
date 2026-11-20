<?php // Chạy MỘT LẦN: http://localhost/doan2/setup.php để tạo tài khoản quản trị, rồi XÓA file này.
require_once 'config.php';
if($pdo->query('SELECT COUNT(*) FROM '.T_USERS.'')->fetchColumn()>0) exit('Đã có tài khoản quản trị. Hãy xóa file setup.php.');
$pass=bin2hex(random_bytes(4));
$pdo->prepare('INSERT INTO '.T_USERS.'(username,password_hash) VALUES(?,?)')->execute(['admin',password_hash($pass,PASSWORD_DEFAULT)]);
echo 'Đã tạo tài khoản: <b>admin</b> / <b>'.$pass.'</b> (hãy ghi lại, rồi xóa file setup.php).';
