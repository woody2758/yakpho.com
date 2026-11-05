<?php
header('Content-Type: application/json');
require_once "../db.php";

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

$api_key = $_GET['api_key'] ?? '';
$content_id = $_GET['content_id'] ?? 0;

if ($api_key !== 'yakpho2026') {
    echo json_encode(['ok' => false, 'msg' => 'Invalid API Key']);
    exit;
}

$group_id = $_GET['group_id'] ?? 0;

if (!$content_id) {
    echo json_encode(['ok' => false, 'msg' => 'Missing content_id']);
    exit;
}

$stmt = $db->prepare("SELECT campaign_id, message, media_type, media_urls, short_code, target_url FROM fb_campaign_contents WHERE id = ?");
$stmt->execute([$content_id]);
$content = $stmt->fetch();

if ($content) {
    $text = $content['message'];
    
    // Add tracking link if target_url exists
    if (!empty($content['target_url'])) {
        $tracking_link = "";
        
        if ($group_id > 0) {
            // Check if short code already exists for this group and content
            $stmt_check = $db->prepare("SELECT short_code FROM fb_short_links WHERE campaign_id = ? AND content_id = ? AND group_id = ? LIMIT 1");
            $stmt_check->execute([$content['campaign_id'], $content_id, $group_id]);
            $existing_link = $stmt_check->fetch();
            
            if ($existing_link) {
                $unique_short_code = $existing_link['short_code'];
            } else {
                // Generate a new unique 5-character short code
                do {
                    $unique_short_code = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
                    $stmt_uniq = $db->prepare("SELECT id FROM fb_short_links WHERE short_code = ?");
                    $stmt_uniq->execute([$unique_short_code]);
                } while ($stmt_uniq->fetch());
                
                // Insert into fb_short_links
                $stmt_insert = $db->prepare("INSERT INTO fb_short_links (short_code, campaign_id, content_id, group_id, target_url) VALUES (?, ?, ?, ?, ?)");
                $stmt_insert->execute([$unique_short_code, $content['campaign_id'], $content_id, $group_id, $content['target_url']]);
            }
            $tracking_link = "https://yakpho.com/l/" . $unique_short_code;
        } else if (!empty($content['short_code'])) {
            // Fallback to old system if no group_id is provided
            $tracking_link = "https://yakpho.com/l/" . $content['short_code'];
        }

        if (!empty($tracking_link)) {
            // Add emoji and CTA
            $cta_link = "👉 สนใจคลิก: " . $tracking_link . " 👈";
            
            // If placeholder [LINK] exists, replace it, else append
            if (strpos($text, '[LINK]') !== false) {
                $text = str_replace('[LINK]', $cta_link, $text);
            } else {
                $text .= "\n\n" . $cta_link;
            }
        }
    }
    
    $images = [];
    if ($content['media_type'] === 'image' && !empty($content['media_urls'])) {
        // Assume media_urls is stored as JSON array of paths like ["uploads/campaigns/img1.jpg"]
        $parsed = json_decode($content['media_urls'], true);
        if (is_array($parsed)) {
            $images = $parsed;
        } else {
            // Fallback if comma separated
            $images = array_map('trim', explode(',', $content['media_urls']));
        }
    }

    echo json_encode([
        'ok' => true,
        'content' => $text,
        'images' => $images // Relative paths like 'uploads/xxx.jpg'
    ]);
} else {
    echo json_encode(['ok' => false, 'msg' => 'Content not found']);
}
