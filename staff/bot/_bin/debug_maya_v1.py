import asyncio
import os
from playwright.async_api import async_playwright

async def debug_maya():
    async with async_playwright() as p:
        print("\n--- [Maya Debug Agent V1] Starting Diagnostics ---")
        browser = await p.chromium.launch(headless=True)
        storage_path = "fb_auth.json"
        storage_state = storage_path if os.path.exists(storage_path) and os.path.getsize(storage_path) > 10 else None
        
        context = await browser.new_context(
            storage_state=storage_state,
            user_agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
            viewport={'width': 1920, 'height': 1080}
        )
        
        page = await context.new_page()
        
        # 1. GOOGLE TEST (IS INTERNET OK?)
        try:
            print("[1/3] Checking Google Connectivity...")
            await page.goto("https://www.google.com/", timeout=30000)
            print(f"      [RESULT] OK! Title: {await page.title()}")
        except Exception as e:
            print(f"      [RESULT] Google FAILED: {e}")

        # 2. FACEBOOK HOME TEST (IS AUTH OK?)
        try:
            print("[2/3] Checking Facebook Home...")
            await page.goto("https://www.facebook.com/", wait_until="domcontentloaded", timeout=60000)
            title = await page.title()
            content = await page.content()
            print(f"      [RESULT] FB Home URL: {page.url}")
            print(f"      [RESULT] Title: {title}")
            print(f"      [RESULT] Content Length: {len(content)}")
            if "login" in page.url or "Log in" in title:
                print("      [ALERT] SESSION EXPIRED: Facebook redirected you to Login page.")
        except Exception as e:
            print(f"      [RESULT] FB Home FAILED: {e}")

        # 3. MBASIC SEARCH TEST (IS SEARCH BLOCKED?)
        try:
            test_query = "ร้านนวด สระบุรี"
            print(f"[3/3] Checking M-Basic Search for '{test_query}'...")
            search_url = f"https://mbasic.facebook.com/search/pages/?q={test_query}"
            await page.goto(search_url, wait_until="domcontentloaded", timeout=60000)
            title = await page.title()
            content = await page.content()
            print(f"      [RESULT] Search URL: {page.url}")
            print(f"      [RESULT] Title: {title}")
            print(f"      [RESULT] Content Length: {len(content)}")
            
            if len(content) < 500:
                print(f"      [DUMP] Raw Content: {content[:100]}")
                if "Not Found" in content:
                    print("      [CONCLUSION] IP BLOCK: Facebook is returning fake 'Not Found'.")
            else:
                print("      [CONCLUSION] SUCCESS: Search results ARE visible!")
        except Exception as e:
            print(f"      [RESULT] Search FAILED: {e}")

        await browser.close()
        print("--- [Maya Debug Agent V1] Diagnostics Complete ---\n")

if __name__ == "__main__":
    asyncio.run(debug_maya())
