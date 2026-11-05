<?php
require_once "db.php";

echo "Starting migration for ai_staff table...<br>";

// 1. Create the table
$createTableSql = "
CREATE TABLE IF NOT EXISTS ai_staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bot_script VARCHAR(100) NOT NULL,
    name_en VARCHAR(100) NOT NULL,
    name_th VARCHAR(100) NOT NULL,
    department VARCHAR(100) NOT NULL DEFAULT 'Social Automation',
    role VARCHAR(100) NOT NULL,
    identity_desc VARCHAR(255) NOT NULL,
    objective TEXT NOT NULL,
    details TEXT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    start_date DATE NOT NULL,
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

try {
    $db->exec($createTableSql);
    echo "Table 'ai_staff' ensured.<br>";
} catch (PDOException $e) {
    die("Error creating table: " . $e->getMessage());
}

// 2. Clear existing data to prevent duplicates during testing
$db->exec("TRUNCATE TABLE ai_staff");

// 3. Insert data
$bot_staff = [
    'yakpho_real_work' => [
        'name' => 'Jonathan H. Mills', 
        'thai' => 'คุณจอน (Jon)', 
        'img' => 'bot_lead_gen.png', 
        'role' => 'Review Specialist & Map Manager',
        'identity' => 'Google Account: yakpho.com@gmail.com / saravuth.k@gmail.com',
        'objective' => 'ดูแลการรีวิวแผนที่ (Google Map) และงานระบบ Google ทั้งหมด (ไม่ใช้ Profile Facebook)',
        'details' => 'คุณจอนรับผิดชอบงานด้านรีวิวและการจัดการพิกัดร้านค้าบนแผนที่ โดยใช้โปรไฟล์ Gmail เฉพาะของบริษัทในการปฏิบัติงาน'
    ],
    'fb_group_scraper' => [
        'name' => 'Linda J. Robinson', 
        'thai' => 'คุณลินดา (Linda)', 
        'img' => 'bot_fb_group.png', 
        'role' => 'International Group Scout',
        'identity' => 'FB Profile: YakPho.Aroma (Worldwide)',
        'objective' => 'เฝ้ากลุ่มเล็งเป้าหมายกลุ่มสปาและนวดต่างประเทศ เพื่อหาโพสต์ความต้องการซื้อ',
        'details' => 'คุณลินดามีหน้าที่สแกนกลุ่มต่างประเทศและรายงานข้อมูลโพสต์ที่น่าสนใจเข้ามาในระบบ'
    ],
    'fb_ai_processor'  => [
        'name' => 'Dr. Janie Bode', 
        'thai' => 'คุณหมอเจนี่ (Dr. Janie)', 
        'img' => 'bot_ai_brain.png', 
        'role' => 'Strategy Analyzer',
        'identity' => 'AI Brain System (Gemini)',
        'objective' => 'วิเคราะห์เนื้อหาและติดแท็กระดับคุณภาพลูกค้า (Lead Quality)',
        'details' => 'คุณหมอเจนี่ทำหน้าที่วิเคราะห์ข้อความและตัดสินใจว่า Lead ไหนคือโอกาสทองของธุรกิจ'
    ],
    'fb_joiner'        => [
        'name' => 'Mathew J. Adams', 
        'thai' => 'คุณแมท (Matt)', 
        'img' => 'bot_joiner.png', 
        'role' => 'Community Connector',
        'identity' => 'FB Profile: YakPho.Aroma (Worldwide)',
        'objective' => 'ขยายฐานกลุ่ม Facebook ต่างประเทศด้วยระบบสมัครเข้ากลุ่มอัตโนมัติ',
        'details' => 'คุณแมทรับผิดชอบการขอเข้ากลุ่มและตอบคำถามแอดมินกลุ่มเพื่อให้บอทตัวอื่นๆ เข้าไปทำงานได้'
    ],
    'fb_page_scraper'  => [
        'name' => 'Maya Crist MD (Legacy)', 
        'thai' => 'คุณหมอมายา (Dr. Maya)', 
        'img' => 'bot_page_scout.png', 
        'role' => 'Thai Domestic Page Scout',
        'identity' => 'FB Profile: YakPho Official (Domestic)',
        'objective' => 'สแกนหาข้อมูลลูกค้าและร้านนวดชั้นนำในไทยจากหน้าเพจโดยตรง',
        'details' => 'คุณหมอมายาเน้นงานในประเทศ (Domestic) ขุดข้อมูลจากเพจ Facebook เพื่อหา Lead คุณภาพสูง'
    ],
    'fb_search_harvester' => [
        'name' => 'James T. Harvester', 
        'thai' => 'คุณเจมส์ (James)', 
        'img' => 'bot_james.png', 
        'role' => 'Market Harvester',
        'identity' => 'System Search Agent',
        'objective' => 'กวาดรายชื่อร้านจากหน้า Search ทั่วประเทศไทยในปริมาณมาก',
        'details' => 'คุณเจมส์เป็นฝ่ายล่วงหน้าที่ทำหน้าที่หา URL เป้าหมายให้พนักงานคนอื่นๆ เข้าไปเจาะข้อมูล'
    ],
    'fb_deep_scraper'    => [
        'name' => 'Sarah V. Deep', 
        'thai' => 'คุณซาร่า (Sarah)', 
        'img' => 'bot_sarah.png', 
        'role' => 'Data Extraction Expert',
        'identity' => 'Deep Scout Agent',
        'objective' => 'ขุดหาหมายเลขโทรศัพท์และช่องทางการติดต่อส่วนตัวเชิงลึก',
        'details' => 'คุณซาร่ามีหน้าที่เจาะเข้าไปในส่วนลึกของเพจเพื่อดึงเอาเบอร์ติดต่อโดยตรงออกมา'
    ]
];

$stmt = $db->prepare("INSERT INTO ai_staff (bot_script, name_en, name_th, department, role, identity_desc, objective, details, image_path, start_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$startDate = date('Y-m-d'); // Today's date for initial records

foreach ($bot_staff as $script => $data) {
    $stmt->execute([
        $script,
        $data['name'],
        $data['thai'],
        'Social Automation',
        $data['role'],
        $data['identity'],
        $data['objective'],
        $data['details'],
        $data['img'],
        $startDate,
        'Active'
    ]);
    echo "Inserted: " . $data['name'] . "<br>";
}

echo "<br><b>Migration completed successfully!</b>";
?>
