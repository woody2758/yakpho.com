import time
import subprocess
import os
import signal
import requests
from datetime import datetime

# --- CONFIGURATION ---
BASE_URL = "https://yakpho.com/staff/api/bot_bridge.php"
API_KEY = "yakpho2026"
BOT_DIR = "/root/esther_aroma_ai/dept1_leads/"
LOG_DIR = "/root/esther_aroma_ai/dept1_leads/logs/"

if not os.path.exists(LOG_DIR):
    os.makedirs(LOG_DIR)

def get_last_logs(bot_key):
    log_file = os.path.join(LOG_DIR, f"{bot_key}.log")
    if not os.path.exists(log_file): return ""
    try:
        with open(log_file, 'r', encoding='utf-8', errors='ignore') as f:
            lines = f.readlines()
            return "".join(lines[-15:])
    except: return ""

def is_pid_running(pid):
    if not pid or pid <= 0: return False
    try:
        os.kill(pid, 0)
        return True
    except OSError:
        return False

def run_agent():
    print(f"[{datetime.now()}] AI Staff Remote Agent (JSON Mode) started.")

    while True:
        try:
            # 1. Poll for commands
            print(f"[{datetime.now()}] Polling for commands...", flush=True)
            headers = {'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'}
            r = requests.get(f"{BASE_URL}?action=poll&api_key={API_KEY}", headers=headers, timeout=15)
            if r.status_code != 200:
                print(f"API Error ({r.status_code}): {r.text}", flush=True)
                time.sleep(10)
                continue

            try:
                data = r.json()
            except Exception as je:
                print(f"JSON Parse Error. Raw response: {r.text[:500]}", flush=True)
                time.sleep(10)
                continue

            if not data.get('ok'):
                print("API Error:", data.get('msg'), flush=True)
                time.sleep(10)
                continue

            # 2. bot_bridge.php sends commands as a List of rows
            # e.g. [{"bot_key": "fb_joiner", "pending_command": "start"}, ...]
            raw_commands = data.get('commands', [])
            if isinstance(raw_commands, list):
                commands = {row['bot_key']: row['pending_command'] for row in raw_commands if row.get('bot_key')}
            elif isinstance(raw_commands, dict):
                commands = raw_commands
            else:
                commands = {}

            updates_to_send = []

            for bot_key, cmd in commands.items():
                script_path = os.path.join(BOT_DIR, f"{bot_key}.py")
                pid_file = os.path.join(BOT_DIR, f"{bot_key}.pid")
                log_path = os.path.join(LOG_DIR, f"{bot_key}.log")

                current_pid = 0
                if os.path.exists(pid_file):
                    try:
                        with open(pid_file, 'r') as f:
                            current_pid = int(f.read().strip())
                    except: pass

                running = is_pid_running(current_pid)
                update = {'bot_key': bot_key, 'logs': get_last_logs(bot_key)}

                # Handle Start Command
                if cmd == 'start' and not running:
                    if not os.path.exists(script_path):
                        print(f"[{datetime.now()}] ERROR: File not found at {script_path}", flush=True)
                        continue
                    try:
                        with open(log_path, 'w') as log_f:
                            print(f"[{datetime.now()}] Spawning subprocess for {bot_key}...", flush=True)
                            proc = subprocess.Popen(['python3', '-u', script_path],
                                                   stdout=log_f, stderr=subprocess.STDOUT,
                                                   cwd=BOT_DIR,
                                                   start_new_session=True)
                            with open(pid_file, 'w') as pf: pf.write(str(proc.pid))
                            update['pid'] = proc.pid
                            update['status'] = 'running'
                            update['started'] = True
                            print(f"[{datetime.now()}] SUCCESS: Started {bot_key} with PID {proc.pid}", flush=True)
                    except Exception as spawn_e:
                        print(f"[{datetime.now()}] SPAWN ERROR for {bot_key}: {spawn_e}", flush=True)

                # Handle Stop Command
                elif cmd == 'stop':
                    if running:
                        print(f"Stopping {bot_key} (PID: {current_pid})...", flush=True)
                        try:
                            os.kill(current_pid, signal.SIGTERM)
                            time.sleep(1)
                            if is_pid_running(current_pid): os.kill(current_pid, signal.SIGTERM)
                        except: pass

                    update['pid'] = 0
                    update['status'] = 'idle'
                    update['stopped'] = True
                    if os.path.exists(pid_file): os.remove(pid_file)

                updates_to_send.append(update)

            # 3. ตรวจสอบ .pid ทุก cycle เสมอ (ไม่ว่าจะมี pending command หรือเปล่า)
            # → จับกรณีที่ bot จบเองตามธรรมชาติ (เช่น จอย 5 กลุ่มแล้ว exit)
            try:
                for pid_filename in [f for f in os.listdir(BOT_DIR) if f.endswith('.pid')]:
                    bot_key_hb = pid_filename.replace('.pid', '')

                    # ถ้า bot_key นี้ถูกจัดการใน command loop แล้ว → ข้ามไป
                    if bot_key_hb in commands:
                        continue

                    pid_path = os.path.join(BOT_DIR, pid_filename)
                    try:
                        with open(pid_path, 'r') as f:
                            hb_pid = int(f.read().strip())
                    except:
                        hb_pid = 0

                    running = is_pid_running(hb_pid)

                    if not running and hb_pid > 0:
                        # Process ตายแล้ว — ลบ .pid และแจ้ง server ว่า idle
                        print(f"[{datetime.now()}] [{bot_key_hb}] Process (PID {hb_pid}) finished naturally → marking IDLE", flush=True)
                        try:
                            os.remove(pid_path)
                        except: pass
                        updates_to_send.append({
                            'bot_key': bot_key_hb,
                            'pid': 0,
                            'status': 'idle',
                            'stopped': True,
                            'logs': get_last_logs(bot_key_hb)
                        })
                    else:
                        # Process ยังทำงานอยู่ → heartbeat ปกติ
                        updates_to_send.append({
                            'bot_key': bot_key_hb,
                            'pid': hb_pid,
                            'status': 'running',
                            'logs': get_last_logs(bot_key_hb)
                        })
            except Exception as hb_e:
                print(f"Heartbeat error: {hb_e}", flush=True)

            # 4. Sync back to Hosting
            print(f"Syncing {len(updates_to_send)} updates to server...", flush=True)
            sync_headers = headers.copy()
            sync_headers['Content-Type'] = 'application/json'
            sr = requests.post(f"{BASE_URL}?action=sync&api_key={API_KEY}",
                         json={'updates': updates_to_send}, headers=sync_headers, timeout=20)
            print(f"Sync complete. Server says: {sr.text[:100]}", flush=True)

        except Exception as e:
            print(f"Loop Error: {e}", flush=True)

        print(f"Sleeping 5s...", flush=True)
        time.sleep(5)

if __name__ == "__main__":
    run_agent()
