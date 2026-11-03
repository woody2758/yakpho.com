<?php
require_once __DIR__ . '/../includes/config.php';

try {
    $db->exec("ALTER TABLE yakpho_contact ADD COLUMN about_image varchar(255) DEFAULT NULL AFTER contact_id;");
    $db->exec("ALTER TABLE yakpho_contact_translations ADD COLUMN about_title varchar(255) DEFAULT NULL;");
    $db->exec("ALTER TABLE yakpho_contact_translations ADD COLUMN about_subtitle varchar(255) DEFAULT NULL;");
    $db->exec("ALTER TABLE yakpho_contact_translations ADD COLUMN about_html text DEFAULT NULL;");

    // Default data for English and Thai
    $defaultAboutTh = '
    <p class="p">
    เราเชื่อว่า "กลิ่นหอม" คือภาษาที่สื่อสารได้กับคนทั่วโลก เราจึงยกระดับภูมิปัญญาสมุนไพรไทยไปอีกขั้น ด้วยการค้นพบความลงตัวใหม่ของ <strong>“Tarragon Infused Oil”</strong> (น้ำมันสกัดทาร์รากอน) สูตรลับเฉพาะของเรา ที่ผสานเข้ากับสมุนไพรไทยแท้ มอบสัมผัสที่แตกต่าง ลึกซึ้ง และทรงพลังยิ่งกว่าเดิม
    </p>
    <p class="p"><strong>ทำไมต้อง YakPho Aroma Balm?</strong></p>
    <ul class="p">
    <li><strong>The Secret Formula:</strong> โดดเด่นด้วย Tarragon Infused Oil เอกลักษณ์ของเอสเธอร์ อโรม่า ผสานสมุนไพรไทยคัดพิเศษ</li>
    <li><strong>Fresh & Crafted:</strong> ผลิตสดใหม่ทุกออเดอร์ (Freshly Made) ตามมาตรฐานความปลอดภัย เพื่อคุณค่าสูงสุด</li>
    <li><strong>Global Trust:</strong> การันตีคุณภาพด้วยยอดจำหน่ายกว่า 10 ประเทศทั่วโลก (ยุโรป, อเมริกา, ออสเตรเลีย, นิวซีแลนด์ และเอเชีย)</li>
    <li><strong>Our Signature:</strong> บาล์มนวดสูตรร้อน, สูตรเย็น และสูตรนวดตัว (Body Massage Balm)</li>
    </ul>
    <blockquote class="p muted" style="margin-top:12px;">
    “Empowering the World with Thai Herbal Wellness<br>
    ส่งต่อพลังบำบัดจากธรรมชาติไทยสู่สากล”
    </blockquote>
    ';
    
    $db->exec("UPDATE yakpho_contact SET about_image = 'tarragon.webp' WHERE contact_id = 1;");
    
    $stmt = $db->prepare("UPDATE yakpho_contact_translations SET about_title = 'YakPho: ศิลปะแห่งการบำบัด... ผสานความลับจาก Tarragon Infused Oil', about_subtitle = 'Esther Aroma Co., Ltd.', about_html = ? WHERE contact_id = 1 AND lang_code = 'th';");
    $stmt->execute([$defaultAboutTh]);

    echo "✅ ตารางถูกอัปเดตเพื่อรองรับ About Us แล้ว!\n";
} catch (PDOException $e) {
    echo "❌ ข้อผิดพลาด (อาจมีคอลัมน์อยู่แล้ว): " . $e->getMessage() . "\n";
}
?>
