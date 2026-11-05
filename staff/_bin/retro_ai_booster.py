import requests
import json
import time
import re
import sys
import google.generativeai as genai

# --- CONFIGURATION (Match with fb_group_scraper.py) ---
BASE_URL = "https://yakpho.com/staff/api/"
API_KEY = "yakpho2026"

def get_pending_groups():
    url = f"{BASE_URL}get_groups_for_ai.php?api_key={API_KEY}"
    try:
        response = requests.get(url, timeout=15)
        if response.status_code == 200:
            return response.json().get('groups', [])
    except Exception as e:
        print(f"Error fetching: {e}")
    return []

def update_group_ai(group_id, marker, reason):
    url = f"{BASE_URL}update_group_info.php?api_key={API_KEY}"
    try:
        # Update Marker
        requests.post(url, json={"id": group_id, "field": "strategy_marker", "value": marker}, timeout=10)
        # Update Reason
        requests.post(url, json={"id": group_id, "field": "ai_reason", "value": reason}, timeout=10)
    except Exception as e:
        # print(f"Update error: {e}")
        pass

def analyze_with_gemini(model, group_name):
    prompt = f"""
    Role: Social Media Engagement Specialist for 'Yakpho Aroma'.
    Task: Analyze this Facebook Group for 'AI Posting Strategy'.
    
    Group Name: {group_name}
    
    Strategy Rules:
    🔴 'Observe': Generic, moderated, or broad professional groups.
    🟡 'Soft': Community/Value-focused (therapists, health, relaxation).
    🟢 'Hard': Marketplace, sales, or specific Thai massage worker groups.
    ⚪ 'Blacklist': Irrelevant or spam.

    Return ONLY a Valid JSON object:
    {{
        "marker": "Observe" | "Soft" | "Hard" | "Blacklist",
        "reason": "short explanation in Thai or English"
    }}
    """
    try:
        response = model.generate_content(prompt)
        text = response.text
        clean_text = re.sub(r'```json\n?|```', '', text).strip()
        return json.loads(clean_text)
    except:
        return None

def main():
    print("🚀 Starting Retroactive AI Booster (V5 - Classic Mode)...")
    
    # 1. Get API Key from Argument
    manual_key = sys.argv[1] if len(sys.argv) > 1 else ""
    
    if not manual_key:
        print("❌ Error: Please provide Gemini API Key as an argument.")
        print("💡 Usage: python3 retro_ai_booster.py YOUR_API_KEY")
        return

    print(f"✅ Using API Key: {manual_key[:10]}...")
    
    try:
        genai.configure(api_key=manual_key)
        model = genai.GenerativeModel('gemini-1.5-flash')
    except Exception as e:
        print(f"❌ Gemini Setup Error: {e}")
        return

    while True:
        groups = get_pending_groups()
        if not groups:
            print("✅ All groups processed! Mission Accomplished.")
            break
        
        print(f"📦 Processing batch of {len(groups)} groups...")
        
        for g in groups:
            gid = g['id']
            name = g['group_name']
            
            print(f"  - Analyzing: {name} (ID: {gid})")
            result = analyze_with_gemini(model, name)
            
            if result:
                marker = result.get('marker', 'Observe')
                reason = result.get('reason', 'Retro AI Analysis V5')
                update_group_ai(gid, marker, reason)
                print(f"    ✅ Result Saved: {marker}")
            else:
                print(f"    ❌ AI Failed for {name}")
            
            time.sleep(1.5) # Prevent rate limiting

    print("🎉 Done.")

if __name__ == "__main__":
    main()
