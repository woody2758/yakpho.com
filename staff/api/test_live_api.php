<?php
$ch = curl_init("https://yakpho.com/staff/api/get_groups_to_post.php?api_key=yakpho2026");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
echo "Raw response from live:\n";
var_dump($res);
$decoded = json_decode($res, true);
echo "\nJSON decoded:\n";
var_dump($decoded);
curl_close($ch);
