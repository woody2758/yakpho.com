<?php
require '../db.php';
$history = $db->query("SELECT COUNT(*) FROM fb_scrape_history")->fetchColumn();
$cities = $db->query("SELECT COUNT(*) FROM cities")->fetchColumn();
$keywords = $db->query("SELECT cfg_value FROM fb_configs WHERE cfg_key='target_keywords'")->fetchColumn();
echo "History: $history\nCities: $cities\nKeywords: " . count(explode(',', $keywords)) . "\n";
?>
