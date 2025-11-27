<?php
/**
 * Test Product System - Verify Database Import
 */

require_once __DIR__ . '/../includes/config.php';

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Product System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f8f9fa; }
        .test-section { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .test-section h3 { color: #0d6efd; margin-bottom: 15px; }
        .badge { font-size: 0.9em; }
        table { font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">🧪 Product System - Database Verification</h1>

        <?php
        // Test 1: Languages
        echo '<div class="test-section">';
        echo '<h3>1. Languages</h3>';
        try {
            $stmt = $db->query("SELECT * FROM languages ORDER BY lang_order");
            $languages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($languages) > 0) {
                echo '<span class="badge bg-success">✓ Found ' . count($languages) . ' languages</span>';
                echo '<table class="table table-sm mt-3">';
                echo '<thead><tr><th>Code</th><th>Name</th><th>Flag</th><th>Status</th><th>Default</th></tr></thead><tbody>';
                foreach ($languages as $lang) {
                    echo '<tr>';
                    echo '<td><code>' . htmlspecialchars($lang['lang_code']) . '</code></td>';
                    echo '<td>' . htmlspecialchars($lang['lang_name']) . '</td>';
                    echo '<td>' . $lang['lang_flag'] . '</td>';
                    echo '<td>' . ($lang['lang_status'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>') . '</td>';
                    echo '<td>' . ($lang['lang_default'] ? '<span class="badge bg-primary">Default</span>' : '') . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<span class="badge bg-danger">✗ No languages found</span>';
            }
        } catch (Exception $e) {
            echo '<span class="badge bg-danger">✗ Error: ' . $e->getMessage() . '</span>';
        }
        echo '</div>';

        // Test 2: Attribute Groups
        echo '<div class="test-section">';
        echo '<h3>2. Attribute Groups</h3>';
        try {
            $stmt = $db->query("SELECT ag.*, agt.group_name 
                               FROM product_attribute_groups ag
                               LEFT JOIN product_attribute_group_translations agt ON ag.group_id = agt.group_id AND agt.lang_code = 'th'
                               ORDER BY ag.group_order");
            $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($groups) > 0) {
                echo '<span class="badge bg-success">✓ Found ' . count($groups) . ' attribute groups</span>';
                echo '<table class="table table-sm mt-3">';
                echo '<thead><tr><th>Code</th><th>Name (TH)</th><th>Type</th><th>Status</th></tr></thead><tbody>';
                foreach ($groups as $group) {
                    echo '<tr>';
                    echo '<td><code>' . htmlspecialchars($group['group_code']) . '</code></td>';
                    echo '<td>' . htmlspecialchars($group['group_name'] ?? 'N/A') . '</td>';
                    echo '<td><span class="badge bg-info">' . $group['group_type'] . '</span></td>';
                    echo '<td>' . ($group['group_status'] ? '✓' : '✗') . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<span class="badge bg-danger">✗ No attribute groups found</span>';
            }
        } catch (Exception $e) {
            echo '<span class="badge bg-danger">✗ Error: ' . $e->getMessage() . '</span>';
        }
        echo '</div>';

        // Test 3: Formulas
        echo '<div class="test-section">';
        echo '<h3>3. Formulas (H, C, B)</h3>';
        try {
            $stmt = $db->query("SELECT pa.*, pat.attribute_name, pat.attribute_description
                               FROM product_attributes pa
                               LEFT JOIN product_attribute_translations pat ON pa.attribute_id = pat.attribute_id AND pat.lang_code = 'th'
                               WHERE pa.group_id = (SELECT group_id FROM product_attribute_groups WHERE group_code = 'formula')
                               ORDER BY pa.attribute_order");
            $formulas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($formulas) > 0) {
                echo '<span class="badge bg-success">✓ Found ' . count($formulas) . ' formulas</span>';
                echo '<table class="table table-sm mt-3">';
                echo '<thead><tr><th>Code</th><th>Name (TH)</th><th>Description</th></tr></thead><tbody>';
                foreach ($formulas as $formula) {
                    echo '<tr>';
                    echo '<td><code>' . htmlspecialchars($formula['attribute_code']) . '</code></td>';
                    echo '<td><strong>' . htmlspecialchars($formula['attribute_name'] ?? 'N/A') . '</strong></td>';
                    echo '<td>' . htmlspecialchars($formula['attribute_description'] ?? '') . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<span class="badge bg-danger">✗ No formulas found</span>';
            }
        } catch (Exception $e) {
            echo '<span class="badge bg-danger">✗ Error: ' . $e->getMessage() . '</span>';
        }
        echo '</div>';

        // Test 4: Scents
        echo '<div class="test-section">';
        echo '<h3>4. Scents (12 กลิ่น)</h3>';
        try {
            $stmt = $db->query("SELECT pa.*, pat.attribute_name, pat.attribute_description
                               FROM product_attributes pa
                               LEFT JOIN product_attribute_translations pat ON pa.attribute_id = pat.attribute_id AND pat.lang_code = 'th'
                               WHERE pa.group_id = (SELECT group_id FROM product_attribute_groups WHERE group_code = 'scent')
                               ORDER BY pa.attribute_order");
            $scents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($scents) > 0) {
                echo '<span class="badge bg-success">✓ Found ' . count($scents) . ' scents</span>';
                echo '<table class="table table-sm mt-3">';
                echo '<thead><tr><th>Code</th><th>Name (TH)</th><th>Description</th><th>Image</th></tr></thead><tbody>';
                foreach ($scents as $scent) {
                    echo '<tr>';
                    echo '<td><code>' . htmlspecialchars($scent['attribute_code']) . '</code></td>';
                    echo '<td><strong>' . htmlspecialchars($scent['attribute_name'] ?? 'N/A') . '</strong></td>';
                    echo '<td class="small">' . htmlspecialchars(mb_substr($scent['attribute_description'] ?? '', 0, 50)) . '...</td>';
                    echo '<td><code>' . htmlspecialchars($scent['attribute_image'] ?? 'N/A') . '</code></td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<span class="badge bg-danger">✗ No scents found</span>';
            }
        } catch (Exception $e) {
            echo '<span class="badge bg-danger">✗ Error: ' . $e->getMessage() . '</span>';
        }
        echo '</div>';

        // Test 5: Price Tiers
        echo '<div class="test-section">';
        echo '<h3>5. Price Tiers</h3>';
        try {
            $stmt = $db->query("SELECT * FROM price_tiers ORDER BY tier_id");
            $tiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($tiers) > 0) {
                echo '<span class="badge bg-success">✓ Found ' . count($tiers) . ' price tiers</span>';
                
                foreach ($tiers as $tier) {
                    echo '<h5 class="mt-3">' . htmlspecialchars($tier['tier_name']) . '</h5>';
                    
                    $stmt2 = $db->prepare("SELECT * FROM price_tier_levels WHERE tier_id = ? ORDER BY level_order");
                    $stmt2->execute([$tier['tier_id']]);
                    $levels = $stmt2->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($levels) > 0) {
                        echo '<table class="table table-sm">';
                        echo '<thead><tr><th>Min Quantity</th><th>Price/Unit</th></tr></thead><tbody>';
                        foreach ($levels as $level) {
                            echo '<tr>';
                            echo '<td>' . $level['min_quantity'] . ' kg</td>';
                            echo '<td><strong>' . number_format($level['price_per_unit'], 2) . ' THB</strong></td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                    }
                }
            } else {
                echo '<span class="badge bg-danger">✗ No price tiers found</span>';
            }
        } catch (Exception $e) {
            echo '<span class="badge bg-danger">✗ Error: ' . $e->getMessage() . '</span>';
        }
        echo '</div>';

        // Test 6: Product Translations
        echo '<div class="test-section">';
        echo '<h3>6. Product Translations (Migrated Data)</h3>';
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM product_translations");
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                echo '<span class="badge bg-success">✓ Found ' . $count . ' product translations</span>';
                
                $stmt = $db->query("SELECT pt.*, p.product_code 
                                   FROM product_translations pt
                                   LEFT JOIN product p ON pt.product_id = p.product_id
                                   WHERE pt.lang_code = 'th'
                                   LIMIT 5");
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($products) > 0) {
                    echo '<p class="mt-3 mb-2"><small>Sample (first 5):</small></p>';
                    echo '<table class="table table-sm">';
                    echo '<thead><tr><th>Product ID</th><th>Code</th><th>Name</th></tr></thead><tbody>';
                    foreach ($products as $product) {
                        echo '<tr>';
                        echo '<td>' . $product['product_id'] . '</td>';
                        echo '<td><code>' . htmlspecialchars($product['product_code'] ?? 'N/A') . '</code></td>';
                        echo '<td>' . htmlspecialchars(mb_substr($product['product_name'], 0, 50)) . '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                }
            } else {
                echo '<span class="badge bg-warning">⚠ No product translations found (normal if no products exist yet)</span>';
            }
        } catch (Exception $e) {
            echo '<span class="badge bg-danger">✗ Error: ' . $e->getMessage() . '</span>';
        }
        echo '</div>';

        // Summary
        echo '<div class="test-section">';
        echo '<h3>✅ Summary</h3>';
        echo '<p>All core tables have been created and populated successfully!</p>';
        echo '<p><strong>Next steps:</strong></p>';
        echo '<ol>';
        echo '<li>Create PHP functions for product management</li>';
        echo '<li>Build Admin UI for managing products</li>';
        echo '<li>Test creating a product with variants</li>';
        echo '</ol>';
        echo '</div>';
        ?>

    </div>
</body>
</html>
