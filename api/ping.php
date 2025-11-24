<?php
// api/ping.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';

try {
  $stmt = $pdo->query("SELECT 1 as ok");
  $row = $stmt->fetch();
  echo json_encode([
    'status' => 'ok',
    'db' => (int)$row['ok'],
    'time' => date('c'),
  ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['status' => 'error'], JSON_UNESCAPED_UNICODE);
}