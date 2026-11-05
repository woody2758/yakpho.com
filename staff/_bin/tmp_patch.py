import re
import sys

file_path = r'd:\xampp\htdocs\yakpho.com\staff\creative_manage.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Update Gallery Select (around line 344)
old_gallery = '''<div class="d-flex align-items-center gap-3 p-3 bg-light rounded border">
                <input type="file" id="upload-input" class="form-control" accept="image/*" multiple>'''

new_gallery = '''<div class="d-flex align-items-center gap-3 p-3 bg-light rounded border">
                <select id="gallery_zone_select" class="form-select fw-bold" style="width:160px;">
                  <option value="All">🌐 ใช้ได้ทั้งหมด</option>
                  <option value="Thailand">🇹🇭 ไทย</option>
                  <option value="Global">🌍 ต่างประเทศ</option>
                </select>
                <input type="file" id="upload-input" class="form-control" accept="image/*" multiple>'''
content = content.replace(old_gallery, new_gallery)

# 2. Update Image DB Save (around line 33)
old_img_save = '''$stmt = $db->prepare("INSERT INTO creative_images (file_path) VALUES (?)");
            $stmt->execute([$target]);'''

new_img_save = '''$zone = $_POST['target_zone'] ?? 'All';
            $stmt = $db->prepare("INSERT INTO creative_images (file_path, target_zone) VALUES (?, ?)");
            $stmt->execute([$target, $zone]);'''
content = content.replace(old_img_save, new_img_save)

# 3. Update Gallery Display badges (around line 359)
old_img_card = '''<div class="img-card shadow-sm">
                <img src="<?= htmlspecialchars($display_src) ?>" loading="lazy"'''

new_img_card = '''<div class="img-card shadow-sm">
                <?php if($img['target_zone'] == 'Thailand'): ?>
                  <span class="badge bg-info-lt position-absolute top-0 start-0 m-1" style="z-index: 10;">🇹🇭 ไทย</span>
                <?php elseif($img['target_zone'] == 'Global'): ?>
                  <span class="badge bg-orange-lt position-absolute top-0 start-0 m-1" style="z-index: 10;">🌍 นอก</span>
                <?php endif; ?>
                <img src="<?= htmlspecialchars($display_src) ?>" loading="lazy"'''
content = content.replace(old_img_card, new_img_card)

# 4. Update JS for image upload zone (around line 763)
old_js_upload = '''if (uploadMode === 'gallery') {
            formData.append('cropped_image', blob, 'image.jpg');
        } else {'''

new_js_upload = '''if (uploadMode === 'gallery') {
            formData.append('cropped_image', blob, 'image.jpg');
            const zoneSel = document.getElementById('gallery_zone_select');
            if(zoneSel) formData.append('target_zone', zoneSel.value);
        } else {'''
content = content.replace(old_js_upload, new_js_upload)

# 5. Add zone to Add Content Form (around line 386)
old_add_form = '''<div class="mb-3">
                    <label class="form-label">แท็กแคมเปญ (Campaign Tag)</label>'''

new_add_form = '''<div class="mb-3">
                    <label class="form-label">โซน (Zone)</label>
                    <select name="target_zone" id="content_zone_select" class="form-select fw-bold">
                      <option value="All">🌐 ใช้ได้ทั้งหมด</option>
                      <option value="Thailand">🇹🇭 ไทย (Thailand)</option>
                      <option value="Global">🌍 ต่างประเทศ (Global)</option>
                    </select>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">แท็กแคมเปญ (Campaign Tag)</label>'''
content = content.replace(old_add_form, new_add_form)

# 6. Content Add Backend (around line 71)
old_content_add = '''$campaign_tag = !empty($_POST['campaign_tag']) ? $_POST['campaign_tag'] : null;
        if (trim($content_text) !== '') {
            $stmt = $db->prepare("INSERT INTO creative_contents (ai_strategy, content_text, campaign_tag) VALUES (?, ?, ?)");
            $stmt->execute([$strategy, $content_text, $campaign_tag]);'''

new_content_add = '''$target_zone = $_POST['target_zone'] ?? 'All';
        $campaign_tag = !empty($_POST['campaign_tag']) ? $_POST['campaign_tag'] : null;
        if (trim($content_text) !== '') {
            $stmt = $db->prepare("INSERT INTO creative_contents (ai_strategy, target_zone, content_text, campaign_tag) VALUES (?, ?, ?, ?)");
            $stmt->execute([$strategy, $target_zone, $content_text, $campaign_tag]);'''
content = content.replace(old_content_add, new_content_add)

# 7. Edit Modal HTML addition (I need to find the edit content modal)
# The modal is at id="editContentModal", we will replace inside its body
old_edit_modal = '''<input type="hidden" id="edit_content_id">
            <div class="mb-3">
              <label class="form-label">แท็กแคมเปญ (Campaign Tag)</label>'''

new_edit_modal = '''<input type="hidden" id="edit_content_id">
            <div class="mb-3">
              <label class="form-label">โซน (Zone)</label>
              <select id="edit_target_zone" class="form-select fw-bold">
                <option value="All">🌐 ใช้ได้ทั้งหมด</option>
                <option value="Thailand">🇹🇭 ไทย (Thailand)</option>
                <option value="Global">🌍 ต่างประเทศ (Global)</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">แท็กแคมเปญ (Campaign Tag)</label>'''
if old_edit_modal in content:
    content = content.replace(old_edit_modal, new_edit_modal)
else:
    # try another format
    old_edit_modal2 = '''<input type="hidden" id="edit_content_id">
          <div class="mb-3">
            <label class="form-label">แท็กแคมเปญ (Campaign Tag)</label>'''
    content = content.replace(old_edit_modal2, new_edit_modal)

# 8. Update Edit Content Backend (around line 85)
old_content_edit = '''$id = $_POST['id'];
        $content_text = $_POST['content_text'] ?? '';
        $campaign_tag = !empty($_POST['campaign_tag']) ? $_POST['campaign_tag'] : null;
        if (trim($content_text) !== '') {
            $stmt = $db->prepare("UPDATE creative_contents SET content_text = ?, campaign_tag = ? WHERE id = ?");
            $stmt->execute([$content_text, $campaign_tag, $id]);'''

new_content_edit = '''$id = $_POST['id'];
        $target_zone = $_POST['target_zone'] ?? 'All';
        $content_text = $_POST['content_text'] ?? '';
        $campaign_tag = !empty($_POST['campaign_tag']) ? $_POST['campaign_tag'] : null;
        if (trim($content_text) !== '') {
            $stmt = $db->prepare("UPDATE creative_contents SET target_zone = ?, content_text = ?, campaign_tag = ? WHERE id = ?");
            $stmt->execute([$target_zone, $content_text, $campaign_tag, $id]);'''
content = content.replace(old_content_edit, new_content_edit)

# 9. Update openEditModal JS (around line 594)
old_open_edit_js = '''function openEditModal(id, text, tag) {'''
new_open_edit_js = '''function openEditModal(id, text, tag, zone) {
    document.getElementById('edit_target_zone').value = zone || 'All';'''
content = content.replace(old_open_edit_js, new_open_edit_js)

# 10. Update Edit Form Submit JS (around line 619)
old_edit_submit_js = '''fd.append('campaign_tag', document.getElementById('edit_campaign_tag').value);'''
new_edit_submit_js = '''fd.append('campaign_tag', document.getElementById('edit_campaign_tag').value);
    fd.append('target_zone', document.getElementById('edit_target_zone').value);'''
content = content.replace(old_edit_submit_js, new_edit_submit_js)

# 11. Update openEditModal button call (around line 209)
old_btn_edit = '''onclick="openEditModal(<?= $c['id'] ?>, <?= htmlspecialchars(json_encode($c['content_text']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($c['campaign_tag'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)"'''
new_btn_edit = '''onclick="openEditModal(<?= $c['id'] ?>, <?= htmlspecialchars(json_encode($c['content_text']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($c['campaign_tag'] ?? ''), ENT_QUOTES, 'UTF-8') ?>, '<?= htmlspecialchars($c['target_zone'] ?? 'All') ?>')"'''
content = content.replace(old_btn_edit, new_btn_edit)


# 12. Display badges on contents (around line 184)
old_content_badges = '''<div style="white-space: pre-wrap;"><?= htmlspecialchars($c['content_text']) ?></div>
                      <?php if(!empty($c['campaign_tag'])): ?>'''
new_content_badges = '''<div style="white-space: pre-wrap;"><?= htmlspecialchars($c['content_text']) ?></div>
                      <div class="mt-2 d-flex flex-wrap gap-2">
                        <?php if($c['target_zone'] == 'Thailand'): ?>
                            <span class="badge bg-info-lt">🇹🇭 ไทย</span>
                        <?php elseif($c['target_zone'] == 'Global'): ?>
                            <span class="badge bg-orange-lt">🌍 ต่างประเทศ</span>
                        <?php else: ?>
                            <span class="badge bg-secondary-lt">🌐 ทั้งหมด</span>
                        <?php endif; ?>
                      </div>
                      <?php if(!empty($c['campaign_tag'])): ?>'''
content = content.replace(old_content_badges, new_content_badges)


with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Patched successfully")
