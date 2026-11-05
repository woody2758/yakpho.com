<?php
declare(strict_types=1);

/**
 * YakPho Policy helper (shared by frontend router & admin/settings/policy.php)
 */

if (!function_exists('ensure_default_yakpho_policies')) {

/**
 * Ensure default Yak Pho Policies table (`yakpho_policies` + `yakpho_policies_translations`)
 * and seed comprehensive Terms of Service & PDPA Privacy Policy if not yet created.
 */
function ensure_default_yakpho_policies(PDO $db): void
{
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS `yakpho_policies` (
            `policy_id` INT(11) NOT NULL PRIMARY KEY,
            `terms_title` VARCHAR(255) NOT NULL DEFAULT '',
            `terms_subtitle` VARCHAR(255) NOT NULL DEFAULT '',
            `terms_html` LONGTEXT NULL,
            `privacy_title` VARCHAR(255) NOT NULL DEFAULT '',
            `privacy_subtitle` VARCHAR(255) NOT NULL DEFAULT '',
            `privacy_html` LONGTEXT NULL,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS `yakpho_policies_translations` (
            `translation_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `policy_id` INT(11) NOT NULL DEFAULT 1,
            `lang_code` VARCHAR(10) NOT NULL DEFAULT 'th',
            `terms_title` VARCHAR(255) NOT NULL DEFAULT '',
            `terms_subtitle` VARCHAR(255) NOT NULL DEFAULT '',
            `terms_html` LONGTEXT NULL,
            `privacy_title` VARCHAR(255) NOT NULL DEFAULT '',
            `privacy_subtitle` VARCHAR(255) NOT NULL DEFAULT '',
            `privacy_html` LONGTEXT NULL,
            UNIQUE KEY `uniq_policy_lang` (`policy_id`, `lang_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $chk = (int)$db->query("SELECT COUNT(*) FROM yakpho_policies WHERE policy_id = 1")->fetchColumn();
        if ($chk === 0) {
            $thTermsTitle = 'ข้อกำหนดและเงื่อนไขการใช้บริการ (Terms & Conditions)';
            $thTermsSub   = 'เงื่อนไขการสั่งซื้อปลีก-ส่ง การชำระเงิน การจัดส่ง และการรับประกันคุณภาพผลิตภัณฑ์ยักษ์โพธิ์';
            $thTermsHtml  = '<h4>1. ข้อมูลทั่วไปและขอบเขตการให้บริการ</h4>
<p>เว็บไซต์ <strong>YakPho.com</strong> ดำเนินการโดย <strong>บริษัท เอสเธอร์ อโรม่า จำกัด (Esther Aroma Co., Ltd.)</strong> ผู้ผลิตและจัดจำหน่ายผลิตภัณฑ์บาล์มนวดสมุนไพรและสปาอโรม่าภายใต้แบรนด์ <strong>"ยักษ์โพธิ์ (Yak Pho Aroma)"</strong> การเข้าใช้งานและสั่งซื้อสินค้าผ่านเว็บไซต์นี้ถือว่าท่านได้อ่าน เข้าใจ และตกลงยอมรับข้อกำหนดและเงื่อนไขการใช้บริการฉบับนี้ทุกประการ</p>

<h4>2. การสั่งซื้อและเรทราคาส่งร้านนวด &amp; สปา (B2B Wholesale)</h4>
<ul>
    <li><strong>ราคาปลีกและราคาส่งอัตโนมัติ:</strong> ระบบตะกร้าสินค้าและตารางสั่งซื้อราคาส่ง (Wholesale Matrix) จะคำนวณเรทราคาส่งให้อัตโนมัติตามน้ำหนักรวม (กิโลกรัม) หรือจำนวนแพ็คเกจที่กำหนด โดยสามารถคละสูตร (สูตรร้อน, สูตรเย็น, สูตรนวดตัว) และคละกลิ่นได้ทุกกลิ่น</li>
    <li><strong>ความถูกต้องของข้อมูลผู้รับ:</strong> ลูกค้ากรุณาตรวจสอบชื่อผู้รับ เบอร์โทรศัพท์ และที่อยู่จัดส่งให้ถูกต้องครบถ้วนก่อนยืนยันคำสั่งซื้อ เพื่อให้การจัดส่งพัสดุเป็นไปอย่างรวดเร็ว</li>
</ul>

<h4>3. การชำระเงินและระบบสะสมแต้ม M-Stamp</h4>
<ul>
    <li><strong>ช่องทางการชำระเงิน:</strong> รองรับการโอนเงินผ่านบัญชีธนาคารพาณิชย์, สแกน QR Code พร้อมเพย์ (PromptPay) และบริการเก็บเงินปลายทาง (COD) สำหรับการจัดส่งภายในประเทศไทย</li>
    <li><strong>บริการเก็บเงินปลายทาง (COD):</strong> บริษัทขนส่งเอกชนอาจมีค่าธรรมเนียมบริการเก็บเงินปลายทาง 3% จากยอดรวมตามเงื่อนไขของผู้ให้บริการขนส่ง</li>
    <li><strong>การสะสมและใช้แต้ม M-Stamp:</strong> สมาชิกจะได้รับแต้มสะสม M-Stamp ทุกยอดการสั่งซื้อที่ชำระเงินเสร็จสมบูรณ์ (ทุก 100 บาท = 1 แต้ม) โดย 1 แต้มมีมูลค่าส่วนลดเท่ากับ 1 บาท สำหรับใช้เป็นส่วนลดในการสั่งซื้อครั้งถัดไป</li>
</ul>

<h4>4. นโยบายการจัดส่งพัสดุ (ในประเทศและต่างประเทศ)</h4>
<ul>
    <li><strong>การจัดส่งภายในประเทศ:</strong> จัดส่งผ่านบริษัทขนส่งเอกชนชั้นนำ (อาทิ SPX Express, Flash Express, Kerry หรือไปรษณีย์ไทย) ใช้เวลาจัดส่งประมาณ 1–3 วันทำการหลังจากยืนยันการชำระเงินหรือยืนยันคำสั่งซื้อ</li>
    <li><strong>การจัดส่งต่างประเทศ (International Export):</strong> สำหรับลูกค้าสปาไทยในต่างประเทศ ระบบจะคำนวณน้ำหนักและแจ้งเรทค่าจัดส่งที่คุ้มค่าที่สุด พร้อมเอกสารมาตรฐานประกอบการนำเข้า (COA / MSDS ตามร้องขอ)</li>
</ul>

<h4>5. การรับประกันคุณภาพสินค้าและการเปลี่ยน/คืนสินค้า</h4>
<ul>
    <li>ผลิตภัณฑ์ทุกชิ้นผลิตภายใต้มาตรฐานโรงงานที่ได้รับการรับรอง ปราศจากสารสเตียรอยด์และโลหะหนัก</li>
    <li>หากสินค้าเกิดความเสียหายจากการขนส่ง ถุงรั่วซึม หรือได้รับสินค้าไม่ตรงตามรายการสั่งซื้อ ลูกค้าสามารถแจ้งทีมงานแอดมินผ่านช่องทาง LINE Official (<strong>@yakpho</strong>) พร้อมภาพถ่ายหลักฐานภายใน <strong>7 วัน</strong> นับจากวันที่ได้รับพัสดุ ทางบริษัทฯ ยินดีจัดส่งสินค้าชิ้นใหม่ทดแทนให้โดยไม่มีค่าใช้จ่ายเพิ่มเติม</li>
</ul>';

            $thPrivacyTitle = 'นโยบายความเป็นส่วนตัว (PDPA) & มาตรฐานการบริการ';
            $thPrivacySub   = 'การคุ้มครองข้อมูลส่วนบุคคลตาม พ.ร.บ. คุ้มครองข้อมูลส่วนบุคคล (PDPA) และมาตรฐานความปลอดภัยระดับสากล';
            $thPrivacyHtml  = '<h4>1. นโยบายคุ้มครองข้อมูลส่วนบุคคล (PDPA Compliance)</h4>
<p><strong>บริษัท เอสเธอร์ อโรม่า จำกัด</strong> ให้ความสำคัญสูงสุดต่อความเป็นส่วนตัวและความปลอดภัยของข้อมูลส่วนบุคคลของลูกค้าและพาร์ทเนอร์ร้านสปาทุกท่าน ตามพระราชบัญญัติคุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562 (PDPA)</p>

<h4>2. ข้อมูลส่วนบุคคลที่เราเก็บรวบรวม</h4>
<ul>
    <li><strong>ข้อมูลเพื่อการจัดส่งและออกใบเสร็จ:</strong> ชื่อ-นามสกุล, ชื่อร้านสปา/บริษัท, ที่อยู่จัดส่ง, เบอร์โทรศัพท์ และอีเมล</li>
    <li><strong>ข้อมูลการเชื่อมต่อบัญชีสมาชิก:</strong> ข้อมูลโปรไฟล์พื้นฐานจากการเข้าสู่ระบบผ่าน LINE Login (LINE User ID, ชื่อที่แสดง, รูปโปรไฟล์) เพื่อใช้แจ้งเตือนสถานะพัสดุอัตโนมัติและสะสมแต้ม M-Stamp</li>
    <li><strong>ข้อมูลพิกัดร้านค้าและโปรไฟล์ธุรกิจสปา:</strong> ชื่อร้าน พิกัดแผนที่ Google Maps และความชื่นชอบกลิ่นสมุนไพรที่ท่านระบุไว้เพื่อรับสิทธิพิเศษ VIP และแต้มสะสม</li>
</ul>

<h4>3. วัตถุประสงค์ในการใช้ข้อมูล</h4>
<ul>
    <li>เพื่อดำเนินการจัดเตรียมสินค้า จัดส่งพัสดุ แจ้งเลขพัสดุ (Tracking Number) และออกใบเสร็จรับเงิน/ใบกำกับภาษี</li>
    <li>เพื่อคำนวณสิทธิประโยชน์สมาชิก แต้มสะสม M-Stamp และค่าคอมมิชชันสำหรับพาร์ทเนอร์หมอนวด (Yak Pho Partner / Affiliate)</li>
    <li>ทางบริษัทฯ <strong>ไม่มีนโยบายนำข้อมูลของท่านไปจำหน่ายหรือเผยแพร่ให้แก่บุคคลภายนอก</strong> เว้นแต่เป็นการส่งต่อข้อมูลที่จำเป็นให้แก่บริษัทขนส่งเพื่อการจัดส่งพัสดุถึงมือท่านเท่านั้น</li>
</ul>

<h4>4. สิทธิของเจ้าของข้อมูลส่วนบุคคล</h4>
<p>ท่านสามารถเข้าถึง แก้ไข เพิ่มเติม หรือลบรายการที่อยู่จัดส่งและข้อมูลร้านค้าของท่านได้ด้วยตนเองตลอด 24 ชั่วโมงผ่านหน้า <strong>"โปรไฟล์ของฉัน (Member Profile)"</strong> หรือติดต่อแจ้งความประสงค์ผ่านฝ่ายบริการลูกค้าของบริษัทฯ</p>

<h4>5. มาตรฐานการผลิตและบริการระดับพรีเมียม (Service &amp; Quality Standards)</h4>
<ul>
    <li><strong>Natural &amp; Authentic:</strong> คัดสรรสมุนไพรไทยแท้สกัดเข้มข้น ผสานนวัตกรรม Tarragon Infused Oil เอกสิทธิ์เฉพาะยักษ์โพธิ์</li>
    <li><strong>Therapist-Friendly Formula:</strong> เนื้อบาล์มเนียนนุ่ม ลื่นมือ ซึมไว ไม่เหนียวเหนอะหนะ และอ่อนโยนต่อผิวมือของหมอนวดแม้ต้องสัมผัสต่อเนื่องตลอดวัน</li>
    <li><strong>Export Ready:</strong> รองรับเอกสาร Certificate of Analysis (COA) และ Material Safety Data Sheet (MSDS) สำหรับพาร์ทเนอร์ร้านนวดไทยและสปาชั้นนำทั่วโลก</li>
</ul>';

            $enTermsTitle = 'Terms & Conditions of Service';
            $enTermsSub   = 'Ordering terms, wholesale B2B pricing tiers, payment methods, shipping, and product warranty.';
            $enTermsHtml  = '<h4>1. General Overview</h4>
<p>Welcome to <strong>YakPho.com</strong>, operated by <strong>Esther Aroma Co., Ltd.</strong>, the official manufacturer and distributor of <strong>Yak Pho Aroma</strong> Thai herbal massage balms and spa aromatherapy products. By accessing and placing orders on this website, you agree to be bound by these Terms &amp; Conditions.</p>

<h4>2. Retail &amp; B2B Spa Wholesale Ordering</h4>
<ul>
    <li><strong>Automated Wholesale Tiers:</strong> Our shopping cart and Wholesale Matrix automatically calculate tiered wholesale rates based on total weight (kg) or package quantities. You may freely mix and match formulas (Hot, Cool, Body Massage) and all 12 signature scents.</li>
    <li><strong>Accurate Delivery Details:</strong> Please verify recipient name, phone number, and shipping address prior to confirming your order to ensure seamless dispatch.</li>
</ul>

<h4>3. Payment &amp; M-Stamp Loyalty Program</h4>
<ul>
    <li><strong>Accepted Payments:</strong> Bank transfer, PromptPay QR Code, and Cash on Delivery (COD within Thailand).</li>
    <li><strong>M-Stamp Rewards:</strong> Members earn 1 M-Stamp point for every 100 THB spent on completed orders. Each M-Stamp point equals 1 THB discount on future purchases.</li>
</ul>

<h4>4. Domestic &amp; International Shipping</h4>
<ul>
    <li><strong>Domestic Delivery (Thailand):</strong> Dispatched via leading couriers (SPX Express, Flash Express, Kerry, Thailand Post) within 1–3 business days.</li>
    <li><strong>International Export:</strong> We ship worldwide to Thai massage &amp; spa partners with full export documentation support (COA &amp; MSDS available upon request).</li>
</ul>

<h4>5. Quality Guarantee &amp; Replacements</h4>
<p>If any product arrives damaged or incorrect, please contact our support team via LINE Official (<strong>@yakpho</strong>) with photos within <strong>7 days</strong> of delivery for a complimentary replacement.</p>';

            $enPrivacyTitle = 'Privacy Policy (PDPA) & Service Standards';
            $enPrivacySub   = 'Personal Data Protection Act (PDPA) compliance and international spa quality assurance.';
            $enPrivacyHtml  = '<h4>1. Personal Data Protection (PDPA Compliance)</h4>
<p><strong>Esther Aroma Co., Ltd.</strong> is committed to protecting your personal data in strict compliance with Thailand\'s Personal Data Protection Act B.E. 2562 (PDPA).</p>

<h4>2. Information We Collect</h4>
<ul>
    <li><strong>Order &amp; Shipping Data:</strong> Name, spa/company name, shipping addressbook entries, phone number, and email address.</li>
    <li><strong>LINE Official &amp; Member Profile:</strong> Basic profile identifier when linking LINE Login for real-time parcel tracking alerts and M-Stamp loyalty points.</li>
    <li><strong>Spa Business Profile:</strong> Store location, Google Maps URL, and scent preferences submitted via our VIP portal.</li>
</ul>

<h4>3. How We Use Your Data</h4>
<p>Your information is used exclusively for order fulfillment, parcel delivery notifications, digital receipts, and loyalty rewards. We <strong>never sell or disclose your personal data</strong> to unauthorized third parties.</p>

<h4>4. Quality &amp; Service Standards</h4>
<ul>
    <li><strong>Authentic Thai Herbal Craftsmanship:</strong> Formulated with signature Tarragon Infused Oil and pure botanical extracts, free from steroids and heavy metals.</li>
    <li><strong>Therapist-Approved Texture:</strong> Smooth glide, rapid absorption, non-greasy finish, and gentle on therapists\' hands during all-day spa sessions.</li>
</ul>';

            $stmtIns = $db->prepare("INSERT INTO yakpho_policies (policy_id, terms_title, terms_subtitle, terms_html, privacy_title, privacy_subtitle, privacy_html, updated_at) VALUES (1, ?, ?, ?, ?, ?, ?, NOW())");
            $stmtIns->execute([$thTermsTitle, $thTermsSub, $thTermsHtml, $thPrivacyTitle, $thPrivacySub, $thPrivacyHtml]);

            $stmtTrans = $db->prepare("INSERT INTO yakpho_policies_translations (policy_id, lang_code, terms_title, terms_subtitle, terms_html, privacy_title, privacy_subtitle, privacy_html) VALUES (1, ?, ?, ?, ?, ?, ?, ?)");
            $stmtTrans->execute(['th', $thTermsTitle, $thTermsSub, $thTermsHtml, $thPrivacyTitle, $thPrivacySub, $thPrivacyHtml]);
            $stmtTrans->execute(['en', $enTermsTitle, $enTermsSub, $enTermsHtml, $enPrivacyTitle, $enPrivacySub, $enPrivacyHtml]);
        }
    } catch (Throwable $e) {
        // Silent fail if read-only
    }
}

/**
 * Get Policy & Terms page data for frontend `/th/policy`
 */
function get_policy_page_data(PDO $db, string $lang = 'th'): array
{
    ensure_default_yakpho_policies($db);

    $result = [
        'terms_title'      => $lang === 'th' ? 'ข้อกำหนดและเงื่อนไขการใช้บริการ' : 'Terms & Conditions',
        'terms_subtitle'   => '',
        'terms_html'       => '',
        'privacy_title'    => $lang === 'th' ? 'นโยบายความเป็นส่วนตัว (PDPA) & มาตรฐานการบริการ' : 'Privacy Policy (PDPA) & Service Standards',
        'privacy_subtitle' => '',
        'privacy_html'     => '',
        'updated_at'       => date('Y-m-d H:i:s'),
    ];

    try {
        $stmtMain = $db->query("SELECT * FROM yakpho_policies WHERE policy_id = 1 LIMIT 1");
        $main = $stmtMain ? $stmtMain->fetch(PDO::FETCH_ASSOC) : null;

        $stmtTr = $db->prepare("SELECT * FROM yakpho_policies_translations WHERE policy_id = 1 AND lang_code = ? LIMIT 1");
        $stmtTr->execute([$lang]);
        $tr = $stmtTr->fetch(PDO::FETCH_ASSOC);

        if (!$tr && $lang !== 'th') {
            $stmtTr->execute(['en']);
            $tr = $stmtTr->fetch(PDO::FETCH_ASSOC);
        }
        if (!$tr) {
            $stmtTr->execute(['th']);
            $tr = $stmtTr->fetch(PDO::FETCH_ASSOC);
        }

        if ($main) {
            $result['updated_at'] = $main['updated_at'] ?? $result['updated_at'];
            $result['terms_title']      = !empty($tr['terms_title']) ? $tr['terms_title'] : $main['terms_title'];
            $result['terms_subtitle']   = !empty($tr['terms_subtitle']) ? $tr['terms_subtitle'] : $main['terms_subtitle'];
            $result['terms_html']       = !empty($tr['terms_html']) ? $tr['terms_html'] : $main['terms_html'];
            $result['privacy_title']    = !empty($tr['privacy_title']) ? $tr['privacy_title'] : $main['privacy_title'];
            $result['privacy_subtitle'] = !empty($tr['privacy_subtitle']) ? $tr['privacy_subtitle'] : $main['privacy_subtitle'];
            $result['privacy_html']     = !empty($tr['privacy_html']) ? $tr['privacy_html'] : $main['privacy_html'];
        }
    } catch (Throwable $e) {}

    return $result;
}


}
