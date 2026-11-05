<?php
require_once "db.php";

try {
    // Add campaign_tag to creative_contents
    $db->exec("ALTER TABLE creative_contents ADD COLUMN campaign_tag VARCHAR(100) NULL DEFAULT NULL AFTER ai_strategy");
    echo "Added campaign_tag to creative_contents\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column campaign_tag already exists in creative_contents\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

try {
    // Add campaign_tag to creative_post_queue
    $db->exec("ALTER TABLE creative_post_queue ADD COLUMN campaign_tag VARCHAR(100) NULL DEFAULT NULL AFTER status");
    echo "Added campaign_tag to creative_post_queue\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column campaign_tag already exists in creative_post_queue\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
echo "Done.";
?>
