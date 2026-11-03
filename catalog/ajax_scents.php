<?php
include __DIR__ . "/includes/config.php";
include __DIR__ . "/includes/api_helper.php";

$scentsRes = fetch_esther_api('scents_content.php', ['lang' => CURRENT_LANG]);
$scentsIntro = $scentsRes ? ($scentsRes['data']['intro'] ?? []) : [];
$scentsItems = $scentsRes ? ($scentsRes['data']['items'] ?? []) : [];

$title = $scentsIntro['title'] ?? 'กลิ่นยอดนิยม (12 แบบ)';
$subtitle = $scentsIntro['subtitle'] ?? '';
$description = $scentsIntro['description'] ?? '';

// If no items, load fallback items from the original index.php for TH language
if (empty($scentsItems) && CURRENT_LANG === 'th') {
    $fallbackScents = [
        ["01. สีขาว – ต้นตำรับ","ทารากอนออย การบูร พิมเสน เมนทอล ยูคาลิปตัส เปปเปอร์มิ้น กานพูล อบเชย และส่วนผสมต้นตำรับยักษ์โพธิ์", "1.webp"],
        ["02. สีเหลือง – ไพล","กลิ่นสมุนไพรอุ่น ๆ แบบไทย กลิ่นหอมลึกของไพลและน้ำมันระกำน้ำมันสกัดขมิ้นชัน", "2.webp"],
        ["03. สีเขียว – เสลดพังพอน","ผสานกลิ่นอโวคาโด แมกโนเลีย มะลิ และอื่นๆ สัดส่วนที่ลงตัว", "3.webp"],
        ["04. เขียวอ่อน – ตะไคร้หอม","กลิ่นสะอาด สดชื่นแบบสปา ผสานกลิ่นซิตรัสของตะไคร้หอม", "4.webp"],
        ["05. เขียวอ่อน – หญ้าเอ็นยืด","กลิ่นผลไม้รวมผสานกับแอปเปิลแมกโนเลีย", "5.webp"],
        ["06. ม่วงอ่อน – ลาเวนเดอร์","กลิ่นหอมแบบยุโรป อบอวลด้วยลาเวนเดอร์แท้ ให้ความรู้สึกผ่อนคลาย", "6.webp"],
        ["07. สีเขียว – ยูคาลิปตัส","กลิ่นสดชื่น ปลอดโปร่ง ช่วยให้หายใจโล่งและปลุกความสดใส", "7.webp"],
        ["08. สีขาว – มะลิ","กลิ่นดอกไม้ไทยหอมหวาน ละมุนละไม สื่อถึงความอ่อนโยนและสง่างาม", "8.webp"],
        ["09. ชมพูอ่อน – กุหลาบ","กลิ่นหอมโรแมนติก อบอวลด้วยกลิ่นกลีบกุหลาบ ให้ความรู้สึกนุ่มนวล", "9.webp"],
        ["10. เหลืองอ่อน – ขิงมินท์","ผสมความอุ่นของขิงและความเย็นของมินท์ กลมกล่อม สดชื่น", "10.webp"],
        ["11. สีขาว – ดอกโมก","กลิ่นดอกไม้ไทยบริสุทธิ์ อ่อนละมุน มีเอกลักษณ์เฉพาะของโมกไทย", "11.webp"],
        ["12. สีขาว – น้ำมันมะพร้าว","กลิ่นธรรมชาติบริสุทธิ์ของมะพร้าว หอมเบา ๆ เหมือนชายหาดเมืองร้อน", "12.webp"],
    ];
    foreach($fallbackScents as $i => $s){
        $scentsItems[] = [
            'image_url' => 'assets/img/scents/' . $s[2],
            'title' => $s[0],
            'subtitle' => $s[1]
        ];
    }
}
?>

<div class="wrap">
  <div class="text-center" style="margin-bottom: 24px;">
      <h2 class="h2 center"><?= htmlspecialchars($title) ?></h2>
      <?php if (!empty($subtitle)): ?>
          <p class="p center muted" style="margin-top: 4px;"><?= nl2br(htmlspecialchars($subtitle)) ?></p>
      <?php endif; ?>
      <?php if (!empty($description)): ?>
          <p class="p center muted" style="margin-top: 8px; font-size: 0.95em;"><?= nl2br(htmlspecialchars($description)) ?></p>
      <?php endif; ?>
  </div>

  <div class="grid-4">
    <?php foreach($scentsItems as $item): ?>
      <div class="card reveal">
        <div class="body">
          <div class="scent-image">
            <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">
          </div>
          <strong class="h3" style="font-size:16px;margin:12px 0 6px;display:block;"><?= htmlspecialchars($item['title']) ?></strong>
          <span class="p"><?= nl2br(htmlspecialchars($item['subtitle'])) ?></span>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
// Trigger reveal animation for newly loaded content
setTimeout(() => {
    const reveals = document.querySelectorAll('#scents .reveal');
    reveals.forEach(r => r.classList.add('show'));
}, 100);
</script>
