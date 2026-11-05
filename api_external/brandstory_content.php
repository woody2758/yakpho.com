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
        'title' => '',
        'subtitle' => '',
        'image_url' => '',
        'html_content' => ''
    ]
];

try {
    // Fetch Brand Story Data
    $stmt = $db->prepare("
        SELECT 
            i.image_file,
            t.intro_title AS title, 
            t.intro_subtitle AS subtitle,
            t.html_content
        FROM yakpho_brandstory_intro i
        LEFT JOIN yakpho_brandstory_intro_translations t ON i.intro_id = t.intro_id AND t.lang_code = :lang
        WHERE i.intro_id = 1
    ");
    $stmt->execute(['lang' => $lang]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        $response['data']['title'] = $data['title'] ?? '';
        $response['data']['subtitle'] = $data['subtitle'] ?? '';
        // If image exists, form full URL or just the file name/path
        if (!empty($data['image_file'])) {
            // Check if it's an absolute URL or just a filename in uploads
            if (strpos($data['image_file'], 'http') === 0) {
                $response['data']['image_url'] = $data['image_file'];
            } else {
                // If it's just the file name, the catalog expects 'assets/img/brand_story.webp' or full URL.
                // Normally it is 'assets/img/brand_story.webp' or from admin upload 'uploads/yakpho/'.
                if ($data['image_file'] === 'brand_story.webp') {
                    $response['data']['image_url'] = 'assets/img/' . $data['image_file'];
                } else {
                    $response['data']['image_url'] = BASE_URL . '/uploads/yakpho/' . $data['image_file'];
                }
            }
        }
        $response['data']['html_content'] = $data['html_content'] ?? '';
    }

    $response['status'] = true;
    $response['message'] = 'Success';

    http_response_code(200);
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Error fetching brand story data: ' . $e->getMessage(),
        'data' => null
    ]);
}
?>
