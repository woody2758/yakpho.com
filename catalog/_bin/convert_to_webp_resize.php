<?php
// ============================================================
// Yakpho Aroma – Convert & Resize to WEBP (≤100KB)
// แสดงผลบน Browser + Resize ให้อัตโนมัติ
// ============================================================

$inputDir  = __DIR__ . '/assets/img_src';
$outputDir = __DIR__ . '/assets/img/scents';
$max_kb    = 100;
$quality   = 90;

header("Content-Type: text/html; charset=utf-8");
echo "<h3>Yakpho Aroma – Resize to WEBP ≤ {$max_kb}KB</h3><pre>";

if (!is_dir($outputDir)) mkdir($outputDir, 0777, true);

$files = glob("$inputDir/*.{jpg,jpeg,png,webp}", GLOB_BRACE);
if (!$files) {
    echo "❌ ไม่พบไฟล์ใน $inputDir\n";
    exit;
}

foreach ($files as $file) {
    $info = pathinfo($file);
    $out  = $outputDir . '/' . $info['filename'] . '.webp';
    $ext  = strtolower($info['extension']);

        switch ($ext) {
            case 'jpg':
            case 'jpeg':
            case 'jfif':   // ✅ เพิ่มบรรทัดนี้เพื่อรองรับไฟล์ .jfif
                $img = imagecreatefromjpeg($file);
                break;

            case 'png':
                $img = imagecreatefrompng($file);
                break;

            case 'webp':
                $img = imagecreatefromwebp($file);
                break;

            default:
                echo "⚠️ ข้าม {$info['basename']} (ไม่รองรับ)\n";
                continue 2;
        }
    if (!$img) { echo "❌ โหลดภาพล้มเหลว: {$info['basename']}\n"; continue; }

    $width = imagesx($img);
    $height = imagesy($img);
    $scale = 1.0;
    $try = 0;

    do {
        $try++;
        $newW = (int)($width * $scale);
        $newH = (int)($height * $scale);
        $resized = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $width, $height);

        $tmp = tempnam(sys_get_temp_dir(), 'yakpho');
        imagewebp($resized, $tmp, $quality);
        $sizeKB = filesize($tmp) / 1024;
        imagedestroy($resized);

        echo sprintf("%-20s pass:%2d  %4dx%-4d  %5.1fKB  q=%2d  scale=%.2f\n",
            $info['basename'], $try, $newW, $newH, $sizeKB, $quality, $scale);

        if ($sizeKB > $max_kb && $try < 15) {
            $scale *= 0.9;
            if ($quality > 50) $quality -= 5;
            unlink($tmp);
        } else {
            rename($tmp, $out);
            echo "✅ บันทึก: {$out} ({$sizeKB}KB)\n\n";
            break;
        }
    } while (true);

    imagedestroy($img);
}

echo "🎉 เสร็จสิ้น!</pre>";
?>
