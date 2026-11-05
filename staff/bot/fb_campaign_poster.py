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
        r = requests.get(f"{BASE_URL}get_campaign_queue.php?api_key={API_KEY}", headers=headers, timeout=15)
        res = r.json()
        if res.get('ok'):
            return res.get('groups', []), None
        else:
            return [], res.get('next_run')
    except Exception as e:
        print(f"[{get_time()}] Error fetching groups: {e}")
    return [], None

async def get_content(content_id):
    headers = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"}
    try:
        url = f"{BASE_URL}get_campaign_content.php?api_key={API_KEY}&content_id={content_id}"
        r = requests.get(url, headers=headers, timeout=15)
        res = r.json()
        if res.get('ok'):
            return res
    except Exception as e:
        print(f"[{get_time()}] Error fetching content: {e}")
    return None

async def mark_posted(queue_id):
    headers = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"}
    try:
        r = requests.post(f"{BASE_URL}update_campaign_posted.php?api_key={API_KEY}", headers=headers, json={"queue_id": queue_id, "status": "success"}, timeout=15)
        return r.json().get('ok')
    except: return False

async def mark_failed(queue_id, msg=""):
    headers = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"}
    try:
        r = requests.post(f"{BASE_URL}update_campaign_posted.php?api_key={API_KEY}", headers=headers, json={"queue_id": queue_id, "status": "failed", "error_msg": msg}, timeout=15)
        return r.json().get('ok')
    except: return False

async def run_poster():
    settings = await get_bot_settings()
    active_role = settings.get('active_persona', 'worldwide')

    print("\n" + "="*50)
    print(f"[{get_time()}] >>> FB CAMPAIGN POSTER ({active_role.upper()}) <<<")
    print("="*50)
    
    db_cookies_json = settings.get('cookies', {}).get(active_role, '')

    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=HEADLESS)
        
        # 🔑 Load Cookies from Cloud
        raw_cookies = None
        if db_cookies_json and len(db_cookies_json) > 50:
            print(f"[{get_time()}] ☁️ Using Cloud Cookies for: {active_role.upper()}")
            try: raw_cookies = json.loads(db_cookies_json)
            except: pass
        
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
        
        await context.add_init_script("Object.defineProperty(navigator, 'webdriver', {get: () => undefined});")
        page = await context.new_page()

        print(f"[{get_time()}] Verifying session...")
        await page.goto("https://www.facebook.com/", wait_until="domcontentloaded", timeout=60000)
        await human_delay(3, 5)

        session_ok = True
        if "login" in page.url.lower(): session_ok = False
        
        if session_ok:
            try:
                logged_in_signal = await page.locator('[aria-label="Home"], [aria-label="หน้าแรก"], div[role="feed"]').count()
                if logged_in_signal == 0: session_ok = False
            except: pass

        if not session_ok:
            print(f"[{get_time()}] !!! EXPIRED SESSION !!!")
            await browser.close()
            return

        groups, next_run = await get_target_groups()
        if not groups:
            if next_run:
                # Calculate time diff
                from datetime import datetime
                try:
                    next_time = datetime.strptime(next_run, '%Y-%m-%d %H:%M:%S')
                    now = datetime.now()
                    diff = next_time - now
                    if diff.total_seconds() > 0:
                        days = diff.days
                        hours, rem = divmod(diff.seconds, 3600)
                        minutes, seconds = divmod(rem, 60)
                        
                        time_str = ""
                        if days > 0: time_str += f"{days} วัน "
                        if hours > 0: time_str += f"{hours} ชั่วโมง "
                        if minutes > 0: time_str += f"{minutes} นาที"
                        
                        print(f"[{get_time()}] ⏳ สถานะ: รอเวลาโพสต์... คิวถัดไปจะเริ่มในอีก {time_str} (เวลา {next_run})")
                    else:
                        print(f"[{get_time()}] ⏳ สถานะ: คิวพร้อมรันแล้ว (เวลา {next_run}) แต่ยังไม่ถูกดึงมาทำงาน")
                except:
                    print(f"[{get_time()}] ⏳ สถานะ: รอเวลาโพสต์... คิวถัดไปคือเวลา {next_run}")
            else:
                print(f"[{get_time()}] No groups ready for posting (or all are in cooldown).")
            await browser.close()
            return
            
        print(f"[{get_time()}] Found {len(groups)} campaign jobs ready to post.")

        for idx, group in enumerate(groups):
            q_id = group['queue_id']
            c_id = group['content_id']
            g_name = group['group_name']
            g_url = group['group_url']
            
            print(f"\n[{get_time()}] 🎯 Target [{idx+1}/{len(groups)}]: {g_name}")
            
            try:
                print(f"[{get_time()}] Navigating to {g_url}")
                await page.goto(g_url, wait_until="domcontentloaded", timeout=60000)
                await human_delay(4, 7)
                await human_scroll(page)
                
                selectors = [
                    'div[role="button"]:has-text("Write something")',
                    'div[role="button"]:has-text("เขียนอะไรสักหน่อย")',
                    'div:text-is("Write something...")',
                    'div:text-is("เขียนอะไรสักหน่อย...")'
                ]
                
                composer_trigger = None
                for sel in selectors:
                    loc = page.locator(sel).first
                    if await loc.is_visible():
                        composer_trigger = loc
                        break
                
                if not composer_trigger:
                    print(f"[{get_time()}] ⚠️ Cannot find post composer box. Removing from queue.")
                    await mark_failed(q_id, "Composer not found")
                    continue
                
                print(f"[{get_time()}] Content ID: {c_id} -> Fetching Content...")
                content_data = await get_content(c_id)
                if not content_data:
                    print(f"[{get_time()}] ⚠️ Failed to get content. Skipping.")
                    continue
                    
                text_to_post = content_data['content']
                img_rel_paths = content_data.get('images', [])
                
                valid_img_paths = []
                import tempfile
                temp_dir = tempfile.gettempdir()
                req_headers = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36"}
                for p in img_rel_paths:
                    # Remove leading slash if any
                    p_clean = p.lstrip('/')
                    img_url = f"https://yakpho.com/staff/{p_clean}"
                    try:
                        print(f"[{get_time()}] Downloading image: {img_url}")
                        img_r = requests.get(img_url, headers=req_headers, timeout=15)
                        if img_r.status_code == 200:
                            tmp_path = os.path.join(temp_dir, os.path.basename(p_clean))
                            with open(tmp_path, 'wb') as f: f.write(img_r.content)
                            valid_img_paths.append(tmp_path)
                            print(f"[{get_time()}] ✅ Downloaded: {tmp_path}")
                        else:
                            print(f"[{get_time()}] ❌ Failed to download {img_url} (HTTP {img_r.status_code})")
                    except Exception as e:
                        print(f"[{get_time()}] ❌ Error downloading {img_url}: {e}")

                print(f"[{get_time()}] Opening composer...")
                await composer_trigger.click(force=True)
                await human_delay(3, 5)
                
                dialog = page.locator('div[role="dialog"]:visible').first
                await dialog.wait_for(state="visible", timeout=10000)
                
                textbox = dialog.locator('div[role="textbox"][contenteditable="true"]:visible').first
                await textbox.wait_for(state="visible", timeout=10000)
                
                if valid_img_paths:
                    print(f"[{get_time()}] Uploading {len(valid_img_paths)} media files first...")
                    
                    # Try to click the "Photo/video" button and intercept the OS file dialog
                    add_photo_btn = page.locator('div[aria-label="Photo/video"], div[aria-label="รูปภาพ/วิดีโอ"], div[aria-label="Photo/Video"]').first
                    
                    file_uploaded = False
                    if await add_photo_btn.is_visible():
                        try:
                            # Intercept the file dialog so it doesn't show up on screen
                            async with page.expect_file_chooser(timeout=3000) as fc_info:
                                await add_photo_btn.click(force=True)
                            file_chooser = await fc_info.value
                            await file_chooser.set_files(valid_img_paths)
                            file_uploaded = True
                            print(f"[{get_time()}] ✅ Uploaded via intercepted file chooser")
                            await human_delay(1, 2)
                        except Exception as e:
                            # Timeout means the button didn't open the OS dialog (it might have opened an internal FB panel)
                            print(f"[{get_time()}] No OS dialog opened by button, proceeding to fallback...")
                            
                    if not file_uploaded:
                        # Fallback: Find the correct file input directly
                        file_input = page.locator('input[type="file"][multiple]')
                        
                        if await file_input.count() > 0:
                            await file_input.first.set_input_files(valid_img_paths)
                        else:
                            # Fallback to the first file input
                            file_input_any = page.locator('input[type="file"]')
                            if await file_input_any.count() > 0:
                                if len(valid_img_paths) == 1:
                                    await file_input_any.first.set_input_files(valid_img_paths)
                                else:
                                    try:
                                        await file_input_any.first.set_input_files(valid_img_paths)
                                    except Exception as e:
                                        print(f"[{get_time()}] First input failed, trying another one... {e}")
                                        if await file_input_any.count() > 1:
                                            await file_input_any.nth(1).set_input_files(valid_img_paths)
                    
                    print(f"[{get_time()}] Waiting for image uploads to complete...")
                    await human_delay(8, 12)

                print(f"[{get_time()}] Typing content...")
                await textbox.focus()
                await human_delay(1, 2)
                await page.keyboard.insert_text(text_to_post)
                await human_delay(2, 4)
                
                post_btn = page.locator('div[role="button"][aria-label="Post"], div[role="button"][aria-label="โพสต์"], div[aria-label="Post"], div[aria-label="โพสต์"]').first
                if await post_btn.is_visible() and not await post_btn.is_disabled():
                    print(f"[{get_time()}] Clicking POST...")
                    await post_btn.click(force=True)
                    
                    try:
                        await post_btn.wait_for(state="hidden", timeout=20000)
                        print(f"[{get_time()}] ✅ Post submitted successfully!")
                    except Exception:
                        print(f"[{get_time()}] ⚠️ Post button didn't hide cleanly. Checking if it's pending admin approval...")
                        # We still consider it a success because the click went through, it's just stuck on a popup or loading
                        pass
                    
                    await mark_posted(q_id)
                    
                    delay_mins = random.randint(5, 15)
                    total_delay = (delay_mins * 60) + random.randint(0, 59)
                    
                    from datetime import datetime, timedelta
                    resume_time = (datetime.now() + timedelta(seconds=total_delay)).strftime('%H:%M:%S')
                    
                    print(f"[{get_time()}] 💤 Resting for {delay_mins} mins... (โพสต์กลุ่มถัดไปเวลาประมาณ {resume_time})")
                    await asyncio.sleep(total_delay)
                else:
                    print(f"[{get_time()}] ⚠️ Post button not visible or disabled.")
                    await mark_failed(q_id, "Post button disabled")
                
            except Exception as e:
                print(f"[{get_time()}] ⚠️ Error during posting: {e}")
                await mark_failed(q_id, str(e))
                
        await browser.close()
        print(f"[{get_time()}] Poster Cycle Complete.")

if __name__ == "__main__":
    asyncio.run(run_poster())
