<?php
require_once "db.php";

echo "<h2>Leads Database Diagnosis</h2>";

try {
    // 1. Check if table exists
    $result = $db->query("SHOW TABLES LIKE 'thai_massage_pages'");
    if ($result->rowCount() == 0) {
        echo "<b style='color:red;'>ERROR:</b> Table 'thai_massage_pages' NOT FOUND.<br>";
        
        // Let's create it with full enriched schema
        $sql = "CREATE TABLE IF NOT EXISTS thai_massage_pages (
            page_id INT AUTO_INCREMENT PRIMARY KEY,
            page_name VARCHAR(255),
            page_url VARCHAR(255) UNIQUE,
            messenger_link VARCHAR(255),
            province VARCHAR(100),
            followers VARCHAR(50),
            phone VARCHAR(50),
            email VARCHAR(100),
            website VARCHAR(255),
            is_active TINYINT DEFAULT 1,
            last_visited_at DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $db->exec($sql);
        echo "✓ Created table 'thai_massage_pages' with full schema.<br>";
    } else {
        echo "✓ Table 'thai_massage_pages' exists.<br>";
        
        // 2. Check columns
        $columns = $db->query("DESCRIBE thai_massage_pages")->fetchAll(PDO::FETCH_COLUMN);
        $required = ['phone', 'email', 'website', 'last_visited_at'];
        
        foreach ($required as $col) {
            if (!in_array($col, $columns)) {
                echo "X Column '$col' is MISSING. Fixing...<br>";
                if ($col == 'last_visited_at') {
                    $db->exec("ALTER TABLE thai_massage_pages ADD COLUMN last_visited_at DATETIME AFTER is_active");
                } else {
                    $db->exec("ALTER TABLE thai_massage_pages ADD COLUMN $col VARCHAR(255) AFTER followers");
                }
                echo "✓ Added column '$col'.<br>";
            } else {
                echo "✓ Column '$col' is present.<br>";
            }
        }
    }

    // 3. Check current count
    $count = $db->query("SELECT COUNT(*) FROM thai_massage_pages")->fetchColumn();
    echo "<br><b>Current record count:</b> " . $count . "<br>";

    echo "<br><b style='color:green;'>DIAGNOSIS COMPLETE:</b> The database is now fully compatible with Dr. Maya V3. Please try running the bot again.";

} catch (Exception $e) {
    echo "<b style='color:red;'>ERROR:</b> " . $e->getMessage();
}
?>
