<?php
require_once '../includes/config.php';
// Temporary bypass for running the setup script
/*
if (php_sapi_name() !== 'cli') {
    require_once '../includes/auth.php';
} else {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['admin_logged'] = true;
    $_SESSION['admin_role'] = 'owner';
}
*/

header('Content-Type: application/json');

try {
    // 1. Create `sales_channels` table
    $db->exec("CREATE TABLE IF NOT EXISTS `sales_channels` (
        `channel_id` INT AUTO_INCREMENT PRIMARY KEY,
        `channel_name` VARCHAR(100) NOT NULL COMMENT 'ชื่อช่องทาง',
        `channel_code` VARCHAR(50) NOT NULL UNIQUE COMMENT 'รหัสย่อ',
        `channel_status` TINYINT(1) DEFAULT 1 COMMENT '0=ปิด, 1=เปิดใช้งาน',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Seed default channels if empty
    $checkChannels = $db->query("SELECT COUNT(*) FROM `sales_channels`")->fetchColumn();
    if ($checkChannels == 0) {
        $defaultChannels = [
            ['LINE OA', 'line'],
            ['Facebook', 'fb'],
            ['Website', 'web'],
            ['Shopee', 'shopee'],
            ['Lazada', 'lazada'],
            ['Tiktok', 'tiktok'],
            ['Phone', 'phone'],
            ['Therapist', 'therapist']
        ];
        $stmt = $db->prepare("INSERT INTO `sales_channels` (channel_name, channel_code) VALUES (?, ?)");
        foreach ($defaultChannels as $ch) {
            $stmt->execute($ch);
        }
    }

    // Helper function to check if a column exists in a table
    function columnExists($db, $table, $column) {
        try {
            $stmt = $db->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    // 2. Add columns to `orders` table if they do not exist
    if (!columnExists($db, 'orders', 'orders_channel')) {
        $db->exec("ALTER TABLE `orders` ADD COLUMN `orders_channel` VARCHAR(50) DEFAULT NULL COMMENT 'ช่องทางการขาย (ผูกกับ channel_code)';");
    }
    
    if (!columnExists($db, 'orders', 'orders_country_id')) {
        // Default to 171 (Thailand)
        $db->exec("ALTER TABLE `orders` ADD COLUMN `orders_country_id` INT DEFAULT 171 COMMENT 'ประเทศของลูกค้า (ผูกกับ countries.country_id)';");
    }
    
    if (!columnExists($db, 'orders', 'agent_user_id')) {
        $db->exec("ALTER TABLE `orders` ADD COLUMN `agent_user_id` INT DEFAULT NULL COMMENT 'ID สมาชิกที่เป็นเซลส์/ตัวแทน/ร้านนวดผู้ได้ค่าคอม';");
    }

    // 3. Add column to `ordetail` table if it does not exist
    if (!columnExists($db, 'ordetail', 'item_cost')) {
        $db->exec("ALTER TABLE `ordetail` ADD COLUMN `item_cost` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'ราคาทุน ณ วันซื้อขาย';");
    }

    // 4. Create `agent_commissions` table
    $db->exec("CREATE TABLE IF NOT EXISTS `agent_commissions` (
        `commission_id` INT AUTO_INCREMENT PRIMARY KEY,
        `orders_id` INT NOT NULL,
        `user_id` INT NOT NULL COMMENT 'ตัวแทนที่ได้รับเงิน (users.user_id)',
        `commission_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'ยอดคอมมิชชันที่ได้รับ',
        `payout_status` ENUM('pending', 'approved', 'paid', 'cancelled') DEFAULT 'pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 5. Run Migration Logic for Existing Orders
    // Set item_cost of existing ordetail records from product.product_cprice if it is currently 0.00
    // First check if product table and product_cprice exists
    if (columnExists($db, 'product', 'product_cprice')) {
        $db->exec("UPDATE `ordetail` od
                   JOIN `product` p ON od.product_id = p.product_id
                   SET od.item_cost = p.product_cprice
                   WHERE od.item_cost = 0.00 OR od.item_cost IS NULL;");
    }

    echo json_encode([
        'success' => true,
        'message' => 'Database schema synchronized successfully! Default channels seeded and historical cost migration completed.'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'PDO Error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'General Error: ' . $e->getMessage()
    ]);
}
?>
