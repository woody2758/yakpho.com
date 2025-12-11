<?php
require_once 'admin/includes/config.php';

// Bypass auth for testing
// require_once 'admin/includes/auth.php'; 

header('Content-Type: application/json');

try {
    $page = 1;
    $limit = 15;
    $offset = 0;
    $search = '';
    $status_id = 0;

    echo "Testing Query...\n";

    // Base query
    $sql = "SELECT o.*, 
            u.user_name, u.user_lastname, 
            s.orsts_detail, s.orsts_color, s.orsts_code,
            (SELECT SUM(subtotal) FROM ordetail WHERE orders_id = o.orders_id) as total_amount
            FROM orders o
            LEFT JOIN user u ON o.user_id = u.user_id
            LEFT JOIN orsts s ON o.orsts_id = s.orsts_id
            WHERE 1=1";
    
    echo "SQL: $sql\n";

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Query Successful. Found " . count($orders) . " orders.\n";
    print_r($orders);

} catch (PDOException $e) {
    echo "SQL Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
