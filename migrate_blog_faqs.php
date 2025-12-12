<?php
/**
 * Blog & FAQs Multi-Language Migration Script
 * Creates translation tables and migrates existing data
 */

require_once __DIR__ . '/includes/config.php';

try {
    echo "=== BLOG & FAQS MULTI-LANGUAGE MIGRATION ===\n\n";
    
    $db->beginTransaction();
    
    // ========================================
    // 1. CREATE TRANSLATION TABLES
    // ========================================
    
    echo "Creating translation tables...\n\n";
    
    // Blog translations
    $db->exec("
        CREATE TABLE IF NOT EXISTS blog_translations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            blog_id INT NOT NULL,
            lang_code VARCHAR(2) NOT NULL,
            blog_name TEXT,
            blog_excerpt TEXT,
            blog_detail LONGTEXT,
            blog_tag VARCHAR(85),
            UNIQUE KEY unique_blog_lang (blog_id, lang_code),
            INDEX idx_blog_id (blog_id),
            INDEX idx_lang_code (lang_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ blog_translations table created\n";
    
    // Blog category translations
    $db->exec("
        CREATE TABLE IF NOT EXISTS blogcat_translations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            blogcat_id TINYINT NOT NULL,
            lang_code VARCHAR(2) NOT NULL,
            blogcat_name VARCHAR(255),
            blogcat_detail TEXT,
            UNIQUE KEY unique_blogcat_lang (blogcat_id, lang_code),
            INDEX idx_blogcat_id (blogcat_id),
            INDEX idx_lang_code (lang_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ blogcat_translations table created\n";
    
    // FAQs translations
   $db->exec("
        CREATE TABLE IF NOT EXISTS faqs_translations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            faqs_id INT NOT NULL,
            lang_code VARCHAR(2) NOT NULL,
            faqs_name TEXT,
            faqs_detail TEXT,
            UNIQUE KEY unique_faqs_lang (faqs_id, lang_code),
            INDEX idx_faqs_id (faqs_id),
            INDEX idx_lang_code (lang_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ faqs_translations table created\n";
    
    // FAQ category translations
    $db->exec("
        CREATE TABLE IF NOT EXISTS faqscat_translations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            faqscat_id INT NOT NULL,
            lang_code VARCHAR(2) NOT NULL,
            faqscat_name VARCHAR(255),
            faqscat_detail TEXT,
            UNIQUE KEY unique_faqscat_lang (faqscat_id, lang_code),
            INDEX idx_faqscat_id (faqscat_id),
            INDEX idx_lang_code (lang_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ faqscat_translations table created\n\n";
    
    // ========================================
    // 2. MIGRATE BLOG DATA
    // ========================================
    
    echo "Migrating blog data...\n";
    
    $blogs = $db->query("SELECT * FROM blog WHERE blog_del = 0")->fetchAll(PDO::FETCH_ASSOC);
    $blogCount = 0;
    
    foreach ($blogs as $blog) {
        // Determine source language (default to 'th' if blog_lang is empty or invalid)
        $sourceLang = !empty($blog['blog_lang']) && strlen($blog['blog_lang']) === 1 
            ? 'th'  // Assume 'th' for now
            : 'th';
        
        // Insert translation for source language
        $stmt = $db->prepare("
            INSERT IGNORE INTO blog_translations (blog_id, lang_code, blog_name, blog_excerpt, blog_detail, blog_tag)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $blog['blog_id'],
            $sourceLang,
            $blog['blog_name'],
            $blog['blog_excerpt'],
            $blog['blog_detail'],
            $blog['blog_tag']
        ]);
        
        // Create empty translations for other languages
        $languages = ['en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru'];
        foreach ($languages as $lang) {
            $stmt = $db->prepare("
                INSERT IGNORE INTO blog_translations (blog_id, lang_code, blog_name, blog_excerpt, blog_detail, blog_tag)
                VALUES (?, ?, '', '', '', '')
            ");
            $stmt->execute([$blog['blog_id'], $lang]);
        }
        
        $blogCount++;
    }
    echo "✓ Migrated $blogCount blog posts\n\n";
    
    // ========================================
    // 3. MIGRATE BLOG CATEGORIES
    // ========================================
    
    echo "Migrating blog categories...\n";
    
    $blogcats = $db->query("SELECT * FROM blogcat WHERE blogcat_del = 0")->fetchAll(PDO::FETCH_ASSOC);
    $blogcatCount = 0;
    
    foreach ($blogcats as $cat) {
        // Insert Thai translation
        $stmt = $db->prepare("
            INSERT IGNORE INTO blogcat_translations (blogcat_id, lang_code, blogcat_name, blogcat_detail)
            VALUES (?, 'th', ?, ?)
        ");
        $stmt->execute([
            $cat['blogcat_id'],
            $cat['blogcat_name'],
            $cat['blogcat_detail']
        ]);
        
        // Create empty translations for other languages
        $languages = ['en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru'];
        foreach ($languages as $lang) {
            $stmt = $db->prepare("
                INSERT IGNORE INTO blogcat_translations (blogcat_id, lang_code, blogcat_name, blogcat_detail)
                VALUES (?, ?, '', '')
            ");
            $stmt->execute([$cat['blogcat_id'], $lang]);
        }
        
        $blogcatCount++;
    }
    echo "✓ Migrated $blogcatCount blog categories\n\n";
    
    // ========================================
    // 4. MIGRATE FAQS
    // ========================================
    
    echo "Migrating FAQs...\n";
    
    $faqs = $db->query("SELECT * FROM faqs WHERE faqs_del = 0")->fetchAll(PDO::FETCH_ASSOC);
    $faqsCount = 0;
    
    foreach ($faqs as $faq) {
        // Insert Thai translation (assume existing data is Thai)
        $stmt = $db->prepare("
            INSERT IGNORE INTO faqs_translations (faqs_id, lang_code, faqs_name, faqs_detail)
            VALUES (?, 'th', ?, ?)
        ");
        $stmt->execute([
            $faq['faqs_id'],
            $faq['faqs_name'],
            $faq['faqs_detail']
        ]);
        
        // Create empty translations for other languages
        $languages = ['en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru'];
        foreach ($languages as $lang) {
            $stmt = $db->prepare("
                INSERT IGNORE INTO faqs_translations (faqs_id, lang_code, faqs_name, faqs_detail)
                VALUES (?, ?, '', '')
            ");
            $stmt->execute([$faq['faqs_id'], $lang]);
        }
        
        $faqsCount++;
    }
    echo "✓ Migrated $faqsCount FAQs\n\n";
    
    // ========================================
    // 5. MIGRATE FAQ CATEGORIES
    // ========================================
    
    echo "Migrating FAQ categories...\n";
    
    $faqscats = $db->query("SELECT * FROM faqscat WHERE faqscat_del = 0")->fetchAll(PDO::FETCH_ASSOC);
    $faqscatCount = 0;
    
    foreach ($faqscats as $cat) {
        // Insert Thai translation
        $stmt = $db->prepare("
            INSERT IGNORE INTO faqscat_translations (faqscat_id, lang_code, faqscat_name, faqscat_detail)
            VALUES (?, 'th', ?, ?)
        ");
        $stmt->execute([
            $cat['faqscat_id'],
            $cat['faqscat_name'],
            $cat['faqscat_detail']
        ]);
        
        // Create empty translations for other languages
        $languages = ['en', 'de', 'fr', 'zh', 'ko', 'ja', 'ru'];
        foreach ($languages as $lang) {
            $stmt = $db->prepare("
                INSERT IGNORE INTO faqscat_translations (faqscat_id, lang_code, faqscat_name, faqscat_detail)
                VALUES (?, ?, '', '')
            ");
            $stmt->execute([$cat['faqscat_id'], $lang]);
        }
        
        $faqscatCount++;
    }
    echo "✓ Migrated $faqscatCount FAQ categories\n\n";
    
    $db->commit();
    
    // ========================================
    // SUMMARY
    // ========================================
    
    echo "=== MIGRATION SUMMARY ===\n\n";
    echo "Tables Created: 4\n";
    echo "  - blog_translations\n";
    echo "  - blogcat_translations\n";
    echo "  - faqs_translations\n";
    echo "  - faqscat_translations\n\n";
    
    echo "Data Migrated:\n";
    echo "  - Blog posts: $blogCount\n";
    echo "  - Blog categories: $blogcatCount\n";
    echo "  - FAQs: $faqsCount\n";
    echo "  - FAQ categories: $faqscatCount\n\n";
    
    echo "Languages per record: 8 (th, en, de, fr, zh, ko, ja, ru)\n\n";
    
    echo "✅ Migration completed successfully!\n";
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
