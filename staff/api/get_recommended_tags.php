<?php
require_once "../db.php";
header('Content-Type: application/json');

$tags = [];

try {
    // ดึง tag ทั้งหมดเรียงตาม id ล่าสุด เพื่อให้ tag ที่เพิ่งถูกใช้งานขึ้นมาก่อน
    $res = $db->query("SELECT campaign_tag FROM facebook_groups WHERE campaign_tag IS NOT NULL AND campaign_tag != '' ORDER BY id DESC")->fetchAll(PDO::FETCH_COLUMN);
    foreach($res as $row) {
        $parts = explode(',', $row);
        foreach($parts as $p) {
            $t = trim($p);
            if($t && !in_array($t, $tags)) {
                $tags[] = $t;
            }
        }
    }
} catch(Exception $e) {}

echo json_encode(['ok'=>true, 'tags'=>$tags]);
?>
