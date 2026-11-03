<?php
include __DIR__ . "/includes/config.php";
include __DIR__ . "/includes/api_helper.php";

$formulaRes = fetch_esther_api('formula_content.php', ['lang' => CURRENT_LANG]);
$formulaSlides = $formulaRes ? ($formulaRes['data']['items'] ?? []) : [];
$formulaIntro = $formulaRes ? ($formulaRes['data']['intro'] ?? []) : [];

$f_title = !empty($formulaIntro['intro_title']) ? $formulaIntro['intro_title'] : '🌿 3 สูตรพิเศษสำหรับร้านนวด';
$f_subtitle = !empty($formulaIntro['intro_subtitle']) ? $formulaIntro['intro_subtitle'] : '"เพราะความหอมคือหัวใจของการบำบัด"';
$f_desc = !empty($formulaIntro['intro_description']) ? $formulaIntro['intro_description'] : "ทุกสูตรของเราผสาน <strong>“Tarragon Infused Oil”</strong> (น้ำมันสกัดทาร์รากอน) สูตรลับจาก <em>เอสเธอร์ ทารากอน ฟาร์ม</em> \nทำหน้าที่เป็นตัวประสาน (Harmonizer) เชื่อมโยงสมุนไพรไทยเข้ากับความหอมละมุนระดับสากล \nมอบสัมผัสที่ลึกซึ้งและแตกต่าง ให้ทุกการนวดเป็นช่วงเวลาที่ลูกค้าประทับใจไม่รู้ลืม";
?>
  <div class="wrap">
    <h2 class="h2 center"><?= htmlspecialchars($f_title) ?></h2>
    
    <div style="max-width: 800px; margin: 0 auto 25px auto; text-align: center;">
      <p class="p" style="font-size: 1.1em; color: #2E5A38; margin-bottom: 5px;">
        <strong><?= htmlspecialchars($f_subtitle) ?></strong>
      </p>
      <p class="p" style="white-space: pre-line; margin-top: 0;">
        <?= nl2br($f_desc) ?>
      </p>
    </div>

    <div class="grid-3">
      <?php if (!empty($formulaSlides)): ?>
        <?php foreach ($formulaSlides as $slide): ?>
          <div class="card reveal show line-<?= htmlspecialchars($slide['css_class'] ?? 'default') ?>">
            <img src="<?= htmlspecialchars($slide['image_url'] ?? '') ?>" alt="<?= htmlspecialchars($slide['slide_title'] ?? '') ?>">
            <div class="body">
              <h3 class="h3" style="margin-bottom: 8px;"><?= htmlspecialchars($slide['slide_title'] ?? '') ?></h3>
              <p class="p" style="white-space: pre-line; margin-top: 0;">
                <?= htmlspecialchars($slide['slide_subtitle'] ?? '') ?>
              </p>
              <?php if (!empty($slide['badges_array'])): ?>
              <ul class="p bullet">
                <?php foreach ($slide['badges_array'] as $bullet): ?>
                <li><?= htmlspecialchars($bullet) ?></li>
                <?php endforeach; ?>
              </ul>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="card reveal show line-hot">
          <img src="assets/img/best_h001.webp" alt="สูตรร้อน – The Original Hot">
          <div class="body">
            <h3 class="h3" style="margin-bottom: 8px;">🔥 The Original Hot (สูตรร้อน)</h3>
            <p class="p" style="margin-top: 0;">
              การโคจรมาพบกันของ <strong>'ราชันย์สมุนไพรไทย'</strong> อย่างไพลสดและขมิ้นชัน ผสานเข้ากับ <strong>Tarragon Infused Oil</strong> 
              ช่วยลดทอนกลิ่นฉุนดั้งเดิม ให้กลายเป็นความหอมนุ่มลึก ร้อนลึกเข้าถึงจุดปวดเมื่อย 
              แต่ไม่แสบผิว ช่วยคลายเส้นที่ตึงเครียดสะสมได้อย่างชะงัด
            </p>
            <ul class="p bullet">
              <li>ร้อนลึก คลายเส้น</li>
              <li>หอมนุ่ม ไม่ฉุน</li>
              <li>เบาสบายตัว</li>
            </ul>
          </div>
        </div>

        <div class="card reveal show line-cool">
          <img src="assets/img/best_h002.webp" alt="สูตรเย็น – The Refreshing Cold">
          <div class="body">
            <h3 class="h3" style="margin-bottom: 8px;">❄️ The Refreshing Cold (สูตรเย็น)</h3>
            <p class="p" style="margin-top: 0;">
              ความเย็นซ่าบริสุทธิ์ของ <strong>เปปเปอร์มินต์และยูคาลิปตัส</strong> ถูกโอบอุ้มด้วยกลิ่นเขียวขจีที่เป็นเอกลักษณ์ของ 
              <strong>Tarragon Infused Oil</strong> เปรียบเสมือนการสูดอากาศบริสุทธิ์กลางทุ่งสมุนไพร 
              ช่วยเปิดทางเดินหายใจ และรีเซ็ตความเหนื่อยล้าให้กลับมามีพลังอีกครั้ง
            </p>
            <ul class="p bullet">
              <li>เย็นซ่า สดชื่น</li>
              <li>โล่งจมูก ผ่อนคลาย</li>
              <li>รีเซ็ตพลังงาน</li>
            </ul>
          </div>
        </div>

        <div class="card reveal show line-massage">
          <img src="assets/img/best_h003.webp" alt="สูตรนวดตัว – The Body Flow">
          <div class="body">
            <h3 class="h3" style="margin-bottom: 8px;">💆‍♀️ The Body Flow (สูตรนวดตัว)</h3>
            <p class="p" style="margin-top: 0;">
              นวัตกรรม <strong>Balm-to-Oil</strong> ที่เปลี่ยนเนื้อบาล์มเป็นน้ำมันเคลือบผิวบางเบา 
              ผสานน้ำมันธรรมชาติ 3 ชนิดและ <strong>Tarragon Infused Oil</strong> ช่วยปลอบประโลมผิว (Soothing) 
              ให้สัมผัสที่ลื่นไหลต่อเนื่อง ไม่สะดุด บำรุงผิวให้นุ่มชุ่มชื้นแต่ไม่เหนียวเหนอะหนะ
            </p>
            <ul class="p bullet">
              <li>สัมผัสลื่นไหล</li>
              <li>บำรุงผิวชุ่มชื้น</li>
              <li>ผ่อนคลายล้ำลึก</li>
            </ul>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
