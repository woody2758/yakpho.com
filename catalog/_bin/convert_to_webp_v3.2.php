<?php
/*
===========================================================
 YakPho Aroma – Convert Images → WEBP (v3.2)
 Author: Woody & ChatGPT
 Description:
   - รองรับ JPG / JPEG / JFIF / PNG
   - อ่าน EXIF แล้วหมุนภาพแนวตั้งอัตโนมัติ
   - Resize ความสูง 800px (รักษาอัตราส่วน)
   - ลดขนาด ≤ 50 KB (ปรับคุณภาพอัตโนมัติ)
   - ย้ายต้นฉบับไปเก็บใน /_originals/
   - แสดงสรุปผลท้ายหน้า
===========================================================
*/

// 📸 ฟังก์ชันแก้ภาพเอียงจาก EXIF
function fixImageOrientation(&$img, $filePath) {
    if (function_exists('exif_read_data') &&
        in_array(strtolower(pathinfo($filePath, PATHINFO_EXTENSION)), ['jpg','jpeg','jfif'])) {
        $exif = @exif_read_data($filePath);
        if (!empty($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 3: $img = imagerotate($img, 180, 0); break;
                case 6: $img = imagerotate($img, -90, 0); break;
                case 8: $img = imagerotate($img, 90, 0); break;
            }
        }
    }
}

// 🧩 โฟลเดอร์หลัก
$baseDir   = __DIR__ . '/images/';
$backupDir = $baseDir . '_originals/';

// ⚙️ การตั้งค่า
$targetMax    = 50 * 1024;  // ≤ 50 KB
$qualityStart = 90;
$qualityMin   = 40;
$targetHeight = 800;        // 📏 Resize ความสูง

// 🔍 ตรวจสอบโฟลเดอร์
if (!is_dir($baseDir)) die("❌ ไม่พบโฟลเดอร์ภาพ: $baseDir");
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
    echo "📁 สร้างโฟลเดอร์สำรองแล้ว → _originals<br>";
}

// 🔎 ค้นหาไฟล์ภาพ
$files = glob($baseDir . '*.{jpg,jpeg,JPG,JPEG,png,PNG,jfif,JFIF}', GLOB_BRACE);
if (empty($files)) die("⚠️ ไม่พบไฟล์ภาพในโฟลเดอร์นี้");

echo "🚀 เริ่มแปลงและ Resize ภาพทั้งหมด ...<br><br>";

// ตัวแปรเก็บสถิติ
$totalOriginal  = 0;
$totalConverted = 0;
$countSuccess   = 0;
$countFail      = 0;
$startTime      = microtime(true);

foreach ($files as $file) {
    $info = pathinfo($file);
    $basename = $info['basename'];
    $ext = strtolower($info['extension']);
    $output = $baseDir . $info['filename'] . '.webp';

    $origSize = filesize($file);
    $totalOriginal += $origSize;

    // โหลดภาพ
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
        case 'jfif':
            $img = @imagecreatefromjpeg($file);
            fixImageOrientation($img, $file);
            break;
        case 'png':
            $img = @imagecreatefrompng($file);
            imagepalettetotruecolor($img);
            imagealphablending($img, true);
            imagesavealpha($img, true);
            break;
        default:
            echo "⚠️ ข้าม {$basename} (ไม่รองรับ .$ext)<br>";
            continue 2;
    }
    if (!$img) {
        echo "❌ ไม่สามารถเปิดภาพ: $basename<br>";
        $countFail++;
        continue;
    }

    // 📐 Resize ความสูง = 800px (รักษาอัตราส่วน)
    $width = imagesx($img);
    $height = imagesy($img);
    if ($height > $targetHeight) {
        $newHeight = $targetHeight;
        $newWidth = intval($width * ($newHeight / $height));
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($resized, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($img);
        $img = $resized;
        echo "📏 Resize → {$newWidth}x{$newHeight}px<br>";
    }

    // 🔁 ลดคุณภาพจน ≤50KB
    $q = $qualityStart;
    do {
        imagewebp($img, $output, $q);
        clearstatcache();
        $size = filesize($output);
        $q -= 10;
    } while ($size > $targetMax && $q >= $qualityMin);

    imagedestroy($img);

    if (file_exists($output)) {
        $totalConverted += $size;
        $countSuccess++;
        echo "✅ แปลงสำเร็จ: <strong>{$basename}</strong> → "
            . basename($output)
            . " <small>(" . round($size/1024,1) . " KB @q" . ($q+10) . ")</small><br>";

        // 📦 ย้ายต้นฉบับ
        $backupPath = $backupDir . $basename;
        if (@rename($file, $backupPath))
            echo "↪️ ย้ายต้นฉบับไป: _originals/$basename<br><br>";
        else
            echo "⚠️ ย้ายต้นฉบับไม่สำเร็จ (ตรวจสิทธิ์โฟลเดอร์)<br><br>";
    } else {
        echo "❌ แปลงไฟล์ไม่สำเร็จ: $basename<br><br>";
        $countFail++;
    }
}

// 🕒 สรุปผล
$elapsed    = round(microtime(true) - $startTime, 2);
$reduction  = ($totalOriginal > 0)
    ? round(100 - ($totalConverted / $totalOriginal) * 100, 1)
    : 0;

echo "<hr><h3>📊 สรุปผลการแปลงภาพ</h3>";
echo "✅ แปลงสำเร็จ: {$countSuccess} ไฟล์<br>";
echo "⚠️ ไม่สำเร็จ: {$countFail} ไฟล์<br>";
echo "💾 ขนาดรวมเดิม: " . round($totalOriginal/1024,1) . " KB<br>";
echo "📉 ขนาดหลังแปลง: " . round($totalConverted/1024,1) . " KB<br>";
echo "💫 ลดลงประมาณ {$reduction}%<br>";
echo "⏱️ ใช้เวลา: {$elapsed} วินาที<br>";
echo "<hr>🎉 เสร็จสิ้นทั้งหมด!";
?>
