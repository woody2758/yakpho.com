import asyncio
from playwright.async_api import async_playwright
import os
import sys

async def check_fb():
    print("--- YakPho FB Auth Check ---")
    storage_state_path = "fb_auth.json"
    
    if not os.path.exists(storage_state_path):
        print(f"FAILED: {storage_state_path} NOT FOUND in {os.getcwd()}")
        return

    async with async_playwright() as p:
        print("[1/3] Launching Browser with Cookies...")
        browser = await p.chromium.launch(headless=True)
        context = await browser.new_context(
            storage_state=storage_state_path,
            user_agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
        )
        page = await context.new_page()
        
        print("[2/3] Checking Facebook Session...")
        try:
            await page.goto("https://www.facebook.com/", wait_until="networkidle", timeout=60000)
            current_url = page.url
            print(f"Current URL: {current_url}")
            
            if "login" in current_url:
                print("❌ RESULT: SESSION EXPIRED (Redirected to Login)")
            else:
                print("✓ RESULT: LOGGED IN SUCCESSFULLY!")
                # Try search as well
                print("[3/3] Testing Search Redirection...")
                await page.goto("https://www.facebook.com/search/pages/?q=test", wait_until="networkidle", timeout=30000)
                print(f"Search URL: {page.url}")
                if "login" in page.url:
                    print("❌ RESULT: SEARCH BLOCKED (Redirected to Login)")
                else:
                    print("✓ RESULT: SEARCH IS ACCESSIBLE!")
        except Exception as e:
            print(f"❌ ERROR: {e}")
            
        await browser.close()

if __name__ == "__main__":
    asyncio.run(check_fb())
