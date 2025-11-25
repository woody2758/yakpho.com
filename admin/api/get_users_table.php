<?php
// Start output buffering to prevent any output before headers
ob_start();

try {
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../../includes/functions/user.php';

    // Clear any previous output
    ob_clean();
    
    header('Content-Type: application/json');

    // Get parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $search = trim($_GET['search'] ?? '');
    $role = trim($_GET['role'] ?? '');
    $sort = trim($_GET['sort'] ?? 'desc');

    $limit = 20;
    $offset = ($page - 1) * $limit;

// Fetch data
$users = get_all_users($limit, $offset, $search, $role, $sort);
$totalUsers = count_all_users($search, $role);
$totalPages = ceil($totalUsers / $limit);

// Generate table HTML
ob_start();
?>
<table class="table table-hover align-middle mb-0">
    <thead class="table-light">
        <tr>
            <th class="ps-4">
                <?php
                $currentParams = $_GET;
                $currentParams['sort'] = ($sort === 'desc') ? 'asc' : 'desc';
                $sortUrl = '?' . http_build_query($currentParams);
                ?>
                <a href="<?= $sortUrl ?>" class="text-decoration-none text-dark d-flex align-items-center gap-1 sort-link">
                    ID
                    <?php if ($sort === 'desc'): ?>
                        <i data-lucide="arrow-down" style="width:14px;height:14px;"></i>
                    <?php else: ?>
                        <i data-lucide="arrow-up" style="width:14px;height:14px;"></i>
                    <?php endif; ?>
                </a>
            </th>
            <th>รูป</th>
            <th>ชื่อ - นามสกุล</th>
            <th>ข้อมูลติดต่อ</th>
            <th>ตำแหน่ง (Role)</th>
            <th>สถานะ</th>
            <th>วันที่สมัคร</th>
            <th class="text-end pe-4" style="width: 190px;">จัดการ</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($users) > 0): ?>
            <?php foreach ($users as $u): 
                $img_path = __DIR__ . "/../../assets/img/profile/{$u['user_id']}/{$u['user_picture']}";
                $img_url  = ROOT_URL . "/assets/img/profile/{$u['user_id']}/{$u['user_picture']}";
                
                // Determine role badge color
                switch ($u['role']) {
                    case 'owner':
                    case 'admin':
                        $roleBadge = 'bg-danger';
                        break;
                    case 'manager':
                        $roleBadge = 'bg-warning text-dark';
                        break;
                    case 'editor':
                    case 'staff':
                        $roleBadge = 'bg-info text-dark';
                        break;
                    default:
                        $roleBadge = 'bg-secondary';
                }
                
                $canChangeRole = in_array($_SESSION['admin_role'], ['admin', 'owner']);
                $role_hierarchy = ['owner' => 1, 'admin' => 2, 'manager' => 3, 'editor' => 4, 'staff' => 5];
                $current_level = $role_hierarchy[$_SESSION['admin_role']] ?? 999;
                $canChangeStatus = $current_level <= 5;
            ?>
                <tr id="user-row-<?= $u['user_id'] ?>">
                    <td class="ps-4">C<?= $u['user_id'] ?></td>
                    <td style="width: 50px;">
                        <div class="d-flex align-items-center">
                            <?php if (!empty($u['user_picture']) && file_exists($img_path)): ?>
                                <img src="<?= $img_url ?>?t=<?= time() ?>" alt="User" class="rounded-circle me-2" style="width:40px; height:40px; object-fit:cover;">
                            <?php else: ?>
                                <div class="avatar-circle me-2 bg-light text-secondary d-flex align-items-center justify-content-center" style="width:40px; height:40px; border-radius:50%;">
                                    <img src="<?= ROOT_URL ?>/assets/img/profile/default.png" style="width:40px; height:40px;">
                                </div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td style="width:180px;">
                        <div class="fw-bold"><?= htmlspecialchars($u['user_name'] ?? '') ?> <?= htmlspecialchars($u['user_lastname'] ?? '') ?></div>
                    </td>
                    <td>
                        <div><?= htmlspecialchars($u['user_email']) ?></div>
                        <?php if (!empty($u['user_mobile'])): ?>
                            <small class="text-muted"><?= htmlspecialchars($u['user_mobile']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($canChangeRole && $u['user_id'] != $_SESSION['admin_id']): ?>
                            <span class="badge <?= $roleBadge ?> rounded-pill" 
                                  style="cursor: pointer;"
                                  onclick="changeRole(<?= $u['user_id'] ?>, '<?= $u['role'] ?>')"
                                  id="role-badge-<?= $u['user_id'] ?>"
                                  title="คลิกเพื่อเปลี่ยนตำแหน่ง">
                                <?= ucfirst($u['role']) ?> <i class="fas fa-pen ms-1" style="font-size: 10px;"></i>
                            </span>
                        <?php else: ?>
                            <span class="badge <?= $roleBadge ?> rounded-pill"><?= ucfirst($u['role']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($canChangeStatus && $u['user_id'] != $_SESSION['admin_id']): ?>
                            <?php if ($u['user_status'] == 1): ?>
                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill" 
                                      style="cursor: pointer;"
                                      onclick="changeStatus(<?= $u['user_id'] ?>, 1)"
                                      id="status-badge-<?= $u['user_id'] ?>"
                                      title="คลิกเพื่อเปลี่ยนสถานะ">
                                    Active <i class="fas fa-pen ms-1" style="font-size: 10px;"></i>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill" 
                                      style="cursor: pointer;"
                                      onclick="changeStatus(<?= $u['user_id'] ?>, 0)"
                                      id="status-badge-<?= $u['user_id'] ?>"
                                      title="คลิกเพื่อเปลี่ยนสถานะ">
                                    Inactive <i class="fas fa-pen ms-1" style="font-size: 10px;"></i>
                                </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if ($u['user_status'] == 1): ?>
                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill">Inactive</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <i data-lucide="plus-circle" style="width:14px; height:14px; margin-right:6px; color: #10b981;"></i>
                            <span><?= date('d/m/Y', strtotime($u['created_at'] ?? $u['user_start'] ?? 'now')) ?></span>
                        </div>
                        <?php if (!empty($u['updated_at'])): ?>
                            <div class="d-flex align-items-center text-muted small mt-1">
                                <i data-lucide="edit-3" style="width:12px; height:12px; margin-right:6px;"></i>
                                <span><?= date('d/m/Y', strtotime($u['updated_at'])) ?></span>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="text-end pe-4">
                        <button class="btn btn-sm btn-outline-primary me-1" onclick="editUser(<?= $u['user_id'] ?>)" title="แก้ไข">
                            <i data-lucide="edit-2" style="width:14px;height:14px;"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(<?= $u['user_id'] ?>)" title="ลบ">
                            <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="8" class="text-center py-5">
                    <i data-lucide="inbox" style="width:48px;height:48px;opacity:0.3;"></i>
                    <p class="mt-2 text-muted">ไม่พบข้อมูลผู้ใช้</p>
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
<?php
$tableHtml = ob_get_clean();

// Generate pagination HTML
ob_start();
echo render_pagination($page, $totalPages, "?page=%d&search=" . urlencode($search) . "&role=" . $role . "&sort=" . $sort);
$paginationHtml = ob_get_clean();

// Return JSON response
echo json_encode([
    'success' => true,
    'table' => $tableHtml,
    'pagination' => $paginationHtml,
    'total' => $totalUsers,
    'currentPage' => $page,
    'totalPages' => $totalPages
]);

} catch (Exception $e) {
    // Log error
    error_log("get_users_table.php error: " . $e->getMessage());
    
    // Return error JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
