import asyncio
import json
import os
import requests
import random
import re
import google.generativeai as genai
from playwright.async_api import async_playwright
from datetime import datetime

# --- CONFIGURATION ---
BASE_URL = "https://yakpho.com/staff/api/"
API_KEY = "yakpho2026"
HEADLESS = True  # Set to True for VPS (Ubuntu Server)

async def update_status(group_id, status):
    headers = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"}
    requests.post(f"{BASE_URL}update_group_status.php?api_key={API_KEY}", 
                  json={"id": group_id, "status": status}, headers=headers, timeout=15)

async def get_queued_groups():
    headers = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"}
    r = requests.get(f"{BASE_URL}get_queued_groups.php?api_key={API_KEY}", headers=headers, timeout=15)
    data = r.json()
    if not data.get('ok'):
        print(f"API Error fetching queued groups: {data}")
    return data.get('groups', [])

async def check_pending_groups(page):
    headers = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"}
    try:
        r = requests.get(f"{BASE_URL}get_pending_groups.php?api_key={API_KEY}", headers=headers, timeout=15)
        data = r.json()
        if not data.get('ok'): return
        pending_groups = data.get('groups', [])
        if not pending_groups: return
        
        print(f"\n[{datetime.now().strftime('%H:%M:%S')}] 🔍 Checking {len(pending_groups)} pending groups for approval...")
        for g in pending_groups:
            try:
                await page.goto(g['group_url'], timeout=60000, wait_until="domcontentloaded")
                await asyncio.sleep(random.randint(4, 7))
                
                is_joined = False
                
                # Check for "Write something" box (strongest indicator of being joined)
                write_post = await page.locator('div[role="button"]:has-text("Write something..."), div[role="button"]:has-text("เขียนอะไรสักหน่อย")').count()
                if write_post > 0: is_joined = True
                
                # Check for "Joined" button
                joined_btn = await page.locator('div[aria-label="Joined"], div[aria-label="เข้าร่วมแล้ว"], div[role="button"]:has-text("Joined"), div[role="button"]:has-text("เข้าร่วมแล้ว")').count()
                if joined_btn > 0: is_joined = True
                
                # Check for "Join group" button (means we got rejected or cancelled)
                join_btn_count = await page.locator('div[aria-label="Join group"], div[aria-label="เข้าร่วมกลุ่ม"], div[role="button"]:has-text("Join group"), div[role="button"]:has-text("เข้าร่วมกลุ่ม")').count()

                if is_joined:
                    print(f"    🎉 {g['group_name']} is APPROVED! Marking as Joined.")
                    await update_status(g['id'], 'Joined')
                elif join_btn_count > 0:
                    print(f"    ❌ {g['group_name']} was REJECTED. Reverting to Not Joined.")
                    await update_status(g['id'], 'Not Joined')
                else:
                    print(f"    ⏳ {g['group_name']} is still Pending.")
                
                await asyncio.sleep(random.randint(15, 30)) # Wait to not trigger spam block
            except Exception as e:
                print(f"    ⚠️ Error checking {g['group_name']}: {e}")
                
    except Exception as e:
        print(f"Error fetching pending groups: {e}")

async def get_gemini_key():
    headers = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"}
    r = requests.get(f"{BASE_URL}get_fb_settings.php?api_key={API_KEY}", headers=headers, timeout=15)
    return r.json().get('gemini_api_key', '')

def init_ai_stable(gemini_key):
    try:
        genai.configure(api_key=gemini_key)
        all_models = [m.name for m in genai.list_models() if 'generateContent' in m.supported_generation_methods]
        blocked = ['2.0', '2.5', 'robotics']
        clean = [m for m in all_models if not any(bk in m.lower() for bk in blocked)]
        best = ""
        for pref in ['1.5-flash', 'flash', 'pro']:
            for m in clean:
                if pref in m.lower():
                    best = m
                    break
            if best: break
        return genai.GenerativeModel(best if best else all_models[0])
    except: return None

async def answer_questions(page, group_name, gemini_key):
    try:
        # Check if there are textboxes for questions
        textboxes = await page.query_selector_all("textarea, div[role='textbox']")
        if not textboxes:
            return
            
        print("    📝 Filling generic answers (AI disabled to save quota)...")
        # Generic polite answer that works for most groups
        answer_text = "Yes, I agree. We are interested in joining this community. Thank you!"
        
        # Fill textboxes
        for box in textboxes:
            await box.click()
            await box.fill(answer_text)
            await asyncio.sleep(0.5)
            
    except Exception as e:
        print(f"⚠️ Error answering questions: {str(e)[:150]}...")

async def get_all_settings():
    headers = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"}
    try:
        r = requests.get(f"{BASE_URL}get_fb_settings.php?api_key={API_KEY}", headers=headers, timeout=15)
        return r.json().get('settings', {})
    except: return {}

async def run_joiner():
    print("\n" + "="*50)
    print(" >>> FB JOINER (V4.0 - CLOUD ENABLED) <<<")
    print("="*50)
    
    groups = await get_queued_groups()
    if not groups:
        print("No groups in queue.")
        return

    settings = await get_all_settings()
    gemini_key = settings.get('gemini_api_key', '')
    active_role = settings.get('active_persona', 'worldwide')
    db_cookies_json = settings.get('cookies', {}).get(active_role, '')

    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=HEADLESS)
        
        # 🔑 Load Cookies from Cloud
        raw_cookies = None
        if db_cookies_json and len(db_cookies_json) > 50:
            print(f"☁️ Using Cloud Cookies for: {active_role.upper()}")
            try: raw_cookies = json.loads(db_cookies_json)
            except: print("⚠️ Invalid JSON in DB for cookies.")
        
        if not raw_cookies:
            # Fallback Local
            local_file = f"fb_auth_{active_role}.json"
            if os.path.exists(local_file):
                print(f"📂 Using Local Fallback: {local_file}")
                with open(local_file, 'r') as f: raw_cookies = json.load(f)
        
        storage_state = None
        if raw_cookies:
            # Sanitizer & Format for storage_state
            clean_cookies = []
            for c in raw_cookies:
                ss = str(c.get('sameSite', '')).capitalize()
                if ss not in ['Strict', 'Lax', 'None']:
                    if 'sameSite' in c: del c['sameSite']
                else: c['sameSite'] = ss
                # Important for storage_state: ensure domain and path exist
                cd = {
                    "name": c["name"], "value": c["value"],
                    "domain": c.get("domain", ".facebook.com"),
                    "path": c.get("path", "/")
                }
                clean_cookies.append(cd)
            storage_state = {"cookies": clean_cookies, "origins": []}
            print(f"✅ Persona Ready: {active_role.upper()}")
        else:
            print(f"❌ ERROR: No cookies found for {active_role}")
            await browser.close()
            return

        context = await browser.new_context(
            storage_state=storage_state,
            user_agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
            viewport={'width': 1920, 'height': 1080}
        )
        page = await context.new_page()

        # ══════════════════════════════════════════════════
        # 🔐 LOGIN GUARD — ดักทุกกรณีที่หลุด Session
        # ══════════════════════════════════════════════════
        print("Checking login status...")
        await page.goto("https://www.facebook.com/", wait_until="domcontentloaded", timeout=60000)
        await asyncio.sleep(random.randint(4, 6))

        session_ok = True
        session_fail_reason = ""

        # สัญญาณที่ 1: URL มีคำว่า login
        if "login" in page.url.lower():
            session_ok = False
            session_fail_reason = "URL contains 'login'"

        # สัญญาณที่ 2: Title มีคำว่า เข้าสู่ระบบ / Log in / Log into
        page_title = (await page.title()).lower()
        if any(kw in page_title for kw in ["เข้าสู่ระบบ", "log in", "log into", "facebook login"]):
            session_ok = False
            session_fail_reason = f"Title indicates login page: '{page_title}'"

        # สัญญาณที่ 3: หน้า Account Picker (มีปุ่ม "Continue" + "Use another profile")
        # → กรณีที่เห็นในรูป: FB แสดง Profile Card ให้เลือก แต่ URL ยังเป็น facebook.com
        if session_ok:
            try:
                has_continue = await page.locator('div[role="button"]:has-text("Continue"), a:has-text("Continue")').count()
                has_another  = await page.locator(':has-text("Use another profile"), :has-text("Create new account")').count()
                # ถ้าเจอทั้งคู่ = หน้า Account Picker แน่นอน
                if has_continue > 0 and has_another > 0:
                    session_ok = False
                    session_fail_reason = "Account Picker page detected (session cookie expired)"
            except: pass

        # สัญญาณที่ 4: ยืนยัน logged-in ด้วยการหา News Feed / Home icon
        if session_ok:
            try:
                logged_in_signal = await page.locator(
                    '[aria-label="Home"], [aria-label="หน้าแรก"], '
                    'div[role="feed"], [data-pagelet="FeedUnit_0"]'
                ).count()
                if logged_in_signal == 0:
                    # ไม่พบสัญลักษณ์ logged-in → น่าสงสัย ให้ screenshot แล้วหยุด
                    session_ok = False
                    session_fail_reason = "No logged-in signals found (no feed/home icon)"
            except: pass

        if not session_ok:
            print(f"!!! SESSION EXPIRED: {session_fail_reason} !!!")
            print("!!! คุณแมท กลับไปพักก่อนนะครับ — Update fb_auth_worldwide.json แล้วค่อยรันใหม่ !!!")
            await browser.close()
            return

        # Read Profile Name (เฉพาะเมื่อ session ผ่าน)
        try:
            profile_name = "Unknown Name"
            acc_btn = await page.query_selector('div[aria-label*="บัญชี"], div[aria-label*="Account"]')
            if acc_btn:
                aria_text = await acc_btn.get_attribute("aria-label")
                profile_name = aria_text.replace("บัญชี", "").replace("Account", "").strip()
            if profile_name == "Unknown Name" or not profile_name:
                profile_link = await page.query_selector("div[role='navigation'] span[dir='auto']")
                if profile_link:
                    profile_name = await profile_link.inner_text()
            print(f"✅ Logged in successfully. Active Profile: {profile_name}")
        except:
            print(f"✅ Logged in successfully. (Profile Name Unknown)")

        MAX_JOINS = 5  # จอย 5 กลุ่มต่อรอบ แล้วพัก
        joined_count = 0

        for idx, g in enumerate(groups):
            if joined_count >= MAX_JOINS:
                rest_minutes = random.randint(5, 30)
                print(f"[{datetime.now().strftime('%H:%M:%S')}] ✋ ครบ {MAX_JOINS} กลุ่มแล้ว — คุณแมทขอหน่วงเวลาพัก {rest_minutes} นาที (คิวเหลืออีก {len(groups)-idx} กลุ่ม)")
                await asyncio.sleep(rest_minutes * 60)
                joined_count = 0
                
                # รีเฟรชหน้าเพื่อกัน Session หลุดระหว่างพักนาน
                await page.goto("https://www.facebook.com/", wait_until="domcontentloaded", timeout=60000)
                await asyncio.sleep(random.randint(4, 6))

            print(f"\n[{datetime.now().strftime('%H:%M:%S')}] [{joined_count+1}/{MAX_JOINS}] Joining: {g['group_name']} ({g['group_url']})")
            try:
                await page.goto(g['group_url'], timeout=60000, wait_until="domcontentloaded")
                await asyncio.sleep(random.randint(4, 7))  # รอให้ React render ปุ่ม Join

                # ค้นหาและกดปุ่ม "Join Group" หรือ "เข้าร่วมกลุ่ม" ทนทานต่อ React/Translations ด้วย Regex
                join_locs = [
                    # แบบ role + ชื่อ contains (ไม่ใช้ ^ $ แล้ว เพราะ FB ใส่ space นำหน้าบางครั้ง)
                    page.get_by_role("button", name=re.compile(r"(Join|Join group|Join Group|Request to join|Request to Join)", re.IGNORECASE)),
                    page.get_by_role("button", name=re.compile(r"(เข้าร่วม|เข้าร่วมกลุ่ม|ขอเข้าร่วม|ขอเข้าร่วมกลุ่ม)")),
                    # aria-label แบบ contains (FB เปลี่ยนบ่อย)
                    page.locator('[aria-label*="Join group"], [aria-label*="Join Group"], [aria-label*="Request to join"]'),
                    page.locator('[aria-label*="เข้าร่วม"], [aria-label*="ขอเข้าร่วม"]'),
                    # div[role=button] + has-text
                    page.locator('div[role="button"]:has-text("Join group")'),
                    page.locator('div[role="button"]:has-text("Request to join")'),
                    page.locator('div[role="button"]:has-text("เข้าร่วมกลุ่ม")'),
                    page.locator('div[role="button"]:has-text("ขอเข้าร่วม")'),
                    # span text-is (เฉพาะ)
                    page.locator('span:text-is("Join group"), span:text-is("Request to join")'),
                    page.locator('span:text-is("Join")'),
                    page.locator('span:text-is("เข้าร่วมกลุ่ม"), span:text-is("ขอเข้าร่วมกลุ่ม")'),
                    page.locator('span:text-is("เข้าร่วม"), span:text-is("ขอเข้าร่วม")'),
                    # *** Fallback กว้างที่สุด ***
                    page.locator('[role="button"]:has(span:text-matches("Join", "i"))'),
                ]
                
                joined_btn = None
                for loc in join_locs:
                    if await loc.count() > 0:
                        joined_btn = loc.first
                        break

                if joined_btn:
                    await joined_btn.click(force=True, timeout=5000)
                    print(f"[{datetime.now().strftime('%H:%M:%S')}] ✅ Clicked Join Button for {g['group_name']}")
                    await asyncio.sleep(4)
                    
                    # Check for questions
                    if await page.query_selector("div[role='dialog']"):
                        print("Answering entry questions...")
                        await answer_questions(page, g['group_name'], gemini_key)
                        # Submit form using Locators
                        submit_locs = [
                            page.get_by_role("button", name=re.compile(r"^(Submit|Send|ส่ง|ส่งคำตอบ|ส่งคำขอ)$", re.IGNORECASE)),
                            page.locator('[aria-label="Submit"], [aria-label="ส่ง"], [aria-label="ส่งคำตอบ"]')
                        ]
                        for s_loc in submit_locs:
                            if await s_loc.count() > 0:
                                await s_loc.first.click(force=True, timeout=5000)
                                break

                    await update_status(g['id'], 'Pending')
                    print(f"[{datetime.now().strftime('%H:%M:%S')}] 🎉 Successfully requested to join: {g['group_name']}")
                    joined_count += 1
                else:
                    print(f"[{datetime.now().strftime('%H:%M:%S')}] ⚠️ Could not find Join button for {g['group_name']} (Already joined?)")
                    await update_status(g['id'], 'Joined')

            except Exception as e:
                print(f"Error joining {g['group_name']}: {e}")
            
            # Anti-Block delay
    gemini_key = settings.get('gemini_api_key', '')
    active_role = settings.get('active_persona', 'worldwide')
    db_cookies_json = settings.get('cookies', {}).get(active_role, '')

    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=HEADLESS)
        
        # 🔑 Load Cookies from Cloud
        raw_cookies = None
        if db_cookies_json and len(db_cookies_json) > 50:
            print(f"☁️ Using Cloud Cookies for: {active_role.upper()}")
            try: raw_cookies = json.loads(db_cookies_json)
            except: print("⚠️ Invalid JSON in DB for cookies.")
        
        if not raw_cookies:
            # Fallback Local
            local_file = f"fb_auth_{active_role}.json"
            if os.path.exists(local_file):
                print(f"📂 Using Local Fallback: {local_file}")
                with open(local_file, 'r') as f: raw_cookies = json.load(f)
        
        storage_state = None
        if raw_cookies:
            # Sanitizer & Format for storage_state
            clean_cookies = []
            for c in raw_cookies:
                ss = str(c.get('sameSite', '')).capitalize()
                if ss not in ['Strict', 'Lax', 'None']:
                    if 'sameSite' in c: del c['sameSite']
                else: c['sameSite'] = ss
                # Important for storage_state: ensure domain and path exist
                cd = {
                    "name": c["name"], "value": c["value"],
                    "domain": c.get("domain", ".facebook.com"),
                    "path": c.get("path", "/")
                }
                clean_cookies.append(cd)
            storage_state = {"cookies": clean_cookies, "origins": []}
            print(f"✅ Persona Ready: {active_role.upper()}")
        else:
            print(f"❌ ERROR: No cookies found for {active_role}")
            await browser.close()
            return

        context = await browser.new_context(
            storage_state=storage_state,
            user_agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
            viewport={'width': 1920, 'height': 1080}
        )
        page = await context.new_page()

        # ══════════════════════════════════════════════════
        # 🔐 LOGIN GUARD — ดักทุกกรณีที่หลุด Session
        # ══════════════════════════════════════════════════
        print("Checking login status...")
        await page.goto("https://www.facebook.com/", wait_until="domcontentloaded", timeout=60000)
        await asyncio.sleep(random.randint(4, 6))

        session_ok = True
        session_fail_reason = ""

        # สัญญาณที่ 1: URL มีคำว่า login
        if "login" in page.url.lower():
            session_ok = False
            session_fail_reason = "URL contains 'login'"

        # สัญญาณที่ 2: Title มีคำว่า เข้าสู่ระบบ / Log in / Log into
        page_title = (await page.title()).lower()
        if any(kw in page_title for kw in ["เข้าสู่ระบบ", "log in", "log into", "facebook login"]):
            session_ok = False
            session_fail_reason = f"Title indicates login page: '{page_title}'"

        # สัญญาณที่ 3: หน้า Account Picker (มีปุ่ม "Continue" + "Use another profile")
        # → กรณีที่เห็นในรูป: FB แสดง Profile Card ให้เลือก แต่ URL ยังเป็น facebook.com
        if session_ok:
            try:
                has_continue = await page.locator('div[role="button"]:has-text("Continue"), a:has-text("Continue")').count()
                has_another  = await page.locator(':has-text("Use another profile"), :has-text("Create new account")').count()
                # ถ้าเจอทั้งคู่ = หน้า Account Picker แน่นอน
                if has_continue > 0 and has_another > 0:
                    session_ok = False
                    session_fail_reason = "Account Picker page detected (session cookie expired)"
            except: pass

        # สัญญาณที่ 4: ยืนยัน logged-in ด้วยการหา News Feed / Home icon
        if session_ok:
            try:
                logged_in_signal = await page.locator(
                    '[aria-label="Home"], [aria-label="หน้าแรก"], '
                    'div[role="feed"], [data-pagelet="FeedUnit_0"]'
                ).count()
                if logged_in_signal == 0:
                    # ไม่พบสัญลักษณ์ logged-in → น่าสงสัย ให้ screenshot แล้วหยุด
                    session_ok = False
                    session_fail_reason = "No logged-in signals found (no feed/home icon)"
            except: pass

        if not session_ok:
            print(f"!!! SESSION EXPIRED: {session_fail_reason} !!!")
            print("!!! คุณแมท กลับไปพักก่อนนะครับ — Update fb_auth_worldwide.json แล้วค่อยรันใหม่ !!!")
            await browser.close()
            return

        # Read Profile Name (เฉพาะเมื่อ session ผ่าน)
        try:
            profile_name = "Unknown Name"
            acc_btn = await page.query_selector('div[aria-label*="บัญชี"], div[aria-label*="Account"]')
            if acc_btn:
                aria_text = await acc_btn.get_attribute("aria-label")
                profile_name = aria_text.replace("บัญชี", "").replace("Account", "").strip()
            if profile_name == "Unknown Name" or not profile_name:
                profile_link = await page.query_selector("div[role='navigation'] span[dir='auto']")
                if profile_link:
                    profile_name = await profile_link.inner_text()
            print(f"✅ Logged in successfully. Active Profile: {profile_name}")
        except:
            print(f"✅ Logged in successfully. (Profile Name Unknown)")

        MAX_JOINS = 5  # จอย 5 กลุ่มต่อรอบ แล้วพัก
        joined_count = 0

        for idx, g in enumerate(groups):
            if joined_count >= MAX_JOINS:
                rest_minutes = random.randint(5, 30)
                print(f"[{datetime.now().strftime('%H:%M:%S')}] ✋ ครบ {MAX_JOINS} กลุ่มแล้ว — คุณแมทขอหน่วงเวลาพัก {rest_minutes} นาที (คิวเหลืออีก {len(groups)-idx} กลุ่ม)")
                await asyncio.sleep(rest_minutes * 60)
                joined_count = 0
                
                # รีเฟรชหน้าเพื่อกัน Session หลุดระหว่างพักนาน
                await page.goto("https://www.facebook.com/", wait_until="domcontentloaded", timeout=60000)
                await asyncio.sleep(random.randint(4, 6))

            print(f"\n[{datetime.now().strftime('%H:%M:%S')}] [{joined_count+1}/{MAX_JOINS}] Joining: {g['group_name']} ({g['group_url']})")
            try:
                await page.goto(g['group_url'], timeout=60000, wait_until="domcontentloaded")
                await asyncio.sleep(random.randint(4, 7))  # รอให้ React render ปุ่ม Join

                # ค้นหาและกดปุ่ม "Join Group" หรือ "เข้าร่วมกลุ่ม" ทนทานต่อ React/Translations ด้วย Regex
                join_locs = [
                    # แบบ role + ชื่อ contains (ไม่ใช้ ^ $ แล้ว เพราะ FB ใส่ space นำหน้าบางครั้ง)
                    page.get_by_role("button", name=re.compile(r"(Join|Join group|Join Group|Request to join|Request to Join)", re.IGNORECASE)),
                    page.get_by_role("button", name=re.compile(r"(เข้าร่วม|เข้าร่วมกลุ่ม|ขอเข้าร่วม|ขอเข้าร่วมกลุ่ม)")),
                    # aria-label แบบ contains (FB เปลี่ยนบ่อย)
                    page.locator('[aria-label*="Join group"], [aria-label*="Join Group"], [aria-label*="Request to join"]'),
                    page.locator('[aria-label*="เข้าร่วม"], [aria-label*="ขอเข้าร่วม"]'),
                    # div[role=button] + has-text
                    page.locator('div[role="button"]:has-text("Join group")'),
                    page.locator('div[role="button"]:has-text("Request to join")'),
                    page.locator('div[role="button"]:has-text("เข้าร่วมกลุ่ม")'),
                    page.locator('div[role="button"]:has-text("ขอเข้าร่วม")'),
                    # span text-is (เฉพาะ)
                    page.locator('span:text-is("Join group"), span:text-is("Request to join")'),
                    page.locator('span:text-is("Join")'),
                    page.locator('span:text-is("เข้าร่วมกลุ่ม"), span:text-is("ขอเข้าร่วมกลุ่ม")'),
                    page.locator('span:text-is("เข้าร่วม"), span:text-is("ขอเข้าร่วม")'),
                    # *** Fallback กว้างที่สุด ***
                    page.locator('[role="button"]:has(span:text-matches("Join", "i"))'),
                ]
                
                joined_btn = None
                for loc in join_locs:
                    if await loc.count() > 0:
                        joined_btn = loc.first
                        break

                if joined_btn:
                    await joined_btn.click(force=True, timeout=5000)
                    print(f"[{datetime.now().strftime('%H:%M:%S')}] ✅ Clicked Join Button for {g['group_name']}")
                    await asyncio.sleep(4)
                    
                    # Check for questions
                    if await page.query_selector("div[role='dialog']"):
                        print("Answering entry questions...")
                        await answer_questions(page, g['group_name'], gemini_key)
                        # Submit form using Locators
                        submit_locs = [
                            page.get_by_role("button", name=re.compile(r"^(Submit|Send|ส่ง|ส่งคำตอบ|ส่งคำขอ)$", re.IGNORECASE)),
                            page.locator('[aria-label="Submit"], [aria-label="ส่ง"], [aria-label="ส่งคำตอบ"]')
                        ]
                        for s_loc in submit_locs:
                            if await s_loc.count() > 0:
                                await s_loc.first.click(force=True, timeout=5000)
                                break

                    await update_status(g['id'], 'Pending')
                    print(f"[{datetime.now().strftime('%H:%M:%S')}] 🎉 Successfully requested to join: {g['group_name']}")
                    joined_count += 1
                else:
                    print(f"[{datetime.now().strftime('%H:%M:%S')}] ⚠️ Could not find Join button for {g['group_name']} (Already joined?)")
                    await update_status(g['id'], 'Joined')

            except Exception as e:
                print(f"Error joining {g['group_name']}: {e}")
            
            # Anti-Block delay
            await asyncio.sleep(random.randint(30, 90))

        # --- ตรวจสอบกลุ่มที่ Pending ---
        await check_pending_groups(page)

        await browser.close()
        print(f"[{datetime.now().strftime('%H:%M:%S')}] Finished queued groups and pending checks.")

async def main():
    while True:
        try:
            await run_joiner()
        except Exception as e:
            print(f"[{datetime.now().strftime('%H:%M:%S')}] Error in main loop: {e}")
        
        print(f"[{datetime.now().strftime('%H:%M:%S')}] 💤 Sleeping for 60 minutes before next check...")
        await asyncio.sleep(3600)

if __name__ == "__main__":
    asyncio.run(main())
