<?php
/**
 * Site Header Component
 * Reusable header with navigation, cart, language switcher
 */

// Get current page for active navigation
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'th' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    
    <!-- SEO Meta Tags -->
    <title><?= $page_title ?? 'Esther Aroma - Biblical Wellness Products' ?></title>
    <meta name="description" content="<?= $page_description ?? 'Premium wellness products from Biblical ingredients. Esther, Yak Pho, Boaz & Asher brands.' ?>">
    <meta name="keywords" content="<?= $page_keywords ?? 'aromatherapy, wellness, biblical herbs, esther aroma' ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= $page_title ?? 'Esther Aroma' ?>">
    <meta property="og:description" content="<?= $page_description ?? 'Biblical Wellness Products' ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= BASE_URL . $_SERVER['REQUEST_URI'] ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/favicon.png">
    
    <!-- Flag Icons CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/lipis/flag-icons@7.2.3/css/flag-icons.min.css">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/variables.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/base.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/layout.css">
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
            <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/<?= $css ?>.css">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    
    <!-- Header -->
    <header class="site-header" id="site-header">
        <div class="container">
            <div class="header-container">
                
                <!-- Logo -->
                <a href="<?= BASE_URL ?>/" class="site-logo">
                    <div class="site-logo-image" style="background: radial-gradient(circle at 30% 0%, #fdf4e3 0, #b37c2b 40%, #5e3c10 100%); box-shadow: 0 6px 14px rgba(0, 0, 0, 0.35); border-radius: 12px;"></div>
                    <div>
                        <div class="site-logo-text">YAKPHO</div>
                        <div class="site-logo-tagline">Aroma Wellness</div>
                    </div>
                </a>
                
                <!-- Navigation -->
                <nav class="site-nav" id="site-nav">
                    <ul class="nav-menu">
                        <li><a href="<?= BASE_URL ?>/" class="nav-link <?= $current_page == 'index' ? 'active' : '' ?>">หน้าแรก</a></li>
                        <li><a href="<?= BASE_URL ?>/shop/" class="nav-link <?= $current_page == 'shop' ? 'active' : '' ?>">ผลิตภัณฑ์</a></li>
                        <li><a href="<?= BASE_URL ?>/blog/" class="nav-link <?= $current_page == 'blog' ? 'active' : '' ?>">บล็อก</a></li>
                        <li><a href="<?= BASE_URL ?>/about/" class="nav-link <?= $current_page == 'about' ? 'active' : '' ?>">เกี่ยวกับเรา</a></li>
                        <li><a href="<?= BASE_URL ?>/contact/" class="nav-link <?= $current_page == 'contact' ? 'active' : '' ?>">ติดต่อ</a></li>
                    </ul>
                    
                    <!-- Header Actions (Mobile) -->
                    <div class="header-actions" style="display: none;">
                        <a href="<?= BASE_URL ?>/account/login.php" class="btn btn-outline btn-sm">เข้าสู่ระบบ</a>
                    </div>
                </nav>
                
                <!-- Header Actions (Desktop) -->
                <div class="header-actions">
                    <!-- Search -->
                    <button class="header-icon-btn" id="search-btn" aria-label="Search">
                        <i data-lucide="search" width="20" height="20"></i>
                    </button>
                    
                    <!-- Language Switcher -->
                    <?php include __DIR__ . '/language-switcher.php'; ?>
                    
                    <!-- Cart -->
                    <a href="<?= BASE_URL ?>/cart/" class="header-icon-btn" id="cart-btn" aria-label="Shopping Cart">
                        <i data-lucide="shopping-cart" width="20" height="20"></i>
                        <span class="cart-badge" id="cart-count">0</span>
                    </a>
                    
                    <!-- User Account -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="<?= BASE_URL ?>/account/dashboard.php" class="header-icon-btn" aria-label="My Account">
                            <i data-lucide="user" width="20" height="20"></i>
                        </a>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>/account/login.php" class="btn btn-primary btn-sm">เข้าสู่ระบบ</a>
                    <?php endif; ?>
                    
                    <!-- Mobile Menu Toggle -->
                    <button class="mobile-menu-toggle" id="mobile-menu-toggle" aria-label="Toggle Menu">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
                
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <main id="main-content">
