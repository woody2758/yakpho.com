<?php
// sidebar.php – YakPho Premium Sidebar with Grouped Menu
?>
<div class="sidebar" id="sidebar">

    <div class="sidebar-brand">
        <div class="yak-logo"></div>
        <div class="brand-text">
            <span class="brand-main">YAKPHO</span>
            <span class="brand-sub">Aroma Admin</span>
        </div>
    </div>

    <nav class="sidebar-menu">

        <!-- Dashboard -->
        <a href="<?= ADMIN_URL ?>/dashboard/" class="menu-item">
            <i data-lucide="layout-dashboard"></i>
            <span>Dashboard</span>
        </a>

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
                </a>
                <a href="<?= ADMIN_URL ?>/productcat/" class="menu-item sub-item">
                    <i data-lucide="folder-tree"></i>
                    <span>หมวดหมู่ย่อย</span>
                </a>
                <a href="<?= ADMIN_URL ?>/products/" class="menu-item sub-item">
                    <i data-lucide="box"></i>
                    <span>รายการสินค้า</span>
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
                </a>
            </div>
        </div>

        <!-- จัดการผู้ใช้ -->
        <a href="<?= ADMIN_URL ?>/users/" class="menu-item">
            <i data-lucide="users"></i>
            <span>จัดการผู้ใช้</span>
        </a>

        <!-- จัดการเนื้อหา -->
        <div class="menu-group">
            <div class="menu-group-header" onclick="toggleMenuGroup(this)">
                <div class="menu-group-title">
                    <i data-lucide="file-text"></i>
                    <span>จัดการเนื้อหา</span>
                </div>
                <i data-lucide="chevron-down" class="menu-group-arrow"></i>
            </div>
            <div class="menu-group-items">
                <a href="<?= ADMIN_URL ?>/aboutus/" class="menu-item sub-item">
                    <i data-lucide="info"></i>
                    <span>About Us</span>
                </a>
                <a href="<?= ADMIN_URL ?>/blogcat/" class="menu-item sub-item">
                    <i data-lucide="folder"></i>
                    <span>Blog Categories</span>
                </a>
                <a href="<?= ADMIN_URL ?>/blog/" class="menu-item sub-item">
                    <i data-lucide="newspaper"></i>
                    <span>Blog Posts</span>
                </a>
                <a href="<?= ADMIN_URL ?>/faqscat/" class="menu-item sub-item">
                    <i data-lucide="help-circle"></i>
                    <span>FAQs Categories</span>
                </a>
                <a href="<?= ADMIN_URL ?>/faqs/" class="menu-item sub-item">
                    <i data-lucide="message-circle-question"></i>
                    <span>FAQs</span>
                </a>
            </div>
        </div>

        <!-- ตั้งค่า -->
        <a href="<?= ADMIN_URL ?>/settings/" class="menu-item">
            <i data-lucide="settings"></i>
            <span>ตั้งค่าร้านค้า</span>
        </a>

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
    
    // Find all groups
    document.querySelectorAll('.menu-group').forEach(group => {
        const header = group.querySelector('.menu-group-header');
        const groupId = getGroupId(header);
        const items = group.querySelector('.menu-group-items');
        
        // Check if any menu item in this group is active
        let hasActiveItem = false;
        group.querySelectorAll('.menu-item').forEach(item => {
            const itemHref = item.getAttribute('href');
            if (itemHref && currentPath.includes(itemHref)) {
                item.classList.add('active');
                hasActiveItem = true;
            }
        });
        
        // Restore state from localStorage or auto-expand if has active item
        const savedState = localStorage.getItem(`menu_group_${groupId}`);
        
        if (hasActiveItem || savedState === 'open') {
            expandGroup(group);
            // Save state if not already saved
            if (!savedState) {
                localStorage.setItem(`menu_group_${groupId}`, 'open');
            }
        } else if (savedState === 'closed') {
            // Keep closed
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

