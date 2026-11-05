<?php
require_once "../db.php";

header('Content-Type: application/json');

$api_key = $_GET['api_key'] ?? '';
if ($api_key !== 'yakpho2026') {
    die(json_encode(['ok' => false, 'msg' => 'Unauthorized']));
}

try {
    echo "🛠️ Starting Migration...\n";
    
    // สร้างคอลัมน์ที่จำเป็นถ้ายังไม่มี
    $db->exec("ALTER TABLE facebook_groups ADD COLUMN IF NOT EXISTS post_quality VARCHAR(20) NOT NULL DEFAULT 'Good'");
    $db->exec("ALTER TABLE facebook_groups ADD COLUMN IF NOT EXISTS target_zone VARCHAR(50) NOT NULL DEFAULT 'Uncategorized'");
    $db->exec("ALTER TABLE facebook_groups ADD COLUMN IF NOT EXISTS strategy_marker VARCHAR(20) NOT NULL DEFAULT 'Observe'");
    $db->exec("ALTER TABLE facebook_groups ADD COLUMN IF NOT EXISTS ai_reason TEXT");

    echo "✅ Migration Completed Successfully.\n";
    echo json_encode(['ok' => true]);

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
