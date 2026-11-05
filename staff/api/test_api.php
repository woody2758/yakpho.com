<?php
$ch = curl_init("http://localhost/yakpho.com/staff/api/get_groups_to_post.php?api_key=yakpho2026");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
echo "RESPONSE:\n" . $res . "\n";
echo "ERROR:\n" . curl_error($ch) . "\n";
curl_close($ch);
