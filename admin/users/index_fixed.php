<?php
$page_title = "เธเธฑเธ”เธเธฒเธฃเธชเธกเธฒเธเธดเธ";
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
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="ค้นหาชื่อ, Email, Username" value="<?= htmlspecialchars($search) ?>">
                </div>


                <div class="col-md-3">
                    <select name="role" id="roleFilter" class="form-select">
                        <option value="">-- เธ—เธธเธเธ•เธณเนเธซเธเนเธ --</option>
                        <option value="customer" <?= $role == 'customer' ? 'selected' : '' ?>>Customer</option>
                        <option value="staff" <?= $role == 'staff' ? 'selected' : '' ?>>Staff</option>
                        <option value="editor" <?= $role == 'editor' ? 'selected' : '' ?>>Editor</option>
                        <option value="manager" <?= $role == 'manager' ? 'selected' : '' ?>>Manager</option>
                        <option value="admin" <?= $role == 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="owner" <?= $role == 'owner' ? 'selected' : '' ?>>Owner</option>
                    </select>
                </div>
                <div class="col-md-<?= (!empty($search) || !empty($role)) ? '3' : '4' ?>">
                    <button type="submit" class="btn btn-secondary w-100 d-flex align-items-center justify-content-center">
                        <i data-lucide="search" style="width:16px; height:16px; margin-right:6px;"></i>
                        <span>เธเนเธเธซเธฒ</span>
                    </button>
                </div>
                <?php if (!empty($search) || !empty($role)): ?>
                <div class="col-md-1">
                    <a href="./" class="btn btn-sm btn-outline-secondary w-100 d-flex align-items-center justify-content-center" title="เธฅเนเธฒเธเธเนเธฒ">
                        <i data-lucide="x" style="width:16px; height:16px;"></i>
                    </a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>เธฃเธนเธ</th>
                            <th>เธเธทเนเธญ - เธเธฒเธกเธชเธเธธเธฅ</th>
                            <th>เธเนเธญเธกเธนเธฅเธ•เธดเธ”เธ•เนเธญ</th>
                            <th>เธ•เธณเนเธซเธเนเธ (Role)</th>
                            <th>เธชเธ–เธฒเธเธฐ</th>
                            <th>เธงเธฑเธเธ—เธตเนเธชเธกเธฑเธเธฃ</th>
                            <th class="text-end pe-4" style="width: 190px;">เธเธฑเธ”เธเธฒเธฃ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($users) > 0): ?>
                            <?php foreach ($users as $u): ?>
                                <tr id="user-row-<?= $u['user_id'] ?>">
                                    <td class="ps-4">C<?= $u['user_id'] ?></td>
                                    <td style="width: 50px;">
                                        <div class="d-flex align-items-center">
                                            <?php
                                            $img_path = "../../assets/img/profile/{$u['user_id']}/{$u['user_picture']}";
                                            $img_url  = ROOT_URL . "/assets/img/profile/{$u['user_id']}/{$u['user_picture']}";
                                            
                                            if (!empty($u['user_picture']) && file_exists($img_path)) {
                                                ?>
                                                <img src="<?= $img_url ?>" alt="User" class="rounded-circle me-2" style="width:40px; height:40px; object-fit:cover;">
                                                <?php
                                            } else {
                                                ?>
                                                <div class="avatar-circle me-2 bg-light text-secondary d-flex align-items-center justify-content-center" style="width:40px; height:40px; border-radius:50%;">
                                                    <img src="../../assets/img/profile/default.png" style="width:40px; height:40px;">
                                                </div>
                                                <?php
                                            }
                                            ?>
                                    </td>
                                    <td style="width:180px;">
                                            <div class="fw-bold"><?= htmlspecialchars($u['user_name'] ?? '') ?> <?= htmlspecialchars($u['user_lastname'] ?? '') ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($u['user_email']) ?></div>
                                        <?php if (!empty($u['user_mobile'])): ?>
                                            <small class="text-muted"><?= htmlspecialchars($u['user_mobile']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $roleBadge = match ($u['role']) {
                                            'owner', 'admin' => 'bg-danger',
                                            'manager' => 'bg-warning text-dark',
                                            'editor', 'staff' => 'bg-info text-dark',
                                            default => 'bg-secondary',
                                        };
                                        
                                        // Check if current admin has permission to change role (Admin or Owner only)
                                        $canChangeRole = in_array($_SESSION['admin_role'], ['admin', 'owner']);
                                        ?>
                                        
                                        <?php if ($canChangeRole && $u['user_id'] != $_SESSION['admin_id']): ?>
                                            <span class="badge <?= $roleBadge ?> rounded-pill cursor-pointer" 
                                                  onclick="changeRole(<?= $u['user_id'] ?>, '<?= $u['role'] ?>')"
                                                  id="role-badge-<?= $u['user_id'] ?>"
                                                  title="เธเธฅเธดเธเน€เธเธทเนเธญเน€เธเธฅเธตเนเธขเธเธ•เธณเนเธซเธเนเธ">
                                                <?= ucfirst($u['role']) ?> <i class="fas fa-pen ms-1" style="font-size: 10px;"></i>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge <?= $roleBadge ?> rounded-pill"><?= ucfirst($u['role']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        // Check if current admin has permission to change status (Staff or higher)
                                        $role_hierarchy = ['owner' => 1, 'admin' => 2, 'manager' => 3, 'editor' => 4, 'staff' => 5];
                                        $current_level = $role_hierarchy[$_SESSION['admin_role']] ?? 999;
                                        $canChangeStatus = $current_level <= 5;
                                        ?>
                                        <?php if ($canChangeStatus && $u['user_id'] != $_SESSION['admin_id']): ?>
                                            <?php if ($u['user_status'] == 1): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill cursor-pointer" 
                                                      onclick="changeStatus(<?= $u['user_id'] ?>, 1)"
                                                      id="status-badge-<?= $u['user_id'] ?>"
                                                      title="เธเธฅเธดเธเน€เธเธทเนเธญเน€เธเธฅเธตเนเธขเธเธชเธ–เธฒเธเธฐ">
                                                    Active <i class="fas fa-pen ms-1" style="font-size: 10px;"></i>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill cursor-pointer" 
                                                      onclick="changeStatus(<?= $u['user_id'] ?>, 0)"
                                                      id="status-badge-<?= $u['user_id'] ?>"
                                                      title="เธเธฅเธดเธเน€เธเธทเนเธญเน€เธเธฅเธตเนเธขเธเธชเธ–เธฒเธเธฐ">
                                                    Inactive <i class="fas fa-pen ms-1" style="font-size: 10px;"></i>
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?php if ($u['user_status'] == 1): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill cursor-pointer">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill cursor-pointer">Inactive</span>
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
                                        <button onclick="editUser(<?= $u['user_id'] ?>)" class="btn btn-sm btn-outline-primary me-1" title="เนเธเนเนเธ">
                                            <i data-lucide="edit-2" style="width:16px; height:16px;"></i>
                                        </button>
                                        <?php if ($u['user_id'] != $_SESSION['admin_id']): // เธซเนเธฒเธกเธฅเธเธ•เธฑเธงเน€เธญเธ ?>
                                            <button onclick="deleteUser(<?= $u['user_id'] ?>)" class="btn btn-sm btn-outline-danger" title="เธฅเธ">
                                                <i data-lucide="trash-2" style="width:16px; height:16px;"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">เนเธกเนเธเธเธเนเธญเธกเธนเธฅเธชเธกเธฒเธเธดเธ</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Pagination -->
        <div class="card-footer bg-white py-3">
            <?= render_pagination($page, $totalPages, "?page=%d&search=" . urlencode($search) . "&role=" . $role) ?>
        </div>
    </div>
</div>

<script>
// Define global variables for use in external JS
const ADMIN_URL = '<?= ADMIN_URL ?>';
const ROOT_URL = '<?= ROOT_URL ?>';

// Initialize Tom Select for role filter
document.addEventListener("DOMContentLoaded", function() {
    new TomSelect("#roleFilter", {
        controlInput: null,
        allowEmptyOption: true,
        sortField: {
            field: "text",
            direction: "asc"
        },
        dropdownParent: "body"
    });
    
    // Initialize Lucide icons
    lucide.createIcons();
});

// Change Status Function
function changeStatus(userId, currentStatus) {
    const newStatus = currentStatus === 1 ? 0 : 1;
    const statusText = newStatus === 1 ? 'เน€เธเธดเธ”เนเธเนเธเธฒเธ (Active)' : 'เธเธดเธ”เนเธเนเธเธฒเธ (Inactive)';
    
    Swal.fire({
        title: 'เธขเธทเธเธขเธฑเธเธเธฒเธฃเน€เธเธฅเธตเนเธขเธเธชเธ–เธฒเธเธฐ?',
        text: `เธเธธเธ“เธ•เนเธญเธเธเธฒเธฃเน€เธเธฅเธตเนเธขเธเธชเธ–เธฒเธเธฐเน€เธเนเธ "${statusText}" เนเธเนเธซเธฃเธทเธญเนเธกเน?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'เนเธเน, เน€เธเธฅเธตเนเธขเธเน€เธฅเธข',
        cancelButtonText: 'เธขเธเน€เธฅเธดเธ',
        background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
        color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'เธเธณเธฅเธฑเธเธเธฑเธเธ—เธถเธ...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                },
                background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
            });

            fetch(`${ADMIN_URL}/api/update_status.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ user_id: userId, status: newStatus })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'เธชเธณเน€เธฃเนเธ!',
                        text: 'เน€เธเธฅเธตเนเธขเธเธชเธ–เธฒเธเธฐเน€เธฃเธตเธขเธเธฃเนเธญเธขเนเธฅเนเธง',
                        timer: 1500,
                        showConfirmButton: false,
                        background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                        color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
                    });
                    setTimeout(() => location.reload(), 1500);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เน€เธเธดเธ”เธเนเธญเธเธดเธ”เธเธฅเธฒเธ”',
                        text: data.message || 'เนเธกเนเธชเธฒเธกเธฒเธฃเธ–เน€เธเธฅเธตเนเธขเธเธชเธ–เธฒเธเธฐเนเธ”เน',
                        background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                        color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'เน€เธเธดเธ”เธเนเธญเธเธดเธ”เธเธฅเธฒเธ”เนเธเธเธฒเธฃเน€เธเธทเนเธญเธกเธ•เนเธญ',
                    background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                    color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
                });
            });
        }
    });
}

// Change Role Function
function changeRole(userId, currentRole) {
    const roles = ['customer', 'staff', 'editor', 'manager', 'admin', 'owner'];
    const roleOptions = roles.map(r => 
        `<option value="${r}" ${r === currentRole ? 'selected' : ''}>${r.charAt(0).toUpperCase() + r.slice(1)}</option>`
    ).join('');

    Swal.fire({
        title: 'เน€เธเธฅเธตเนเธขเธเธ•เธณเนเธซเธเนเธ',
        html: `<select id="swal-role-select" class="form-select">${roleOptions}</select>`,
        showCancelButton: true,
        confirmButtonText: 'เธเธฑเธเธ—เธถเธ',
        cancelButtonText: 'เธขเธเน€เธฅเธดเธ',
        background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
        color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454',
        preConfirm: () => {
            return document.getElementById('swal-role-select').value;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const newRole = result.value;
            
            if (newRole === currentRole) return;

            Swal.fire({
                title: 'เธเธณเธฅเธฑเธเธเธฑเธเธ—เธถเธ...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                },
                background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
            });

            fetch(`${ADMIN_URL}/api/update_role.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ user_id: userId, role: newRole })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'เธชเธณเน€เธฃเนเธ!',
                        text: 'เน€เธเธฅเธตเนเธขเธเธ•เธณเนเธซเธเนเธเน€เธฃเธตเธขเธเธฃเนเธญเธขเนเธฅเนเธง',
                        timer: 1500,
                        showConfirmButton: false,
                        background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                        color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
                    });
                    setTimeout(() => location.reload(), 1500);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เน€เธเธดเธ”เธเนเธญเธเธดเธ”เธเธฅเธฒเธ”',
                        text: data.message || 'เนเธกเนเธชเธฒเธกเธฒเธฃเธ–เน€เธเธฅเธตเนเธขเธเธ•เธณเนเธซเธเนเธเนเธ”เน',
                        background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                        color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'เน€เธเธดเธ”เธเนเธญเธเธดเธ”เธเธฅเธฒเธ”เนเธเธเธฒเธฃเน€เธเธทเนเธญเธกเธ•เนเธญ',
                    background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                    color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
                });
            });
        }
    });
}
</script>

<!-- Load external JS file for user management functions -->
<script src="<?= ADMIN_ASSETS ?>/js/users.js<?= $ver ?>"></script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . "/../includes/layout.php"; 
?>

