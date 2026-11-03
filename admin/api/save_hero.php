<?php
/**
 * Save Hero Slide
 * Create or update hero slide with translations
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions/image.php';

try {
    session_start();
    $user_id = $_SESSION['user_id'] ?? 0;
    
    $slide_id = $_POST['slide_id'] ?? 0;
    $slide_bg_color = $_POST['slide_bg_color'] ?? '#0A2F2A';
    $button1_link = $_POST['button1_link'] ?? '';
    $button2_link = $_POST['button2_link'] ?? '';
    $slide_status = $_POST['slide_status'] ?? 'active';
    $quick_toggle = $_POST['quick_toggle'] ?? 0;
    
    // Handle quick status toggle
    if ($quick_toggle && $slide_id) {
        $stmt = $db->prepare("UPDATE hero_slides SET slide_status = ?, update_id = ? WHERE slide_id = ?");
        $stmt->execute([$slide_status, $user_id, $slide_id]);
        
        echo json_encode(['success' => true]);
        exit;
    }
    
    // Handle image upload (both file upload and base64)
    $slide_image = $_POST['existing_image'] ?? '';
    
    // Check for base64 image first (from cropper)
    if (!empty($_POST['hero_image_base64'])) {
        $upload_dir = __DIR__ . '/../../uploads/hero/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $base64_image = $_POST['hero_image_base64'];
        
        // Remove data:image prefix
        if (preg_match('/^data:image\/([\w\+\-]+);base64,/', $base64_image, $type)) {
            $base64_image = substr($base64_image, strpos($base64_image, ',') + 1);
        } else {
            throw new Exception('Invalid base64 image format');
        }
        
        $base64_image = str_replace(' ', '+', $base64_image);
        
        $sourceImage = load_image_from_base64($base64_image);
        if (!$sourceImage) {
            throw new Exception('ไม่สามารถอ่านไฟล์รูปภาพได้');
        }
        
        $new_filename = 'hero_' . time() . '_' . uniqid() . '.webp';
        $upload_path = $upload_dir . $new_filename;
        
        if (save_as_webp($sourceImage, $upload_path, 85)) {
            imagedestroy($sourceImage);
            // Delete old image
            if ($slide_image && file_exists($upload_dir . $slide_image)) {
                @unlink($upload_dir . $slide_image);
            }
            $slide_image = $new_filename;
        } else {
            imagedestroy($sourceImage);
            throw new Exception('Failed to save base64 image as WebP');
        }
    }
    // Handle traditional file upload
    elseif (isset($_FILES['slide_image']) && $_FILES['slide_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../../uploads/hero/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES['slide_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'jfif', 'svg', 'bmp', 'heic', 'heif'];
        
        if (!in_array($file_ext, $allowed)) {
            throw new Exception('Invalid file type. Only standard images allowed.');
        }
        
        if ($_FILES['slide_image']['size'] > 5 * 1024 * 1024) {
            throw new Exception('File too large. Maximum 5MB.');
        }
        
        if ($file_ext === 'svg') {
            $new_filename = 'hero_' . time() . '_' . uniqid() . '.svg';
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['slide_image']['tmp_name'], $upload_path)) {
                if ($slide_image && file_exists($upload_dir . $slide_image)) @unlink($upload_dir . $slide_image);
                $slide_image = $new_filename;
            }
        } else {
            $new_filename = 'hero_' . time() . '_' . uniqid() . '.webp';
            $upload_path = $upload_dir . $new_filename;
            
            $sourceImage = load_image($_FILES['slide_image']['tmp_name']);
            if ($sourceImage) {
                if (save_as_webp($sourceImage, $upload_path, 85)) {
                    if ($slide_image && file_exists($upload_dir . $slide_image)) @unlink($upload_dir . $slide_image);
                    $slide_image = $new_filename;
                }
                imagedestroy($sourceImage);
            }
        }
    }
    
    $db->beginTransaction();
    
    if ($slide_id) {
        // Update existing slide
        $stmt = $db->prepare("
            UPDATE hero_slides SET
                slide_image = ?,
                slide_bg_color = ?,
                button1_link = ?,
                button2_link = ?,
                slide_status = ?,
                update_id = ?
            WHERE slide_id = ?
        ");
        $stmt->execute([
            $slide_image,
            $slide_bg_color,
            $button1_link,
            $button2_link,
            $slide_status,
            $user_id,
            $slide_id
        ]);
    } else {
        // Insert new slide
        $stmt = $db->prepare("
            SELECT COALESCE(MAX(slide_order), 0) + 1 as next_order FROM hero_slides
        ");
        $stmt->execute();
        $next_order = $stmt->fetch(PDO::FETCH_ASSOC)['next_order'];
        
        $stmt = $db->prepare("
            INSERT INTO hero_slides (slide_image, slide_bg_color, button1_link, button2_link, slide_status, slide_order, save_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $slide_image,
            $slide_bg_color,
            $button1_link,
            $button2_link,
            $slide_status,
            $next_order,
            $user_id
        ]);
        
        $slide_id = $db->lastInsertId();
    }
    
    // Save translations
    $languages = ['th', 'en', 'zh', 'de', 'fr', 'ja', 'ko', 'ru', 'ar', 'he'];
    
    foreach ($languages as $lang) {
        $slide_title = $_POST["slide_title_$lang"] ?? '';
        $slide_subtitle = $_POST["slide_subtitle_$lang"] ?? '';
        $button1_text = $_POST["button1_text_$lang"] ?? '';
        $button2_text = $_POST["button2_text_$lang"] ?? '';
        
        $stmt = $db->prepare("
            INSERT INTO hero_slides_translations (slide_id, lang_code, slide_title, slide_subtitle, button1_text, button2_text)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                slide_title = VALUES(slide_title),
                slide_subtitle = VALUES(slide_subtitle),
                button1_text = VALUES(button1_text),
                button2_text = VALUES(button2_text)
        ");
        $stmt->execute([$slide_id, $lang, $slide_title, $slide_subtitle, $button1_text, $button2_text]);
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'slide_id' => $slide_id
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
