<?php
require_once "db.php";
$groups = $db->query("SELECT id, campaign_tag FROM facebook_groups WHERE campaign_tag LIKE '%cp%'")->fetchAll(PDO::FETCH_ASSOC);

print_r($groups);
