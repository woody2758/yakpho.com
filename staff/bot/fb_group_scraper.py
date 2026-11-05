import asyncio
import json
import os
import re
import requests
import random
import time
import builtins
from datetime import datetime
import google.generativeai as genai
import urllib3
from playwright.async_api import async_playwright

original_print = builtins.print
def timestamped_print(*args, **kwargs):
    kwargs['flush'] = True
    t = datetime.now().strftime("%H:%M:%S")
    text = " ".join(str(a) for a in args)
    if text.startswith("\n"):
        original_print(f"\n[{t}] {text[1:]}", **kwargs)
    else:
        original_print(f"[{t}] {text}", **kwargs)
builtins.print = timestamped_print

# ปิด Warning SSL
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

# --- CONFIGURATION ---
BASE_URL = "https://yakpho.com/staff/api/"
API_KEY = "yakpho2026"
HEADLESS = True 
HEADERS = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"}

def get_settings():
    url = f"{BASE_URL}get_fb_settings.php?api_key={API_KEY}"
    try:
        response = requests.get(url, headers=HEADERS, verify=False, timeout=15)
        return response.json().get('settings')
    except: return None

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

def analyze_group_fallback(group_name):
    name_lower = group_name.lower()
    
    # 0. Blacklist rules (กรองขยะที่หลุดรอดมา)
    if re.search(r'(บุฟเฟ่ต์|โรค|สุขภาพดี|รถหัวลาก|เกม|หนัง|ดูดวง|หวย|ฟุตบอล|รถมือสอง|เงินกู้|พนัน|บาคาร่า|สล็อต|เว็บตรง|คลิปหลุด|18\+|หาคู่|อาหาร|สูตรอาหาร|ท่องเที่ยว|เที่ยว|ทัวร์|การเมือง|ช้อปปี้|ลาซาด้า|shopee|lazada|แฟนคลับ|fanclub|แมนยู|ลิเวอร์พูล|แมนเชส|ปฏิบัติธรรม|วัดไทย|buddhist|หาบ้าน|เช่าบ้าน|บ้านเช่า|ให้เช่า|หาห้องพัก|อสังหา|หางาน|หาคนทำงาน)', name_lower) and not re.search(r'(นวด|สปา|massage|spa|รับหิ้ว|ฝากหิ้ว|ตลาดนัด|ซื้อขาย|สินค้าไทย|คนไทยใน|ต่างประเทศ)', name_lower):
        return "Blacklist", "Fallback: เจอคีย์เวิร์ดขยะ/ไม่เกี่ยวข้อง"

    # 1. Hard rules (นวด, สปา, หางานนวด, เซ้งร้านนวด, ตลาดนัดนวด)
    if re.search(r'(นวด|สปา|massage|spa|หามือดี|พนักงานนวด|ร้านนวด|เซ้งร้าน|เตียงนวด|อุปกรณ์สปา)', name_lower):
        return "Hard", "Fallback: เน้นกลุ่มนวด/สปาโดยตรง"
        
    # 2. Observe rules (อื่นๆ ที่อาจจะเกี่ยวข้อง)
    # ตัดพวกกลุ่มคนไทยทั่วไปออกไปแล้ว ให้ตกเป็น Observe หรือถ้าไม่อยากได้เลยเดี๋ยวค่อยปัดตก
    return "Observe", "Fallback: ไม่พบคำว่านวด/สปา"

def analyze_group_ai(model, group_name):
    if not model: 
        return analyze_group_fallback(group_name)
    try:
        prompt = f"""
You are an AI filtering Facebook groups for a B2B Spa business targeting Thai expats/workers abroad.
Group Name: "{group_name}"

Rules:
1. "Hard" (Green): Strictly Thai massage shops, spa groups, massage job boards, or buying/selling spa products abroad (e.g., ร้านนวดไทยในซิดนีย์, หางานนวดต่างประเทศ, เซ้งร้านนวด, ตลาดนัดนวดสปา).
2. "Soft" (Yellow): General massage or spa groups where selling products might be allowed.
3. "Observe" (Red): Groups that are strictly located in Thailand but STILL related to spa/massage (e.g., ร้านนวดเชียงใหม่).
4. "Blacklist": General Thai communities abroad (e.g., สมาคมคนไทยในอเมริกา, คนไทยในยุโรป, แม่บ้านต่างแดน). We DO NOT want general communities anymore.
5. "Blacklist": Completely unrelated groups (e.g., Food, Buffets, Diseases, Vehicles, Gaming, general unrelated news) or Groups for foreigners in Thailand.
6. "Blacklist": Groups about finding houses (หาบ้าน, เช่าบ้าน), real estate (อสังหาฯ), or general jobs (หางาน), EXCEPT if it specifically mentions massage jobs.

Respond ONLY with valid JSON exactly like this: {{"marker": "Hard|Soft|Observe|Blacklist", "reason": "เหตุผลสั้นๆ ภาษาไทย"}}
"""
        response = model.generate_content(prompt)
        text = response.text.strip()
        if text.startswith("```json"): text = text[7:]
        if text.endswith("```"): text = text[:-3]
        data = json.loads(text.strip())
        return data.get('marker', 'Observe'), data.get('reason', 'AI V3.6')
    except Exception as e: 
        err_msg = str(e).lower()
        if "quota" in err_msg or "429" in err_msg or "resourceexhausted" in err_msg:
            print("    ⚠️ Gemini API Quota Exceeded. Switching to Regex Fallback.")
        else:
            print(f"    ⚠️ Gemini Error ({str(e)[:30]}). Switching to Regex Fallback.")
        return analyze_group_fallback(group_name)

def save_groups(groups):
    url = f"{BASE_URL}save_fb_group.php?api_key={API_KEY}"
    try: 
        res = requests.post(url, json=groups, headers=HEADERS, verify=False, timeout=30)
        print(f"    💾 ผลการบันทึก: {res.text}")
    except Exception as e: 
        print(f"    ❌ Error saving groups: {e}")

async def scrape_facebook_groups():
    print("\n" + "="*50)
    print(" >>> ANTIGRAVITY SCRAPER (V3.6 - CLOUD COOKIE) <<<")
    print("="*50)
    
    settings = get_settings()
    if not settings: return
    
    # 🎭 1. Persona Management (ดึงจากฐานข้อมูลแล้วครับ)
    active_role = settings.get('active_persona', 'worldwide')
    
    personas = {
        "worldwide": { "name": "Worldwide (YakPho Aroma)", "page": "YakPho.Aroma" },
        "domestic":  { "name": "Domestic (YakPho Official)", "page": "YakPhoOfficial" },
        "esther":    { "name": "Esther Aroma Official", "page": "EstherAromaOfficial" }
    }
    
    p = personas.get(active_role, personas['worldwide'])
    print(f"🎭 Active Role from DB: {active_role.upper()} ({p['name']})")
    
    if settings.get('enable_gemini', True):
        model = init_ai_stable(settings.get('gemini_api_key'))
        if model:
            print("    🧠 AI Engine: GEMINI ENABLED")
    else:
        model = None
        print("    ⚙️ AI Engine: DISABLED (Using Regex Fallback System)")
        
    # 🔗 Generate Keyword Combinations
    base_keywords = settings.get('keywords', [])
    countries = settings.get('countries', [])
    expat_cities = settings.get('expat_cities', [])
    history = set(settings.get('history', []))
    
    all_combinations = []
    if countries or expat_cities:
        for kw in base_keywords:
            kw = str(kw).strip()
            for c in countries:
                all_combinations.append(f"{kw} {str(c).strip()}")
            for city in expat_cities:
                all_combinations.append(f"{kw} {str(city).strip()}")
    else:
        all_combinations = base_keywords
        
    # 🧹 Filter out keywords already scraped
    keywords = [k for k in all_combinations if k not in history]
    
    existing_urls = set(settings.get('existing_urls', []))

    async with async_playwright() as p_sys:
        browser = await p_sys.chromium.launch(headless=HEADLESS)
        context = await browser.new_context(user_agent=HEADERS['User-Agent'])
        
        # 🔑 2. Load Cookies (API First -> Local Fallback)
        db_cookies_json = settings.get('cookies', {}).get(active_role, '')
        
        raw_cookies = None
        if db_cookies_json and len(db_cookies_json) > 50:
            print(f"☁️ Using Cloud Cookies from Database...")
            try: raw_cookies = json.loads(db_cookies_json)
            except: print("⚠️ Warning: Invalid JSON in DB. Falling back to local file.")
            
        if not raw_cookies:
            # Fallback to Local File
            local_file = f"fb_auth_{active_role}.json"
            if os.path.exists(local_file):
                print(f"📂 Using Local File: {local_file}")
                with open(local_file, 'r') as f: raw_cookies = json.load(f)
        
        if raw_cookies:
            clean_cookies = []
            for c in raw_cookies:
                ss = str(c.get('sameSite', '')).capitalize()
                if ss not in ['Strict', 'Lax', 'None']:
                    if 'sameSite' in c: del c['sameSite']
                else: c['sameSite'] = ss
                for k in ['id', 'storeId', 'hostOnly', 'session']:
                    if k in c: del c[k]
                clean_cookies.append(c)
            await context.add_cookies(clean_cookies)
        else:
            print(f"❌ ERROR: No cookies found in DB or local for '{active_role}'")
            await browser.close()
            return
        
        page = await context.new_page()

        # 🛡️ 3. Identity Guard Check
        print(f"🛡️ Verifying Account: {p['name']}...")
        try:
            await page.goto(f"https://www.facebook.com/{p['page']}", wait_until="domcontentloaded", timeout=45000)
            user_title = await page.title()
            
            if "Log in" in user_title or "เข้าสู่ระบบ" in user_title:
                print(f"❌ ERROR: Session invalid for {p['name']}. Please update cookies in Admin.")
                await browser.close()
                return
            
            print(f"✅ Identity Verified. Role: {active_role.upper()}")
            
        except Exception as e:
            print(f"⚠️ Identity Check Warning: {e}")

        # 4. Search Logic
        all_found = []
        for kw in keywords:
            print(f"\n🔍 Searching [{active_role}]: {kw}")
            url = f"https://www.facebook.com/search/groups/?q={requests.utils.quote(kw)}"
            try:
                await page.goto(url, wait_until="domcontentloaded", timeout=60000)
                await asyncio.sleep(5)
                
                # Scroll
                for _ in range(settings.get('scroll_depth', 3)):
                    await page.mouse.wheel(0, 2000)
                    await asyncio.sleep(2)
                
                group_elements = await page.query_selector_all('div[role="article"]')
                
                # --- FALLBACK: ถ้า Facebook เปลี่ยน DOM (ไม่ใช้ role="article" แล้ว) ---
                if len(group_elements) == 0:
                    print("    ⚠️ ไม่พบ `role=\"article\"` กำลังใช้ระบบค้นหาสำรอง (Fallback Scanner)...")
                    # ดึงทุกลิงก์ที่ชี้ไปหา /groups/
                    raw_links = await page.query_selector_all('a[href*="/groups/"]')
                    group_elements = []
                    seen_urls_fallback = set()
                    
                    for lnk in raw_links:
                        try:
                            href = await lnk.get_attribute('href')
                            if not href: continue
                            clean_href = href.split('?')[0].rstrip('/')
                            
                            # ถ้าเป็นลิงก์กลุ่มจริงๆ (ไม่ใช่ groups/search/...)
                            if re.search(r'/groups/\d+', clean_href) or re.search(r'/groups/[a-zA-Z0-9\.\-_]+', clean_href):
                                if 'search' in clean_href: continue
                                
                                if clean_href not in seen_urls_fallback:
                                    seen_urls_fallback.add(clean_href)
                                    # ปีน DOM ขึ้นไปหา Parent ที่คลุมทั้งก้อน
                                    parent_handle = await lnk.evaluate_handle('(el) => { let p = el.parentElement; while(p && p.innerText && p.innerText.length < 50 && p.parentElement) { p = p.parentElement; } return p; }')
                                    if parent_handle:
                                        group_elements.append(parent_handle)
                        except: pass
                # -------------------------------------------------------------------

                batch = []
                seen_in_this_kw = set()
                for article in group_elements:
                    try:
                        links_in_article = await article.query_selector_all('a[href*="/groups/"]')
                        if not links_in_article: continue
                        
                        name = ""
                        link = ""
                        for lnk in links_in_article:
                            txt = await lnk.inner_text()
                            if txt and len(txt.strip()) > 2:
                                name = txt.strip()
                                link = (await lnk.get_attribute('href')).split('?')[0].rstrip('/')
                                break
                                
                        if not name: continue
                        
                        if link in seen_in_this_kw: continue
                        seen_in_this_kw.add(link)
                        
                        if not re.search(r'[ก-๙]|thai|siam', name, re.IGNORECASE): continue

                        if link in existing_urls:
                            print(f"    ⏩ ข้าม: กลุ่มนี้มีในระบบแล้ว")
                            continue
                        
                        print(f"    🔎 เจอเป้าหมาย: {name[:40]}...")
                        
                        name_lower = name.lower()
                        # กรองเบื้องต้นสำหรับกลุ่มต่างชาติในไทยที่ชัดเจน
                        if re.search(r'(in thailand|thailand expat|singles in thailand|foreigners in thailand|farang|we love thailand)', name_lower) and not re.search(r'[ก-๙]', name):
                            print(f"    ❌ ข้าม: (กลุ่มต่างชาติในไทย)")
                            continue
                            
                        # กรองกลุ่มหาบ้าน หางาน อสังหา (ยกเว้นงานนวด)
                        if re.search(r'(หาบ้าน|เช่าบ้าน|บ้านเช่า|ให้เช่า|หาห้องพัก|อสังหา|หางาน|หาคนทำงาน)', name) and not re.search(r'(นวด|สปา|massage|spa)', name, re.IGNORECASE):
                            print(f"    ❌ ข้าม: (กลุ่มหาบ้าน/อสังหา/หางานทั่วไป)")
                            continue

                        # กรองกลุ่มขยะ/ไม่เกี่ยวข้องชัดเจน (บุฟเฟ่ต์, โรค, รถ, หวย, เกมส์, ท่องเที่ยว, กีฬา, ศาสนา)
                        if re.search(r'(บุฟเฟ่ต์|โรค|สุขภาพดี|รถหัวลาก|เกม|หนัง|ดูดวง|หวย|ฟุตบอล|รถมือสอง|เงินกู้|พนัน|บาคาร่า|สล็อต|เว็บตรง|คลิปหลุด|18\+|หาคู่|อาหาร|สูตรอาหาร|ท่องเที่ยว|เที่ยว|ทัวร์|การเมือง|ช้อปปี้|ลาซาด้า|shopee|lazada|แฟนคลับ|fanclub|แมนยู|ลิเวอร์พูล|แมนเชส|ปฏิบัติธรรม|วัดไทย|buddhist)', name_lower) and not re.search(r'(นวด|สปา|massage|spa|รับหิ้ว|ฝากหิ้ว|ตลาดนัด|ซื้อขาย|สินค้าไทย|คนไทยใน|ต่างประเทศ)', name, re.IGNORECASE):
                            print(f"    ❌ ข้าม: (กลุ่มไม่เกี่ยวข้อง/ขยะ)")
                            continue

                        # ดึงข้อมูลเพิ่มเติม (Privacy & Members)
                        article_text = await article.inner_text()
                        privacy = 'Private' if 'ส่วนตัว' in article_text or 'Private' in article_text else 'Public'
                        
                        member_count = 0
                        m = re.search(r'([\d\.,]+)\s*(พัน|หมื่น|แสน|ล้าน|K|M)?\s*(คน|members)', article_text, re.IGNORECASE)
                        if m:
                            num = float(m.group(1).replace(',', ''))
                            multiplier = m.group(2)
                            if multiplier == 'พัน' or (multiplier and multiplier.upper() == 'K'): num *= 1000
                            elif multiplier == 'หมื่น': num *= 10000
                            elif multiplier == 'แสน': num *= 100000
                            elif multiplier == 'ล้าน' or (multiplier and multiplier.upper() == 'M'): num *= 1000000
                            member_count = int(num)

                        marker, reason = analyze_group_ai(model, name)
                        print(f"    🤖 AI วิเคราะห์: [{marker}] เหตุผล: {reason} | {privacy} | {member_count} คน")
                        
                        group_data = {
                            "name": name, "url": link, "tags": f"Scraped|{active_role}", 
                            "marker": marker, "ai_reason": reason, "location": kw,
                            "privacy": privacy, "member_count": member_count
                        }
                        
                        if not any(item['url'] == link for item in all_found):
                            all_found.append(group_data)
                            if marker == 'Blacklist':
                                print(f"    ❌ ปัดตก (Blacklist): ไม่ตรงกลุ่มเป้าหมาย (ไม่บันทึกลงระบบ)")
                            else:
                                save_groups([group_data])
                    except Exception as e:
                        print(f"    ⚠️ Error processing group: {e}")
                        continue

                # บันทึกสถานะว่าคำนี้ถูกค้นหาเรียบร้อยแล้ว
                try:
                    mark_url = f"{BASE_URL}mark_keyword_done.php?api_key={API_KEY}"
                    requests.post(mark_url, json={"keyword": kw}, headers=HEADERS, verify=False, timeout=10)
                    
                    if len(all_found) == 0:
                        print(f"    🏁 จบการค้นหา: ไม่พบกลุ่มใหม่สำหรับคำนี้เลย -> (ตัดออกจากคิวแล้ว)")
                    else:
                        print(f"    🏁 จบการค้นหาคำนี้: สรุปเจอกลุ่มเป้าหมายทั้งหมด {len(all_found)} กลุ่ม -> (ตัดออกจากคิวแล้ว)")
                except Exception as e:
                    print(f"    ⚠️ ไม่สามารถตัดคิวคำค้นหาได้: {e}")

            except Exception as e:
                print(f"    ❌ Error during search: {e}")
                continue

        await browser.close()
        print(f"\n[{datetime.now().strftime('%H:%M:%S')}] 🎉 Finished queue. Total Found: {len(all_found)}")

async def main():
    while True:
        try:
            await scrape_facebook_groups()
        except Exception as e:
            print(f"[{datetime.now().strftime('%H:%M:%S')}] Error in main loop: {e}")
            
        print(f"[{datetime.now().strftime('%H:%M:%S')}] 💤 Sleeping for 60 minutes before searching again...")
        await asyncio.sleep(3600)

if __name__ == "__main__":
    asyncio.run(main())
