<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // ดึงเฉพาะภาษาที่เปิดใช้งาน (lang_status = 1) และเรียงตามลำดับ lang_order
    $stmt = $db->query("SELECT lang_code, lang_name, lang_order FROM languages WHERE lang_status = 1 ORDER BY lang_order ASC");
    $languages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($languages) > 0) {
        // หาภาษา default (ภาษาแรกสุดในลำดับ)
        $default_language = $languages[0]['lang_code'];

        http_response_code(200);
        echo json_encode([
            'status' => true,
            'message' => 'Success',
            'data' => [
                'default_language' => $default_language,
                'items' => $languages
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(404);
        echo json_encode([
            'status' => false,
            'message' => 'No active languages found.',
            'data' => null
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Error fetching languages: ' . $e->getMessage(),
        'data' => null
    ]);
}
?>
