<?php
require_once "../db.php";

header('Content-Type: application/json');

// API Key Validation
$api_key = $_GET['api_key'] ?? $_POST['api_key'] ?? '';
if ($api_key !== 'yakpho2026') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !is_array($data)) {
    echo json_encode(['ok' => false, 'msg' => 'Invalid data']);
    exit;
}

try {
    $stmt = $db->prepare("INSERT INTO thai_massage_pages 
        (page_name, page_url, messenger_link, province, followers, phone, email, website, is_active, last_visited_at) 
        VALUES (:name, :url, :messenger, :province, :followers, :phone, :email, :website, :is_active, NOW())
        ON DUPLICATE KEY UPDATE 
        page_name = VALUES(page_name),
        messenger_link = VALUES(messenger_link),
        followers = VALUES(followers),
        phone = VALUES(phone),
        email = VALUES(email),
        website = VALUES(website),
        is_active = VALUES(is_active),
        last_visited_at = NOW()");

    foreach ($data as $item) {
        $stmt->execute([
            ':name'      => $item['name'] ?? 'Unknown Page',
            ':url'       => $item['url'],
            ':messenger' => $item['messenger'] ?? null,
            ':province'  => $item['province'],
            ':followers' => $item['followers'] ?? null,
            ':phone'     => $item['phone'] ?? null,
            ':email'     => $item['email'] ?? null,
            ':website'   => $item['website'] ?? null,
            ':is_active' => $item['is_active'] ?? 1
        ]);
    }

    echo json_encode(['ok' => true, 'msg' => 'Enriched data saved successfully']);
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
?>
