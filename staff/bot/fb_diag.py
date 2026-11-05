"""
fb_diag.py — HTML Structure Diagnostic Tool
Saves raw HTML from 2 mbasic.facebook.com search results into timestamped files.
Run this on VPS then analyse the .html files to find correct selectors.

Usage:  python3 fb_diag.py
Output: diag_YYYYMMDD_HHMMSS_1.html  and  diag_YYYYMMDD_HHMMSS_2.html
"""

import asyncio
import json
import os
import urllib.parse
from datetime import datetime
from playwright.async_api import async_playwright

HEADLESS = True
STORAGE_PATH = "fb_auth.json"

# Two distinct search queries to compare
QUERIES = [
    "นวดเพื่อสุขภาพใน กรุงเทพมหานคร",
    "ร้านนวดใน เชียงใหม่",
]

async def main():
    ts = datetime.now().strftime("%Y%m%d_%H%M%S")
    print(f"[{ts}] FB Diagnostic Tool — starting")

    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=HEADLESS)

        # Load session
        storage_state = None
        if os.path.exists(STORAGE_PATH) and os.path.getsize(STORAGE_PATH) > 10:
            try:
                with open(STORAGE_PATH, "r", encoding="utf-8") as f:
                    c_data = json.load(f)
                    if isinstance(c_data, list):
                        valid_cookies = [
                            {"name": c["name"], "value": c["value"],
                             "domain": c.get("domain", ".facebook.com"),
                             "path": c.get("path", "/")}
                            for c in c_data if "name" in c and "value" in c
                        ]
                        storage_state = {"cookies": valid_cookies, "origins": []}
                    elif isinstance(c_data, dict) and "cookies" in c_data:
                        storage_state = c_data
                print("  Session loaded OK")
            except Exception as e:
                print(f"  Session load error: {e}")
        else:
            print("  WARNING: No fb_auth.json found — will run logged-out")

        context = await browser.new_context(
            storage_state=storage_state,
            user_agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
            viewport={"width": 1280, "height": 900},
        )
        page = await context.new_page()

        for i, query in enumerate(QUERIES, start=1):
            url = f"https://mbasic.facebook.com/search/pages/?q={urllib.parse.quote(query)}"
            print(f"\n[Q{i}] Navigating to: {url}")
            await page.goto(url, wait_until="domcontentloaded", timeout=60000)
            await asyncio.sleep(5)

            # Check login
            current_url = page.url
            title = await page.title()
            print(f"     Page URL   : {current_url}")
            print(f"     Page Title : {title}")

            if "login" in current_url.lower() or "เข้าสู่ระบบ" in title:
                print("     ❌ LOGGED OUT — session expired! Cannot continue.")
                await browser.close()
                return

            # Save full HTML
            html = await page.content()
            filename = f"diag_{ts}_{i}.html"
            with open(filename, "w", encoding="utf-8") as f:
                f.write(f"<!-- Query: {query} -->\n")
                f.write(f"<!-- URL: {current_url} -->\n")
                f.write(html)
            print(f"     ✅ Saved {len(html):,} bytes → {filename}")

            # Quick structure summary
            summary = await page.evaluate("""
                () => {
                    const forms = Array.from(document.querySelectorAll('form'));
                    const submits = Array.from(document.querySelectorAll('input[type="submit"], button[type="submit"], button'));
                    const allLinks = Array.from(document.querySelectorAll('a[href]'));

                    // Detect follower text patterns
                    const followerNodes = [];
                    document.querySelectorAll('*').forEach(el => {
                        const t = (el.innerText || el.textContent || '').trim();
                        if (t.includes('ผู้ติดตาม') && t.length < 120 && el.children.length < 5) {
                            followerNodes.push({
                                tag: el.tagName,
                                cls: el.className.toString().slice(0, 60),
                                text: t.slice(0, 100)
                            });
                        }
                    });

                    return {
                        formCount: forms.length,
                        formActions: forms.slice(0, 10).map(f => f.getAttribute('action') || ''),
                        submitLabels: submits.slice(0, 10).map(s => (s.value || s.textContent || '').trim().slice(0, 40)),
                        totalLinks: allLinks.length,
                        followerNodes: followerNodes.slice(0, 10),
                    };
                }
            """)

            print(f"\n     --- Structure Summary ---")
            print(f"     Forms ({summary['formCount']}): {summary['formActions']}")
            print(f"     Submit labels   : {summary['submitLabels']}")
            print(f"     Total <a> links : {summary['totalLinks']}")
            print(f"     Follower nodes  :")
            for node in summary['followerNodes']:
                print(f"       <{node['tag']} class=\"{node['cls']}\"> → {node['text']}")

            if i < len(QUERIES):
                print("\n  Waiting 10s before next query...")
                await asyncio.sleep(10)

        await browser.close()
        print(f"\n✅ Diagnostic complete. Check diag_{ts}_1.html and diag_{ts}_2.html")

if __name__ == "__main__":
    asyncio.run(main())
