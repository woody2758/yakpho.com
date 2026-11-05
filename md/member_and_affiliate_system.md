# พิมพ์เขียวระบบสมาชิก, หมอนวด Affiliate, LINE & Resend Email System
**Yak Pho Aroma & Esther Synergy Co., Ltd.**
*เอกสารข้อกำหนดเชิงเทคนิคและแผนผังระบบ (System Architecture & Feature Blueprint)*

---

## 1. ภาพรวมระบบ (System Overview)

ระบบสมาชิกของ Yak Pho Aroma ถูกออกแบบภายใต้แนวคิด **"Seamless Mobile-First E-Commerce"** เน้นความง่ายในการใช้งานสูงสุด ลดอุปสรรคในการสมัครสมาชิก และเพิ่มพลังยอดขายด้วยเครือข่ายหมอนวดไทย (Therapist Affiliate Network)

---

## 2. ระบบการเข้าสู่ระบบ & การยืนยันตัวตน (Authentication & Onboarding)

### 2.1 LINE Login (One-Click Social Login)
* **เป้าหมาย:** ลูกค้าคนไทย 99% ใช้งาน LINE อยู่แล้ว จึงใช้ LINE Login เป็นช่องทางหลัก
* **Flow การทำงาน:**
  1. ลูกค้ากดปุ่ม `"เข้าสู่ระบบด้วย LINE"` บนมือถือหรือเดสก์ท็อป
  2. ระบบเรียกใช้ LINE Login Channel ดึงข้อมูล:
     * `line_user_id` (สำหรับใช้ส่งข้อความแจ้งเตือนและบรอดแคสต์)
     * `display_name` (ชื่อไลน์)
     * `picture_url` (รูปโปรไฟล์)
  3. ระบบนำ `line_user_id` มาตรวจสอบกับตาราง `user`:
     * **ถ้าเคยลงทะเบียนแล้ว:** เข้าสู่ระบบและแสดงโปรไฟล์ทันที
     * **ถ้าเป็นสมาชิกใหม่:** สร้างบัญชีอัตโนมัติพร้อมมอบแต้มต้อนรับ (Welcome M-Stamp 20 แต้ม)

### 2.2 การเชื่อมต่ออีเมล (Email Binding via Resend)
* **เป้าหมาย:** สื่อสารเรื่องสำคัญอย่างเป็นทางการ เช่น ใบเสร็จรับเงิน, แจ้งเลขพัสดุ, เอกสารภาษี และรายงานค่าคอมมิชชัน
* **ขั้นตอน:**
  1. เมื่อสมาชิกเข้าสู่ระบบ จะมีการ์ดแนะนำ: *"เชื่อมต่ออีเมลเพื่อรับใบเสร็จและรับ 20 M-Stamp"*
  2. สมาชิกกรอกอีเมล ➔ ระบบส่งอีเมลยืนยันตัวตนผ่าน **Resend API** พร้อมปุ่มกดยืนยัน (Magic Link)
  3. เมื่อคลิกยืนยัน บัญชีจะถูกบันทึกสถานะ `email_verified = 1` ทันที

---

## 3. ระดับสมาชิกและสิทธิประโยชน์ (Membership Tiers & Symbols)

| ระดับสมาชิก | สัญลักษณ์ Badge | กลุ่มเป้าหมาย | สิทธิประโยชน์หลัก |
| :--- | :---: | :--- | :--- |
| **1. General Member** | 🌿 **Green Herbal Leaf** | ลูกค้าปลีก / ซื้อใช้เองในบ้าน | • สั่งซื้อสินค้าราคาปลีกทั่วไป<br>• เช็คสถานะพัสดุ Flash/Kerry แบบ Realtime<br>• สะสมแต้ม M-Stamp ทุกคำสั่งซื้อ<br>• บันทึกสมุดที่อยู่จัดส่งได้หลายที่อยู่ |
| **2. VIP Spa Member** | 👑 **Emerald Gold Crown** | ร้านนวดแผนไทย & สปาทั่วประเทศ | • **ปลดล็อกเรทราคาส่งโรงงาน 1 กก.** (6 กก. ฿590 / 50 กก. ฿490)<br>• สะสมแต้ม M-Stamp ทุกยอดสั่งซื้อ<br>• สิทธิ์สั่งคละสูตรและกลิ่นได้ครบทั้ง 12 กลิ่น 3 สูตร<br>• สิทธิ์ส่งฟรีทั่วไทยเมื่อสั่งครบยอด |
| **3. Yak Pho Partner** | ⭐ **Golden Yak Star** | หมอนวด / สปาเธอราพิสต์ / ตัวแทน Affiliate | • **ได้รับลิงก์แนะนำ (Affiliate Link) & QR Code ประจำตัว**<br>• รับค่าคอมมิชชัน 10% - 15% จากทุกยอดคำสั่งซื้อของลูกค้า<br>• แดชบอร์ดติดตามยอดคลิก ยอดซื้อ และถอนเงินค่าคอมมิชชัน<br>• ไม่ต้องสต็อกสินค้า ไม่ต้องแพ็คของ โรงงานจัดส่งให้โดยตรง |
| **4. OEM & Export** | 💎 **Platinum Diamond** | เจ้าของแบรนด์ / บริษัทส่งออกต่างประเทศ | • สิทธิ์สั่งผลิต OEM ปรับแต่งสูตรและบรรจุภัณฑ์ (ขั้นต่ำ 100 กก.)<br>• เอกสารรองรับการส่งออกครบวงจร (GMP, COA, MSDS, ใบรับรองแหล่งผลิต)<br>• ปรึกษาทีมเภสัชกร R&D และบริการจดแจ้ง อย. |

---

## 4. ระบบตัวแทนหมอนวด (Therapist Affiliate Model)

* หมอนวดมี **ลิงก์และ QR Code ประจำตัว**
* ลูกค้าที่มานวดสแกน QR สั่งซื้อยาหม่องผ่านเว็บยักษ์โพธิ์
* ระบบบันทึก Cookie แนะนำ 30 วัน
* โรงงานจัดส่งพัสดุด่วนถึงบ้านลูกค้าโดยตรง
* แจ้งเตือนค่าคอมมิชชันเข้า LINE ของหมอนวดทันทีในทุกออเดอร์

---

## 5. เทมเพลตอีเมล Resend ในระบบ Admin (Admin Resend Email Templates)

ในระบบหลังบ้าน (Admin Panel) จะมีเมนู **"จัดการเทมเพลตอีเมล (Email Templates)"** ซึ่งแอดมินสามารถดูตัวอย่าง (Preview), แก้ไขหัวข้อ (Subject), และปรับข้อความในเทมเพลตได้ พร้อมระบบ Merge Tags:

### รายการ 6 เทมเพลตมาตรฐาน:

1. **`order_confirmation` (ยืนยันคำสั่งซื้อ & ใบเสร็จรับเงิน):**
   * *ส่งเมื่อ:* ลูกค้าชำระเงินสำเร็จ
   * *ข้อมูล:* รายการสินค้า, ยอดรวมสุทธิ, วิธีการชำระเงิน, ที่อยู่จัดส่ง, แต้ม M-Stamp ที่ได้รับ
2. **`shipping_tracking` (แจ้งสถานะจัดส่ง & เลขพัสดุ):**
   * *ส่งเมื่อ:* แอดมินสแกนยิงบาร์โค้ดพัสดุ Flash Express / Kerry / ไปรษณีย์ไทย
   * *ข้อมูล:* ขนส่ง, เลขพัสดุ, ปุ่มคลิกเช็คสถานะพัสดุได้ทันทีในคลิกเดียว
3. **`email_verification_welcome` (ยืนยันอีเมล & แต้มต้อนรับสมาชิกใหม่):**
   * *ส่งเมื่อ:* ลูกค้าเชื่อมต่ออีเมลในหน้าโปรไฟล์
   * *ข้อมูล:* ลิงก์ Magic Link กดยืนยันตัวตน + มอบ 20 แต้ม M-Stamp ต้อนรับ
4. **`m_stamp_summary` (สรุปยอดแต้ม M-Stamp & สิทธิพิเศษ):**
   * *ส่งเมื่อ:* มีแต้มเข้า/ออก หรือสรุปรายเดือน
   * *ข้อมูล:* ยอดแต้มสะสมปัจจุบัน, มูลค่าส่วนลดที่ใช้ได้ (1 แต้ม = 1 บาท), สินค้าแนะนำประจำเดือน
5. **`affiliate_commission_monthly` (สรุปยอดค่าคอมมิชชันหมอนวด Partner):**
   * *ส่งเมื่อ:* สิ้นเดือน หรือเมื่อมีการโอนเงินค่าคอมมิชชัน
   * *ข้อมูล:* จำนวนออเดอร์ที่แนะนำได้, ยอดขายรวม, จำนวนเงินค่าคอมมิชชันที่โอนเข้าบัญชี
6. **`reorder_spa_reminder` (แจ้งเตือนสั่งซื้อซ้ำสำหรับร้านนวดสปา):**
   * *ส่งเมื่อ:* ผ่านไป 30 - 45 วันหลังจากร้านสปาสั่งบาล์ม 1 กก. รอบก่อนหน้า
   * *ข้อมูล:* ปริมาณบาล์มใกล้หมดหรือไม่, ลิงก์กดสั่งซื้อซ้ำใน 1 คลิก พร้อมเรทส่งโรงงาน

---

## 6. ระบบ LINE: การบรอดแคสต์ & การส่งข้อความแจ้งเตือน

### 6.1 ใครเชื่อมต่อ LINE แล้ว เราทำ Broadcast เองได้หรือไม่?
> **คำตอบ: ได้แน่นอนครับ 100%!**

เมื่อผู้ใช้เชื่อมต่อ LINE Login หรือเพิ่มเพื่อน LINE OA `@yakpho` เราจะได้รับรหัส **`line_user_id`** (เช่น `Ua1b2c3d4...`) เก็บไว้ในฐานข้อมูล การส่งข้อความแบ่งเป็น 3 รูปแบบ:

1. **Targeted Broadcast เฉพาะกลุ่ม (แนะนำที่สุดผ่านระบบหลังบ้านเรา):**
   * **ยิงหาเฉพาะกลุ่มหมอนวด Affiliate:** เช่น แจ้งโปรโมชั่นเพิ่มค่าคอมมิชชันพิเศษ 20% ประจำสัปดาห์
   * **ยิงหาเฉพาะร้านสปา VIP:** เช่น แจ้งเตือนโปรโมชั่นเรทส่งบาล์มล็อตใหม่
   * *ข้อดี:* ยิงตรงกลุ่ม ไม่กวนลูกค้าทั่วไป และประหยัดโควต้าข้อความของ LINE มหาศาล
2. **Push Message แจ้งเตือนส่วนตัว (Personalized 1-on-1):**
   * แจ้งเตือนทันทีเมื่อของส่งออก: *"พัสดุของคุณจัดส่งแล้ว เลขพัสดุ TH12345..."*
   * แจ้งเตือนหมอนวดเมื่อมีลูกค้าสั่งผ่านลิงก์: *"ยินดีด้วย! คุณได้รับค่าคอมมิชชัน 150 บาทจากออเดอร์ใหม่"*
3. **Mass Broadcast (ยิงทุกคนพร้อมกัน):**
   * สามารถทำได้ทั้งจากระบบหลังบ้านเราผ่าน API หรือทำผ่านหน้าเว็บ LINE Official Account Manager (`manager.line.biz`)

---

## 7. แผนการจัดเตรียมฐานข้อมูล (Database Schema Plan)

```sql
-- 1. ตารางเก็บการเชื่อมต่อ LINE Account
ALTER TABLE user ADD COLUMN line_user_id VARCHAR(64) NULL AFTER fb_id;
ALTER TABLE user ADD COLUMN line_display_name VARCHAR(150) NULL AFTER line_user_id;
ALTER TABLE user ADD COLUMN line_picture_url VARCHAR(255) NULL AFTER line_display_name;
ALTER TABLE user ADD COLUMN member_tier ENUM('general', 'vip_spa', 'therapist_partner', 'oem_export') DEFAULT 'general' AFTER user_level;
ALTER TABLE user ADD COLUMN m_stamp_points INT DEFAULT 0 AFTER user_stars;
ALTER TABLE user ADD COLUMN affiliate_code VARCHAR(30) UNIQUE NULL AFTER user_key;
ALTER TABLE user ADD COLUMN email_verified TINYINT(1) DEFAULT 0 AFTER user_email;

-- 2. ตารางบันทึกแต้มสะสม M-Stamp Ledger
CREATE TABLE IF NOT EXISTS member_points_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    points_change INT NOT NULL,
    balance_after INT NOT NULL,
    action_type ENUM('order_reward', 'order_discount', 'welcome_bonus', 'admin_adjustment', 'review_reward') NOT NULL,
    reference_id INT NULL,
    note VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id)
);

-- 3. ตารางเทมเพลตอีเมล Resend
CREATE TABLE IF NOT EXISTS resend_email_templates (
    template_id INT AUTO_INCREMENT PRIMARY KEY,
    template_code VARCHAR(50) UNIQUE NOT NULL,
    template_name VARCHAR(150) NOT NULL,
    email_subject VARCHAR(255) NOT NULL,
    email_body_html LONGTEXT NOT NULL,
    available_merge_tags TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 4. ตารางบันทึกการส่งข้อความ LINE Logs & Broadcasts
CREATE TABLE IF NOT EXISTS line_broadcast_logs (
    broadcast_id INT AUTO_INCREMENT PRIMARY KEY,
    target_tier VARCHAR(50) NOT NULL,
    message_text TEXT NOT NULL,
    recipient_count INT DEFAULT 0,
    sent_by_user_id INT NOT NULL,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

---

## 8. ขั้นตอนการลงมือปฏิบัติ (Implementation Roadmap)

1. **Phase 1: สร้างฐานข้อมูลและโครงสร้างหลังบ้าน (Database & Architecture)**
2. **Phase 2: ระบบ Resend Email Templates ใน Admin Panel (พร้อมตัวอย่าง Preview)**
3. **Phase 3: LINE Login Integration & Onboarding (เชื่อมโยงเบอร์เดิม 5,609 ราย)**
4. **Phase 4: ระบบหมอนวด Affiliate (Yak Pho Partner Dashboard พร้อม QR ประจำตัว)**
5. **Phase 5: ระบบแต้ม M-Stamp และการตัดส่วนลดที่หน้า Checkout**

