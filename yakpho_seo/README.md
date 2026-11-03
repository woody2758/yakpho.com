# 🌿 YAK PHO & ESTHER AROMA — SEO MASTER PLAYBOOK
> **คู่มือและคลังสกิล SEO ฉบับเฉพาะทางสำหรับ E-Commerce สมุนไพรไทย & สปาอโรม่า (B2C & B2B Wholesale)**  
> รวบรวมและคัดสรรจากชุดทักษะ SEO Agentic Suite สำหรับโปรเจกต์ `yakpho.com` (บริษัท เอสเธอร์ อโรม่า จำกัด)

---

## 📌 สารบัญโครงสร้างโฟลเดอร์ `yakpho_seo`

```
yakpho_seo/
├── README.md                   <- คู่มือสรุปภาพรวมและการนำไปใช้งานจริง (ไฟล์นี้)
├── skills/                     <- สกิลคำสั่งและ Workflow แบบเจาะลึก
│   ├── seo-ecommerce/          <- การปรับแต่งหน้าสินค้า แคตตาล็อก และราคาขายส่ง 1kg
│   ├── seo-geo/                <- Generative Engine Optimization (ติดหน้าแรก ChatGPT, Gemini, AI Overviews)
│   ├── seo-hreflang/           <- การทำ SEO 10 ภาษา (th, en, zh, de, fr, ja, ko, ru, ar, he)
│   ├── seo-schema/             <- Structured Data JSON-LD (Product, Organization, LocalBusiness)
│   ├── seo-technical/          <- Core Web Vitals, Robots.txt, Canonical และความเร็ว
│   ├── seo-sitemap/            <- ระบบ XML Sitemap สำหรับหน้าเว็บหลายภาษา
│   ├── seo-content/            <- การเขียนบทความสมุนไพร สปา และสูตรบำบัด
│   ├── seo-content-brief/      <- เทมเพลตบรีฟงานคอนเทนต์เพื่อส่งต่อทีมเขียน
│   ├── seo-cluster/            <- กลยุทธ์ Topic Cluster สร้างความเชี่ยวชาญเฉพาะทาง (Topical Authority)
│   ├── seo-local/              <- SEO ท้องถิ่น ร้านค้า และโรงงานผลิต (คลองสามวา กทม.)
│   ├── seo-maps/               <- การปรับแต่งหมุด Google Maps & Google Business Profile
│   ├── seo-audit/              <- เช็คลิสต์ตรวจสอบสุขภาพ SEO ประจำเดือน
│   └── seo-images/             <- การปรับแต่งรูปภาพ WebP, ขนาด 50K px+, Alt text
├── agents/                     <- สเปกเอเจนต์และ Persona การทำงานของ AI แต่ละด้าน
└── data/
    └── google-updates.json     <- ประวัติการอัปเดต Google Core Updates ล่าสุด
```

---

## 🎯 7 เสาหลัก SEO ที่สำคัญที่สุดสำหรับ Yak Pho Aroma

### 1. E-Commerce & Wholesale Product SEO (`seo-ecommerce`)
* **โครงสร้าง Title Tag หน้าสินค้า:**  
  `[ชื่อสินค้า] สูตร[ร้อน/เย็น/นวดตัว] [ขนาด] กลิ่น[กลิ่นพรีเมียม] | Yak Pho Aroma`
* **Rich Snippets บนผลการค้นหา:**  
  ต้องมีราคา (Price Currency: THB), สถานะสินค้า (InStock), และรีวิว (AggregateRating) เพื่อให้แสดงดาวสีทองและราคาบน Google ทันที
* **เรทราคาส่ง B2B (Wholesale 1kg):**  
  ระบุคำสำคัญ "เรทราคาส่งร้านนวดสปา", "บาล์ม 1 กิโลกรัม", "โรงงานผลิตบาล์มสมุนไพร" ชัดเจนใน Meta Description

### 2. Generative Engine Optimization (`seo-geo`) — ติดอันดับใน AI Search
* **ทำไมถึงสำคัญ:** ปัจจุบันคนถาม ChatGPT, Gemini, Perplexity มากขึ้นเรื่อยๆ เช่น *"แนะนำบาล์มนวดสมุนไพรร้านสปาเกรดพรีเมียม"* หรือ *"น้ำมันนวดทาร์รากอนคืออะไร"*
* **Entity Clarity:** ระบุชื่อ Entity ชัดเจน:
  * Brand: **Yak Pho Aroma (ยักษ์โพธิ์)**
  * Parent Company: **Esther Aroma Co., Ltd. (บริษัท เอสเธอร์ อโรม่า จำกัด)**
  * Signature Herb: **Tarragon Infused Oil (สารสกัดทาร์รากอน)**
* **สูตร 130-170 คำ:** เนื้อหาคำตอบใน 30% แรกของบทความควรเป็นข้อความสรุปที่ชัดเจน มีตัวเลขและข้อเท็จจริง เพื่อให้ AI หยิบไปตอบ (Citations) ได้ง่าย

### 3. ระบบ SEO 10 ภาษาที่ถูกต้อง (`seo-hreflang`)
* เนื่องจาก Yak Pho รองรับ 10 ภาษา (`th`, `en`, `zh`, `de`, `fr`, `ja`, `ko`, `ru`, `ar`, `he`)
* **กฎสำคัญ:** ต้องมี Self-Referencing Tag (แท็กชี้หาตัวเอง) และ Return Tag (ลิงก์กลับไปกลับมาระหว่างทุกภาษา) แบบสมบูรณ์ พร้อม `x-default` ชี้ไปที่หน้าภาษาหลัก
* เว็บของเราได้ติดตั้งแท็กเหล่านี้ไว้ใน `<head>` ของ [`templates/header.php`](file:///d:/xampp/htdocs/yakpho.com/templates/header.php) เรียบร้อยแล้ว

### 4. สคีมาข้อมูลโครงสร้าง (`seo-schema`)
* **Product Schema:** ใส่ข้อมูลขนาด น้ำหนัก กลิ่น แบรนด์ และราคาขายส่ง
* **Organization Schema:** ยืนยันตัวตนบริษัท บริษัท เอสเธอร์ อโรม่า จำกัด พร้อมเบอร์โทรและช่องทางโซเชียล
* **LocalBusiness Schema:** ระบุที่อยู่สำนักงาน/สถานที่จัดส่ง เลขที่ 32/4 ถนนพระยาสุเรนทร์ แขวงสามวาตะวันตก เขตคลองสามวา กรุงเทพฯ 10510

### 5. กลยุทธ์เสาหลักเนื้อหา (`seo-cluster` & `seo-content`)
* **Pillar Page (หน้าหลัก):** คู่มือศาสตร์การนวดไทยและบาล์มสมุนไพร 3 สูตร
* **Cluster Articles (บทความสนับสนุนใน /blog):**
  1. ความลับของน้ำมันทาร์รากอน (Tarragon Infused Oil) ในการคลายกล้ามเนื้อ
  2. เปรียบเทียบบาล์มสูตรร้อน vs สูตรเย็น สำหรับร้านนวดมืออาชีพ
  3. เทคนิคการเลือกใช้น้ำมันนวดตัวอโรม่าและพิมเสนน้ำเพื่อสุนทรียภาพของลูกค้าสปา
  4. มาตรฐานการส่งออกบาล์มไทยสู่สปาต่างประเทศ (GMP/FDA)

### 6. Local & Google Maps SEO (`seo-local`, `seo-maps`)
* สร้างและยืนยัน **Google Business Profile (GBP)** ภายใต้ชื่อ *"บริษัท เอสเธอร์ อโรม่า จำกัด (Yak Pho Aroma)"*
* ปักหมุดที่ตั้งในเขตคลองสามวา พร้อมใส่หมวดหมู่ *"โรงงานผลิตผลิตภัณฑ์เพื่อสุขภาพ / ร้านขายผลิตภัณฑ์สปา"*
* ช่วยดึงลูกค้ากลุ่มเจ้าของร้านสปาที่ค้นหา "โรงงานบาล์มนวดใกล้ฉัน" หรือ "ซื้อบาล์มสปาราคาส่ง กรุงเทพ"

### 7. Technical Health & Web Vitals (`seo-technical`)
* **Core Web Vitals:** LCP < 2.5s, CLS < 0.1, FID/INP < 200ms
* **ภาพทั้งหมดต้องเป็น WebP:** กำหนดขนาดรูป `width` และ `height` ชัดเจนเพื่อป้องกัน Layout Shift
* **Robots.txt & Sitemap:** กำหนดสิทธิ์ให้บอท Googlebot, Bingbot, และ AI Bots (GPTBot, PerplexityBot) เข้าอ่านเนื้อหาได้อย่างราบรื่น

---

## 🛠️ เช็คลิสต์สิ่งที่ Yak Pho ได้ทำไปแล้ว vs ขั้นตอนถัดไป

| รายการงาน SEO | สถานะปัจจุบัน | ไฟล์ที่เกี่ยวข้อง |
| :--- | :---: | :--- |
| ติดตั้ง GA4 Tag (`G-H8X1HT3GLQ`) | ✅ เรียบร้อย | `templates/header.php` |
| ติดตั้ง E-Commerce Events (`view_item`, `add_to_cart`, `purchase`) | ✅ เรียบร้อย | `product-detail.php`, `yakpho.js`, `checkout.php` |
| แท็ก Canonical & Hreflang 10 ภาษา | ✅ เรียบร้อย | `templates/header.php` |
| JSON-LD Product & Organization Schema | ✅ เรียบร้อย | `product-detail.php`, `header.php` |
| รูปภาพ WebP และ Favicon ครบทุกขนาด | ✅ เรียบร้อย | `assets/img/favicons/` |
| หน้าบทความสปา & Seed ข้อมูลใหม่ | ✅ เรียบร้อย | `templates/blog.php`, ฐานข้อมูล `blog` |
| หน้า FAQ Accordion ดึงจาก DB | ✅ เรียบร้อย | `templates/faqs.php`, ฐานข้อมูล `faqs` |
| **สิ่งที่ควรทำถัดไป:** ปักหมุด Google Business Profile & Submit Sitemap | ⏳ แนะนำทำหลังขึ้น Server จริง | Google Search Console & Google Maps |

