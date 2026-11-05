<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../admin/includes/config.php';

$lang = isset($_GET['lang']) ? trim($_GET['lang']) : 'th';
if (empty($lang)) {
    $lang = 'th';
}

$response = [
    'success' => false,
    'lang' => $lang,
    'data' => [
        'intro' => [],
        'items' => []
    ]
];

try {
    // 1. Fetch Intro (fallback to TH if not found)
    $stmtIntro = $db->prepare("
        SELECT 
            t.intro_title AS title, 
            t.intro_subtitle AS subtitle, 
            t.intro_description AS description
        FROM yakpho_scent_intro i
        LEFT JOIN yakpho_scent_intro_translations t ON i.intro_id = t.intro_id AND t.lang_code = :lang
        WHERE i.intro_id = 1
    ");
    $stmtIntro->execute(['lang' => $lang]);
    $introData = $stmtIntro->fetch(PDO::FETCH_ASSOC);

    if (!$introData || empty($introData['title'])) {
        $introData = [
            'title' => '',
            'subtitle' => '',
            'description' => ''
        ];
    }

    // 2. ดึงข้อมูลรายการกลิ่นยอดนิยม (Scent Items)
    $sql = "
        SELECT 
            i.item_id, 
            i.image_file, 
            t.title,
            t.subtitle
        FROM yakpho_scent_items i
        LEFT JOIN yakpho_scent_translations t ON i.item_id = t.item_id AND t.lang_code = :lang
        WHERE i.item_status = 1
        ORDER BY i.item_order ASC, i.item_id ASC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute(['lang' => $lang]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // สร้าง URL รูปภาพเต็มๆ
    foreach ($items as &$item) {
        if (!empty($item['image_file'])) {
            $item['image_url'] = BASE_URL . "/uploads/yakpho/" . $item['image_file'];
        } else {
            $item['image_url'] = null;
        }
        unset($item['image_file']); // ซ่อนชื่อไฟล์ดิบเพื่อความปลอดภัย
    }

    http_response_code(200);
    echo json_encode([
        'status' => true,
        'message' => 'Success',
        'data' => [
            'language' => $lang,
            'intro' => $introData,
            'items' => $items
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Error fetching scent data: ' . $e->getMessage(),
        'data' => null
    ]);
}
?>
