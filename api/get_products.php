<?php
// api/get_products.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';

/**
 * Query params:
 *   q (optional)   : keyword in product name
 *   cat (optional) : productcat_id
 *   limit (int)    : default 20
 *   page (int)     : default 1
 */

$q    = isset($_GET['q'])   ? trim($_GET['q']) : '';
$cat  = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$limit= isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 20;
$page = isset($_GET['page'])  ? max(1, (int)$_GET['page'])  : 1;
$offset = ($page - 1) * $limit;

$sql = "SELECT p.product_id, p.product_name AS name, p.slug, p.formula, p.scent,
               p.product_price AS price, p.product_stock AS stock, p.product_picture AS picture
        FROM product p";
$where = [];
$params = [];

if ($cat > 0) {
  $where[] = "p.productcat_id = :cat";
  $params[':cat'] = $cat;
}
if ($q !== '') {
  $where[] = "p.product_name LIKE :q";
  $params[':q'] = '%' . $q . '%';
}
if ($where) {
  $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY p.product_id DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
  $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$rows = $stmt->fetchAll();

echo json_encode([
  'status' => 'ok',
  'count'  => count($rows),
  'items'  => $rows,
], JSON_UNESCAPED_UNICODE);