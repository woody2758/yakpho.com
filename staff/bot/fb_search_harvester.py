import asyncio
import json
import os
import re
import requests
import random
import urllib.parse
from playwright.async_api import async_playwright
from datetime import datetime

# --- CONFIGURATION (V6.0 Harvester) ---
BASE_URL = "https://yakpho.com/staff/api/"
API_KEY = "yakpho2026"
HEADLESS = True

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

SEARCH_KEYWORDS = ["ร้านนวดใน", "นวดแผนไทยใน", "นวดเพื่อสุขภาพใน"]

def get_time(): return datetime.now().strftime('%H:%M:%S')

async def human_delay(min_s=3, max_s=8):
    await asyncio.sleep(random.uniform(min_s, max_s))

async def human_scroll(page):
    steps = random.randint(3, 6)
    for _ in range(steps):
        await page.evaluate(f"window.scrollBy(0, {random.randint(300, 800)})")
        await human_delay(1, 3)

def auto_convert_cookies(storage_path):
    if not os.path.exists(storage_path) or os.path.getsize(storage_path) <= 10:
        return None
    try:
        with open(storage_path, "r", encoding="utf-8") as f:
            c_data = json.load(f)
            if isinstance(c_data, list):
                valid_cookies = [{"name": c["name"], "value": c["value"], "domain": c.get("domain", ".facebook.com"), "path": c.get("path", "/")} for c in c_data if "name" in c and "value" in c]
                converted = {"cookies": valid_cookies, "origins": []}
                # Overwrite automatically
                with open(storage_path, "w", encoding="utf-8") as fw:
                    json.dump(converted, fw, indent=4)
                return converted
            elif isinstance(c_data, dict) and "cookies" in c_data:
                return c_data
    except Exception as e:
        print(f"[DEBUG] Error converting cookies: {e}")
    return None

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

async def save_harvester_pages(pages):
    if not pages: return
    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
        "Content-Type": "application/json",
        "Accept": "application/json"
    }
    # Send one at a time to avoid WAF/ModSecurity block on large batches
    ok_count = 0
    for item in pages:
        try:
            r = requests.post(f"{BASE_URL}save_fb_page.php?api_key={API_KEY}", json=[item], headers=headers, timeout=30)
            if r.status_code == 200:
                ok_count += 1
            else:
                print(f"[{get_time()}]    [API] ⚠️ Status {r.status_code} for: {item.get('name','?')}")
        except Exception as e:
            print(f"[{get_time()}]    [API] Item Error: {e}")
    print(f"[{get_time()}]    [API] Queued {ok_count}/{len(pages)} strict-match leads to yakpho.com database.")

async def get_bot_settings():
    headers = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"}
    try:
        r = requests.get(f"{BASE_URL}get_fb_settings.php?api_key={API_KEY}", headers=headers, timeout=15)
        return r.json().get('settings', {})
    except: return {}

async def run_harvester():
    print("\n" + "="*50)
    print(f"[{get_time()}] >>> FB HARVESTER (V7.0 - CLOUD ENABLED) <<<")
    print("="*50)
    
    settings = await get_bot_settings()
    active_role = settings.get('active_persona', 'worldwide')
    db_cookies_json = settings.get('cookies', {}).get(active_role, '')

    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=HEADLESS)
        
        # 🔑 Load Cookies from Cloud
        raw_cookies = None
        if db_cookies_json and len(db_cookies_json) > 50:
            print(f"[{get_time()}] ☁️ Using Cloud cookies for: {active_role.upper()}")
            try: raw_cookies = json.loads(db_cookies_json)
            except: print("⚠️ Invalid JSON for cookies in DB.")
        
        if not raw_cookies:
            # Fallback Local
            local_file = f"fb_auth_{active_role}.json"
            if os.path.exists(local_file):
                print(f"[{get_time()}] 📂 Local Fallback: {local_file}")
                with open(local_file, 'r') as f: raw_cookies = json.load(f)
        
        storage_state = None
        if raw_cookies:
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
            print(f"[{get_time()}] ✅ Persona: {active_role.upper()}")
        else:
            print(f"[{get_time()}] ❌ ERROR: No cookies found for {active_role}")
            await browser.close()
            return

        context = await browser.new_context(
            storage_state=storage_state,
            user_agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
            viewport={'width': 1900, 'height': 1200}
        )
        
        stealth_script = """
        Object.defineProperty(navigator, 'webdriver', {get: () => undefined});
        window.chrome = { runtime: {} };
        """
        page = await context.new_page()
        await page.add_init_script(stealth_script)
        
        print(f"[{get_time()}] [Harvester V6.21] Booting Up Sequence...")
        
        # Session Check
        await page.goto("https://www.facebook.com/", wait_until="domcontentloaded", timeout=60000)
        await human_delay(5, 10)
        title = await page.title()
        if "login" in page.url.lower() or "log in" in title.lower() or "เข้าสู่ระบบ" in title:
            print(f"[{get_time()}] !!! EXPIRED SESSION. STOPPING HARVESTER !!!")
            await browser.close()
            return

        work_list = PROVINCES.copy()
        random.shuffle(work_list)

        for province in work_list:
            keyword = random.choice(SEARCH_KEYWORDS)
            query = f"{keyword} {province}"
            print(f"\n[{get_time()}] --- 🚜 HARVESTING: {query} ---")
            
            try:
                # Use the 'top' search route to bypass Facebook WAF protections
                search_url = f"https://www.facebook.com/search/top/?q={urllib.parse.quote(query)}"
                await page.goto(search_url, wait_until="domcontentloaded", timeout=60000)
                await human_delay(2, 4)

                # Now simulate human clicking to the "Pages" tab so React safely hydrates the data.
                # Use a resilient retry loop in case Facebook's React router experiences high latency.
                clicked = False
                for attempt in range(3):
                    try:
                        # Find the first valid "Pages" or "เพจ" link
                        pages_tab = page.get_by_role("link", name=re.compile(r'^(เพจ|Pages)$', re.IGNORECASE)).first
                        await pages_tab.click(timeout=5000)
                        await page.wait_for_timeout(3000)
                        clicked = True
                        break
                    except Exception as e:
                        await human_delay(2, 4)
                
                if not clicked:
                    print(f"[{get_time()}]   [DEBUG] Could not lock onto 'Pages' tab after 3 attempts.")
                        
                # --- DEBUG SCREENSHOT 2: After Tab Click Attempt ---
                # await page.screenshot(path=f"debug_2_tab_{province}.png", full_page=True)

                # --- Mid-run session check ---
                if "login" in page.url.lower() or "login" in (await page.title()).lower() or "เข้าสู่ระบบ" in (await page.title()):
                    print(f"[{get_time()}] !!! EXPIRED SESSION DETECTED MID-RUN. STOPPING HARVESTER !!!")
                    await browser.close()
                    return

                # Auto-confirm any M-Basic login gate
                login_btn = await page.query_selector("input[value*='Log In'], input[value*='เข้าสู่ระบบ'], input[value*='Continue'], input[value*='ดำเนินการต่อ']")
                if login_btn:
                    await login_btn.click()
                    await page.wait_for_load_state("domcontentloaded")
                    
                # --- Rate Limit & Soft-Ban Detection ---
                # Facebook serves a blank page with just "Not Found" if the account searches too rapidly.
                try:
                    body_text = await page.evaluate("() => document.body.innerText.trim()")
                    if "Not Found" in body_text and len(body_text) < 50:
                        print(f"[{get_time()}] !!! [WARNING] Facebook Anti-Bot 'Not Found' Rate Limit Triggered !!!")
                        print(f"[{get_time()}]     -> Bot is searching too aggressively. Hibernating for 3 minutes to clear throttle...")
                        await page.screenshot(path=f"RATE_LIMIT_BLOCK_{province}.png", full_page=True)
                        await human_delay(180, 200)
                        continue # Skip to next province after the long sleep
                except: pass
                
                await human_delay(3, 5)

                total_harvested = 0
                global_seen_urls = set()  # Prevent pushing dupes across 5 scroll rounds

                for scroll_round in range(5):  # Scroll 5 times to load more cards
                    print(f"[{get_time()}] - Mowing down page {scroll_round+1}/5...")
                    await human_scroll(page)
                    await human_delay(2, 4)

                    # --- DEBUG SCREENSHOT 3: After First Scroll ---
                    # if scroll_round == 0:
                    #     await page.screenshot(path=f"debug_3_scroll_{province}.png", full_page=True)

                    # --- JS extraction via DOM traversal ---
                    raw_pages = await page.evaluate(r"""
                        () => {
                            const SYSTEM_SLUGS = new Set([
                                'terms','policies','privacy','help','ads','business','pages',
                                'login','reg','cookies','careers','developers','about','create',
                                'friends','messages','notifications','settings','bookmarks',
                                'profile','messenger','marketplace','watch','gaming','fundraisers',
                                'events','groups','memories','saved','ads_manager','directory',
                                'checkpoint','recover','unsupportedbrowser','sharer', 'wui', 'home.php',
                                'search', 'hashtag', 'search_results'
                            ]);

                            const seen = new Set();
                            const results = [];

                            // Restrict to Center Search Column to prevent Sidebar links from accidentally inheriting global 'นวด' text from deep parent traversal.
                            // 'role="main"' reliably targets the center feed on both Local and VPS environments.
                            const mainArea = document.querySelector('div[role="main"]') || document.body;
                            const allLinks = Array.from(mainArea.querySelectorAll('a[href]'));

                            allLinks.forEach(link => {
                                try {
                                    let href = link.getAttribute('href') || '';
                                    if (!href) return;
                                    
                                    // Filter out system links
                                    if (href.includes('/groups/') || href.includes('/hashtag/') || href.includes('/reel/')) return;
                                    if (href.includes('business.facebook.com') || href.includes('latest/home')) return;
                                    if (href.includes('/events/') || href.includes('/watch/') || href.includes('/story.php')) return;
                                    if (href.includes('/stories/') || href.includes('/search/') || href.includes('/photo') || href.includes('/video')) return;
                                    if (href.includes('/pages/')) return; // Auto-generated Facebook location/place pages (un-messageable)
                                    if (href.includes('javascript:') || href.includes('/help') || href.includes('/posts/')) return;
                                    if (href.includes('/wui/') || href.includes('/policies') || href.includes('/careers')) return;
                                    if (href.includes('/dialog/') || href.includes('/sharer/')) return;
                                    
                                    // Extreme DOM filtering for Desktop: Only track structural links containing actual page avatars/headers.
                                    // A true Desktop Page Card anchor will often contain an SVG (verified checkmark) or multiple nested heavy divs.
                                    // We filter out lightweight structural navigational items to prevent junk entries.
                                    if (href.includes('?__tn__=')) return; // Ignore deeply nested React metadata parameters completely
                                    if (link.innerText.includes('Like') || link.innerText.includes('Reply')) return;

                                    // Extract name (Take only the very first line to isolate the shop name)
                                    let rawText = (link.innerText || link.textContent || '').trim();
                                    const name = rawText.split('\n')[0].trim();
                                    
                                    // The cards combine many elements inside <a>, so raw text is huge. Name length constraint adjusted.
                                    if (!name || name.length < 3 || name.length > 300) return;

                                    // Safely parse URL without exploding tracking identifiers mapped via Query String
                                    let baseUrl = href;
                                    if (baseUrl.startsWith('/')) baseUrl = 'https://www.facebook.com' + baseUrl;
                                    
                                    baseUrl = baseUrl
                                        .replace('https://mbasic.facebook.com', 'https://www.facebook.com')
                                        .replace('https://m.facebook.com', 'https://www.facebook.com');

                                    if (!baseUrl.includes('facebook.com')) return;

                                    // Calculate Slug Context Safely
                                    let slug = '';
                                    let finalUrl = baseUrl.split('?')[0].replace(/\/$/, ''); // baseline without trailing slash
                                    let qMarkIndex = baseUrl.indexOf('?');

                                    if (baseUrl.includes('profile.php?id=')) {
                                        let idMatch = baseUrl.match(/id=([0-9]+)/);
                                        if(idMatch) {
                                            slug = idMatch[1];
                                            finalUrl = 'https://www.facebook.com/profile.php?id=' + slug;
                                        }
                                    } else {
                                        let parts = finalUrl.split('/').filter(p => !['https:', 'www.facebook.com', 'm.facebook.com', ''].includes(p));
                                        slug = parts.length > 0 ? parts[parts.length - 1].toLowerCase() : '';
                                    }

                                    if (!slug || SYSTEM_SLUGS.has(slug) || slug.length < 2) return;

                                    if (seen.has(slug)) return; // Use slug for deduplication to prevent same page via multiple tracking links

                                    // Extreme Desktop verification: Desktop pages always have profile URLs with NO query strings
                                    // OR profile.php?id=xxxx. Anything else containing ? or long hex codes is spam.
                                    if(finalUrl.includes('.php') && !finalUrl.includes('profile.php')) return;
                                    if(slug.length > 50) return; // Ignore corrupted parameter bleed 
                                    
                                    // Desktop Cards verification: If the closest <a> tag has essentially NO inner text,
                                    // but its parent tree DOES have valid followers text, it's a hidden avatar link. We only want main titles.
                                    let isHiddenGraphicsLink = (link.innerText || '').trim().length < 3;
                                    if (isHiddenGraphicsLink) return;

                                    seen.add(slug);

                                    // Traverse up using parentElement (expanding depth to 12 for heavy Desktop React trees)
                                    let parentText = '';
                                    let current = link;
                                    for (let i = 0; i < 12; i++) {
                                        if (current.parentElement) {
                                            current = current.parentElement;
                                            parentText = (current.innerText || current.textContent || '');
                                            // Safety break: if we hit a container holding hundreds of words, we've gone too far (swallowed the whole feed)
                                            if (parentText.length > 500) break;
                                        } else {
                                            break;
                                        }
                                    }

                                    results.push({ name, url: finalUrl, containerText: parentText });
                                } catch(e) {}
                            });

                            if (results.length === 0) {
                                let dump = [];
                                allLinks.slice(0, 15).forEach(l => {
                                    dump.push({ text: (l.innerText || '').substring(0,25).split('\n').join(' '), href: l.getAttribute('href') });
                                });
                                return [{ name: "DEBUG_DUMP", url: "DEBUG_DUMP", containerText: JSON.stringify(dump) }];
                            }

                            return results;
                        }
                    """)



                    batch_payload = []
                    
                    # Handle debug dump natively
                    if len(raw_pages) == 1 and raw_pages[0].get('name') == "DEBUG_DUMP":
                        print(f"[{get_time()}]   [CRITICAL DEBUG] 0 Cards Found. Top Links on screen:")
                        try:
                            dbg = json.loads(raw_pages[0].get('containerText', '[]'))
                            for d in dbg: print(f"       -> [TXT: {d.get('text')}] [HREF: {d.get('href')}]")
                            if not dbg: print(f"       -> [WARNING] DOM is completely empty! Is the VPS IP blocked by Facebook?")
                            
                            # Emergency Diagnostic Snapshot saving ONLY if 0 cards are found to prevent disk bloat
                            await page.screenshot(path=f"emergency_vps_block_{province}.png", full_page=True)
                            print(f"[{get_time()}]   [DEBUG] Saved emergency snapshot to diagnose VPS block.")
                        except: pass
                        raw_pages = []
                    
                    print(f"[{get_time()}]   [DEBUG] Found {len(raw_pages)} page cards via follow-anchor method")

                    for item in raw_pages:
                        page_name = item.get('name', '').strip()
                        clean_url  = item.get('url', '').strip()
                        container_text = item.get('containerText', '')

                        if not page_name or not clean_url: continue

                        # Desktop AI Quality Gate: Strict rejection of Facebook algorithm bloat.
                        page_name_lower = page_name.lower()
                        container_text_lower = container_text.lower()
                        
                        # Radically narrowed keywords to exactly match Massage/Spa. 
                        # English keywords use regex boundaries to prevent substring bleed (eg. "space" containing "spa")
                        thai_keywords = ["นวด", "สปา", "แพทย์แผนไทย", "แผนไทย", "กายภาพบำบัด"]
                        eng_pattern = r'\b(spa|massage|massages)\b'
                        
                        is_relevant = any(k in page_name_lower for k in thai_keywords) or re.search(eng_pattern, page_name_lower)
                        if not is_relevant:
                            # Verify if the Facebook structural category explicitly mentions our exact service types
                            is_relevant = any(k in container_text_lower for k in thai_keywords) or re.search(eng_pattern, container_text_lower)
                            
                        if not is_relevant:
                            continue # Destroy false-positive lead

                        # Extract followers from container text
                        followers = "N/A"
                        f_match = re.search(
                            r'([0-9.,]+)\s*(พัน|หมื่น|แสน|ล้าน|K|M|%)\s*(คน|คนถูกใจ|คนติดตาม|likes|followers?|recommend|reviews?|fo\.\.\.?\b)?',
                            container_text, re.IGNORECASE
                        )
                        if not f_match:
                            f_match = re.search(
                                r'([0-9.,%]+[KM]?)\s*(คนถูกใจ|คนติดตาม|likes|followers?|recommend|reviews?|fo\.\.\.?\b)',
                                container_text, re.IGNORECASE
                            )
                        
                        if f_match:
                            followers = parse_followers(f_match.group(0))

                        page_id = clean_url.split('/')[-1]
                        batch_payload.append({
                            "name": page_name,
                            "url": clean_url,
                            "messenger": f"https://m.me/{page_id}",
                            "province": province,
                            "followers": followers,
                            "phone": None,
                            "email": None,
                            "website": None,
                            "is_active": 1
                        })

                    # Deduplicate in batch and across scroll rounds
                    unique_batch = []
                    for b in batch_payload:
                        if b['url'] not in global_seen_urls:
                            global_seen_urls.add(b['url'])
                            unique_batch.append(b)

                    if unique_batch:
                        if len(raw_pages) > len(unique_batch):
                            dropped = len(raw_pages) - len(unique_batch)
                            print(f"[{get_time()}]   [DEBUG] Ignored {dropped} irrelevant/duplicate cards.")
                        
                        await save_harvester_pages(unique_batch)
                        total_harvested += len(unique_batch)
                        
                print(f"[{get_time()}] --- Finished Province: {province}. Total Harvested: {total_harvested} ---")
                await human_delay(10, 25) # Rest before next province

            except Exception as e:
                print(f"[{get_time()}] Error on {province}: {e}")

        await browser.close()
        print(f"[{get_time()}] Harvesting Cycle Complete.")

if __name__ == "__main__":
    asyncio.run(run_harvester())
