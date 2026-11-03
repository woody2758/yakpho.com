<?php
// ============================================================
// 🌿 YakPho Aroma – Global Path Configuration
// ============================================================

// Detect root URL dynamically (รองรับ XAMPP + .htaccess + Subfolder)
if (!defined('URL_PATH')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
                 || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

    // กรณีอยู่ใน subfolder เช่น /yakpho/ หรือ /mockup/
    $rootPath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    if ($rootPath === '' || $rootPath === '/') {
        $rootPath = '';
    }

    define('URL_PATH', $protocol . $_SERVER['HTTP_HOST'] . $rootPath . '/');
}

// ตัวอย่างการใช้งาน:
// <link rel="stylesheet" href="...URL_PATH...">

// API URL for Esther REST (ดึงจาก Server จริงตามที่คุณแจ้งไว้)
if (!defined('ESTHER_API_URL')) {
    define('ESTHER_API_URL', 'https://esther.rest/api_external');
}
