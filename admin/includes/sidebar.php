<?php
// sidebar.php – YakPho Premium Sidebar with Grouped Menu
?>
<div class="sidebar" id="sidebar">

    <div class="sidebar-brand">
        <img src="<?= ROOT_URL ?>/uploads/shop/Esther.Aroma.Icon.Logo.png" alt="Esther Aroma Logo" class="brand-logo-img" width="34" height="34">
        <div class="brand-text">
            <span class="brand-main">ESTHER AROMA</span>
            <span class="brand-sub">Admin Control Panel</span>
        </div>
    </div>

    <nav class="sidebar-menu">

        <!-- Dashboard -->
        <a href="<?= ADMIN_URL ?>/dashboard/" class="menu-item">
            <i data-lucide="layout-dashboard"></i>
            <span>Dashboard</span>
        </a>

        <?php
        // Helper function to get counts safely and format with commas
        if (!function_exists('getCount')) {
            function getCount($db, $sql) {
                try {
                    $stmt = $db->query($sql);
                    $count = $stmt ? $stmt->fetchColumn() : 0;
                    return number_format($count);
                } catch (Exception $e) {
                    return "0";
                }
            }
        }

        // Fetch counts
            $count_maincat    = getCount($db, "SELECT COUNT(*) FROM maincat WHERE maincat_del = 0");
            $count_productcat = getCount($db, "SELECT COUNT(*) FROM productcat WHERE productcat_del = 0");
            $count_product    = getCount($db, "SELECT COUNT(*) FROM product WHERE product_del = 0");
            $count_orders     = getCount($db, "SELECT COUNT(*) FROM orders WHERE orders_del = 0");
            $count_user       = getCount($db, "SELECT COUNT(*) FROM user WHERE user_del = 0");
            $count_hero       = getCount($db, "SELECT COUNT(*) FROM hero_slides WHERE hero_slides_del = 0");
            $count_blogcat    = getCount($db, "SELECT COUNT(*) FROM blogcat WHERE blogcat_del = 0");
            $count_blog       = getCount($db, "SELECT COUNT(*) FROM blog WHERE blog_del = 0");
            $count_faqscat    = getCount($db, "SELECT COUNT(*) FROM faqscat WHERE faqscat_del = 0");
            $count_faqs       = getCount($db, "SELECT COUNT(*) FROM faqs WHERE faqs_del = 0");
            $count_menu       = getCount($db, "SELECT COUNT(*) FROM menu WHERE menu_del = 0");
        ?>

        <!-- จัดการสินค้า -->
        <div class="menu-group">
            <div class="menu-group-header" onclick="toggleMenuGroup(this)">
                <div class="menu-group-title">
                    <i data-lucide="package"></i>
                    <span>จัดการสินค้า</span>
                </div>
                <i data-lucide="chevron-down" class="menu-group-arrow"></i>
            </div>
            <div class="menu-group-items">
                <a href="<?= ADMIN_URL ?>/maincat/" class="menu-item sub-item">
                    <i data-lucide="folder-open"></i>
                    <span>หมวดหมู่หลัก</span>
                    <span id="badge-maincat" class="badge bg-secondary bg-opacity-10 text-secondary ms-auto badge-sub"><?= $count_maincat ?></span>
                </a>
                <a href="<?= ADMIN_URL ?>/productcat/" class="menu-item sub-item">
                    <i data-lucide="folder-tree"></i>
                    <span>หมวดหมู่ย่อย</span>
                    <span id="badge-productcat" class="badge bg-secondary bg-opacity-10 text-secondary ms-auto badge-sub"><?= $count_productcat ?></span>
                </a>
                <a href="<?= ADMIN_URL ?>/products/" class="menu-item sub-item">
                    <i data-lucide="box"></i>
                    <span>รายการสินค้า</span>
                    <span id="badge-product" class="badge bg-secondary bg-opacity-10 text-secondary ms-auto badge-sub"><?= $count_product ?></span>
                </a>
                <a href="<?= ADMIN_URL ?>/attributes/master.php" class="menu-item sub-item">
                    <i data-lucide="flask-conical"></i>
                    <span>กลิ่น/สูตร (Master)</span>
                </a>
            </div>
        </div>

        <!-- จัดการคำสั่งซื้อ -->
        <div class="menu-group">
            <div class="menu-group-header" onclick="toggleMenuGroup(this)">
                <div class="menu-group-title">
                    <i data-lucide="shopping-cart"></i>
                    <span>จัดการคำสั่งซื้อ</span>
                </div>
                <i data-lucide="chevron-down" class="menu-group-arrow"></i>
            </div>
            <div class="menu-group-items">
                <a href="<?= ADMIN_URL ?>/orders/" class="menu-item sub-item">
                    <i data-lucide="shopping-bag"></i>
                    <span>รายการสั่งซื้อ</span>
                    <span id="badge-orders" class="badge bg-secondary bg-opacity-10 text-secondary ms-auto badge-sub"><?= $count_orders ?></span>
                </a>
                <a href="<?= ADMIN_URL ?>/production/" class="menu-item sub-item">
                    <i data-lucide="factory"></i>
                    <span>ใบสั่งผลิต</span>
                </a>
                <a href="<?= ADMIN_URL ?>/production/history.php" class="menu-item sub-item">
                    <i data-lucide="history"></i>
                    <span>ประวัติใบสั่งผลิต</span>
                </a>
                <a href="<?= ADMIN_URL ?>/transcat/" class="menu-item sub-item">
                    <i data-lucide="truck"></i>
                    <span>บริการขนส่ง</span>
                </a>
            </div>
        </div>

        <!-- รายงานการขาย -->
        <div class="menu-group">
            <div class="menu-group-header" onclick="toggleMenuGroup(this)">
                <div class="menu-group-title">
                    <i data-lucide="bar-chart-3"></i>
                    <span>รายงานระบบ</span>
                </div>
                <i data-lucide="chevron-down" class="menu-group-arrow"></i>
            </div>
            <div class="menu-group-items">
                <a href="<?= ADMIN_URL ?>/reports/" class="menu-item sub-item">
                    <i data-lucide="bar-chart-3"></i>
                    <span>รายงานการขาย</span>
                </a>
                <a href="<?= ADMIN_URL ?>/reports/statistics.php" class="menu-item sub-item">
                    <i data-lucide="trending-up"></i>
                    <span>สถิติการขาย</span>
                </a>
                <a href="<?= ADMIN_URL ?>/reports/customer_purchases.php" class="menu-item sub-item">
                    <i data-lucide="users-round"></i>
                    <span>รายงานพฤติกรรมลูกค้า</span>
                </a>
            </div>
        </div>

        <!-- ระบบสมาชิก & CRM -->
        <div class="menu-group">
            <div class="menu-group-header" onclick="toggleMenuGroup(this)">
                <div class="menu-group-title">
                    <i data-lucide="crown"></i>
                    <span>ระบบสมาชิก &amp; CRM</span>
                </div>
                <i data-lucide="chevron-down" class="menu-group-arrow"></i>
            </div>
            <div class="menu-group-items">
                <a href="<?= ADMIN_URL ?>/users/members.php" class="menu-item sub-item">
                    <i data-lucide="user-check"></i>
                    <span>สมาชิกระดับ &amp; แต้ม (M-Stamp)</span>
                </a>
                <a href="<?= ADMIN_URL ?>/users/affiliates.php" class="menu-item sub-item">
                    <i data-lucide="award"></i>
                    <span>ตัวแทนหมอนวด (Affiliate)</span>
                </a>
                <a href="<?= ADMIN_URL ?>/users/points_log.php" class="menu-item sub-item">
                    <i data-lucide="coins"></i>
                    <span>ประวัติแต้ม (Points Ledger)</span>
                </a>
                <a href="<?= ADMIN_URL ?>/settings/email_templates.php" class="menu-item sub-item">
                    <i data-lucide="mail"></i>
                    <span>เทมเพลตอีเมล (Resend)</span>
                </a>
                <a href="<?= ADMIN_URL ?>/settings/line_broadcast.php" class="menu-item sub-item">
                    <i data-lucide="message-square"></i>
                    <span>LINE บรอดแคสต์ &amp; API</span>
                </a>
            </div>
        </div>

        <!-- จัดการผู้ใช้ -->
        <a href="<?= ADMIN_URL ?>/users/" class="menu-item">
            <i data-lucide="users"></i>
            <span>จัดการผู้ใช้</span>
            <span id="badge-user" class="badge bg-secondary bg-opacity-10 text-secondary ms-auto badge-sub"><?= $count_user ?></span>
        </a>

        <!-- จัดการเนื้อหาเว็บไซต์ (YakPho.com Unified CMS) -->
        <div class="menu-group">
            <div class="menu-group-header" onclick="toggleMenuGroup(this)">
                <div class="menu-group-title">
                    <i data-lucide="layout-template"></i>
                    <span>จัดการเนื้อหาเว็บไซต์</span>
                </div>
                <i data-lucide="chevron-down" class="menu-group-arrow"></i>
            </div>
            <div class="menu-group-items">
                <a href="<?= ADMIN_URL ?>/yakpho/hero.php" class="menu-item sub-item">
                    <i data-lucide="image"></i>
                    <span>แบนเนอร์หน้าแรก (Hero)</span>
                </a>
                <a href="<?= ADMIN_URL ?>/yakpho/formula.php" class="menu-item sub-item">
                    <i data-lucide="flame"></i>
                    <span>สูตรนวดพิเศษ (3 สูตร)</span>
                </a>
                <a href="<?= ADMIN_URL ?>/yakpho/scents.php" class="menu-item sub-item">
                    <i data-lucide="leaf"></i>
                    <span>กลิ่นยอดนิยม (12 กลิ่น)</span>
                </a>
                <a href="<?= ADMIN_URL ?>/yakpho/pricing.php" class="menu-item sub-item">
                    <i data-lucide="tag"></i>
                    <span>เรทราคาส่ง (Wholesale)</span>
                </a>
                <a href="<?= ADMIN_URL ?>/yakpho/brandstory.php" class="menu-item sub-item">
                    <i data-lucide="book-open"></i>
                    <span>เรื่องราวแบรนด์ (Brand Story)</span>
                </a>
                <a href="<?= ADMIN_URL ?>/yakpho/contact.php" class="menu-item sub-item">
                    <i data-lucide="phone"></i>
                    <span>ติดต่อเรา / เกี่ยวกับเรา</span>
                </a>
                <a href="<?= ADMIN_URL ?>/yakpho/gallery.php" class="menu-item sub-item">
                    <i data-lucide="images"></i>
                    <span>แกลเลอรี (Gallery)</span>
                </a>
                <a href="<?= ADMIN_URL ?>/faqscat/" class="menu-item sub-item">
                    <i data-lucide="help-circle"></i>
                    <span>หมวดหมู่คำถาม (FAQ Categories)</span>
                    <span id="badge-faqscat" class="badge bg-secondary bg-opacity-10 text-secondary ms-auto badge-sub"><?= $count_faqscat ?></span>
                </a>
                <a href="<?= ADMIN_URL ?>/faqs/" class="menu-item sub-item">
                    <i data-lucide="message-circle-question"></i>
                    <span>คำถามที่พบบ่อย (FAQs)</span>
                    <span id="badge-faqs" class="badge bg-secondary bg-opacity-10 text-secondary ms-auto badge-sub"><?= $count_faqs ?></span>
                </a>
                <a href="<?= ADMIN_URL ?>/blogcat/" class="menu-item sub-item">
                    <i data-lucide="folder"></i>
                    <span>หมวดหมู่บทความ (Blog Categories)</span>
                    <span id="badge-blogcat" class="badge bg-secondary bg-opacity-10 text-secondary ms-auto badge-sub"><?= $count_blogcat ?></span>
                </a>
                <a href="<?= ADMIN_URL ?>/blog/" class="menu-item sub-item">
                    <i data-lucide="newspaper"></i>
                    <span>บทความ / ข่าวสาร (Blog Posts)</span>
                    <span id="badge-blog" class="badge bg-secondary bg-opacity-10 text-secondary ms-auto badge-sub"><?= $count_blog ?></span>
                </a>
            </div>
        </div>

        <!-- ตั้งค่า -->
        <div class="menu-group">
            <div class="menu-group-header" onclick="toggleMenuGroup(this)">
                <div class="menu-group-title">
                    <i data-lucide="settings"></i>
                    <span>ตั้งค่าร้านค้า</span>
                </div>
                <i data-lucide="chevron-down" class="menu-group-arrow"></i>
            </div>
            <div class="menu-group-items">
                <a href="<?= ADMIN_URL ?>/settings/order_status.php" class="menu-item sub-item">
                    <i data-lucide="list-checks"></i>
                    <span>สถานะสั่งซื้อ</span>
                </a>
                <a href="<?= ADMIN_URL ?>/settings/languages.php" class="menu-item sub-item">
                    <i data-lucide="languages"></i>
                    <span>จัดการภาษา</span>
                </a>
                <a href="<?= ADMIN_URL ?>/sales_channels/" class="menu-item sub-item">
                    <i data-lucide="share-2"></i>
                    <span>ช่องทางขาย</span>
                </a>
                <a href="<?= ADMIN_URL ?>/settings/" class="menu-item sub-item">
                    <i data-lucide="info"></i>
                    <span>ข้อมูลร้านค้า</span>
                </a>
            </div>
        </div>

    </nav>

    <!-- Footer: Theme Toggle -->
    <div class="sidebar-footer">
        <button class="btn-theme-switch" onclick="toggleTheme()" title="สลับโหมดสว่าง/มืด">
            <i id="theme-icon" data-lucide="moon"></i>
        </button>
    </div>
</div>

<script>
// Toggle menu group expand/collapse
function toggleMenuGroup(header) {
    const group = header.parentElement;
    const groupId = getGroupId(header);
    const arrow = header.querySelector('.menu-group-arrow');
    const items = group.querySelector('.menu-group-items');
    
    const wasActive = group.classList.contains('active');
    
    // Close all other groups (accordion behavior)
    document.querySelectorAll('.menu-group').forEach(otherGroup => {
        if (otherGroup !== group && otherGroup.classList.contains('active')) {
            const otherArrow = otherGroup.querySelector('.menu-group-arrow');
            const otherItems = otherGroup.querySelector('.menu-group-items');
            const otherGroupId = getGroupId(otherGroup.querySelector('.menu-group-header'));
            
            otherGroup.classList.remove('active');
            otherArrow.style.transform = 'rotate(0deg)';
            otherItems.style.maxHeight = '0';
            localStorage.setItem(`menu_group_${otherGroupId}`, 'closed');
        }
    });
    
    // Toggle current group
    if (!wasActive) {
        // Open this group
        group.classList.add('active');
        arrow.style.transform = 'rotate(180deg)';
        items.style.maxHeight = items.scrollHeight + 'px';
        localStorage.setItem(`menu_group_${groupId}`, 'open');
    } else {
        // Close this group
        group.classList.remove('active');
        arrow.style.transform = 'rotate(0deg)';
        items.style.maxHeight = '0';
        localStorage.setItem(`menu_group_${groupId}`, 'closed');
    }
}


// Get unique group ID from header text
function getGroupId(header) {
    const titleText = header.querySelector('.menu-group-title span').textContent.trim();
    return titleText.replace(/\s+/g, '_').toLowerCase();
}

// Expand a specific group
function expandGroup(group) {
    const arrow = group.querySelector('.menu-group-arrow');
    const items = group.querySelector('.menu-group-items');
    
    group.classList.add('active');
    arrow.style.transform = 'rotate(180deg)';
    items.style.maxHeight = items.scrollHeight + 'px';
}

// Initialize menu state on page load
document.addEventListener('DOMContentLoaded', function() {
    const currentPath = window.location.pathname;

    /**
     * Determines if a menu item href matches the current page path precisely.
     *
     * - href ending with '/'  → directory/index link → EXACT match only
     * - href ending with '.php' → specific page → must end with that filename
     */
    function isMenuItemActive(currentPath, itemHref) {
        if (!itemHref || itemHref === 'javascript:void(0)') return false;

        let hrefPath = itemHref;
        try {
            const url = new URL(itemHref, window.location.origin);
            hrefPath = url.pathname;
        } catch(e) {}

        const hrefIsDir = hrefPath.endsWith('/');

        if (hrefIsDir) {
            // Directory link: active only when current path is exactly this dir
            const normCurrent = currentPath.endsWith('/') ? currentPath : currentPath + '/';
            return normCurrent === hrefPath;
        } else {
            // Specific .php file: match by path ending
            return currentPath === hrefPath || currentPath.endsWith(hrefPath);
        }
    }
    
    // Find all groups
    document.querySelectorAll('.menu-group').forEach(group => {
        const header = group.querySelector('.menu-group-header');
        const groupId = getGroupId(header);
        const items = group.querySelector('.menu-group-items');
        
        let hasActiveItem = false;
        group.querySelectorAll('.menu-item').forEach(item => {
            const itemHref = item.getAttribute('href');
            if (itemHref) {
                // Special case: production folder has two sub-pages
                if (currentPath.includes('/production/')) {
                    const currentFile = currentPath.split('/').pop() || 'index.php';
                    const itemFile = itemHref.split('/').pop();
                    if (currentFile === itemFile) {
                        item.classList.add('active');
                        hasActiveItem = true;
                    } else if (isMenuItemActive(currentPath, itemHref)) {
                        hasActiveItem = true;
                    }
                } else if (isMenuItemActive(currentPath, itemHref)) {
                    item.classList.add('active');
                    hasActiveItem = true;
                }
            }
        });
        
        if (hasActiveItem) {
            group.classList.add('current-group');
        }

        const savedState = localStorage.getItem(`menu_group_${groupId}`);
        
        if (hasActiveItem || savedState === 'open') {
            expandGroup(group);
            if (!savedState) {
                localStorage.setItem(`menu_group_${groupId}`, 'open');
            }
        } else if (savedState === 'closed') {
            group.classList.remove('active');
            items.style.maxHeight = '0';
        }
    });
    
    // Check standalone menu items (not in groups)
    document.querySelectorAll('.sidebar-menu > .menu-item').forEach(item => {
        const itemHref = item.getAttribute('href');
        if (itemHref && currentPath.includes(itemHref)) {
            item.classList.add('active');
        }
    });
    
    // Initialize Lucide icons
    if (window.lucide) {
        lucide.createIcons();
    }
});
</script>

