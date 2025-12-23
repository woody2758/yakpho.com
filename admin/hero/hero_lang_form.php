<?php
// Language form fields for hero slides
$lang = $lang ?? 'th';
?>
<div class="mb-3">
    <label class="form-label">หัวข้อ</label>
    <input type="text" class="form-control" name="slide_title_<?= $lang ?>" id="slide_title_<?= $lang ?>" placeholder="Biblical Wellness from Ancient Soil...">
</div>
<div class="mb-3">
    <label class="form-label">คำบรรยาย</label>
    <textarea class="form-control" name="slide_subtitle_<?= $lang ?>" id="slide_subtitle_<?= $lang ?>" rows="3" placeholder="ผลิตภัณฑ์สุขภาพและความงาม..."></textarea>
</div>
<div class="mb-3">
    <label class="form-label">ปุ่ม 1 - ข้อความ</label>
    <input type="text" class="form-control" name="button1_text_<?= $lang ?>" id="button1_text_<?= $lang ?>" placeholder="เลือกซื้อผลิตภัณฑ์">
</div>
<?php if ($lang === 'th'): ?>
<div class="mb-3">
    <label class="form-label">ปุ่ม 1 - ลิงก์</label>
    <input type="text" class="form-control" name="button1_link" id="button1_link" placeholder="/yakpho.com/shop/">
</div>
<?php endif; ?>
<div class="mb-3">
    <label class="form-label">ปุ่ม 2 - ข้อความ</label>
    <input type="text" class="form-control" name="button2_text_<?= $lang ?>" id="button2_text_<?= $lang ?>" placeholder="เรียนรู้เพิ่มเติม">
</div>
<?php if ($lang === 'th'): ?>
<div class="mb-3">
    <label class="form-label">ปุ่ม 2 - ลิงก์</label>
    <input type="text" class="form-control" name="button2_link" id="button2_link" placeholder="/yakpho.com/about/">
</div>
<?php endif; ?>
