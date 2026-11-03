<?php
declare(strict_types=1);

/**
 * ============================================================
 * Esther Aroma & Yak Pho — Live Production Entry Point
 * ============================================================
 */

ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

try {
    require_once __DIR__ . '/includes/config.php';
    require_once __DIR__ . '/includes/router.php';
} catch (Throwable $e) {
    error_log((string)$e);
    http_response_code(500);
    include __DIR__ . '/templates/404.php';
}
