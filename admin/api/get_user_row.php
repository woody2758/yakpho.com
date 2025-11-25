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
    $img_path = __DIR__ . "/../../assets/img/profile/{$u['user_id']}/{$u['user_picture']}";
    $img_url  = ROOT_URL . "/assets/img/profile/{$u['user_id']}/{$u['user_picture']}";
    
    $roleBadge = match ($u['role']) {
        'owner', 'admin' => 'bg-danger',
        'manager' => 'bg-warning text-dark',
        'editor', 'staff' => 'bg-info text-dark',
        default => 'bg-secondary',
    };
    
    $canChangeRole = in_array($_SESSION['admin_role'], ['admin', 'owner']);
    $role_hierarchy = ['owner' => 1, 'admin' => 2, 'manager' => 3, 'editor' => 4, 'staff' => 5];
    $current_level = $role_hierarchy[$_SESSION['admin_role']] ?? 999;
    $canChangeStatus = $current_level <= 5;
    
    ob_start();
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
    <?php
    $html = ob_get_clean();
    
    echo json_encode(['success' => true, 'html' => $html]);
    
} catch (PDOException $e) {
    error_log("Get user row error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
