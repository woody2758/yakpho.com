<?php
$ch = curl_init("http://localhost/yakpho.com/staff/api/get_groups_to_post.php?api_key=yakpho2026");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
echo "Raw response:\n";
var_dump($res);
echo "\nJSON decode error:\n";
var_dump(json_last_error_msg());
$decoded = json_decode($res, true);
var_dump($decoded);
curl_close($ch);
