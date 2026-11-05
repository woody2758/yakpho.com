<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// รับพารามิเตอร์ภาษา ถ้าไม่มีให้ใช้ภาษา 'th' เป็นค่าเริ่มต้น
$lang = $_GET['lang'] ?? 'th';

try {
    // ดึงแบนเนอร์เฉพาะรายการที่เปิดใช้งาน (slide_status = 1) และเรียงตามลำดับ (slide_order)
    // Join ข้อมูลภาษาให้ตรงกับภาษาที่ส่งมา
    $sql = "
        SELECT 
            s.slide_id, 
            s.slide_image, 
            s.btn1_url, 
            s.btn2_url,
            t.slide_title,
            t.slide_subtitle,
            t.slide_badges,
            t.btn1_text,
            t.btn2_text
        FROM yakpho_hero_slides s
        LEFT JOIN yakpho_hero_translations t ON s.slide_id = t.slide_id AND t.lang_code = :lang
        WHERE s.slide_status = 1
        ORDER BY s.slide_order ASC, s.slide_id ASC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute(['lang' => $lang]);
    $slides = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // จัดระเบียบข้อมูลก่อนส่งออก 
    foreach ($slides as &$slide) {
        // เพิ่ม Full URL สำหรับรูปภาพ
        if (!empty($slide['slide_image'])) {
            $slide['image_url'] = BASE_URL . "/uploads/yakpho/" . $slide['slide_image'];
        } else {
            $slide['image_url'] = null;
        }
        
        // แปลง Badges จาก string (คั่นด้วย comma) เป็น array ถ้ามีข้อมูล
        if (!empty($slide['slide_badges'])) {
            // ตัดช่องว่างหน้า/หลัง ของแต่ละคำออก
            $badges = array_map('trim', explode(',', $slide['slide_badges']));
            // กรองคำว่างออก
            $slide['badges_array'] = array_values(array_filter($badges));
        } else {
            $slide['badges_array'] = [];
        }
    }

    http_response_code(200);
    echo json_encode([
        'status' => true,
        'message' => 'Success',
        'data' => [
            'count' => count($slides),
            'language' => $lang,
            'items' => $slides
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Error fetching hero banners: ' . $e->getMessage(),
        'data' => null
    ]);
}
?>
