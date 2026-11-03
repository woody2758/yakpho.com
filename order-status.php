<?php
/**
 * Order Status Tracking Gateway
 * Unifies order tracking into track.php
 */
$orderNo = trim($_GET['no'] ?? $_GET['v'] ?? '');
if (!empty($orderNo)) {
    $_GET['v'] = $orderNo;
}
require_once __DIR__ . "/track.php";
