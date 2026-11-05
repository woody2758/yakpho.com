<?php
require_once "db.php";
require_login(); // Ensure only logged-in staff can run this

$groups = $db->query("SELECT id, campaign_tag FROM facebook_groups WHERE campaign_tag LIKE '%cp_%'")->fetchAll(PDO::FETCH_ASSOC);

$fixed_count = 0;
$log = [];

foreach($groups as $g) {
    $tag = $g['campaign_tag'];
    if (empty($tag)) continue;
    
    $parts = explode(',', $tag);
    $new_parts = [];
    $needs_fix = false;
    
    foreach($parts as $p) {
        $p = trim($p);
        // If it contains cp_ but doesn't start with cp_, it's glued!
        if (strpos($p, 'cp_') !== false && strpos($p, 'cp_') > 0) {
            $idx = strpos($p, 'cp_');
            $original_tag = substr($p, 0, $idx);
            $cp_tag = substr($p, $idx);
            
            if ($original_tag) {
                $new_parts[] = trim($original_tag);
            }
            $new_parts[] = trim($cp_tag);
            $needs_fix = true;
        } else {
            $new_parts[] = $p;
        }
    }
    
    if ($needs_fix) {
        // clean up empty tags just in case
        $new_parts = array_filter(array_map('trim', $new_parts));
        $new_tag_str = implode(',', $new_parts);
        
        $stmt = $db->prepare("UPDATE facebook_groups SET campaign_tag = ? WHERE id = ?");
        $stmt->execute([$new_tag_str, $g['id']]);
        $fixed_count++;
        $log[] = "กลุ่ม ID {$g['id']}: '{$tag}' แก้ไขเป็น '{$new_tag_str}'";
    }
}

echo "<div style='font-family: sans-serif; padding: 20px;'>";
echo "<h2>ทำการแก้ไขแท็กทั้งหมด $fixed_count กลุ่มเรียบร้อยแล้ว</h2>";
if (!empty($log)) {
    echo "<ul>";
    foreach($log as $msg) {
        echo "<li>" . htmlspecialchars($msg) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>ไม่พบแท็กที่ติดกัน (No glued tags found).</p>";
}
echo "<br><p><a href='fb_groups.php' style='padding: 10px 15px; background: #0054a6; color: white; text-decoration: none; border-radius: 5px;'>กลับไปหน้าจัดการกลุ่ม</a></p>";
echo "</div>";
