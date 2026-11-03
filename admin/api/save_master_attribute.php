<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $attributeId = $_POST['attribute_id'] ?? null;
    $groupCode = $_POST['group_code'] ?? '';
    $attributeCode = $_POST['attribute_code'] ?? '';
    $names = $_POST['names'] ?? []; // Array of [lang_code => name]

    $db->beginTransaction();

    if ($attributeId) {
        // Update
        $stmt = $db->prepare("UPDATE product_attributes SET attribute_code = ? WHERE attribute_id = ?");
        $stmt->execute([$attributeCode, $attributeId]);
    } else {
        // Create
        $stmt = $db->prepare("SELECT group_id FROM product_attribute_groups WHERE group_code = ?");
        $stmt->execute([$groupCode]);
        $groupId = $stmt->fetchColumn();
        
        if (!$groupId) throw new Exception("Invalid Group Code: $groupCode");

        $stmt = $db->prepare("INSERT INTO product_attributes (group_id, attribute_code, attribute_status) VALUES (?, ?, 1)");
        $stmt->execute([$groupId, $attributeCode]);
        $attributeId = $db->lastInsertId();
    }

    // Handle Translations Dynamically
    foreach ($names as $langCode => $name) {
        $stmt = $db->prepare("INSERT INTO product_attribute_translations (attribute_id, lang_code, attribute_name) 
                             VALUES (?, ?, ?) 
                             ON DUPLICATE KEY UPDATE attribute_name = VALUES(attribute_name)");
        $stmt->execute([$attributeId, $langCode, $name]);
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'บันทึกข้อมูลเรียบร้อยแล้ว']);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
