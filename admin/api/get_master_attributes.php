<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

try {
    // Helper to get attributes for a group with all translations
    function getAttributesByGroup($groupCode) {
        global $db;
        $sql = "SELECT pa.attribute_id, pa.attribute_code, pa.attribute_index
                FROM product_attributes pa
                JOIN product_attribute_groups pag ON pa.group_id = pag.group_id
                WHERE pag.group_code = ? AND pa.attribute_status = 1
                ORDER BY pa.attribute_index ASC, pa.attribute_id ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$groupCode]);
        $attributes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($attributes as &$attr) {
            $tStmt = $db->prepare("SELECT lang_code, attribute_name FROM product_attribute_translations WHERE attribute_id = ?");
            $tStmt->execute([$attr['attribute_id']]);
            $translations = $tStmt->fetchAll(PDO::FETCH_KEY_PAIR);
            $attr['translations'] = $translations;
            // Also keep name_th and name_en for the main table view
            $attr['name_th'] = $translations['th'] ?? '';
            $attr['name_en'] = $translations['en'] ?? '';
        }
        return $attributes;
    }

    $scents = getAttributesByGroup('scent');
    $formulas = getAttributesByGroup('formula');

    echo json_encode([
        'success' => true,
        'scents' => $scents,
        'formulas' => $formulas
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
