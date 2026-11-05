<?php
require_once "d:/xampp/htdocs/yakpho.com/staff/db.php";

$db->exec("INSERT INTO bot_processes (bot_key, script_name) VALUES ('fb_group_poster', 'fb_group_poster.py') ON DUPLICATE KEY UPDATE script_name=VALUES(script_name)");
$db->exec("INSERT INTO ai_staff (name_th, name_en, role, identity_desc, objective, details, image_path, bot_script, status) 
VALUES ('คุณเอสเธอร์', 'Esther (Auto Poster)', 'Group Content Poster', 'รับหน้าที่โพสต์คอนเทนต์ลงกลุ่มที่ผ่านการเข้าร่วมแล้ว', 'กระจายเนื้อหา (Green/Yellow/Red) ตามที่ AI วางแผนไว้ให้ครอบคลุมกลุ่มเป้าหมาย', 'โพสต์สุ่ม 2-6 รูปภาพ และพัก 15-30 นาทีต่อกลุ่ม', 'bot_poster.png', 'fb_group_poster', 'Active')");

echo "Done";
