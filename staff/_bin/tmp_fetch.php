<?php
$ch = curl_init('http://localhost/yakpho.com/staff/post_world_groups.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$html = curl_exec($ch);
file_put_contents('d:/xampp/htdocs/yakpho.com/staff/rendered.html', $html);
