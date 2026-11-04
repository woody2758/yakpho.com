<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$userId = $_GET['id'] ?? 0;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

try {
    // Fetch user data
    $stmt = $db->prepare("SELECT * FROM user WHERE user_id = ? AND user_del = 0");
    $stmt->execute([$userId]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$u) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Generate row HTML
    $img_path = __DIR__ . "/../../uploads/profile/{$u['user_id']}/{$u['user_picture']}";
    $img_url  = ROOT_URL . "/uploads/profile/{$u['user_id']}/{$u['user_picture']}";
    
    $role_clean = trim($u['role'] ?? '', "'\"");
    $roleBadges = [
        'owner'   => 'bg-danger',
        'admin'   => 'bg-danger',
        'manager' => 'bg-warning text-dark',
        'editor'  => 'bg-info text-dark',
        'staff'   => 'bg-info text-dark',
    ];
    $roleBadge = $roleBadges[$role_clean] ?? 'bg-secondary';
    
    $canChangeRole = in_array($_SESSION['admin_role'], ['admin', 'owner']);
    $role_hierarchy = ['owner' => 1, 'admin' => 2, 'manager' => 3, 'editor' => 4, 'staff' => 5];
    $current_level = $role_hierarchy[$_SESSION['admin_role']] ?? 999;
    $canChangeStatus = $current_level <= 5;
    
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

    ob_start();
    ?>
    <tr id="user-row-<?= $u['user_id'] ?>">
        <td class="ps-4">C<?= $u['user_id'] ?></td>
        <td style="width: 50px;">
            <div class="d-flex align-items-center">
                <div class="profile-pic-wrapper" onclick="openImageManagement(<?= $u['user_id'] ?>, '<?= htmlspecialchars($u['user_name'] . ' ' . $u['user_lastname']) ?>', '<?= !empty($u['user_picture']) && file_exists($img_path) ? $img_url . '?t=' . time() : ROOT_URL . '/uploads/profile/default.png' ?>')">

                    <?php if (!empty($u['user_picture']) && file_exists($img_path)): ?>
                        <img src="<?= $img_url ?>?t=<?= time() ?>" alt="User" class="w-100 h-100" style="object-fit:cover;">
                    <?php else: ?>
                        <div class="w-100 h-100 bg-light text-secondary d-flex align-items-center justify-content-center">
                            <i data-lucide="user" style="width:16px; height:16px;"></i>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </td>
        <td style="width:180px;">
            <div class="fw-bold" style="font-size: 0.85rem;"><?= htmlspecialchars($u['user_name'] ?? '') ?> <?= htmlspecialchars($u['user_lastname'] ?? '') ?></div>
            <?php
            require_once __DIR__ . '/../../includes/functions/address.php';
            $addr_count = count_user_addresses($u['user_id']);
            ?>
            <a href="javascript:void(0)" 
               onclick="openAddressModal(<?= $u['user_id'] ?>, '<?= htmlspecialchars($u['user_name'] ?? '') ?> <?= htmlspecialchars($u['user_lastname'] ?? '') ?>')" 
               class="no-loader text-decoration-none" style="font-size: 0.7rem; color: #6c757d;">
                <i data-lucide="map-pin" style="width:10px;height:10px;"></i> 
                ที่อยู่ (<?= $addr_count ?>)
            </a>
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
            <button class="btn btn-sm btn-outline-primary me-1 py-1 px-2" onclick="editUser(<?= $u['user_id'] ?>)" title="แก้ไข">
                <i data-lucide="edit-2" style="width:12px;height:12px;"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger py-1 px-2" onclick="deleteUser(<?= $u['user_id'] ?>)" title="ลบ">
                <i data-lucide="trash-2" style="width:12px;height:12px;"></i>
            </button>
        </td>
    </tr>
    <?php
    $html = ob_get_clean();
    
    echo json_encode(['success' => true, 'html' => $html]);
    
} catch (PDOException $e) {
    error_log("Get user row error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
