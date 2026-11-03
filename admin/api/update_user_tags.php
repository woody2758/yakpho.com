<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $data['user_id'] ?? 0;
    $tagName = trim($data['tag_name'] ?? '');
    $action = $data['action'] ?? 'add'; // 'add' or 'remove'

    if (!$userId || !$tagName) throw new Exception('Invalid input');

    $db->beginTransaction();

    if ($action === 'add') {
        // 1. Ensure tag exists in user_tags
        $stmt = $db->prepare("SELECT tag_id FROM user_tags WHERE tag_name = ?");
        $stmt->execute([$tagName]);
        $tag = $stmt->fetch();

        if (!$tag) {
            $stmt = $db->prepare("INSERT INTO user_tags (tag_name) VALUES (?)");
            $stmt->execute([$tagName]);
            $tagId = $db->lastInsertId();
        } else {
            $tagId = $tag['tag_id'];
        }

        // 2. Link user to tag
        $stmt = $db->prepare("INSERT IGNORE INTO user_tag_mapping (user_id, tag_id) VALUES (?, ?)");
        $stmt->execute([$userId, $tagId]);

    } else if ($action === 'remove') {
        // Find tag id
        $stmt = $db->prepare("SELECT tag_id FROM user_tags WHERE tag_name = ?");
        $stmt->execute([$tagName]);
        $tag = $stmt->fetch();

        if ($tag) {
            $stmt = $db->prepare("DELETE FROM user_tag_mapping WHERE user_id = ? AND tag_id = ?");
            $stmt->execute([$userId, $tag['tag_id']]);
        }
    }

    $db->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
