# 📑 เอกสารบันทึกการรวมโปรเจกต์ (Project Consolidation)
**จาก Esther.rest สู่โดเมนหลัก Yakpho.com**

> **วันที่บันทึก:** 6 ตุลาคม 2026  
> **สถานะ:** รวมระบบและทดสอบการทำงานบน Localhost สำเร็จ (HTTP 200 ทุกระบบ)  
> **ที่ตั้งโปรเจกต์:** `D:\xampp\htdocs\yakpho.com`

---

## 1. 🎯 วัตถุประสงค์และเป้าหมาย (Objectives)
1. ย้ายระบบ E-commerce ทั้งส่วนหน้าบ้าน (Frontend ตัวใหม่) และหลังบ้าน (Admin Panel) จากโฟลเดอร์ `esther.rest` มาเป็นแกนหลักที่ Root ของโดเมน `yakpho.com`
2. **รักษาและคงสภาพระบบเดิมของ Yakpho.com ให้ทำงานได้ 100%** โดยไม่มีการแก้ไขโค้ดหรือกระทบการทำงาน:
   * `/staff` : ระบบการตลาด และ AI Research ค้นหาข้อมูลภายใน
   * `/l` : ระบบย่อลิงก์ติดตามแคมเปญ (Facebook Link Tracking)
   * `/catalog` : หน้ารายการสินค้าเวอร์ชันเดิม (สำหรับเปิดดูอ้างอิง)
3. **จัดระเบียบไฟล์ให้สะอาด (Clean Production Ready)**: คัดแยกไฟล์เทส, สคริปต์ตรวจสอบ, และไฟล์ชั่วคราวที่ไม่เกี่ยวข้องกับ Production ไปเก็บรวมไว้ที่โฟลเดอร์ `scratch/` เพื่อให้สามารถตรวจสอบย้อนหลังได้โดยไม่รกระบบหลัก

---

## 2. 🏗️ โครงสร้างระบบใหม่ (Project Architecture)

```text
D:\xampp\htdocs\yakpho.com/
│
├── 🌐 ส่วนแกนหลัก E-commerce (นำเข้าจาก esther.rest)
│   ├── 📁 admin/          ⭐ ระบบหลังบ้าน Admin Panel (จัดการสินค้า, ออเดอร์, สมาชิก)
│   ├── 📁 api/            ⭐ API Endpoints สำหรับ Frontend / AJAX
│   ├── 📁 api_external/   ⭐ ระบบเชื่อมต่อ API ภายนอก
│   ├── 📁 assets/         ⭐ Static Assets (CSS, JS, Fonts, Media)
│   ├── 📁 includes/       ⭐ Config, Layouts (Header/Footer), Shared Functions
│   ├── 📁 lang/           ⭐ ไฟล์แปลภาษา (th, en)
│   ├── 📁 templates/      ⭐ HTML/PHP Templates สำหรับหน้าเว็บ
│   ├── 📁 uploads/        ⭐ คลังไฟล์ภาพสินค้า สลิป แบนเนอร์ (~740 MB)
│   ├── 📄 index.php       ⭐ หน้าแรกหลัก (Root Frontend ตัวใหม่)
│   ├── 📄 Home.php        ⭐ หน้าโฮมเพจ
│   ├── 📄 track.php       ⭐ ระบบติดตามสถานะพัสดุสำหรับลูกค้า
│   ├── 📄 receipt.php     ⭐ ระบบใบเสร็จรับเงินอิเล็กทรอนิกส์
│   └── 📄 order-status.php
│
├── 🛡️ ระบบเดิมของ Yakpho (คงไว้ 100% ไม่กระทบ)
│   ├── 📁 staff/          🟢 ระบบจัดการตลาด & AI Research ภายใน (DB: woodysay_yakpho)
│   ├── 📁 l/              🟢 ระบบย่อลิงก์แคมเปญ Redirect (เชื่อมกับ staff)
│   └── 📁 catalog/        🟡 แคตตาล็อกเดิม
│
├── ⚙️ การควบคุมระบบ
│   ├── 📄 .htaccess       🛡️ คุม Clean URLs, Multilang และ Bypass ระบบย่อย
│   └── 📁 md/             📚 เอกสารคู่มือและการพัฒนา
│
└── 🗄️ แหล่งเก็บไฟล์สำหรับตรวจสอบ (Scratch & Non-Production)
    └── 📁 scratch/
        ├── 📁 yakpho_tests/        # ไฟล์เทสเดิมของ yakpho (test_db_tables.php ฯลฯ)
        ├── 📁 esther_admin_temp/   # ไฟล์ check_*.php, test_*.php รวม 31 ไฟล์ที่คัดออกจาก admin
        ├── 📁 esther_root_temp/    # สคริปต์เทสหน้า root เดิมของ esther (check_col.php ฯลฯ)
        ├── 📁 sql_migrations/      # ไฟล์คำสั่ง .sql สำหรับ migrate ฐานข้อมูล
        ├── 📁 esther_bin/          # สคริปต์เครื่องมือภายใน (_bin)
        ├── 📁 esther_notes/        # บันทึกของนักพัฒนา
        ├── 📁 esther_scratch/      # โค้ด scratch เดิมจาก esther
        └── 📁 esther_temp/         # โค้ด temp เดิมจาก esther
```

---

## 3. ⚙️ การตั้งค่าระบบสำคัญ (Key Technical Configurations)

### 3.1 Dynamic URL Auto-Detection
ระบบได้รับการปรับปรุงใน `includes/config.php` และ `admin/includes/config.php` ให้รองรับการทำงานอัตโนมัติทั้งบนเครื่องจำลอง (Localhost) และเซิร์ฟเวอร์จริง (Production):
* **เมื่อรันบน Localhost (`/yakpho.com`)**: 
  * ระบบจะกำหนด `BASE_URL = "/yakpho.com"` และ `ADMIN_URL = "/yakpho.com/admin"`
* **เมื่อรันบน Domain จริง (`https://yakpho.com`)**:
  * ระบบจะกำหนด `BASE_URL = ""` และ `ADMIN_URL = "/admin"` อัตโนมัติโดยไม่ต้องกลับมาแก้ไขโค้ด

### 3.2 การตั้งค่า Bypass และ Routing ใน `.htaccess`
มีการเพิ่มเงื่อนไข Rewrite Condition เพื่อป้องกันไม่ให้ Frontend Router เข้าไปรบกวนโฟลเดอร์ของระบบย่อย:
```apache
# 🛡️ Bypass Sub-systems & Static Directories:
RewriteCond %{REQUEST_URI} ^(/yakpho\.com)?/(admin|staff|catalog|l|scratch|assets|uploads|api|api_external)/ [NC]
RewriteRule ^ - [L]
```
ส่งผลให้:
* `/staff/*` เข้าถึงระบบการตลาดได้โดยตรง
* `/l/*` ประมวลผลย่อลิงก์ได้โดยตรง
* `/admin/*` เข้าถึงหน้าผู้ดูแลระบบได้โดยตรง

---

## 4. 🧪 ผลการทดสอบ (Verification Results)
จากการทดสอบเรียกใช้งาน Localhost Web Server (Apache XAMPP) ทุกระบบตอบสนองถูกต้องสมบูรณ์:

| ระบบ / Endpoint | ผลการทดสอบ | สถานะ |
| :--- | :--- | :--- |
| `http://localhost/yakpho.com/` | **HTTP 200 OK** | Frontend ตัวใหม่ทำงานปกติ |
| `http://localhost/yakpho.com/admin/` | **HTTP 200 OK** | ระบบ Admin หลังบ้านพร้อมใช้งาน |
| `http://localhost/yakpho.com/staff/` | **HTTP 200 OK** | ระบบการตลาด & AI ภายในทำงานปกติ |
| `http://localhost/yakpho.com/catalog/` | **HTTP 200 OK** | แคตตาล็อกเดิมเรียกดูได้ปกติ |
| `http://localhost/yakpho.com/l/?code=test` | **HTTP 200 OK** | ระบบย่อลิงก์แคมเปญทำงานปกติ |

---

## 5. 🚀 ขั้นตอนถัดไปสำหรับการ Deploy ขึ้นเซิร์ฟเวอร์จริง (VPS / Hosting)
เมื่อพร้อมนำขึ้นเซิร์ฟเวอร์ `yakpho.com` ผ่าน FTP:
1. **คัดลอกไฟล์ขึ้นเซิร์ฟเวอร์**: อัปโหลดไฟล์และโฟลเดอร์ทั้งหมด (ยกเว้นโฟลเดอร์ `scratch/` เพื่อประหยัดพื้นที่และความปลอดภัย) ไปยัง `/domains/yakpho.com/public_html/`
2. **ฐานข้อมูล (Database)**:
   * ส่วน E-commerce (Root & Admin) ใช้ Database: `salebnet_thaiherbold` (หรือชื่อ DB บนเซิร์ฟเวอร์จริง)
   * ส่วน Staff ใช้ Database: `woodysay_yakpho`
   * อัปเดตรหัสผ่าน DB ใน `includes/config.php` และ `staff/db.php` ให้ตรงกับบนโฮสติ้ง
3. **ตรวจสอบ SSL / HTTPS**: เปิดใช้งาน Let's Encrypt SSL บน DirectAdmin ให้กับโดเมน `yakpho.com`
