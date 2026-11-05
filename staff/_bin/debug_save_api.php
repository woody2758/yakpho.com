<?php
require_once "db.php";
header('Content-Type: text/html; charset=utf-8');

echo "<h2>🔍 Debug: save_fb_page API Test</h2>";

// 1. Show current table schema
echo "<h3>1. Table Schema (thai_massage_pages)</h3><pre>";
try {
    $rows = $db->query("DESCRIBE thai_massage_pages")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        echo "{$r['Field']} | {$r['Type']} | Null:{$r['Null']} | Key:{$r['Key']} | Default:{$r['Default']}\n";
    }
} catch (Exception $e) { echo "Error: " . $e->getMessage(); }
echo "</pre>";

// 2. Count current rows
echo "<h3>2. Current Row Count</h3>";
try {
    $count = $db->query("SELECT COUNT(*) FROM thai_massage_pages")->fetchColumn();
    echo "<p><strong>Total rows: $count</strong></p>";
} catch (Exception $e) { echo "Error: " . $e->getMessage(); }

// 3. Try a test INSERT via the same API
echo "<h3>3. Test Insert (Direct)</h3>";
try {
    $stmt = $db->prepare("INSERT INTO thai_massage_pages 
        (page_name, page_url, messenger_link, province, followers, phone, email, website, is_active, last_visited_at) 
        VALUES (:name, :url, :messenger, :province, :followers, :phone, :email, :website, :is_active, NOW())
        ON DUPLICATE KEY UPDATE page_name = VALUES(page_name), last_visited_at = NOW()");
    
    $stmt->execute([
        ':name'      => 'Debug Test Page ' . time(),
        ':url'       => 'https://www.facebook.com/debug_test_' . time(),
        ':messenger' => 'https://m.me/debug_test',
        ':province'  => 'กรุงเทพมหานคร',
        ':followers' => '1000',
        ':phone'     => '0812345678',
        ':email'     => null,
        ':website'   => null,
        ':is_active' => 1
    ]);
    $new_count = $db->query("SELECT COUNT(*) FROM thai_massage_pages")->fetchColumn();
    echo "<p>✅ INSERT OK! Row count now: <strong>$new_count</strong></p>";
} catch (Exception $e) {
    echo "<p>❌ INSERT FAILED: <strong>" . $e->getMessage() . "</strong></p>";
    echo "<p>👉 <b>This error is why the bot can't save data!</b></p>";

    // Try to auto-fix missing columns
    echo "<h3>4. Auto-Fix: Adding Missing Columns</h3>";
    $fixes = [
        "ALTER TABLE thai_massage_pages ADD COLUMN IF NOT EXISTS phone VARCHAR(50) NULL",
        "ALTER TABLE thai_massage_pages ADD COLUMN IF NOT EXISTS email VARCHAR(255) NULL",  
        "ALTER TABLE thai_massage_pages ADD COLUMN IF NOT EXISTS website VARCHAR(500) NULL",
        "ALTER TABLE thai_massage_pages ADD COLUMN IF NOT EXISTS last_visited_at DATETIME NULL",
        "ALTER TABLE thai_massage_pages ADD COLUMN IF NOT EXISTS followers VARCHAR(50) NULL",
        "ALTER TABLE thai_massage_pages ADD COLUMN IF NOT EXISTS messenger_link VARCHAR(255) NULL",
    ];
    foreach ($fixes as $sql) {
        try {
            $db->exec($sql);
            echo "<p>✅ $sql</p>";
        } catch (Exception $e2) {
            echo "<p>⚠️ " . $e2->getMessage() . " → $sql</p>";
        }
    }
    echo "<p><a href='debug_save_api.php'><button>🔄 ทดสอบอีกครั้ง</button></a></p>";
}

// 4. Show last 5 rows
echo "<h3>4. Last 5 Rows in DB</h3><pre>";
try {
    $rows = $db->query("SELECT id, page_name, province, last_visited_at FROM thai_massage_pages ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        echo "ID:{$r['id']} | {$r['province']} | {$r['page_name']} | {$r['last_visited_at']}\n";
    }
} catch (Exception $e) { echo "Error: " . $e->getMessage(); }
echo "</pre>";
?>
