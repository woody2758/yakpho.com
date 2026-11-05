import sys

file_path = 'd:/xampp/htdocs/yakpho.com/staff/post_world_groups.php'
with open(file_path, 'r', encoding='utf-8') as f:
    lines = f.readlines()

new_lines = []
skip = False

for i, line in enumerate(lines):
    # Detect start of left column
    if '<!-- List Groups -->' in line:
        skip = True
        continue
    
    # Detect start of right column
    if '<!-- Job Queue -->' in line:
        skip = False
    
    if skip:
        continue
    
    # Modify col-md-5 to col-12 for Job Queue
    if 'class="col-md-5"' in line and not skip:
        line = line.replace('class="col-md-5"', 'class="col-md-12"')
    
    new_lines.append(line)

with open(file_path, 'w', encoding='utf-8') as f:
    f.writelines(new_lines)

print('Updated post_world_groups.php successfully.')
