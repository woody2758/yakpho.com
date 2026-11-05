import asyncio
import json
import os
import requests
import random
import time
from playwright.async_api import async_playwright
from datetime import datetime

# --- CONFIGURATION ---
BASE_URL = "https://yakpho.com/staff/api/"
API_KEY = "yakpho2026"
HEADLESS = False

def get_time(): return datetime.now().strftime('%H:%M:%S')

async def human_delay(min_s=2, max_s=5):
    await asyncio.sleep(random.uniform(min_s, max_s))

async def human_scroll(page):
    steps = random.randint(2, 4)
    for _ in range(steps):
        await page.evaluate(f"window.scrollBy(0, {random.randint(200, 600)})")
        await human_delay(1, 3)

async def get_bot_settings():
    headers = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"}
    try:
        r = requests.get(f"{BASE_URL}get_fb_settings.php?api_key={API_KEY}", headers=headers, timeout=15)
        return r.json().get('settings', {})
    except Exception as e:
        print(f"[{get_time()}] ⚠️ Error fetching settings: {e}")
        return {}

async def get_target_groups():
    headers = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"}
    try:
        r = requests.get(f"{BASE_URL}get_groups_to_post.php?api_key={API_KEY}", headers=headers, timeout=15)
        res = r.json()
        if res.get('ok'):
            return res.get('groups', [])
    except Exception as e:
        print(f"[{get_time()}] Error fetching groups: {e}")
        try: print(f"[{get_time()}] Raw API Response: {r.text[:500]}")
        except: pass
    return []

async def get_content(strategy, zone):
    headers = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"}
    try:
        url = f"{BASE_URL}get_creative_content.php?api_key={API_KEY}&strategy={strategy}&zone={zone}"
        r = requests.get(url, headers=headers, timeout=15)
        res = r.json()
        if res.get('ok'):
            return res
    except Exception as e:
        print(f"[{get_time()}] Error fetching content: {e}")
    return None

async def mark_posted(group_id, queue_id):
    headers = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"}
    try:
        r = requests.post(f"{BASE_URL}update_group_posted.php?api_key={API_KEY}", headers=headers, json={"id": group_id, "queue_id": queue_id}, timeout=15)
        return r.json().get('ok')
    except: return False

async def mark_failed(group_id):
    headers = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"}
    try:
        r = requests.post(f"{BASE_URL}remove_group_from_queue.php?api_key={API_KEY}", headers=headers, json={"id": group_id}, timeout=15)
        return r.json().get('ok')
    except: return False

async def run_poster():
    settings = await get_bot_settings()
    active_role = settings.get('active_persona', 'worldwide')

    print("\n" + "="*50)
    print(f"[{get_time()}] >>> FB GROUP POSTER ({active_role.upper()}) <<<")
    print("="*50)
    
    db_cookies_json = settings.get('cookies', {}).get(active_role, '')

    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=HEADLESS)
        
        # 🔑 Load Cookies from Cloud
        raw_cookies = None
        if db_cookies_json and len(db_cookies_json) > 50:
            print(f"[{get_time()}] ☁️ Using Cloud Cookies for: {active_role.upper()}")
            try: raw_cookies = json.loads(db_cookies_json)
            except: print(f"[{get_time()}] ⚠️ Invalid JSON in DB for cookies.")
        
        if not raw_cookies:
            local_file = f"fb_auth_{active_role}.json"
            if os.path.exists(local_file):
                print(f"[{get_time()}] 📂 Using Local Fallback: {local_file}")
                with open(local_file, 'r') as f: raw_cookies = json.load(f)
        
        if not raw_cookies:
            print(f"[{get_time()}] ❌ ERROR: No cookies found for {active_role}")
            await browser.close()
            return

        clean_cookies = []
        for c in raw_cookies:
            ss = str(c.get('sameSite', '')).capitalize()
            if ss not in ['Strict', 'Lax', 'None']:
                if 'sameSite' in c: del c['sameSite']
            else: c['sameSite'] = ss
            cd = {
                "name": c["name"], "value": c["value"],
                "domain": c.get("domain", ".facebook.com"),
                "path": c.get("path", "/")
            }
            clean_cookies.append(cd)
        storage_state = {"cookies": clean_cookies, "origins": []}

        context = await browser.new_context(
            storage_state=storage_state,
            user_agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
            viewport={'width': 1366, 'height': 768}
        )
        
        # Disable webdriver flag
        await context.add_init_script("Object.defineProperty(navigator, 'webdriver', {get: () => undefined});")
        page = await context.new_page()

        # ══════════════════════════════════════════════════
        # 🔐 LOGIN GUARD — ดักทุกกรณีที่หลุด Session
        # ══════════════════════════════════════════════════
        print(f"[{get_time()}] Verifying session...")
        await page.goto("https://www.facebook.com/", wait_until="domcontentloaded", timeout=60000)
        await human_delay(3, 5)

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
        if session_ok:
            try:
                has_continue = await page.locator('div[role="button"]:has-text("Continue"), a:has-text("Continue")').count()
                has_another  = await page.locator(':has-text("Use another profile"), :has-text("Create new account")').count()
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
                    session_ok = False
                    session_fail_reason = "No logged-in signals found (no feed/home icon)"
            except: pass

        if not session_ok:
            print(f"[{get_time()}] !!! EXPIRED SESSION: {session_fail_reason} !!!")
            print(f"[{get_time()}] !!! PLEASE UPDATE COOKIES IN DATABASE FOR: {active_role.upper()} !!!")
            await page.screenshot(path="expired_session_debug.png")
            await browser.close()
            return

        # Read Profile Name
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
            print(f"[{get_time()}] ✅ Logged in successfully. Active Profile: {profile_name}")
        except:
            print(f"[{get_time()}] ✅ Logged in successfully. (Profile Name Unknown)")

        groups = await get_target_groups()
        if not groups:
            print(f"[{get_time()}] No groups ready for posting (or all are in cooldown).")
            await browser.close()
            return
            
        print(f"[{get_time()}] Found {len(groups)} groups ready to post.")

        for idx, group in enumerate(groups):
            g_id = group['id']
            q_id = group.get('queue_id')
            g_name = group['group_name']
            g_url = group['group_url']
            g_strategy = group['strategy_marker']
            g_zone = group.get('target_zone', 'Thailand')
            
            print(f"\n[{get_time()}] 🎯 Target [{idx+1}/{len(groups)}]: {g_name}")
            
            try:
                print(f"[{get_time()}] Navigating to {g_url}")
                await page.goto(g_url, wait_until="domcontentloaded", timeout=60000)
                await human_delay(4, 7)
                await human_scroll(page)
                
                # Check for group rules popup or unjoined status
                if "login" in page.url.lower():
                    print(f"[{get_time()}] ❌ Session expired mid-run.")
                    break
                
                # Try to click the "Write something..." area.
                # Facebook DOM changes frequently. We use multiple possible selectors.
                selectors = [
                    'div[role="button"]:has-text("Write something")',
                    'div[role="button"]:has-text("เขียนอะไรสักหน่อย")',
                    'div:text-is("Write something...")',
                    'div:text-is("เขียนอะไรสักหน่อย...")',
                    'span:text-is("Write something...")',
                    'span:text-is("เขียนอะไรสักหน่อย...")',
                    'div[role="button"]:has-text("Create a public post")',
                    'div[role="button"]:has-text("สร้างโพสต์สาธารณะ")',
                    'text=Write something',
                    'text=เขียนอะไรสักหน่อย'
                ]
                
                composer_trigger = None
                for sel in selectors:
                    loc = page.locator(sel).first
                    if await loc.is_visible():
                        composer_trigger = loc
                        break
                
                if not composer_trigger:
                    join_btn = await page.locator('div[role="button"]:has-text("Join Group"), div[role="button"]:has-text("เข้าร่วมกลุ่ม"), span:has-text("Join Group"), span:has-text("เข้าร่วมกลุ่ม")').count()
                    if join_btn > 0:
                        print(f"[{get_time()}] ⚠️ Not a member of this group. Removing from queue & skipping.")
                    else:
                        print(f"[{get_time()}] ⚠️ Cannot find post composer box. Are we blocked from posting or not a member? Removing from queue.")
                        # Debug screenshot
                        await page.screenshot(path=f"poster_composer_not_found_{g_id}.png")
                    
                    # Call API to remove this invalid group from the queue so we don't get stuck on it forever
                    await mark_failed(g_id)
                    continue
                
                print(f"[{get_time()}] Strategy: {g_strategy} -> Fetching Content...")
                
                content_data = await get_content(g_strategy, g_zone)
                if not content_data:
                    print(f"[{get_time()}] ⚠️ Failed to get content. Skipping.")
                    continue
                    
                text_to_post = content_data['content']
                img_rel_paths = content_data['images']
                
                print(f"[{get_time()}] Loaded text ({len(text_to_post)} chars) and {len(img_rel_paths)} images.")
                
                valid_img_paths = []
                import tempfile
                temp_dir = tempfile.gettempdir()
                dl_headers = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"}
                for p in img_rel_paths:
                    # p is something like 'uploads/creative/xxx.jpg'
                    img_url = f"https://yakpho.com/staff/{p}"
                    try:
                        img_r = requests.get(img_url, headers=dl_headers, timeout=15)
                        if img_r.status_code == 200:
                            tmp_path = os.path.join(temp_dir, os.path.basename(p))
                            with open(tmp_path, 'wb') as f:
                                f.write(img_r.content)
                            valid_img_paths.append(tmp_path)
                            print(f"[{get_time()}] Downloaded image: {os.path.basename(p)}")
                        else:
                            print(f"[{get_time()}] Failed to download {img_url} (HTTP {img_r.status_code})")
                    except Exception as e:
                        print(f"[{get_time()}] Error downloading image {p}: {e}")

                if not valid_img_paths:
                    print(f"[{get_time()}] ⚠️ No valid physical images found. Stopping to prevent error.")
                    continue
                
                print(f"[{get_time()}] Opening composer...")
                await composer_trigger.click(force=True)
                await human_delay(3, 5)
                
                # After clicking, the modal opens. We need to find the text input.
                # It must be inside the dialog modal! Otherwise it might grab the search bar.
                # Use :visible pseudo-class to ignore hidden dialogs (like notifications modal)
                dialog = page.locator('div[role="dialog"]:visible').first
                await dialog.wait_for(state="visible", timeout=10000)
                
                textbox = dialog.locator('div[role="textbox"][contenteditable="true"]:visible').first
                await textbox.wait_for(state="visible", timeout=10000)
                
                print(f"[{get_time()}] Typing content...")
                # Try focusing the element directly, which is more reliable than a forced click for React/Lexical editors
                await textbox.focus()
                await human_delay(1, 2)
                # Use insert_text which pastes the text (faster and more reliable than typing letter by letter for emojis/thai)
                await page.keyboard.insert_text(text_to_post)
                await human_delay(2, 4)
                
                # Verify if text was entered, if not try fill() as a fallback
                content_length = await textbox.evaluate("el => el.innerText.length")
                if content_length < 5:
                    print(f"[{get_time()}] ⚠️ Text didn't register. Trying alternative fill method...")
                    await textbox.fill(text_to_post)
                    await human_delay(2, 3)
                
                # Upload images
                print(f"[{get_time()}] Uploading {len(valid_img_paths)} images...")
                # Facebook's file input is usually hidden. We look for one that supports multiple files first.
                file_input_multiple = page.locator('input[type="file"][multiple][accept*="image"]')
                file_input_any = page.locator('input[type="file"][accept*="image"]')
                
                if await file_input_multiple.count() > 0:
                    await file_input_multiple.first.set_input_files(valid_img_paths)
                elif await file_input_any.count() > 0:
                    # If we only find a single-file input, we might need to upload one by one
                    # But Facebook's composer usually has a multiple input.
                    # Let's try to pass just the first one if we can't do multiple, or force it.
                    # Wait, if we can't find multiple, maybe we need to click the 'Add Photo/Video' button first!
                    add_photo_btn = page.locator('div[aria-label="Photo/video"], div[aria-label="รูปภาพ/วิดีโอ"]').first
                    if await add_photo_btn.is_visible():
                        await add_photo_btn.click(force=True)
                        await human_delay(1, 2)
                        
                    # Try finding the multiple input again after clicking the button
                    file_input_multiple_retry = page.locator('input[type="file"][multiple][accept*="image"]')
                    if await file_input_multiple_retry.count() > 0:
                        await file_input_multiple_retry.first.set_input_files(valid_img_paths)
                    else:
                        print(f"[{get_time()}] ⚠️ Could not find a multiple file input. Falling back to single image.")
                        await file_input_any.first.set_input_files(valid_img_paths[0])
                else:
                    print(f"[{get_time()}] ⚠️ Cannot find file input. Aborting post.")
                    continue
                
                # Wait for images to render (they upload via ajax)
                print(f"[{get_time()}] Waiting for image uploads to complete...")
                await human_delay(10, 15)
                
                # Find the Post / โพสต์ button
                # Usually it's an aria-label="Post" or has text "Post" and is a button
                post_btn = page.locator('div[role="button"][aria-label="Post"], div[role="button"][aria-label="โพสต์"]').first
                if not await post_btn.is_visible():
                    post_btn = page.locator('div[aria-label="Post"], div[aria-label="โพสต์"]').first
                    
                if await post_btn.is_visible() and not await post_btn.is_disabled():
                    print(f"[{get_time()}] Clicking POST...")
                    await post_btn.click(force=True)
                    
                    # Wait for the Post button itself to disappear (modal closed)
                    await post_btn.wait_for(state="hidden", timeout=30000)
                    print(f"[{get_time()}] ✅ Post submitted successfully!")
                    
                    # Record success in db
                    await mark_posted(g_id, q_id)
                    
                    # Random delay 5-15 mins
                    delay_mins = random.randint(5, 15)
                    delay_secs = random.randint(0, 59)
                    total_delay = (delay_mins * 60) + delay_secs
                    print(f"[{get_time()}] 💤 Human delay active: Resting for {delay_mins} mins {delay_secs} secs before next post...")
                    await asyncio.sleep(total_delay)
                else:
                    print(f"[{get_time()}] ⚠️ Post button not visible or disabled.")
                
            except Exception as e:
                print(f"[{get_time()}] ⚠️ Error during posting: {e}")
                try:
                    await page.screenshot(path=f"poster_error_{g_id}.png")
                    print(f"[{get_time()}] 📸 Saved error screenshot to poster_error_{g_id}.png")
                except: pass
                
        await browser.close()
        print(f"[{get_time()}] Poster Cycle Complete.")

if __name__ == "__main__":
    asyncio.run(run_poster())
