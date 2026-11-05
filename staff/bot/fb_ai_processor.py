import requests
import json
import time
from google import genai

# Configuration
BASE_URL = "https://yakpho.com/staff/api"
API_KEY = "yakpho2026"

def get_settings():
    headers = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"}
    try:
        r = requests.get(f"{BASE_URL}/get_fb_settings.php?api_key={API_KEY}", headers=headers, timeout=15)
        return r.json()
    except Exception as e:
        print(f"Error fetching settings: {e}")
        return None

def get_groups():
    headers = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"}
    try:
        r = requests.get(f"{BASE_URL}/get_groups_for_ai.php?api_key={API_KEY}", headers=headers, timeout=15)
        return r.json().get('groups', [])
    except Exception as e:
        print(f"Error fetching groups: {e}")
        return []

def update_ai_data(results):
    try:
        headers = {
            "Content-Type": "application/json",
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
        }
        r = requests.post(
            f"{BASE_URL}/update_fb_ai.php?api_key={API_KEY}",
            json=results,
            headers=headers,
            timeout=30
        )
        return r.json()
    except Exception as e:
        print(f"Error updating AI data: {e}")
        return None

def process_groups():
    settings = get_settings()
    if not settings or not settings.get('gemini_api_key'):
        print("Error: Gemini API Key not found in settings.")
        return

    client = genai.Client(api_key=settings['gemini_api_key'])

    groups = get_groups()
    if not groups:
        print("No groups found for processing.")
        return

    print(f"Processing {len(groups)} groups with Gemini AI...")
    
    results = []
    for g in groups:
        print(f" - Analyzing: {g['group_name']}")
        if 'private' in str(g.get('privacy_type', '')).lower():
            print(f"   -> Skipped (Private Group)")
            results.append({
                "id": g['id'],
                "tags": "[Private-Group]",
                "score": 0,
                "summary": "คัดทิ้ง: เป็นกลุ่มปิด (Private Group) บอทไม่สามารถเข้าไปโพสต์ได้อย่างเสรี"
            })
            continue

        prompt = f"""
        Analyze this Facebook Group for a B2B business 'YakPho Aroma'.
        The target audience is strictly 'Thai Communities Abroad' (คนไทยในต่างประเทศ).
        We DO NOT want local massage shops or closed groups.
        
        Group Name: {g['group_name']}
        Activity: {g['last_activity']}
        Members: {g['member_count']}
        
        Tasks:
        1. Categorize using tags: [Thai-Expat], [Massage-Pro], [Community].
        2. Give a Suitability Score (1-10). 
           - Give 8-10 (Green) for 'Thai communities/jobs abroad' (e.g., คนไทยในออสเตรเลีย, หางานนวด).
           - Give 5-7 (Yellow) for 'Public Thai Massage shops abroad'.
           - Give 1-3 (Red) for 'Groups strictly in Thailand' or unrelated groups.
        3. Write a 1-sentence summary in Thai explaining the score.
        
        Response MUST be in STRICT JSON format:
        {{
          "tags": "[Tags]",
          "score": 1-10,
          "summary": "Reasoning in Thai"
        }}
        """
        
        try:
            response = client.models.generate_content(model='gemini-1.5-flash', contents=prompt)
            # Find JSON in response (Gemini sometimes adds markdown blocks)
            text = response.text
            if "```json" in text:
                text = text.split("```json")[1].split("```")[0].strip()
            elif "{" in text:
                text = text[text.find("{"):text.rfind("}")+1]
            
            ai_data = json.loads(text)
            results.append({
                "id": g['id'],
                "tags": ai_data.get('tags', ''),
                "score": int(ai_data.get('score', 5)),
                "summary": ai_data.get('summary', '')
            })
            
            # Anti-throttle (Free tier allows 15 RPM, so sleep 4 seconds)
            time.sleep(4) 
            
        except Exception as e:
            err_msg = str(e)
            if "quota" in err_msg.lower() or "429" in err_msg or "resourceexhausted" in err_msg.lower():
                print(f"   ⚠️ Gemini Quota Exceeded. Stopping AI process.")
                import os
                os._exit(1)
            else:
                print(f"   ⚠️ Error analyzing {g['group_name']}: {err_msg[:150]}...")

    if results:
        print(f"Updating {len(results)} groups in database...")
        update_ai_data(results)
        print("AI Processing Complete.")

if __name__ == "__main__":
    process_groups()
