<?php
$page_title = "จัดการคำสั่งซื้อ";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/auth.php";

ob_start();
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i data-lucide="shopping-bag" class="me-2"></i>จัดการคำสั่งซื้อ</h2>
        <button onclick="createNewOrder()" class="btn btn-success">
            <i data-lucide="plus" class="me-2"></i>สร้างออเดอร์ใหม่
        </button>
    </div>

    <!-- Status Dashboard -->
    <div id="statusDashboard" class="row g-3 mb-4">
        <!-- Status cards will be loaded here -->
        <div class="col-12 text-center py-3">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>

    <!-- Search Card -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-12">
                    <div class="position-relative">
                        <input type="text" id="searchInput" class="form-control" placeholder="ค้นหาเลขที่ออเดอร์, ชื่อลูกค้า...">
                        <span id="clearSearch" class="position-absolute end-0 top-50 translate-middle-y d-none align-items-center justify-content-center" 
                              style="cursor:pointer; margin-right:8px; width:20px; height:20px; border-radius:50%; background-color:rgba(108, 117, 125, 0.2);" 
                              title="ล้างคำค้นหา" onclick="clearSearch()">
                            <i data-lucide="x" style="width:12px; height:12px; color:#6c757d;"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div id="tableContainer">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pagination -->
        <div class="card-footer bg-white py-3" id="paginationContainer">
            <!-- Pagination will be loaded here -->
        </div>
    </div>
</div>

<!-- Order Detail Modal -->
<div class="modal fade" id="orderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="modalTitle">รายละเอียดคำสั่งซื้อ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="modalContent" class="p-4">
                    <!-- Content will be loaded here -->
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <div class="d-flex align-items-center me-auto">
                    <span class="me-2 fw-bold">เปลี่ยนสถานะ:</span>
                    <select id="statusSelect" class="form-select form-select-sm" style="width: 200px;">
                        <!-- Status options will be loaded here -->
                    </select>
                    <div class="form-check ms-2">
                        <input class="form-check-input" type="checkbox" id="notifyEmail">
                        <label class="form-check-label small" for="notifyEmail">แจ้งเตือนอีเมล</label>
                    </div>
                    <button onclick="updateStatus()" class="btn btn-sm btn-primary ms-2">บันทึก</button>
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<!-- User Image Modal -->
<div class="modal fade" id="userImageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">อัปเดตรูปโปรไฟล์ลูกค้า</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <form id="userImageForm">
                    <input type="hidden" id="user_image_id" name="user_id">
                    <div class="mb-3">
                        <img id="current_user_image" src="" class="rounded-circle border mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                    </div>
                    <div class="mb-3">
                        <input type="file" class="form-control" name="user_picture" accept="image/*" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" onclick="saveUserImage()">บันทึก</button>
            </div>
        </div>
    </div>
</div>

<!-- Shipping Modal -->
<div class="modal fade" id="shippingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">จัดการการจัดส่ง</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="shippingForm">
                    <input type="hidden" id="shipping_order_id" name="orders_id">
                    <div class="mb-3">
                        <label class="form-label">เลือกขนส่ง</label>
                        <select class="form-select" id="shipping_transcat_id" name="transcat_id" required>
                            <option value="">-- เลือกขนส่ง --</option>
                            <!-- Options will be loaded via JS -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">เลขพัสดุ (Tracking Number)</label>
                        <input type="text" class="form-control" id="shipping_tracking" name="orders_tracking" placeholder="ระบุเลขพัสดุ">
                        <div class="form-text">กรณีมีหลายเลข ให้คั่นด้วยเครื่องหมายคอมม่า (,)</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" onclick="saveShipping()">บันทึก</button>
            </div>
        </div>
    </div>
</div>

<!-- Customer Edit Modal -->
<div class="modal fade" id="customerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">แก้ไขข้อมูลลูกค้า</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="customerForm">
                    <input type="hidden" id="customerUserId" name="user_id">
                    <input type="hidden" id="customerOrderId" name="orders_id">
                    <div class="mb-3">
                        <label class="form-label">ชื่อ (First Name)</label>
                        <input type="text" class="form-control" id="customerName" name="user_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">นามสกุล (Last Name)</label>
                        <input type="text" class="form-control" id="customerLastname" name="user_lastname" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">เบอร์โทร (Phone)</label>
                        <input type="text" class="form-control" id="customerMobile" name="user_mobile">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">อีเมล (Email)</label>
                        <input type="email" class="form-control" id="customerEmail" name="user_email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ข้อความคำสั่งซื้อ (Order Message)</label>
                        <textarea class="form-control" id="customerOrdersMsg" name="orders_msg" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" onclick="saveCustomerInfo()">บันทึก</button>
            </div>
        </div>
    </div>
</div>

<!-- Address Selection Modal -->
<div class="modal fade" id="addressSelectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addressSelectTitle">เลือกที่อยู่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="addressList" class="list-group">
                    <!-- Addresses will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Order Modal -->
<div class="modal fade" id="createOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title"><i data-lucide="shopping-cart" class="me-2"></i>สร้างออเดอร์ใหม่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                
                <!-- Customer Info Section -->
                <div id="customerInfoSection" class="card mb-3">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0"><i data-lucide="user" class="me-2"></i>ข้อมูลลูกค้า</h6>
                    </div>
                    <div class="card-body" id="selectedCustomerInfo">
                        <div class="text-center text-muted py-3">
                            <i data-lucide="user-plus" class="mb-2" style="width:32px;height:32px;"></i>
                            <p class="mb-0">กรุณาเลือกลูกค้า</p>
                        </div>
                    </div>
                </div>

                <!-- Address Section -->
                <div id="addressSection" class="card mb-3 d-none">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0"><i data-lucide="map-pin" class="me-2"></i>ที่อยู่จัดส่ง</h6>
                    </div>
                    <div class="card-body" id="selectedAddressInfo">
                        <!-- Address will be shown here -->
                    </div>
                </div>

                <!-- Cart Section -->
                <div id="cartSection" class="card mb-3 d-none">
                    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i data-lucide="shopping-bag" class="me-2"></i>รายการสินค้า</h6>
                        <button onclick="addProductToNewOrder()" class="btn btn-sm btn-success" id="addProductBtn">
                            <i data-lucide="plus" style="width:14px;height:14px;"></i> เพิ่มสินค้า
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div id="newOrderCartItems" class="table-responsive">
                            <div class="text-center text-muted py-4">
                                <i data-lucide="shopping-cart" style="width:32px;height:32px;"></i>
                                <p class="mb-0 mt-2">ยังไม่มีสินค้าในตะกร้า</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Settings Section -->
                <div id="settingsSection" class="card d-none">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0"><i data-lucide="settings" class="me-2"></i>ตั้งค่าเพิ่มเติม</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">ค่าจัดส่ง (บาท)</label>
                                <input type="number" id="newOrderShipping" class="form-control" 
                                       value="0" min="0" step="0.01" 
                                       onchange="updateNewOrderTotal()">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">หมายเหตุ</label>
                                <input type="text" id="newOrderNotes" class="form-control" 
                                       placeholder="ข้อความถึงลูกค้า...">
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
            <div class="modal-footer bg-light">
                <div class="me-auto">
                    <div class="d-flex align-items-center">
                        <span class="me-2">ยอดรวม:</span>
                        <h4 class="mb-0 text-success fw-bold" id="newOrderGrandTotal">0.00 บาท</h4>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button onclick="submitNewOrder()" class="btn btn-success" id="submitOrderBtn">
                    <i data-lucide="check" class="me-2"></i>สร้างออเดอร์
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Define global variables
const ADMIN_URL = '<?= ADMIN_URL ?>';
const ROOT_URL = '<?= ROOT_URL ?>';

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Lucide icons
    lucide.createIcons();
    
    // Load initial table
    if (typeof loadOrderTable === 'function') {
        loadOrderTable(1);
    } else {
        console.error('loadOrderTable is not defined');
    }
    
    // Setup search with debounce
    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', function() {
        const value = this.value.trim();
        const clearBtn = document.getElementById('clearSearch');
        
        // Show/hide clear button
        if (value) {
            clearBtn.classList.remove('d-none');
            clearBtn.classList.add('d-flex');
        } else {
            clearBtn.classList.add('d-none');
            clearBtn.classList.remove('d-flex');
        }
        
        // Debounce search
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadOrderTable(1, value);
        }, 300);
    });
});

function clearSearch() {
    document.getElementById('searchInput').value = '';
    document.getElementById('clearSearch').classList.add('d-none');
    loadOrderTable(1);
}
</script>

<!-- Load external JS file -->
<script src="<?= ADMIN_ASSETS ?>/js/orders.js<?= $ver ?>"></script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . "/../includes/layout.php"; 
?>
