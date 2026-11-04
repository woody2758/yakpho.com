<?php
// 301 Moved Permanently to modern products catalog
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocalSub = (strpos($_SERVER['REQUEST_URI'] ?? '', '/yakpho.com') === 0);
$prefix = $isLocalSub ? '/yakpho.com' : '';

header("HTTP/1.1 301 Moved Permanently");
header("Location: " . $prefix . "/th/products");
exit;
