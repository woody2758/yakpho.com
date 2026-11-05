<?php
$ch = curl_init('https://yakpho.com/staff/api/update_campaign_posted.php?api_key=yakpho2026');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['queue_id' => 999999, 'status' => 'success']));
$response = curl_exec($ch);
echo "Response: " . $response . "\n";
?>
