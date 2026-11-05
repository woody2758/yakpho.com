<?php
require_once "../staff/db.php";

$code = $_GET['code'] ?? '';

if (empty($code)) {
    die("Invalid link.");
}

// Protect against SQLi/XSS
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $code)) {
    die("Invalid link format.");
}

$stmt = $db->prepare("SELECT id as content_id, campaign_id, target_url FROM fb_campaign_contents WHERE short_code = ? LIMIT 1");
$stmt->execute([$code]);
$content = $stmt->fetch();

if (!$content || empty($content['target_url'])) {
    die("Link not found or has been removed.");
}

$target_url = $content['target_url'];
$campaign_id = $content['campaign_id'];
$content_id = $content['content_id'];

// Check for bots/crawlers
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$is_bot = preg_match('/bot|crawl|slurp|spider|mediapartners|facebookexternalhit|Facebot/i', $user_agent);

if (!$is_bot) {
    try {
        $stmt_track = $db->prepare("
            INSERT INTO fb_link_tracking (short_code, campaign_id, content_id, click_count, last_clicked_at) 
            VALUES (?, ?, ?, 1, NOW()) 
            ON DUPLICATE KEY UPDATE click_count = click_count + 1, last_clicked_at = NOW()
        ");
        $stmt_track->execute([$code, $campaign_id, $content_id]);
    } catch(Exception $e) {
        // Silently ignore to not break the redirect
    }
}

// Perform the redirect
header("Location: " . $target_url);
exit;
