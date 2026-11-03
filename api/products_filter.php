<?php
declare(strict_types=1);

/**
 * ============================================================
 * YakPho & Esther Aroma — Async Product Filter & Search JSON API
 * ============================================================
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/frontend_data.php';

try {
    $lang = trim((string)($_GET['lang'] ?? ($_SESSION['lang'] ?? 'th')));
    $filters = [
        'maincat_id' => $_GET['maincat_id'] ?? '',
        'cat_id'     => $_GET['cat_id'] ?? '',
        'formula'    => $_GET['formula'] ?? '',
        'size'       => $_GET['size'] ?? '',
        'q'          => $_GET['q'] ?? '',
        'sort'       => $_GET['sort'] ?? 'recommended',
        'page'       => (int)($_GET['page'] ?? 1),
        'limit'      => (int)($_GET['limit'] ?? 12),
    ];

    $result = get_catalog_products($db, $filters, $lang);

    echo json_encode([
        'status' => true,
        'data' => $result,
        'message' => 'OK',
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'data' => null,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

