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
    'data' => null
];

try {
    // Fetch Contact Data
    $stmt = $db->prepare("
        SELECT 
            c.phone,
            c.line_id,
            c.facebook,
            c.email,
            c.whatsapp,
            c.map_embed,
            c.about_image,
            t.title, 
            t.company_name,
            t.address,
            t.about_title,
            t.about_subtitle,
            t.about_html
        FROM yakpho_contact c
        LEFT JOIN yakpho_contact_translations t ON c.contact_id = t.contact_id AND t.lang_code = :lang
        WHERE c.contact_id = 1
    ");
    $stmt->execute(['lang' => $lang]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        $response['data'] = $data;
        $response['data']['language'] = $lang;
        
        // Handle about_image URL
        if (!empty($data['about_image'])) {
            if (strpos($data['about_image'], 'http') === 0) {
                $response['data']['about_image_url'] = $data['about_image'];
            } else {
                if ($data['about_image'] === 'tarragon.webp') {
                    $response['data']['about_image_url'] = 'assets/img/' . $data['about_image'];
                } else {
                    $response['data']['about_image_url'] = BASE_URL . '/uploads/yakpho/' . $data['about_image'];
                }
            }
        } else {
            $response['data']['about_image_url'] = '';
        }
    }

    $response['status'] = true;
    $response['message'] = 'Success';

    http_response_code(200);
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Error fetching contact data: ' . $e->getMessage(),
        'data' => null
    ]);
}
?>
