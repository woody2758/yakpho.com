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
    'status' => false,
    'message' => '',
    'data' => [
        'language' => $lang,
        'intro' => [],
        'tiers' => [],
        'features' => []
    ]
];

try {
    // 1. Fetch Intro
    $stmtIntro = $db->prepare("
        SELECT 
            t.intro_title AS title, 
            t.intro_subtitle AS subtitle
        FROM yakpho_pricing_intro i
        LEFT JOIN yakpho_pricing_intro_translations t ON i.intro_id = t.intro_id AND t.lang_code = :lang
        WHERE i.intro_id = 1
    ");
    $stmtIntro->execute(['lang' => $lang]);
    $introData = $stmtIntro->fetch(PDO::FETCH_ASSOC);

    if (!$introData || empty($introData['title'])) {
        $introData = [
            'title' => '',
            'subtitle' => ''
        ];
    }
    $response['data']['intro'] = $introData;

    // 2. Fetch Tiers
    $sqlTiers = "
        SELECT 
            t.tier_id, 
            tr.tier_volume AS volume,
            tr.tier_price AS price
        FROM yakpho_pricing_tiers t
        LEFT JOIN yakpho_pricing_tier_translations tr ON t.tier_id = tr.tier_id AND tr.lang_code = :lang
        WHERE t.tier_status = 1
        ORDER BY t.tier_order ASC, t.tier_id ASC
    ";
    $stmtTiers = $db->prepare($sqlTiers);
    $stmtTiers->execute(['lang' => $lang]);
    $response['data']['tiers'] = $stmtTiers->fetchAll(PDO::FETCH_ASSOC);

    // 3. Fetch Features
    $sqlFeatures = "
        SELECT 
            f.feature_id, 
            f.feature_icon AS icon,
            tr.feature_text AS text
        FROM yakpho_pricing_features f
        LEFT JOIN yakpho_pricing_feature_translations tr ON f.feature_id = tr.feature_id AND tr.lang_code = :lang
        WHERE f.feature_status = 1
        ORDER BY f.feature_order ASC, f.feature_id ASC
    ";
    $stmtFeatures = $db->prepare($sqlFeatures);
    $stmtFeatures->execute(['lang' => $lang]);
    $response['data']['features'] = $stmtFeatures->fetchAll(PDO::FETCH_ASSOC);

    $response['status'] = true;
    $response['message'] = 'Success';

    http_response_code(200);
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Error fetching pricing data: ' . $e->getMessage(),
        'data' => null
    ]);
}
?>
