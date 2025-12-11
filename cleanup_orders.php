<?php
require_once 'admin/includes/config.php';

try {
    echo "Starting Order Cleanup...\n";
    echo "--------------------------------\n";

    // 1. Count existing data
    $stmt = $db->query("SELECT COUNT(*) FROM product");
    $productCount = $stmt->fetchColumn();
    echo "Total Products: $productCount\n";

    $stmt = $db->query("SELECT COUNT(*) FROM ordetail");
    $ordetailCount = $stmt->fetchColumn();
    echo "Total Order Details: $ordetailCount\n";

    $stmt = $db->query("SELECT COUNT(*) FROM orders");
    $ordersCount = $stmt->fetchColumn();
    echo "Total Orders: $ordersCount\n";

    echo "--------------------------------\n";

    // 2. Delete invalid order details
    // Delete ordetail where product_id is not in product table
    $sql = "DELETE FROM ordetail WHERE product_id NOT IN (SELECT product_id FROM product)";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $deletedDetails = $stmt->rowCount();
    echo "Deleted $deletedDetails invalid order details (products not found).\n";

    // 3. Delete empty orders
    // Delete orders that have no corresponding ordetail records
    $sql = "DELETE FROM orders WHERE orders_id NOT IN (SELECT DISTINCT orders_id FROM ordetail)";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $deletedOrders = $stmt->rowCount();
    echo "Deleted $deletedOrders empty orders (no items left).\n";

    echo "--------------------------------\n";
    echo "Cleanup Complete.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
