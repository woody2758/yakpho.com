<?php
/**
 * Product Management Functions
 * Handles all product-related operations with multi-language support
 */

/**
 * Get all products with pagination and filters
 * 
 * @param int $page Current page
 * @param int $limit Items per page
 * @param string $search Search term
 * @param int $categoryId Filter by category
 * @param string $langCode Language code (default: 'th')
 * @return array Products with translations
 */
function get_all_products($page = 1, $limit = 20, $search = '', $categoryId = 0, $langCode = 'th') {
    global $db;
    
    $offset = ($page - 1) * $limit;
    
    $sql = "SELECT p.*, pt.product_name, pt.product_excerpt, pt.product_unit,
                   pc.productcat_id, pct.productcat_name,
                   tier.tier_name
            FROM product p
            LEFT JOIN product_translations pt ON p.product_id = pt.product_id AND pt.lang_code = ?
            LEFT JOIN productcat pc ON p.productcat_id = pc.productcat_id
            LEFT JOIN productcat_translations pct ON pc.productcat_id = pct.productcat_id AND pct.lang_code = ?
            LEFT JOIN price_tiers tier ON p.price_tier_id = tier.tier_id
            WHERE p.product_del = 0";
    
    $params = [$langCode, $langCode];
    
    // Search
    if (!empty($search)) {
        $search = trim(preg_replace('/\s+/', ' ', $search));
        $keywords = explode(' ', $search);
        $searchConditions = [];
        
        foreach ($keywords as $keyword) {
            if (empty($keyword)) continue;
            $searchConditions[] = "(pt.product_name LIKE ? OR p.product_code LIKE ? OR p.product_id = ?)";
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
            $params[] = $keyword;
        }
        
        if (!empty($searchConditions)) {
            $sql .= " AND (" . implode(" AND ", $searchConditions) . ")";
        }
    }
    
    // Category filter
    if ($categoryId > 0) {
        $sql .= " AND p.productcat_id = ?";
        $params[] = $categoryId;
    }
    
    $sql .= " ORDER BY p.product_id DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Count total products for pagination
 */
function count_all_products($search = '', $categoryId = 0, $langCode = 'th') {
    global $db;
    
    $sql = "SELECT COUNT(DISTINCT p.product_id) 
            FROM product p
            LEFT JOIN product_translations pt ON p.product_id = pt.product_id AND pt.lang_code = ?
            WHERE p.product_del = 0";
    
    $params = [$langCode];
    
    if (!empty($search)) {
        $search = trim(preg_replace('/\s+/', ' ', $search));
        $keywords = explode(' ', $search);
        $searchConditions = [];
        
        foreach ($keywords as $keyword) {
            if (empty($keyword)) continue;
            $searchConditions[] = "(pt.product_name LIKE ? OR p.product_code LIKE ? OR p.product_id = ?)";
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
            $params[] = $keyword;
        }
        
        if (!empty($searchConditions)) {
            $sql .= " AND (" . implode(" AND ", $searchConditions) . ")";
        }
    }
    
    if ($categoryId > 0) {
        $sql .= " AND p.productcat_id = ?";
        $params[] = $categoryId;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

/**
 * Get single product by ID with translation
 */
function get_product_by_id($productId, $langCode = 'th') {
    global $db;
    
    $sql = "SELECT p.*, pt.product_name, pt.product_excerpt, pt.product_detail, 
                   pt.product_unit, pt.product_youtube, pt.product_tag,
                   pt.seo_title, pt.seo_description
            FROM product p
            LEFT JOIN product_translations pt ON p.product_id = pt.product_id AND pt.lang_code = ?
            WHERE p.product_id = ? AND p.product_del = 0";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$langCode, $productId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get all translations for a product
 */
function get_product_translations($productId) {
    global $db;
    
    $sql = "SELECT pt.*, l.lang_name, l.lang_code
            FROM product_translations pt
            LEFT JOIN languages l ON pt.lang_code = l.lang_code
            WHERE pt.product_id = ?
            ORDER BY l.lang_order";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$productId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Create new product
 */
function create_product($data) {
    global $db;
    
    try {
        $db->beginTransaction();
        
        // Insert main product
        $sql = "INSERT INTO product (product_code, productcat_id, product_slug, product_picture,
                                    product_price, product_nprice, product_cprice, product_weight,
                                    price_tier_id, product_stock, stock_alert_enabled, stock_alert_level,
                                    product_status, product_date, save_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['product_code'],
            $data['productcat_id'] ?? null,
            $data['product_slug'] ?? null,
            $data['product_picture'] ?? null,
            $data['product_price'] ?? 0,
            $data['product_nprice'] ?? 0,
            $data['product_cprice'] ?? 0,
            $data['product_weight'] ?? 0,
            $data['price_tier_id'] ?? null,
            $data['product_stock'] ?? 0,
            $data['stock_alert_enabled'] ?? 0,
            $data['stock_alert_level'] ?? 10,
            $data['product_status'] ?? 1,
            $data['save_id'] ?? 0
        ]);
        
        $productId = $db->lastInsertId();
        
        $db->commit();
        return $productId;
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Update product
 */
function update_product($productId, $data) {
    global $db;
    
    try {
        $db->beginTransaction();
        
        $sql = "UPDATE product SET 
                product_code = ?, productcat_id = ?, product_slug = ?, product_picture = ?,
                product_price = ?, product_nprice = ?, product_cprice = ?, product_weight = ?,
                price_tier_id = ?, product_stock = ?, stock_alert_enabled = ?, stock_alert_level = ?,
                product_status = ?, product_update = NOW(), update_id = ?
                WHERE product_id = ?";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['product_code'],
            $data['productcat_id'] ?? null,
            $data['product_slug'] ?? null,
            $data['product_picture'] ?? null,
            $data['product_price'] ?? 0,
            $data['product_nprice'] ?? 0,
            $data['product_cprice'] ?? 0,
            $data['product_weight'] ?? 0,
            $data['price_tier_id'] ?? null,
            $data['product_stock'] ?? 0,
            $data['stock_alert_enabled'] ?? 0,
            $data['stock_alert_level'] ?? 10,
            $data['product_status'] ?? 1,
            $data['update_id'] ?? 0,
            $productId
        ]);
        
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Save product translation
 */
function save_product_translation($productId, $langCode, $data) {
    global $db;
    
    $sql = "INSERT INTO product_translations 
            (product_id, lang_code, product_name, product_excerpt, product_detail,
             product_unit, product_youtube, product_tag, seo_title, seo_description)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            product_name = VALUES(product_name),
            product_excerpt = VALUES(product_excerpt),
            product_detail = VALUES(product_detail),
            product_unit = VALUES(product_unit),
            product_youtube = VALUES(product_youtube),
            product_tag = VALUES(product_tag),
            seo_title = VALUES(seo_title),
            seo_description = VALUES(seo_description)";
    
    $stmt = $db->prepare($sql);
    return $stmt->execute([
        $productId,
        $langCode,
        $data['product_name'] ?? '',
        $data['product_excerpt'] ?? null,
        $data['product_detail'] ?? null,
        $data['product_unit'] ?? null,
        $data['product_youtube'] ?? null,
        $data['product_tag'] ?? null,
        $data['seo_title'] ?? null,
        $data['seo_description'] ?? null
    ]);
}

/**
 * Delete product (soft delete)
 */
function delete_product($productId, $adminId = 0) {
    global $db;
    
    $sql = "UPDATE product SET product_del = 1, update_id = ?, product_update = NOW() 
            WHERE product_id = ?";
    
    $stmt = $db->prepare($sql);
    return $stmt->execute([$adminId, $productId]);
}

/**
 * Get product variants
 */
function get_product_variants($productId, $langCode = 'th') {
    global $db;
    
    $sql = "SELECT pv.*,
                   JSON_UNQUOTE(JSON_EXTRACT(pv.variant_attributes, '$.formula')) as formula_code,
                   JSON_UNQUOTE(JSON_EXTRACT(pv.variant_attributes, '$.scent')) as scent_code
            FROM product_variants pv
            WHERE pv.product_id = ?
            ORDER BY pv.variant_sku";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$productId]);
    $variants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get attribute names
    foreach ($variants as &$variant) {
        if (!empty($variant['formula_code'])) {
            $variant['formula_name'] = get_attribute_name_by_code('formula', $variant['formula_code'], $langCode);
        }
        if (!empty($variant['scent_code'])) {
            $variant['scent_name'] = get_attribute_name_by_code('scent', $variant['scent_code'], $langCode);
        }
    }
    
    return $variants;
}

/**
 * Create product variant
 */
function create_product_variant($productId, $sku, $attributes, $stock = 0) {
    global $db;
    
    $sql = "INSERT INTO product_variants (product_id, variant_sku, variant_attributes, variant_stock)
            VALUES (?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    return $stmt->execute([
        $productId,
        $sku,
        json_encode($attributes),
        $stock
    ]);
}

/**
 * Update variant stock
 */
function update_variant_stock($variantId, $newStock, $reason, $orderId = null, $adminId = null) {
    global $db;
    
    try {
        $db->beginTransaction();
        
        // Get current stock
        $stmt = $db->prepare("SELECT variant_stock, product_id FROM product_variants WHERE variant_id = ?");
        $stmt->execute([$variantId]);
        $variant = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$variant) {
            throw new Exception("Variant not found");
        }
        
        $stockBefore = $variant['variant_stock'];
        $stockChange = $newStock - $stockBefore;
        
        // Update stock
        $stmt = $db->prepare("UPDATE product_variants SET variant_stock = ? WHERE variant_id = ?");
        $stmt->execute([$newStock, $variantId]);
        
        // Log
        $stmt = $db->prepare("INSERT INTO product_stock_log 
                             (product_id, variant_id, stock_change, stock_before, stock_after, reason, order_id, admin_id)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $variant['product_id'],
            $variantId,
            $stockChange,
            $stockBefore,
            $newStock,
            $reason,
            $orderId,
            $adminId
        ]);
        
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}
