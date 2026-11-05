<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../admin/includes/config.php';

$response = [
    'status' => false,
    'message' => '',
    'data' => []
];

try {
    $lang = isset($_GET['lang']) ? trim($_GET['lang']) : 'th';

    // Fetch Categories
    $stmtCat = $db->prepare("
        SELECT c.category_id, c.sort_order, 
               COALESCE(t.category_name, c.category_name) as category_name
        FROM yakpho_gallery_categories c
        LEFT JOIN yakpho_gallery_category_translations t ON c.category_id = t.category_id AND t.lang_code = ?
        ORDER BY c.sort_order ASC, c.category_id ASC
    ");
    $stmtCat->execute([$lang]);
    $categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Images
    $stmtImg = $db->query("SELECT * FROM yakpho_gallery_images ORDER BY sort_order ASC, image_id DESC");
    $allImages = $stmtImg->fetchAll(PDO::FETCH_ASSOC);

    // Group images by category
    $imagesByCategory = [];
    foreach ($allImages as $img) {
        $catId = $img['category_id'];
        if (!isset($imagesByCategory[$catId])) {
            $imagesByCategory[$catId] = [];
        }
        $img['image_url'] = BASE_URL . '/uploads/yakpho/' . $img['image_file'];
        $imagesByCategory[$catId][] = $img;
    }

    $data = [];
    foreach ($categories as $cat) {
        $cat['images'] = $imagesByCategory[$cat['category_id']] ?? [];
        $data[] = $cat;
    }

    $response['status'] = true;
    $response['message'] = 'Success';
    $response['data'] = $data;

    http_response_code(200);
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Error fetching gallery data: ' . $e->getMessage(),
        'data' => []
    ]);
}
?>
