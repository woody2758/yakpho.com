<?php
// Yakpho Aroma v2 – E‑Catalog (Stagetimer-style theme)
?>
<?php 
include __DIR__ . "/includes/config.php"; 
include __DIR__ . "/includes/api_helper.php"; 
include __DIR__ . "/includes/header.php"; 

// Fetch Active Languages
$langRes = fetch_esther_api('languages.php');
$active_languages = ($langRes && isset($langRes['data']['items'])) ? $langRes['data']['items'] : [];
?>

<style>
.loader-container { display: flex; justify-content: center; align-items: center; min-height: 200px; width: 100%; }
.loader-spinner { border: 4px solid #f3f3f3; border-top: 4px solid #2E5A38; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>

<!-- ================================
   🔍 Topbar (Search + Brand)
================================ -->
<div class="topbar">
  <div class="inner">
    <a href="./">
<div class="brand">
   <img src="assets/img/YakPhoAromaLogo.webp" alt="YAKPHO Logo" class="brand-logo">

  <div class="brand-text">
    <div class="brand-line1">YAKPHO</div>
    <div class="brand-line2">Aroma Balm</div>
  </div>

</div>
</a>



<div class="search-wrap" style="display: flex; align-items: center; gap: 10px;">
  <!-- Language Switcher -->
  <?php if (!empty($active_languages)): ?>
  <div class="lang-dropdown">
    <?php 
        // Find current language name and flag
        $current_lang_name = 'TH';
        $current_flag = 'th';
        foreach ($active_languages as $l) {
            if ($l['lang_code'] == CURRENT_LANG) {
                $current_lang_name = strtoupper($l['lang_code']);
                $code = strtolower($l['lang_code']);
                $current_flag = $code;
                if ($code == 'en') $current_flag = 'gb';
                if ($code == 'zh') $current_flag = 'cn';
                if ($code == 'ja') $current_flag = 'jp';
                if ($code == 'ko') $current_flag = 'kr';
            }
        }
    ?>
    <button class="lang-btn">
      <span class="fi fi-<?= $current_flag ?>"></span>
      <span class="lang-text"><?= $current_lang_name ?></span>
      <i data-lucide="chevron-down" style="width: 14px; height: 14px;"></i>
    </button>
    <div class="lang-menu">
      <?php foreach ($active_languages as $l): 
          $code = strtolower($l['lang_code']);
          $flag = $code;
          if ($code == 'en') $flag = 'gb';
          if ($code == 'zh') $flag = 'cn';
          if ($code == 'ja') $flag = 'jp';
          if ($code == 'ko') $flag = 'kr';
      ?>
          <a href="?lang=<?= $code ?>" class="<?= CURRENT_LANG == $code ? 'active' : '' ?>">
              <span class="fi fi-<?= $flag ?>"></span>
              <?= htmlspecialchars($l['lang_name'] ?? strtoupper($code)) ?>
          </a>
      <?php endforeach; ?>
    </div>
  </div>
  
  <style>
  .lang-dropdown {
      position: relative;
      display: inline-block;
  }
  .lang-btn {
      display: flex;
      align-items: center;
      gap: 6px;
      background: #fff;
      border: 1px solid #ddd;
      padding: 6px 12px;
      border-radius: 20px;
      cursor: pointer;
      font-size: 14px;
      font-weight: 500;
      color: #333;
      transition: all 0.2s;
  }
  .lang-btn:hover {
      background: #f8f9fa;
      border-color: #ccc;
  }
  .lang-btn .fi {
      font-size: 1.2em;
      border-radius: 2px;
  }
  .lang-menu {
      position: absolute;
      top: 100%;
      right: 0;
      margin-top: 8px;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
      border: 1px solid #eee;
      min-width: 140px;
      opacity: 0;
      visibility: hidden;
      transform: translateY(-10px);
      transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
      z-index: 1000;
      overflow: hidden;
  }
  .lang-dropdown:hover .lang-menu {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
  }
  .lang-menu a {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 16px;
      color: #333;
      text-decoration: none;
      font-size: 14px;
      transition: background 0.2s;
  }
  .lang-menu a:hover {
      background: #f5f5f5;
  }
  .lang-menu a.active {
      background: #f0f7f2;
      color: #2E5A38;
      font-weight: 500;
  }
  .lang-menu a .fi {
      font-size: 1.2em;
      border-radius: 2px;
  }
  </style>
  <?php endif; ?>

  <div class="search-box">
    <input type="text" id="pageSearch" class="search-input" placeholder="ค้นหา...">
    <i class="search-icon" data-lucide="search"></i>
  </div>
</div>



  </div>
</div>


<main>

<!-- HERO -->
<section class="section hero" id="home" style="margin-bottom: 40px; position: relative;">
  <div class="loader-container"><div class="loader-spinner"></div></div>
</section>


<section class="section formula" id="lines">
  <div class="loader-container"><div class="loader-spinner"></div></div>
</section>


  <!-- SCENTS -->
  <section class="section" id="scents">
    <div class="loader-container"><div class="loader-spinner"></div></div>
  </section>
<!-- ============================================================
💰 YakPho Aroma – เรทราคาส่ง (v2.4)
============================================================ -->
<section id="pricing" class="section has-bg-white">
  <?php include __DIR__ . '/ajax_pricing.php'; ?>
</section>

<!-- ============================================================
💎 Brand Story — Touch of Thai Wellness
============================================================ -->
<section id="brand-story" class="section has-bg-soft">
  <?php include __DIR__ . '/ajax_brandstory.php'; ?>
</section>





<!-- CONTACT / ABOUT -->
<section id="contact" class="section with-bg">
  <div class="wrap grid-2">

    <!-- 🏢 เกี่ยวกับบริษัท -->
    <div class="reveal">
      <div class="card">
<div class="body">
  <img src="assets/img/tarragon.webp" style="width: 100%;">
  <h2 class="h3">Yแคตตาล็อกบาล์มนวด & ยาหม่องสมุนไพรอโรม่า YAKPHŌ</h3>
  <h4 class="h4">ร้าน เอสเธอร์ อโรม่า</h4>
  <div class="p"><strong>Esther Aroma </strong></div>
 <p class="p">
  รวมพลัง<strong>สมุนไพรไทยแท้</strong> ทั้งไพล เสลดพังพอน และสารสกัดธรรมชาติ ผสานสูตรเฉพาะ <strong>“Tarragon Infused Oil”</strong> ให้สัมผัสยาหม่องบาล์มนวดที่คลายปวดเมื่อยได้อย่างตรงจุด เนื้อลื่นนวดได้นาน ไม่เหนียวเหนอะหนะ พร้อมกลิ่นหอมอโรม่าร่วมสมัยที่ไม่ฉุนแสบจมูก
</p>
  
  <p class="p"><strong>ทำไมต้องเป็นยาหม่องยักษ์โพธิ์อโรมาสมุนไพรไทย</strong></p>
  
  <ul class="p">
    <li><strong>The Secret Formula:</strong> โดดเด่นด้วย Tarragon Infused Oil รวมกับน้ำมันสกัดสมุนไพรไทยธรรมชาติ เอกลักษณ์ของเอสเธอร์ อโรม่า</li>
    <li><strong>Fresh & Crafted:</strong> ผลิตสดใหม่ทุกออเดอร์ (Freshly Made) ตามมาตรฐานความปลอดภัย เพื่อคุณค่าสูงสุด</li>
    <li><strong>Global Trust:</strong> การันตีคุณภาพ โดยคำสั่งซื้อจากร้านนวด ประเทศทั่วโลก (ยุโรป, อเมริกา, ออสเตรเลีย, นิวซีแลนด์ และเอเชีย)</li>
    <li><strong>Our Signature:</strong>ยาหม่องนวดสูตรร้อน, สูตรเย็นผ่อนคลาย และสูตรนวดตัว (Body Massage Balm)</li>
  </ul>
  
  <blockquote class="p muted" style="margin-top:12px;">
    “Empowering the World with Thai Herbal Wellness<br>
    ส่งต่อพลังบำบัดจากธรรมชาติไทยสู่สากล”
  </blockquote>
</div>
      </div>
    </div>

    <!-- 📞 ติดต่อเรา -->
    <div class="reveal">
      <div class="card">
        <div class="body">
          <h3 class="h3">ติดต่อเรา</h3>

          <p class="p">
            <b>เอสเธอร์ อโรม่า</b><br>เลขที่ 32/4 ถนนพระยาสุเรนทร์ แขวงสามวาตะวันตก เขตคลองสามวา กรุงเทพมหานคร 10510 ประเทศไทย<br>
            โทร: (+66) 80 061 7073
          </p>
          <p class="p">
            Line Official: <a href="https://lin.ee/kGIUwnf2" target="_blank">@yakpho</a><br>
            Facebook: <a href="https://www.facebook.com/YakPho.Aroma" target="_blank">YakPho Aroma</a><br>
            Email: <a href="mailto:thaiherbfc@gmail.com">thaiherbfc@gmail.com</a><br>
            WhatsApp: <a href="https://wa.me/660800617073" target="_blank">+66 080 061 7073</a>
          </p>



          <p class="p muted">
          <iframe src="https://www.google.com/maps/embed?pb=!1m17!1m12!1m3!1d1171.3928645250064!2d100.69168596961516!3d13.86660730117883!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m2!1m1!2zMTPCsDUxJzU5LjgiTiAxMDDCsDQxJzMyLjQiRQ!5e1!3m2!1sth!2sth!4v1762700325446!5m2!1sth!2sth" width="100%" height="615" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>  

          </p>
        </div>
      </div>
    </div>

  </div>
</section>

<!-- ============================================================
💬 YakPho Aroma – Contact Buttons Section (v2.4)
============================================================ -->
<section id="contact-links" class="section center box-contact">
  <div class="wrap">
    <h2 class="h2">ติดต่อ YakPho Aroma</h2>
    <p class="p muted">คลิกเพื่อพูดคุยกับเราได้ทันที ทุกช่องทาง ทุกเวลา ทั่วโลก</p>

    <div class="contact-buttons">
      <!-- LINE -->
      <a href="https://lin.ee/kGIUwnf2" target="_blank" rel="noopener" class="contact-btn line">
        <i data-lucide="message-square"></i>
        <span>LINE Official</span>
      </a>

      <!-- Messenger -->
      <a href="https://www.facebook.com/YakPho.Aroma" target="_blank" rel="noopener" class="contact-btn messenger">
        <i data-lucide="facebook"></i>
        <span>Messenger</span>
      </a>

      <!-- WhatsApp -->
      <a href="https://wa.me/660800617073" target="_blank" rel="noopener" class="contact-btn whatsapp">
        <i data-lucide="phone"></i>
        <span>WhatsApp</span>
      </a>

      <!-- Email -->
      <a href="mailto:thaiherbfc@gmail.com" class="contact-btn email">
        <i data-lucide="mail"></i>
        <span>Email Us</span>
      </a>
    </div>
  </div>
</section>

<!-- GALLERY -->
<section id="gallery" class="section with-bg">
  <?php include __DIR__ . '/ajax_gallery.php'; ?>
</section>



</main>

<!-- Sticky footer CTA -->
<div class="sticky-footer">
  <div class="sticky-inner">
    <a href="#contact-links" class="btn chat" ><i data-lucide="message-circle"></i> คุยกับเรา</a>
    <a class="btn order-btn" href="order/"><i data-lucide="shopping-cart"></i> สั่งซื้อที่นี่</a>
  </div>
</div>

<!-- Lightbox Root -->
<div class="lightbox" id="lightbox-root" aria-hidden="true">
  <img id="lightbox-img" alt="preview" style="max-width:90vw;max-height:90vh;border-radius:12px;border:1px solid #333">
</div>

<!-- Scripts -->
<script src="https://unpkg.com/lucide@latest"></script>
<script>
  // Init icons
  window.lucide && window.lucide.createIcons();

  // Reveal on view
  const io = new IntersectionObserver((entries)=>{
    entries.forEach(e=>{ if(e.isIntersecting){ e.target.classList.add('show'); io.unobserve(e.target); } });
  }, {threshold:0.15});
  document.querySelectorAll('.reveal, .zoom').forEach(el=>io.observe(el));

  // Simple search next
  (function(){
    const input = document.getElementById('pageSearch');
    const nextBtn = document.getElementById('nextResult');
    let lastQuery = "";
    nextBtn?.addEventListener('click', ()=>{
      const q = (input?.value||"").trim();
      if(!q) return;
      if(q !== lastQuery){ lastQuery = q; }
      if(typeof window.find === 'function'){
        window.find(q, false, false, true, false, false, false);
      }else{
        alert('Press Ctrl+F and type: ' + q);
      }
    });
  })();

  // Lightbox
  (function(){
    const root = document.getElementById('lightbox-root');
    const img = document.getElementById('lightbox-img');
    document.addEventListener('click', (e)=>{
      const a = e.target.closest('a[data-lightbox]');
      if(a){
        e.preventDefault();
        img.src = a.getAttribute('href');
        root.classList.add('active');
        root.setAttribute('aria-hidden','false');
      }
      if(e.target === root){
        root.classList.remove('active');
        root.setAttribute('aria-hidden','true');
        img.removeAttribute('src');
      }
    });
    document.addEventListener('keydown',(e)=>{
      if(e.key === 'Escape'){
        root.classList.remove('active');
        root.setAttribute('aria-hidden','true');
        img.removeAttribute('src');
      }
    });
  })();

  // Load Content Async
  const currentLang = '<?= CURRENT_LANG ?>';
  
  fetch(`ajax_hero.php?lang=${currentLang}`)
    .then(r => r.text())
    .then(html => {
        document.getElementById('home').innerHTML = html;
        if (typeof Swiper !== 'undefined') {
            new Swiper('.heroSwiper', {
              loop: true,
              autoplay: { delay: 5000, disableOnInteraction: false },
              pagination: { el: '.swiper-pagination', clickable: true },
              navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' }
            });
        }
        window.lucide && window.lucide.createIcons();
    });

  fetch(`ajax_formula.php?lang=${currentLang}`)
    .then(r => r.text())
    .then(html => {
        document.getElementById('lines').innerHTML = html;
        document.querySelectorAll('#lines .reveal, #lines .zoom').forEach(el=>io.observe(el));
        window.lucide && window.lucide.createIcons();
    });

  fetch(`ajax_scents.php?lang=${currentLang}`)
    .then(r => r.text())
    .then(html => {
        document.getElementById('scents').innerHTML = html;
        document.querySelectorAll('#scents .reveal').forEach(el=>io.observe(el));
        window.lucide && window.lucide.createIcons();
    });
</script>
<!-- ============================================================
✨ Smooth Scroll – YakPho Aroma v2.4
============================================================ -->
<script>
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function (e) {
    const target = document.querySelector(this.getAttribute('href'));
    if (target) {
      e.preventDefault();
      target.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });
      // ปิดเมนู (ถ้ามี mobile menu ภายหลัง)
      history.pushState(null, null, this.getAttribute('href'));
    }
  });
});

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function (e) {
    const target = document.querySelector(this.getAttribute('href'));
    if (target) {
      e.preventDefault();
      const topbarHeight = document.querySelector('.topbar')?.offsetHeight || 0;
      const elementPosition = target.getBoundingClientRect().top + window.pageYOffset;
      const offsetPosition = elementPosition - (topbarHeight + 10); // ✅ เว้นหัว 10px
      window.scrollTo({ top: offsetPosition, behavior: "smooth" });
    }
  });
});
</script>
<!-- 🍃 Floating Menu Button -->
<button id="menuToggle" class="floating-menu-btn" aria-label="Toggle menu">
  <div class="bar"></div>
  <div class="bar"></div>
  <div class="bar"></div>
</button>

<!-- 🌿 Slide-out Menu -->
<nav id="sideMenu" class="side-menu">
  <a href="#home"><i data-lucide="home"></i> หน้าแรก</a>
  <a href="#lines"><i data-lucide="flame"></i> สามสูตรพิเศษ</a>
  <a href="#scents"><i data-lucide="leaf"></i> กลิ่นหอมยอดนิยม</a>
  <a href="#brand-story"><i data-lucide="book-open"></i> เรื่องราวแบรนด์</a>
  <a href="#gallery"><i data-lucide="images"></i> แกลเลอรี</a>
  <a href="#contact"><i data-lucide="info"></i> เกี่ยวกับเรา</a>
  <a href="#contact-links"><i data-lucide="message-circle"></i> คุยกับเรา</a>
</nav>
<?php include __DIR__ . "/includes/footer.php"; ?>
