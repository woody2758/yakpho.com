<?php
declare(strict_types=1);

/**
 * ============================================================
 * YakPho & Esther Aroma — Frontend Data & Service Layer
 * ============================================================
 * Strictly enforces Layer 2 Architecture: View templates (templates/*.php)
 * contain ZERO direct database queries. All DB queries live here.
 */

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

/**
 * Helper: Translate UI labels across 10 languages with fallback to EN/TH
 */
function __t(string $key, ?string $lang = null): string
{
    global $lang_code;
    $l = $lang ?? ($lang_code ?? ($_SESSION['lang'] ?? 'th'));

    $dict = [
        'nav_home' => ['th' => 'หน้าแรก', 'en' => 'Home', 'zh' => '首页', 'ja' => 'ホーム', 'ko' => '홈', 'de' => 'Startseite', 'fr' => 'Accueil', 'ru' => 'Главная', 'ar' => 'الرئيسية', 'he' => 'בית'],
        'nav_about' => ['th' => 'เกี่ยวกับเรา', 'en' => 'About Us', 'zh' => '关于我们', 'ja' => '私たちについて', 'ko' => '회사 소개', 'de' => 'Über uns', 'fr' => 'À propos', 'ru' => 'О нас', 'ar' => 'معلومات عنا', 'he' => 'עלינו'],
        'nav_products' => ['th' => 'สินค้าทั้งหมด', 'en' => 'Our Products', 'zh' => '所有产品', 'ja' => '製品一覧', 'ko' => '전체 상품', 'de' => 'Produkte', 'fr' => 'Nos Produits', 'ru' => 'Продукция', 'ar' => 'منتجاتنا', 'he' => 'המוצרים שלנו'],
        'nav_gift_sets' => ['th' => 'ชุดของขวัญ', 'en' => 'Personalised Gift Sets', 'zh' => '定制礼盒', 'ja' => 'ギフトセット', 'ko' => '기프트 세트', 'de' => 'Geschenksets', 'fr' => 'Coffrets Cadeaux', 'ru' => 'Подарочные наборы', 'ar' => 'مجموعات الهدايا', 'he' => 'מארזי מתנה'],
        'nav_corporate' => ['th' => 'เรทราคาส่ง & สปา', 'en' => 'Corporate & Wholesale', 'zh' => '批发与水疗合作', 'ja' => '卸売・法人向け', 'ko' => '도매 및 기업 선물', 'de' => 'Großhandel & Spa', 'fr' => 'Grossiste & Spa', 'ru' => 'Опт и СПА', 'ar' => 'الجملة والشركات', 'he' => 'סיטונאות וספא'],
        'nav_stores' => ['th' => 'สาขาและตัวแทน', 'en' => 'Our Stores', 'zh' => '门店与代理', 'ja' => '店舗案内', 'ko' => '매장 안내', 'de' => 'Filialen', 'fr' => 'Nos Boutiques', 'ru' => 'Магазины', 'ar' => 'فروعنا', 'he' => 'החנויות שלנו'],
        'nav_contact' => ['th' => 'ติดต่อเรา', 'en' => 'Contact Us', 'zh' => '联系我们', 'ja' => 'お問い合わせ', 'ko' => '문의하기', 'de' => 'Kontakt', 'fr' => 'Contact', 'ru' => 'Контакты', 'ar' => 'اتصل بنا', 'he' => 'צור קשר'],
        'nav_track' => ['th' => 'เช็คสถานะพัสดุ', 'en' => 'Track Order', 'zh' => '订单追踪', 'ja' => '配送状況確認', 'ko' => '배송 조회', 'de' => 'Sendungsverfolgung', 'fr' => 'Suivi de commande', 'ru' => 'Отследить заказ', 'ar' => 'تتبع الطلب', 'he' => 'מעקב משלוחים'],
        'btn_shop_now' => ['th' => 'เลือกชมสินค้า', 'en' => 'Explore Collection', 'zh' => '立即选购', 'ja' => 'コレクションを見る', 'ko' => '상품 보기', 'de' => 'Kollektion entdecken', 'fr' => 'Découvrir', 'ru' => 'Смотреть каталог', 'ar' => 'تسوق الآن', 'he' => 'לצפייה במוצרים'],
        'btn_wholesale' => ['th' => 'สั่งซื้อเรทส่งร้านนวด', 'en' => 'B2B Wholesale Matrix', 'zh' => '批发订购表', 'ja' => '業務用卸売オーダー', 'ko' => '도매 주문표', 'de' => 'B2B Großhandel', 'fr' => 'Commande Pro B2B', 'ru' => 'Оптовый заказ', 'ar' => 'طلب الجملة', 'he' => 'הזמנה סיטונאית'],
        'btn_add_cart' => ['th' => 'หยิบใส่ตะกร้า', 'en' => 'Add to Cart', 'zh' => '加入购物车', 'ja' => 'カートに追加', 'ko' => '장바구니 담기', 'de' => 'In den Warenkorb', 'fr' => 'Ajouter au panier', 'ru' => 'В корзину', 'ar' => 'أضف إلى السلة', 'he' => 'הוסף לסל'],
        'btn_view_detail' => ['th' => 'ดูรายละเอียด', 'en' => 'View Details', 'zh' => '查看详情', 'ja' => '詳細を見る', 'ko' => '상세 보기', 'de' => 'Details ansehen', 'fr' => 'Voir détails', 'ru' => 'Подробнее', 'ar' => 'عرض التفاصيل', 'he' => 'פרטים נוספים'],
        'btn_checkout' => ['th' => 'ดำเนินการสั่งซื้อ', 'en' => 'Proceed to Checkout', 'zh' => '去结账', 'ja' => 'レジへ進む', 'ko' => '결제하기', 'de' => 'Zur Kasse', 'fr' => 'Commander', 'ru' => 'Оформить заказ', 'ar' => 'إتمام الطلب', 'he' => 'מעבר לתשלום'],
        'cart_title' => ['th' => 'ตะกร้าสินค้าของคุณ', 'en' => 'Your Shopping Cart', 'zh' => '您的购物车', 'ja' => 'ショッピングカート', 'ko' => '장바구니', 'de' => 'Ihr Warenkorb', 'fr' => 'Votre Panier', 'ru' => 'Ваша корзина', 'ar' => 'سلة التسوق', 'he' => 'סל הקניות שלך'],
        'cart_empty' => ['th' => 'ยังไม่มีสินค้าในตะกร้า', 'en' => 'Your cart is currently empty', 'zh' => '购物车是空的', 'ja' => 'カートは空です', 'ko' => '장바구니가 비어 있습니다', 'de' => 'Ihr Warenkorb ist leer', 'fr' => 'Votre panier est vide', 'ru' => 'Корзина пуста', 'ar' => 'سلة التسوق فارغة', 'he' => 'הסל שלך ריק'],
        'theme_light' => ['th' => 'โหมดสว่าง (Anona)', 'en' => 'Light Mode', 'zh' => '浅色模式', 'ja' => 'ライトモード', 'ko' => '라이트 모드', 'de' => 'Heller Modus', 'fr' => 'Mode Clair', 'ru' => 'Светлая тема', 'ar' => 'الوضع الفاتح', 'he' => 'מצב בהיר'],
        'theme_dark' => ['th' => 'โหมดมืด (Esther Emerald)', 'en' => 'Emerald Dark', 'zh' => '深绿模式', 'ja' => 'エメラルドダーク', 'ko' => '에메랄드 다크', 'de' => 'Smaragd Dunkel', 'fr' => 'Mode Émeraude', 'ru' => 'Изумрудная тема', 'ar' => 'الوضع الداكن', 'he' => 'מצב כהה'],
        'currency' => ['th' => '฿', 'en' => '฿', 'zh' => '฿', 'ja' => '฿', 'ko' => '฿', 'de' => '฿', 'fr' => '฿', 'ru' => '฿', 'ar' => '฿', 'he' => '฿'],
        'unit_kg' => ['th' => 'กก.', 'en' => 'kg', 'zh' => '公斤', 'ja' => 'kg', 'ko' => 'kg', 'de' => 'kg', 'fr' => 'kg', 'ru' => 'кг', 'ar' => 'كجم', 'he' => 'ק"ג'],
    ];

    if (isset($dict[$key][$l])) {
        return $dict[$key][$l];
    }
    return $dict[$key]['en'] ?? ($dict[$key]['th'] ?? $key);
}

/**
 * Resolve product image path safely with fallback
 */
function resolve_product_image(?string $filename, string $size = 'large', int $fallbackIdx = 1): string
{
    $baseDir = dirname(__DIR__);
    $urlBase = defined('URL_PATH') ? URL_PATH : '';
    $baseDir = dirname(__DIR__);

    if (!empty($filename)) {
        $clean = basename(trim($filename));
        $candidates = [
            "uploads/products/{$size}-{$clean}",
            "uploads/products/large-{$clean}",
            "uploads/products/small-{$clean}",
            "uploads/products/original-{$clean}",
            "uploads/products/{$clean}",
            "catalog/assets/img/{$clean}",
        ];
        foreach ($candidates as $rel) {
            if (file_exists($baseDir . '/' . $rel)) {
                return $urlBase . '/' . $rel;
            }
        }
    }

    $idx = (($fallbackIdx - 1) % 8) + 1;
    $fallbackRel = sprintf('catalog/assets/img/best_h00%d.webp', $idx);
    if (file_exists($baseDir . '/' . $fallbackRel)) {
        return $urlBase . '/' . $fallbackRel;
    }
    return $urlBase . '/catalog/assets/img/YakPhoAromaLogo.webp';
}

/**
 * Resolve CMS / Hero / Gallery / Scent image safely with fallback
 */
function resolve_cms_image(?string $filename, string $fallbackRel = 'catalog/assets/img/brand_story.webp'): string
{
    $baseDir = dirname(__DIR__);
    $urlBase = defined('URL_PATH') ? URL_PATH : '';

    if (!empty($filename)) {
        $clean = basename(trim($filename));
        $candidates = [
            "uploads/yakpho/{$clean}",
            "uploads/hero/{$clean}",
            "uploads/blog/{$clean}",
            "uploads/shop/{$clean}",
            "uploads/products/large-{$clean}",
            "uploads/products/{$clean}",
            "catalog/assets/img/{$clean}",
        ];
        foreach ($candidates as $rel) {
            if (file_exists($baseDir . '/' . $rel)) {
                return $urlBase . '/' . $rel;
            }
        }
    }

    if (file_exists($baseDir . '/' . ltrim($fallbackRel, '/'))) {
        return $urlBase . '/' . ltrim($fallbackRel, '/');
    }
    return $urlBase . '/catalog/assets/img/brand_story.webp';
}

/**
 * Calculate wholesale per-kg price based on total 1kg bags ordered
 */
function calculate_wholesale_kg_rate(int $totalKg): int
{
    if ($totalKg >= 100) return 470;
    if ($totalKg >= 50)  return 490;
    if ($totalKg >= 30)  return 500;
    if ($totalKg >= 20)  return 530;
    if ($totalKg >= 10)  return 560;
    if ($totalKg >= 6)   return 590;
    return 690;
}

/**
 * Calculate dynamic shipping fee by total weight (grams) and destination
 * Brackets:
 * <= 2000g: 50 THB
 * 2001g - 4000g: 67 THB
 * 4001g - 6000g: 70 THB
 * 6001g - 8000g: 80 THB
 * 8001g - 10000g: 90 THB
 * 10001g - 12000g: 90 THB
 * > 12000g: 0 THB (Free shipping)
 * Destination 'international': 0 THB (Pending quote based on destination)
 */
function calculate_shipping_by_weight($totalGrams, string $destination = 'domestic'): array
{
    if ($destination === 'international') {
        return [
            'cost' => 0.0,
            'is_free' => false,
            'is_quote' => true,
            'notice' => 'รอแจ้งค่าจัดส่งตามพื้นที่ปลายทาง (Pending quote)',
            'label' => 'ตรวจสอบปลายทาง',
        ];
    }

    if ($totalGrams <= 0) {
        return [
            'cost' => 0.0,
            'is_free' => true,
            'is_quote' => false,
            'notice' => 'ส่งฟรี',
            'label' => '฿0',
        ];
    }

    if ($totalGrams > 12000) {
        $cost = 0.0;
        $isFree = true;
        $notice = 'ส่งฟรีทั่วประเทศ (> 12 กก.)';
        $label = 'ส่งฟรี';
    } elseif ($totalGrams > 10000) {
        $cost = 90.0;
        $isFree = false;
        $notice = 'ค่าจัดส่งพัสดุด่วนตามน้ำหนัก (' . number_format($totalGrams) . ' กรัม)';
        $label = '฿90';
    } elseif ($totalGrams > 8000) {
        $cost = 90.0;
        $isFree = false;
        $notice = 'ค่าจัดส่งพัสดุด่วนตามน้ำหนัก (' . number_format($totalGrams) . ' กรัม)';
        $label = '฿90';
    } elseif ($totalGrams > 6000) {
        $cost = 80.0;
        $isFree = false;
        $notice = 'ค่าจัดส่งพัสดุด่วนตามน้ำหนัก (' . number_format($totalGrams) . ' กรัม)';
        $label = '฿80';
    } elseif ($totalGrams > 4000) {
        $cost = 70.0;
        $isFree = false;
        $notice = 'ค่าจัดส่งพัสดุด่วนตามน้ำหนัก (' . number_format($totalGrams) . ' กรัม)';
        $label = '฿70';
    } elseif ($totalGrams > 2000) {
        $cost = 67.0;
        $isFree = false;
        $notice = 'ค่าจัดส่งพัสดุด่วนตามน้ำหนัก (' . number_format($totalGrams) . ' กรัม)';
        $label = '฿67';
    } else {
        $cost = 50.0;
        $isFree = false;
        $notice = 'ค่าจัดส่งพัสดุด่วนตามน้ำหนัก (' . number_format($totalGrams) . ' กรัม)';
        $label = '฿50';
    }

    return [
        'cost' => $cost,
        'is_free' => $isFree,
        'is_quote' => false,
        'notice' => $notice,
        'label' => $label,
    ];
}

/**
 * Get current session shopping cart summary and detailed items
 */
function get_cart_summary(PDO $db, string $lang = 'th', string $destination = 'domestic'): array
{
    if (!isset($_SESSION['yakpho_cart']) || !is_array($_SESSION['yakpho_cart'])) {
        $_SESSION['yakpho_cart'] = [];
    }

    $rawCart = $_SESSION['yakpho_cart'];
    if (empty($rawCart)) {
        return [
            'items' => [],
            'total_qty' => 0,
            'total_1kg_qty' => 0,
            'total_weight_grams' => 0,
            'wholesale_kg_rate' => 690,
            'subtotal' => 0.0,
            'retail_total' => 0.0,
            'wholesale_savings' => 0.0,
            'shipping_info' => calculate_shipping_by_weight(0, $destination),
            'shipping_cost' => 0.0,
            'grand_total' => 0.0,
        ];
    }

    $productIds = array_map('intval', array_keys($rawCart));
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));

    $sql = "
        SELECT p.product_id, p.product_code, p.productcat_id, p.product_price, p.product_nprice,
               p.product_picture, p.product_weight, p.scent_id, p.formula_id,
               pc.prod_size, pc.prod_formula,
               COALESCE(NULLIF(pt.product_name, ''), NULLIF(pt_en.product_name, ''), NULLIF(pt_th.product_name, ''), p.product_name) AS product_name
        FROM product p
        LEFT JOIN productcat pc ON p.productcat_id = pc.productcat_id
        LEFT JOIN product_translations pt ON p.product_id = pt.product_id AND pt.lang_code = ?
        LEFT JOIN product_translations pt_en ON p.product_id = pt_en.product_id AND pt_en.lang_code = 'en'
        LEFT JOIN product_translations pt_th ON p.product_id = pt_th.product_id AND pt_th.lang_code = 'th'
        WHERE p.product_id IN ($placeholders)
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute(array_merge([$lang], $productIds));
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $productMap = [];
    foreach ($rows as $r) {
        $productMap[(int)$r['product_id']] = $r;
    }

    // Count total 1kg balm bags (categories 32, 38, 40) for automatic wholesale tier pricing!
    $total1KgQty = 0;
    foreach ($rawCart as $pid => $qty) {
        $pid = (int)$pid;
        $qty = max(0, (int)$qty);
        if ($qty <= 0 || !isset($productMap[$pid])) continue;
        $catId = (int)$productMap[$pid]['productcat_id'];
        if (in_array($catId, [32, 38, 40], true)) {
            $total1KgQty += $qty;
        }
    }

    $kgTierRate = calculate_wholesale_kg_rate($total1KgQty);

    $items = [];
    $totalQty = 0;
    $totalWeightGrams = 0;
    $subtotal = 0.0;
    $retailTotal = 0.0;

    foreach ($rawCart as $pid => $qty) {
        $pid = (int)$pid;
        $qty = max(0, (int)$qty);
        if ($qty <= 0 || !isset($productMap[$pid])) {
            unset($_SESSION['yakpho_cart'][$pid]);
            continue;
        }
        $prod = $productMap[$pid];
        $catId = (int)$prod['productcat_id'];
        $retailPrice = (float)$prod['product_price'] > 0 ? (float)$prod['product_price'] : 690.0;

        // Apply automatic wholesale tier rate if it's a 1kg balm bag
        $unitPrice = in_array($catId, [32, 38, 40], true) ? (float)$kgTierRate : $retailPrice;
        $lineTotal = $unitPrice * $qty;
        $lineRetail = $retailPrice * $qty;

        // Weight calculation
        $itemWeight = (int)($prod['product_weight'] ?? 0);
        if ($itemWeight <= 0) {
            $itemWeight = in_array($catId, [32, 38, 40], true) ? 1000 : 100;
        }
        $lineWeight = $itemWeight * $qty;
        $totalWeightGrams += $lineWeight;

        $totalQty += $qty;
        $subtotal += $lineTotal;
        $retailTotal += $lineRetail;

        $imgUrl = resolve_product_image($prod['product_picture'], 'small', $pid);
        $items[] = [
            'product_id' => $pid,
            'product_code' => $prod['product_code'] ?: ('YP' . $pid),
            'productcat_id' => $catId,
            'product_name' => $prod['product_name'] ?: ('Product #' . $pid),
            'image' => $imgUrl,
            'image_url' => $imgUrl,
            'qty' => $qty,
            'weight_each' => $itemWeight,
            'retail_price' => $retailPrice,
            'unit_price' => $unitPrice,
            'is_wholesale_1kg' => in_array($catId, [32, 38, 40], true),
            'line_total' => $lineTotal,
        ];
    }

    $savings = max(0.0, $retailTotal - $subtotal);
    $shippingInfo = calculate_shipping_by_weight($totalWeightGrams, $destination);
    $shippingCost = (float)$shippingInfo['cost'];
    $grandTotal = $subtotal + $shippingCost;

    return [
        'items' => $items,
        'total_qty' => $totalQty,
        'total_1kg_qty' => $total1KgQty,
        'total_weight_grams' => $totalWeightGrams,
        'wholesale_kg_rate' => $kgTierRate,
        'subtotal' => $subtotal,
        'retail_total' => $retailTotal,
        'wholesale_savings' => $savings,
        'shipping_info' => $shippingInfo,
        'shipping_cost' => $shippingCost,
        'grand_total' => $grandTotal,
    ];
}

/**
 * Get global site context (contact info, languages, main categories, cart summary, SEO defaults)
 */
function get_site_context(PDO $db, string $lang = 'th'): array
{
    // 1. Active languages
    $langs = [];
    try {
        $stmt = $db->query("SELECT code, name, flag, is_rtl FROM languages WHERE status = 1 ORDER BY sort_order ASC, id ASC");
        $langs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $langs = [
            ['code' => 'th', 'name' => 'ไทย', 'flag' => '🇹🇭', 'is_rtl' => 0],
            ['code' => 'en', 'name' => 'English', 'flag' => '🇬🇧', 'is_rtl' => 0],
        ];
    }

    // 2. Contact & Company Info from yakpho_contact + yakpho_contact_translations
    $contact = [
        'phone' => '(+66) 80 061 7073',
        'line_id' => '@yakpho',
        'facebook' => 'YakPho Aroma',
        'email' => 'thaiherbfc@gmail.com',
        'whatsapp' => '+66 080 061 7073',
        'company_name' => $lang === 'th' ? 'บริษัท เอสเธอร์ อโรม่า จำกัด (Esther Aroma Co., Ltd.)' : 'Esther Aroma Co., Ltd.',
        'address' => $lang === 'th'
            ? 'เลขที่ 32/4 ถนนพระยาสุเรนทร์ แขวงสามวาตะวันตก เขตคลองสามวา กรุงเทพมหานคร 10510 ประเทศไทย'
            : '32/4 Phraya Suren Rd, Sam Wa Tawan Tok, Khlong Sam Wa, Bangkok 10510, Thailand',
        'about_title' => 'YakPho: ศิลปะแห่งการบำบัด... ผสานความลับจาก Tarragon Infused Oil',
        'about_subtitle' => 'Esther Aroma Co., Ltd. • กลุ่มสุขภาพและเครื่องหอม (From Soil To Soul)',
        'about_html' => '',
        'map_embed' => '',
    ];

    try {
        $stmt = $db->prepare("
            SELECT c.*,
                   COALESCE(NULLIF(ct.company_name, ''), NULLIF(ct_en.company_name, ''), ct_th.company_name) AS company_name,
                   COALESCE(NULLIF(ct.address, ''), NULLIF(ct_en.address, ''), ct_th.address) AS address,
                   COALESCE(NULLIF(ct.about_title, ''), NULLIF(ct_en.about_title, ''), ct_th.about_title) AS about_title,
                   COALESCE(NULLIF(ct.about_subtitle, ''), NULLIF(ct_en.about_subtitle, ''), ct_th.about_subtitle) AS about_subtitle,
                   COALESCE(NULLIF(ct.about_html, ''), NULLIF(ct_en.about_html, ''), ct_th.about_html) AS about_html
            FROM yakpho_contact c
            LEFT JOIN yakpho_contact_translations ct ON c.contact_id = ct.contact_id AND ct.lang_code = ?
            LEFT JOIN yakpho_contact_translations ct_en ON c.contact_id = ct_en.contact_id AND ct_en.lang_code = 'en'
            LEFT JOIN yakpho_contact_translations ct_th ON c.contact_id = ct_th.contact_id AND ct_th.lang_code = 'th'
            LIMIT 1
        ");
        $stmt->execute([$lang]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $contact = array_merge($contact, array_filter($row, fn($v) => $v !== null && $v !== ''));
        }
    } catch (Exception $e) {
        // Keep defaults
    }

    // 3. Main Categories (Yak Pho Spa & Wellness Only: maincat_id = 1)
    $mainCats = [];
    try {
        $stmt = $db->prepare("
            SELECT m.maincat_id, m.maincat_slug, m.maincat_icon,
                   COALESCE(NULLIF(mt.maincat_name, ''), NULLIF(mt_en.maincat_name, ''), mt_th.maincat_name) AS maincat_name,
                   COALESCE(NULLIF(mt.maincat_detail, ''), NULLIF(mt_en.maincat_detail, ''), mt_th.maincat_detail) AS maincat_detail
            FROM maincat m
            LEFT JOIN maincat_translations mt ON m.maincat_id = mt.maincat_id AND mt.lang_code = ?
            LEFT JOIN maincat_translations mt_en ON m.maincat_id = mt_en.maincat_id AND mt_en.lang_code = 'en'
            LEFT JOIN maincat_translations mt_th ON m.maincat_id = mt_th.maincat_id AND mt_th.lang_code = 'th'
            WHERE m.maincat_status = 1 AND m.maincat_del = 0
            ORDER BY m.maincat_index ASC, m.maincat_id ASC
        ");
        $stmt->execute([$lang]);
        $mainCats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $mainCats = [];
    }

    // 4. Product Categories with active products (All active maincat & productcat)
    $productCats = [];
    try {
        $stmt = $db->prepare("
            SELECT pc.productcat_id, pc.maincat_id, pc.prod_size, pc.prod_formula,
                   COALESCE(NULLIF(pct.productcat_name, ''), NULLIF(pct_en.productcat_name, ''), NULLIF(pct_th.productcat_name, ''), pc.productcat_name) AS productcat_name,
                   COALESCE(NULLIF(pct.productcat_detail, ''), NULLIF(pct_en.productcat_detail, ''), NULLIF(pct_th.productcat_detail, ''), pc.productcat_detail) AS productcat_detail,
                   (SELECT COUNT(*) FROM product p WHERE p.productcat_id = pc.productcat_id AND p.product_del = 0 AND p.product_status = 1) AS product_count,
                   (SELECT p2.product_picture FROM product p2 WHERE p2.productcat_id = pc.productcat_id AND p2.product_del = 0 AND p2.product_status = 1 AND p2.product_picture != '' ORDER BY p2.product_id ASC LIMIT 1) AS sample_picture
            FROM productcat pc
            JOIN maincat m ON pc.maincat_id = m.maincat_id AND m.maincat_status = 1 AND m.maincat_del = 0
            LEFT JOIN productcat_translations pct ON pc.productcat_id = pct.productcat_id AND pct.lang_code = ?
            LEFT JOIN productcat_translations pct_en ON pc.productcat_id = pct_en.productcat_id AND pct_en.lang_code = 'en'
            LEFT JOIN productcat_translations pct_th ON pc.productcat_id = pct_th.productcat_id AND pct_th.lang_code = 'th'
            WHERE pc.productcat_status = 1 AND pc.productcat_del = 0
            HAVING product_count > 0
            ORDER BY m.maincat_index ASC, pc.productcat_index ASC, pc.productcat_id ASC
        ");
        $stmt->execute([$lang]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $idx => $r) {
            $r['cover_image'] = resolve_product_image($r['sample_picture'] ?? '', 'large', $idx + 1);
            $productCats[] = $r;
        }
    } catch (Exception $e) {
        $productCats = [];
    }

    $cart = get_cart_summary($db, $lang);

    return [
        'lang' => $lang,
        'languages' => $langs,
        'contact' => $contact,
        'main_categories' => $mainCats,
        'product_categories' => $productCats,
        'cart' => $cart,
        'seo' => [
            'title' => $lang === 'th'
                ? 'Yak Pho Aroma | ผลิตภัณฑ์สมุนไพรและสปาอโรม่า โดย บริษัท เอสเธอร์ อโรม่า จำกัด'
                : 'Yak Pho Aroma | Thai Herbal Spa & Wellness by Esther Aroma Co., Ltd.',
            'description' => $lang === 'th'
                ? 'ยักษ์โพธิ์ อโรม่า (Yak Pho Aroma) บาล์มนวดสมุนไพรและน้ำมันนวดสูตรเข้มข้น 3 สูตร 12 กลิ่นซิกเนเจอร์ กลุ่มสุขภาพและเครื่องหอม (From Soil To Soul) โดย บริษัท เอสเธอร์ อโรม่า จำกัด'
                : 'Yak Pho Aroma: Concentrated Thai herbal massage balms and spa aromatherapy oils (From Soil To Soul) by Esther Aroma Co., Ltd.',
            'keywords' => 'YakPho, Yak Pho Aroma, Esther Aroma, ยักษ์โพธิ์, เอสเธอร์ อโรม่า, บาล์มนวดสมุนไพร, น้ำมันนวดอโรม่า, ร้านนวดไทย, สปา',
            'image' => (defined('URL_PATH') ? URL_PATH : '') . '/catalog/assets/img/brand_story.webp',
        ],
    ];
}

/**
 * Get all data required for the Homepage (templates/home.php)
 */
function get_home_page_data(PDO $db, string $lang = 'th'): array
{
    $urlBase = defined('URL_PATH') ? URL_PATH : '';
    $baseDir = dirname(__DIR__);

    // 1. Hero Slides from yakpho_hero_slides
    $heroSlides = [];
    try {
        $stmt = $db->prepare("
            SELECT s.slide_id, s.slide_image, s.btn1_url, s.btn2_url,
                   COALESCE(NULLIF(t.slide_title, ''), NULLIF(t_en.slide_title, ''), t_th.slide_title) AS slide_title,
                   COALESCE(NULLIF(t.slide_subtitle, ''), NULLIF(t_en.slide_subtitle, ''), t_th.slide_subtitle) AS slide_subtitle,
                   COALESCE(NULLIF(t.slide_badges, ''), NULLIF(t_en.slide_badges, ''), t_th.slide_badges) AS slide_badges,
                   COALESCE(NULLIF(t.btn1_text, ''), NULLIF(t_en.btn1_text, ''), t_th.btn1_text) AS btn1_text,
                   COALESCE(NULLIF(t.btn2_text, ''), NULLIF(t_en.btn2_text, ''), t_th.btn2_text) AS btn2_text
            FROM yakpho_hero_slides s
            LEFT JOIN yakpho_hero_translations t ON s.slide_id = t.slide_id AND t.lang_code = ?
            LEFT JOIN yakpho_hero_translations t_en ON s.slide_id = t_en.slide_id AND t_en.lang_code = 'en'
            LEFT JOIN yakpho_hero_translations t_th ON s.slide_id = t_th.slide_id AND t_th.lang_code = 'th'
            WHERE s.slide_status = 1
            ORDER BY s.slide_order ASC, s.slide_id ASC
        ");
        $stmt->execute([$lang]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $fallbackHeroImgs = [
            'catalog/assets/img/brand_story.webp',
            'catalog/assets/img/about1.webp',
        ];
        foreach ($rows as $i => $r) {
            $r['image_url'] = resolve_cms_image($r['slide_image'], $fallbackHeroImgs[$i % count($fallbackHeroImgs)]);
            $r['badges_list'] = array_filter(array_map('trim', explode(',', (string)$r['slide_badges'])));
            $heroSlides[] = $r;
        }
    } catch (Exception $e) {
        $heroSlides = [];
    }

    // 2. Three Core Formulas Intro & Items from yakpho_formula_intro + yakpho_formula_items
    $formulaIntro = [
        'title' => $lang === 'th' ? '3 สูตรพิเศษสำหรับร้านนวดและสปา' : '3 Special Formulas for Massage Spas',
        'subtitle' => $lang === 'th' ? 'เพราะความหอมคือหัวใจของการบำบัด' : 'Because Aroma is the Heart of Therapy',
        'description' => '',
    ];
    try {
        $stmtFi = $db->prepare("
            SELECT COALESCE(NULLIF(fit.intro_title, ''), NULLIF(fit_en.intro_title, ''), fit_th.intro_title) AS title,
                   COALESCE(NULLIF(fit.intro_subtitle, ''), NULLIF(fit_en.intro_subtitle, ''), fit_th.intro_subtitle) AS subtitle,
                   COALESCE(NULLIF(fit.intro_description, ''), NULLIF(fit_en.intro_description, ''), fit_th.intro_description) AS description
            FROM yakpho_formula_intro fi
            LEFT JOIN yakpho_formula_intro_translations fit ON fi.intro_id = fit.intro_id AND fit.lang_code = ?
            LEFT JOIN yakpho_formula_intro_translations fit_en ON fi.intro_id = fit_en.intro_id AND fit_en.lang_code = 'en'
            LEFT JOIN yakpho_formula_intro_translations fit_th ON fi.intro_id = fit_th.intro_id AND fit_th.lang_code = 'th'
            WHERE fi.intro_id = 1
            LIMIT 1
        ");
        $stmtFi->execute([$lang]);
        $rowFi = $stmtFi->fetch(PDO::FETCH_ASSOC);
        if ($rowFi) {
            $formulaIntro['title'] = $rowFi['title'] ?: $formulaIntro['title'];
            $formulaIntro['subtitle'] = $rowFi['subtitle'] ?: $formulaIntro['subtitle'];
            $formulaIntro['description'] = $rowFi['description'] ?: '';
        }
    } catch (Exception $e) {}

    $formulas = [];
    try {
        $stmt = $db->prepare("
            SELECT f.item_id, f.image_file, f.css_class, f.item_order,
                   COALESCE(NULLIF(ft.title, ''), NULLIF(ft_en.title, ''), ft_th.title) AS title,
                   COALESCE(NULLIF(ft.subtitle, ''), NULLIF(ft_en.subtitle, ''), ft_th.subtitle) AS subtitle,
                   COALESCE(NULLIF(ft.bullets, ''), NULLIF(ft_en.bullets, ''), ft_th.bullets) AS bullets
            FROM yakpho_formula_items f
            LEFT JOIN yakpho_formula_translations ft ON f.item_id = ft.item_id AND ft.lang_code = ?
            LEFT JOIN yakpho_formula_translations ft_en ON f.item_id = ft_en.item_id AND ft_en.lang_code = 'en'
            LEFT JOIN yakpho_formula_translations ft_th ON f.item_id = ft_th.item_id AND ft_th.lang_code = 'th'
            WHERE f.item_status = 1
            ORDER BY f.item_order ASC, f.item_id ASC
        ");
        $stmt->execute([$lang]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $formulaFallbacks = [
            'catalog/assets/img/best_h001.webp',
            'catalog/assets/img/best_h004.webp',
            'catalog/assets/img/best_h006.webp',
        ];
        $formulaCatMap = [1 => 32, 2 => 38, 3 => 40];
        foreach ($rows as $i => $r) {
            $r['image_url'] = resolve_cms_image($r['image_file'], $formulaFallbacks[$i % 3]);
            $r['bullet_list'] = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string)$r['bullets'])));
            $r['target_cat_id'] = $formulaCatMap[$i + 1] ?? 32;
            $formulas[] = $r;
        }
    } catch (Exception $e) {
        $formulas = [];
    }

    // 3. 12 Signature Scents Intro & Items from yakpho_scent_intro + yakpho_scent_items
    $scentIntro = [
        'title' => $lang === 'th' ? 'กลิ่นยอดนิยม (12 แบบ)' : 'Popular Scents (12 Types)',
        'subtitle' => $lang === 'th' ? 'เลือกความหอมที่ตอบโจทย์ร้านคุณ คละได้ทุกสูตร' : 'Choose the perfect aroma for your spa',
        'description' => '',
    ];
    try {
        $stmtSi = $db->prepare("
            SELECT COALESCE(NULLIF(sit.intro_title, ''), NULLIF(sit_en.intro_title, ''), sit_th.intro_title) AS title,
                   COALESCE(NULLIF(sit.intro_subtitle, ''), NULLIF(sit_en.intro_subtitle, ''), sit_th.intro_subtitle) AS subtitle,
                   COALESCE(NULLIF(sit.intro_description, ''), NULLIF(sit_en.intro_description, ''), sit_th.intro_description) AS description
            FROM yakpho_scent_intro si
            LEFT JOIN yakpho_scent_intro_translations sit ON si.intro_id = sit.intro_id AND sit.lang_code = ?
            LEFT JOIN yakpho_scent_intro_translations sit_en ON si.intro_id = sit_en.intro_id AND sit_en.lang_code = 'en'
            LEFT JOIN yakpho_scent_intro_translations sit_th ON si.intro_id = sit_th.intro_id AND sit_th.lang_code = 'th'
            WHERE si.intro_id = 1
            LIMIT 1
        ");
        $stmtSi->execute([$lang]);
        $rowSi = $stmtSi->fetch(PDO::FETCH_ASSOC);
        if ($rowSi) {
            $scentIntro['title'] = $rowSi['title'] ?: $scentIntro['title'];
            $scentIntro['subtitle'] = $rowSi['subtitle'] ?: $scentIntro['subtitle'];
            $scentIntro['description'] = $rowSi['description'] ?: '';
        }
    } catch (Exception $e) {}

    $scents = [];
    try {
        $stmt = $db->prepare("
            SELECT s.item_id, s.image_file, s.item_order,
                   COALESCE(NULLIF(st.title, ''), NULLIF(st_en.title, ''), st_th.title) AS title,
                   COALESCE(NULLIF(st.subtitle, ''), NULLIF(st_en.subtitle, ''), st_th.subtitle) AS subtitle
            FROM yakpho_scent_items s
            LEFT JOIN yakpho_scent_translations st ON s.item_id = st.item_id AND st.lang_code = ?
            LEFT JOIN yakpho_scent_translations st_en ON s.item_id = st_en.item_id AND st_en.lang_code = 'en'
            LEFT JOIN yakpho_scent_translations st_th ON s.item_id = st_th.item_id AND st_th.lang_code = 'th'
            WHERE s.item_status = 1
            ORDER BY s.item_order ASC, s.item_id ASC
        ");
        $stmt->execute([$lang]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $i => $r) {
            $fallbackImg = sprintf('catalog/assets/img/g%02d.webp', ($i % 12) + 1);
            $r['image_url'] = resolve_cms_image($r['image_file'], $fallbackImg);
            $scents[] = $r;
        }
    } catch (Exception $e) {
        $scents = [];
    }

    // 4.1 Real Best Sellers: Top 20 1kg Balms from Completed Orders (Status 5, 6, 11) with 1-hr file cache
    $bestSellers = [];
    $cacheDir = $baseDir . '/uploads/cache';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0777, true);
    }
    $cacheFile = $cacheDir . '/bestsellers_' . $lang . '.json';
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 3600)) {
        $cachedData = @json_decode(file_get_contents($cacheFile), true);
        if (is_array($cachedData) && !empty($cachedData)) {
            $bestSellers = $cachedData;
        }
    }

    if (empty($bestSellers)) {
        try {
            $stmt = $db->prepare("
                SELECT p.product_id, p.product_code, p.productcat_id, p.product_price, p.product_nprice,
                       p.product_picture, p.scent_id, p.formula_id,
                       pc.prod_size, pc.prod_formula,
                       COALESCE(NULLIF(pt.product_name, ''), NULLIF(pt_en.product_name, ''), NULLIF(pt_th.product_name, ''), p.product_name) AS product_name,
                       COALESCE(NULLIF(pt.product_excerpt, ''), NULLIF(pt_en.product_excerpt, ''), NULLIF(pt_th.product_excerpt, ''), p.product_excerpt) AS product_excerpt,
                       SUM(od.ordetail_qty) AS total_sold
                FROM ordetail od
                JOIN orders o ON od.orders_id = o.orders_id
                JOIN product p ON od.product_id = p.product_id
                LEFT JOIN productcat pc ON p.productcat_id = pc.productcat_id
                LEFT JOIN product_translations pt ON p.product_id = pt.product_id AND pt.lang_code = ?
                LEFT JOIN product_translations pt_en ON p.product_id = pt_en.product_id AND pt_en.lang_code = 'en'
                LEFT JOIN product_translations pt_th ON p.product_id = pt_th.product_id AND pt_th.lang_code = 'th'
                WHERE o.orders_del = 0 
                  AND o.orders_status IN (5, 6, 11)
                  AND p.product_del = 0 
                  AND p.product_status = 1
                  AND p.productcat_id IN (32, 38, 40)
                GROUP BY p.product_id
                ORDER BY total_sold DESC
                LIMIT 20
            ");
            $stmt->execute([$lang]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $i => $r) {
                $rank = $i + 1;
                $sold = (int)$r['total_sold'];
                $r['rank'] = $rank;
                $r['total_sold_num'] = $sold;
                $r['total_sold_formatted'] = number_format($sold);
                $r['image_url'] = resolve_product_image($r['product_picture'], 'small', $i + 1);
                $r['price_formatted'] = number_format((float)$r['product_price'], 0);
                $r['nprice_formatted'] = (float)$r['product_nprice'] > (float)$r['product_price']
                    ? number_format((float)$r['product_nprice'], 0)
                    : '';
                $r['is_1kg_wholesale'] = true;

                if ($rank === 1) {
                    $r['badge_text'] = $lang === 'th' ? 'อันดับ 1 ขายดีสุด' : 'No.1 Best Seller';
                    $r['badge_class'] = 'badge-rank-1';
                } elseif ($rank === 2) {
                    $r['badge_text'] = $lang === 'th' ? 'อันดับ 2 ยอดนิยม' : 'No.2 Best Seller';
                    $r['badge_class'] = 'badge-rank-2';
                } elseif ($rank === 3) {
                    $r['badge_text'] = $lang === 'th' ? 'อันดับ 3 ยอดนิยม' : 'No.3 Best Seller';
                    $r['badge_class'] = 'badge-rank-3';
                } elseif ($rank <= 10) {
                    $r['badge_text'] = $lang === 'th' ? "อันดับ {$rank}" : "Top {$rank}";
                    $r['badge_class'] = 'badge-wholesale';
                } else {
                    $r['badge_text'] = $lang === 'th' ? 'เรทส่ง ฿590' : 'Wholesale ฿590';
                    $r['badge_class'] = 'badge-wholesale';
                }
                $bestSellers[] = $r;
            }
            if (!empty($bestSellers)) {
                @file_put_contents($cacheFile, json_encode($bestSellers, JSON_UNESCAPED_UNICODE));
            }
        } catch (Exception $e) {
            $bestSellers = [];
        }
    }

    // 4.2 Recommended Products: 12 Random from Top 30 Most-Viewed Yak Pho Products
    $recommendedProducts = [];
    try {
        $yakphoCats = '32, 38, 40, 34, 37, 43, 49, 50, 51, 23, 39, 55, 56, 57, 36, 13';
        $stmtRec = $db->prepare("
            SELECT p.product_id, p.product_code, p.productcat_id, p.product_price, p.product_nprice,
                   p.product_picture, p.scent_id, p.formula_id, p.product_view,
                   pc.prod_size, pc.prod_formula,
                   COALESCE(NULLIF(pt.product_name, ''), NULLIF(pt_en.product_name, ''), NULLIF(pt_th.product_name, ''), p.product_name) AS product_name,
                   COALESCE(NULLIF(pt.product_excerpt, ''), NULLIF(pt_en.product_excerpt, ''), NULLIF(pt_th.product_excerpt, ''), p.product_excerpt) AS product_excerpt
            FROM product p
            LEFT JOIN productcat pc ON p.productcat_id = pc.productcat_id
            LEFT JOIN product_translations pt ON p.product_id = pt.product_id AND pt.lang_code = ?
            LEFT JOIN product_translations pt_en ON p.product_id = pt_en.product_id AND pt_en.lang_code = 'en'
            LEFT JOIN product_translations pt_th ON p.product_id = pt_th.product_id AND pt_th.lang_code = 'th'
            WHERE p.product_del = 0 
              AND p.product_status = 1
              AND p.productcat_id IN ($yakphoCats)
            ORDER BY p.product_view DESC
            LIMIT 30
        ");
        $stmtRec->execute([$lang]);
        $recPool = $stmtRec->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($recPool)) {
            shuffle($recPool);
            $selectedPool = array_slice($recPool, 0, 12);
            foreach ($selectedPool as $i => $r) {
                $r['image_url'] = resolve_product_image($r['product_picture'], 'small', $i + 1);
                $r['price_formatted'] = number_format((float)$r['product_price'], 0);
                $r['nprice_formatted'] = (float)$r['product_nprice'] > (float)$r['product_price']
                    ? number_format((float)$r['product_nprice'], 0)
                    : '';
                $r['is_1kg_wholesale'] = in_array((int)$r['productcat_id'], [32, 38, 40], true);
                $r['badge_text'] = $lang === 'th' ? 'แนะนำ' : 'Featured';
                $r['badge_class'] = 'badge-rec';
                $recommendedProducts[] = $r;
            }
        }
    } catch (Exception $e) {
        $recommendedProducts = [];
    }

    $featuredProducts = !empty($bestSellers) ? $bestSellers : $recommendedProducts;

    // 5. Brand Story Intro
    $brandStory = [
        'title' => 'Touch of Thai Wellness',
        'subtitle' => '40 Years of Thai Herbal Heritage & Crown Tarragon Mastery',
        'html_content' => '',
        'image_url' => $urlBase . '/catalog/assets/img/brand_story.webp',
    ];
    try {
        $stmt = $db->prepare("
            SELECT b.image_file,
                   COALESCE(NULLIF(bt.intro_title, ''), NULLIF(bt_en.intro_title, ''), bt_th.intro_title) AS title,
                   COALESCE(NULLIF(bt.intro_subtitle, ''), NULLIF(bt_en.intro_subtitle, ''), bt_th.intro_subtitle) AS subtitle,
                   COALESCE(NULLIF(bt.html_content, ''), NULLIF(bt_en.html_content, ''), bt_th.html_content) AS html_content
            FROM yakpho_brandstory_intro b
            LEFT JOIN yakpho_brandstory_intro_translations bt ON b.intro_id = bt.intro_id AND bt.lang_code = ?
            LEFT JOIN yakpho_brandstory_intro_translations bt_en ON b.intro_id = bt_en.intro_id AND bt_en.lang_code = 'en'
            LEFT JOIN yakpho_brandstory_intro_translations bt_th ON b.intro_id = bt_th.intro_id AND bt_th.lang_code = 'th'
            LIMIT 1
        ");
        $stmt->execute([$lang]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $brandStory['title'] = $row['title'] ?: $brandStory['title'];
            $brandStory['subtitle'] = $row['subtitle'] ?: $brandStory['subtitle'];
            $brandStory['html_content'] = $row['html_content'] ?: '';
            $brandStory['image_url'] = resolve_cms_image($row['image_file'], 'catalog/assets/img/brand_story.webp');
        }
    } catch (Exception $e) {
        // Keep defaults
    }

    // 6. Wholesale Pricing Intro, Tiers & Features
    $pricingIntro = [
        'title' => $lang === 'th' ? 'เรทราคาส่ง YakPho Aroma' : 'YakPho Aroma Wholesale Rates',
        'subtitle' => $lang === 'th' ? 'ราคาต่อกิโลกรัม สำหรับทุกสูตรและทุกกลิ่น' : 'Price per kilogram for all formulas and scents',
    ];
    try {
        $stmtPi = $db->prepare("
            SELECT COALESCE(NULLIF(pit.intro_title, ''), NULLIF(pit_en.intro_title, ''), pit_th.intro_title) AS title,
                   COALESCE(NULLIF(pit.intro_subtitle, ''), NULLIF(pit_en.intro_subtitle, ''), pit_th.intro_subtitle) AS subtitle
            FROM yakpho_pricing_intro pi
            LEFT JOIN yakpho_pricing_intro_translations pit ON pi.intro_id = pit.intro_id AND pit.lang_code = ?
            LEFT JOIN yakpho_pricing_intro_translations pit_en ON pi.intro_id = pit_en.intro_id AND pit_en.lang_code = 'en'
            LEFT JOIN yakpho_pricing_intro_translations pit_th ON pi.intro_id = pit_th.intro_id AND pit_th.lang_code = 'th'
            WHERE pi.intro_id = 1
            LIMIT 1
        ");
        $stmtPi->execute([$lang]);
        $rowPi = $stmtPi->fetch(PDO::FETCH_ASSOC);
        if ($rowPi) {
            $pricingIntro['title'] = $rowPi['title'] ?: $pricingIntro['title'];
            $pricingIntro['subtitle'] = $rowPi['subtitle'] ?: $pricingIntro['subtitle'];
        }
    } catch (Exception $e) {}

    $pricingTiers = [];
    try {
        $stmt = $db->prepare("
            SELECT pt.tier_id, pt.tier_order,
                   COALESCE(NULLIF(ptt.tier_volume, ''), NULLIF(ptt_en.tier_volume, ''), ptt_th.tier_volume) AS tier_volume,
                   COALESCE(NULLIF(ptt.tier_price, ''), NULLIF(ptt_en.tier_price, ''), ptt_th.tier_price) AS tier_price
            FROM yakpho_pricing_tiers pt
            LEFT JOIN yakpho_pricing_tier_translations ptt ON pt.tier_id = ptt.tier_id AND ptt.lang_code = ?
            LEFT JOIN yakpho_pricing_tier_translations ptt_en ON pt.tier_id = ptt_en.tier_id AND ptt_en.lang_code = 'en'
            LEFT JOIN yakpho_pricing_tier_translations ptt_th ON pt.tier_id = ptt_th.tier_id AND ptt_th.lang_code = 'th'
            WHERE pt.tier_status = 1
            ORDER BY pt.tier_order ASC, pt.tier_id ASC
        ");
        $stmt->execute([$lang]);
        $pricingTiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $pricingTiers = [];
    }

    $pricingFeatures = [];
    try {
        $stmtPf = $db->prepare("
            SELECT pf.feature_id, pf.feature_icon,
                   COALESCE(NULLIF(pft.feature_text, ''), NULLIF(pft_en.feature_text, ''), pft_th.feature_text) AS feature_text
            FROM yakpho_pricing_features pf
            LEFT JOIN yakpho_pricing_feature_translations pft ON pf.feature_id = pft.feature_id AND pft.lang_code = ?
            LEFT JOIN yakpho_pricing_feature_translations pft_en ON pf.feature_id = pft_en.feature_id AND pft_en.lang_code = 'en'
            LEFT JOIN yakpho_pricing_feature_translations pft_th ON pf.feature_id = pft_th.feature_id AND pft_th.lang_code = 'th'
            WHERE pf.feature_status = 1
            ORDER BY pf.feature_order ASC, pf.feature_id ASC
        ");
        $stmtPf->execute([$lang]);
        $pricingFeatures = $stmtPf->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $pricingFeatures = [];
    }

    return [
        'hero_slides' => $heroSlides,
        'formula_intro' => $formulaIntro,
        'formulas' => $formulas,
        'scent_intro' => $scentIntro,
        'scents' => $scents,
        'featured_products' => $featuredProducts,
        'best_sellers' => $bestSellers,
        'recommended_products' => $recommendedProducts,
        'brand_story' => $brandStory,
        'pricing_intro' => $pricingIntro,
        'pricing_tiers' => $pricingTiers,
        'pricing_features' => $pricingFeatures,
    ];
}

/**
 * Get filtered and paginated catalog products for /products and api/products_filter.php
 * Defaults to Yak Pho Spa & Wellness products (maincat_id = 1)
 */
function get_catalog_products(PDO $db, array $filters = [], string $lang = 'th'): array
{
    $maincatId = isset($filters['maincat_id']) && $filters['maincat_id'] !== '' ? (int)$filters['maincat_id'] : 0;
    $catId = isset($filters['cat_id']) && $filters['cat_id'] !== '' ? (int)$filters['cat_id'] : 0;
    $formulaFilter = trim((string)($filters['formula'] ?? ''));
    $sizeFilter = trim((string)($filters['size'] ?? ''));
    $search = trim((string)($filters['q'] ?? ''));
    $sort = trim((string)($filters['sort'] ?? 'recommended'));
    $page = max(1, (int)($filters['page'] ?? 1));
    $limit = max(1, min(48, (int)($filters['limit'] ?? 12)));
    $offset = ($page - 1) * $limit;

    $where = ["p.product_del = 0", "p.product_status = 1"];
    $params = [];

    // Filter by maincat only when explicitly set and cat_id is not specified
    if ($maincatId > 0 && $catId === 0) {
        $where[] = "pc.maincat_id = ?";
        $params[] = $maincatId;
    }
    if ($catId > 0) {
        $where[] = "p.productcat_id = ?";
        $params[] = $catId;
    }
    if ($formulaFilter !== '') {
        if ($formulaFilter === 'hot') {
            $where[] = "p.productcat_id IN (32, 41, 49)";
        } elseif ($formulaFilter === 'cool') {
            $where[] = "p.productcat_id IN (38, 50, 56)";
        } elseif ($formulaFilter === 'massage') {
            $where[] = "p.productcat_id IN (40, 51, 46, 55, 57)";
        }
    }
    if ($sizeFilter !== '') {
        if ($sizeFilter === '1kg') {
            $where[] = "p.productcat_id IN (32, 38, 40, 57)";
        } elseif ($sizeFilter === '40g') {
            $where[] = "p.productcat_id IN (49, 50, 51)";
        }
    }
    if ($search !== '') {
        $where[] = "(p.product_name LIKE ? OR p.product_code LIKE ? OR pt.product_name LIKE ? OR pt_th.product_name LIKE ? OR pt_en.product_name LIKE ?)";
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like, $like);
    }

    $whereSql = implode(' AND ', $where);

    // Count total
    $countSql = "
        SELECT COUNT(DISTINCT p.product_id)
        FROM product p
        LEFT JOIN productcat pc ON p.productcat_id = pc.productcat_id
        LEFT JOIN product_translations pt ON p.product_id = pt.product_id AND pt.lang_code = ?
        LEFT JOIN product_translations pt_en ON p.product_id = pt_en.product_id AND pt_en.lang_code = 'en'
        LEFT JOIN product_translations pt_th ON p.product_id = pt_th.product_id AND pt_th.lang_code = 'th'
        WHERE $whereSql
    ";
    $stmtCount = $db->prepare($countSql);
    $stmtCount->execute(array_merge([$lang], $params));
    $totalRows = (int)$stmtCount->fetchColumn();
    $totalPages = max(1, (int)ceil($totalRows / $limit));

    // Order By
    $orderBy = "FIELD(pc.maincat_id, 1, 2, 3), p.productcat_id ASC, p.product_id ASC";
    if ($sort === 'price_asc') {
        $orderBy = "p.product_price ASC, p.product_id ASC";
    } elseif ($sort === 'price_desc') {
        $orderBy = "p.product_price DESC, p.product_id DESC";
    } elseif ($sort === 'newest') {
        $orderBy = "p.product_id DESC";
    }

    $sql = "
        SELECT p.product_id, p.product_code, p.productcat_id, p.product_price, p.product_nprice,
               p.product_picture, p.product_weight, 0 AS product_likes,
               pc.maincat_id, pc.prod_size, pc.prod_formula,
               COALESCE(NULLIF(pct.productcat_name, ''), NULLIF(pct_en.productcat_name, ''), NULLIF(pct_th.productcat_name, ''), pc.productcat_name) AS category_name,
               COALESCE(NULLIF(pt.product_name, ''), NULLIF(pt_en.product_name, ''), NULLIF(pt_th.product_name, ''), p.product_name) AS product_name,
               COALESCE(NULLIF(pt.product_excerpt, ''), NULLIF(pt_en.product_excerpt, ''), NULLIF(pt_th.product_excerpt, ''), p.product_excerpt) AS product_excerpt
        FROM product p
        LEFT JOIN productcat pc ON p.productcat_id = pc.productcat_id
        LEFT JOIN productcat_translations pct ON pc.productcat_id = pct.productcat_id AND pct.lang_code = ?
        LEFT JOIN productcat_translations pct_en ON pc.productcat_id = pct_en.productcat_id AND pct_en.lang_code = 'en'
        LEFT JOIN productcat_translations pct_th ON pc.productcat_id = pct_th.productcat_id AND pct_th.lang_code = 'th'
        LEFT JOIN product_translations pt ON p.product_id = pt.product_id AND pt.lang_code = ?
        LEFT JOIN product_translations pt_en ON p.product_id = pt_en.product_id AND pt_en.lang_code = 'en'
        LEFT JOIN product_translations pt_th ON p.product_id = pt_th.product_id AND pt_th.lang_code = 'th'
        WHERE $whereSql
        ORDER BY $orderBy
        LIMIT $limit OFFSET $offset
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute(array_merge([$lang, $lang], $params));
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check user wishlists for active likes
    $sessionId = session_id();
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $likedMap = [];
    if (!empty($rows)) {
        $pIds = array_column($rows, 'product_id');
        $placeholders = implode(',', array_fill(0, count($pIds), '?'));
        try {
            $stLike = $db->prepare("
                SELECT product_id FROM user_wishlist 
                WHERE (session_id = ? OR (user_id > 0 AND user_id = ?)) AND product_id IN ($placeholders)
            ");
            $stLike->execute(array_merge([$sessionId, $userId], $pIds));
            $likedMap = array_fill_keys($stLike->fetchAll(PDO::FETCH_COLUMN), true);
        } catch (Exception $e) {}
    }

    $products = [];
    foreach ($rows as $i => $r) {
        $price = (float)$r['product_price'];
        $nprice = (float)$r['product_nprice'];
        $pId = (int)$r['product_id'];
        $r['image_url'] = resolve_product_image($r['product_picture'], 'small', $i + 1);
        $r['price_formatted'] = number_format($price, 0);
        $r['nprice_formatted'] = $nprice > $price ? number_format($nprice, 0) : '';
        $r['is_1kg_wholesale'] = in_array((int)$r['productcat_id'], [32, 38, 40], true);
        $r['likes_count'] = max(0, (int)($r['product_likes'] ?? 0));
        $r['is_liked'] = !empty($likedMap[$pId]);
        $products[] = $r;
    }

    return [
        'products' => $products,
        'pagination' => [
            'total_items' => $totalRows,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'per_page' => $limit,
        ],
    ];
}

/**
 * Get Single Product Detail + Related Products for templates/product-detail.php
 */
function get_single_product_detail(PDO $db, $idOrCode, string $lang = 'th'): ?array
{
    $raw = trim((string)$idOrCode);
    if ($raw === '') return null;

    $isNumeric = ctype_digit($raw);
    $cond = $isNumeric ? "p.product_id = ?" : "(p.product_code = ? OR p.slug = ?)";
    $bind = $isNumeric ? [$lang, $lang, (int)$raw] : [$lang, $lang, $raw, $raw];

    $sql = "
        SELECT p.*,
               pc.maincat_id, pc.prod_size, pc.prod_formula,
               COALESCE(NULLIF(pct.productcat_name, ''), NULLIF(pct_en.productcat_name, ''), NULLIF(pct_th.productcat_name, ''), pc.productcat_name) AS category_name,
               COALESCE(NULLIF(pt.product_name, ''), NULLIF(pt_en.product_name, ''), NULLIF(pt_th.product_name, ''), p.product_name) AS trans_name,
               COALESCE(NULLIF(pt.product_excerpt, ''), NULLIF(pt_en.product_excerpt, ''), NULLIF(pt_th.product_excerpt, ''), p.product_excerpt) AS trans_excerpt,
               COALESCE(NULLIF(pt.product_detail, ''), NULLIF(pt_en.product_detail, ''), NULLIF(pt_th.product_detail, ''), p.product_detail) AS trans_detail,
               COALESCE(NULLIF(pt.product_unit, ''), NULLIF(pt_en.product_unit, ''), NULLIF(pt_th.product_unit, ''), p.product_unit) AS trans_unit
        FROM product p
        LEFT JOIN productcat pc ON p.productcat_id = pc.productcat_id
        LEFT JOIN productcat_translations pct ON pc.productcat_id = pct.productcat_id AND pct.lang_code = ?
        LEFT JOIN productcat_translations pct_en ON pc.productcat_id = pct_en.productcat_id AND pct_en.lang_code = 'en'
        LEFT JOIN productcat_translations pct_th ON pc.productcat_id = pct_th.productcat_id AND pct_th.lang_code = 'th'
        LEFT JOIN product_translations pt ON p.product_id = pt.product_id AND pt.lang_code = ?
        LEFT JOIN product_translations pt_en ON p.product_id = pt_en.product_id AND pt_en.lang_code = 'en'
        LEFT JOIN product_translations pt_th ON p.product_id = pt_th.product_id AND pt_th.lang_code = 'th'
        WHERE p.product_del = 0 AND $cond
        LIMIT 1
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($bind);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        return null;
    }

    $pid = (int)$product['product_id'];
    $catId = (int)$product['productcat_id'];
    $product['display_name'] = $product['trans_name'] ?: ('Product #' . $pid);
    $product['display_excerpt'] = $product['trans_excerpt'] ?: '';
    $product['display_detail'] = $product['trans_detail'] ?: '';
    $product['image_large'] = resolve_product_image($product['product_picture'], 'large', $pid);
    $product['image_small'] = resolve_product_image($product['product_picture'], 'small', $pid);
    $product['is_1kg_wholesale'] = in_array($catId, [32, 38, 40], true);
    $product['likes_count'] = max(0, (int)($product['product_likes'] ?? 0));
    $product['is_liked'] = false;
    $sessionId = session_id();
    $userId = (int)($_SESSION['user_id'] ?? 0);
    try {
        $stLike = $db->prepare("
            SELECT id FROM user_wishlist 
            WHERE (session_id = ? OR (user_id > 0 AND user_id = ?)) AND product_id = ? 
            LIMIT 1
        ");
        $stLike->execute([$sessionId, $userId, $pid]);
        $product['is_liked'] = (bool)$stLike->fetchColumn();
    } catch (Exception $e) {}

    // Gallery images if any
    $gallery = [$product['image_large']];
    try {
        $stmtImg = $db->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC");
        $stmtImg->execute([$pid]);
        foreach ($stmtImg->fetchAll(PDO::FETCH_COLUMN) as $imgFile) {
            $resolved = resolve_product_image($imgFile, 'large', $pid);
            if (!in_array($resolved, $gallery, true)) {
                $gallery[] = $resolved;
            }
        }
    } catch (Exception $e) {
        // Ignore if table/cols differ
    }
    $product['gallery'] = $gallery;

    // Related products (ดึงสินค้าหมวดเดียวกันก่อน ถ้าไม่ครบ 20 ให้ดึงสินค้าแนะนำ/ยอดนิยมมาเติมให้ครบ 20 รายการ)
    $related = [];
    $seenIds = [$pid];
    try {
        // 1. ดึงสินค้าในหมวดหมู่เดียวกัน
        $stmtRel = $db->prepare("
            SELECT p.product_id, p.product_code, p.productcat_id, p.product_price, p.product_nprice, p.product_picture,
                   COALESCE(NULLIF(pt.product_name, ''), NULLIF(pt_en.product_name, ''), NULLIF(pt_th.product_name, ''), p.product_name) AS product_name
            FROM product p
            LEFT JOIN product_translations pt ON p.product_id = pt.product_id AND pt.lang_code = ?
            LEFT JOIN product_translations pt_en ON p.product_id = pt_en.product_id AND pt_en.lang_code = 'en'
            LEFT JOIN product_translations pt_th ON p.product_id = pt_th.product_id AND pt_th.lang_code = 'th'
            WHERE p.productcat_id = ? AND p.product_id != ? AND p.product_del = 0 AND p.product_status = 1
            ORDER BY p.product_id ASC
            LIMIT 20
        ");
        $stmtRel->execute([$lang, $catId, $pid]);
        foreach ($stmtRel->fetchAll(PDO::FETCH_ASSOC) as $i => $r) {
            $r['image_url'] = resolve_product_image($r['product_picture'], 'small', $i + 1);
            $r['price_formatted'] = number_format((float)$r['product_price'], 0);
            $related[] = $r;
            $seenIds[] = (int)$r['product_id'];
        }

        // 2. ถ้าในหมวดเดิมมีไม่ถึง 20 รายการ ให้ดึงสินค้าจากหมวดอื่นๆ มาเติมให้ครบ 20 รายการ
        $needed = 20 - count($related);
        if ($needed > 0 && !empty($seenIds)) {
            $inClause = implode(',', array_map('intval', $seenIds));
            $stmtExtra = $db->prepare("
                SELECT p.product_id, p.product_code, p.productcat_id, p.product_price, p.product_nprice, p.product_picture,
                       COALESCE(NULLIF(pt.product_name, ''), NULLIF(pt_en.product_name, ''), NULLIF(pt_th.product_name, ''), p.product_name) AS product_name
                FROM product p
                LEFT JOIN product_translations pt ON p.product_id = pt.product_id AND pt.lang_code = ?
                LEFT JOIN product_translations pt_en ON p.product_id = pt_en.product_id AND pt_en.lang_code = 'en'
                LEFT JOIN product_translations pt_th ON p.product_id = pt_th.product_id AND pt_th.lang_code = 'th'
                WHERE p.product_id NOT IN ($inClause) AND p.product_del = 0 AND p.product_status = 1
                ORDER BY p.product_id DESC
                LIMIT $needed
            ");
            $stmtExtra->execute([$lang]);
            foreach ($stmtExtra->fetchAll(PDO::FETCH_ASSOC) as $i => $r) {
                $r['image_url'] = resolve_product_image($r['product_picture'], 'small', count($related) + $i + 1);
                $r['price_formatted'] = number_format((float)$r['product_price'], 0);
                $related[] = $r;
            }
        }
    } catch (Exception $e) {
        $related = [];
    }

    $product['related_products'] = $related;
    return $product;
}

/**
 * Smart Product Content Tabs Parser
 * 1. Checks <h3> / <h2> headings in rich text
 * 2. Checks standard bold section headers (สรรพคุณ, วิธีใช้, ส่วนประกอบ, ขนาดบรรจุ, ฯลฯ)
 * 3. Falls back cleanly to a single 'รายละเอียดสินค้า' tab
 */
function parse_product_content_tabs(string $html): array
{
    $html = trim($html);
    if ($html === '') return [];

    $tabs = [];

    // Rule 1: Split by <h3> or <h2> (Future-proof for Summernote)
    if (preg_match_all('/<h[23][^>]*>(.*?)<\/h[23]>/iu', $html, $m)) {
        $parts = preg_split('/<h[23][^>]*>.*?<\/h[23]>/iu', $html);
        $intro = trim($parts[0] ?? '');
        if ($intro !== '' && strip_tags($intro) !== '') {
            $tabs['รายละเอียดสินค้า'] = $intro;
        }
        foreach ($m[1] as $idx => $heading) {
            $tabName = trim(strip_tags($heading));
            $content = trim($parts[$idx + 1] ?? '');
            if ($tabName !== '' && $content !== '') {
                $tabs[$tabName] = $content;
            }
        }
        if (!empty($tabs)) return $tabs;
    }

    // Rule 2: Fallback for existing database products formatted with bold titles
    $knownHeaders = 'สรรพคุณทั่วไป|สรรพคุณ|คุณสมบัติ|วิธีการใช้|วิธีใช้|ส่วนประกอบสำคัญ|ส่วนประกอบ|ส่วนผสม|สารสกัด|เหมาะสำหรับ|ลักษณะเนื้อสัมผัส|ขนาดบรรจุ';
    $pattern = '/<p[^>]*>\s*<strong>\s*(' . $knownHeaders . ')\s*:?\s*<\/strong>(?:<br\s*\/?>)?/iu';

    if (preg_match_all($pattern, $html, $matches, PREG_OFFSET_CAPTURE)) {
        $splits = preg_split($pattern, $html);
        $intro = trim($splits[0] ?? '');
        if ($intro !== '' && strip_tags($intro) !== '') {
            $tabs['รายละเอียดสินค้า'] = $intro;
        }
        foreach ($matches[1] as $idx => $match) {
            $title = trim($match[0]);
            $body = trim($splits[$idx + 1] ?? '');
            if ($body !== '') {
                if (!preg_match('/^<p/i', $body) && preg_match('/<\/p>$/i', $body)) {
                    $body = '<p>' . $body;
                }
                $tabs[$title] = $body;
            }
        }
        if (!empty($tabs)) return $tabs;
    }

    // Rule 3: Single tab fallback
    return ['รายละเอียดสินค้า' => $html];
}

/**
 * Get the 12 Scents x 3 Formulas Wholesale Matrix Data (for B2B / Corporate / Spa Bulk Order)
 */
function get_wholesale_matrix_data(PDO $db, string $lang = 'th'): array
{
    // Ordered 12 scents matching exact user order & screenshot naming
    $scentOrder = [
        1  => ['scent_id' => 30, 'code_num' => '001', 'color_hex' => '#F5F1E8', 'name_th' => '01. สีขาว. กลิ่นต้นตำรับ', 'name_en' => 'Original', 'core_th' => 'กลิ่นต้นตำรับ'],
        2  => ['scent_id' => 29, 'code_num' => '002', 'color_hex' => '#EACC62', 'name_th' => '02. สีเหลือง. กลิ่นไพล', 'name_en' => 'Zingiber cassumunar', 'core_th' => 'กลิ่นไพล'],
        3  => ['scent_id' => 28, 'code_num' => '003', 'color_hex' => '#2E6F40', 'name_th' => '03. สีเขียว. กลิ่นเสลดพังพอน', 'name_en' => 'Barleria Oil', 'core_th' => 'กลิ่นเสลดพังพอน'],
        4  => ['scent_id' => 19, 'code_num' => '004', 'color_hex' => '#86C166', 'name_th' => '04. สีเขียว. กลิ่นตะไคร้หอม', 'name_en' => 'Lemongrass', 'core_th' => 'กลิ่นตะไคร้หอม'],
        5  => ['scent_id' => 27, 'code_num' => '005', 'color_hex' => '#74A12E', 'name_th' => '05. สีเขียว. กลิ่นหญ้าเอ็นยืด', 'name_en' => 'Plantain', 'core_th' => 'กลิ่นหญ้าเอ็นยืด'],
        6  => ['scent_id' => 20, 'code_num' => '006', 'color_hex' => '#9B7EDE', 'name_th' => '06. สีม่วง. กลิ่นลาเวนเดอร์', 'name_en' => 'Lavender', 'core_th' => 'กลิ่นลาเวนเดอร์'],
        7  => ['scent_id' => 21, 'code_num' => '007', 'color_hex' => '#4EA898', 'name_th' => '07. สีขาว. กลิ่นยูคาลิปตัส', 'name_en' => 'Eucalyptus', 'core_th' => 'กลิ่นยูคาลิปตัส'],
        8  => ['scent_id' => 25, 'code_num' => '008', 'color_hex' => '#FAF8F5', 'name_th' => '08. สีขาว. กลิ่นมะลิ', 'name_en' => 'Jasmine', 'core_th' => 'กลิ่นมะลิ'],
        9  => ['scent_id' => 24, 'code_num' => '009', 'color_hex' => '#E899A8', 'name_th' => '09. สีชมพู. กลิ่นกุหลาบ', 'name_en' => 'Rose', 'core_th' => 'กลิ่นกุหลาบ'],
        10 => ['scent_id' => 22, 'code_num' => '010', 'color_hex' => '#F3E196', 'name_th' => '10. สีเหลืองอ่อน. กลิ่นขิงมินท์', 'name_en' => 'Ginger Mint', 'core_th' => 'กลิ่นขิงมินท์'],
        11 => ['scent_id' => 23, 'code_num' => '011', 'color_hex' => '#EFECE6', 'name_th' => '11. สีขาว. กลิ่นดอกโมก', 'name_en' => 'Water jasmine', 'core_th' => 'กลิ่นดอกโมก'],
        12 => ['scent_id' => 26, 'code_num' => '012', 'color_hex' => '#FFFFFF', 'name_th' => '12. สีขาว. กลิ่นน้ำมันมะพร้าว', 'name_en' => 'Coconut Oil', 'core_th' => 'กลิ่นน้ำมันมะพร้าว'],
    ];

    // Fetch all 1kg products in categories 32 (Hot), 38 (Cool), 40 (Massage)
    $stmt = $db->prepare("
        SELECT p.product_id, p.product_code, p.productcat_id, p.product_price, p.scent_id, p.product_picture,
               COALESCE(NULLIF(pt.product_name, ''), NULLIF(pt_en.product_name, ''), NULLIF(pt_th.product_name, ''), p.product_name) AS product_name
        FROM product p
        LEFT JOIN product_translations pt ON p.product_id = pt.product_id AND pt.lang_code = ?
        LEFT JOIN product_translations pt_en ON p.product_id = pt_en.product_id AND pt_en.lang_code = 'en'
        LEFT JOIN product_translations pt_th ON p.product_id = pt_th.product_id AND pt_th.lang_code = 'th'
        WHERE p.productcat_id IN (32, 38, 40) AND p.product_del = 0 AND p.product_status = 1
    ");
    $stmt->execute([$lang]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $byCatAndScent = [32 => [], 38 => [], 40 => []];
    foreach ($rows as $r) {
        $cid = (int)$r['productcat_id'];
        $sid = (int)$r['scent_id'];
        $byCatAndScent[$cid][$sid] = $r;
    }

    $matrixRows = [];
    foreach ($scentOrder as $idx => $meta) {
        $sid = $meta['scent_id'];
        $hotProd = $byCatAndScent[32][$sid] ?? null;
        $coolProd = $byCatAndScent[38][$sid] ?? null;
        $massProd = $byCatAndScent[40][$sid] ?? null;

        $samplePic = $hotProd['product_picture'] ?? ($coolProd['product_picture'] ?? ($massProd['product_picture'] ?? ''));

        $matrixRows[] = [
            'index' => $idx,
            'code_num' => $meta['code_num'],
            'scent_id' => $sid,
            'color_hex' => $meta['color_hex'],
            'name_th' => $meta['name_th'],
            'name_en' => $meta['name_en'],
            'core_th' => $meta['core_th'],
            'scent_label' => $meta['name_th'] . ' ' . $meta['name_en'],
            'image_url' => resolve_product_image($samplePic, 'small', $idx),
            'hot' => [
                'product_id' => (int)($hotProd['product_id'] ?? 0),
                'product_code' => $hotProd['product_code'] ?? ('YP' . (int)($hotProd['product_id'] ?? 0)),
                'sku' => 'H' . $meta['code_num'],
                'code' => 'H' . $meta['code_num'],
                'name' => $hotProd['product_name'] ?? ('H' . $meta['code_num']),
                'price' => 690,
            ],
            'cool' => [
                'product_id' => (int)($coolProd['product_id'] ?? 0),
                'product_code' => $coolProd['product_code'] ?? ('YP' . (int)($coolProd['product_id'] ?? 0)),
                'sku' => 'C' . $meta['code_num'],
                'code' => 'C' . $meta['code_num'],
                'name' => $coolProd['product_name'] ?? ('C' . $meta['code_num']),
                'price' => 690,
            ],
            'massage' => [
                'product_id' => (int)($massProd['product_id'] ?? 0),
                'product_code' => $massProd['product_code'] ?? ('YP' . (int)($massProd['product_id'] ?? 0)),
                'sku' => 'B' . $meta['code_num'],
                'code' => 'B' . $meta['code_num'],
                'name' => $massProd['product_name'] ?? ('B' . $meta['code_num']),
                'price' => 690,
            ],
        ];
    }

    $priceRates = [
        ['min' => 1,   'price' => 690],
        ['min' => 6,   'price' => 590],
        ['min' => 10,  'price' => 560],
        ['min' => 20,  'price' => 530],
        ['min' => 30,  'price' => 500],
        ['min' => 50,  'price' => 490],
        ['min' => 100, 'price' => 470],
    ];

    $tiers = [
        ['min_kg' => 1,   'max_kg' => 5,   'rate' => 690, 'label_th' => '1 – 5 กก.',   'label_en' => '1 – 5 kg'],
        ['min_kg' => 6,   'max_kg' => 9,   'rate' => 590, 'label_th' => '6 – 9 กก.',   'label_en' => '6 – 9 kg'],
        ['min_kg' => 10,  'max_kg' => 19,  'rate' => 560, 'label_th' => '10 – 19 กก.', 'label_en' => '10 – 19 kg'],
        ['min_kg' => 20,  'max_kg' => 29,  'rate' => 530, 'label_th' => '20 – 29 กก.', 'label_en' => '20 – 29 kg'],
        ['min_kg' => 30,  'max_kg' => 49,  'rate' => 500, 'label_th' => '30 – 49 กก.', 'label_en' => '30 – 49 kg'],
        ['min_kg' => 50,  'max_kg' => 99,  'rate' => 490, 'label_th' => '50 – 99 กก.', 'label_en' => '50 – 99 kg'],
        ['min_kg' => 100, 'max_kg' => 999, 'rate' => 470, 'label_th' => '100+ กก.',    'label_en' => '100+ kg'],
    ];

    return [
        'matrix' => $matrixRows,
        'tiers' => $tiers,
        'price_rates' => $priceRates,
        'shipping_flat_th' => 60,
    ];
}

/**
 * Get Checkout Data (Provinces, Payment Categories, Bank Accounts)
 */
function get_checkout_page_data(PDO $db, string $lang = 'th'): array
{
    $provinces = [];
    try {
        $stmt = $db->query("SELECT provinces_id, name_th, name_en FROM provinces ORDER BY name_th ASC");
        $provinces = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $provinces = [];
    }

    $countries = [];
    try {
        $stmt = $db->query("SELECT country_id, country_name, country_code FROM countries ORDER BY country_name ASC");
        $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $countries = [];
    }

    $banks = [];
    try {
        $stmt = $db->query("SELECT bank_id, bank_name, bank_account, bank_number, bank_branch FROM bank WHERE bank_status = 1 AND bank_del = 0 ORDER BY bank_id ASC");
        $banks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $banks = [
            ['bank_name' => 'ธนาคารไทยพาณิชย์ (SCB)', 'bank_account' => 'บริษัท เอสเธอร์ อโรม่า จำกัด / ชุมพล อุปวงค์', 'bank_number' => '641-242029-0', 'bank_branch' => 'สำนักงานใหญ่']
        ];
    }

    $memberPoints = 0;
    $loggedUser = null;
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if ($userId > 0) {
        try {
            $stmt = $db->prepare("SELECT * FROM user WHERE user_id = ? AND user_del = 0 LIMIT 1");
            $stmt->execute([$userId]);
            $loggedUser = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($loggedUser) {
                unset($loggedUser['user_password'], $loggedUser['user_key']);
                $loggedUser['user_name']     = (string)($loggedUser['user_name'] ?? '');
                $loggedUser['user_mobile']   = (string)(!empty($loggedUser['user_mobile']) ? $loggedUser['user_mobile'] : ($loggedUser['user_tel'] ?? ''));
                $loggedUser['user_address']  = (string)($loggedUser['user_address'] ?? '');
                $loggedUser['user_postcode'] = (string)($loggedUser['user_postcode'] ?? '');
                $memberPoints = max((int)($loggedUser['m_stamp_points'] ?? 0), (int)($loggedUser['user_stars'] ?? 0));
                
                // Fetch all saved shipping addresses from addr (primary addr_position DESC first)
                $stA = $db->prepare("
                    SELECT a.addr_id, a.addr_name, a.addr_mobile, a.addr_detail, a.addr_detail2,
                           a.addr_postcode, a.provinces_id, a.country_id, a.addr_type, a.addr_position,
                           a.addr_forword, a.addr_tax_id, p.name_th AS province_name
                    FROM addr a
                    LEFT JOIN provinces p ON a.provinces_id = p.provinces_id
                    WHERE a.user_id = ? AND a.addr_del = 0
                    ORDER BY a.addr_position DESC, CASE WHEN a.addr_type = 1 THEN 0 ELSE 1 END, a.addr_id DESC
                ");
                $stA->execute([$userId]);
                $savedAddrList = $stA->fetchAll(PDO::FETCH_ASSOC) ?: [];
                $loggedUser['saved_addresses'] = $savedAddrList;
                $savedAddr = $savedAddrList[0] ?? null;
                if ($savedAddr) {
                    if (!empty($savedAddr['addr_name'])) {
                        $loggedUser['user_name'] = $savedAddr['addr_name'];
                    }
                    if (!empty($savedAddr['addr_mobile'])) {
                        $loggedUser['user_mobile'] = $savedAddr['addr_mobile'];
                    }
                    $loggedUser['user_address'] = $savedAddr['addr_detail'] ?: $loggedUser['user_address'];
                    $loggedUser['user_address_detail2'] = $savedAddr['addr_detail2'] ?: '';
                    $loggedUser['user_postcode'] = $savedAddr['addr_postcode'] ?: $loggedUser['user_postcode'];
                    if (!empty($savedAddr['provinces_id'])) {
                        $loggedUser['user_province'] = $savedAddr['provinces_id'];
                    }
                    if (!empty($savedAddr['country_id'])) {
                        $loggedUser['country_id'] = $savedAddr['country_id'];
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('[get_checkout_page_data] ' . $e->getMessage());
        }
    }

    return [
        'provinces'        => $provinces,
        'countries'        => $countries,
        'banks'            => $banks,
        'cart'             => get_cart_summary($db, $lang),
        'member_points'    => $memberPoints,
        'logged_user'      => $loggedUser,
        'affiliate_cookie' => $_COOKIE['yp_affiliate'] ?? '',
    ];
}

/**
 * Get Profile & Loyalty Data (Zero-DB in Template per md/AGENT.md)
 */
function get_profile_page_data(PDO $db, string $lang = 'th'): array
{
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $userData = null;
    $affiliateData = null;
    $orderCounts = [
        'to_pay'     => 0,
        'to_ship'    => 0,
        'to_receive' => 0,
        'completed'  => 0,
    ];
    $userOrders = [];

    if ($userId > 0) {
        try {
            $stmt = $db->prepare("SELECT * FROM user WHERE user_id = ?");
            $stmt->execute([$userId]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($userData && !empty($userData['affiliate_code'])) {
                $affStmt = $db->prepare("SELECT * FROM affiliates WHERE affiliate_code = ? LIMIT 1");
                $affStmt->execute([$userData['affiliate_code']]);
                $affiliateData = $affStmt->fetch(PDO::FETCH_ASSOC);
            }

            // Order status counts from database
            $cntStmt = $db->prepare("
                SELECT 
                    COUNT(CASE WHEN orders_status IN (1, 2) THEN 1 END) as to_pay,
                    COUNT(CASE WHEN orders_status IN (3, 4, 10) THEN 1 END) as to_ship,
                    COUNT(CASE WHEN orders_status IN (5, 11) THEN 1 END) as to_receive,
                    COUNT(CASE WHEN orders_status = 6 THEN 1 END) as completed
                FROM orders
                WHERE user_id = ? AND orders_del = 0
            ");
            $cntStmt->execute([$userId]);
            $cntRow = $cntStmt->fetch(PDO::FETCH_ASSOC);
            if ($cntRow) {
                $orderCounts = [
                    'to_pay'     => (int)($cntRow['to_pay'] ?? 0),
                    'to_ship'    => (int)($cntRow['to_ship'] ?? 0),
                    'to_receive' => (int)($cntRow['to_receive'] ?? 0),
                    'completed'  => (int)($cntRow['completed'] ?? 0),
                ];
            }

            // Fetch recent orders for user
            $ordStmt = $db->prepare("
                SELECT o.orders_id, o.orders_no, o.track_token, o.orders_date, 
                       o.orders_total, o.orders_delicost, o.orders_discount, o.orders_cod, o.orders_grandtotal,
                       o.orders_status, o.orders_tracking, o.paycat_id, o.transcat_id, o.orders_msg,
                       o.addr_id,
                       s.orsts_detail, s.orsts_color,
                       pay.paycat_name, pay.paycat_nshort,
                       tr.transcat_name, tr.transcat_link
                FROM orders o
                LEFT JOIN orsts s ON o.orders_status = s.orsts_id
                LEFT JOIN paycat pay ON o.paycat_id = pay.paycat_id
                LEFT JOIN transcat tr ON o.transcat_id = tr.transcat_id
                WHERE o.user_id = ? AND o.orders_del = 0
                ORDER BY o.orders_id DESC
                LIMIT 30
            ");
            $ordStmt->execute([$userId]);
            $userOrders = $ordStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}
    }

    // Support guest order lookup via search_order or order_no parameter
    $searchOrderNo = trim((string)($_GET['order_no'] ?? ($_GET['search_order'] ?? ($_SESSION['last_order_no'] ?? ''))));
    if ($userId === 0 && $searchOrderNo !== '') {
        try {
            $guestStmt = $db->prepare("
                SELECT o.orders_id, o.orders_no, o.track_token, o.orders_date, 
                       o.orders_total, o.orders_delicost, o.orders_discount, o.orders_cod, o.orders_grandtotal,
                       o.orders_status, o.orders_tracking, o.paycat_id, o.transcat_id, o.orders_msg,
                       o.addr_id,
                       s.orsts_detail, s.orsts_color,
                       pay.paycat_name, pay.paycat_nshort,
                       tr.transcat_name, tr.transcat_link
                FROM orders o
                LEFT JOIN orsts s ON o.orders_status = s.orsts_id
                LEFT JOIN paycat pay ON o.paycat_id = pay.paycat_id
                LEFT JOIN transcat tr ON o.transcat_id = tr.transcat_id
                WHERE (o.orders_no = ? OR o.orders_tracking = ?) AND o.orders_del = 0
                LIMIT 1
            ");
            $guestStmt->execute([$searchOrderNo, $searchOrderNo]);
            $gOrd = $guestStmt->fetch(PDO::FETCH_ASSOC);
            if ($gOrd) {
                $userOrders[] = $gOrd;
            }
        } catch (Exception $e) {}
    }

    // Populate order items and shipping addresses for each order (Zero-DB View Layer per md/AGENT.md)
    if (!empty($userOrders)) {
        try {
            $orderIds = array_column($userOrders, 'orders_id');
            $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
            
            // Fetch order items joined with product
            $itemStmt = $db->prepare("
                SELECT d.ordetail_id, d.orders_id, d.product_id, d.ordetail_qty, 
                       COALESCE(NULLIF(d.unit_price, 0), d.product_price, 0) as unit_price,
                       COALESCE(NULLIF(d.subtotal, 0), (d.ordetail_qty * COALESCE(NULLIF(d.unit_price, 0), d.product_price, 0))) as subtotal,
                       d.ordetail_memo,
                       p.product_name, p.product_code, 
                       COALESCE(NULLIF(p.product_picture, ''), p.product_img, '') as product_picture
                FROM ordetail d
                LEFT JOIN product p ON d.product_id = p.product_id
                WHERE d.orders_id IN ($placeholders)
                ORDER BY d.ordetail_id ASC
            ");
            $itemStmt->execute($orderIds);
            $itemsMap = [];
            foreach ($itemStmt->fetchAll(PDO::FETCH_ASSOC) as $it) {
                $itemsMap[$it['orders_id']][] = $it;
            }

            // Fetch shipping addresses
            $addrIds = array_filter(array_unique(array_column($userOrders, 'addr_id')));
            $addrMap = [];
            if (!empty($addrIds)) {
                $addrPlaceholders = implode(',', array_fill(0, count($addrIds), '?'));
                $addrStmt = $db->prepare("
                    SELECT a.addr_id, a.addr_name, a.addr_mobile, a.addr_detail, a.addr_postcode,
                           prov.name_th as province_name
                    FROM addr a
                    LEFT JOIN provinces prov ON a.provinces_id = prov.provinces_id
                    WHERE a.addr_id IN ($addrPlaceholders)
                ");
                $addrStmt->execute(array_values($addrIds));
                foreach ($addrStmt->fetchAll(PDO::FETCH_ASSOC) as $a) {
                    $addrMap[$a['addr_id']] = $a;
                }
            }

            foreach ($userOrders as &$ord) {
                $ord['items'] = $itemsMap[$ord['orders_id']] ?? [];
                $aId = (int)($ord['addr_id'] ?? 0);
                $ord['shipping_addr'] = $addrMap[$aId] ?? null;
            }
            unset($ord);
        } catch (Exception $e) {}
    }

    $userName      = $userData['user_name'] ?? ($_SESSION['user_name'] ?? ($_SESSION['customer_name'] ?? 'ลูกค้าทั่วไป (Guest)'));
    $userTel       = $userData['user_mobile'] ?? ($_SESSION['user_mobile'] ?? ($_SESSION['customer_tel'] ?? ''));
    $userEmail     = $userData['user_email'] ?? ($_SESSION['user_email'] ?? '');
    $emailVerified = (bool)($userData['email_verified'] ?? false);
    $memberTier    = $userData['member_tier'] ?? ($_SESSION['member_tier'] ?? 'general');
    $mStampPoints  = (int)($userData['m_stamp_points'] ?? ($_SESSION['m_stamp_points'] ?? 0));
    $lineUserId    = $userData['line_user_id'] ?? ($_SESSION['line_user_id'] ?? '');
    $lineName      = $userData['line_display_name'] ?? ($_SESSION['line_display_name'] ?? '');
    $linePic       = $userData['line_picture_url'] ?? ($_SESSION['line_picture_url'] ?? '');
    $affiliateCode = $userData['affiliate_code'] ?? ($_SESSION['affiliate_code'] ?? '');
    $tierInfo      = function_exists('get_member_tier_info') ? get_member_tier_info($memberTier) : ['badge' => '🌿', 'short_name' => 'General'];
    $referralUrl   = !empty($affiliateCode) ? (defined('ROOT_URL') ? ROOT_URL : '') . '/?ref=' . $affiliateCode : '';

    // Shipping Addresses & Provinces from DB
    $shippingAddress = [
        'addr_id'       => 0,
        'name'          => $userName,
        'mobile'        => $userTel,
        'detail'        => $userData['user_address'] ?? '',
        'detail2'       => '',
        'provinces_id'  => (int)($userData['user_province'] ?? 1),
        'province_name' => '',
        'postcode'      => $userData['user_postcode'] ?? '',
        'addr_type'     => 1,
        'addr_position' => 1,
        'addr_forword'  => '',
        'addr_tax_id'   => '',
    ];
    $userAddresses = [];
    $provincesList = [];

    try {
        $provStmt = $db->query("SELECT provinces_id, name_th FROM provinces WHERE provinces_id != 79 ORDER BY name_th ASC");
        $provincesList = $provStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {}

    if ($userId > 0) {
        try {
            $addrStmt = $db->prepare("
                SELECT a.*, p.name_th AS province_name
                FROM addr a
                LEFT JOIN provinces p ON a.provinces_id = p.provinces_id
                WHERE a.user_id = ? AND a.addr_del = 0
                ORDER BY a.addr_position DESC, CASE WHEN a.addr_type = 1 THEN 0 ELSE 1 END, a.addr_id DESC
            ");
            $addrStmt->execute([$userId]);
            $userAddresses = $addrStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Ensure at least one address is marked primary (addr_position = 1) if addresses exist
            if (!empty($userAddresses)) {
                $hasPrimary = false;
                foreach ($userAddresses as $ua) {
                    if ((int)($ua['addr_position'] ?? 0) === 1) {
                        $hasPrimary = true;
                        break;
                    }
                }
                if (!$hasPrimary) {
                    $userAddresses[0]['addr_position'] = 1;
                    try {
                        $db->prepare("UPDATE addr SET addr_position = 1 WHERE addr_id = ?")->execute([(int)$userAddresses[0]['addr_id']]);
                    } catch (Throwable $e2) {}
                }

                $addrRow = $userAddresses[0];
                $shippingAddress['addr_id']       = (int)($addrRow['addr_id'] ?? 0);
                if (!empty($addrRow['addr_name'])) $shippingAddress['name'] = $addrRow['addr_name'];
                if (!empty($addrRow['addr_mobile'])) $shippingAddress['mobile'] = $addrRow['addr_mobile'];
                if (!empty($addrRow['addr_detail'])) $shippingAddress['detail'] = $addrRow['addr_detail'];
                $shippingAddress['detail2']       = (string)($addrRow['addr_detail2'] ?? '');
                $shippingAddress['provinces_id']  = (int)($addrRow['provinces_id'] ?? 1);
                $shippingAddress['province_name'] = (string)($addrRow['province_name'] ?? '');
                if (!empty($addrRow['addr_postcode'])) $shippingAddress['postcode'] = $addrRow['addr_postcode'];
                $shippingAddress['addr_type']     = (int)($addrRow['addr_type'] ?? 1);
                $shippingAddress['addr_position'] = (int)($addrRow['addr_position'] ?? 1);
                $shippingAddress['addr_forword']  = (string)($addrRow['addr_forword'] ?? '');
                $shippingAddress['addr_tax_id']   = (string)($addrRow['addr_tax_id'] ?? '');
            }
        } catch (Exception $e) {}
    }

    // Parse Store & Interactive VIP Profile (user_note JSON + user columns)
    $crmNote = [];
    if (!empty($userData['user_note'])) {
        $decodedNote = json_decode((string)$userData['user_note'], true);
        if (is_array($decodedNote)) {
            $crmNote = $decodedNote;
        }
    }
    $storeProfile = [
        'nickname'             => (string)($userData['user_nickname'] ?? ($crmNote['nickname'] ?? '')),
        'birthdate'            => (string)($userData['user_birthdate'] ?? ($crmNote['birthdate'] ?? '')),
        'line_id'              => (string)($userData['line_display_name'] ?? ($crmNote['line_id'] ?? '')),
        'shop_name'            => (string)($crmNote['shop_name'] ?? ''),
        'shop_address'         => (string)($crmNote['shop_address'] ?? ''),
        'user_google_map'      => (string)($userData['user_google_map'] ?? ($crmNote['user_google_map'] ?? '')),
        'user_website'         => (string)($userData['user_website'] ?? ($crmNote['user_website'] ?? '')),
        'business_type'        => (string)($crmNote['business_type'] ?? ''),
        'favorite_scents'      => is_array($crmNote['favorite_scents'] ?? null) ? $crmNote['favorite_scents'] : [],
        'feedback'             => (string)($crmNote['feedback'] ?? ''),
        'vip_bonus_50_awarded' => !empty($crmNote['vip_bonus_50_awarded']),
    ];

    $selectedStatus = (string)($_GET['status'] ?? 'all');

    return [
        'user_id'          => $userId,
        'user_name'        => $userName,
        'user_tel'         => $userTel,
        'user_email'       => $userEmail,
        'email_verified'   => $emailVerified,
        'member_tier'      => $memberTier,
        'tier_info'        => $tierInfo,
        'm_stamp_points'   => $mStampPoints,
        'line_user_id'     => $lineUserId,
        'line_name'        => $lineName,
        'line_pic'         => $linePic,
        'affiliate_code'   => $affiliateCode,
        'affiliate_data'   => $affiliateData,
        'referral_url'     => $referralUrl,
        'shipping_address' => $shippingAddress,
        'user_addresses'   => $userAddresses,
        'provinces'        => $provincesList,
        'store_profile'    => $storeProfile,
        'order_counts'     => $orderCounts,
        'user_orders'      => $userOrders,
        'selected_status'  => $selectedStatus,
        'flash_success'    => $_SESSION['flash_success'] ?? null,
        'flash_error'      => $_SESSION['flash_error'] ?? null,
    ];
}

function ensure_default_yakpho_faqs(PDO $db): void
{
    try {
        // Ensure tables exist on both local and production databases
        $db->exec("CREATE TABLE IF NOT EXISTS `faqscat` (
            `faqscat_id` INT AUTO_INCREMENT PRIMARY KEY,
            `faqscat_name` VARCHAR(255) DEFAULT NULL,
            `faqscat_detail` TEXT DEFAULT NULL,
            `faqscat_index` INT DEFAULT 0,
            `faqscat_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `faqscat_status` INT DEFAULT 1,
            `faqscat_del` INT DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS `faqscat_translations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `faqscat_id` INT NOT NULL,
            `lang_code` VARCHAR(10) NOT NULL,
            `faqscat_name` VARCHAR(255) DEFAULT NULL,
            `faqscat_detail` TEXT DEFAULT NULL,
            UNIQUE KEY `uniq_faqscat_lang` (`faqscat_id`, `lang_code`),
            INDEX `idx_faqscat_id` (`faqscat_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS `faqs` (
            `faqs_id` INT AUTO_INCREMENT PRIMARY KEY,
            `faqscat_id` INT NOT NULL DEFAULT 1,
            `faqs_name` VARCHAR(500) DEFAULT NULL,
            `faqs_detail` TEXT DEFAULT NULL,
            `faqs_index` INT DEFAULT 0,
            `faqs_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `faqs_status` INT DEFAULT 1,
            `faqs_del` INT DEFAULT 0,
            `faqs_view` INT DEFAULT 0,
            INDEX `idx_faqscat_id` (`faqscat_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS `faqs_translations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `faqs_id` INT NOT NULL,
            `lang_code` VARCHAR(10) NOT NULL,
            `faqs_name` VARCHAR(500) DEFAULT NULL,
            `faqs_detail` TEXT DEFAULT NULL,
            UNIQUE KEY `uniq_faqs_lang` (`faqs_id`, `lang_code`),
            INDEX `idx_faqs_id` (`faqs_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $cnt = (int)$db->query("SELECT COUNT(*) FROM faqs WHERE faqs_del = 0 AND faqs_status = 1")->fetchColumn();
        if ($cnt > 0) {
            return;
        }

        // Seed 3 default Yak Pho FAQ Categories if faqscat has no active categories
        $catCnt = (int)$db->query("SELECT COUNT(*) FROM faqscat WHERE faqscat_del = 0 AND faqscat_status = 1")->fetchColumn();
        if ($catCnt === 0) {
            $defaultCats = [
                1 => [
                    'th_name' => 'สั่งซื้อและเรทราคาส่งสปา (Ordering & Wholesale)',
                    'th_desc' => 'ข้อมูลการสั่งซื้อ เรทราคาส่งร้านนวด และเงื่อนไขการเปิดบิล B2B',
                    'en_name' => 'Ordering & Spa Wholesale',
                    'en_desc' => 'Orders, spa wholesale pricing tiers, and B2B ordering procedures.',
                ],
                2 => [
                    'th_name' => 'ผลิตภัณฑ์และสรรพคุณ (Products & Ingredients)',
                    'th_desc' => 'สูตรบาล์ม ส่วนผสมสมุนไพรธรรมชาติ และวิธีใช้เพื่อผลลัพธ์สูงสุด',
                    'en_name' => 'Products & Ingredients',
                    'en_desc' => 'Balm formulas, herbal active ingredients, and application guides.',
                ],
                3 => [
                    'th_name' => 'การจัดส่งและบริการส่งออก (Shipping & Global Export)',
                    'th_desc' => 'รอบจัดส่งพัสดุในประเทศ และการจัดส่งสินค้าไปยังต่างประเทศตามมาตรฐาน GMP',
                    'en_name' => 'Shipping & Global Export',
                    'en_desc' => 'Domestic fulfillment timelines and international export with GMP compliance.',
                ],
            ];
            $insCat = $db->prepare("INSERT IGNORE INTO faqscat (faqscat_id, faqscat_name, faqscat_detail, faqscat_index, faqscat_date, faqscat_status, faqscat_del) VALUES (?, ?, ?, ?, NOW(), 1, 0)");
            $insCatTr = $db->prepare("INSERT IGNORE INTO faqscat_translations (faqscat_id, lang_code, faqscat_name, faqscat_detail) VALUES (?, ?, ?, ?)");
            foreach ($defaultCats as $cid => $cData) {
                $insCat->execute([$cid, $cData['th_name'], $cData['th_desc'], $cid]);
                $insCatTr->execute([$cid, 'th', $cData['th_name'], $cData['th_desc']]);
                $insCatTr->execute([$cid, 'en', $cData['en_name'], $cData['en_desc']]);
            }
        }

        // Fetch active category IDs
        $catIds = $db->query("SELECT faqscat_id FROM faqscat WHERE faqscat_del = 0 AND faqscat_status = 1 ORDER BY faqscat_index ASC, faqscat_id ASC")->fetchAll(PDO::FETCH_COLUMN);
        $cat1 = (int)($catIds[0] ?? 1);
        $cat2 = (int)($catIds[1] ?? $cat1);
        $cat3 = (int)($catIds[2] ?? $cat1);

        $defaultFaqs = [
            [
                'cat_id' => $cat1,
                'index'  => 1,
                'th_q'   => 'บาล์มสมุนไพรยักษ์โพธิ์ 1 กิโลกรัม เหมาะสำหรับร้านนวดและสปาอย่างไร?',
                'th_a'   => 'บาล์มขนาด 1 กิโลกรัม บรรจุในถุงฟอยล์สุญญากาศ Food-Grade ช่วยรักษาคุณค่าของน้ำมันหอมระเหยและสมุนไพรสดใหม่ได้ยาวนาน ไม่แห้งระเหยง่าย ให้ต้นทุนต่อหัวการนวดที่คุ้มค่าที่สุด โดย 1 ถุงสามารถใช้บริการลูกค้าได้มากกว่า 60-80 คอร์ส พร้อมเนื้อบาล์มเนียนนุ่ม ซึมซาบพอดีมือ ไม่เหนียวเหนอะหนะตัวลูกค้า',
                'en_q'   => 'Why is the 1kg Yak Pho Herbal Balm ideal for massage shops and spas?',
                'en_a'   => 'Packaged in food-grade vacuum foil bags, our 1kg balm preserves essential oils and fresh herbal potency longer while delivering the best cost per massage session (60-80 sessions per bag) with a smooth, non-greasy texture.',
            ],
            [
                'cat_id' => $cat1,
                'index'  => 2,
                'th_q'   => 'เรทราคาส่งสปา (Wholesale) เริ่มต้นที่กี่กิโลกรัม และมีเงื่อนไขอย่างไร?',
                'th_a'   => 'เรทราคาส่งร้านนวดและสปาเริ่มต้นสั่งซื้อตั้งแต่ 1 กิโลกรัมขึ้นไป โดยระบบจะคำนวณส่วนลดตามขั้นบันไดปริมาณสั่งซื้อให้อัตโนมัติ (Tier 1: 1-5 กก., Tier 2: 6-19 กก., Tier 3: 20 กก. ขึ้นไป) สามารถคละสูตรและกลิ่นได้ตามต้องการ พร้อมออกใบกำกับภาษีเต็มรูปแบบได้',
                'en_q'   => 'What is the minimum order quantity for spa wholesale pricing?',
                'en_a'   => 'Spa wholesale pricing starts from just 1 kg with automatic volume tier discounts (1-5 kg, 6-19 kg, 20+ kg). You can freely mix formulas and scents, and full tax invoices are supported.',
            ],
            [
                'cat_id' => $cat2,
                'index'  => 3,
                'th_q'   => 'น้ำมันสกัดทาร์รากอน (Tarragon Infused Oil) คืออะไร แตกต่างจากบาล์มทั่วไปอย่างไร?',
                'th_a'   => 'ทาร์รากอน (Tarragon) สายพันธุ์พิเศษที่เราเพาะปลูกในฟาร์มออร์แกนิก มีสารสำคัญกลุ่ม Estragole และฟลาโวนอยด์ธรรมชาติที่มีคุณสมบัติช่วยผ่อนคลายกล้ามเนื้อที่ตึงเกร็งเรื้อรัง เมื่อผสานเข้ากับไพลสด เสลดพังพอน และสมุนไพรไทยแท้ จะช่วยเพิ่มการไหลเวียนโลหิตและกระจายความอบอุ่นลึกสู่ชั้นกล้ามเนื้อได้ดียิ่งขึ้นอย่างเป็นเอกลักษณ์',
                'en_q'   => 'What is Tarragon Infused Oil and how does it elevate the balm?',
                'en_a'   => 'Organically grown Tarragon contains natural Estragole and flavonoids that soothe deep muscular tension. Combined with Plai and Clinacanthus nutans, it enhances micro-circulation and deep soothing warmth.',
            ],
            [
                'cat_id' => $cat2,
                'index'  => 4,
                'th_q'   => 'สูตรร้อน สูตรเย็น และสูตรนวดตัว แตกต่างกันอย่างไร เลือกรุ่นไหนดี?',
                'th_a'   => "1. สูตรร้อน (Hot Formula): เหมาะสำหรับอาการปวดเมื่อยลึก เส้นตึง ออฟฟิศซินโดรม และการกดจุดนวดไทย\n2. สูตรเย็น (Cool Formula): เหมาะสำหรับการคลายกล้ามเนื้อหลังออกกำลังกาย อาการบวมล้า และให้ความสดชื่นตื่นตัว\n3. สูตรนวดตัว (Body Massage Formula): เนื้อบาล์มลื่นนุ่มพิเศษ เหมาะสำหรับการนวดน้ำมันอโรม่าและนวดสวีดิชผ่อนคลายทั้งเรือนร่าง",
                'en_q'   => 'How do Hot, Cool, and Body Massage formulas differ?',
                'en_a'   => "1. Hot Formula: Best for deep muscle aches, stiff tendons, office syndrome, and Thai acupressure.\n2. Cool Formula: Ideal for post-workout recovery, swelling, and refreshing aromatherapy.\n3. Body Massage Formula: Extra-smooth glide crafted for full-body aroma and Swedish massage.",
            ],
            [
                'cat_id' => $cat3,
                'index'  => 5,
                'th_q'   => 'ผลิตภัณฑ์ของยักษ์โพธิ์มีเลขที่จดแจ้ง อย. และมาตรฐาน GMP หรือไม่?',
                'th_a'   => 'ผลิตภัณฑ์ทุกชิ้นภายใต้เครือ เอสเธอร์ อโรม่า และยักษ์โพธิ์ ผ่านการรับรองมาตรฐานการผลิตสากล GMP (Good Manufacturing Practice) และมีเลขที่ใบรับจดแจ้งจากสำนักงานคณะกรรมการอาหารและยา (อย.) ครบถ้วน ถูกต้องตามกฎหมาย ปลอดภัย 100%',
                'en_q'   => 'Are Yak Pho products certified by Thai FDA and GMP compliant?',
                'en_a'   => 'Every product under Esther Aroma and Yak Pho is manufactured in GMP-certified facilities with full Thai FDA registration numbers for 100% safety and legal compliance.',
            ],
            [
                'cat_id' => $cat3,
                'index'  => 6,
                'th_q'   => 'ทางบริษัทมีบริการส่งออกต่างประเทศและเอกสาร COA / MSDS หรือไม่?',
                'th_a'   => 'เราให้บริการส่งออกผลิตภัณฑ์สปาไทยไปยังกว่า 10 ประเทศทั่วโลก (เช่น ยุโรป, อเมริกา, ออสเตรเลีย, ญี่ปุ่น) พร้อมจัดเตรียมเอกสาร Certificate of Analysis (COA), Material Safety Data Sheet (MSDS) และ Certificate of Free Sale (CFS) สำหรับดำเนินพิธีการศุลกากรครบวงจร',
                'en_q'   => 'Do you support international export with COA and MSDS documentation?',
                'en_a'   => 'Yes, we export to over 10 countries worldwide and provide full Certificate of Analysis (COA), Material Safety Data Sheet (MSDS), and Certificate of Free Sale (CFS) documentation.',
            ],
        ];

        $insFaq = $db->prepare("INSERT INTO faqs (faqscat_id, faqs_name, faqs_detail, faqs_index, faqs_date, faqs_status, faqs_del, faqs_view) VALUES (?, ?, ?, ?, NOW(), 1, 0, 100)");
        $insFaqTr = $db->prepare("INSERT IGNORE INTO faqs_translations (faqs_id, lang_code, faqs_name, faqs_detail) VALUES (?, ?, ?, ?)");
        foreach ($defaultFaqs as $f) {
            $insFaq->execute([$f['cat_id'], $f['th_q'], $f['th_a'], $f['index']]);
            $newFaqId = (int)$db->lastInsertId();
            if ($newFaqId > 0) {
                $insFaqTr->execute([$newFaqId, 'th', $f['th_q'], $f['th_a']]);
                $insFaqTr->execute([$newFaqId, 'en', $f['en_q'], $f['en_a']]);
            }
        }
    } catch (Exception $e) {
        // Ignore if tables do not exist
    }
}

/**
 * Get FAQs (with multi-language translations & categories) and Gallery (with categories & images)
 */
function get_stores_and_faqs_data(PDO $db, string $lang = 'th'): array
{
    ensure_default_yakpho_faqs($db);

    $faqCategories = [];
    try {
        $stmtCat = $db->prepare("
            SELECT c.faqscat_id,
                   COALESCE(NULLIF(t_req.faqscat_name, ''), NULLIF(t_en.faqscat_name, ''), NULLIF(t_th.faqscat_name, ''), NULLIF(c.faqscat_name, ''), 'FAQs') AS category_name,
                   COALESCE(NULLIF(t_req.faqscat_detail, ''), NULLIF(t_en.faqscat_detail, ''), NULLIF(t_th.faqscat_detail, ''), NULLIF(c.faqscat_detail, ''), '') AS category_detail
            FROM faqscat c
            LEFT JOIN faqscat_translations t_req ON c.faqscat_id = t_req.faqscat_id AND t_req.lang_code = ?
            LEFT JOIN faqscat_translations t_en  ON c.faqscat_id = t_en.faqscat_id  AND t_en.lang_code = 'en'
            LEFT JOIN faqscat_translations t_th  ON c.faqscat_id = t_th.faqscat_id  AND t_th.lang_code = 'th'
            WHERE c.faqscat_status = 1 AND c.faqscat_del = 0
            ORDER BY c.faqscat_index ASC, c.faqscat_id ASC
        ");
        $stmtCat->execute([$lang]);
        $faqCategories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $faqCategories = [];
    }

    $faqs = [];
    try {
        $stmt = $db->prepare("
            SELECT f.faqs_id,
                   f.faqscat_id,
                   COALESCE(NULLIF(ft_req.faqs_name, ''), NULLIF(ft_en.faqs_name, ''), NULLIF(ft_th.faqs_name, ''), f.faqs_name) AS faqs_name,
                   COALESCE(NULLIF(ft_req.faqs_detail, ''), NULLIF(ft_en.faqs_detail, ''), NULLIF(ft_th.faqs_detail, ''), f.faqs_detail) AS faqs_detail,
                   COALESCE(NULLIF(ct_req.faqscat_name, ''), NULLIF(ct_en.faqscat_name, ''), NULLIF(ct_th.faqscat_name, ''), NULLIF(c.faqscat_name, ''), '') AS category_name
            FROM faqs f
            LEFT JOIN faqscat c ON f.faqscat_id = c.faqscat_id
            LEFT JOIN faqs_translations ft_req ON f.faqs_id = ft_req.faqs_id AND ft_req.lang_code = ?
            LEFT JOIN faqs_translations ft_en  ON f.faqs_id = ft_en.faqs_id  AND ft_en.lang_code = 'en'
            LEFT JOIN faqs_translations ft_th  ON f.faqs_id = ft_th.faqs_id  AND ft_th.lang_code = 'th'
            LEFT JOIN faqscat_translations ct_req ON f.faqscat_id = ct_req.faqscat_id AND ct_req.lang_code = ?
            LEFT JOIN faqscat_translations ct_en  ON f.faqscat_id = ct_en.faqscat_id  AND ct_en.lang_code = 'en'
            LEFT JOIN faqscat_translations ct_th  ON f.faqscat_id = ct_th.faqscat_id  AND ct_th.lang_code = 'th'
            WHERE f.faqs_status = 1 AND f.faqs_del = 0
              AND (c.faqscat_status IS NULL OR (c.faqscat_status = 1 AND c.faqscat_del = 0))
            ORDER BY COALESCE(c.faqscat_index, 99) ASC, f.faqs_index ASC, f.faqs_id ASC
        ");
        $stmt->execute([$lang, $lang]);
        $rawFaqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rawFaqs as $rf) {
            $rf['faq_id']   = (int)$rf['faqs_id'];
            $rf['question'] = (string)$rf['faqs_name'];
            $rf['answer']   = str_replace('\\n', "\n", (string)$rf['faqs_detail']);
            $faqs[] = $rf;
        }
    } catch (Exception $e) {
        $faqs = [];
    }

    // Attach grouped items into each faqCategory
    $faqsByCatId = [];
    foreach ($faqs as $fItem) {
        $cid = (int)($fItem['faqscat_id'] ?? 0);
        $faqsByCatId[$cid][] = $fItem;
    }

    $populatedFaqCategories = [];
    foreach ($faqCategories as $cat) {
        $cid = (int)$cat['faqscat_id'];
        $cat['items'] = $faqsByCatId[$cid] ?? [];
        if (!empty($cat['items'])) {
            $populatedFaqCategories[] = $cat;
            unset($faqsByCatId[$cid]);
        }
    }

    // If any FAQs belong to uncategorized or other categories, group them as well
    foreach ($faqsByCatId as $cid => $orphanItems) {
        if (!empty($orphanItems)) {
            $populatedFaqCategories[] = [
                'faqscat_id'      => $cid,
                'category_name'   => $orphanItems[0]['category_name'] ?: ($lang === 'th' ? 'คำถามทั่วไปเกี่ยวกับยักษ์โพธิ์' : 'General Questions'),
                'category_detail' => '',
                'items'           => $orphanItems,
            ];
        }
    }
    $faqCategories = $populatedFaqCategories;

    $galleryCategories = [];
    $gallery = [];
    try {
        $stmtGalCat = $db->prepare("
            SELECT c.category_id,
                   COALESCE(NULLIF(t_req.category_name, ''), NULLIF(t_en.category_name, ''), NULLIF(t_th.category_name, ''), c.category_name) AS category_name
            FROM yakpho_gallery_categories c
            LEFT JOIN yakpho_gallery_category_translations t_req ON c.category_id = t_req.category_id AND t_req.lang_code = ?
            LEFT JOIN yakpho_gallery_category_translations t_en  ON c.category_id = t_en.category_id  AND t_en.lang_code = 'en'
            LEFT JOIN yakpho_gallery_category_translations t_th  ON c.category_id = t_th.category_id  AND t_th.lang_code = 'th'
            ORDER BY c.sort_order ASC, c.category_id ASC
        ");
        $stmtGalCat->execute([$lang]);
        $catRows = $stmtGalCat->fetchAll(PDO::FETCH_ASSOC);

        $stmtGalImg = $db->query("SELECT image_id, category_id, image_file FROM yakpho_gallery_images ORDER BY sort_order ASC, image_id ASC");
        $imgRows = $stmtGalImg->fetchAll(PDO::FETCH_ASSOC);

        $imagesByCat = [];
        foreach ($imgRows as $i => $r) {
            $fallback = sprintf('catalog/assets/img/g%02d.webp', ($i % 24) + 1);
            $r['image_url'] = resolve_cms_image($r['image_file'], $fallback);
            $gallery[] = $r;
            $imagesByCat[(int)$r['category_id']][] = $r;
        }

        foreach ($catRows as $cat) {
            $cid = (int)$cat['category_id'];
            $cat['images'] = $imagesByCat[$cid] ?? [];
            if (!empty($cat['images'])) {
                $galleryCategories[] = $cat;
            }
        }
    } catch (Exception $e) {
        $gallery = [];
        $galleryCategories = [];
    }

    return [
        'faq_categories' => $faqCategories,
        'faqs' => $faqs,
        'gallery_categories' => $galleryCategories,
        'gallery' => $gallery,
    ];
}

/**
 * Format commenter name: nickname first; if empty, first name only
 */
function format_blog_commenter_name(array $uRow): string
{
    $nick = trim((string)($uRow['user_nickname'] ?? ''));
    if ($nick !== '') {
        return $nick;
    }
    $fullName = trim((string)($uRow['user_name'] ?? ''));
    if ($fullName !== '') {
        $parts = preg_split('/\s+/u', $fullName);
        if (!empty($parts[0])) {
            return $parts[0];
        }
        return $fullName;
    }
    $lineName = trim((string)($uRow['line_display_name'] ?? ''));
    if ($lineName !== '') {
        return $lineName;
    }
    return 'สมาชิก';
}

/**
 * Get Blog & News articles for frontend listing and single article detail
 */
function get_blog_posts_data(PDO $db, string $lang = 'th', $detailLookup = null): array
{
    $posts = [];
    $categories = [];
    $currentPost = null;
    $prevPost = null;
    $nextPost = null;
    $commentsTree = [];
    $totalCommentsCount = 0;

    try {
        // Ensure blog_likes and blog_comments tables exist
        $db->exec("CREATE TABLE IF NOT EXISTS `blog_likes` (
            `like_id` INT(11) NOT NULL AUTO_INCREMENT,
            `blog_id` INT(11) NOT NULL,
            `user_id` INT(11) NOT NULL DEFAULT 0,
            `visitor_token` VARCHAR(64) NOT NULL DEFAULT '',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`like_id`),
            UNIQUE KEY `uniq_blog_visitor` (`blog_id`, `visitor_token`),
            KEY `idx_blog_id` (`blog_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS `blog_comments` (
            `comment_id` INT(11) NOT NULL AUTO_INCREMENT,
            `blog_id` INT(11) NOT NULL,
            `parent_id` INT(11) NOT NULL DEFAULT 0,
            `user_id` INT(11) NOT NULL,
            `comment_text` TEXT NOT NULL,
            `likes_count` INT(11) NOT NULL DEFAULT 0,
            `comment_status` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`comment_id`),
            KEY `idx_blog_parent` (`blog_id`, `parent_id`),
            KEY `idx_user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $stmtCat = $db->query("SELECT blogcat_id, blogcat_name FROM blogcat WHERE blogcat_status = 1 AND blogcat_del = 0 ORDER BY blogcat_index ASC");
        if ($stmtCat) {
            $categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
        }

        $stmt = $db->query("
            SELECT b.blog_id, b.blogcat_id, b.blog_url, b.blog_name, b.blog_excerpt, b.blog_detail, b.blog_picture, b.blog_date, b.blog_view, b.blog_tag,
                   COALESCE(c.blogcat_name, 'สาระน่ารู้') AS blogcat_name,
                   (SELECT COUNT(*) FROM blog_likes bl WHERE bl.blog_id = b.blog_id) AS likes_count,
                   (SELECT COUNT(*) FROM blog_comments bc WHERE bc.blog_id = b.blog_id AND bc.comment_status = 1) AS comments_count
            FROM blog b
            LEFT JOIN blogcat c ON b.blogcat_id = c.blogcat_id
            WHERE b.blog_status = 1 AND b.blog_del = 0
            ORDER BY b.blog_date DESC, b.blog_id DESC
            LIMIT 48
        ");
        if ($stmt) {
            $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $baseDir = dirname(__DIR__);
            $urlBase = defined('URL_PATH') ? URL_PATH : '';
            foreach ($raw as $item) {
                $pic = $item['blog_picture'] ?? '';
                $img = $urlBase . '/catalog/assets/img/brand_story.webp';
                if (!empty($pic)) {
                    $candidates = [
                        "uploads/blog/{$pic}",
                        "uploads/blog/cover/{$pic}",
                        "uploads/blog/original-{$pic}",
                        "uploads/blog/medium-{$pic}",
                        "uploads/blog/small-{$pic}",
                    ];
                    foreach ($candidates as $cand) {
                        if (file_exists($baseDir . '/' . $cand)) {
                            $img = $urlBase . '/' . $cand;
                            break;
                        }
                    }
                }
                $item['image_url'] = $img;
                $item['detail_url'] = $urlBase . '/' . $lang . '/blog/' . (int)$item['blog_id'];
                $posts[] = $item;
            }
        }

        // If looking up a specific article (by ID or slug)
        $lookupStr = trim((string)($detailLookup ?? ''));
        if ($lookupStr !== '') {
            $foundIdx = null;
            foreach ($posts as $idx => $p) {
                if ((string)$p['blog_id'] === $lookupStr || (!empty($p['blog_url']) && $p['blog_url'] === $lookupStr)) {
                    $foundIdx = $idx;
                    break;
                }
            }

            if ($foundIdx !== null) {
                $currentPost = $posts[$foundIdx];
                $blogId = (int)$currentPost['blog_id'];

                // Increment view count once per request
                try {
                    $db->prepare("UPDATE blog SET blog_view = blog_view + 1 WHERE blog_id = ?")->execute([$blogId]);
                    $currentPost['blog_view'] = (int)$currentPost['blog_view'] + 1;
                } catch (Throwable $e) {}

                // Previous and Next articles (Left = newer/previous in list, Right = older/next in list)
                if (isset($posts[$foundIdx - 1])) {
                    $prevPost = $posts[$foundIdx - 1];
                }
                if (isset($posts[$foundIdx + 1])) {
                    $nextPost = $posts[$foundIdx + 1];
                }

                // Check if current visitor liked this post
                $userId = (int)($_SESSION['user_id'] ?? 0);
                if (empty($_SESSION['blog_visitor_token'])) {
                    $_SESSION['blog_visitor_token'] = bin2hex(random_bytes(16));
                }
                $visitorToken = $userId > 0 ? ('u_' . $userId) : ('s_' . $_SESSION['blog_visitor_token']);
                $chkLike = $db->prepare("SELECT like_id FROM blog_likes WHERE blog_id = ? AND visitor_token = ? LIMIT 1");
                $chkLike->execute([$blogId, $visitorToken]);
                $currentPost['user_liked'] = (bool)$chkLike->fetchColumn();

                // Load comments & replies (Facebook-style 2-level tree)
                $cStmt = $db->prepare("
                    SELECT c.*, u.user_name, u.user_nickname, u.line_display_name, u.line_picture_url
                    FROM blog_comments c
                    LEFT JOIN user u ON c.user_id = u.user_id
                    WHERE c.blog_id = ? AND c.comment_status = 1
                    ORDER BY c.created_at ASC, c.comment_id ASC
                ");
                $cStmt->execute([$blogId]);
                $allComms = $cStmt->fetchAll(PDO::FETCH_ASSOC);
                $totalCommentsCount = count($allComms);

                $parents = [];
                $repliesByParent = [];
                foreach ($allComms as $cRow) {
                    $dispName = format_blog_commenter_name($cRow);
                    $cItem = [
                        'comment_id'   => (int)$cRow['comment_id'],
                        'parent_id'    => (int)$cRow['parent_id'],
                        'user_id'      => (int)$cRow['user_id'],
                        'display_name' => $dispName,
                        'avatar_url'   => !empty($cRow['line_picture_url']) ? (string)$cRow['line_picture_url'] : '',
                        'initial'      => mb_strtoupper(mb_substr($dispName, 0, 1, 'UTF-8'), 'UTF-8') ?: 'U',
                        'comment_text' => (string)$cRow['comment_text'],
                        'likes_count'  => (int)$cRow['likes_count'],
                        'user_liked'   => !empty($_SESSION['liked_comment_' . (int)$cRow['comment_id']]),
                        'created_at'   => (string)$cRow['created_at'],
                        'created_text' => date('d M Y H:i', strtotime((string)$cRow['created_at'])),
                        'replies'      => [],
                    ];
                    if ($cItem['parent_id'] === 0) {
                        $parents[$cItem['comment_id']] = $cItem;
                    } else {
                        $repliesByParent[$cItem['parent_id']][] = $cItem;
                    }
                }
                foreach ($parents as $cid => &$pComm) {
                    if (!empty($repliesByParent[$cid])) {
                        $pComm['replies'] = $repliesByParent[$cid];
                    }
                }
                unset($pComm);
                // Show newest parent comments first or chronological (chronological like FB comments with replies inside)
                $commentsTree = array_values($parents);
            }
        }
    } catch (Exception $e) {
        $posts = [];
    }

    // Current logged-in user summary for comment box
    $currentUserInfo = null;
    $sessUid = (int)($_SESSION['user_id'] ?? 0);
    if ($sessUid > 0) {
        try {
            $uSt = $db->prepare("SELECT user_id, user_name, user_nickname, line_display_name, line_picture_url FROM user WHERE user_id = ? LIMIT 1");
            $uSt->execute([$sessUid]);
            $uR = $uSt->fetch(PDO::FETCH_ASSOC);
            if ($uR) {
                $dName = format_blog_commenter_name($uR);
                $currentUserInfo = [
                    'user_id'      => $sessUid,
                    'display_name' => $dName,
                    'avatar_url'   => !empty($uR['line_picture_url']) ? (string)$uR['line_picture_url'] : '',
                    'initial'      => mb_strtoupper(mb_substr($dName, 0, 1, 'UTF-8'), 'UTF-8') ?: 'U',
                ];
            }
        } catch (Throwable $e) {}
    }

    return [
        'posts'               => $posts,
        'categories'          => $categories,
        'current_post'        => $currentPost,
        'prev_post'           => $prevPost,
        'next_post'           => $nextPost,
        'comments'            => $commentsTree,
        'total_comments'      => $totalCommentsCount,
        'current_user'        => $currentUserInfo,
    ];
}

// Policy & Terms (Terms of Service / PDPA) helpers
require_once __DIR__ . '/policy_helper.php';
