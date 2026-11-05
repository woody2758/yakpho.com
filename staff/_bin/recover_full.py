import re
import json

log_file = r'C:\Users\Windows 10\.gemini\antigravity-ide\brain\bca73452-7a45-4378-bd81-8c25b863aee2\.system_generated\logs\transcript.jsonl'

fb_lines = {}

with open(log_file, 'r', encoding='utf-8') as f:
    for line in f:
        # We look for "File Path: `file:///.../fb_groups.php`"
        if 'fb_groups.php' in line:
            # decode json to make it easier
            try:
                data = json.loads(line)
            except:
                continue
            
            def extract(text):
                if not isinstance(text, str): return
                if 'fb_groups.php' in text and '<line_number>:' in text:
                    # extract lines
                    matches = re.findall(r'^(\d+):\s(.*)$', text, re.MULTILINE)
                    for m in matches:
                        line_num = int(m[0])
                        content = m[1]
                        # only add if not empty or if it's new
                        if line_num not in fb_lines or len(content) > len(fb_lines[line_num]):
                            fb_lines[line_num] = content
            
            def walk(obj):
                if isinstance(obj, dict):
                    for k, v in obj.items():
                        extract(v)
                        walk(v)
                elif isinstance(obj, list):
                    for v in obj:
                        extract(v)
                        walk(v)
            walk(data)

print(f"Captured {len(fb_lines)} lines")
with open('recovered2.php', 'w', encoding='utf-8') as out:
    for i in range(1, max(fb_lines.keys()) + 1):
        out.write(fb_lines.get(i, '') + '\n')
