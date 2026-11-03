<?php
require_once __DIR__ . "/includes/config.php";
require_once __DIR__ . "/includes/scanner_auth.php";

// Handle Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['scanner_logged'], $_SESSION['scanner_user_id'], $_SESSION['scanner_name'], $_SESSION['scanner_role']);
    header("Location: " . ADMIN_URL . "/scanner/login");
    exit;
}

$page_title = "Packing Scanner PWA";
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <!-- PWA Meta Tags -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#4f46e5">
    <title><?= $page_title ?></title>
    
    <!-- Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- HTML5 QR Code -->
    <script src="https://unpkg.com/html5-qrcode"></script>

    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f3f4f6; color: #1f2937; margin: 0; padding: 0; display: flex; flex-direction: column; min-height: 100vh; }
        .app-header { background: #4f46e5; color: white; padding: 15px 20px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .app-header h5 { margin: 0; font-weight: 600; font-size: 1.1rem; }
        .app-content { flex: 1; display: flex; flex-direction: column; padding: 20px; }
        
        /* Scanner Container */
        .scanner-container { background: white; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); padding: 15px; margin-bottom: 20px; position: relative; overflow: hidden; }
        #qr-reader { width: 100%; border-radius: 12px; overflow: hidden; }
        
        /* Camera Snapshot UI */
        .camera-container { display: none; background: white; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); padding: 15px; margin-bottom: 20px; text-align: center; }
        #video-feed { width: 100%; aspect-ratio: 1 / 1; object-fit: cover; border-radius: 12px; background: #000; display: block; }
        
        .status-badge { background: #e0e7ff; color: #4338ca; padding: 8px 16px; border-radius: 99px; font-weight: 600; font-size: 0.9rem; text-align: center; margin-bottom: 15px; display: inline-block; width: auto; }
        
        .btn-capture { background: #4f46e5; color: white; border: none; border-radius: 50%; width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; margin: 20px auto 10px; box-shadow: 0 4px 14px rgba(79,70,229,0.4); }
        .btn-capture:active { transform: scale(0.95); }
        .btn-capture i { width: 28px; height: 28px; }
        
        .btn-cancel { background: #f1f5f9; color: #64748b; border: none; border-radius: 99px; padding: 10px 24px; font-weight: 600; margin-top: 10px; }
        
        .current-order-card { background: white; border-radius: 16px; padding: 15px; border-left: 4px solid #10b981; display: none; margin-bottom: 20px; }
        .current-order-card h6 { margin: 0; color: #10b981; font-weight: 700; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .current-order-card .order-no { font-size: 1.25rem; font-weight: 700; margin: 5px 0 0; }
        
        .loading-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.9); z-index: 9999; display: none; flex-direction: column; align-items: center; justify-content: center; }
        .spinner-border { width: 3rem; height: 3rem; color: #4f46e5; margin-bottom: 15px; }

        /* Pending Orders List */
        .pending-orders-container { background: white; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); padding: 15px; margin-top: 30px; margin-bottom: 20px; }
        .pending-orders-container h6 { color: #374151; }
        .pending-order-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
        .pending-order-item:last-child { border-bottom: none; }
        .pending-order-item .order-no { font-weight: 700; color: #1f2937; font-size: 1rem; }
        .pending-order-item .order-date { font-size: 0.8rem; color: #64748b; }
    </style>
</head>
<body>

    <div class="app-header">
        <div class="d-flex align-items-center">
            <h5 class="mb-0">Packing Scanner</h5>
        </div>
        <div class="d-flex align-items-center">
            <span class="text-white small me-3" style="opacity: 0.8;"><i data-lucide="user" style="width: 14px; height: 14px; margin-bottom: 2px;"></i> <?= htmlspecialchars($SCANNER_NAME) ?></span>
            <a href="?logout=1" class="text-white text-decoration-none">
                <i data-lucide="log-out" style="width: 20px; height: 20px;"></i>
            </a>
        </div>
    </div>

    <div class="app-content">
        <div class="text-center">
            <div id="step1-status" class="status-badge">
                <i data-lucide="qr-code" style="width: 16px; height: 16px; margin-right: 5px; margin-bottom: 2px;"></i> สแกน QR Code หน้าบิล
            </div>
        </div>

        <!-- Current Scanned Order -->
        <div id="current-order-info" class="current-order-card">
            <h6>ออเดอร์ที่สแกนแล้ว</h6>
            <div class="order-no" id="scanned-order-no">202605269853</div>
        </div>

        <!-- Start Scanner Button -->
        <div class="text-center" id="scan-btn-container" style="margin-top: 40px;">
            <button id="btn-start-scan" class="btn btn-primary shadow" style="width: 160px; height: 160px; border-radius: 50%; background: #4f46e5; border: none; display: flex; flex-direction: column; align-items: center; justify-content: center; margin: 0 auto; transition: transform 0.2s;">
                <i data-lucide="scan-line" style="width: 64px; height: 64px; margin-bottom: 10px; color: white;"></i>
                <span class="fw-bold fs-5 text-white">เริ่มสแกน</span>
            </button>
            <div class="text-muted small mt-3">กดปุ่มเพื่อเปิดกล้องสแกน QR Code หน้าบิล</div>
        </div>

        <!-- Scanner View -->
        <div id="scanner-section" class="scanner-container" style="display: none;">
            <div id="qr-reader"></div>
            <div class="text-center mt-3 text-muted small">หันกล้องไปที่ QR Code เพื่ออ่านรหัสอัตโนมัติ</div>
        </div>
        
        <!-- Camera View -->
        <div id="camera-section" class="camera-container">
            <h6 class="fw-bold mb-3 text-start">ถ่ายภาพกล่องสินค้า</h6>
            <video id="video-feed" autoplay playsinline></video>
            <button id="btn-capture" class="btn-capture">
                <i data-lucide="camera"></i>
            </button>
            <div class="text-muted small mt-2">กดปุ่มเพื่อถ่ายภาพ</div>
            <button id="btn-cancel" class="btn-cancel">ยกเลิก/สแกนใหม่</button>
            <canvas id="photo-canvas" style="display: none;"></canvas>
        </div>

        <!-- Pending Orders List -->
        <div class="pending-orders-container">
            <h6 class="fw-bold mb-3 d-flex justify-content-between align-items-center">
                <span><i data-lucide="list" style="width: 18px; height: 18px; margin-bottom: 2px; margin-right: 5px;"></i> รายการเตรียมแพ็ค</span>
                <span class="badge bg-primary rounded-pill" id="pending-count">0</span>
            </h6>
            <div id="pending-orders-list">
                <div class="text-center text-muted small py-3">กำลังโหลดข้อมูล...</div>
            </div>
        </div>
    </div>

    <!-- Loading -->
    <div id="loading" class="loading-overlay">
        <div class="spinner-border" role="status"></div>
        <div id="loading-text" class="fw-bold text-primary">กำลังบันทึกข้อมูล...</div>
    </div>

<script>
lucide.createIcons();

let html5QrCode = null;
let isStartingScanner = false;
let scannedOrderNo = null;
let cameraStream = null;

// Initialize QR Scanner
function startQRScanner() {
    if (!document.getElementById('qr-reader')) return;
    if (isStartingScanner) return; // Prevent double trigger
    
    if (html5QrCode && html5QrCode.isScanning) {
        return; // Already scanning
    }

    if (!html5QrCode) {
        html5QrCode = new Html5Qrcode("qr-reader");
    }

    isStartingScanner = true;
    
    // Show loading spinner
    document.getElementById('loading-text').innerText = 'กำลังเปิดกล้องสแกน...';
    document.getElementById('loading').style.display = 'flex';

    html5QrCode.start(
        { facingMode: "environment" }, 
        {
            fps: 10, 
            qrbox: { width: 250, height: 250 }, 
            aspectRatio: 1.0 
        },
        onScanSuccess,
        onScanFailure
    ).then(() => {
        isStartingScanner = false;
        document.getElementById('loading').style.display = 'none'; // Hide spinner
    }).catch(err => {
        isStartingScanner = false;
        document.getElementById('loading').style.display = 'none'; // Hide spinner
        console.error("Camera access error:", err);
        Swal.fire({
            icon: 'error',
            title: 'ไม่สามารถเปิดกล้องได้',
            text: 'กรุณาอนุญาตการเข้าถึงกล้องหลัง',
            confirmButtonColor: '#4f46e5'
        });
        
        // Show button again if failed
        document.getElementById('scanner-section').style.display = 'none';
        document.getElementById('scan-btn-container').style.display = 'block';
    });
}

function onScanSuccess(decodedText, decodedResult) {
    let orderNo = decodedText.trim();
    orderNo = orderNo.replace(/^EST/i, '');
    
    if (!/^\d+$/.test(orderNo)) {
        Swal.fire({
            icon: 'error',
            title: 'QR Code ไม่ถูกต้อง',
            text: 'รหัสที่ได้: ' + decodedText,
            confirmButtonColor: '#4f46e5'
        });
        return;
    }
    
    html5QrCode.stop().then(() => {
        document.getElementById('loading-text').innerText = 'กำลังตรวจสอบออเดอร์...';
        document.getElementById('loading').style.display = 'flex';

        fetch('<?= ADMIN_URL ?>/api/check_order_scan.php?orders_no=' + orderNo)
        .then(response => response.json())
        .then(data => {
            document.getElementById('loading').style.display = 'none';
            if (data.status === 'success') {
                scannedOrderNo = orderNo;
                document.getElementById('scanned-order-no').innerText = orderNo;
                
                document.getElementById('scanner-section').style.display = 'none';
                document.getElementById('step1-status').style.display = 'none';
                document.getElementById('current-order-info').style.display = 'block';
                
                startCamera();
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'ตรวจสอบพบปัญหา',
                    text: data.message,
                    confirmButtonColor: '#4f46e5'
                }).then(() => {
                    resetScanner();
                });
            }
        })
        .catch(err => {
            document.getElementById('loading').style.display = 'none';
            console.error(err);
            Swal.fire({
                icon: 'error',
                title: 'ข้อผิดพลาด',
                text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้',
                confirmButtonColor: '#ef4444'
            }).then(() => {
                resetScanner();
            });
        });
    }).catch(err => {
        console.error("Failed to stop scanner.", err);
    });
}

function onScanFailure(error) {}

// Start Camera for photo
async function startCamera() {
    const video = document.getElementById('video-feed');
    const cameraSection = document.getElementById('camera-section');
    cameraSection.style.display = 'block';
    
    try {
        cameraStream = await navigator.mediaDevices.getUserMedia({ 
            video: { 
                facingMode: { ideal: "environment" },
                width: { ideal: 1280 },
                height: { ideal: 720 }
            },
            audio: false 
        });
        video.srcObject = cameraStream;
    } catch (err) {
        console.error("Error accessing camera:", err);
        Swal.fire({
            icon: 'error',
            title: 'เปิดกล้องไม่ได้',
            text: 'ไม่สามารถเรียกใช้กล้องหลังได้',
            confirmButtonColor: '#4f46e5'
        });
    }
}

function stopCamera() {
    if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
    }
    document.getElementById('camera-section').style.display = 'none';
}

// Capture button click
document.getElementById('btn-capture').addEventListener('click', () => {
    const video = document.getElementById('video-feed');
    const canvas = document.getElementById('photo-canvas');
    
    // Determine the square size (take the smallest dimension)
    const minDim = Math.min(video.videoWidth, video.videoHeight);
    
    // Calculate crop starting points to center the crop
    const startX = (video.videoWidth - minDim) / 2;
    const startY = (video.videoHeight - minDim) / 2;
    
    // Set canvas dimensions to the square size
    canvas.width = minDim;
    canvas.height = minDim;
    
    // Draw video frame to canvas, cropping the center
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, startX, startY, minDim, minDim, 0, 0, minDim, minDim);
    
    // Resize & convert to webp (max width 800px)
    const MAX_WIDTH = 800;
    let width = canvas.width;
    let height = canvas.height;
    
    if (width > MAX_WIDTH) {
        height = MAX_WIDTH; // It's a square, so height = width
        width = MAX_WIDTH;
    }
    
    const resizedCanvas = document.createElement('canvas');
    resizedCanvas.width = width;
    resizedCanvas.height = height;
    const resizedCtx = resizedCanvas.getContext('2d');
    resizedCtx.drawImage(canvas, 0, 0, width, height);

    
    const webpData = resizedCanvas.toDataURL('image/webp', 0.8);
    
    // Stop camera and upload
    stopCamera();
    uploadData(webpData);
});

// Cancel button
document.getElementById('btn-cancel').addEventListener('click', () => {
    stopCamera();
    resetScanner();
});

// Reset to Step 1
function resetScanner() {
    document.getElementById('current-order-info').style.display = 'none';
    document.getElementById('scanner-section').style.display = 'none';
    document.getElementById('scan-btn-container').style.display = 'block';
    document.getElementById('step1-status').style.display = 'inline-block';
    scannedOrderNo = null;
    
    // Stop QR Scanner if running
    if (html5QrCode) {
        try { html5QrCode.stop(); } catch(e) {}
    }
}

// Upload Data API Call
function uploadData(base64Image) {
    document.getElementById('loading-text').innerText = 'กำลังบันทึกข้อมูล...';
    document.getElementById('loading').style.display = 'flex';
    
    const formData = new FormData();
    formData.append('orders_no', scannedOrderNo);
    formData.append('image_data', base64Image);
    
    fetch('<?= ADMIN_URL ?>/api/pack_order.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('loading').style.display = 'none';
        
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ!',
                text: 'บันทึกภาพแพ็คกิ้งเรียบร้อยแล้ว',
                confirmButtonColor: '#10b981',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                resetScanner();
                loadPendingOrders();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: data.message || 'ไม่สามารถบันทึกได้',
                confirmButtonColor: '#ef4444'
            }).then(() => {
                resetScanner();
            });
        }
    })
    .catch(error => {
        document.getElementById('loading').style.display = 'none';
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'ระบบมีปัญหา',
            text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้',
            confirmButtonColor: '#ef4444'
        }).then(() => {
            resetScanner();
        });
    });
}

function loadPendingOrders() {
    fetch('<?= ADMIN_URL ?>/api/get_pending_pack_orders.php')
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const listContainer = document.getElementById('pending-orders-list');
            const countBadge = document.getElementById('pending-count');
            
            countBadge.innerText = data.data.length;
            
            if (data.data.length === 0) {
                listContainer.innerHTML = '<div class="text-center text-muted small py-3">ไม่มีรายการเตรียมแพ็ค</div>';
                return;
            }
            
            let html = '';
            data.data.forEach(order => {
                html += `
                    <div class="pending-order-item">
                        <div class="order-no">${order.orders_no}</div>
                        <div class="order-date">${order.orders_date}</div>
                    </div>
                `;
            });
            listContainer.innerHTML = html;
        }
    })
    .catch(error => console.error('Error loading pending orders:', error));
}

// Start everything when DOM is loaded
window.addEventListener('load', () => {
    loadPendingOrders();
    // startQRScanner(); 
});

// Big Button Click
document.getElementById('btn-start-scan').addEventListener('click', () => {
    document.getElementById('scan-btn-container').style.display = 'none';
    document.getElementById('scanner-section').style.display = 'block';
    // Add small delay to ensure DOM is fully rendered before starting camera
    setTimeout(() => {
        startQRScanner();
    }, 150);
});

</script>

<!-- Add custom PWA CSS fixes for html5-qrcode library -->
<style>
    #qr-reader { border: none !important; }
    #qr-reader__scan_region { background: #f8fafc; }
    #qr-reader__dashboard_section_csr button { background: #4f46e5 !important; color: white !important; border: none !important; padding: 8px 16px !important; border-radius: 99px !important; font-weight: 600 !important; cursor: pointer; }
    #qr-reader a { display: none !important; } /* hide powered by */
</style>
</body>
</html>
