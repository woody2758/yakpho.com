import asyncio
import json
import os
import re
import requests
import random
import urllib.parse
from bs4 import BeautifulSoup
from playwright.async_api import async_playwright
from datetime import datetime

# --- CONFIGURATION (V6.0 Deep Scraper) ---
BASE_URL = "https://yakpho.com/staff/api/"
API_KEY = "yakpho2026"
HEADLESS = True

def get_time(): return datetime.now().strftime('%H:%M:%S')

async def human_delay(min_s=3, max_s=8):
    await asyncio.sleep(random.uniform(min_s, max_s))

async def get_pending_tasks(limit=5):
    try:
        r = requests.get(f"{BASE_URL}get_pending_deep_scan.php?api_key={API_KEY}&limit={limit}", timeout=10)
        data = r.json()
        if data.get("ok"): return data["data"]
    except Exception as e:
        print(f"[{get_time()}] API Error fetching tasks: {e}")
    return []

async def save_deep_scan(payload):
    try:
        r = requests.post(f"{BASE_URL}save_deep_info.php?api_key={API_KEY}", json=payload, timeout=10)
        data = r.json()
        print(f"[{get_time()}] API Save Status: {data.get('msg')}")
    except Exception as e:
        print(f"[{get_time()}] Save Error: {e}")

async def run_deep_scraper():
    tasks = await get_pending_tasks(5)
    if not tasks:
        print(f"[{get_time()}] No pending deep scans found in queue. Chilling...")
        return
        
    print(f"[{get_time()}] Found {len(tasks)} pages to Deep Scan.")

    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=HEADLESS)
        storage_path = "fb_auth_thai.json"
        
        storage_state = None
        if os.path.exists(storage_path):
            with open(storage_path, "r", encoding="utf-8") as f:
                c_data = json.load(f)
                if isinstance(c_data, list):
                    valid_cookies = [{"name": c["name"], "value": c["value"], "domain": c.get("domain", ".facebook.com"), "path": c.get("path", "/")} for c in c_data if "name" in c and "value" in c]
                    storage_state = {"cookies": valid_cookies, "origins": []}
                elif isinstance(c_data, dict) and "cookies" in c_data:
                    storage_state = c_data
                    
        context = await browser.new_context(
            storage_state=storage_state, 
            user_agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
            viewport={'width': 1920, 'height': 1080}
        )
        page = await context.new_page()

        for idx, task in enumerate(tasks):
            url = task['page_url']
            m_url = url.replace("www.facebook.com", "mbasic.facebook.com").replace("m.facebook.com", "mbasic.facebook.com")
            if "mbasic.facebook.com" not in m_url:
                m_url = f"https://mbasic.facebook.com/{url.split('facebook.com/')[-1]}"

            print(f"[{get_time()}] [{idx+1}/{len(tasks)}] Diving into: {task['page_name']} ({m_url})")
            
            payload = {
                "id": task['id'],
                "phone": None,
                "email": None,
                "website": None
            }
            
            try:
                await page.goto(m_url, wait_until="domcontentloaded", timeout=60000)
                await human_delay(3, 6)
                
                # Check session loss
                title = await page.title()
                if "login" in page.url.lower() or "เข้าสู่ระบบ" in title:
                    print(f"[{get_time()}] !!! EXPIRED SESSION. STOPPING DEEP SCRAPER !!!")
                    break

                # Extract info from M-Basic page
                content = await page.content()
                try: clean_text = await page.locator("body").inner_text()
                except: clean_text = content
                
                p_match = re.search(r'0[2-9]\d{1,2}-?\d{3}-?\d{3,4}|\+66\s?\d{1,2}-?\d{3}-?\d{3,4}', content)
                if p_match: payload["phone"] = p_match.group(0).strip()
                
                e_match = re.search(r'[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}', content)
                if e_match: payload["email"] = e_match.group(0).strip()
                
                links = await page.query_selector_all("a[href*='http']")
                for link in links:
                    href = await link.get_attribute("href")
                    if not href: continue
                    if all(x not in href for x in ["facebook.com", "messenger.com", "instagram.com", "whatsapp.com", "l.facebook.com"]):
                        clean_url = href.split('?fbclid=')[0].split('&fbclid=')[0]
                        payload["website"] = clean_url
                        break
                    elif "l.facebook.com" in href:
                        match = re.search(r'u=(.*?)&', href)
                        if match:
                            website_url = urllib.parse.unquote(match.group(1))
                            if "bing.com/maps" not in website_url.lower() and "google.com/maps" not in website_url.lower():
                                clean_url = website_url.split('?fbclid=')[0].split('&fbclid=')[0]
                                payload["website"] = clean_url
                                break
                                
                # Follow System Check
                follow_btn = await page.query_selector("a[href*='/a/subscribe.php']")
                if follow_btn:
                    print(f"[{get_time()}]    Found Follow button! Clicking...")
                    await follow_btn.click()
                    await human_delay(2, 4)
                
                print(f"[{get_time()}]    Extracted: Phone: {payload['phone']} | Email: {payload['email']}")
                await save_deep_scan(payload)
                
            except Exception as e:
                print(f"[{get_time()}] Error on details: {e}")
                
            await human_delay(15, 25)

        await browser.close()
        print(f"[{get_time()}] Deep Scraper Cycle Finish.")

if __name__ == "__main__":
    asyncio.run(run_deep_scraper())
