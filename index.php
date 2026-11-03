<?php
declare(strict_types=1);

/**
 * ============================================================
 * Esther Aroma & Yak Pho — Live Production Entry Point
 * ============================================================
 */

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/router.php';
