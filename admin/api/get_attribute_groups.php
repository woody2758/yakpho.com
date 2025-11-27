<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions/attribute.php';

header('Content-Type: application/json');

try {
    $groups = get_attribute_groups('th');
    echo json_encode(['success' => true, 'groups' => $groups]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
