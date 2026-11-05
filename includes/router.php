<?php
declare(strict_types=1);

// Auto-login Member via 1-year Remember Cookie
if (empty($_SESSION['user_id']) && !empty($_COOKIE['yakpho_member_remember']) && isset($db)) {
    try {
        $raw = base64_decode($_COOKIE['yakpho_member_remember']);
        $parts = explode(':', $raw);
        if (count($parts) === 2) {
            $cUserId = (int)$parts[0];
            $cHash = $parts[1];
            if ($cUserId > 0) {
                $stmtM = $db->prepare("SELECT * FROM user WHERE user_id = ? AND user_del = 0 LIMIT 1");
                $stmtM->execute([$cUserId]);
                $uM = $stmtM->fetch(PDO::FETCH_ASSOC);
                if ($uM) {
                    $expectedHash = hash_hmac('sha256', (string)$uM['user_id'], 'yakpho_member_secret_' . ($uM['user_password'] ?? ''));
                    if (hash_equals($expectedHash, $cHash)) {
                        $_SESSION['user_id']           = (int)$uM['user_id'];
                        $_SESSION['user_name']         = $uM['user_name'] ?: $uM['user_username'];
                        $_SESSION['user_username']     = $uM['user_username'];
                        $_SESSION['user_email']        = $uM['user_email'];
                        $_SESSION['user_mobile']       = $uM['user_mobile'] ?: $uM['user_tel'];
                        $_SESSION['member_tier']       = $uM['member_tier'] ?: 'general';
                        $_SESSION['m_stamp_points']    = (int)$uM['m_stamp_points'];
                        $_SESSION['line_user_id']      = $uM['line_user_id'];
                        $_SESSION['line_display_name'] = $uM['line_display_name'];
                        $_SESSION['line_picture_url']  = $uM['line_picture_url'];
                        $_SESSION['affiliate_code']    = $uM['affiliate_code'];
                    }
                }
            }
        }
    } catch (Exception $e) {}
}


/**
 * ============================================================
 * YakPho & Esther Aroma — Master Frontend Router
 * ============================================================
 * Prepares all database-driven View data via includes/frontend_data.php
 * before including the View templates (templates/*.php).
 */

require_once __DIR__ . '/frontend_data.php';
require_once __DIR__ . '/member_helper.php';
track_affiliate_visit($db);

$route = trim((string)($_GET['route'] ?? ''), '/');
$segments = $route === '' ? [''] : explode('/', $route);
$base = $segments[0] ?? '';
$page_id = $segments[1] ?? null;

// Direct dispatch for Order Tracking & Digital Receipts
if ($base === 'track' || $base === 'order-status') {
    $token = $page_id ?: trim($_GET['v'] ?? $_GET['no'] ?? '');
    if (!empty($token)) {
        $_GET['v'] = $token;
        $_GET['no'] = $token;
    }
    require_once __DIR__ . '/../track.php';
    exit;
}

if ($base === 'pay' || $base === 'payment') {
    $token = $page_id ?: trim($_GET['v'] ?? $_GET['no'] ?? '');
    if (!empty($token)) {
        $_GET['v'] = $token;
        $_GET['no'] = $token;
    }
    require_once __DIR__ . '/../pay.php';
    exit;
}

if ($base === 'receipt') {
    $token = $page_id ?: trim($_GET['v'] ?? $_GET['no'] ?? '');
    if (!empty($token)) {
        $_GET['v'] = $token;
        $_GET['no'] = $token;
    }
    require_once __DIR__ . '/../receipt.php';
    exit;
}

$routes = [
    ''                => 'home.php',
    'home'            => 'home.php',
    'about'           => 'about.php',
    'products'        => 'products.php',
    'product'         => 'product-detail.php',
    'gift-sets'       => 'gift-sets.php',
    'corporate-gifts' => 'corporate-gifts.php',
    'wholesale'       => 'corporate-gifts.php',
    'stores'          => 'stores-contact.php',
    'contact'         => 'stores-contact.php',
    'checkout'        => 'checkout.php',
    'vip'             => 'vip.php',
    'orders'          => 'profile-orders.php',
    'profile'         => 'profile-orders.php',
    'account'         => 'profile-orders.php',
    'faqs'            => 'faqs.php',
    'faq'             => 'faqs.php',
    'gallery'         => 'gallery.php',
    'brandstory'      => 'brandstory.php',
    'story'           => 'brandstory.php',
    'blog'            => 'blog.php',
    'articles'        => 'blog.php',
    'news'            => 'blog.php',
];

// 1. Load Global Site Context (Contact, Languages, Main Categories, Cart, Default SEO)
$SITE = get_site_context($db, $lang);
$SEO = $SITE['seo'];

// 2. Prepare Route-Specific View Data & Dynamic SEO
$template_file = '404.php';
if (array_key_exists($base, $routes)) {
    $template_file = $routes[$base];
}

switch ($template_file) {
    case 'home.php':
        $HOME_DATA = get_home_page_data($db, $lang);
        $MATRIX_PREVIEW = get_wholesale_matrix_data($db, $lang);
        // ------------------------------------------------------------
        // ตั้งค่า SEO หน้าแรก (Homepage Title, Description, Keywords)
        // ------------------------------------------------------------
        $SEO['title'] = $lang === 'th'
            ? 'ยาหม่องกิโล ยาหม่องถุง ราคาส่งร้านนวด & สปา 1 กก. ฿590.- | Yak Pho Aroma (ยักษ์โพธิ์)'
            : 'Thai Massage Balm 1kg Refill Pouch | Yak Pho Aroma Wholesale';
        $SEO['description'] = $lang === 'th'
            ? 'ยาหม่องกิโลและยาหม่องถุง 1 กก. สูตรเข้มข้น 3 สูตร 12 กลิ่นซิกเนเจอร์ ผสาน Tarragon Infused Oil ภูมิปัญญาสมุนไพรไทยแท้ ลื่นมือ ซึมไว ไม่คันมือ ไม่แสบร้อนผิวแขก ราคาส่งร้านนวดเริ่มต้น ฿590/กก.'
            : 'Concentrated Thai herbal massage balms & spa aromatherapy 1kg refill pouches. 3 signature formulas, 12 scents. Crafted for professional Thai massage shops & spas.';
        $SEO['keywords'] = 'ยาหม่องกิโล, ยาหม่องถุง, ยาหม่องราคาส่ง, ยาหม่องร้านนวด, บาล์มนวด 1 กก, ยาหม่อง 1 กิโล, ยาหม่องสปา, ยักษ์โพธิ์, Yak Pho Aroma';
        break;

    case 'about.php':
        $HOME_DATA = get_home_page_data($db, $lang);
        $STORES_DATA = get_stores_and_faqs_data($db, $lang);
        $SEO['title'] = ($lang === 'th' ? 'เกี่ยวกับเรา & เรื่องราวสมุนไพร | ' : 'About Us & Heritage | ') . 'Yak Pho Aroma (Esther Aroma Co., Ltd.)';
        break;

    case 'brandstory.php':
        $HOME_DATA = get_home_page_data($db, $lang);
        $SEO['title'] = ($lang === 'th' ? 'เรื่องราวแบรนด์ยักษ์โพธิ์ (Brand Story) | ' : 'Yak Pho Brand Story | ') . 'Yak Pho Aroma';
        $SEO['description'] = ($lang === 'th' ? 'เรื่องราวและจิตวิญญาณแบรนด์ยักษ์โพธิ์ (From Soil To Soul) ภูมิปัญญาสมุนไพรไทยผสานนวัตกรรมสกัดทาร์รากอน โดย บริษัท เอสเธอร์ อโรม่า จำกัด' : 'Authentic Thai Herbal Wisdom and Tarragon Infused Oil by Esther Aroma Co., Ltd.');
        $SEO['keywords'] = 'เรื่องราวแบรนด์ยักษ์โพธิ์, Yak Pho Brand Story, เอสเธอร์ อโรม่า, From Soil To Soul, ทาร์รากอนสกัด, Tarragon Infused Oil';
        break;

    case 'gallery.php':
        $STORES_DATA = get_stores_and_faqs_data($db, $lang);
        $SEO['title'] = ($lang === 'th' ? 'ภาพบรรยากาศแกลเลอรี (Gallery) | ' : 'Photo Gallery | ') . 'Yak Pho Aroma';
        $SEO['description'] = ($lang === 'th' ? 'ชมภาพบรรยากาศกรรมวิธีการผลิต วัตถุดิบสมุนไพร และผลิตภัณฑ์สปาอโรม่ายักษ์โพธิ์ มาตรฐานส่งออกสากล' : 'Photo Gallery of Yak Pho Aroma natural herbs, production standards, and spa wellness products.');
        $SEO['keywords'] = 'ภาพแกลเลอรียักษ์โพธิ์, บาล์มนวดสปา, แหล่งผลิตสมุนไพรแท้, Yak Pho Gallery';
        break;

    case 'faqs.php':
        $STORES_DATA = get_stores_and_faqs_data($db, $lang);
        $SEO['title'] = ($lang === 'th' ? 'คำถามที่ถูกถามบ่อย (FAQs) | ' : 'Frequently Asked Questions (FAQs) | ') . 'Yak Pho Aroma';
        $SEO['description'] = ($lang === 'th' ? 'รวมคำถามที่พบบ่อยเกี่ยวกับบาล์มนวดสมุนไพรร้านสปา เรทราคาส่ง 1 กก. สูตรทาร์รากอน และการจัดส่งทั่วประเทศ' : 'Frequently Asked Questions about Yak Pho Aroma spa wholesale products, 1kg rates, and worldwide export.');
        $SEO['keywords'] = 'คำถามที่พบบ่อย, FAQs ยักษ์โพธิ์, เรทราคาส่ง 1 กก, บาล์มนวดสูตรร้อนสูตรเย็น, จัดส่งพัสดุ';
        break;

    case 'products.php':
        $reqPage = max(1, (int)($_GET['page'] ?? 1));
        $reqCat = $_GET['cat_id'] ?? '';
        
        // Pretty sub-routes support: e.g. /products/page/2 or /products/48 or /products/48/page/2
        if (($segments[1] ?? '') === 'page' && !empty($segments[2])) {
            $reqPage = max(1, (int)$segments[2]);
        } elseif (is_numeric($segments[1] ?? '')) {
            $reqCat = (int)$segments[1];
            if (($segments[2] ?? '') === 'page' && !empty($segments[3])) {
                $reqPage = max(1, (int)$segments[3]);
            }
        }

        $CATALOG_FILTERS = [
            'maincat_id' => $_GET['maincat_id'] ?? '',
            'cat_id'     => $reqCat,
            'formula'    => $_GET['formula'] ?? '',
            'size'       => $_GET['size'] ?? '',
            'q'          => trim((string)($_GET['q'] ?? '')),
            'sort'       => $_GET['sort'] ?? 'recommended',
            'page'       => $reqPage,
            'limit'      => 12,
        ];
        $CATALOG_DATA = get_catalog_products($db, $CATALOG_FILTERS, $lang);
        $SEO['title'] = ($lang === 'th' ? 'ผลิตภัณฑ์สปาและสมุนไพรทั้งหมด | ' : 'All Spa & Herbal Products | ') . 'Yak Pho Aroma';
        break;

    case 'product-detail.php':
        $lookupId = $page_id ?? ($_GET['id'] ?? '');
        $PRODUCT_DETAIL = get_single_product_detail($db, $lookupId, $lang);
        $WHOLESALE_DATA = get_wholesale_matrix_data($db, $lang);
        if (!$PRODUCT_DETAIL) {
            $template_file = '404.php';
            http_response_code(404);
        } else {
            $SEO['title'] = $PRODUCT_DETAIL['display_name'] . ' | Yak Pho Aroma';
            $SEO['description'] = $PRODUCT_DETAIL['display_excerpt'] ?: $SEO['description'];
            $SEO['image'] = $PRODUCT_DETAIL['image_large'];
        }
        break;

    case 'gift-sets.php':
        $HOME_DATA = get_home_page_data($db, $lang);
        $SEO['title'] = ($lang === 'th' ? 'ชุดของขวัญสปาและสมุนไพรพรีเมียม | ' : 'Personalised Spa Gift Sets | ') . 'Yak Pho Aroma';
        break;

    case 'corporate-gifts.php':
        $WHOLESALE_DATA = get_wholesale_matrix_data($db, $lang);
        $HOME_DATA = get_home_page_data($db, $lang);
        $SEO['title'] = ($lang === 'th' ? 'สั่งซื้อเรทราคาส่งร้านนวด & สปา B2B | ' : 'Corporate Gifts & B2B Spa Wholesale | ') . 'Yak Pho Aroma';
        break;

    case 'stores-contact.php':
        $STORES_DATA = get_stores_and_faqs_data($db, $lang);
        $SEO['title'] = ($lang === 'th' ? 'คำถามที่พบบ่อย (FAQs) สาขา และติดต่อเรา | ' : 'FAQs, Stores & Contact | ') . 'Yak Pho Aroma';
        break;

    case 'blog.php':
        $blogLookup = $page_id ?? ($_GET['id'] ?? null);
        $BLOG_DATA = get_blog_posts_data($db, $lang, $blogLookup);
        if (!empty($BLOG_DATA['current_post'])) {
            $cp = $BLOG_DATA['current_post'];
            $SEO['title'] = $cp['blog_name'] . ' | Yak Pho Aroma';
            $SEO['description'] = $cp['blog_excerpt'] ?: mb_substr(trim(strip_tags((string)($cp['blog_detail'] ?? ''))), 0, 160, 'UTF-8');
            $SEO['image'] = $cp['image_url'] ?? $SEO['image'];
        } else {
            $SEO['title'] = ($lang === 'th' ? 'บทความสมุนไพรนวดสปา & สาระน่ารู้ | ' : 'Herbal Spa Articles & Insights | ') . 'Yak Pho Aroma';
            $SEO['description'] = ($lang === 'th' ? 'บทความความรู้เกี่ยวกับสมุนไพรไทย ศาสตร์การบำบัดกลิ่น (Aromatherapy) เคล็ดลับการบริหารร้านสปา และมาตรฐานบาล์มส่งออกสากล' : 'Ancient Thai herbal wisdom, aromatherapy science, spa business insights, and international export standards.');
            $SEO['keywords'] = 'บทความสมุนไพรนวดสปา, บาล์มนวดร้านสปา, สรรพคุณไพล, น้ำมันทาร์รากอน, เทคนิคการนวดไทย';
        }
        break;

    case 'checkout.php':
        $CHECKOUT_DATA = get_checkout_page_data($db, $lang);
        $SEO['title'] = ($lang === 'th' ? 'ยืนยันคำสั่งซื้อและชำระเงิน | ' : 'Secure Checkout | ') . 'Yak Pho Aroma';
        break;

    case 'vip.php':
        $SEO['title'] = ($lang === 'th' ? 'สิทธิพิเศษสำหรับสมาชิก VIP | ' : 'Exclusive VIP Member Portal | ') . 'Yak Pho Aroma';
        break;

    case 'profile-orders.php':
        $PROFILE_DATA = get_profile_page_data($db, $lang);
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        $SEO['title'] = ($lang === 'th' ? 'คำสั่งซื้อ ข้อมูลบัญชี และติดตามพัสดุ | ' : 'Orders & Account Profile | ') . 'Yak Pho Aroma';
        break;

    default:
        http_response_code(404);
        break;
}

// 3. Render Header -> Main View -> Footer
include __DIR__ . '/../templates/header.php';

$full_template_path = __DIR__ . '/../templates/' . $template_file;
if (file_exists($full_template_path)) {
    include $full_template_path;
} else {
    include __DIR__ . '/../templates/404.php';
}

include __DIR__ . '/../templates/footer.php';
