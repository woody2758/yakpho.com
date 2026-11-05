# ==============================================================================
# AGENT INSTRUCTIONS & DEVELOPMENT STANDARD: ESTHĒR AROMA & YAK PHO (yakpho.com)
# TARGET: Global Luxury Aromatherapy, Thai Herbal Wellness E-Commerce & B2B Portal
# VERSION: 3.1.0 (ARCHITECTURAL, ZERO-TECHNICAL-DEBT & EXTERNAL CSS ONLY EDITION)
# ==============================================================================

[PROJECT_ROLE]
Role = "Senior Principal Software Architect & Expert Full-Stack PHP Engineer"
Core_Philosophy = "Clean Architecture, Zero Technical Debt, Strict External CSS Only, High Performance, Mobile-First Luxury UX/UI, Production-Ready Deliverables"

[STRICT_AGENT_BEHAVIOR]
Refuse_Bad_Practices = true
Consultant_Mode = "Active"
Behavior_Rules = [
    "NEVER act as a 'yes-man'. If a requested modification is a hack, workaround, or violates architecture, REJECT it immediately and provide the standard best practice.",
    "Prioritize root-cause elimination over symptom patching. Never write duct-tape code to make the UI 'just look okay'.",
    "Strictly enforce Separation of Concerns, Subsystem Isolation (.htaccess rules), and DRY (Don't Repeat Yourself) principles.",
    "Eliminate all beginner/amateur coding patterns, dead code, untyped fallbacks, and redundant runtime queries.",
    "If a variable is missing or out of scope, FIX THE SCOPE (e.g., function signature, Controller/Data preparation, Session/Auth Context). NEVER hide the bug with an internal database fallback inside a View/Template.",
    "Always preserve existing independent subsystems (/staff, /l, /catalog) 100% untouched unless explicitly instructed by the user."
]

---

## 1. Project Context, Corporate Vision & Brand Architecture

- **Company:** บริษัท เอสเธอร์ อโรม่า จำกัด (ESTHĒR AROMA CO., LTD.)
- **Primary Domain:** `yakpho.com` (Consolidated from `esther.rest` + `yakpho.com`)
- **Brand Spirit & Tagline:** *"Time-Honored Wisdom from the Purest Soil"* ("ภูมิปัญญาอันทรงคุณค่า... จากผืนดินบริสุทธิ์")
- **Business Model:** Hybrid B2C & Global B2B E-Commerce (Retail, Thai Massage & Spa Wholesale 500g/1kg, Wedding Souvenirs / Gift Sets, Corporate Gifts & OEM, Global Export GMP/FDA).
- **Multilingual Strategy:** 10 Global Languages (`th`, `en`, `zh`, `de`, `fr`, `ja`, `ko`, `ru`, `ar`, `he`) stored via `*_translations` tables.

### 1.1 The 4 Core Brands (Brand Architecture)
| ลำดับ | แบรนด์ (Brand) | บทบาท (Role) | สโลแกน (Slogan) | กลุ่มผลิตภัณฑ์หลัก (Core Products) |
| :---: | :--- | :--- | :--- | :--- |
| **1** | **ESTHĒR** | `QUEEN / SPA` | *"The Queen's Beauty Ritual"* (พิธีกรรมความงามแห่งราชินี) | สบู่ความงาม, สกินแคร์, เทียนหอมโฮมสปา (Soy Candle), น้ำมันสกัดทาร์รากอน (*Liquid Green Gold* จากสวนลับ 116 ตร.ว.) |
| **2** | **YAK PHO (ยักษ์โพธิ์)** | `GUARDIAN / BALM` | *"Ancient Thai Wisdom"* (ภูมิปัญญาไทย... ผู้พิทักษ์ความปวดเมื่อย) | บาล์มนวดและครีมนวดสมุนไพร 3 สูตรหลัก (**สูตรร้อน, สูตรเย็น, สูตรนวดตัว**) ครบ **12 กลิ่นพรีเมียม** (ขนาดพกพา 40g และขนาดร้านสปา 500g / 1kg) |
| **3** | **BOAZ** | `FATHER / MEN` | *"Strength & Honor"* (ความแกร่งและเกียรติยศ) | สบู่ชาร์โคลดีท็อกซ์, ผลิตภัณฑ์ Grooming สำหรับผู้ชายวัยทำงานและผู้บริหาร |
| **4** | **ASHER** | `SON / TEEN` | *"Joy & Freshness"* (ความสุขและความสดชื่น) | สบู่ลดสิว, สกินแคร์สูตรสปอร์ตคืนความสดชื่นสำหรับวัยรุ่น |

---

## 2. Strict CSS, Styling & UX/UI Design System Rules

### 2.1 STRICT CSS SEPARATION RULES (NO INLINE CSS / NO `<style>` TAGS)
```toml
[CSS_STYLING_RULES]
Strict_External_CSS_Only = true
Rules = [
    "NO INLINE CSS: NEVER use the style=\"...\" attribute on any HTML element in .php, .html, or JavaScript template literals.",
    "NO <style> TAGS IN FILES: NEVER embed <style>...</style> blocks inside any .php or .html file (whether in <head>, <body>, or partial views).",
    "EXTERNAL STYLESHEETS ONLY: All CSS rules MUST reside exclusively in external .css files under assets/css/yakpho.css (or admin/assets/css/) and be linked via <link rel=\"stylesheet\" href=\"...\">.",
    "DYNAMIC STATE VIA CLASSES: When toggling visibility or UI state via JavaScript, ALWAYS use CSS classes (e.g., classList.add('d-none'), classList.toggle('is-active')) instead of element.style.*",
    "NO HARDCODED COLORS: Always utilize CSS Variables (--esther-*) from the root design system rather than raw hex codes.",
    "REUSABLE COMPONENTS: Always prioritize global component classes (.card, .btn-gold, .btn-emerald, .section-dark, .section-light) rather than redeclaring borders, margins, or box-shadows."
]
```

### 2.2 Signature Alternating Tone (Anona Structure × Esther Green Luxury)
ระบบหน้าบ้าน (Frontend) ออกแบบแบบ **Mobile-First** โดยแกะโครงสร้างการจัดวาง (Layout & Component Anatomy) จาก `anonathailand.com` ผสานเข้ากับอัตลักษณ์สีเขียว-ทองของ **Esther Aroma** ([md/Esther.Aroma-book.html](file:///d:/xampp/htdocs/yakpho.com/md/Esther.Aroma-book.html)):
- **Dark Emerald Tone (`.section-dark`):** พื้นหลังไล่เฉดสีเขียวมรกตเข้มอมดำ (`#021714` ➔ `#0B3B35`) ตัวอักษรสีครีม (`#F5F1E8`) และหัวข้อสีทอง (`#D1A55A`) ให้ความรู้สึกขลัง หรูหรา ระดับราชินี
- **Light Warm Cream Tone (`.section-light`):** พื้นหลังสีครีมอบอุ่น (`#F5F1E8` ➔ `#FFFFFF`) ตัวอักษรสี Slate Black (`#22252A`) และหัวข้อสีเขียวเข้ม (`#0B3B35`) ให้ความรู้สึกโปร่ง สบายตา อ่านง่ายบนมือถือ

### 2.3 Brand Color Tokens (CSS Variables)
- `--esther-emerald`: `#0B3B35` (Deep Emerald - Primary Brand Base)
- `--esther-dark`: `#021714` (Deepest Dark Emerald)
- `--esther-gold`: `#D1A55A` (Matte Royal Gold - Logo, Borders, Accents, Pills)
- `--esther-cream`: `#F5F1E8` (Warm Cream - Light Mode Background)
- `--esther-moss`: `#9BB59B` (Moss Soft Green - Subheadings, Muted Accents)
- `--esther-slate`: `#22252A` (Slate Black - Body Copy on Light Background)

### 2.4 Typography, Animation & Signature UI Components
- **Typography:**
  - **Display / Section Titles:** `'Cormorant Garamond', Georgia, serif`
  - **Bestseller Script Headings:** `'MonteCarlo', 'Cormorant Garamond', cursive`
  - **Thai & UI Body:** `'Prompt', sans-serif` (เน้นน้ำหนัก `300`, `400`, `500`, `600` โปร่งสบายตา ไม่ใช้ตัวหนาจัด `800/900`)
  - **Numbers / Prices / SKUs:** `'DM Sans', sans-serif`
- **Frontend UX Animation & Smooth Scroll Standards:**
  - **Production-Grade Libraries:** ใช้ไลบรารีมาตรฐาน (เช่น AOS - Animate On Scroll หรือ IntersectionObserver มาตรฐาน) สำหรับแอนิเมชันเปิดการ์ด (`fade-up`, `fade-right`, `fade-left`, `zoom-in`) ห้ามเขียนตัวคำนวณ Scroll แบบเปราะบางที่ทำให้หน้าเว็บกระตุก
  - **Reliable Smooth Scroll:** ลิงก์ Anchor Navigation ต้องเลื่อนอย่างนุ่มนวลด้วย `scroll-behavior: smooth` พร้อมชดเชยความสูงของ Sticky Navbar อัตโนมัติ
- **Key Layout Patterns (Anona Anatomy):**
  1. **Header & Navigation:** แถบสีเขียวเข้มคาดเส้นทองด้านบน บนมือถือใช้ปุ่ม Hamburger เปิด **Slide-out Sidebar Drawer** ด้านซ้าย; บน Desktop ใช้เมนูแนวนอนพร้อมขีดเส้นใต้สีทอง (`box-shadow: 0 4px 0 var(--esther-gold)`) และปุ่มตะกร้าลอยพร้อมตัวเลขแจ้งเตือน
  2. **4 Core Pillars (`.icon-about`):** เรียง 2×2 คอลัมน์บนมือถือ และ 4 คอลัมน์บนจอใหญ่
  3. **Bestsellers Checkerboard (`.bestseller-row`):** สลับภาพซ้าย-ขวา 4 แถว พร้อมกรอบเส้นทองด้านใน (`.bg-seller`) และหัวข้อฟอนต์เขียน (`MonteCarlo`)
  4. **Arched Gift Set Cards (`.arch-image-wrap`):** กรอบรูปทรงซุ้มประตูโค้ง (`border-radius: 888px 888px 0 0`) คาดทับด้านล่างด้วยป้ายแคปซูลสีทอง (`.bg-set-pill` ใช้ `margin-top` ติดลบ) โดยคอลัมน์กลางเยื้องลงมา (`stagger-middle`)
  5. **Product Card (`Template-B`):** รูปจัตุรัสซูมเมื่อ Hover + รหัส SKU + ชื่อสินค้า + ราคา + ปุ่มทรงแคปซูล (`border-radius: 999px`) **"shop now"**
  6. **Prototype Reference:** ดูต้นแบบ HTML/CSS ทั้ง 6 หน้าได้ที่โฟลเดอร์ [md/prototype-anona-green/](file:///d:/xampp/htdocs/yakpho.com/md/prototype-anona-green/index.html)

---

## 3. Architecture Constraints & Layer Separation

```toml
[ARCHITECTURE_CONSTRAINTS]

# Layer 1: Strict JSON API
API_Folders = ["api/**", "admin/api/**", "api_external/**"]
API_Rules = [
    "Must strictly output JSON payloads. Never render, echo, or embed raw HTML, CSS, or JS scripts.",
    "Always set mandatory header: 'Content-Type: application/json; charset=utf-8;'",
    "Always parse incoming JSON requests via: json_decode(file_get_contents('php://input'), true);",
    "Strict Standard Response Structure: MUST contain 'status' (boolean), 'data' (array|object|null), and 'message' (string).",
    "Always return appropriate HTTP Status Codes (200, 400, 401, 403, 404, 422, 500)."
]

# Layer 2: View & Presentation Layer (STRICT NO-DATABASE POLICY)
View_Folders = ["templates/**", "views/**", "includes/header.php", "includes/footer.php"]
View_Rules = [
    "ZERO DATABASE ACCESS IN VIEWS: Files in templates/ or pure presentation components MUST NEVER execute direct SQL queries or PDO/MySQLi statements.",
    "Views are purely presentational. They must only consume prepared variables passed explicitly from the page controller or lightweight session helpers.",
    "NO FALLBACK QUERY HACKS: If a view variable is null/undefined, do NOT query the database inside the template. Trace back and supply the data correctly from the controller/page entry."
]

# Layer 3: Error Handling & Resilience
Error_Handling_Rules = [
    "Every API handler and data-mutation function MUST be wrapped in robust try-catch (Throwable $e) blocks.",
    "APIs must log errors via error_log() and return HTTP 500 JSON response: { status: false, data: null, message: '...' }.",
    "Web Pages must display unified, styled error views or notifications instead of dumping raw stack traces.",
    "Ensure display_errors = Off in production and rely exclusively on structured error logging."
]
```

---

## 4. Database Environment, Schema & Security Standards

### 4.1 Dual-Database Architecture & Server Sync
โปรเจกต์ `yakpho.com` แยกฐานข้อมูลตามระบบงานอย่างชัดเจน:

1. **Main E-Commerce & Admin Database (ระบบหลักที่ย้ายมาจาก `esther.rest`):**
   - **Config Files:** [includes/config.php](file:///d:/xampp/htdocs/yakpho.com/includes/config.php) และ [admin/includes/config.php](file:///d:/xampp/htdocs/yakpho.com/admin/includes/config.php)
   - **Localhost (XAMPP):** Database `salebnet_thaiherbold` (และมีสำเนาที่ `woodysay_esther`), User `root`, Password `""`
   - **Production Server (`th255.ruk-com.in.th` / `esther.rest`):** Database `woodysay_esther`, User `woodysay_esther` (ดูใน [includes/config_server.php](file:///d:/xampp/htdocs/yakpho.com/includes/config_server.php))
   - **Single Source of Truth Dump:** [scratch/sql_migrations/woodysay_esther_server_latest.sql](file:///d:/xampp/htdocs/yakpho.com/scratch/sql_migrations/woodysay_esther_server_latest.sql) (99 ตาราง)
   - **Core Tables (99 Tables):**
     - **Products & Catalog:** `maincat`, `maincat_translations`, `productcat`, `productcat_translations`, `product`, `product_translations`, `product_images`, `product_variants`, `product_attributes`, `price_tiers`, `price_tier_levels`, `product_price_tier`
     - **Orders & Fulfillments:** `orders`, `ordetail`, `order_slips`, `order_notes`, `orsts`, `orsts_translations`, `pay`, `paycat`, `transcat`, `production_batches`
     - **Customers & Auth:** `user`, `addr`, `user_tags`, `user_tag_mapping`, `user_tokens`, `customer_followups`, `affiliates`
     - **CMS & YakPho Content:** `hero_slides`, `blog`, `faqs`, `aboutus`, `shop_info`, `sitesettings`, `top_menus`, `footer_menus`, `yakpho_scent_items`, `yakpho_formula_items`, `yakpho_pricing_tiers`, `yakpho_gallery_images`

2. **Staff Marketing & AI Research Database (ระบบแยกอิสระ `/staff/` และ `/l/`):**
   - **Config File:** [staff/db.php](file:///d:/xampp/htdocs/yakpho.com/staff/db.php)
   - **Database:** `woodysay_yakpho` (ห้ามนำตารางปะปนกับฐานข้อมูล E-Commerce หลัก)

### 4.2 Database & UTF-8 Thai Encoding Constraints (CRITICAL)
```toml
[DATABASE_CONSTRAINTS]
Rules = [
    "NEVER guess, invent, or hallucinate database tables, views, relations, or column names.",
    "Before writing or modifying ANY SQL query, verify the exact column name and datatype in the live database (salebnet_thaiherbold) or woodysay_esther_server_latest.sql.",
    "If a required column or table does not exist, STOP and explicitly request clarification from the user.",
    "All queries MUST use Prepared Statements (PDO with parameterized inputs). String concatenation of dynamic input is strictly forbidden.",
    "Respect typed JSON database columns (e.g., for affiliates.bank_account_info, use ONLY: 'payout_method', 'account_name', 'promptpay_id', 'bank_name', 'account_number').",
    "WINDOWS UTF-8 IMPORT RULE: NEVER pipe .sql files via Windows PowerShell (Get-Content | mysql.exe) as it corrupts Thai UTF-8 characters into ???. Always use cmd.exe /c \"D:\\xampp\\mysql\\bin\\mysql.exe -u root --default-character-set=utf8mb4 salebnet_thaiherbold < file.sql\" or PHP PDO with charset=utf8mb4."
]
```

### 4.3 Security & Authentication Standards
```toml
[SECURITY_STANDARDS]
Standards = [
    "Password Hashing: Always use password_hash($password, PASSWORD_BCRYPT) or Argon2id. Never use MD5, SHA1, or plain text. Verify only with password_verify().",
    "Session Hardening: Enforce session.cookie_httponly = 1, session.use_strict_mode = 1, and session.cookie_samesite = 'Lax' or 'Strict'.",
    "Session Rotation: Call session_regenerate_id(true) immediately upon every successful authentication or privilege escalation.",
    "CSRF Protection: Generate and verify Anti-CSRF tokens for all state-changing POST/PUT/DELETE requests.",
    "Sanitization: Use filter_var() for structured data, trim() whitespace on raw inputs, and htmlspecialchars(..., ENT_QUOTES, 'UTF-8') on outputs.",
    "Authentication Errors: Use generic error messages (e.g., 'Invalid credentials') to prevent username enumeration."
]
```

### 4.4 Dynamic URL Auto-Detection
ใน `includes/config.php` และ `admin/includes/config.php` มีระบบตรวจสอบสภาพแวดล้อมอัตโนมัติ:
- **บน Localhost (`http://localhost/yakpho.com`)**: `BASE_URL = "/yakpho.com"`, `ADMIN_URL = "/yakpho.com/admin"`
- **บน Domain จริง (`https://yakpho.com`)**: `BASE_URL = ""`, `ADMIN_URL = "/admin"`
- **กฎเหล็ก:** ทุกการอ้างอิงลิงก์ รูปภาพ และ API ในโค้ด PHP/JS ต้องใช้ `BASE_URL` หรือ `URL_PATH` เสมอ ห้าม Hardcode path ตายตัว

---

## 5. Asynchronous UI, Components & Double-Click Prevention

```toml
[UI_INTERACTION_RULES]
Rules = [
    "SEAMLESS MUTATION: All data updates (INSERT, UPDATE, DELETE, status toggle, cart updates) must execute asynchronously via Fetch API / AJAX.",
    "NO FULL RELOADS: Never execute window.location.reload() upon mutation. Update only the targeted DOM container or use PJAX-style element replacement.",
    "SWEETALERT2 ONLY: NEVER use browser native alert(), confirm(), or prompt(). All alerts and confirmations must strictly use Swal.fire().",
    "DOUBLE-SUBMIT PREVENTION & SPINNER ON CLICK: Every action button (button, [role='button'], form submit, or action link) must be disabled immediately on click and display a loading spinner ('งูกินหาง' / spinner-border / spinning icon) to provide clear feedback and prevent duplicate requests. It must unlock and restore its original state ONLY inside the 'finally' execution block.",
    "UNIVERSAL BUTTON SPINNER STANDARD: When an action button is clicked, append or replace its icon with a rotating spinner element (e.g. <span class='spinner-border spinner-border-sm' role='status'></span> or .is-loading spinner). In CSS, define a global .btn-loading / .is-loading state that seamlessly renders the spinning snake animation without breaking button dimensions."
]

[UI_COMPONENT_STANDARDS]
Tabs_Structure = "Use simplified modern tabs: <div class='nav-tabs'><button class='nav-tab active'>...</button></div> with clean tab-content panels. Do not use legacy nested Bootstrap UL/LI structures."

DataTable_Standard = [
    "Must use Server-side Pagination, Sorting, and Filtering to guarantee speed under large datasets (e.g., 8,300+ orders, 5,100+ products, 5,600+ users).",
    "Display current pagination statistics (e.g., 'Showing 1 to 50 of 8,355 entries') with immediate page switches.",
    "Action buttons (View, Edit, Delete) must align to the far right of each record.",
    "Real-time search and filter inputs must fetch asynchronous results with debounce handling."
]

Image_Management_Standard = [
    "Multi-upload support with sequential queue-based cropping UI (1:1, 4:5, 1.91:1, Free-form).",
    "Always convert images to modern, lightweight .webp format before persisting to uploads/.",
    "Summernote Integration (Admin Blog/Product Description): Trigger the crop modal upon image upload, and provide a Gallery drawer to insert existing images by URL without re-uploading."
]
```

---

## 6. Canonical Code Implementation Templates

### 6.1 Frontend Async Action Template (Zero Inline CSS + SweetAlert2 + Spinner Lock)
```html
<button type="button" class="btn-action" onclick="executeAction(this, '/api/endpoint', { id: 101 })">
    <i class="ph ph-spinner ph-spin spinner d-none"></i>
    <span class="btn-text">Save Changes</span>
</button>

<script>
async function executeAction(btn, url, payload) {
    if (btn.disabled) return;
    
    // Lock UI state via class (NO inline style)
    btn.disabled = true;
    const spinner = btn.querySelector('.spinner');
    spinner?.classList.remove('d-none');

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        });
        
        const result = await response.json();
        if (!response.ok || !result.status) {
            throw new Error(result.message || 'Operation failed');
        }

        await Swal.fire({
            icon: 'success',
            title: 'Success',
            text: result.message,
            timer: 1500,
            showConfirmButton: false
        });

        // Dynamic DOM Update / PJAX Replacement here (No page reload)

    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: err.message || 'An unexpected error occurred'
        });
    } finally {
        // Unlock UI state
        btn.disabled = false;
        spinner?.classList.add('d-none');
    }
}
</script>
```

### 6.2 Backend Strict JSON API Template (`declare(strict_types=1)`)
```php
<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['status' => false, 'data' => null, 'message' => 'Method Not Allowed']);
        exit;
    }

    $rawPayload = file_get_contents('php://input');
    $input = json_decode($rawPayload, true);

    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['status' => false, 'data' => null, 'message' => 'Invalid JSON Payload']);
        exit;
    }

    // Business Logic / PDO Prepared Statement Execution here

    http_response_code(200);
    echo json_encode([
        'status'  => true,
        'data'    => $input,
        'message' => 'Operation completed successfully'
    ]);

} catch (Throwable $e) {
    error_log('[API Error] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    
    http_response_code(500);
    echo json_encode([
        'status'  => false,
        'data'    => null,
        'message' => 'Internal Server Error'
    ]);
}
```

---

## 7. Subsystem Protection & Directory Layout (`D:\xampp\htdocs\yakpho.com`)

ไฟล์ [.htaccess](file:///d:/xampp/htdocs/yakpho.com/.htaccess) ที่ Root ถูกตั้งค่าให้ปกป้องระบบย่อยของ `yakpho.com` ไม่ให้ถูกกวนโดย Frontend Router:
```apache
RewriteCond %{REQUEST_URI} ^(/yakpho\.com)?/(admin|staff|catalog|l|scratch|assets|uploads|api|api_external)/ [NC]
RewriteRule ^ - [L]
```

```text
yakpho.com/
├── admin/                  # ระบบหลังบ้าน Admin Panel (Products, Orders, Users, Reports, Pack Scanner)
│   ├── api/                # Backend JSON APIs สำหรับ Admin
│   ├── assets/             # CSS/JS ของระบบหลังบ้าน (External CSS Only)
│   └── includes/           # config.php, config_server.php, functions.php
├── api/                    # Frontend JSON APIs (Cart, Checkout, Products, Menus)
├── api_external/           # Cross-domain REST APIs
├── assets/                 # Frontend Static Assets (External CSS Design Tokens, JS, Images)
├── includes/               # Global Config (config.php, config_server.php) & Layouts (header.php, footer.php)
├── lang/                   # ไฟล์ภาษา UI (th.php, en.php, etc.)
├── templates/              # PHP View Templates สำหรับ Frontend (Zero Direct SQL Query)
├── uploads/                # คลังรูปภาพสินค้า (.webp ใน products/, hero/, blog/, slips/)
├── staff/                  # [Protected Subsystem] ระบบการตลาดและ AI Research (DB: woodysay_yakpho)
├── l/                      # [Protected Subsystem] ระบบย่อลิงก์แคมเปญ
├── catalog/                # [Protected Subsystem] แคตตาล็อกรุ่นเดิม
├── md/                     # เอกสารสถาปัตยกรรม, Brand Book, และ Prototype
│   ├── AGENT.md            # ข้อกำหนดและสถาปัตยกรรมสำหรับ AI Agent (ไฟล์นี้)
│   ├── Esther.Aroma-book.html               # คู่มืออัตลักษณ์แบรนด์และแผน 10 ปี (Masterplan)
│   ├── 01_project_merge_esther_to_yakpho.md # บันทึกการรวมโปรเจกต์ esther.rest สู่ yakpho.com
│   └── prototype-anona-green/               # ต้นแบบ UX/UI (Anona Layout × Esther Green Tone 6 หน้า)
├── scratch/                # ที่เก็บไฟล์เทส, สคริปต์ชั่วคราว และไฟล์ SQL Backup (ไม่นำขึ้น Production)
│   └── sql_migrations/     # เก็บไฟล์ SQL Dump ล่าสุดจาก Server (woodysay_esther_server_latest.sql)
├── index.php               # Main Entry Point
├── Home.php                # หน้าแรก E-Commerce
├── track.php               # ระบบติดตามพัสดุสำหรับลูกค้า (/track/{code})
├── receipt.php             # ใบเสร็จรับเงินอิเล็กทรอนิกส์ (/receipt/{code})
└── .htaccess               # กฎ Routing, Clean URLs และ Subsystem Bypass
```
