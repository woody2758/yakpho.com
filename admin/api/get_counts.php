<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

try {
    // Helper function to get counts
    function getCount($db, $sql) {
        $stmt = $db->query($sql);
        $count = $stmt ? $stmt->fetchColumn() : 0;
        return number_format($count);
    }

    $counts = [
        'maincat'    => getCount($db, "SELECT COUNT(*) FROM maincat WHERE maincat_del = 0"),
        'productcat' => getCount($db, "SELECT COUNT(*) FROM productcat WHERE productcat_del = 0"),
        'product'    => getCount($db, "SELECT COUNT(*) FROM product WHERE product_del = 0"),
        'orders'     => getCount($db, "SELECT COUNT(*) FROM orders WHERE orders_del = 0"),
        'user'       => getCount($db, "SELECT COUNT(*) FROM user WHERE user_del = 0"),
        'hero'       => getCount($db, "SELECT COUNT(*) FROM hero_slides WHERE hero_slides_del = 0"),
        'blogcat'    => getCount($db, "SELECT COUNT(*) FROM blogcat WHERE blogcat_del = 0"),
        'blog'       => getCount($db, "SELECT COUNT(*) FROM blog WHERE blog_del = 0"),
        'faqscat'    => getCount($db, "SELECT COUNT(*) FROM faqscat WHERE faqscat_del = 0"),
        'faqs'       => getCount($db, "SELECT COUNT(*) FROM faqs WHERE faqs_del = 0"),
        'menu'       => getCount($db, "SELECT COUNT(*) FROM menu WHERE menu_del = 0")
    ];

    echo json_encode(['success' => true, 'counts' => $counts]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
