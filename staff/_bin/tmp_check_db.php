<?php
require_once "d:/xampp/htdocs/yakpho.com/staff/db.php";

try {
    // 1. Check if table exists
    $tableExists = $db->query("SHOW TABLES LIKE 'thai_massage_pages'")->rowCount() > 0;
    
    if (!$tableExists) {
        echo "Table thai_massage_pages MISSING! Re-creating it...\n";
        $sql = "CREATE TABLE thai_massage_pages (
            id INT AUTO_INCREMENT PRIMARY KEY,
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
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $db->exec($sql);
        echo "Table created successfully!\n";
    } else {
        echo "Table exists. Checking columns...\n";
        $columns = $db->query("DESCRIBE thai_massage_pages")->fetchAll(PDO::FETCH_COLUMN);
        $required = ['phone', 'email', 'website', 'last_visited_at'];
        foreach ($required as $col) {
            if (!in_array($col, $columns)) {
                echo "Column $col MISSING! Running migration...\n";
                $db->exec("ALTER TABLE thai_massage_pages ADD COLUMN $col VARCHAR(255)");
            }
        }
        echo "Table structure is OK!\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
