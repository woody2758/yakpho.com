<?php
require "../includes/config.php"; // เรียกฐานข้อมูล salebnet_thaiherb

$newPass = "yp123456";
$hash = password_hash($newPass, PASSWORD_BCRYPT, ["cost" => 10]);

$sql = "UPDATE user SET user_password = :hash";
$stmt = $db->prepare($sql);
$stmt->execute([':hash' => $hash]);

echo "อัปเดตรหัสผ่านทุกบัญชีเรียบร้อยแล้ว<br>";
echo "รหัสผ่านใหม่ = yp123456<br>";
echo "Hash = " . $hash;
