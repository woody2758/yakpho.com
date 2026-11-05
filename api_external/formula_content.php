<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$lang = $_GET['lang'] ?? 'th';

try {
    // 1. ดึงข้อมูลส่วน Intro (ID=1)
    $stmtIntro = $db->prepare("
        SELECT 
            t.intro_title,
            t.intro_subtitle,
            t.intro_description
        FROM yakpho_formula_intro i
        LEFT JOIN yakpho_formula_intro_translations t ON i.intro_id = t.intro_id AND t.lang_code = :lang
        WHERE i.intro_id = 1
    ");
    $stmtIntro->execute(['lang' => $lang]);
    $introData = $stmtIntro->fetch(PDO::FETCH_ASSOC);

    if (!$introData) {
        $introData = [
            'intro_title' => '',
            'intro_subtitle' => '',
            'intro_description' => ''
        ];
    }

    // 2. ดึงข้อมูลสูตรนวด (Formula Items)
    $sql = "
        SELECT 
            i.item_id, 
            i.image_file, 
            i.css_class, 
            t.title AS slide_title,
            t.subtitle AS slide_subtitle,
            t.bullets
        FROM yakpho_formula_items i
        LEFT JOIN yakpho_formula_translations t ON i.item_id = t.item_id AND t.lang_code = :lang
        WHERE i.item_status = 1
        ORDER BY i.item_order ASC, i.item_id ASC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute(['lang' => $lang]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as &$item) {
        if (!empty($item['image_file'])) {
            $item['image_url'] = BASE_URL . "/uploads/yakpho/" . $item['image_file'];
        } else {
            $item['image_url'] = null;
        }
        
        if (!empty($item['bullets'])) {
            $badges = array_map('trim', explode(',', $item['bullets']));
            $item['badges_array'] = array_values(array_filter($badges));
        } else {
            $item['badges_array'] = [];
        }
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
        'message' => 'Error fetching formula data: ' . $e->getMessage(),
        'data' => null
    ]);
}
?>
