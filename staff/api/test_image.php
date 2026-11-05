<?php
$url = "https://yakpho.com/staff/uploads/creative/1781068794_6427.jpg";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$res = curl_exec($ch);
var_dump(substr($res, 0, 500));
