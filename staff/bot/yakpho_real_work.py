import json
import re
import os
import random
import requests
import urllib.parse
from datetime import datetime
from playwright.sync_api import sync_playwright

API_URL = "https://yakpho.com/staff/api/save_lead.php"
API_URL_CITIES = "https://yakpho.com/staff/api/target_cities.php"
API_URL = "https://yakpho.com/staff/api/save_lead.php"
API_URL_CITIES = "https://yakpho.com/staff/api/target_cities.php"
API_URL_UPDATE = "https://yakpho.com/staff/api/update_status.php"
API_URL_BOT_MANAGER = "https://yakpho.com/staff/api/bot_manager.php"
API_KEY = "yakpho2026"
TEMP_FILE = "temp_leads.json"

# Global Config
BOT_CONFIG = {
    "status": "on",
    "telegram_token": "",
    "telegram_chat_id": ""
}

def report_online_status():
    """ รายงานตัวเป้าหมายว่ารันอยู่บน Local (คุณจอน) """
    try:
        url = f"{API_URL_BOT_MANAGER}?action=report_status&bot=yakpho_real_work&status=running&pid={os.getpid()}&api_key={API_KEY}"
        requests.get(url, timeout=5)
    except: pass

def fetch_bot_config():
    """ ดึงสถานะและคอนฟิกจาก Admin """
    try:
        # รายงานตัวก่อน
        report_online_status()
        
        headers = {
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
        }
        # เช็คสถานะปัจจุบันของบอท yakpho_real_work (คุณจอน)
        response = requests.get(f"{API_URL_BOT_MANAGER}?action=status&bot=yakpho_real_work&api_key={API_KEY}", headers=headers, timeout=10)
        if response.status_code == 200:
            data = response.json()
            # ถ้ามีคำสั่งรอให้ stop ให้ยุติการทำงาน (การเช็ค status == idle ห้ามทำตรงนี้เพราะตอนเริ่มระบบ status จะเป็น idle อยู่ 1-2 วินาที)
            if data.get("pending_command") == "stop":
                print("🛑 [ADMIN CONTROL] ตรวจพบคำสั่ง 'สั่งให้พักผ่อน' (OFF) - กำลังยุติการทำงาน...")
                # แจ้งกลับว่าหยุดแล้ว
                try: requests.get(f"{API_URL_BOT_MANAGER}?action=report_status&bot=yakpho_real_work&status=idle&pid=0&api_key={API_KEY}", timeout=5)
                except: pass
                return False
                
        return True
    except Exception as e:
        print(f"⚠️ ไม่สามารถเชื่อมต่อ Admin Control ได้ ({e}) - บังคับทำงานต่อไป...")
        return True

def update_pulse(action, progress=None):
    """ ส่งสถานะการทำงานปัจจุบันกลับไปยัง Admin Dashboard """
    pass # ย้ายไปใช้ระบบ bot_manager แทน

def send_telegram_alert(message):
    """ ส่งการแจ้งเตือนเข้า Telegram """
    token = BOT_CONFIG.get("telegram_token")
    chat_id = BOT_CONFIG.get("telegram_chat_id")
    
    if not token or not chat_id:
        return # ข้ามถ้าไม่ได้ตั้งค่า
        
    try:
        url = f"https://api.telegram.org/bot{token}/sendMessage"
        payload = {
            "chat_id": chat_id,
            "text": message,
            "parse_mode": "HTML"
        }
        requests.post(url, json=payload, timeout=10)
    except Exception as e:
        print(f"⚠️ ไม่สามารถส่งหา Telegram ได้: {e}")

def find_email(page, url):
    """
    ฟังก์ชันสำหรับเข้าไปที่หน้าเว็บเป้าหมาย และหาอีเมล
    1. ลองหาในหน้าแรกก่อน
    2. ถ้าไม่เจอ ลองหาลิงก์คำว่า Contact / About แล้วคลิกเข้าไปหาอีกรอบ
    """
    try:
        # เปิดหน้าเว็บด้วย Timeout 30 วินาที
        print(f"Debug: กำลังค้นหาอีเมลในเว็บไซต์ -> {url}")
        page.goto(url, timeout=30000)
        page.wait_for_load_state('domcontentloaded', timeout=30000)
        
        # Regex สำหรับหาอีเมลพื้นฐาน (Basic Email Regex)
        email_pattern = r'[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+'
        
        # กรองคำผิดปกติที่เป็นรหัสของเว็บ ไม่ใช่อีเมลคน
        def is_valid_email(e):
            e = e.lower()
            if e.endswith(('.png', '.jpg', '.jpeg', '.gif', '.webp', '.svg')):
                return False
            if any(word in e for word in ["intl-segmenter", "sentry", "wix", "example", "gsap@", "core-js@"]):
                return False
            # กรองพวกที่ลงท้ายด้วยตัวเลข (มักจะเป็นเวอร์ชันของ JS libraries เช่น @11.7.10)
            if re.search(r'@[0-9]+\.', e):
                return False
            # กรองอีเมลที่เป็นตัวเลขล้วนหน้า @ ซึ่งมักจะเป็น ID ของระบบ
            if re.match(r'^[0-9]+@', e):
                return False
            return True

        # 1. พยายามหาในส่วน Footer ก่อน (เป้าหมายหลัก)
        footer_locators = page.locator("footer, .footer, #footer, [class*='footer']").all()
        for footer in footer_locators:
            try:
                footer_content = footer.inner_html()
                emails = re.findall(email_pattern, footer_content)
                valid_emails = [e for e in emails if is_valid_email(e)]
                if valid_emails:
                    print("Debug: (เจอใน Footer)")
                    return valid_emails[0].lower()
            except:
                pass
                
        # 2. ค้นหาในหน้าแรกทั้งหมด (กรณีไม่มีใน footer แบบชัดเจน)
        content = page.content()
        emails = re.findall(email_pattern, content)
        valid_emails = [e for e in emails if is_valid_email(e)]
        
        if valid_emails:
            return valid_emails[0].lower()
            
        # 3. ถ้าไม่เจอในหน้าแรก ให้พยายามหาลิงก์ที่เขียนว่า Contact
        contact_links = page.locator("a", has_text=re.compile(r"contact|about", re.IGNORECASE)).all()
        if contact_links:
            # เลือกลิงก์ที่มีคำว่า contact ก่อน
            target = None
            for link in contact_links:
                href = link.get_attribute("href")
                if href and "contact" in href.lower():
                    target = link
                    break
            
            # ถ้าไม่มีลิงก์ contact ชัดเจน เอาลิงก์แรกที่ได้ (อาจเป็น about)
            if not target:
                target = contact_links[0]
                
            try:
                target.click(timeout=5000)
                page.wait_for_load_state('domcontentloaded', timeout=30000)
                page.wait_for_timeout(2000) # รอให้เว็บเรนเดอร์เนื้อหา
                
                content = page.content()
                emails = re.findall(email_pattern, content)
                valid_emails = [e for e in emails if is_valid_email(e)]
                
                if valid_emails:
                    return valid_emails[0].lower()
            except:
                pass # ถ้าคลิกไม่ได้ หรือโหลดนานไป ก็ข้าม
                
    except Exception as e:
        print(f"Debug: เกิดข้อผิดพลาดตอนเข้าเว็บ {url} ({e})")
        
    return ""

def save_lead_to_api(lead_data):
    """ ส่งข้อมูล JSON ไปยัง MySQL API """
    try:
        # ใส่ User-Agent จำลองเป็นเบราว์เซอร์จริง เพื่อหลบเลี่ยง Firewall (OpenResty) บล็อกบอท (403 Forbidden)
        headers = {
            "Content-Type": "application/json",
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
        }
        response = requests.post(
            API_URL, 
            json=lead_data, 
            headers=headers,
            timeout=30
        )
        if response.status_code == 200:
            print(f"✅ บันทึก '{lead_data['shop_name']}' ลง MySQL สำเร็จแล้ว")
            return True
        else:
            print(f"⚠️ Error จาก Server: {response.text}")
            return False
    except requests.exceptions.RequestException as e:
        print(f"❌ เชื่อมต่อ Server ไม่สำเร็จ: {e}")
        return False

def save_to_temp(lead_data):
    """ บันทึกข้อมูลที่ส่งไม่สำเร็จลงไฟล์ temp_leads.json ในเครื่อง (เขียนต่อท้ายแบบ JSONL เพื่อประหยัด Memory) """
    try:
        # เปิดไฟล์เขียนต่อท้ายทันที (O(1) Time Complexity)
        with open(TEMP_FILE, 'a', encoding='utf-8') as f:
            f.write(json.dumps(lead_data, ensure_ascii=False) + "\\n")
        print(f"💾 สำรองข้อมูล '{lead_data['shop_name']}' ลงไฟล์ {TEMP_FILE} ไว้รอส่งใหม่ (Server ล่ม)")
    except Exception as e:
        print(f"❌ ไม่สามารถสำรองข้อมูลลงไฟล์ temp ได้ ({e})")

def get_target_cities():
    """ ดึงรายชื่อเมืองจาก API """
    try:
        headers = {
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
        }
        response = requests.get(f"{API_URL_CITIES}?api_key={API_KEY}", headers=headers, timeout=15)
        if response.status_code == 200:
            data = response.json()
            return data.get("data", [])
        else:
            print(f"⚠️ ไม่สามารถดึงรายชื่อเมืองได้ (Status {response.status_code})")
            return []
    except Exception as e:
        print(f"❌ โหลดข้อมูลเมืองจาก API ไม่สำเร็จ: {e}")
        return []

def mark_city_completed(city_id):
    """ ส่งข้อมูลไปอัปเดตสถานะเมืองว่าดึงข้อมูลเสร็จแล้ว (completed) """
    try:
        payload = {
            "api_key": API_KEY,
            "city_id": city_id,
            "status": "completed"
        }
        headers = {
            "Content-Type": "application/json",
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
        }
        response = requests.post(API_URL_UPDATE, json=payload, headers=headers, timeout=30)
        if response.status_code == 200:
            print(f"✅ ทำเครื่องหมายเมือง ID {city_id} สถานะ: completed เรียบร้อยแล้ว")
        else:
            print(f"⚠️ แจ้งสถานะเมืองไม่สำเร็จ: {response.text}")
    except Exception as e:
        print(f"❌ เชื่อมต่อ API อัปเดตสถานะไม่สำเร็จ: {e}")

def run_work():
    # 1. อ่านไฟล์กุญแจ google_key.json เพื่อซ่อมแซม \escape กัน Error (แม้จะไม่ได้ใช้แล้วก็ตาม)
    print("Debug: กำลังอ่านไฟล์ google_key.json เพื่อตรวจสอบความถูกต้อง...")
    try:
        with open('google_key.json', 'r', encoding='utf-8') as f:
            raw_content = f.read()
            fixed_content = raw_content.replace('\\', '\\\\')
            key_data = json.loads(fixed_content)
            
            if 'private_key' in key_data:
                pk = key_data['private_key'].replace('\\n', '\n')
                key_data['private_key'] = pk.replace('\\', '')
                
        print("Debug: อ่านไฟล์ google_key.json สำเร็จ (ระบบพร้อมทำงาน)!")
    except Exception as e:
        print(f"Error: ไม่สามารถอ่านหรือแก้ไขไฟล์ google_key.json ได้ ({e})")
        return

    # 3. เริ่มต้น Playwright 
    print("Debug: กำลังเริ่มต้น Playwright เพื่อดึงข้อมูลเชิงลึกจาก Google Maps...")
    try:
        with sync_playwright() as p:
            # ป้องกันบอท
            browser = p.chromium.launch(headless=True, args=["--disable-blink-features=AutomationControlled"])
            context = browser.new_context(
                user_agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
                viewport={"width": 1920, "height": 1080},
                locale="en-GB"
            )
            
            page = context.new_page()
            email_page = context.new_page()
            
            total_saved = 0
            
            while True:
                # 2. อ่านรายชื่อเมืองเป้าหมายจาก API (ดึงทีละ 10 ชุด)
                target_cities = get_target_cities()
                if not target_cities:
                    print("\n🎉 ยินดีด้วย! ไม่พบรายชื่อเมืองที่ต้องทำแล้ว (ทำครบหมดแล้วทุกเมืองในระบบ!)")
                    break
                
                print(f"Debug: โหลดเป้าหมายจาก API สำเร็จ {len(target_cities)} เมือง")
                
                # วนลูปตามรายชื่อเมือง 10 อันดับแรก
                for target in target_cities:
                    city_id = target.get('id')
                city = str(target.get('city') or '').strip()
                region = str(target.get('region') or '').strip()
                country = str(target.get('country') or '').strip()
                
                if region == '-': region = ''
                
                if not city:
                    continue
                    
                # สร้าง Query โดยใช้ City + Region + Country เพื่อความแม่นยำสูงสุด (โดยเฉพาะตลาด UK)
                location_parts = [p for p in [city, region, country] if p]
                location_str = ', '.join(location_parts)
                query = f"Thai massage" 
                print(f"\n=========================================")
                print(f"📍 เริ่มค้นหาเป้าหมาย: '{query}' in {location_str}")
                print(f"=========================================")
                
                # ถ้าโดนเบรกจาก fetch_bot_config ด้านล่าง ให้เตรียมเบรกลูปใหญ่ด้วย
                should_break_all = False
                
                # ตรวจสอบสถานะ On/Off จาก Admin ทุกครั้งที่เริ่มเมืองใหม่ พร้อมดึง Config ล่าสุด
                if not fetch_bot_config():
                    should_break_all = True
                    break
                
                update_pulse(f"📍 Starting City: {city}", progress=0)
                
                # ค้นหาบน Maps ด้วย City+Region+Country
                safe_query = urllib.parse.quote_plus(f"{query} {location_str}")
                search_url = f"https://www.google.com/maps/search/{safe_query}"
                page.goto(search_url)
                
                # กดข้ามหน้าต่าง Consent ของคุกกี้ (พบบ่อยในยุโรป)
                try:
                    button = page.locator('button:has-text("Reject all"), button:has-text("Accept all")').first
                    if button.is_visible(timeout=3000):
                        print("Debug: พบหน้าต่าง Consent (คุกกี้) กำลังกดข้าม...")
                        button.click()
                except:
                    pass
                
                print("Debug: รอให้รายชื่อโหลดขึ้นมา...")
                page.wait_for_timeout(5000)
                
                # ค้นหาลิงก์ร้านค้าทั้งหมดที่แสดงในแถบด้านซ้ายด้วย JS Snapshot ทันที
                # ป้องกัน Error เวลา React ของ Google Maps รีทัช DOM แล้วทำให้ Locator หาย
                shop_data = page.evaluate('''() => {
                    return Array.from(document.querySelectorAll('a[href*="/maps/place/"]'))
                        .map(a => ({
                            name: a.getAttribute('aria-label') || '',
                            url: a.href || ''
                        }))
                        .filter(x => x.name && x.url);
                }''')
                
                total_found = len(shop_data)
                print(f"Debug: เจอร้านในพื้นที่ '{location_str}' จำนวน {total_found} ร้าน")
                
                # ===== FALLBACK: ถ้าไม่พบผล ให้ลองค้นหาแค่ City, Country =====
                if total_found == 0 and region:
                    print(f"⚠️  ไม่พบผลจาก '{location_str}' — ลอง Fallback ด้วยแค่ '{city}, {country}'...")
                    safe_fallback = urllib.parse.quote_plus(f"{query} {city} {country}")
                    fallback_url = f"https://www.google.com/maps/search/{safe_fallback}"
                    page.goto(fallback_url)
                    page.wait_for_timeout(5000)
                    
                    shop_data = page.evaluate('''() => {
                        return Array.from(document.querySelectorAll('a[href*="/maps/place/"]'))
                            .map(a => ({
                                name: a.getAttribute('aria-label') || '',
                                url: a.href || ''
                            }))
                            .filter(x => x.name && x.url);
                    }''')
                    total_found = len(shop_data)
                    print(f"Debug: Fallback เจอร้านจำนวน {total_found} ร้าน")
                
                if total_found == 0:
                    print(f"⏩ ข้ามเมือง {city} เนื่องจากไม่พบร้านค้าเลย")
                    if city_id:
                        mark_city_completed(city_id)
                    continue

                saved_count = 0
                for shop in shop_data:
                    if saved_count >= 10: # ดึงแค่ 10 ร้านต่อเมือง
                        break
                        
                    final_name = shop['name'].strip()
                    maps_url = shop['url']
                    
                    print(f"\n--- ร้านที่ {saved_count+1} ใน {city} ---")
                    print(f"👉 ชื่อ: {final_name}")
                    print("Debug: กำลังคลิกดูรายละเอียดของร้านนี้เพื่อหาลิงก์เว็บไซต์...")
                    
                    # เลือกร้านโดยการเข้าไปที่ URL ของร้านตรงๆ (ชัวร์และแม่นยำกว่าการพยายามควานหาเพื่อคลิก)
                    try:
                        page.goto(maps_url, timeout=45000)
                    except:
                        pass
                    page.wait_for_timeout(3500) # รอให้ Sidebar สไลด์ข้อมูลร้านขึ้นมา
                    
                    # ------ ดึงรายละเอียดของร้านผ่าน JS Snapshot ทันที (รองรับทุกภาษาบนโลก) ------
                    shop_details = page.evaluate('''() => {
                        let data = { website: "", reviews: "0", highlights: "" };
                        
                        try {
                            const webBtn = document.querySelector('a[data-item-id="authority"]');
                            if(webBtn) data.website = webBtn.href;
                            
                            // หารีวิว: หาสแปนที่มีวงเล็บตัวเลข (แบบฉบับ Google Maps ทั่วโลก) เช่น "(128)" หรือ "(1,234)"
                            const reviewSpan = Array.from(document.querySelectorAll('span')).find(el => el.innerText && el.innerText.trim().match(/^\\(([\d,]+)\\)$/));
                            if (reviewSpan) {
                                let match = reviewSpan.innerText.match(/([\d,]+)/);
                                if(match) data.reviews = match[1].replace(/,/g, '');
                            } else {
                                // Fallback หาก Google อัปเดต UI หาปุ่มรีวิวตรงๆ (ลบวงเล็บ/เครื่องหมายการเขียนออก)
                                const btn = document.querySelector('button[aria-label*="reviews"], button[aria-label*="รีวิว"], button[aria-label*="レビュー"]');
                                if(btn) {
                                    let match = (btn.getAttribute("aria-label") || btn.innerText).match(/([\d,]+)/);
                                    if(match) data.reviews = match[1].replace(/,/g, '');
                                }
                            }
                            
                            // หา Highlights: คำสำคัญเด่นๆ จากรีวิว
                            let kwNodes = Array.from(document.querySelectorAll('button[aria-label*="Mentioned"], button[aria-label*="อ้างถึง"], .MyEned'));
                            let kws = kwNodes.map(el => el.innerText.trim().split('\\n')[0]).filter(t => t.length > 2 && t.length < 25);
                            if(kws.length > 0) {
                                // กรองคำซ้ำและเอาแค่ 3 คำแรก
                                data.highlights = [...new Set(kws)].slice(0, 3).join(", ");
                            }
                        } catch(e) {}
                        
                        return data;
                    }''')
                    
                    website = shop_details['website']
                    review_count = shop_details['reviews']
                    review_highlights = shop_details['highlights']
                    
                    print(f"🌐 เว็บไซต์: {website if website else 'N/A'}")
                    print(f"⭐ รีวิว: {review_count} (จุดเด่น: {review_highlights if review_highlights else 'ไม่มีสรุป'})")
                    
                    # --- Bilingual Logic (Thai + English) ---
                    # Move this OUTSIDE except block and fix indentation
                    prefixes_th = [
                        "ประทับใจร้านของคุณมากครับ",
                        "เห็นร้านใน Google Maps แล้วดูดีมากเลยครับ",
                        "ชื่นชมในความใส่ใจและบริการที่เป็นมืออาชีพครับ"
                    ]
                    prefixes_en = [
                        "I'm really impressed by your shop",
                        "Found your place on Google Maps and it looks amazing",
                        "Great professional presence online"
                    ]
                    idx = random.randint(0, len(prefixes_th)-1)
                    prefix_th = prefixes_th[idx]
                    prefix_en = prefixes_en[idx]

                    if review_highlights:
                        content_th = f"ลูกค้าชมเรื่อง {review_highlights} กันเยอะมาก เยี่ยมไปเลยครับ!"
                        content_en = f"It's great to see so many positive reviews specifically mentioning your {review_highlights}!"
                    elif int(review_count) > 0:
                        content_th = "เห็นมีลูกค้ารีวิวชื่นชมร้านไว้เยอะเลย น่าไปใช้บริการมากครับ!"
                        content_en = "I noticed your shop has built a fantastic reputation with great customer reviews!"
                    else:
                        content_th = "ภาพลักษณ์ร้านดูสะอาดตาและตกแต่งสวยงามมากครับ!"
                        content_en = "Your shop looks beautiful, clean, and very welcoming to customers!"
                    
                    suffix = " - Yakpho Aroma (ยักษ์โพธิ์ อโรม่า)"
                    praise_draft = f"{prefix_th} {content_th} / {prefix_en} {content_en}{suffix}"
                        
                    print(f"⭐ อิงจากรีวิว: {review_count} ครั้ง | จุดเด่น: {review_highlights if review_highlights else 'N/A'}")
                    print(f"💬 ร่างคำชม: {praise_draft}")
                    
                    # หาอีเมล
                    email = ""
                    if website:
                        print("Debug: กำลังใช้อีกแท็บตามเข้าไปดูหน้าเว็บเพื่อหาอีเมล...")
                        email = find_email(email_page, website)
                        if email:
                            print(f"📧 อีเมล: {email}")
                        else:
                            print("📧 อีเมล: Not Found")
                    else:
                        print("📧 อีเมล: N/A (ไม่มีเว็บไซต์)")
                    
                    # แพ็กข้อมูลเข้าก้อน JSON (Payload) — ครบถ้วนรวม region
                    payload = {
                        "api_key": API_KEY,
                        "shop_name": final_name if final_name else "Not Found",
                        "city": city,
                        "region": region,
                        "country": country,
                        "maps_url": maps_url.strip() if maps_url else "N/A",
                        "website": website.strip() if website else "N/A",
                        "email": email.strip() if email else "Not Found",
                        "review_count": int(review_count) if review_count.isdigit() else 0,
                        "review_highlights": review_highlights,
                        "praise_draft": praise_draft,
                        "status": "New Lead"
                    }
                    
                    # 5. ส่งหน้าไปที่ PHP Server
                    success = save_lead_to_api(payload)
                    
                    # 6. Fallback ลงไฟล์ถ้า Server ล่ม
                    if not success:
                        save_to_temp(payload)
                        
                    saved_count += 1
                    total_saved += 1
                    
                    # อัปเกรดสถานะทุกครั้งที่บันทึก 1 ร้าน
                    status_msg = f"🔍 Processing {city} ({saved_count}/10)"
                    progress = int((saved_count / 10) * 100)
                    update_pulse(status_msg, progress=progress)
                
                # ปิดจบของเมืองนี้: ยิง API แจ้ง Completed
                if city_id:
                    mark_city_completed(city_id)
                
                # ส่งแจ้งเตือน Telegram หลังจบแต่ละเมือง
                telegram_msg = (
                    f"✅ <b>Job Summary: {city}</b>\n"
                    f"🌍 Country: {country}\n"
                    f"🏠 Leads Found: {saved_count} shops\n"
                    f"🤖 Status: Completed"
                )
                send_telegram_alert(telegram_msg)
                
                # Check for stop loop flag
                if should_break_all:
                    break
                    
            browser.close()
            print(f"\n🚀 สำเร็จ! ดึงข้อมูลรวมทั้งหมดเสร็จสมบูรณ์ บันทึกสำเร็จรวม {total_saved} ร้าน")
            print("💤 ส่งบอทพักผ่อน (IDLE)...")
            try: requests.get(f"{API_URL_BOT_MANAGER}?action=report_status&bot=yakpho_real_work&status=idle&pid=0&api_key={API_KEY}", timeout=5)
            except: pass

    except Exception as e:
        print(f"Error: เกิดข้อผิดพลาดร้ายแรงระหว่างทำงานของ Playwright ({e})")

if __name__ == "__main__":
    run_work()
