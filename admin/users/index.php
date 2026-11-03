<?php
$page_title = "จัดการสมาชิก";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../../includes/functions/user.php";
require_once __DIR__ . "/../../includes/functions/pagination.php";
require_once __DIR__ . "/../../includes/functions/address.php";

// Pagination & Filter
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit  = 15;
$offset = ($page - 1) * $limit;
$search = trim($_GET['search'] ?? '');
$role   = trim($_GET['role'] ?? '');
$tier   = trim($_GET['tier'] ?? '');
$sort   = trim($_GET['sort'] ?? 'desc'); // desc = newest first, asc = oldest first

// Query
$users      = get_all_users($limit, $offset, $search, $role, $sort, '', $tier);
$totalUsers = count_all_users($search, $role, '', $tier);
$totalPages = ceil($totalUsers / $limit);
$ver = "?v=" . time();

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
                    <div class="position-relative">
                        <input type="text" name="search" id="searchInput" class="form-control" placeholder="ค้นหาชื่อ, เบอร์โทร, อีเมล, รหัสลูกค้า (C13213)" value="<?= htmlspecialchars($search) ?>">
                        <?php if (!empty($search)): ?>
                        <span onclick="clearSearch()" class="position-absolute end-0 top-50 translate-middle-y d-flex align-items-center justify-content-center" style="cursor:pointer; margin-right:8px; width:20px; height:20px; border-radius:50%; background-color:rgba(108, 117, 125, 0.2);" title="ล้างคำค้นหา">
                            <i data-lucide="x" style="width:12px; height:12px; color:#6c757d;"></i>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="role" id="roleFilter" class="form-select">
                        <option value="">-- ทุกตำแหน่ง --</option>
                        <option value="customer" <?= $role == 'customer' ? 'selected' : '' ?>>Customer</option>
                        <option value="staff" <?= $role == 'staff' ? 'selected' : '' ?>>Staff</option>
                        <option value="editor" <?= $role == 'editor' ? 'selected' : '' ?>>Editor</option>
                        <option value="manager" <?= $role == 'manager' ? 'selected' : '' ?>>Manager</option>
                        <option value="admin" <?= $role == 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="owner" <?= $role == 'owner' ? 'selected' : '' ?>>Owner</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="tier" id="tierFilter" class="form-select">
                        <option value="">-- ทุกระดับสมาชิก --</option>
                        <option value="general" <?= $tier === 'general' ? 'selected' : '' ?>>🌿 General Member</option>
                        <option value="vip_spa" <?= $tier === 'vip_spa' ? 'selected' : '' ?>>👑 VIP Spa (ร้านนวด)</option>
                        <option value="therapist_partner" <?= $tier === 'therapist_partner' ? 'selected' : '' ?>>⭐ Yak Pho Partner (หมอนวด)</option>
                        <option value="oem_export" <?= $tier === 'oem_export' ? 'selected' : '' ?>>💎 OEM & Export</option>
                    </select>
                </div>
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
                            <th class="ps-4">
                                <?php
                                // Build query params for sort toggle
                                $currentParams = $_GET;
                                $currentParams['sort'] = ($sort === 'desc') ? 'asc' : 'desc';
                                $sortUrl = '?' . http_build_query($currentParams);
                                ?>
                                <a href="<?= $sortUrl ?>" class="text-decoration-none text-dark d-flex align-items-center gap-1">
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
                                $userId = (int)$u['user_id'];
                                $fullname = trim(($u['user_name'] ?? '') . ' ' . ($u['user_lastname'] ?? ''));
                                if (empty($fullname)) {
                                    $fullname = !empty($u['user_mobile']) ? $u['user_mobile'] : (!empty($u['user_email']) ? $u['user_email'] : 'C' . $userId);
                                }
                                $userPic = $u['user_picture'] ?? '';
                                $img_path = __DIR__ . "/../../uploads/profile/{$userId}/{$userPic}";
                                $img_url  = ROOT_URL . "/uploads/profile/{$userId}/{$userPic}";
                                if (!file_exists($img_path) && !empty($userPic) && file_exists(__DIR__ . "/../../uploads/profile/{$userPic}")) {
                                    $img_path = __DIR__ . "/../../uploads/profile/{$userPic}";
                                    $img_url  = ROOT_URL . "/uploads/profile/{$userPic}";
                                }
                                $hasImage = !empty($userPic) && file_exists($img_path);
                                $avatarUrl = $hasImage ? $img_url . '?t=' . time() : (!empty($u['line_picture_url']) ? $u['line_picture_url'] : ROOT_URL . '/uploads/profile/default.png');
                                $showAvatar = $hasImage || !empty($u['line_picture_url']);

                                $addr_count = count_user_addresses($userId);
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

                                $role_clean = trim($u['role'] ?? '', "'\"");
                                $roleBadge = match ($role_clean) {
                                    'owner', 'admin'  => 'bg-danger',
                                    'manager'         => 'bg-warning text-dark',
                                    'editor', 'staff' => 'bg-info text-dark',
                                    default           => 'bg-secondary',
                                };
                                $canChangeRole = in_array($_SESSION['admin_role'], ['admin', 'owner']);
                                $role_hierarchy = ['owner' => 1, 'admin' => 2, 'manager' => 3, 'editor' => 4, 'staff' => 5];
                                $current_level = $role_hierarchy[$_SESSION['admin_role']] ?? 999;
                                $canChangeStatus = $current_level <= 5;
                            ?>
                                <tr id="user-row-<?= $userId ?>">
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark">C<?= $userId ?></div>
                                        <?php
                                        $order_count_stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND orders_del = 0");
                                        $order_count_stmt->execute([$userId]);
                                        $order_count = (int)$order_count_stmt->fetchColumn();
                                        if ($order_count > 0):
                                        ?>
                                        <div class="text-danger mt-1 cursor-pointer" style="font-size: 0.75rem; display: inline-flex; align-items: center; gap: 4px;" title="จำนวนการสั่งซื้อทั้งหมด คลิกเพื่อดู" onclick="window.location.href='<?= ADMIN_URL ?>/orders/?search=<?= urlencode('C'.$userId) ?>&status=all'">
                                            <i data-lucide="heart" style="width:12px;height:12px;fill:#dc3545;"></i> <?= number_format($order_count) ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="width: 50px;">
                                        <div class="d-flex align-items-center">
                                            <div class="profile-pic-wrapper" style="width:36px; height:36px; border-radius:50%; overflow:hidden; border:1px solid #e5e7eb; cursor:pointer;" onclick="openImageManagement(<?= $userId ?>, '<?= htmlspecialchars(addslashes($fullname)) ?>', '<?= $avatarUrl ?>')">
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
                                        <div class="fw-bold" style="font-size: 0.85rem;"><?= htmlspecialchars($fullname) ?></div>
                                        <div class="d-flex align-items-center flex-wrap gap-1 mt-1">
                                            <a href="javascript:void(0)" 
                                               onclick="openAddressModal(<?= $userId ?>, '<?= htmlspecialchars(addslashes($fullname)) ?>')" 
                                               class="no-loader text-decoration-none" style="font-size: 0.7rem; color: #6c757d;">
                                                <i data-lucide="map-pin" style="width:10px;height:10px;"></i> 
                                                ที่อยู่ (<?= $addr_count ?>)
                                            </a>
                                            <span class="badge rounded-pill <?= $tierClass ?> user-tier-badge-<?= $userId ?>" title="ระดับสมาชิก"><?= $tierLabel ?></span>
                                            <span class="badge rounded-pill bg-gold-subtle text-dark user-points-badge-<?= $userId ?>" title="แต้มสะสม M-Stamp">🪙 <?= number_format($userPoints) ?> แต้ม</span>
                                            <?php if (!empty($u['line_user_id'])): ?>
                                            <span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success" title="LINE: <?= htmlspecialchars($u['line_display_name'] ?? 'เชื่อมต่อแล้ว') ?>" style="font-size: 0.62rem;">
                                                <i data-lucide="message-circle" style="width:9px;height:9px;"></i> LINE
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($u['user_email'] ?: '-') ?></div>
                                        <?php if (!empty($u['user_mobile'])): ?>
                                            <small class="text-muted"><?= htmlspecialchars($u['user_mobile']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($canChangeRole && $userId != $_SESSION['admin_id']): ?>
                                            <span class="badge <?= $roleBadge ?> rounded-pill" 
                                                  style="cursor: pointer; font-size: 0.65rem;"
                                                  onclick="changeRole(<?= $userId ?>, '<?= addslashes($role_clean) ?>')"
                                                  id="role-badge-<?= $userId ?>"
                                                  title="คลิกเพื่อเปลี่ยนตำแหน่ง">
                                                <?= ucfirst($role_clean) ?> <i class="fas fa-pen ms-1" style="font-size: 8px;"></i>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge <?= $roleBadge ?> rounded-pill" style="font-size: 0.65rem;"><?= ucfirst($role_clean) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($canChangeStatus && $userId != $_SESSION['admin_id']): ?>
                                            <?php if ($u['user_status'] == 1): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill" 
                                                      style="cursor: pointer; font-size: 0.65rem;"
                                                      onclick="changeStatus(<?= $userId ?>, 1)"
                                                      id="status-badge-<?= $userId ?>"
                                                      title="คลิกเพื่อเปลี่ยนสถานะ">
                                                    Active <i class="fas fa-pen ms-1" style="font-size: 8px;"></i>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill" 
                                                      style="cursor: pointer; font-size: 0.65rem;"
                                                      onclick="changeStatus(<?= $userId ?>, 0)"
                                                      id="status-badge-<?= $userId ?>"
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
                                        <button class="btn-action-icon btn-action-tier me-1" onclick="openAdjustTierModal(<?= $userId ?>, '<?= htmlspecialchars(addslashes($fullname)) ?>', '<?= htmlspecialchars($u['member_tier'] ?? 'general') ?>', <?= $userPoints ?>, <?= !empty($u['line_user_id']) ? 'true' : 'false' ?>)" title="ปรับระดับสมาชิก & แต้ม M-Stamp">
                                            <i data-lucide="award"></i>
                                        </button>
                                        <button class="btn-action-icon btn-action-vip me-1" onclick="copyVipInvite(<?= $userId ?>, this)" title="คัดลอกลิงก์เชิญ VIP (30 วัน)">
                                            <i data-lucide="crown"></i>
                                        </button>
                                        <button class="btn-action-icon btn-action-key me-1" onclick="openResetPasswordModal(<?= $userId ?>, '<?= htmlspecialchars(addslashes($fullname)) ?>')" title="รีเซ็ตรหัสผ่าน">
                                            <i data-lucide="key"></i>
                                        </button>
                                        <button class="btn-action-icon btn-action-edit me-1" onclick="editUser(<?= $userId ?>)" title="แก้ไขข้อมูลผู้ใช้">
                                            <i data-lucide="edit-2"></i>
                                        </button>
                                        <?php if ($userId != $_SESSION['admin_id']): ?>
                                            <button class="btn-action-icon btn-action-delete" onclick="deleteUser(<?= $userId ?>)" title="ลบผู้ใช้">
                                                <i data-lucide="trash-2"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">ไม่พบข้อมูลสมาชิก</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Pagination -->
        <div class="card-footer bg-white py-3">
            <?= render_pagination($page, $totalPages, "?page=%d&search=" . urlencode($search) . "&role=" . $role . "&tier=" . urlencode($tier) . "&sort=" . $sort) ?>
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
    
    // Setup AJAX pagination and search
    setupAjaxTableRefresh();

    const tierFilterEl = document.getElementById('tierFilter');
    if (tierFilterEl) {
        tierFilterEl.addEventListener('change', function() {
            performSearch();
        });
    }
});

// Function to load users table via AJAX
function loadUsersTable(params) {
    // Add subtle loading indicator (opacity 50%)
    const tableContainer = document.querySelector('.table-responsive');
    if (tableContainer) {
        tableContainer.style.opacity = '0.5';
        tableContainer.style.pointerEvents = 'none';
    }
    
    fetch(`${ADMIN_URL}/api/get_users_table.php?${params}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Replace table content
                if (tableContainer) {
                    tableContainer.innerHTML = data.table;
                    tableContainer.style.opacity = '1';
                    tableContainer.style.pointerEvents = 'auto';
                }
                
                // Replace pagination
                const paginationContainer = document.querySelector('.card-footer');
                if (paginationContainer) {
                    paginationContainer.innerHTML = data.pagination;
                }
                
                // Update URL without reload
                const newUrl = `${window.location.pathname}?${params}`;
                history.pushState(null, '', newUrl);
                
                // Re-initialize Lucide icons
                lucide.createIcons();
            } else {
                throw new Error(data.message || 'Failed to load data');
            }
        })
        .catch(error => {
            console.error('Error loading table:', error);
            
            // Restore table opacity
            if (tableContainer) {
                tableContainer.style.opacity = '1';
                tableContainer.style.pointerEvents = 'auto';
            }
            
            // Show error in console for debugging
            console.error('Full error details:', {
                url: `${ADMIN_URL}/api/get_users_table.php?${params}`,
                error: error.message,
                stack: error.stack
            });
            
            // Optional: Show subtle error message
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: 'ไม่สามารถโหลดข้อมูลได้ กรุณาลองใหม่อีกครั้ง',
                timer: 2000,
                showConfirmButton: false,
                background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
            });
        });
}

// Setup AJAX table refresh for pagination and search
function setupAjaxTableRefresh() {
    // Intercept pagination clicks
    document.addEventListener('click', function(e) {
        const pageLink = e.target.closest('.page-link');
        const sortLink = e.target.closest('.sort-link');
        
        if (pageLink && !pageLink.parentElement.classList.contains('disabled')) {
            e.preventDefault();
            const url = new URL(pageLink.href);
            loadUsersTable(url.searchParams.toString());
        } else if (sortLink) {
            e.preventDefault();
            const url = new URL(sortLink.href);
            loadUsersTable(url.searchParams.toString());
        }
    });
    
    // Intercept search form submission
    const searchForm = document.querySelector('form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            performSearch();
        });
        
        // Live search with debounce (triggers after 2+ characters)
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            let debounceTimer;
            searchInput.addEventListener('input', function() {
                const searchValue = this.value.trim();
                
                // Clear previous timer
                clearTimeout(debounceTimer);
                
                // Only search if 2+ characters or empty (to show all)
                if (searchValue.length >= 2 || searchValue.length === 0) {
                    debounceTimer = setTimeout(() => {
                        performSearch();
                    }, 300); // 300ms debounce
                }
            });
        }
    }
    
    // Handle browser back/forward buttons
    window.addEventListener('popstate', function() {
        const params = new URLSearchParams(window.location.search);
        loadUsersTable(params.toString());
    });
}

// Clear search function
function clearSearch() {
    document.getElementById('searchInput').value = '';
    performSearch();
}

// Helper function to perform search
function performSearch() {
    const formData = new FormData(document.querySelector('form'));
    const params = new URLSearchParams(formData);
    
    // Preserve current sort parameter from URL
    const currentParams = new URLSearchParams(window.location.search);
    const currentSort = currentParams.get('sort') || 'desc';
    params.set('sort', currentSort);
    
    // Reset to page 1 on new search
    params.set('page', '1');
    
    loadUsersTable(params.toString());
}

// Change Status Function
function changeStatus(userId, currentStatus) {
    const newStatus = currentStatus === 1 ? 0 : 1;
    const statusText = newStatus === 1 ? 'เปิดใช้งาน (Active)' : 'ปิดใช้งาน (Inactive)';
    
    Swal.fire({
        title: 'ยืนยันการเปลี่ยนสถานะ?',
        text: `คุณต้องการเปลี่ยนสถานะเป็น \"${statusText}\" ใช่หรือไม่?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'ใช่, เปลี่ยนเลย',
        cancelButtonText: 'ยกเลิก',
        background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
        color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'กำลังบันทึก...',
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
                    // Update badge dynamically without page reload
                    const badge = document.getElementById(`status-badge-${userId}`);
                    if (badge) {
                        if (newStatus === 1) {
                            badge.className = 'badge bg-success bg-opacity-10 text-success rounded-pill';
                            badge.style.cursor = 'pointer';
                            badge.innerHTML = 'Active <i class="fas fa-pen ms-1" style="font-size: 10px;"></i>';
                            badge.setAttribute('onclick', `changeStatus(${userId}, 1)`);
                        } else {
                            badge.className = 'badge bg-secondary bg-opacity-10 text-secondary rounded-pill';
                            badge.style.cursor = 'pointer';
                            badge.innerHTML = 'Inactive <i class="fas fa-pen ms-1" style="font-size: 10px;"></i>';
                            badge.setAttribute('onclick', `changeStatus(${userId}, 0)`);
                        }
                    }
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        text: 'เปลี่ยนสถานะเรียบร้อยแล้ว',
                        timer: 1500,
                        showConfirmButton: false,
                        background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                        color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: data.message || 'ไม่สามารถเปลี่ยนสถานะได้',
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
                    text: 'เกิดข้อผิดพลาดในการเชื่อมต่อ',
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
        title: 'เปลี่ยนตำแหน่ง',
        html: `<select id="swal-role-select" class="form-select">${roleOptions}</select>`,
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
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
                title: 'กำลังบันทึก...',
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
                    // Update badge dynamically without page reload
                    const badge = document.getElementById(`role-badge-${userId}`);
                    if (badge) {
                        // Determine badge color based on role
                        let badgeClass = 'badge rounded-pill';
                        if (newRole === 'owner' || newRole === 'admin') {
                            badgeClass += ' bg-danger';
                        } else if (newRole === 'manager') {
                            badgeClass += ' bg-warning text-dark';
                        } else if (newRole === 'editor' || newRole === 'staff') {
                            badgeClass += ' bg-info text-dark';
                        } else {
                            badgeClass += ' bg-secondary';
                        }
                        
                        badge.className = badgeClass;
                        badge.style.cursor = 'pointer';
                        badge.innerHTML = `${newRole.charAt(0).toUpperCase() + newRole.slice(1)} <i class="fas fa-pen ms-1" style="font-size: 10px;"></i>`;
                        badge.setAttribute('onclick', `changeRole(${userId}, '${newRole}')`);
                    }
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        text: 'เปลี่ยนตำแหน่งเรียบร้อยแล้ว',
                        timer: 1500,
                        showConfirmButton: false,
                        background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                        color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: data.message || 'ไม่สามารถเปลี่ยนตำแหน่งได้',
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
                    text: 'เกิดข้อผิดพลาดในการเชื่อมต่อ',
                    background: document.body.getAttribute('data-theme') === 'dark' ? '#1e1e1e' : '#fff',
                    color: document.body.getAttribute('data-theme') === 'dark' ? '#fff' : '#545454'
                });
            });
        }
    });
}
</script>

<!-- Customer Edit Modal (Matches Orders customerModal with full details) -->
<div class="modal fade" id="customerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content position-relative">
            <div id="customerModalLoadingOverlay" class="position-absolute top-0 start-0 w-100 h-100 bg-white d-flex flex-column justify-content-center align-items-center z-3 d-none" style="border-radius: 0.3rem; opacity: 0.9;">
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
                <div class="mt-3 text-primary fw-bold fs-5">กำลังโหลดข้อมูล...</div>
            </div>
            <div class="modal-header">
                <h5 class="modal-title"><i data-lucide="user-check" class="me-2 text-primary"></i>แก้ไขข้อมูลลูกค้าแบบครบวงจร</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
                <form id="customerForm">
                    <input type="hidden" id="customerUserId" name="user_id">
                    <input type="hidden" id="customerOrderId" name="orders_id" value="0">
                    <input type="hidden" id="customerAddrId" name="addr_id" value="0">

                    <!-- Section 1: Customer Profile & Tier -->
                    <div class="card mb-3 border">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0 fw-bold"><i data-lucide="user" class="me-2 text-primary"></i>1. ข้อมูลส่วนตัวและระดับสมาชิก</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">ชื่อ (First Name) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm" id="customerName" name="user_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">นามสกุล (Last Name) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm" id="customerLastname" name="user_lastname" required>
                                </div>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">ชื่อเล่น (Nickname)</label>
                                    <input type="text" class="form-control form-control-sm" id="customerNickname" name="user_nickname">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">เบอร์โทรศัพท์ (Phone) <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control form-control-sm" id="customerMobile" name="user_mobile">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">อีเมล (Email)</label>
                                    <input type="email" class="form-control form-control-sm" id="customerEmail" name="user_email">
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-primary"><i data-lucide="award" class="me-1"></i> ระดับสมาชิก (Member Tier)</label>
                                    <select class="form-select form-select-sm" id="customerTier" name="member_tier">
                                        <option value="general">🌿 General Member (ลูกค้าปลีกทั่วไป)</option>
                                        <option value="vip_spa">👑 VIP Spa Member (ร้านนวด & สปา เรทส่ง)</option>
                                        <option value="therapist_partner">⭐ Yak Pho Partner (หมอนวด Affiliate)</option>
                                        <option value="oem_export">💎 OEM & Export (สั่งผลิต 100 กก.+)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-warning"><i data-lucide="coins" class="me-1"></i> แต้มสะสม M-Stamp (แต้ม = บาท)</label>
                                    <input type="number" class="form-control form-control-sm" id="customerPoints" name="m_stamp_points" min="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Shipping / Billing Address -->
                    <div class="card mb-3 border">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0 fw-bold"><i data-lucide="map-pin" class="me-2 text-success"></i>2. ข้อมูลที่อยู่จัดส่งพัสดุ / ใบเสร็จ</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">ชื่อผู้รับ / ชื่อร้านสปา / บริษัท</label>
                                    <input type="text" class="form-control form-control-sm" id="customerAddrName" name="addr_name" placeholder="เช่น ร้านพิมพ์มาดา นวดเพื่อสุขภาพ">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">เบอร์โทรศัพท์ผู้รับ</label>
                                    <input type="tel" class="form-control form-control-sm" id="customerAddrMobile" name="addr_mobile" placeholder="08x-xxx-xxxx">
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-bold">รายละเอียดที่อยู่ (บ้านเลขที่, หมู่บ้าน, ซอย, ถนน) <span class="text-danger">*</span></label>
                                <textarea class="form-control form-control-sm" id="customerAddrDetail" name="addr_detail" rows="2" placeholder="เช่น 99/42 ซอยรามอินทรา 34 ถนนรามอินทรา..."></textarea>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">ตำบล / แขวง / อำเภอ / เขต</label>
                                    <input type="text" class="form-control form-control-sm" id="customerAddrDetail2" name="addr_detail2" placeholder="เช่น แขวงท่าแร้ง เขตบางเขน">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">จังหวัด</label>
                                    <select class="form-select form-select-sm" id="customerProvince" name="provinces_id">
                                        <option value="">-- กำลังโหลดจังหวัด... --</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">รหัสไปรษณีย์</label>
                                    <input type="text" class="form-control form-control-sm" id="customerAddrPostcode" name="addr_postcode" placeholder="เช่น 10220">
                                </div>
                            </div>
                            <div class="mb-1">
                                <label class="form-label small fw-bold text-muted">เลขประจำตัวผู้เสียภาษี (Tax ID / ถ้ามี)</label>
                                <input type="text" class="form-control form-control-sm" id="customerAddrTax" name="addr_tax_id" placeholder="เลขประจำตัวผู้เสียภาษี 13 หลัก">
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Extra Info & Notes -->
                    <div class="card mb-2 border">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0 fw-bold"><i data-lucide="globe" class="me-2 text-info"></i>3. ข้อมูลเพิ่มเติม / พิกัดร้านสปา</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <label class="form-label small fw-bold text-primary"><i data-lucide="globe" class="me-1" style="width: 14px; height: 14px;"></i> เว็บไซต์ (Website / เพจเฟซบุ๊ก)</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" id="customerWebsite" name="user_website" placeholder="ระบุเว็บไซต์หรือเพจ เช่น https://facebook.com/...">
                                    <a href="#" target="_blank" class="btn btn-outline-primary" id="btnOpenWebsite" style="display:none;"><i data-lucide="external-link" style="width: 14px; height: 14px;"></i> เปิดลิงก์</a>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-bold text-success"><i data-lucide="map-pin" class="me-1" style="width: 14px; height: 14px;"></i> ลิงก์ Google Map ร้านสปา</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" id="customerGoogleMap" name="user_google_map" placeholder="วางลิงก์ Google Map ที่นี่">
                                    <a href="#" target="_blank" class="btn btn-outline-success" id="btnOpenMap" style="display:none;"><i data-lucide="external-link" style="width: 14px; height: 14px;"></i> ดูแผนที่</a>
                                </div>
                            </div>
                            <div>
                                <label class="form-label small fw-bold text-info"><i data-lucide="file-text" class="me-1" style="width: 14px; height: 14px;"></i> ข้อมูลลูกค้า / หมายเหตุภายใน</label>
                                <textarea class="form-control form-control-sm" id="customerNote" name="user_note" rows="2" placeholder="ระบุข้อมูลที่ควรทราบเกี่ยวกับลูกค้ารายนี้..."></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary px-4" onclick="saveCustomerInfo()">
                    <i data-lucide="save" class="me-1" style="width:16px;height:16px;"></i> บันทึกข้อมูล
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">เพิ่มลูกค้าใหม่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">วางข้อมูลลูกค้าทั้งหมดที่นี่:</label>
                        <textarea id="customerDataPaste" class="form-control" rows="5" placeholder="ตัวอย่าง:
สมชาย ใจดี ชาย
123/45 หมู่ 1 ต.บางเขน อ.เมือง
จ.นนทบุรี 11000
0812345678
youremail@gmail.com
หมายเหตุการจัดส่ง
"></textarea>
                    </div>
                    <div class="col-md-6">
                        <p class="mt-2 mb-0"><strong>รูปแบบการป้อนข้อมูล</strong></p>
                        <ol class="small ms-3">
                            <li>ชื่อ-นามสกุล ชื่อเล่น (ถ้ามี) – แบ่งโดยเว้นวรรค</li>
                            <li>เลขที่ ชื่อหมู่บ้าน/คอนโด ชั้น ห้อง/หมู่ ถนน ซอย</li>
                            <li>ตำบล/แขวง อำเภอ/เขต</li>
                            <li>จังหวัด รหัสไปรษณีย์ – แบ่งโดยเว้นวรรค</li>
                            <li>เบอร์โทรศัพท์ – ต้องมี</li>
                            <li>อีเมล (ถ้ามี) – หากไม่มีให้เว้นบรรทัดเปล่า</li>
                            <li>หมายเหตุ (ถ้ามี)</li>
                        </ol>
                    </div>
                </div>
                <button type="button" onclick="parseCustomerData()" class="btn btn-sm btn-secondary mt-2">
                    <i data-lucide="zap" style="width:14px;height:14px;"></i> แยกข้อมูลอัตโนมัติ
                </button>
                <hr>
                <form id="addCustomerForm">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">ชื่อ <span class="text-danger">*</span></label>
                            <input type="text" id="addCustomerName" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" id="addCustomerLastname" name="lastname" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ชื่อเล่น</label>
                            <input type="text" id="addCustomerNickname" name="nickname" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                            <input type="text" id="addCustomerMobile" name="mobile" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">อีเมล</label>
                            <input type="email" id="addCustomerEmail" name="email" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">ที่อยู่บรรทัด 1 <span class="text-danger">*</span></label>
                            <input type="text" id="addCustomerAddrDetail" name="addr_detail" class="form-control" placeholder="เลขที่ หมู่บ้าน/คอนโด ชั้น ห้อง ถนน ซอย" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">ที่อยู่บรรทัด 2 <span class="text-danger">*</span></label>
                            <input type="text" id="addCustomerAddrDetail2" name="addr_detail2" class="form-control" placeholder="ตำบล/แขวง อำเภอ/เขต" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">จังหวัด <span class="text-danger">*</span></label>
                            <select id="addCustomerProvince" name="province_id" class="form-select" required>
                                <option value="">-- เลือกจังหวัด --</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">รหัสไปรษณีย์ <span class="text-danger">*</span></label>
                            <input type="text" id="addCustomerPostcode" name="postcode" class="form-control" maxlength="5" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ประเภทที่อยู่</label>
                            <select id="addCustomerAddrType" name="addr_type" class="form-select">
                                <option value="ที่บ้าน" selected>ที่บ้าน</option>
                                <option value="ที่ทำงาน">ที่ทำงาน</option>
                                <option value="ที่อยู่สำหรับออกใบกำกับภาษี">ที่อยู่สำหรับออกใบกำกับภาษี</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">หมายเหตุ</label>
                            <input type="text" id="addCustomerForword" name="addr_forword" class="form-control">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" onclick="saveCustomer()" class="btn btn-primary">บันทึก</button>
            </div>
        </div>
    </div>
</div>

<!-- Address Management Modal -->
<div class="modal fade" id="addressModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i data-lucide="map-pin" style="width:20px;height:20px;"></i>
                    <span id="addressModalTitle">ที่อยู่ของลูกค้า</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Add Address Button -->
                <div class="mb-3">
                    <button type="button" class="btn btn-success btn-sm" onclick="showAddressForm()">
                        <i data-lucide="plus" style="width:16px;height:16px;"></i>
                        เพิ่มที่อยู่ใหม่
                    </button>
                </div>

                <!-- Address Form (Hidden by default) -->
                <div id="addressFormContainer" style="display:none;">
                    <div class="card mb-3 border-primary">
                        <div class="card-header bg-primary bg-opacity-10">
                            <h6 class="mb-0" id="formTitle">เพิ่มที่อยู่ใหม่</h6>
                        </div>
                        <div class="card-body">
                            <form id="addressForm">
                                <input type="hidden" id="addr_id" name="addr_id">
                                <input type="hidden" id="user_id" name="user_id">
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">ชื่อผู้รับ <span class="text-danger">*</span></label>
                                        <input type="text" id="addr_name" name="addr_name" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                                        <input type="text" id="addr_mobile" name="addr_mobile" class="form-control" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">ที่อยู่บรรทัด 1 <span class="text-danger">*</span></label>
                                        <input type="text" id="addr_detail" name="addr_detail" class="form-control" placeholder="เลขที่ หมู่บ้าน/คอนโด ชั้น ห้อง ถนน ซอย" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">ที่อยู่บรรทัด 2</label>
                                        <input type="text" id="addr_detail2" name="addr_detail2" class="form-control" placeholder="ตำบล/แขวง อำเภอ/เขต">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">จังหวัด <span class="text-danger">*</span></label>
                                        <select id="provinces_id" name="provinces_id" class="form-select" required>
                                            <option value="">-- เลือกจังหวัด --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">รหัสไปรษณีย์</label>
                                        <input type="text" id="addr_postcode" name="addr_postcode" class="form-control" maxlength="5">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">ประเภทที่อยู่ <span class="text-danger">*</span></label>
                                        <select id="addr_type" name="addr_type" class="form-select" required>
                                            <option value="1">🏠 ที่บ้าน</option>
                                            <option value="2">🏢 ที่ทำงาน</option>
                                            <option value="3">📄 ที่อยู่สำหรับออกบิล/ใบกำกับภาษี</option>
                                            <option value="4">📦 ใช้เป็นผู้ส่งในนาม</option>
                                            <option value="5">👤 ผู้รับแทน</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">หมายเหตุ</label>
                                        <input type="text" id="addr_forword" name="addr_forword" class="form-control">
                                    </div>
                                </div>
                                
                                <div class="mt-3 d-flex gap-2">
                                    <button type="button" class="btn btn-primary" onclick="saveAddress()">
                                        <i data-lucide="save" style="width:16px;height:16px;"></i>
                                        บันทึก
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="cancelAddressForm()">
                                        ยกเลิก
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Address List -->
                <div id="addressList">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Load external JS file for user management functions -->
<script src="<?= ADMIN_ASSETS ?>/js/users.js<?= $ver ?>"></script>
<script src="<?= ADMIN_ASSETS ?>/js/addresses.js<?= $ver ?>"></script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . "/../includes/layout.php"; 
?>
