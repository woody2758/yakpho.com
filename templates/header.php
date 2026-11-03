<?php

$currentLang = $SITE['lang'] ?? 'th';

$contactInfo = $SITE['contact'] ?? [];

$cartSummary = $SITE['cart'] ?? ['total_qty' => 0, 'wholesale_kg_rate' => 690, 'items' => [], 'subtotal' => 0, 'wholesale_savings' => 0, 'grand_total' => 0];

$currentRoute = trim((string)($route ?? ''), '/');

$isRtl = in_array($currentLang, ['ar', 'he'], true);

$canonicalUrl = ROOT_URL . ($currentLang !== 'th' ? '/' . $currentLang : '') . ($currentRoute !== '' ? '/' . $currentRoute : '');

$ver = time();

$savedTheme = $_COOKIE['yakpho_theme'] ?? 'dark';

?>

<!DOCTYPE html>

<html lang="<?= htmlspecialchars($currentLang) ?>" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>" data-theme="<?= htmlspecialchars($savedTheme) ?>">

<head>
    <!-- ✅ Google Analytics 4 (GA4) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-H8X1HT3GLQ"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-H8X1HT3GLQ');
    </script>

    <script>

    (function() {

        var t = localStorage.getItem('yakpho_theme') || '<?= htmlspecialchars($savedTheme) ?>';

        document.documentElement.setAttribute('data-theme', t);

        if (!document.cookie.includes('yakpho_theme=')) {

            document.cookie = "yakpho_theme=" + t + "; path=/; max-age=31536000; SameSite=Lax";

        }

    })();

    </script>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">

    <title><?= htmlspecialchars($SEO['title'] ?? 'Yak Pho Aroma | Thai Herbal Spa & Wellness') ?></title>

    <meta name="description" content="<?= htmlspecialchars($SEO['description'] ?? 'ผลิตภัณฑ์สมุนไพรและสปาอโรม่า ยักษ์โพธิ์ โดย บริษัท เอสเธอร์ อโรม่า จำกัด ผู้เชี่ยวชาญด้านบาล์มนวดสมุนไพรสูตรเข้มข้นมาตรฐานส่งออก') ?>">

    <meta name="keywords" content="<?= htmlspecialchars($SEO['keywords'] ?? 'YakPho, Yak Pho Aroma, ยักษ์โพธิ์, เอสเธอร์ อโรม่า, บาล์มนวดสมุนไพร, น้ำมันนวดสปา, เรทส่งร้านนวด') ?>">

    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">



    <!-- Canonical & Multilingual Hreflang SEO -->

    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">

    <link rel="alternate" hreflang="x-default" href="<?= htmlspecialchars(ROOT_URL . '/' . $currentRoute) ?>">

    <?php foreach (($SITE['languages'] ?? []) as $hl): ?>

        <link rel="alternate" hreflang="<?= htmlspecialchars($hl['code']) ?>" href="<?= htmlspecialchars(ROOT_URL . '/' . $hl['code'] . '/' . $currentRoute) ?>">

    <?php endforeach; ?>



    <!-- Social & OpenGraph -->

    <meta property="og:type" content="website">

    <meta property="og:title" content="<?= htmlspecialchars($SEO['title'] ?? 'Yak Pho Aroma') ?>">

    <meta property="og:description" content="<?= htmlspecialchars($SEO['description'] ?? '') ?>">

    <meta property="og:image" content="<?= htmlspecialchars($SEO['image'] ?: (ROOT_URL . '/catalog/assets/img/brand_story.webp')) ?>">

    <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl) ?>">

    <meta property="og:site_name" content="Yak Pho Aroma">

    <meta name="twitter:card" content="summary_large_image">

    <meta name="twitter:title" content="<?= htmlspecialchars($SEO['title'] ?? 'Yak Pho Aroma') ?>">

    <meta name="twitter:description" content="<?= htmlspecialchars($SEO['description'] ?? '') ?>">

    <meta name="twitter:image" content="<?= htmlspecialchars($SEO['image'] ?: (ROOT_URL . '/catalog/assets/img/brand_story.webp')) ?>">



    <!-- Favicon & Touch Icon (Esther Aroma Official Branding) -->

    <link rel="icon" type="image/webp" href="<?= URL_PATH ?>/assets/img/favicons/favicon.webp">

    <link rel="icon" type="image/png" sizes="32x32" href="<?= URL_PATH ?>/assets/img/favicons/favicon-32x32.png">

    <link rel="icon" type="image/png" sizes="16x16" href="<?= URL_PATH ?>/assets/img/favicons/favicon-16x16.png">

    <link rel="apple-touch-icon" sizes="180x180" href="<?= URL_PATH ?>/assets/img/favicons/apple-touch-icon.png">

    <link rel="shortcut icon" href="<?= URL_PATH ?>/favicon.ico">



    <!-- Preload Primary LCP Assets (Zero CDN, 100% Localhost / Server) -->

    <link rel="preload" as="image" href="<?= URL_PATH ?>/catalog/assets/img/YakPhoAromaLogo.webp" fetchpriority="high">



    <!-- Local High-Performance Typography & App CSS -->

    <link rel="stylesheet" href="<?= URL_PATH ?>/assets/fonts/fonts.css?v=<?= $ver ?>">

    <link rel="stylesheet" href="<?= URL_PATH ?>/assets/css/yakpho.css?v=<?= $ver ?>">



    <!-- ============================================================

         AEO & GEO Structured Entities (Google AI Overviews & LLMs)

         ============================================================ -->

    <script type="application/ld+json">

    {

        "@context": "https://schema.org",

        "@graph": [

            {

                "@type": "HealthAndBeautyBusiness",

                "@id": "<?= ROOT_URL ?>/#organization",

                "name": "Yak Pho Aroma",

                "alternateName": ["ยักษ์โพธิ์ อโรมา", "Esther Aroma Co., Ltd.", "บริษัท เอสเธอร์ อโรม่า จำกัด"],

                "url": "<?= ROOT_URL ?>",

                "logo": "<?= ROOT_URL ?>/catalog/assets/img/YakPhoAromaLogo.webp",

                "image": "<?= ROOT_URL ?>/catalog/assets/img/brand_story.webp",

                "description": "ผู้ผลิตและจำหน่ายยาหม่องกิโล ยาหม่องถุง บาล์มนวดสมุนไพรไทยและสปาอโรม่าสูตรเข้มข้น 3 สูตร 12 กลิ่น สำหรับร้านนวดและสปา",

                "telephone": "<?= htmlspecialchars($contactInfo['phone'] ?? '+66800617073') ?>",

                "email": "<?= htmlspecialchars($contactInfo['email'] ?? 'thaiherbfc@gmail.com') ?>",

                "priceRange": "฿฿",

                "paymentAccepted": ["Cash", "Credit Card", "PromptPay", "Bank Transfer"],

                "currenciesAccepted": "THB",

                "address": {

                    "@type": "PostalAddress",

                    "streetAddress": "<?= htmlspecialchars($contactInfo['address'] ?? 'Bangkok, Thailand') ?>",

                    "addressCountry": "TH"

                },

                "sameAs": [

                    "https://www.facebook.com/yakphoaroma",

                    "https://line.me/R/ti/p/@yakpho"

                ]

            },

            {

                "@type": "WebSite",

                "@id": "<?= ROOT_URL ?>/#website",

                "url": "<?= ROOT_URL ?>",

                "name": "Yak Pho Aroma",

                "potentialAction": {

                    "@type": "SearchAction",

                    "target": "<?= ROOT_URL ?>/<?= $currentLang ?>/products?q={search_term_string}",

                    "query-input": "required name=search_term_string"

                }

            },

            {

                "@type": "FAQPage",

                "@id": "<?= ROOT_URL ?>/#faq",

                "mainEntity": [

                    {

                        "@type": "Question",

                        "name": "บาล์มนวดสมุนไพร ยักษ์โพธิ์ มีสูตรอะไรบ้าง?",

                        "acceptedAnswer": {

                            "@type": "Answer",

                            "text": "ยักษ์โพธิ์มี 3 สูตรหลักมาตรฐานร้านนวดสปา: 1) สูตรร้อน พริก-ไพล สำหรับคลายปวดเมื่อยลึกเส้นตึง 2) สูตรเย็น ยูคาลิปตัส-เปปเปอร์มินต์ ผ่อนคลายกล้ามเนื้อ สดชื่นเบาสบาย 3) สูตรนวดตัว อโรม่าสปา ลื่นมือ ซึมไว ไม่เหนียวเหนอะหนะ พร้อม 12 กลิ่นอโรม่าซิกเนเจอร์"

                        }

                    },

                    {

                        "@type": "Question",

                        "name": "สั่งซื้อยาหม่องกิโล ยาหม่องถุง ราคาส่งสำหรับร้านนวดและสปา ขั้นต่ำเท่าไร?",

                        "acceptedAnswer": {

                            "@type": "Answer",

                            "text": "สั่งซื้อยาหม่องกิโล ราคาส่งเริ่มต้นเพียง 6 กก. ในราคาเพียง ฿590.-/กก. (จากปกติ ฿690.-) สามารถคละได้ครบทั้ง 3 สูตร และ 12 กลิ่น บรรจุถุงซีลสุญญากาศอย่างดี ปรุงสดใหม่ทุกออเดอร์"

                        }

                    },

                    {

                        "@type": "Question",

                        "name": "มีบริการจัดส่งฟรีและการชำระเงินอย่างไร?",

                        "acceptedAnswer": {

                            "@type": "Answer",

                            "text": "จัดส่งฟรีทั่วประเทศเมื่อสั่งซื้อยอดรวม ฿990 ขึ้นไป จัดส่งด่วนผ่าน Flash Express, Kerry, ไปรษณีย์ไทย มีบริการชำระผ่าน PromptPay QR Code, โอนธนาคาร และมีบริการเก็บเงินปลายทาง (COD)"

                        }

                    }

                ]

            }

        ]

    }

    </script>

</head>

<body data-base-url="<?= htmlspecialchars(URL_PATH) ?>">



    <!-- 1. APP HEADER BAR (Thaimart App Layout) -->

    <header class="app-header" id="appHeader">

        <div class="yp-container">

            <div class="app-header-inner">

                <!-- Brand Logo & Name -->

                <a href="<?= URL_PATH ?>/<?= $currentLang ?>/" class="app-brand" aria-label="Yak Pho Aroma Home">

                    <img src="<?= URL_PATH ?>/catalog/assets/img/YakPhoAromaLogo.webp" alt="Yak Pho Logo" class="app-brand-logo" width="38" height="38">

                    <div class="app-brand-text">

                        <span class="app-brand-name">YAK PHO</span>

                        <span class="app-brand-tagline">AROMA SPA</span>

                    </div>

                </a>



                <!-- Integrated Search Bar -->

                <div class="app-search-box">

                    <div class="app-search-input-wrap">

                        <svg class="app-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>

                        <input type="search" id="headerSearchInput" class="app-search-input" placeholder="<?= $currentLang === 'th' ? 'ค้นหาบาล์ม, สปาอโรม่า...' : 'Search balms, aroma oils, spa sets...' ?>" autocomplete="off">

                        <button type="button" class="app-search-action-btn" onclick="const q=document.getElementById('headerSearchInput').value; if(q) window.location.href='<?= URL_PATH ?>/<?= $currentLang ?>/products?q='+encodeURIComponent(q);" aria-label="Search">

                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>

                        </button>

                    </div>



                    <!-- Autocomplete / Quick Search Dropdown -->

                    <div class="search-dropdown-menu" id="searchDropdownMenu">

                        <div style="font-size: 0.76rem; font-weight: 700; color: var(--yp-text-muted); margin-bottom: 4px;">คำค้นหายอดนิยม</div>

                        <div class="search-quick-tags">

                            <a href="<?= URL_PATH ?>/<?= $currentLang ?>/products?cat_id=32" class="search-quick-tag">สูตรร้อน 1 กก.</a>

                            <a href="<?= URL_PATH ?>/<?= $currentLang ?>/products?cat_id=38" class="search-quick-tag">สูตรเย็น 1 กก.</a>

                            <a href="<?= URL_PATH ?>/<?= $currentLang ?>/products?cat_id=40" class="search-quick-tag">สูตรนวดตัว 1 กก.</a>

                            <a href="<?= URL_PATH ?>/<?= $currentLang ?>/products?q=40g" class="search-quick-tag">ตลับ 40 กรัม</a>

                            <a href="<?= URL_PATH ?>/<?= $currentLang ?>/products?cat_id=36" class="search-quick-tag">พิมเสนน้ำ</a>

                            <a href="<?= URL_PATH ?>/<?= $currentLang ?>/corporate-gifts" class="search-quick-tag">เรทส่งร้านนวด 6 กก.</a>

                        </div>

                    </div>

                </div>



                <!-- Action Tools: Multi-Channel Chat + Cart -->

                <div class="app-header-tools">

                    <div class="header-chat-dropdown-wrap">

                        <button type="button" class="header-tool-btn" id="btnHeaderChatToggle" title="ติดต่อเรา (LINE / Messenger)" aria-label="Contact Channels" onclick="toggleHeaderChatDropdown(event)">

                            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>

                        </button>

                        <div class="header-chat-dropdown" id="headerChatDropdown">

                            <a href="https://lin.ee/vyUtLZ6" target="_blank" rel="noopener" class="header-chat-circle header-chat-line" title="ติดต่อผ่าน LINE Official">

                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>

                            </a>

                            <a href="https://m.me/YakPho.Aroma" target="_blank" rel="noopener" class="header-chat-circle header-chat-messenger" title="ติดต่อผ่าน Facebook Messenger">

                                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.477 2 2 6.145 2 11.258c0 2.908 1.455 5.503 3.735 7.16V22l3.415-1.874c.91.252 1.872.389 2.85.389 5.523 0 10-4.145 10-9.257C22 6.145 17.523 2 12 2zm1.066 12.441-2.584-2.756-5.045 2.756 5.549-5.892 2.65 2.756 4.978-2.756-5.548 5.892z"/></svg>

                            </a>

                        </div>

                    </div>



                    <button type="button" class="header-tool-btn" data-action="open-cart" title="ตะกร้าสินค้า" aria-label="Cart">

                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>

                        <span class="header-badge" data-cart-badge style="<?= ((int)($cartSummary['total_qty'] ?? 0) > 0) ? 'display:flex;' : 'display:none;' ?>">

                            <?= (int)($cartSummary['total_qty'] ?? 0) ?>

                        </span>

                    </button>

                </div>

            </div>

        </div>

    </header>



    <!-- 2. SLIDE-UP CART DRAWER & BOTTOM SHEET -->

    <div class="cart-drawer-overlay" id="cartDrawerOverlay">

        <div class="cart-drawer-panel">

            <div class="cart-drawer-header">

                <div class="cart-drawer-title">

                    <?= $currentLang === 'th' ? 'ตะกร้าสินค้าของคุณ' : 'Your Shopping Cart' ?>

                </div>

                <button type="button" class="cart-drawer-close" data-action="close-cart" aria-label="Close Cart">

                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>

                </button>

            </div>



            <div class="cart-drawer-body">

                <!-- Dynamic Tier Alert Banner -->

                <div class="cart-tier-banner" id="cartDrawerTierNotice" style="display:none;"></div>

                <div class="cart-items-list" id="cartDrawerItemsList"></div>

            </div>



            <div class="cart-drawer-footer">

                <div class="cart-summary-line">

                    <span>ยอดรวมสินค้า</span>

                    <strong id="cartDrawerSubtotal">฿0.00</strong>

                </div>

                <div class="cart-summary-line">

                    <span id="cartDrawerShippingLabel">การจัดส่ง (ตามน้ำหนัก)</span>

                    <span id="cartDrawerShipping" style="color:var(--yp-green); font-weight:600;">คำนวณตามน้ำหนัก</span>

                </div>

                <div class="cart-summary-line cart-summary-total">

                    <span>ยอดชำระสุทธิ</span>

                    <span id="cartDrawerTotal">฿0.00</span>

                </div>

                <a href="<?= URL_PATH ?>/<?= $currentLang ?>/checkout" class="btn-checkout-primary">

                    <span>ดำเนินการสั่งซื้อ (Checkout)</span>

                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>

                </a>

            </div>

        </div>

    </div>



    <!-- MAIN APP WRAPPER -->

    <main id="mainAppContent">

