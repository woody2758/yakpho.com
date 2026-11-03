<?php
include_once __DIR__ . "/includes/config.php";
include_once __DIR__ . "/includes/api_helper.php";

$bsRes = fetch_esther_api('brandstory_content.php', ['lang' => CURRENT_LANG]);
$bsData = $bsRes ? ($bsRes['data'] ?? []) : [];

$bs_title = !empty($bsData['title']) ? $bsData['title'] : 'Brand Story';
$bs_subtitle = !empty($bsData['subtitle']) ? $bsData['subtitle'] : 'Touch of Thai Wellness';
$bs_image = !empty($bsData['image_url']) ? $bsData['image_url'] : 'assets/img/brand_story.webp';

if (!empty($bsData['html_content'])) {
    $bs_content = $bsData['html_content'];
} else {
    $bs_content = '
        <p class="p">
          YakPho Aroma เชื่อว่าการนวดไทยไม่ใช่เพียงการผ่อนคลายกล้ามเนื้อ  
          แต่คือ <strong>ศิลปะแห่งการบำบัดใจและร่างกาย</strong> ที่เชื่อมโยงคนกับธรรมชาติอย่างลึกซึ้ง  
          สัมผัสของมือช่างนวดคือพลังแห่งความตั้งใจ — และกลิ่นสมุนไพรคือถ้อยคำที่บอกว่า  
          <em>“คุณปลอดภัยแล้ว... จงพักผ่อนและหายใจได้อย่างเป็นอิสระอีกครั้ง”</em>
        </p>

        <p class="p">
          เราจึงตั้งใจสร้างบาล์มสมุนไพรที่ไม่ใช่เพียง <strong>ผลิตภัณฑ์</strong>  
          แต่เป็น <strong>เครื่องมือแห่งการเยียวยา</strong> ให้ร้านนวดและสปาทั่วโลก  
          ได้ส่งต่อความรู้สึกแบบเดียวกับที่คนไทยรู้จักมานับร้อยปี —  
          ความอบอุ่นของไพล กลิ่นเย็นสดชื่นของเมนทอล และสัมผัสนุ่มมือที่เต็มไปด้วยความใส่ใจ
        </p>

        <h3 class="h3">ทำไมเราทำเพื่อต่างประเทศ</h3>
        <p class="p">
          ร้านนวดไทยในต่างแดนคือ <strong>ทูตแห่งวัฒนธรรม</strong> ที่ใช้หัวใจในการสื่อสาร  
          แต่พวกเขาขาดสิ่งหนึ่ง — <em>บาล์มที่เข้าใจหัวใจของช่างนวดไทยจริง ๆ</em><br>
          YakPho Aroma จึงถือกำเนิดขึ้น เพื่อสร้างบาล์มที่มี <strong>กลิ่นแบบสปาไทยแท้</strong>,  
          <strong>สัมผัสลื่นมือไม่เหนียว</strong>, และ <strong>ปลอดภัยต่อมือช่างนวด</strong>  
          ทุกสูตรถูกคิดขึ้นจากประสบการณ์จริงของช่างนวดมืออาชีพ
        </p>

        <h3 class="h3">เพื่อให้ลูกค้าของร้านนวด ได้รับมากกว่าความผ่อนคลาย</h3>
        <p class="p">
          บาล์มของ YakPho Aroma ถูกออกแบบให้กลิ่นค่อย ๆ คลี่ตัว  
          จากสมุนไพรไทยพื้นบ้านสู่โทนอโรม่าสากล  
          เพื่อให้ลูกค้าในยุโรป อเมริกา หรือออสเตรเลีย  
          ได้หลับตาแล้วรู้สึกเหมือนอยู่ในสปาใจกลางเชียงใหม่หรือริมทะเลภูเก็ต  
          ทุกการนวดจึงไม่ใช่แค่การบริการ แต่คือ <strong>การเดินทางของความรู้สึก</strong>  
          จากมือช่างไทย... สู่หัวใจของคนทั่วโลก
        </p>

        <blockquote class="p muted" style="margin-top:10px;">
          “เราสร้างบาล์มที่ไม่ใช่แค่หอม...  
          แต่เป็นกลิ่นของความตั้งใจ ความอบอุ่น และความเป็นไทยที่สัมผัสได้”
        </blockquote>
    ';
}
?>

<div class="wrap grid-2">
  <!-- ด้านซ้าย : ภาพประกอบ -->
  <div class="reveal">
    <h2 class="h2"><?= htmlspecialchars($bs_title) ?> <div class="text-brand"><?= htmlspecialchars($bs_subtitle) ?></div></h2>
    <div class="brand-photo">
      <img src="<?= htmlspecialchars($bs_image) ?>" alt="<?= htmlspecialchars($bs_subtitle) ?>">
    </div>
  </div>

  <!-- ด้านขวา : เนื้อหา -->
  <div class="reveal">
    <div class="brand-body">
      <?= $bs_content ?>
    </div>
  </div>
</div>
