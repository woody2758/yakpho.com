<?php
// Start output buffering to prevent any output before headers
ob_start();

try {
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../../includes/functions/user.php';
    require_once __DIR__ . '/../../includes/functions/pagination.php';
    require_once __DIR__ . '/../../includes/functions/address.php';

    // Get parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $search = trim($_GET['search'] ?? '');
    $role = trim($_GET['role'] ?? '');
    $tag = trim($_GET['tag'] ?? '');
    $tier = trim($_GET['tier'] ?? '');
    $sort = trim($_GET['sort'] ?? 'desc');

    $limit = 15;
    $offset = ($page - 1) * $limit;

    // Check if tables exist, if not create them (Auto-Fix)
    $db->exec("CREATE TABLE IF NOT EXISTS user_tags (
        tag_id INT AUTO_INCREMENT PRIMARY KEY,
        tag_name VARCHAR(100) NOT NULL UNIQUE,
        tag_color VARCHAR(20) DEFAULT '#6366f1',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $db->exec("CREATE TABLE IF NOT EXISTS user_tag_mapping (
        mapping_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        tag_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY user_tag_unique (user_id, tag_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Auto-fix empty roles to customer
    $db->exec("UPDATE user SET role = 'customer' WHERE role = '' OR role IS NULL");
    
    // Auto-fix: Add user_stars column
    try {
        $db->query("SELECT user_stars FROM user LIMIT 1");
    } catch (Exception $e) {
        $db->exec("ALTER TABLE user ADD COLUMN user_stars INT DEFAULT 0 AFTER user_lastname");
    }

    $users = get_all_users($limit, $offset, $search, $role, $sort, $tag, $tier);
    $totalUsers = count_all_users($search, $role, $tag, $tier);
    $totalPages = ceil($totalUsers / $limit);

// Generate table HTML
ob_start();
?>
<style>
    .force-responsive {
        width: 100% !important;
        overflow-x: auto !important;
        display: block !important;
        -webkit-overflow-scrolling: touch;
    }
    .profile-pic-wrapper {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        overflow: hidden;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        cursor: pointer;
        transition: transform 0.2s;
    }
    .profile-pic-wrapper:hover {
        transform: scale(1.1);
    }
    .min-h-30 { min-height: 30px; }
    
    /* Emerald Tag Style */
    .tag-badge {
        background-color: #065f46;
        color: white;
        font-size: 0.65rem;
        padding: 2px 8px;
        border-radius: 4px;
        margin-right: 4px;
        margin-bottom: 4px;
        display: inline-flex;
        align-items: center;
        cursor: pointer;
        transition: all 0.2s;
        border: 1px solid transparent;
    }
    .tag-badge:hover {
        background-color: #047857;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .btn-manage-tags {
        color: #065f46;
        cursor: pointer;
        padding: 2px 5px;
        border-radius: 4px;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        background: rgba(6, 95, 70, 0.05);
    }
    .btn-manage-tags:hover {
        background: rgba(6, 95, 70, 0.15);
        color: #047857;
    }
</style>

<div class="table-responsive force-responsive">
    <table class="table table-hover align-middle mb-0" id="users-list-table">
    <thead class="table-light" style="font-size: 0.75rem;">
        <tr>
            <th class="ps-4" style="width: 80px;">
                <?php
                $currentParams = $_GET;
                $currentParams['sort'] = ($sort === 'desc') ? 'asc' : 'desc';
                $sortUrl = '?' . http_build_query($currentParams);
                ?>
                <a href="<?= $sortUrl ?>" class="text-decoration-none text-dark d-flex align-items-center no-loader">
                    ID
                    <i data-lucide="chevrons-up-down" class="ms-1" style="width:12px;height:12px;"></i>
                </a>
            </th>
            <th colspan="2">ชื่อ - นามสกุล</th>
            <th>ข้อมูลติดต่อ</th>
            <th>อีเมล</th>
            <th>Tags (กลุ่มลูกค้า)</th>
            <th>ตำแหน่ง (Role)</th>
            <th>สถานะ</th>
            <th>วันที่สมัคร</th>
            <th class="text-end pe-4">จัดการ</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($users) > 0): ?>
            <?php foreach ($users as $u): 
                $userId = (int)$u['user_id'];
                $userPic = $u['user_picture'] ?? '';
                $img_path = __DIR__ . "/../../uploads/profile/{$userId}/{$userPic}";
                $img_url  = ROOT_URL . "/uploads/profile/{$userId}/{$userPic}";
                
                // Fallback check if stored directly in uploads/profile/
                if (!file_exists($img_path) && !empty($userPic) && file_exists(__DIR__ . "/../../uploads/profile/{$userPic}")) {
                    $img_path = __DIR__ . "/../../uploads/profile/{$userPic}";
                    $img_url  = ROOT_URL . "/uploads/profile/{$userPic}";
                }

                $hasImage = !empty($userPic) && file_exists($img_path);
                $avatarUrl = $hasImage ? $img_url . '?t=' . time() : (!empty($u['line_picture_url']) ? $u['line_picture_url'] : ROOT_URL . '/uploads/profile/default.png');
                $showAvatar = $hasImage || !empty($u['line_picture_url']);
                $fullname = trim(($u['user_name'] ?? '') . ' ' . ($u['user_lastname'] ?? ''));
                if (empty($fullname)) {
                    $fullname = !empty($u['user_mobile']) ? $u['user_mobile'] : (!empty($u['user_email']) ? $u['user_email'] : '- ไม่ระบุชื่อ -');
                }
                
                // Fetch tags for this user
                $tagStmt = $db->prepare("
                    SELECT t.tag_name 
                    FROM user_tags t
                    JOIN user_tag_mapping m ON t.tag_id = m.tag_id
                    WHERE m.user_id = ?
                    ORDER BY t.tag_name ASC
                ");
                $tagStmt->execute([$u['user_id']]);
                $userTags = $tagStmt->fetchAll(PDO::FETCH_COLUMN);

                // Determine role badge color
                $role_clean = trim($u['role'] ?? '', "'\"");
                switch ($role_clean) {
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
                    <td class="ps-4">
                        <div class="fw-bold text-dark">C<?= $u['user_id'] ?></div>
                        <?php
                        $order_count_stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND orders_del = 0");
                        $order_count_stmt->execute([$u['user_id']]);
                        $order_count = $order_count_stmt->fetchColumn();
                        if ($order_count > 0):
                        ?>
                        <div class="text-danger mt-1" style="font-size: 0.75rem; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;" title="จำนวนการสั่งซื้อทั้งหมดคลิกเพื่อดู" onclick="window.location.href='<?= ADMIN_URL ?>/orders/?search=<?= urlencode('C'.$u['user_id']) ?>&status=all'">
                            <i data-lucide="heart" style="width:12px;height:12px;fill:#dc3545;"></i> <?= number_format($order_count) ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td style="width: 50px;">
                        <div class="d-flex align-items-center">
                            <div class="profile-pic-wrapper" onclick="openImageManagement(<?= $u['user_id'] ?>, '<?= htmlspecialchars(addslashes($fullname)) ?>', '<?= $avatarUrl ?>')">
                                <?php if ($showAvatar): ?>
                                    <img src="<?= $avatarUrl ?>" alt="User" class="w-100 h-100" style="object-fit:cover;">
                                <?php else: ?>
                                    <div class="w-100 h-100 bg-light text-secondary d-flex align-items-center justify-content-center">
                                        <i data-lucide="user" style="width:16px; height:16px;"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td style="width:180px;">
                        <div class="d-flex align-items-center gap-2">
                            <div class="fw-medium text-dark customer-name" onclick="openEditCustomerModal(<?= $u['user_id'] ?>)" style="cursor: pointer;"><?= $fullname ?></div>
                            <!-- 5-Star Rating System -->
                            <?php $user_stars = isset($u['user_stars']) ? (int)$u['user_stars'] : 0; ?>
                            <div class="user-stars-container d-flex align-items-center" id="stars-container-users-<?= $u['user_id'] ?>">
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" 
                                         fill="<?= $i <= $user_stars ? '#ffc107' : 'none' ?>" 
                                         stroke="<?= $i <= $user_stars ? '#ffc107' : '#adb5bd' ?>" 
                                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                                         class="cursor-pointer star-icon" 
                                         onclick="updateUserStars(<?= $u['user_id'] ?>, <?= $i ?>)"
                                         style="transition: transform 0.1s; margin: 0 1px;">
                                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                    </svg>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php
                        $addr_count = count_user_addresses($u['user_id']);
                        $tierClass = match($u['member_tier'] ?? 'general') {
                            'vip_spa'           => 'badge-tier-vip',
                            'therapist_partner' => 'badge-tier-partner',
                            'oem_export'        => 'badge-tier-oem',
                            default             => 'badge-tier-general',
                        };
                        $tierLabel = match($u['member_tier'] ?? 'general') {
                            'vip_spa'           => '👑 VIP Spa',
                            'therapist_partner' => '⭐ Partner',
                            'oem_export'        => '💎 OEM',
                            default             => '🌿 General',
                        };
                        $userPoints = (int)($u['m_stamp_points'] ?? 0);
                        ?>
                        <div class="d-flex align-items-center flex-wrap gap-1 mt-1">
                            <a href="javascript:void(0)" 
                               onclick="openAddressModal(<?= $u['user_id'] ?>, '<?= htmlspecialchars($u['user_name'] ?? '') ?> <?= htmlspecialchars($u['user_lastname'] ?? '') ?>')" 
                               class="no-loader text-decoration-none" style="font-size: 0.7rem; color: #6c757d;">
                                <i data-lucide="map-pin" style="width:10px;height:10px;"></i> 
                                ที่อยู่ (<?= $addr_count ?>)
                            </a>
                            <span class="badge rounded-pill <?= $tierClass ?> user-tier-badge-<?= $u['user_id'] ?>" title="ระดับสมาชิก"><?= $tierLabel ?></span>
                            <span class="badge rounded-pill bg-gold-subtle text-dark user-points-badge-<?= $u['user_id'] ?>" title="แต้มสะสม M-Stamp">🪙 <?= number_format($userPoints) ?> แต้ม</span>
                            <?php if (!empty($u['line_user_id'])): ?>
                            <span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success" title="LINE: <?= htmlspecialchars($u['line_display_name'] ?? 'เชื่อมต่อแล้ว') ?>" style="font-size: 0.62rem;">
                                <i data-lucide="message-circle" style="width:9px;height:9px;"></i> LINE
                            </span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <?php if (!empty($u['user_mobile'])): ?>
                            <div style="font-size: 0.8rem;"><?= htmlspecialchars($u['user_mobile']) ?></div>
                        <?php else: ?>
                            <span class="text-muted small">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="text-primary" style="font-size: 0.8rem;"><?= htmlspecialchars($u['user_email'] ?: '-') ?></div>
                    </td>
                    <td>
                        <div id="user-tags-<?= $u['user_id'] ?>" class="d-flex flex-wrap align-items-center min-h-30">
                            <?php foreach ($userTags as $tagName): ?>
                                <span class="tag-badge" onclick="filterByTag('<?= addslashes($tagName) ?>')">
                                    <?= htmlspecialchars($tagName) ?>
                                </span>
                            <?php endforeach; ?>
                            <div class="btn-manage-tags" onclick="promptAddTag(<?= $u['user_id'] ?>)" title="จัดการ Tag">
                                <i data-lucide="edit-2" style="width:12px;height:12px;"></i>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php if ($canChangeRole && $u['user_id'] != $_SESSION['admin_id']): ?>
                            <span class="badge <?= $roleBadge ?> rounded-pill" 
                                  style="cursor: pointer; font-size: 0.65rem;"
                                  onclick="changeRole(<?= $u['user_id'] ?>, '<?= addslashes($role_clean) ?>')"
                                  id="role-badge-<?= $u['user_id'] ?>"
                                  title="คลิกเพื่อเปลี่ยนตำแหน่ง">
                                <?= ucfirst($role_clean) ?> <i class="fas fa-pen ms-1" style="font-size: 8px;"></i>
                            </span>
                        <?php else: ?>
                            <span class="badge <?= $roleBadge ?> rounded-pill" style="font-size: 0.65rem;"><?= ucfirst($role_clean) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($canChangeStatus && $u['user_id'] != $_SESSION['admin_id']): ?>
                            <?php if ($u['user_status'] == 1): ?>
                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill" 
                                      style="cursor: pointer; font-size: 0.65rem;"
                                      onclick="changeStatus(<?= $u['user_id'] ?>, 1)"
                                      id="status-badge-<?= $u['user_id'] ?>"
                                      title="คลิกเพื่อเปลี่ยนสถานะ">
                                    Active <i class="fas fa-pen ms-1" style="font-size: 8px;"></i>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill" 
                                      style="cursor: pointer; font-size: 0.65rem;"
                                      onclick="changeStatus(<?= $u['user_id'] ?>, 0)"
                                      id="status-badge-<?= $u['user_id'] ?>"
                                      title="คลิกเพื่อเปลี่ยนสถานะ">
                                    Inactive <i class="fas fa-pen ms-1" style="font-size: 8px;"></i>
                                </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if ($u['user_status'] == 1): ?>
                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill" style="font-size: 0.65rem;">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill" style="font-size: 0.65rem;">Inactive</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex align-items-center" style="font-size: 0.85rem;">
                            <i data-lucide="plus-circle" style="width:14px; height:14px; margin-right:6px; color: #10b981;"></i>
                            <span><?= date('d/m/Y', strtotime($u['created_at'] ?? $u['user_start'] ?? 'now')) ?></span>
                        </div>
                        <?php if (!empty($u['updated_at'])): ?>
                            <div class="d-flex align-items-center text-muted small mt-1" style="font-size: 0.75rem;">
                                <i data-lucide="edit-3" style="width:12px; height:12px; margin-right:6px;"></i>
                                <span><?= date('d/m/Y', strtotime($u['updated_at'])) ?></span>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="text-end pe-4" style="white-space: nowrap;">
                        <button class="btn-action-icon btn-action-tier me-1" onclick="openAdjustTierModal(<?= $u['user_id'] ?>, '<?= htmlspecialchars(addslashes($fullname)) ?>', '<?= htmlspecialchars($u['member_tier'] ?? 'general') ?>', <?= $userPoints ?>, <?= !empty($u['line_user_id']) ? 'true' : 'false' ?>)" title="ปรับระดับสมาชิก & แต้ม M-Stamp">
                            <i data-lucide="award"></i>
                        </button>
                        <button class="btn-action-icon btn-action-vip me-1" onclick="copyVipInvite(<?= $u['user_id'] ?>, this)" title="คัดลอกลิงก์เชิญ VIP (30 วัน)">
                            <i data-lucide="crown"></i>
                        </button>
                        <button class="btn-action-icon btn-action-key me-1" onclick="openResetPasswordModal(<?= $u['user_id'] ?>, '<?= htmlspecialchars(addslashes($fullname)) ?>')" title="รีเซ็ตรหัสผ่าน">
                            <i data-lucide="key"></i>
                        </button>
                        <button class="btn-action-icon btn-action-edit me-1" onclick="editUser(<?= $u['user_id'] ?>)" title="แก้ไขข้อมูลผู้ใช้">
                            <i data-lucide="edit-2"></i>
                        </button>
                        <button class="btn-action-icon btn-action-delete" onclick="deleteUser(<?= $u['user_id'] ?>)" title="ลบผู้ใช้">
                            <i data-lucide="trash-2"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="10" class="text-center py-5">
                    <i data-lucide="inbox" style="width:48px;height:48px;opacity:0.3;"></i>
                    <p class="mt-2 text-muted">ไม่พบข้อมูลผู้ใช้</p>
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
    </table>
</div>
<?php
$tableHtml = ob_get_clean();

// Generate pagination HTML
ob_start();
echo render_pagination($page, $totalPages, "?page=%d&search=" . urlencode($search) . "&role=" . $role . "&tier=" . urlencode($tier) . "&sort=" . $sort . "&tag=" . urlencode($tag));
$paginationHtml = ob_get_clean();

header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'table' => $tableHtml,
    'pagination' => $paginationHtml,
    'total' => (int)$totalUsers
]);

} catch (Exception $e) {
    error_log("get_users_table.php error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
