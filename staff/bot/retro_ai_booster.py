import requests
import json
import time
import re
import sys
import google.generativeai as genai
import urllib3

# ปิด Warning SSL
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

# --- CONFIGURATION ---
BASE_URL = "https://yakpho.com/staff/api/"
API_KEY = "yakpho2026"
HEADERS = {"User-Agent": "Mozilla/5.0 ..."}
BATCH_SIZE = 25 

def get_server_settings():
    url = f"{BASE_URL}get_fb_settings.php?api_key={API_KEY}"
    try:
        response = requests.get(url, headers=HEADERS, timeout=15, verify=False)
        return response.json().get('settings', {}) if response.status_code == 200 else {}
    except: return {}

def get_pending_groups():
    url = f"{BASE_URL}get_groups_for_ai.php?api_key={API_KEY}"
    try:
        response = requests.get(url, headers=HEADERS, timeout=15, verify=False)
        return response.json().get('groups', []) if response.status_code == 200 else []
    except: return []

def update_group_ai(group_id, marker, reason):
    url = f"{BASE_URL}update_group_info.php?api_key={API_KEY}"
    try:
        requests.post(url, json={"id": group_id, "field": "strategy_marker", "value": marker}, headers=HEADERS, timeout=10, verify=False)
        requests.post(url, json={"id": group_id, "field": "ai_reason", "value": reason}, headers=HEADERS, timeout=10, verify=False)
    except: pass

def main():
    print("\n" + "="*50)
    print(" >>> ANTIGRAVITY BOOSTER (V10.2 - RETRO STABLE) <<<")
    print("="*50)
    
    settings = get_server_settings()
    gemini_key = settings.get('gemini_api_key', '').strip()
    
    if not gemini_key:
        print("❌ Error: Gemini Key Missing.")
        return

    # 📡 สแกนหาโมเดลที่เสถียรที่สุด (กำจัด 2.0/2.5/Robotics)
    genai.configure(api_key=gemini_key)
    try:
        all_models = [m.name for m in genai.list_models() if 'generateContent' in m.supported_generation_methods]
        
        # 🟢 แบนรุ่นที่เสี่ยง (2.0/2.5/Robotics/Vision/Experimental)
        blocked = ['2.0', '2.5', 'robotics', 'experimental', 'exp', 'vision']
        clean_models = [m for m in all_models if not any(bk in m.lower() for bk in blocked)]
        
        # เน้นเจาะจง 1.5 เท่านั้น เพราะเสถียรที่สุดสำหรับ Paid Tier
        best_model = ""
        for pref in ['1.5-flash', 'gemini-1.5', 'flash', 'pro']:
            for m in clean_models:
                if pref in m.lower():
                    best_model = m
                    break
            if best_model: break
        
        if not best_model: best_model = clean_models[0] if clean_models else all_models[0]
        print(f"✅ AI Ready: Successfully mapped to {best_model}")
        model = genai.GenerativeModel(best_model)
    except Exception as e:
        print(f"❌ SDK Scan Error: {e}")
        return

    while True:
        groups = get_pending_groups()
        if not groups:
            print("\n🎉 MISSION COMPLETE! DATABASE IS FULLY CATEGORIZED.")
            break
            
        print(f"📦 Analyzing current batches...")
        for i in range(0, len(groups), BATCH_SIZE):
            chunk = groups[i : i + BATCH_SIZE]
            print(f"  - Analyzing Batch {i//BATCH_SIZE + 1}...")
            ai_chunk = []
            for g in chunk:
                if 'private' in str(g.get('privacy_type', '')).lower():
                    update_group_ai(g['id'], 'Observe', 'คัดทิ้ง: กลุ่มปิด (Private Group)')
                else:
                    ai_chunk.append(g)
            
            if not ai_chunk: 
                continue
                
            names_text = "\n".join([f"ID:{g['id']} | NAME:{g['group_name']}" for g in ai_chunk])
            prompt = f"Categorize for B2B Spa Targeting Thai owners/workers abroad.\n" \
                     f"- Green(Hard): Thai communities or jobs abroad (e.g. คนไทยในอังกฤษ, หางานนวดในอเมริกา, สมาคมคนไทย)\n" \
                     f"- Yellow(Soft): Public Thai Massage shops/groups abroad (e.g. ร้านนวดไทยในซิดนีย์, Thai Massage London)\n" \
                     f"- Red(Observe): Groups strictly in Thailand (e.g. ร้านนวดเชียงใหม่) OR Not related to Thai massage/spa.\n" \
                     f"JSON ARRAY ONLY: [{{'id':ID, 'marker':'color', 'reason':'thai'}}, ...]\nList:\n{names_text}"
            
            try:
                response = model.generate_content(prompt)
                clean_text = re.sub(r'```json\n?|```', '', response.text).strip()
                results = json.loads(clean_text)
                
                for res in results:
                    marker = {"Green": "Hard", "Yellow": "Soft", "Red": "Observe"}.get(res.get('marker', '').capitalize(), res.get('marker', 'Observe'))
                    update_group_ai(res.get('id'), marker, res.get('reason', 'AI Final Analysis V10.2'))
                
                print(f"    ✅ Update Successful ({len(results)} items saved).")
                
            except Exception as e:
                err_msg = str(e).lower()
                if "quota" in err_msg or "429" in err_msg or "resourceexhausted" in err_msg:
                    print(f"    ⛔ Gemini Quota Exceeded (Tier 1 Limit 10K RPD). Stopping booster to prevent infinite loop.")
                    sys.exit(1)
                else:
                    print(f"    ⚠️ Batch Error: {str(e)[:100]}")
            
            time.sleep(4) # ป้องกัน 429 Too Many Requests (โควต้าฟรี 15 RPM)

    print("\n🎉 DONE.")

if __name__ == "__main__":
    main()
