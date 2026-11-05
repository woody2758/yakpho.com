<?php
require_once "db.php";
try {
    $db->exec("ALTER TABLE facebook_groups ADD COLUMN IF NOT EXISTS ai_summary TEXT AFTER suitability_score");
    echo "AI Summary column added successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
