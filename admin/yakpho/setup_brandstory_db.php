<?php
require_once __DIR__ . '/../includes/config.php';

try {
    // 1. ตาราง yakpho_brandstory_intro (เก็บข้อมูลส่วนเกริ่นนำและรูปภาพหลัก)
    $sql1 = "CREATE TABLE IF NOT EXISTS `yakpho_brandstory_intro` (
        `intro_id` int(11) NOT NULL AUTO_INCREMENT,
        `image_file` varchar(255) DEFAULT NULL,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`intro_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $db->exec($sql1);

    // 2. ตาราง yakpho_brandstory_intro_translations (แปลภาษาให้ Intro)
    $sql2 = "CREATE TABLE IF NOT EXISTS `yakpho_brandstory_intro_translations` (
        `translation_id` int(11) NOT NULL AUTO_INCREMENT,
        `intro_id` int(11) NOT NULL,
        `lang_code` varchar(10) NOT NULL,
        `intro_title` varchar(255) DEFAULT NULL,
        `intro_subtitle` varchar(255) DEFAULT NULL,
        `html_content` text,
        PRIMARY KEY (`translation_id`),
        UNIQUE KEY `idx_intro_lang` (`intro_id`, `lang_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $db->exec($sql2);

    // แทรกข้อมูลเริ่มต้นสำหรับ Intro ถ้ายังไม่มี
    $checkIntro = $db->query("SELECT COUNT(*) FROM yakpho_brandstory_intro WHERE intro_id = 1")->fetchColumn();
    if ($checkIntro == 0) {
        // Insert base row
        $db->exec("INSERT INTO yakpho_brandstory_intro (intro_id, image_file) VALUES (1, 'brand_story.webp')");
        
        // Default HTML content (from index.php)
        $defaultHtml = '
        <p class="p">
          YakPho Aroma เชื่อว่าการนวดไทยไม่ใช่เพียงการผ่อนคลายกล้ามเนื้อ  
          แต่คือ <strong>ศิลปะแห่งการบำบัดใจและร่างกาย</strong> ที่เชื่อมโยงคนกับธรรมชาติอย่างลึกซึ้ง  
          สัมผัสของมือช่างนวดคือพลังแห่งความตั้งใจ — และกลิ่นสมุนไพรคือถ้อยคำที่บอกว่า  
          <em>“คุณปลอดภัยแล้ว... จงพักผ่อนและหายใจได้อย่างเป็นอิสระอีกครั้ง”</em>
        </p>

        <p class="p">
          เราจึงตั้งใจสร้างบาล์มสมุนไพรที่ไม่ใช่เพียง <strong>ผลิตภัณฑ์</strong>  
          แต่เป็น <strong>เครื่องมือแห่งการเยียวยา</strong> ให้ร้านนวดและสปาทั่วโลก  
          ได้ส่งต่อความรู้สึกแบบเดียวกับที่คนไทยรู้จักมานับร้อยปี —  
          ความอบอุ่นของไพล กลิ่นเย็นสดชื่นของเมนทอล และสัมผัสนุ่มมือที่เต็มไปด้วยความใส่ใจ
        </p>

        <h3 class="h3">ทำไมเราทำเพื่อต่างประเทศ</h3>
        <p class="p">
          ร้านนวดไทยในต่างแดนคือ <strong>ทูตแห่งวัฒนธรรม</strong> ที่ใช้หัวใจในการสื่อสาร  
          แต่พวกเขาขาดสิ่งหนึ่ง — <em>บาล์มที่เข้าใจหัวใจของช่างนวดไทยจริง ๆ</em><br>
          YakPho Aroma จึงถือกำเนิดขึ้น เพื่อสร้างบาล์มที่มี <strong>กลิ่นแบบสปาไทยแท้</strong>,  
          <strong>สัมผัสลื่นมือไม่เหนียว</strong>, และ <strong>ปลอดภัยต่อมือช่างนวด</strong>  
          ทุกสูตรถูกคิดขึ้นจากประสบการณ์จริงของช่างนวดมืออาชีพ
        </p>

        <h3 class="h3">เพื่อให้ลูกค้าของร้านนวด ได้รับมากกว่าความผ่อนคลาย</h3>
        <p class="p">
          บาล์มของ YakPho Aroma ถูกออกแบบให้กลิ่นค่อย ๆ คลี่ตัว  
          จากสมุนไพรไทยพื้นบ้านสู่โทนอโรม่าสากล  
          เพื่อให้ลูกค้าในยุโรป อเมริกา หรือออสเตรเลีย  
          ได้หลับตาแล้วรู้สึกเหมือนอยู่ในสปาใจกลางเชียงใหม่หรือริมทะเลภูเก็ต  
          ทุกการนวดจึงไม่ใช่แค่การบริการ แต่คือ <strong>การเดินทางของความรู้สึก</strong>  
          จากมือช่างไทย... สู่หัวใจของคนทั่วโลก
        </p>

        <blockquote class="p muted" style="margin-top:10px;">
          “เราสร้างบาล์มที่ไม่ใช่แค่หอม...  
          แต่เป็นกลิ่นของความตั้งใจ ความอบอุ่น และความเป็นไทยที่สัมผัสได้”
        </blockquote>
        ';

        $stmtTrans = $db->prepare("INSERT INTO yakpho_brandstory_intro_translations (intro_id, lang_code, intro_title, intro_subtitle, html_content) VALUES (?, ?, ?, ?, ?)");
        $stmtTrans->execute([1, 'th', 'Brand Story', 'Touch of Thai Wellness', $defaultHtml]);
        $stmtTrans->execute([1, 'en', 'Brand Story', 'Touch of Thai Wellness', '']);
    }

    echo "✅ ตารางฐานข้อมูลสำหรับส่วน 'Brand Story' ถูกสร้างเรียบร้อยแล้ว!\n";

} catch (PDOException $e) {
    echo "❌ มีข้อผิดพลาดในการสร้างฐานข้อมูล: " . $e->getMessage() . "\n";
}
?>
