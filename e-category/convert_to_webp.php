<?php
/*
===========================================================
 Yakpho Aroma – Convert JPG → WEBP Utility
 Author: Woody & ChatGPT
 Description:
  - แปลงไฟล์ JPG/JPEG → WEBP
  - ย้ายไฟล์ต้นฉบับไปเก็บไว้ที่ /assets/img/_originals/
===========================================================
*/

// 🧩 กำหนดโฟลเดอร์หลัก
$baseDir = __DIR__ . '/assets/img_src/i';
$backupDir = $baseDir . '_originals/';

// 🧠 ตั้งค่าคุณภาพของ WebP (0–100)
$quality = 80;

// 🔍 ตรวจสอบโฟลเดอร์
if (!is_dir($baseDir)) {
    die("❌ ไม่พบโฟลเดอร์ภาพ: $baseDir");
}
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
    echo "📁 สร้างโฟลเดอร์สำรองเรียบร้อย: _originals<br>";
}

// 🔎 ค้นหาไฟล์ JPG ทั้งหมด
$files = glob($baseDir . '*.{jpg,jpeg,JPG,JPEG}', GLOB_BRACE);
if (empty($files)) {
    die("⚠️ ไม่พบไฟล์ JPG ในโฟลเดอร์นี้");
}

echo "🚀 เริ่มแปลงไฟล์ JPG → WEBP ...<br><br>";

foreach ($files as $file) {
    $basename = basename($file);
    $output = preg_replace('/\.(jpe?g)$/i', '.webp', $file);

    $img = @imagecreatefromjpeg($file);
    if (!$img) {
        echo "❌ ไม่สามารถอ่านไฟล์: $basename<br>";
        continue;
    }

    // ✅ แปลงและบันทึก WEBP
    if (imagewebp($img, $output, $quality)) {
        imagedestroy($img);
        echo "✅ แปลงสำเร็จ: $basename → " . basename($output) . "<br>";

        // 📦 ย้ายไฟล์ต้นฉบับไป _originals
        $backupPath = $backupDir . $basename;
        if (rename($file, $backupPath)) {
            echo "↪️ ย้ายไฟล์ต้นฉบับไป: _originals/$basename<br><br>";
        } else {
            echo "⚠️ ย้ายไฟล์ต้นฉบับไม่สำเร็จ (ตรวจสิทธิ์โฟลเดอร์)<br><br>";
        }
    } else {
        echo "❌ แปลงไฟล์ไม่สำเร็จ: $basename<br><br>";
        imagedestroy($img);
    }
}

echo "<hr>🎉 เสร็จสิ้นทั้งหมด!";
?>
