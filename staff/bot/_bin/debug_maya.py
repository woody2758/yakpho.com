import asyncio
from playwright.async_api import async_playwright
import os
import sys

async def run_diagnostic():
    print("--- YakPho AI Staff Diagnostic ---")
    print(f"Python Version: {sys.version}")
    print(f"CWD: {os.getcwd()}")
    
    try:
        async with async_playwright() as p:
            print("[1/3] Launching Browser (Headless)...")
            browser = await p.chromium.launch(headless=True)
            print("✓ Browser Launched Successfully!")
            
            print("[2/3] Opening Page...")
            page = await browser.new_page()
            
            print("[3/3] Navigating to example.com...")
            response = await page.goto("https://www.example.com", timeout=30000)
            print(f"✓ Navigation Success! Status: {response.status}")
            
            title = await page.title()
            print(f"✓ Page Title: {title}")
            
            await browser.close()
            print("\n--- DIAGNOSTIC COMPLETED: NO ISSUES FOUND ---")
            
    except Exception as e:
        print("\n--- DIAGNOSTIC FAILED! ---")
        print(f"ERROR TYPE: {type(e).__name__}")
        print(f"ERROR MESSAGE: {e}")
        print("\n--- POSSIBLE CAUSES ---")
        if "Executable doesn't exist" in str(e):
            print("Action: Need to run 'playwright install'")
        elif "dependencies" in str(e):
            print("Action: Need to run 'playwright install-deps'")
        else:
            print("Action: Check network or permissions.")

if __name__ == "__main__":
    asyncio.run(run_diagnostic())
