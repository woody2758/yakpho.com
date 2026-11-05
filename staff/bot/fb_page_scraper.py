import asyncio
import json
import os
import re
import requests
import random
import urllib.parse
from playwright.async_api import async_playwright
from datetime import datetime, timedelta

# --- CONFIGURATION ---
BASE_URL = "https://yakpho.com/staff/api/"
API_KEY = "yakpho2026"
HEADLESS = True  # Set to True for VPS (Ubuntu Server)

PROVINCES = [
    "กรุงเทพมหานคร", "กระบี่", "กาญจนบุรี", "กาฬสินธุ์", "กำแพงเพชร", "ขอนแก่น", "จันทบุรี", "ฉะเชิงเทรา", "ชลบุรี", "ชัยนาท", 
    "ชัยภูมิ", "ชุมพร", "เชียงราย", "เชียงใหม่", "ตรัง", "ตราด", "ตาก", "นครนายก", "นครปฐม", "นครพนม", "นครราชสีมา", 
    "นครศรีธรรมราช", "นครสวรรค์", "นนทบุรี", "นราธิวาส", "น่าน", "บึงกาฬ", "บุรีรัมย์", "ปทุมธานี", "ประจวบคีรีขันธ์", 
    "ปราจีนบุรี", "ปัตตานี", "พระนครศรีอยุธยา", "พะเยา", "พังงา", "พัทลุง", "พิจิตร", "พิษณุโลก", "เพชรบุรี", "เพชรบูรณ์", 
    "แพร่", "ภูเก็ต", "มหาสารคาม", "มุกดาหาร", "แม่ฮ่องสอน", "ยโสธร", "ยะลา", "ร้อยเอ็ด", "ระนอง", "ระยอง", "ราชบุรี", 
    "ลพบุรี", "ลำปาง", "ลำพูน", "เลย", "ศรีสะเกษ", "สกลนคร", "สงขลา", "สตูล", "สมุทรปราการ", "สมุทรสงคราม", "สมุทรสาคร", 
    "สระแก้ว", "สระบุรี", "สิงห์บุรี", "สุโขทัย", "สุพรรณบุรี", "สุราษฎร์ธานี", "สุรินทร์", "หนองคาย", "หนองบัวลำภู", "อ่างทอง", 
    "อำนาจเจริญ", "อุดรธานี", "อุตรดิตถ์", "อุทัยธานี", "อุบลราชธานี"
]

SEARCH_KEYWORDS = [
    "ร้านนวดใน", "นวดแผนไทยใน", "สปาใน", "นวดเพื่อสุขภาพใน", "นวดอโรม่าใน",
    "รับสมัครพนักงานนวดใน", "หมอนวดใน", "นวดแก้อาการใน",
    "นวดน้ำมันใน", "นวดเท้าใน", "Health and Wellness "
]

def get_time():
    return datetime.now().strftime('%H:%M:%S')

async def human_delay(min_s=5, max_s=15):
    """Wait for a random duration to mimic human reading/thinking."""
    delay = random.uniform(min_s, max_s)
    await asyncio.sleep(delay)

async def human_scroll(page):
    """Perform a series of small, jittery scrolls like a human reading a feed."""
    steps = random.randint(3, 6)
    for _ in range(steps):
        scroll_amt = random.randint(300, 800)
        if random.random() < 0.2:
            scroll_amt = -random.randint(100, 300)
        await page.evaluate(f"window.scrollBy(0, {scroll_amt})")
        await human_delay(1, 4)

def parse_followers(text):
    if not text or text == "N/A": return "N/A"
    t = text.lower().replace(",", "")
    multiplier = 1
    if "k" in t or "พัน" in t: multiplier = 1000
    elif "m" in t or "ล้าน" in t: multiplier = 1000000
    elif "หมื่น" in t: multiplier = 10000
    elif "แสน" in t: multiplier = 100000
    
    num_match = re.search(r'[\d.]+', t)
    if num_match:
        try: return str(int(float(num_match.group(0)) * multiplier))
        except: pass
    return text.strip()

async def save_pages(pages):
    try:
        headers = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"}
        r = requests.post(f"{BASE_URL}save_fb_page.php?api_key={API_KEY}", json=pages, headers=headers, timeout=30)
        print(f"[{get_time()}]    [API] Saved to server. Status: {r.status_code}")
    except Exception as e:
        print(f"[{get_time()}]    [API] Save Error: {e}")

async def get_page_details(page, url, expected_name="Unknown"):
    """Deep dive into a page with human-like interactions using M-Basic."""
    details = {"followers": "N/A", "phone": None, "email": None, "website": None, "name": "Unknown"}
    try:
        # Standardize URL to M-Basic for consistent, lightweight scraping
        m_url = url.replace("www.facebook.com", "mbasic.facebook.com").replace("m.facebook.com", "mbasic.facebook.com")
        if "mbasic.facebook.com" not in m_url:
            m_url = f"https://mbasic.facebook.com/{url.split('facebook.com/')[-1]}"

        print(f"[{get_time()}]    - Inspecting (M-Basic): {m_url}")
        await page.goto(m_url, wait_until="domcontentloaded", timeout=60000)
        await human_delay(3, 6)
        
        # Pull business name securely from the Page Title
        title_str = await page.title()
        if title_str and title_str.strip() != "Facebook":
            clean_name = title_str.replace(" - Home | Facebook", "").replace(" | Facebook", "").replace(" - หน้าหลัก | Facebook", "").split(" - ")[0].strip()
            if len(clean_name) > 2:
                details["name"] = clean_name
        
        # V4.5 Fallback to Search Result Name
        if details["name"] == "Unknown" and expected_name != "Unknown":
            details["name"] = expected_name
        
        content = await page.content()
        
        # Extract clean visible text to bypass HTML tags (spans, divs) interrupting words
        try: clean_text = await page.locator("body").inner_text()
        except: clean_text = content
        
        # Followers
        f_match = re.search(r'(?:ผู้ติดตาม|ติดตาม|followers)[\s:]*(?:เพจนี้)?\s*(\d[\d.,]*(?:\s*[kKmMพันหมื่นแสนล้าน]+)?)|(\d[\d.,]*(?:\s*[kKmMพันหมื่นแสนล้าน]+)?)\s*(?:คน)?\s*(?:เพจนี้)?\s*(?:ผู้ติดตาม|ติดตาม|followers)', clean_text, re.IGNORECASE)
        if f_match: 
            raw_f = (f_match.group(1) or f_match.group(2) or "").strip(" .,\n")
            details["followers"] = parse_followers(raw_f)
        
        # Phone
        p_match = re.search(r'0[2-9]\d{1,2}-?\d{3}-?\d{3,4}|\+66\s?\d{1,2}-?\d{3}-?\d{3,4}', content)
        if p_match: details["phone"] = p_match.group(0).strip()
        
        # Email
        e_match = re.search(r'[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}', content)
        if e_match: details["email"] = e_match.group(0).strip()
        
        # Website (Look for external links)
        links = await page.query_selector_all("a[href*='http']")
        for link in links:
            href = await link.get_attribute("href")
            if not href: continue
            if all(x not in href for x in ["facebook.com", "messenger.com", "instagram.com", "whatsapp.com", "l.facebook.com"]):
                clean_url = href.split('?fbclid=')[0].split('&fbclid=')[0]
                details["website"] = clean_url
                break
            elif "l.facebook.com" in href:
                match = re.search(r'u=(.*?)&', href)
                if match:
                    website_url = urllib.parse.unquote(match.group(1))
                    if "bing.com/maps" not in website_url.lower() and "google.com/maps" not in website_url.lower() and "maps.app.goo.gl" not in website_url.lower():
                        clean_url = website_url.split('?fbclid=')[0].split('&fbclid=')[0]
                        details["website"] = clean_url
                        break
                    
    except Exception as e:
        print(f"[{get_time()}]    ! Detail Error: {e}")
    return details

async def scrape_pages():
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=HEADLESS)
        storage_path = "fb_auth_thai.json"
        
        # V4.4: UNIVERSAL COOKIE LOADER
        storage_state = None
        loaded_cookie_count = 0
        if os.path.exists(storage_path) and os.path.getsize(storage_path) > 10:
            try:
                with open(storage_path, "r", encoding="utf-8") as f:
                    c_data = json.load(f)
                    if isinstance(c_data, list):
                        # Convert raw array to Playwright format
                        valid_cookies = []
                        for c in c_data:
                            if "name" in c and "value" in c:
                                valid_cookies.append({
                                    "name": c["name"],
                                    "value": c["value"],
                                    "domain": c.get("domain", ".facebook.com"),
                                    "path": c.get("path", "/")
                                })
                        storage_state = {"cookies": valid_cookies, "origins": []}
                        loaded_cookie_count = len(valid_cookies)
                    elif isinstance(c_data, dict) and "cookies" in c_data:
                        storage_state = c_data
                        loaded_cookie_count = len(c_data["cookies"])
                print(f"[{get_time()}] [AUTH] Loaded {loaded_cookie_count} cookies from {storage_path}")
            except Exception as e:
                print(f"[{get_time()}] [AUTH] Error loading cookies: {e}")
        else:
            print(f"[{get_time()}] [AUTH] WARNING: No valid fb_auth.json found!")
        
        ua = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
        context = await browser.new_context(
            storage_state=storage_state, 
            user_agent=ua,
            viewport={'width': 1920, 'height': 1080}
        )
        
        # --- STEALTH INITIALIZATION ---
        stealth_script = """
        Object.defineProperty(navigator, 'webdriver', {get: () => undefined});
        window.chrome = { runtime: {} };
        Object.defineProperty(navigator, 'languages', {get: () => ['en-US', 'en', 'th']});
        Object.defineProperty(navigator, 'plugins', {get: () => [1, 2, 3, 4, 5]});
        """
        
        async def init_stealth(p):
            await p.add_init_script(stealth_script)

        page = await context.new_page()
        await init_stealth(page)

        print(f"[{get_time()}] [Maya V4.3] True Vision Engaged...")
        # DETAIL PAGE INSTANCE
        detail_page = await context.new_page()
        await init_stealth(detail_page)
        
        # Check Initial Session
        await page.goto("https://www.facebook.com/", wait_until="domcontentloaded", timeout=60000)
        await human_delay(10, 15) # Wait for final redirect
        title = await page.title()
        content_low = (await page.content()).lower()
        if "login" in page.url.lower() or "log in" in title.lower() or "เข้าสู่ระบบ" in title or "forgot password" in content_low or "create new account" in content_low:
            print(f"[{get_time()}] --- !!! DEEP-SCAN FAILURE: SESSION EXPIRED (Stopping) !!! ---")
            try: requests.get(f"{BASE_URL}bot_manager.php?action=report_status&bot=fb_page_scraper&status=error_auth&api_key={API_KEY}", timeout=5)
            except: pass
            await browser.close()
            return # Mandatory Exit
        else:
            print(f"[{get_time()}] Session Active. Acting like a user...")
            try: requests.get(f"{BASE_URL}bot_manager.php?action=report_status&bot=fb_page_scraper&status=running&pid={os.getpid()}&api_key={API_KEY}", timeout=5)
            except: pass

        work_list = PROVINCES.copy()
        random.shuffle(work_list)
        visit_count = 0

        for province in work_list:
            keyword = random.choice(SEARCH_KEYWORDS)
            query = f"{keyword} {province}"
            print(f"\n[{get_time()}] --- Harvesting Task: {query} ---")
            
            try:
                search_url = f"https://mbasic.facebook.com/search/pages/?q={urllib.parse.quote(query)}"
                await page.goto(search_url, wait_until="domcontentloaded", timeout=60000)
                await human_delay(4, 7)
                
                # Check for M-Basic Login Prompts
                login_btn = await page.query_selector("input[value*='Log In'], input[value*='เข้าสู่ระบบ'], input[value*='Continue'], input[value*='ดำเนินการต่อ']")
                if login_btn:
                    print(f"[{get_time()}] - M-Basic gating detected. Auto-confirming...")
                    await login_btn.click()
                    await page.wait_for_load_state("domcontentloaded")
                    await human_delay(5, 8)

                # V4.2: CHRONO TRACKER (Timestamped Screenshot)
                ss_filename = f"maya_view_{datetime.now().strftime('%Y%m%d_%H%M%S')}.png"
                await page.screenshot(path=ss_filename)
                print(f"[{get_time()}] - [EVIDENCE] Chrono Screenshot saved: '{ss_filename}'")

                await human_scroll(page)
                content = await page.content()
                all_links = await page.query_selector_all("a")
                print(f"[{get_time()}] - [Diagnostics] Content Size: {len(content)}, Total <a>: {len(all_links)}")

                # V4.3: DEEP-SCAN FAIL-FAST (Exit on Auth/IP Block)
                title = await page.title()
                content_low = content.lower()
                is_auth_wall = "login" in page.url.lower() or "log in" in title.lower() or "เข้าสู่ระบบ" in title or "forgot password" in content_low
                is_ip_block = len(content) < 1000 and "Not Found" in content
                
                if (len(content) < 50000 and is_auth_wall) or is_ip_block:
                    print(f"[{get_time()}] !!! ALERT: SESSION DROPPED OR IP BLOCKED. STOPPING... !!!")
                    status = "error_auth" if is_auth_wall else "error_ip"
                    try: requests.get(f"{BASE_URL}bot_manager.php?action=report_status&bot=fb_page_scraper&status={status}&api_key={API_KEY}", timeout=5)
                    except: pass
                    break # Stop search loop

                page_urls = {}
                for link in all_links:
                    href = await link.get_attribute("href")
                    if not href: continue
                    link_text_raw = await link.inner_text()
                    link_text = link_text_raw.lower()
                    
                    # SYSTEM LINK FILTER (Extreme)
                    ignore = ["/search", "/groups", "/ads/", "/business/", "/help/", "/legal/", "/settings", "/stories", "/photo", "/posts", "/notifications", "/messages", "/marketplace", "/lite/", "/watch/", "/gaming/", "/latest/", "/browse/", "/about", "/pages/create", "/careers/", "/reg/", "/login/", "/recover/", "/privacy/", "/terms/", "/checkpoint/", "/cookie/", "/directory/", "/events/", "/funding/", "/items/", "/memories/", "/offers/", "/places/", "/points/", "/safety/", "/safetycheck/", "/saved/", "/services/", "/support/", "/v/", "/video/"]
                    if any(x in href.lower() for x in ignore): continue
                    if any(x in link_text for x in ["video", "facebook", "lite", "watch", "more", "see all", "privacy", "terms", "cookies", "login", "create", "register", "careers", "about", "help"]): continue
                    
                    # Business Page Pattern Detection
                    clean = href.split('?')[0].split('&')[0]
                    if not clean.startswith("http"):
                        if clean.startswith("/"): clean = f"https://www.facebook.com{clean}"
                        else: continue
                    
                    if "facebook.com/" in clean:
                        path_parts = [p for p in clean.replace("https://www.facebook.com/", "").replace("https://m.facebook.com/", "").replace("https://mbasic.facebook.com/", "").split('/') if p]
                        # Business pages usually have simple paths or specific ID patterns
                        if len(path_parts) == 1 or ("pages/" in clean) or ("profile.php" in clean):
                             if len(link_text_raw.strip()) > 3:
                                 if clean not in page_urls or page_urls[clean] == "Unknown":
                                     page_urls[clean] = link_text_raw.strip()
                             elif clean not in page_urls:
                                 page_urls[clean] = "Unknown"

                # V4.8: UNLEASHED MODE (Harvest all visible search results)
                urls_to_process = list(page_urls.keys())
                print(f"[{get_time()}] - Identified {len(page_urls)} potential pages. Harvesting ALL {len(urls_to_process)}...")
                
                for idx, url in enumerate(urls_to_process):
                    expected_name = page_urls[url]
                    visit_count += 1
                    print(f"[{get_time()}] - [{province}] Analyzing [{idx+1}/{len(urls_to_process)}]: {url}")
                    details = await get_page_details(detail_page, url, expected_name)
                    
                    # V4.0 Quality Gate: Mandatory Content Check
                    massage_keywords = ["นวด", "สปา", "massage", "spa", "aroma", "wellness", "คลินิก", "therapy", "clinic"]
                    content_str = (details["name"] + (details["phone"] or "") + (details["email"] or "")).lower()
                    if not any(x in content_str for x in massage_keywords):
                        print(f"[{get_time()}]    [SKIP] Not a Massage/Spa page: {details['name']}")
                        continue

                    # NEGATIVE QUALITY GATE (Exclude Products & Junk)
                    anti_junk = ["น้ำมัน", "ครีม", "ยาหม่อง", "สมุนไพรดิบ", "หัวเชื้อ", "อุปกรณ์", "เตียงนวด", "รู้รอบ", "ธุรกิจ", "แฟรนไชส์", "รับทำ", "การตลาด", "จำหน่าย", "ส่งออก", "น้ำหอม", "เซรั่ม"]
                    if any(x in details["name"].lower() for x in anti_junk):
                        print(f"[{get_time()}]    [SKIP] Product/School/Junk identified: {details['name']}")
                        continue

                    junk_names = ["unknown", "video", "facebook", "lite", "n/a", "facebook lite", "watch", "home", "profile", "register", "careers"]
                    if details["name"].lower() in junk_names or len(details["name"]) < 2:
                        print(f"[{get_time()}]    [SKIP] System/Junk Page Detected: {details['name']}")
                        continue

                    page_id = url.rstrip('/').split('/')[-1]
                    
                    # Build Clean Payload
                    payload = [{
                        "name": details["name"],
                        "url": url,
                        "messenger": f"https://m.me/{page_id}",
                        "province": province,
                        "followers": details["followers"],
                        "phone": details["phone"],
                        "email": details["email"],
                        "website": details["website"],
                        "is_active": 1
                    }]
                    
                    print(f"[{get_time()}]    [API] Sending '{details['name']}' to yakpho.com...")
                    await save_pages(payload)
                    await human_delay(15, 30)

                    if visit_count % 15 == 0:
                        break_min = random.randint(5, 12)
                        print(f"[{get_time()}] --- Natural Break: Resting for {break_min} mins ---")
                        await asyncio.sleep(break_min * 60)
                
            except Exception as e:
                print(f"[{get_time()}] Error processing province {province}: {e}")

            await human_delay(30, 90)
        
        await detail_page.close()
        await browser.close()

if __name__ == "__main__":
    asyncio.run(scrape_pages())
