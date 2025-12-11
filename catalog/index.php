<?php
// Yakpho Aroma v2 – E‑Catalog (Stagetimer-style theme)
?>
<?php 
include __DIR__ . "/includes/config.php"; 
include __DIR__ . "/includes/header.php"; 
?>

<!-- ================================
   🔍 Topbar (Search + Brand)
================================ -->
<div class="topbar">
  <div class="inner">
<div class="brand">
  <div class="brand-line1">YAKPHO</div>
  <div class="brand-line2">Aroma</div>
</div>


    <div class="search-wrap">
      <input type="text" id="pageSearch" placeholder="ค้นหา..." class="input-weblitepro search-input">
      <button id="nextResult" class="btn-weblitepro search-btn" title="ค้นหา">
        <i data-lucide="search"></i>
      </button>
    </div>
  </div>
</div>


<main>

<!-- HERO -->
<section class="section hero" id="home">
  <div class="wrap hero-grid">

    <!-- 🖼️ Hero Image -->
    <div class="hero-image reveal">
      <div class="card-hero fade-image">
        <img src="assets/img/hero.webp" alt="YakPho Aroma">
        <div class="fade-overlay"></div>
      </div>
    </div>

    <!-- ✨ Hero Text -->
    <div class="hero-text reveal">
      <h1 class="h1">Premium Herbal Balm for Thai Massage Shops Worldwide</h1>
      <p class="p">
        บาล์มนวดสมุนไพรระดับพรีเมียม สูตรเฉพาะ “ยักษ์โพธิ์อโรม่า”
        เนื้อบาล์มเนียนนุ่ม ไม่เหนียว ไม่ขาวลอย เมื่อสัมผัสผิวจะค่อย ๆ เปลี่ยนเป็นน้ำมันนวดที่ลื่นมือ
        ผลิตสดใหม่ทุกออเดอร์ ส่งตรงจากไทยสู่ร้านนวดทั่วโลก
      </p>

      <div class="lead-badges">
        <span class="badge-h">ผลิตสดใหม่ทุกออเดอร์</span>
        <span class="badge-h">ถุงซีลสุญญากาศ 1 กก.</span>
        <span class="badge-h">ลื่นมือ ไม่เหนียว</span>
        <span class="badge-h">กลิ่นหอมสปา 12 แบบ</span>
      </div>

      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px">
        <a href="#pricing" class="btn secondary"><i data-lucide="tags"></i> ดูเรทราคาส่ง</a>
        <a href="order/" class="btn order-btn"><i data-lucide="shopping-cart"></i> สั่งซื้อตอนนี้</a>
      </div>
    </div>

  </div>
</section>


<!-- PRODUCT LINES -->
<section class="section formula" id="lines">
  <div class="wrap">
    <h2 class="h2 center">🌿 3 สูตรพิเศษสำหรับร้านนวด</h2>
    <p class="p center">เลือกสูตรที่เข้ากับสไตล์การนวดและบรรยากาศของร้าน
เพื่อให้ทุกการสัมผัสคือช่วงเวลาแห่งความสบายและความประทับใจของลูกค้า</p>

    <div class="grid-3">

      <!-- 🔥 สูตรร้อน -->
      <div class="card reveal line-hot">
        <img src="assets/img/best_h001.webp" alt="สูตรร้อน – Hot Herbal Balm">
        <div class="body">
          <h3 class="h3">🔥 สูตรร้อน – Hot Herbal Balm</h3>
          <p class="p">
            กลิ่นหอมสมุนไพรอุ่นนุ่มที่โอบร่างกายไว้ด้วยความผ่อนคลาย  
            สัมผัสแรกคือความอุ่นซึมลึก คลายความตึงเมื่อย เหมาะกับผู้ที่ต้องการฟื้นฟูกล้ามเนื้อ  
            หลังการนวดหนักหรือออกกำลังกาย ช่วยให้เลือดไหลเวียนดี และเติมพลังให้รู้สึกอบอุ่นอีกครั้ง
          </p>
          <ul class="p bullet">
            <li>อบอุ่น</li>
            <li>ผ่อนคลาย</li>
            <li>ฟื้นฟูกำลัง</li>
          </ul>
        </div>
      </div>

      <!-- ❄️ สูตรเย็น -->
      <div class="card reveal line-cool">
        <img src="assets/img/best_h002.webp" alt="สูตรเย็น – Cool Relax Balm">
        <div class="body">
          <h3 class="h3">❄️ สูตรเย็น – Cool Relax Balm</h3>
          <p class="p">
            กลิ่นหอมเย็นสะอาดแบบสปา ให้ความรู้สึกสดชื่น โล่งสบาย  
            เหมาะกับผู้ที่ต้องการคลายร้อน คลายเมื่อย และเติมความสดชื่นให้ร่างกาย  
            นวดได้ยาวนานโดยไม่แห้งเร็ว เหมาะสำหรับการนวดผ่อนคลายประจำวัน
          </p>
          <ul class="p bullet">
            <li>เย็นสดชื่น</li>
            <li>เบาสบาย</li>
            <li>โปร่งโล่ง</li>
          </ul>
        </div>
      </div>

      <!-- 💆‍♀️ สูตรนวดตัว -->
      <div class="card reveal line-massage">
        <img src="assets/img/best_h003.webp" alt="สูตรนวดตัว – Massage Spa Balm">
        <div class="body">
          <h3 class="h3">💆‍♀️ สูตรนวดตัว – Massage Spa Balm</h3>
          <p class="p">
            สูตรเฉพาะสำหรับงานนวดมืออาชีพ ให้สัมผัสนุ่มลื่น  
            ไม่ร้อนและไม่เย็นจนเกินไป กลิ่นหอมแนวอโรม่าช่วยสร้างบรรยากาศผ่อนคลาย  
            เหมาะกับสปา ร้านนวด และผู้ที่ต้องการความสบายลึกในทุกสัมผัส บำรุงผิว ด้วยน้ำมันมะพร้าว และวิตามินอี
          </p>
          <ul class="p bullet">
            <li>เนียนนุ่ม</li>
            <li>ลื่นมือ</li>
            <li>ผ่อนคลายลึก</li>
          </ul>
        </div>
      </div>

    </div>
  </div>
</section>


  <!-- SCENTS -->
  <section class="section" id="scents">
    <div class="wrap">
      <h2 class="h2 center">กลิ่นยอดนิยม (12 แบบ)</h2>
      <div class="grid-4">
        <?php
          $scents = [
            ["01. สีขาว – ต้นตำรับ","การบูร พิมเสน เมนทอล ยูคาลิปตัส เปปเปอร์มิ้น กานพูล อบเชย"],
            ["02. สีเหลือง – ไพล","กลิ่นสมุนไพรอุ่น ๆ แบบไทย กลิ่นหอมลึกของไพลและน้ำมันระกำ"],
            ["03. สีเขียว – เสลดพังพอน","อโวคาโด + แมกโนเลีย + มะลิ"],
            ["04. เขียวอ่อน – ตะไคร้หอม","กลิ่นสะอาด สดชื่นแบบสปา ผสานกลิ่นซิตรัสของตะไคร้หอม"],
            ["05. เขียวอ่อน – หญ้าเอ็นยืด","แอปเปิล + แมกโนเลีย"],
            ["06. ม่วงอ่อน – ลาเวนเดอร์","กลิ่นหอมแบบยุโรป อบอวลด้วยลาเวนเดอร์แท้ ให้ความรู้สึกผ่อนคลาย"],
            ["07. สีเขียว – ยูคาลิปตัส","กลิ่นสดชื่น ปลอดโปร่ง ช่วยให้หายใจโล่งและปลุกความสดใส"],
            ["08. สีขาว – มะลิ","กลิ่นดอกไม้ไทยหอมหวาน ละมุนละไม สื่อถึงความอ่อนโยนและสง่างาม"],
            ["09. ชมพูอ่อน – กุหลาบ","กลิ่นหอมโรแมนติก อบอวลด้วยกลิ่นกลีบกุหลาบ ให้ความรู้สึกนุ่มนวล"],
            ["10. เหลืองอ่อน – ขิงมินท์","ผสมความอุ่นของขิงและความเย็นของมินท์ กลมกล่อม สดชื่น"],
            ["11. สีขาว – ดอกโมก","กลิ่นดอกไม้ไทยบริสุทธิ์ อ่อนละมุน มีเอกลักษณ์เฉพาะของโมกไทย"],
            ["12. สีขาว – น้ำมันมะพร้าว","กลิ่นธรรมชาติบริสุทธิ์ของมะพร้าว หอมเบา ๆ เหมือนชายหาดเมืองร้อน"],
          ];
          foreach($scents as $i => $s){
            echo '<div class="card reveal">
            <div class="body">
              <div class="scent-image">
              <img src="assets/img/scents/'.($i+1).'.webp" alt="'.$s[0].'">
            </div>
            <strong class="h3" style="font-size:16px;margin:0 0 6px;">'.$s[0].'</strong><br><span class="p">'.$s[1].'</span></div></div>';
          }
        ?>
      </div>
    </div>
  </section>
<!-- ============================================================
💰 YakPho Aroma – เรทราคาส่ง (v2.4)
============================================================ -->
<section id="pricing" class="section has-bg-white">
  <div class="wrap center">
    <h2 class="h2">เรทราคาส่ง YakPho Aroma</h2>
    <p class="p">ราคาต่อกิโลกรัม สำหรับทุกสูตรและทุกกลิ่น</p>

    <table class="price-table">
      <thead>
        <tr>
          <th>ปริมาณ (กิโลกรัม)</th>
          <th>ราคาต่อกก.</th>
        </tr>
      </thead>
      <tbody>
        <tr><td>1 กก.</td><td>690 บาท</td></tr>
        <tr><td>6 กก.</td><td>590 บาท</td></tr>
        <tr><td>10 กก.</td><td>560 บาท</td></tr>
        <tr><td>20 กก.</td><td>530 บาท</td></tr>
        <tr><td>30 กก.</td><td>500 บาท</td></tr>
        <tr><td>50 กก.</td><td>490 บาท</td></tr>
        <tr><td>100 กก.</td><td>470 บาท</td></tr>
      </tbody>
    </table>

    <div class="p muted" style="margin-top:16px;">
      <div>📦 บรรจุถุงซีลขนาด 1 กก. แน่นหนาพร้อมเดินทางไกล</div> 
      <div>🌿 ผลิตสดใหม่ทุกออเดอร์ เพื่อคงคุณภาพและกลิ่นหอม</div>
      <div>🎨 คละสูตร คละกลิ่นได้ทุกแบบ </div>
    </div>
  </div>
</section>

<!-- ============================================================
💎 Brand Story — Touch of Thai Wellness
============================================================ -->
<section id="brand-story" class="section has-bg-soft">
  <div class="wrap grid-2">
    <!-- ด้านซ้าย : ภาพประกอบ -->
    <div class="reveal">
      <h2 class="h2">Brand Story <div class="text-brand">Touch of Thai Wellness</div></h2>
      <div class="brand-photo">
        <img src="assets/img/brand_story.webp" alt="Touch of Thai Wellness">
      </div>
    </div>

    <!-- ด้านขวา : เนื้อหา -->
    <div class="reveal">
      <div class="brand-body">
        
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
      </div>
    </div>
  </div>
</section>





<!-- CONTACT / ABOUT -->
<section id="contact" class="section with-bg">
  <div class="wrap grid-2">

    <!-- 🏢 เกี่ยวกับบริษัท -->
    <div class="reveal">
      <div class="card">
        <div class="body">
          <h2 class="h2">เกี่ยวกับเรา</h2>
          <h4 class="h4">บริษัท ยักษ์โพธิ์ อโรม่า อินเตอร์เทรด จำกัด</h4>
          <p class="p"><strong>YakPho Aroma Intertrade Co., Ltd.</strong></p>
          <p class="p">
            ผู้ผลิตและจำหน่ายผลิตภัณฑ์สมุนไพรไทยเพื่อสุขภาพและการนวดบำบัด  
            ภายใต้แนวคิด <em>“Empowering the World with Thai Herbal Wellness”</em><br>
            เรานำภูมิปัญญาสมุนไพรไทยผสานกับเทคโนโลยีการผลิตสมัยใหม่  
            เพื่อส่งต่อพลังแห่งธรรมชาติไทยสู่สปาและร้านนวดทั่วโลก
          </p>
          <ul class="p">
            <li>ผลิตสดใหม่ทุกออเดอร์ • ตามมาตรฐานความปลอดภัย</li>
            <li>จำหน่ายมาแล้ว กว่า 10 ประเทศ ทั่ว ยุโรป อเมริกา ออสเตรเลีย นิวซีแลนด์ และ เอเชีย</li>
            <li>สินค้าหลัก: ปาล์มนวดนวด สูตรร้อน สูตรเย็น สูตรนวดตัว</li>
            <li>วัตถุดิบสมุนไพรไทยแท้ คัดสรรจากแหล่งปลูกธรรมชาติ</li>
          </ul>
          <blockquote class="p muted" style="margin-top:12px;">
            “กลิ่นหอมจากสมุนไพรไทย ไม่ได้เป็นเพียงความผ่อนคลาย  
            แต่คือพลังบำบัดจากธรรมชาติ ที่เชื่อมโยงหัวใจของคนทั่วโลกเข้าด้วยกัน”
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
            <b>บริษัท ยักษ์โพธิ์ อโรม่า อินเตอร์เทรด จำกัด</b><br>เลขที่ 32/4 ถนนพระยาสุเรนทร์ แขวงสามวาตะวันตก เขตคลองสามวา กรุงเทพมหานคร 10510 ประเทศไทย<br>
            โทร: (+66) 80 061 7073
          </p>
          <p class="p">
            Line Official: <a href="https://lin.ee/kGIUwnf2" target="_blank">@yakpho</a><br>
            Facebook: <a href="https://www.facebook.com/YakPho.Aroma" target="_blank">YakPho Aroma</a><br>
            Email: <a href="mailto:thaiherbfc@gmail.com">thaiherbfc@gmail.com</a><br>
            WhatsApp: <a href="https://wa.me/660800617073" target="_blank">+66 080 061 7073</a>
          </p>



          <p class="p muted">
          <iframe src="https://www.google.com/maps/embed?pb=!1m17!1m12!1m3!1d1171.3928645250064!2d100.69168596961516!3d13.86660730117883!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m2!1m1!2zMTPCsDUxJzU5LjgiTiAxMDDCsDQxJzMyLjQiRQ!5e1!3m2!1sth!2sth!4v1762700325446!5m2!1sth!2sth" width="100%" height="250" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>  

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
    <p class="p muted">คลิกเพื่อพูดคุยกับเราได้ทันที ทุกช่องทาง ทั่วโลก</p>

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
  <div class="wrap">
    <h2 class="h2 center">Gallery</h2>

    <div class="gallery reveal">
      <?php
        $imgs = [];
        for ($i = 1; $i <= 95; $i++) {
            $imgs[] = "g" . str_pad($i, 2, "0", STR_PAD_LEFT);
        }

        foreach($imgs as $g){
          $src = "assets/img/".$g.".webp";
          echo '
          <a href="'.$src.'" data-fancybox="gallery" data-no-loader="true" class="fade-in">
            <img class="lazy" data-src="'.$src.'" alt="YakPho Aroma image '.$g.'" loading="lazy">
          </a>';
        }
      ?>
    </div>
  </div>
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
