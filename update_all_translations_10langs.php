<?php
/**
 * Update All Translation Tables for 10 Languages
 * - Adds missing ja, ru to aboutus_translation and product_translations
 * - Adds ar, he to all translation tables
 */

require_once __DIR__ . '/includes/config.php';

try {
    echo "=== Updating All Translation Tables to 10 Languages ===\n\n";
    
    $db->beginTransaction();
    
    // Define all 10 languages
    $allLanguages = ['th', 'en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru', 'ar', 'he'];
    
    // ========================================
    // 1. UPDATE ABOUTUS_TRANSLATION
    // ========================================
    
    echo "1. Updating aboutus_translation...\n";
    $aboutus = $db->query("SELECT aboutus_id FROM aboutus WHERE aboutus_del = 0")->fetchAll(PDO::FETCH_COLUMN);
    
    $aboutusAdded = 0;
    foreach ($aboutus as $id) {
        foreach ($allLanguages as $lang) {
            // Check if translation exists
            $check = $db->prepare("SELECT id FROM aboutus_translation WHERE aboutus_id = ? AND lang_code = ?");
            $check->execute([$id, $lang]);
            
            if (!$check->fetchColumn()) {
                $stmt = $db->prepare("
                    INSERT INTO aboutus_translation (aboutus_id, lang_code, title, subtitle, content)
                    VALUES (?, ?, '', '', '')
                ");
                $stmt->execute([$id, $lang]);
                $aboutusAdded++;
            }
        }
    }
    echo "   ✓ Added $aboutusAdded missing translations\n\n";
    
    // ========================================
    // 2. UPDATE BLOG_TRANSLATIONS
    // ========================================
    
    echo "2. Updating blog_translations...\n";
    $blogs = $db->query("SELECT blog_id FROM blog WHERE blog_del = 0")->fetchAll(PDO::FETCH_COLUMN);
    
    $blogAdded = 0;
    foreach ($blogs as $id) {
        foreach ($allLanguages as $lang) {
            $check = $db->prepare("SELECT id FROM blog_translations WHERE blog_id = ? AND lang_code = ?");
            $check->execute([$id, $lang]);
            
            if (!$check->fetchColumn()) {
                $stmt = $db->prepare("
                    INSERT INTO blog_translations (blog_id, lang_code, blog_name, blog_excerpt, blog_detail, blog_tag)
                    VALUES (?, ?, '', '', '', '')
                ");
                $stmt->execute([$id, $lang]);
                $blogAdded++;
            }
        }
    }
    echo "   ✓ Added $blogAdded missing translations\n\n";
    
    // ========================================
    // 3. UPDATE BLOGCAT_TRANSLATIONS
    // ========================================
    
    echo "3. Updating blogcat_translations...\n";
    $blogcats = $db->query("SELECT blogcat_id FROM blogcat WHERE blogcat_del = 0")->fetchAll(PDO::FETCH_COLUMN);
    
    $blogcatAdded = 0;
    foreach ($blogcats as $id) {
        foreach ($allLanguages as $lang) {
            $check = $db->prepare("SELECT id FROM blogcat_translations WHERE blogcat_id = ? AND lang_code = ?");
            $check->execute([$id, $lang]);
            
            if (!$check->fetchColumn()) {
                $stmt = $db->prepare("
                    INSERT INTO blogcat_translations (blogcat_id, lang_code, blogcat_name, blogcat_detail)
                    VALUES (?, ?, '', '')
                ");
                $stmt->execute([$id, $lang]);
                $blogcatAdded++;
            }
        }
    }
    echo "   ✓ Added $blogcatAdded missing translations\n\n";
    
    // ========================================
    // 4. UPDATE FAQS_TRANSLATIONS
    // ========================================
    
    echo "4. Updating faqs_translations...\n";
    $faqs = $db->query("SELECT faqs_id FROM faqs WHERE faqs_del = 0")->fetchAll(PDO::FETCH_COLUMN);
    
    $faqsAdded = 0;
    foreach ($faqs as $id) {
        foreach ($allLanguages as $lang) {
            $check = $db->prepare("SELECT id FROM faqs_translations WHERE faqs_id = ? AND lang_code = ?");
            $check->execute([$id, $lang]);
            
            if (!$check->fetchColumn()) {
                $stmt = $db->prepare("
                    INSERT INTO faqs_translations (faqs_id, lang_code, faqs_name, faqs_detail)
                    VALUES (?, ?, '', '')
                ");
                $stmt->execute([$id, $lang]);
                $faqsAdded++;
            }
        }
    }
    echo "   ✓ Added $faqsAdded missing translations\n\n";
    
    // ========================================
    // 5. UPDATE FAQSCAT_TRANSLATIONS
    // ========================================
    
    echo "5. Updating faqscat_translations...\n";
    $faqscats = $db->query("SELECT faqscat_id FROM faqscat WHERE faqscat_del = 0")->fetchAll(PDO::FETCH_COLUMN);
    
    $faqscatAdded = 0;
    foreach ($faqscats as $id) {
        foreach ($allLanguages as $lang) {
            $check = $db->prepare("SELECT id FROM faqscat_translations WHERE faqscat_id = ? AND lang_code = ?");
            $check->execute([$id, $lang]);
            
            if (!$check->fetchColumn()) {
                $stmt = $db->prepare("
                    INSERT INTO faqscat_translations (faqscat_id, lang_code, faqscat_name, faqscat_detail)
                    VALUES (?, ?, '', '')
                ");
                $stmt->execute([$id, $lang]);
                $faqscatAdded++;
            }
        }
    }
    echo "   ✓ Added $faqscatAdded missing translations\n\n";
    
    // ========================================
    // 6. UPDATE PRODUCT_TRANSLATIONS
    // ========================================
    
    echo "6. Updating product_translations...\n";
    $products = $db->query("SELECT product_id FROM product WHERE product_del = 0")->fetchAll(PDO::FETCH_COLUMN);
    
    $productAdded = 0;
    foreach ($products as $id) {
        foreach ($allLanguages as $lang) {
            $check = $db->prepare("SELECT product_id FROM product_translations WHERE product_id = ? AND lang_code = ?");
            $check->execute([$id, $lang]);
            
            if (!$check->fetchColumn()) {
                $stmt = $db->prepare("
                    INSERT INTO product_translations (product_id, lang_code, product_name, product_excerpt, product_detail, product_unit, product_youtube, product_tag, seo_title, seo_description)
                    VALUES (?, ?, '', '', '', '', '', '', '', '')
                ");
                $stmt->execute([$id, $lang]);
                $productAdded++;
            }
        }
    }
    echo "   ✓ Added $productAdded missing translations\n\n";
    
    $db->commit();
    
    // ========================================
    // SUMMARY
    // ========================================
    
    echo "=== MIGRATION SUMMARY ===\n\n";
    echo "Translations Added:\n";
    echo "  - aboutus_translation: $aboutusAdded\n";
    echo "  - blog_translations: $blogAdded\n";
    echo "  - blogcat_translations: $blogcatAdded\n";
    echo "  - faqs_translations: $faqsAdded\n";
    echo "  - faqscat_translations: $faqscatAdded\n";
    echo "  - product_translations: $productAdded\n";
    echo "  TOTAL: " . ($aboutusAdded + $blogAdded + $blogcatAdded + $faqsAdded + $faqscatAdded + $productAdded) . "\n\n";
    
    echo "✅ All translation tables updated to 10 languages!\n";
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
