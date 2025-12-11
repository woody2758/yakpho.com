<?php
// layout.php – YakPho Admin Premium Layout

if (!isset($page_title)) { $page_title = "YakPho Admin"; }
if (!isset($content))    { $content = ""; }

// ตรงนี้ "ไม่" include config/auth แล้ว
// ให้แต่ละหน้าที่เรียก layout.php เป็นคน include เอง
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($page_title) ?> – YakPho Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Global CSS -->
    <link rel="stylesheet" href="<?= ADMIN_ASSETS ?>/css/admin.css?v=<?= $ver ?>">
    <link rel="stylesheet" href="<?= ADMIN_ASSETS ?>/css/layout.css?v=<?= $ver ?>">
    <link rel="stylesheet" href="<?= ADMIN_ASSETS ?>/css/components.css?v=<?= $ver ?>">
    <link rel="stylesheet" href="<?= ADMIN_ASSETS ?>/css/sweetalert.css?v=<?= $ver ?>">
    <link rel="stylesheet" href="<?= ADMIN_ASSETS ?>/css/swal-fix.css?v=<?= $ver ?>">
    <link rel="stylesheet" href="<?= ADMIN_ASSETS ?>/css/mobile-fix.css?v=<?= $ver ?>">
    <link rel="stylesheet" href="<?= ADMIN_ASSETS ?>/css/sortable.css?v=<?= $ver ?>">

    <!-- Dashboard CSS (เฉพาะหน้าที่ต้องใช้) -->
    <?php if (!empty($include_dashboard_css)): ?>
        <link rel="stylesheet" href="<?= ADMIN_ASSETS ?>/css/dashboard.css?v=<?= $ver ?>">
    <?php endif; ?>

    <!-- jQuery (Required for Summernote) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Icon Library -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Chart.js (ถ้าหน้านั้นตั้ง $include_chart = true) -->
    <?php if (!empty($include_chart)): ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>

    <!-- Tom Select (Custom Dropdown) -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    
    <!-- Summernote WYSIWYG Editor (Lite version works best with BS5) -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    
    <!-- Cropper.js for Image Cropping -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" rel="stylesheet">
    
    <!-- SortableJS for Drag & Drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
</head>

<body class="admin-layout">

    <!-- Sidebar -->
    <?php include __DIR__ . "/sidebar.php"; ?>

    <!-- Main Area -->
    <div class="main">

        <!-- Topbar -->
        <?php include __DIR__ . "/topbar.php"; ?>

        <!-- Page Content -->
        <main class="container-admin">
            <?= $content ?>
        </main>
    </div>

    <!-- Bootstrap Bundle JS (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Summernote JS -->
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    
    <!-- Cropper.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

    <!-- Language Tabs Manager (with LocalStorage) -->
    <script src="<?= ADMIN_ASSETS ?>/js/language-tabs.js?v=<?= $ver ?>"></script>

    <!-- Theme Toggle JS -->
    <script src="<?= ADMIN_ASSETS ?>/js/theme.js?v=<?= $ver ?>"></script>

    <!-- Page Specific Scripts -->
    <?php if (!empty($extra_scripts)) echo $extra_scripts; ?>

    <!-- Global Page Loader -->
    <div id="page-loader">
        <div class="loader-spinner"></div>
        <div class="loader-text">
            LOADING<span class="loading-dots">
                <span class="dot">.</span>
                <span class="dot">.</span>
                <span class="dot">.</span>
            </span>
        </div>
    </div>
    
    <style>
    /* Page loader solid background */
    #page-loader {
        background-color: #ffffff !important; /* ทึบสีขาว */
    }
    
    [data-theme="dark"] #page-loader {
        background-color: #1a1d23 !important; /* ทึบสีดำ (dark mode) */
    }
    
    /* Animated loading dots */
    .loading-dots {
        display: inline-block;
    }
    
    .loading-dots .dot {
        animation: dotPulse 1.4s infinite ease-in-out;
        opacity: 0;
    }
    
    .loading-dots .dot:nth-child(1) {
        animation-delay: 0s;
    }
    
    .loading-dots .dot:nth-child(2) {
        animation-delay: 0.2s;
    }
    
    .loading-dots .dot:nth-child(3) {
        animation-delay: 0.4s;
    }
    
    @keyframes dotPulse {
        0%, 60%, 100% {
            opacity: 0;
        }
        30% {
            opacity: 1;
        }
    }
    </style>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        // 1. Create icons
        lucide.createIcons();

        // 2. Restore sidebar state from localStorage (Desktop only)
        if (window.innerWidth > 991) {
            const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (sidebarCollapsed) {
                document.body.classList.add('sidebar-collapsed');
            }
        }

        // 3. Active menu
        const path = window.location.pathname;
        document.querySelectorAll('.sidebar .menu-item').forEach(link => {
            if (link.href.includes(path)) {
                link.classList.add('active');
            }
        });

        // 4. Sidebar Tooltip
        const body = document.body;
        let tooltipEl = null;

        function showTooltip(e) {
            if (!body.classList.contains('sidebar-collapsed')) return;
            const item  = e.currentTarget;
            const label = item.querySelector('span');
            if (!label) return;

            const text = label.textContent.trim();
            if (!text) return;

            if (!tooltipEl) {
                tooltipEl = document.createElement('div');
                tooltipEl.className = 'sidebar-tooltip';
                document.body.appendChild(tooltipEl);
            }
            tooltipEl.textContent = text;

            const rect = item.getBoundingClientRect();
            tooltipEl.style.top  = (rect.top + rect.height / 2) + 'px';
            tooltipEl.style.left = (rect.right + 8) + 'px';
            tooltipEl.style.transform = 'translateY(-50%)';
            tooltipEl.style.display   = 'block';
        }

        function hideTooltip() {
            if (tooltipEl) tooltipEl.style.display = 'none';
        }

        document.querySelectorAll('.sidebar .menu-item').forEach(item => {
            item.addEventListener('mouseenter', showTooltip);
            item.addEventListener('mouseleave', hideTooltip);
        });

        // 5. Global Page Loader Logic
        const loader = document.getElementById('page-loader');
        
        // Hide loader if page loaded from cache (bfcache)
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                loader.style.display = 'none';
            }
        });
        
        // Show loader only on real page navigation (not AJAX)
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a');
            if (!link) return;
            
            // ✅ Exclude Summernote editor links
            if (link.closest('.note-editor')) {
                loader.style.display = 'none';
                return;
            }
            
            // Exclude AJAX-handled links (pagination, sort, no-loader)
            if (link.classList.contains('page-link') || 
                link.classList.contains('sort-link') ||
                link.classList.contains('no-loader')) {
                // Hide loader immediately if it's showing
                loader.style.display = 'none';
                return; // Don't show loader for AJAX links
            }
            
            // Show loader for real navigation
            if (link.href && !link.target && !link.download) {
                loader.style.display = 'flex';
            }
        }, true); // Use capture phase to run before other handlers

        // Toggle Sidebar Function (Desktop: save to localStorage, Mobile: just toggle)
        window.toggleSidebar = function() {
            const isMobile = window.innerWidth <= 991;
            
            if (isMobile) {
                // Mobile: Toggle sidebar open/close
                const sidebar = document.querySelector('.sidebar');
                sidebar.classList.toggle('open');
                document.body.classList.toggle('sidebar-open');
            } else {
                // Desktop: Toggle collapsed state and save to localStorage
                document.body.classList.toggle('sidebar-collapsed');
                const isCollapsed = document.body.classList.contains('sidebar-collapsed');
                localStorage.setItem('sidebarCollapsed', isCollapsed);
            }
        };

        // Mobile sidebar toggle
        const sidebarToggle = document.querySelector('.btn-sidebar-toggle');
        const sidebar = document.querySelector('.sidebar');
        
        if (sidebarToggle && sidebar) {
            // Close sidebar when clicking overlay (mobile only)
            document.body.addEventListener('click', function(e) {
                if (window.innerWidth <= 991 && 
                    document.body.classList.contains('sidebar-open') && 
                    !sidebar.contains(e.target) && 
                    !sidebarToggle.contains(e.target)) {
                    sidebar.classList.remove('open');
                    document.body.classList.remove('sidebar-open');
                }
            });
        }


    });
    </script>
</body>
</html>
