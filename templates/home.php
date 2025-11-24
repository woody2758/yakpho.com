<section class="hero-section">
<div class="hero-bg"></div>
<div class="hero-content">
<h1 class="hero-title">YakPho Aroma</h1>
<p class="hero-sub">Premium Thai Herbal Wellness for Massage Shops Worldwide</p>
<a href="<?= URL_PATH ?>/<?= $lang ?>/products" class="btn-primary">ดูสินค้า</a>
</div>
</section>


<section class="features">
<div class="container">
<h2 class="section-title">ทำไมต้อง YakPho?</h2>
<div class="feature-grid">
<div class="feature-item">
<img src="<?= URL_PATH ?>/assets/img/ico-natural.png" class="feature-icon">
<h3>สมุนไพรแท้</h3>
<p>ผลิตจากวัตถุดิบไทยแท้ ปลอดสารอันตราย ใช้ได้กับทุกสภาพผิว</p>
</div>
<div class="feature-item">
<img src="<?= URL_PATH ?>/assets/img/ico-massage.png" class="feature-icon">
<h3>ออกแบบเพื่อร้านนวด</h3>
<p>ครีมนวดและบาล์มสูตรพิเศษ เพื่อผู้ประกอบการนวดไทยทั่วโลก</p>
</div>
<div class="feature-item">
<img src="<?= URL_PATH ?>/assets/img/ico-premium.png" class="feature-icon">
<h3>มาตรฐานระดับสากล</h3>
<p>ผลิตตามมาตรฐานโรงงาน GMP พร้อมส่งออกได้ทั่วโลก</p>
</div>
</div>
</div>
</section>


<section class="highlight-products">
<div class="container">
<h2 class="section-title">12 กลิ่นพรีเมียม</h2>
<div class="product-grid">
<?php foreach (load_json('scents.json') as $s): ?>
<a href="<?= URL_PATH ?>/<?= $lang ?>/product/<?= $s['slug'] ?>" class="product-card">
<div class="product-img" style="background-image:url('<?= URL_PATH ?>/assets/img/scents/<?= $s['slug'] ?>.jpg');"></div>
<h3><?= $s['name'] ?></h3>
</a>
<?php endforeach; ?>
</div>
</div>
</section>


<section class="cta-section">
<h2>ต้องการสั่งจำนวนมากสำหรับร้านนวด?</h2>
<p>เรทราคาส่ง และส่วนลดพิเศษสำหรับผู้ประกอบการ</p>
<a href="<?= URL_PATH ?>/<?= $lang ?>/contact" class="btn-primary">ติดต่อเรา</a>
</section>


