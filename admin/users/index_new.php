<?php
$page_title = "จัดการสมาชิก";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../../includes/functions/user.php";
require_once __DIR__ . "/../../includes/functions/pagination.php";

// Pagination & Filter
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit  = 20;
$offset = ($page - 1) * $limit;
$search = trim($_GET['search'] ?? '');
$role   = trim($_GET['role'] ?? '');

// Query
$users      = get_all_users($limit, $offset, $search, $role);
$totalUsers = count_all_users($search, $role);
$totalPages = ceil($totalUsers / $limit);

ob_start();
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i class="fas fa-users me-2"></i>จัดการสมาชิก</h2>
        <button onclick="addCustomer()" class="btn btn-success">
            <i data-lucide="user-plus" style="width:16px; height:16px; margin-right:6px;"></i> เพิ่มลูกค้าใหม่
        </button>
    </div>

    <!-- Filter Card -->