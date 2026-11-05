import json
import re
import sys

log_file = r'C:\Users\Windows 10\.gemini\antigravity-ide\brain\bca73452-7a45-4378-bd81-8c25b863aee2\.system_generated\logs\transcript.jsonl'
lines = []

try:
    with open(log_file, 'r', encoding='utf-8') as f:
        for line in f:
            lines.append(json.loads(line))
except Exception as e:
    print(e)
    sys.exit(1)

fb_lines = {}
max_line = 0

for step in lines:
    if step.get('type') == 'PLANNER_RESPONSE':
        if 'tool_calls' in step:
            pass # tool calls are here
    elif step.get('type') == 'TOOL_CALL_RESPONSE' or step.get('type') == 'SYSTEM' or step.get('source') == 'SYSTEM':
        pass # need to figure out what type tool responses are

    # Let's just blindly search all string values for 'fb_groups.php' and line numbers.
    s = json.dumps(step)
    if 'fb_groups.php' in s and '<line_number>:' in s or 'The following code has been modified to include a line number' in s:
        # It's a view_file output!
        # find the output text. Usually in 'content' or 'output'
        # let's extract all matches of `^\d+: `
        matches = re.findall(r'^(\d+):\s(.*)$', s, re.MULTILINE)
        # However, s is json, so newlines are \n
        
        # better to do recursive search for dict values that are strings
        def search_dict(d):
            if isinstance(d, dict):
                for k, v in d.items():
                    if isinstance(v, str):
                        if 'The following code has been modified to include a line number' in v and 'fb_groups.php' in v:
                            m = re.findall(r'^(\d+):\s(.*)$', v, re.MULTILINE)
                            for num, text in m:
                                fb_lines[int(num)] = text
                    search_dict(v)
            elif isinstance(d, list):
                for item in d:
                    search_dict(item)
                    
        search_dict(step)

    # We also need to apply replace_file_content modifications.
    # Look for diff_block_start
    if 'diff_block_start' in s and 'fb_groups.php' in s:
        # this is complicated. Instead of applying diffs, can I just find the LAST view_file?
        # Actually I did `multi_replace_file_content` which modifies the file. The file is lost.
        # But wait, does the IDE save backups in some other folder?
        pass

print(f"Captured lines: {len(fb_lines)}")
if fb_lines:
    print(f"Max line: {max(fb_lines.keys())}")
    with open('recovered.php', 'w', encoding='utf-8') as f:
        for i in range(1, max(fb_lines.keys()) + 1):
            f.write(fb_lines.get(i, '') + '\n')
