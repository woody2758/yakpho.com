# โครงสร้างระบบ Facebook Group Campaign & A/B Testing

เอกสารนี้สรุปโครงสร้างและแผนการพัฒนาระบบโพสต์ Facebook Group แบบใหม่ (Campaign-based) เพื่อใช้เป็น Reference ในการทำงาน

## 1. แนวคิดหลัก (Core Concept)
เปลี่ยนจากระบบ "สุ่มโพสต์ (Random)" เป็นระบบ **"แคมเปญ (Campaign)"** คล้าย Facebook Ads โดยมีคุณสมบัติหลักคือ:
*   **Targeted Audience:** เลือกระบุกลุ่ม Facebook เป้าหมายได้เอง
*   **A/B Testing:** สามารถใส่เนื้อหา (Content) ได้หลายรูปแบบ (ข้อความ + รูป/วิดีโอ) ในแคมเปญเดียว เพื่อทดสอบว่าแบบไหนได้ผลลัพธ์ดีที่สุด
*   **Link Tracking:** มีระบบนับสถิติการคลิกลิงก์ (Click Counter) แยกตามรูปแบบคอนเทนต์
*   **Drip-feed Posting:** ทยอยโพสต์ทีละกลุ่มโดยมีการหน่วงเวลา เพื่อความปลอดภัยของบัญชี Facebook

---

## 2. โครงสร้างฐานข้อมูล (Database Schema)
ต้องเพิ่มตารางใหม่เพื่อจัดการระบบแคมเปญและเก็บสถิติ:

1.  **`fb_campaigns`** (ข้อมูลหลักของแคมเปญ)
    *   `id`, `name`, `status` (active/paused/completed), `created_at`
2.  **`fb_campaign_targets`** (กลุ่มเป้าหมาย)
    *   `campaign_id`, `fb_group_id`
3.  **`fb_campaign_contents`** (เนื้อหาสำหรับ A/B Test)
    *   `id`, `campaign_id`, `message` (ข้อความ), `media_type` (image/video), `media_urls` (พาทไฟล์รูป/วิดีโอ)
4.  **`fb_campaign_schedules`** (คิวงานโพสต์)
    *   `queue_id`, `campaign_id`, `group_id`, `content_id`, `scheduled_time`, `status` (pending/success/failed)
5.  **`link_tracking`** (เก็บสถิติการคลิก)
    *   `short_code`, `campaign_id`, `content_id`, `destination_url`, `click_count`

---

## 3. สถาปัตยกรรมการทำงาน (System Architecture)

ระบบจะแบ่งหน้าที่การทำงานแยกกันอย่างชัดเจน เพื่อประสิทธิภาพและไม่ให้หน้าเว็บค้าง:

### 3.1 ระบบ Link Tracking (A/B Testing)
*   เมื่อผู้ใช้สร้างแคมเปญและใส่ URL ปลายทาง ระบบ PHP จะสร้าง "ลิงก์สั้น" (เช่น `yakpho.com/l/xxxxx`) ฝังเข้าไปในข้อความของแต่ละคอนเทนต์
*   เมื่อมีคนคลิกลิงก์สั้นจาก Facebook จะวิ่งมาที่ PHP Script -> ทำการ **+1 จำนวนคลิก** ใน Database -> แล้ว Redirect ไปเว็บเป้าหมายจริงๆ

### 3.2 Web UI (PHP หน้าบ้าน)
*   ไฟล์ `post_world_groups.php` ทำหน้าที่เป็น **หน้าจอตั้งค่า (Wizard)** เท่านั้น 
*   เมื่อตั้งค่าเสร็จ PHP จะทำการสร้างคิวงาน (Queue) ลงในตาราง `fb_campaign_schedules` และสร้างลิงก์สั้นให้เสร็จสรรพ

### 3.3 Backend Job (Windows Task Scheduler + Python)
*   **Windows Task Scheduler:** ตั้งเวลาให้ปลุกไฟล์ `/bot/fb_group_poster.py` ขึ้นมาทำงานทุกๆ 5 นาที (เสมือน Cronjob)
*   **Python Bot (`fb_group_poster.py`):** ทำหน้าที่เป็น **"Runner (คนทำงาน)"**
    1.  เรียก PHP API (`get_groups_to_post.php`) เพื่อถามว่า *"มีคิวไหนถึงเวลาโพสต์แล้วบ้าง?"* (เช็คจาก `scheduled_time`)
    2.  เรียก PHP API (`get_campaign_content.php`) เพื่อขอข้อความและรูปภาพของคิวนั้นๆ
    3.  ใช้ Playwright ทำการโพสต์ลง Facebook (ระบบมีการหน่วงเวลาแบบมนุษย์ 5-15 นาทีในตัวอยู่แล้ว เป็นการทำ Drip-feed ไปในตัว)
    4.  โพสต์เสร็จ แจ้งสถานะกลับไปยัง PHP API (`update_group_posted.php`)

---

## 4. แผนการพัฒนา (Implementation Phases)
หากต้องการเริ่มงาน จะแบ่งออกเป็น 4 เฟส ดังนี้:

*   **Phase 1: Database & Tracking** -> สร้างตาราง SQL ใหม่ และเขียนสคริปต์ทำ Short Link Redirect (`yakpho.com/l/...`)
*   **Phase 2: Backend API Updates** -> ปรับปรุง API ฝั่ง PHP ให้คุยกับ Database ตัวใหม่ เพื่อเตรียมส่งข้อมูลให้ Python
*   **Phase 3: Web UI Builder** -> พัฒนาหน้าจอ `post_world_groups.php` ให้รองรับการเลือกกลุ่ม, สร้างเนื้อหา A/B Test และตั้งเวลา
*   **Phase 4: Python Integration** -> ปรับแก้โค้ด `fb_group_poster.py` เล็กน้อย เพื่อให้รับข้อมูลชุดใหม่ไปทำงานได้ถูกต้อง
