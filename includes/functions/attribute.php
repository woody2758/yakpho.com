<?php
/**
 * Attribute Management Functions
 * Handles product attributes (formula, scent, size, color, etc.)
 */

/**
 * Get all attribute groups
 */
function get_attribute_groups($langCode = 'th') {
    global $db;
    
    $sql = "SELECT ag.*, agt.group_name, agt.group_description
            FROM product_attribute_groups ag
            LEFT JOIN product_attribute_group_translations agt 
                ON ag.group_id = agt.group_id AND agt.lang_code = ?
            WHERE ag.group_status = 1
            ORDER BY ag.group_order";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$langCode]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get attributes by group code
 */
function get_attributes_by_group($groupCode, $langCode = 'th') {
    global $db;
    
    $sql = "SELECT pa.*, pat.attribute_name, pat.attribute_description
            FROM product_attributes pa
            LEFT JOIN product_attribute_translations pat 
                ON pa.attribute_id = pat.attribute_id AND pat.lang_code = ?
            WHERE pa.group_id = (SELECT group_id FROM product_attribute_groups WHERE group_code = ?)
              AND pa.attribute_status = 1
            ORDER BY pa.attribute_order";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$langCode, $groupCode]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get attribute name by code
 */
function get_attribute_name_by_code($groupCode, $attributeCode, $langCode = 'th') {
    global $db;
    
    $sql = "SELECT pat.attribute_name
            FROM product_attributes pa
            LEFT JOIN product_attribute_translations pat 
                ON pa.attribute_id = pat.attribute_id AND pat.lang_code = ?
            WHERE pa.group_id = (SELECT group_id FROM product_attribute_groups WHERE group_code = ?)
              AND pa.attribute_code = ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$langCode, $groupCode, $attributeCode]);
    return $stmt->fetchColumn() ?: $attributeCode;
}

/**
 * Get product's attribute sets
 */
function get_product_attribute_sets($productId, $langCode = 'th') {
    global $db;
    
    $sql = "SELECT ag.*, agt.group_name
            FROM product_attribute_sets pas
            INNER JOIN product_attribute_groups ag ON pas.group_id = ag.group_id
            LEFT JOIN product_attribute_group_translations agt 
                ON ag.group_id = agt.group_id AND agt.lang_code = ?
            WHERE pas.product_id = ?
            ORDER BY ag.group_order";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$langCode, $productId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Assign attribute group to product
 */
function assign_attribute_to_product($productId, $groupId) {
    global $db;
    
    $sql = "INSERT IGNORE INTO product_attribute_sets (product_id, group_id) VALUES (?, ?)";
    $stmt = $db->prepare($sql);
    return $stmt->execute([$productId, $groupId]);
}

/**
 * Remove attribute group from product
 */
function remove_attribute_from_product($productId, $groupId) {
    global $db;
    
    $sql = "DELETE FROM product_attribute_sets WHERE product_id = ? AND group_id = ?";
    $stmt = $db->prepare($sql);
    return $stmt->execute([$productId, $groupId]);
}

/**
 * Get formulas (H, C, B)
 */
function get_formulas($langCode = 'th') {
    return get_attributes_by_group('formula', $langCode);
}

/**
 * Get scents (01-12)
 */
function get_scents($langCode = 'th') {
    return get_attributes_by_group('scent', $langCode);
}

/**
 * Generate variants for product
 * Creates all possible combinations of assigned attributes
 */
function generate_product_variants($productId, $defaultStock = 0) {
    global $db;
    
    try {
        $db->beginTransaction();
        
        // Get attribute sets for this product
        $sets = get_product_attribute_sets($productId);
        
        if (empty($sets)) {
            throw new Exception("No attribute sets assigned to product");
        }
        
        // Get attributes for each group
        $attributeGroups = [];
        foreach ($sets as $set) {
            $attributes = get_attributes_by_group($set['group_code']);
            if (!empty($attributes)) {
                $attributeGroups[$set['group_code']] = $attributes;
            }
        }
        
        // Generate combinations
        $combinations = generate_combinations($attributeGroups);
        
        // Create variants
        $count = 0;
        foreach ($combinations as $combo) {
            // Build SKU
            $skuParts = [$productId];
            $attributes = [];
            
            foreach ($combo as $groupCode => $attribute) {
                $skuParts[] = $attribute['attribute_code'];
                $attributes[$groupCode] = $attribute['attribute_code'];
            }
            
            $sku = implode('-', $skuParts);
            
            // Check if variant already exists
            $stmt = $db->prepare("SELECT variant_id FROM product_variants WHERE product_id = ? AND variant_sku = ?");
            $stmt->execute([$productId, $sku]);
            
            if (!$stmt->fetchColumn()) {
                create_product_variant($productId, $sku, $attributes, $defaultStock);
                $count++;
            }
        }
        
        $db->commit();
        return $count;
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Helper: Generate all combinations of attributes
 */
function generate_combinations($arrays, $i = 0) {
    if (!isset($arrays[$i])) {
        return array();
    }
    
    $keys = array_keys($arrays);
    $currentKey = $keys[$i];
    $currentArray = $arrays[$currentKey];
    
    if ($i == count($arrays) - 1) {
        $result = [];
        foreach ($currentArray as $item) {
            $result[] = [$currentKey => $item];
        }
        return $result;
    }
    
    $tmp = generate_combinations($arrays, $i + 1);
    $result = array();
    
    foreach ($currentArray as $item) {
        foreach ($tmp as $t) {
            $result[] = array_merge([$currentKey => $item], $t);
        }
    }
    
    return $result;
}
