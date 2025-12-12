<?php
/**
 * Check Blog and FAQs Database Structure
 */

require_once __DIR__ . '/includes/config.php';

echo "=== BLOG TABLES ===\n\n";

// Check blog table
$stmt = $db->query("DESCRIBE blog");
echo "Blog Table Structure:\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("  %-30s %-20s %s\n", $row['Field'], $row['Type'], $row['Null']);
}

echo "\n";

// Check blogcat table
$stmt = $db->query("DESCRIBE blogcat");
echo "BlogCat Table Structure:\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("  %-30s %-20s %s\n", $row['Field'], $row['Type'], $row['Null']);
}

echo "\n=== FAQS TABLES ===\n\n";

// Check faqs table
$stmt = $db->query("DESCRIBE faqs");
echo "FAQs Table Structure:\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("  %-30s %-20s %s\n", $row['Field'], $row['Type'], $row['Null']);
}

echo "\n";

// Check faqscat table
$stmt = $db->query("DESCRIBE faqscat");
echo "FAQsCat Table Structure:\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("  %-30s %-20s %s\n", $row['Field'], $row['Type'], $row['Null']);
}

// Count records
echo "\n=== RECORD COUNTS ===\n\n";
echo "Blog: " . $db->query("SELECT COUNT(*) FROM blog")->fetchColumn() . " records\n";
echo "BlogCat: " . $db->query("SELECT COUNT(*) FROM blogcat")->fetchColumn() . " records\n";
echo "FAQs: " . $db->query("SELECT COUNT(*) FROM faqs")->fetchColumn() . " records\n";
echo "FAQsCat: " . $db->query("SELECT COUNT(*) FROM faqscat")->fetchColumn() . " records\n";
