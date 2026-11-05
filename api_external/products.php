<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// รับพารามิเตอร์ภาษา ถ้าไม่มีให้ใช้ภาษา 'th' เป็นค่าเริ่มต้น
$lang = $_GET['lang'] ?? 'th';

try {
    // ดึงสินค้าเฉพาะกลุ่มยักษ์โพธิ์ (maincat_id = 1) และต้องเปิดใช้งาน (product_status = 1) และยังไม่ถูกลบ (product_del = 0)
    // มีการ Join ข้อมูลภาษา (translations) ให้ตรงกับภาษาที่ส่งมา
    $sql = "
        SELECT 
            p.product_id, 
            p.product_code, 
            p.product_price, 
            p.product_cprice, 
            p.product_stock, 
            p.product_picture,
            (SELECT GROUP_CONCAT(image_file SEPARATOR ',') FROM product_images WHERE product_id = p.product_id) AS gallery_files,
            p.scent_id,
            p.formula_id,
            pc.productcat_id,
            pt.product_name, 
            pt.product_excerpt, 
            pt.product_description,
            pct.productcat_name,
            scent_t.attribute_name AS scent_name,
            formula_t.attribute_name AS formula_name
        FROM product p
        INNER JOIN productcat pc ON p.productcat_id = pc.productcat_id
        LEFT JOIN product_translations pt ON p.product_id = pt.product_id AND pt.lang_code = :lang
        LEFT JOIN productcat_translations pct ON pc.productcat_id = pct.productcat_id AND pct.lang_code = :lang
        LEFT JOIN product_attribute_translations scent_t ON p.scent_id = scent_t.attribute_id AND scent_t.lang_code = :lang
        LEFT JOIN product_attribute_translations formula_t ON p.formula_id = formula_t.attribute_id AND formula_t.lang_code = :lang
        WHERE pc.maincat_id = 1 
          AND p.product_status = 1 
          AND p.product_del = 0
        ORDER BY p.product_index ASC, p.product_id ASC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute(['lang' => $lang]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // จัดระเบียบข้อมูลก่อนส่งออก (เช่น ใส่ URL รูปภาพให้ครบถ้วน)
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $base_url = $protocol . "://" . $host . "/esther.rest"; // เมื่อขึ้น Server จริง อาจจะต้องปรับค่าตรงนี้ตามโครงสร้าง Server
    
    foreach ($products as &$product) {
        // เพิ่ม Full URL สำหรับรูปภาพหลัก
        if (!empty($product['product_picture'])) {
            $product['image_url'] = $base_url . "/uploads/products/" . $product['product_picture'];
            $product['image_thumb'] = $base_url . "/uploads/products/small-" . $product['product_picture'];
        } else {
            $product['image_url'] = null;
            $product['image_thumb'] = null;
        }
        
        // แยก Gallery 
        if (!empty($product['gallery_files'])) {
            $gallery = explode(',', $product['gallery_files']);
            $gallery_urls = [];
            foreach ($gallery as $img) {
                if (!empty($img)) {
                    $gallery_urls[] = $base_url . "/uploads/products/gallery/" . $img;
                }
            }
            $product['gallery_urls'] = $gallery_urls;
        } else {
            $product['gallery_urls'] = [];
        }
        
        // Remove the raw string to keep response clean
        unset($product['gallery_files']);
    }

    http_response_code(200);
    echo json_encode([
        'status' => true,
        'message' => 'Success',
        'data' => [
            'count' => count($products),
            'language' => $lang,
            'items' => $products
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Error fetching products: ' . $e->getMessage(),
        'data' => null
    ]);
}
?>
