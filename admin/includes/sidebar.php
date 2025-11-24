<?php
// sidebar.php – YakPho Premium Sidebar
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

        <a href="<?= ADMIN_URL ?>/dashboard.php" class="menu-item">
            <i data-lucide="layout-dashboard"></i>
            <span>Dashboard</span>
        </a>

        <a href="<?= ADMIN_URL ?>/product_type/list.php" class="menu-item">
            <i data-lucide="boxes"></i>
            <span>ประเภทสินค้า</span>
        </a>

        <a href="<?= ADMIN_URL ?>/productcat/list.php" class="menu-item">
            <i data-lucide="folder-tree"></i>
            <span>หมวดสินค้า</span>
        </a>

        <a href="<?= ADMIN_URL ?>/product/list.php" class="menu-item">
            <i data-lucide="package"></i>
            <span>สินค้า</span>
        </a>

        <a href="<?= ADMIN_URL ?>/orders/list.php" class="menu-item">
            <i data-lucide="shopping-bag"></i>
            <span>คำสั่งซื้อ</span>
        </a>

        <a href="<?= ADMIN_URL ?>/users/" class="menu-item">
            <i data-lucide="users"></i>
            <span>ผู้ใช้ระบบ</span>
        </a>

    </nav>

    <!-- Footer: Theme Toggle -->
    <div class="sidebar-footer">
        <button class="btn-theme-switch" onclick="toggleTheme()" title="สลับโหมดสว่าง/มืด">
            <i id="theme-icon" data-lucide="moon"></i>
        </button>
    </div>
</div>
