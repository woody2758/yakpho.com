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
$sort   = trim($_GET['sort'] ?? 'desc'); // desc = newest first, asc = oldest first

// Query
$users      = get_all_users($limit, $offset, $search, $role, $sort);
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
                        <option value="">-- ทุกตำแหน่ง --</option>
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
                        <span>ค้นหา</span>
                    </button>
                </div>
                <?php if (!empty($search) || !empty($role)): ?>
                <div class="col-md-1">
                    <a href="./" class="btn btn-sm btn-outline-secondary w-100 d-flex align-items-center justify-content-center" title="ล้างค่า">
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
                                        <?php
                                        // Check if current admin has permission to change status (Staff or higher)
                                        $role_hierarchy = ['owner' => 1, 'admin' => 2, 'manager' => 3, 'editor' => 4, 'staff' => 5];
                                        $current_level = $role_hierarchy[$_SESSION['admin_role']] ?? 999;
                                        $canChangeStatus = $current_level <= 5;
                                        ?>
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
                                        <button onclick="editUser(<?= $u['user_id'] ?>)" class="btn btn-sm btn-outline-primary me-1" title="แก้ไข">
                                            <i data-lucide="edit-2" style="width:16px; height:16px;"></i>
                                        </button>
                                        <?php if ($u['user_id'] != $_SESSION['admin_id']): // ห้ามลบตัวเอง ?>
                                            <button onclick="deleteUser(<?= $u['user_id'] ?>)" class="btn btn-sm btn-outline-danger" title="ลบ">
                                                <i data-lucide="trash-2" style="width:16px; height:16px;"></i>
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
    
    // Setup AJAX pagination and search
    setupAjaxTableRefresh();
});

// Function to load users table via AJAX
function loadUsersTable(params) {
    // Add subtle loading indicator to table
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
                            <input type="text" id="customerName" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" id="customerLastname" name="lastname" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ชื่อเล่น</label>
                            <input type="text" id="customerNickname" name="nickname" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                            <input type="text" id="customerMobile" name="mobile" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">อีเมล</label>
                            <input type="email" id="customerEmail" name="email" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">ที่อยู่บรรทัด 1 <span class="text-danger">*</span></label>
                            <input type="text" id="customerAddrDetail" name="addr_detail" class="form-control" placeholder="เลขที่ หมู่บ้าน/คอนโด ชั้น ห้อง ถนน ซอย" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">ที่อยู่บรรทัด 2 <span class="text-danger">*</span></label>
                            <input type="text" id="customerAddrDetail2" name="addr_detail2" class="form-control" placeholder="ตำบล/แขวง อำเภอ/เขต" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">จังหวัด <span class="text-danger">*</span></label>
                            <select id="customerProvince" name="province_id" class="form-select" required>
                                <option value="">-- เลือกจังหวัด --</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">รหัสไปรษณีย์ <span class="text-danger">*</span></label>
                            <input type="text" id="customerPostcode" name="postcode" class="form-control" maxlength="5" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ประเภทที่อยู่</label>
                            <select id="customerAddrType" name="addr_type" class="form-select">
                                <option value="ที่บ้าน" selected>ที่บ้าน</option>
                                <option value="ที่ทำงาน">ที่ทำงาน</option>
                                <option value="ที่อยู่สำหรับออกใบกำกับภาษี">ที่อยู่สำหรับออกใบกำกับภาษี</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">หมายเหตุ</label>
                            <input type="text" id="customerForword" name="addr_forword" class="form-control">
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

<!-- Load external JS file for user management functions -->
<script src="<?= ADMIN_ASSETS ?>/js/users.js<?= $ver ?>"></script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . "/../includes/layout.php"; 
?>
