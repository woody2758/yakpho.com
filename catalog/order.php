<?php
include __DIR__ . "/includes/config.php"; 
include __DIR__ . "/includes/header.php"; 
?>
<div class="topbar">
      <div class="inner">
    <div class="brand"><a href="../">
      <div class="brand-line1">YAKPHO</div>
      <div class="brand-line2">Aroma</div>
    </a>
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


<section id="home" class="section with-bg  tophome">
<div class="wrap">
<div class="card">
  <div class="body">
          <h3 class="h3">วิธีการสั่งซื้อ</h3>
<a href="index.php" class="back-btn" aria-label="กลับหน้าหลัก">
  <i data-lucide="arrow-left"></i> กลับ
</a>
  <div class="sub">
    เรทราคาสามารถคละสูตรได้ทั้ง 3 สูตร คละกลิ่นได้ทั้งหมด 12 กลิ่น 
    <ol>
      <li>เลื่อนดูกลิ่นและสูตร เช่นสูตร ร้อน เย็น นวดตัว</li>
      <li>คลิกปุ่มบวกเพื่อเพิ่ม ปุ่มลบ เพื่อนำออก</li>
      <li>ระบบรวมราคาให้อัตโนมัติเมื่อถือเรทราคาที่ต้อง</li>
      <li>เมื่อเลือกเสร็จแล้ว ราคารวมจะปรากฏด้านล่าง</li>
      <li>คลิกปุ่ม ส่งคำสั่งซื้อ แล้วนำข้อมูล วางที่ปุ่มแชท เพื่อส่งแอดมินได้เลยค่ะ</li>
      
    </ol>
<div class="center">
  <a href="#pricing" class="btn secondary"> <i data-lucide="tags"></i>  เรทราคาส่ง</a>
  <a href="#delivery" class="btn secondary"> <i data-lucide="package"></i>  การจัดส่ง</a>

</div>
  
  </div>
</div>
</div>
</div>
</section>
<section id="order" class="section with-bg">
<div class="wrap">
  <?php

$COMPANY_NAME = "Yakpho Aroma Intertrade Co., Ltd.";
$VERSION      = "v3.2";
$UPDATED_AT   = date("Y-m-d");
$LOCAL_SHIP_TH = 60;

$PRICE_RATE = [
  ["min"=>1,   "price"=>690],
  ["min"=>6,   "price"=>590],
  ["min"=>10,  "price"=>560],
  ["min"=>20,  "price"=>530],
  ["min"=>30,  "price"=>500],
  ["min"=>50,  "price"=>490],
  ["min"=>100, "price"=>470],
];

$SCENTS = [
  ["th"=>"สีขาว. กลิ่นต้นตำรับ",   "en"=>"Original",             "code"=>"001"],
  ["th"=>"สีเหลือง. กลิ่นไพล",        "en"=>"Zingiber cassumunar", "code"=>"002"],
  ["th"=>"สีเขียว. กลิ่นเสลดพังพอน",  "en"=>"Barleria Oil",        "code"=>"003"],
  ["th"=>"สีเขียว. กลิ่นตะไคร้หอม",  "en"=>"Lemongrass",          "code"=>"004"],
  ["th"=>"สีเขียว. กลิ่นหญ้าเอ็นยืด", "en"=>"Plantain",            "code"=>"005"],
  ["th"=>"สีม่วง. กลิ่นลาเวนเดอร์",   "en"=>"Lavender",            "code"=>"006"],
  ["th"=>"สีขาว. กลิ่นยูคาลิปตัส",    "en"=>"Eucalyptus",          "code"=>"007"],
  ["th"=>"สีขาว. กลิ่นมะลิ",          "en"=>"Jasmine",             "code"=>"008"],
  ["th"=>"สีชมพู. กลิ่นกุหลาบ",       "en"=>"Rose",                "code"=>"009"],
  ["th"=>"สีเหลืองอ่อน. กลิ่นขิงมินท์", "en"=>"Ginger Mint",       "code"=>"010"],
  ["th"=>"สีขาว. กลิ่นดอกโมก",        "en"=>"Water jasmine",       "code"=>"011"],
  ["th"=>"สีขาว. กลิ่นน้ำมันมะพร้าว", "en"=>"Coconut Oil",         "code"=>"012"],
];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["order_text"], $_POST["ref"])) {
  $ref = preg_replace("/[^0-9]/", "", $_POST["ref"]);
  if ($ref === "") { $ref = (string)time(); }
  $dir = __DIR__ . "/orders";
  if (!is_dir($dir)) { mkdir($dir, 0777, true); }
  $name = $dir . "/" . $ref . "_order.txt";
  $order_text = str_replace("\\n", "\n", $_POST["order_text"]);
  $ok = file_put_contents($name, $order_text);
  header("Content-Type: application/json");
  echo json_encode([ "saved" => $ok !== false, "file" => basename($name), "ref" => $ref ]);
  exit;
}

$PRICE_RATE_JSON = json_encode($PRICE_RATE, JSON_UNESCAPED_UNICODE);
$SCENTS_JSON     = json_encode($SCENTS, JSON_UNESCAPED_UNICODE);
?>
<h2 class="center"><i data-lucide="shopping-cart"></i> เลือกสินค้าจากตารางนี้</h2>
  <!-- Debug: Total Scents = <?= count($SCENTS) ?> -->
  <table id="orderTable" aria-describedby="tableHelp">
    <thead>
      <tr class="head">
        <th>ลำดับ / กลิ่น (TH/EN)</th>
        <th class="hide-sm">สูตรร้อน<br><small>Hot (H)</small></th>
        <th class="hide-sm">สูตรเย็น<br><small>Cool (C)</small></th>
        <th class="hide-sm">สูตรนวดตัว<br><small>Balanced (B)</small></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($SCENTS as $i=>$s): ?>
        <tr>
          <th scope="row" class="scent-th">
            <?= str_pad($i+1,2,"0",STR_PAD_LEFT) ?>. <?= htmlspecialchars($s["th"]) ?>
            <span class="scent-sub"><?= htmlspecialchars($s["en"]) ?></span>
          </th>
          <?php
            $code = $s["code"];
            foreach (["H","C","B"] as $f) {
              $sku = $f . $code;
              $label = ($f === "H" ? "สูตรร้อน" : ($f === "C" ? "สูตรเย็น" : "สูตรนวดตัว"));
              echo '<td data-label="'.$label.'">';
              echo '<div class="cell" data-formula="'.htmlspecialchars($f).'" data-sku="'.htmlspecialchars($sku).'">';
              echo '<button class="btn minus" aria-label="ลดจำนวน">−</button>';
              echo '<span class="qty" aria-live="polite">0</span>';
              echo '<button class="btn plus" aria-label="เพิ่มจำนวน">+</button>';
              echo '<span class="sku">SKU: '.htmlspecialchars($sku).'</span>';
              echo '</div>';
              echo '</td>';
            }
          ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <p id="tableHelp" class="opt" aria-hidden="true" style="color:#6b7280;margin-top:8px;">กดปุ่ม +/− เพื่อเลือกจำนวน (kg) ช่องที่มีจำนวน &gt; 0 จะมีสีไฮไลต์</p>
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
🌿 Section: 10 อันดับขายดี – YakPho Aroma
============================================================ -->
<section id="beseller" class="best-seller">
  <div class="container">
    <h2 class="section-title">
      <i data-lucide="flame"></i> 10 อันดับขายดี
    </h2>

    <div class="best-grid">
      <div class="item rank-1"><div class="badge">#1</div><h3>สีขาว ต้นตำรับ <span>สูตรร้อน</span></h3></div>
      <div class="item rank-2"><div class="badge">#2</div><h3>สีเหลือง กลิ่นไพล <span>สูตรร้อน</span></h3></div>
      <div class="item rank-3"><div class="badge">#3</div><h3>สีเขียว เสลดพังพอน <span>สูตรร้อน</span></h3></div>
      <div class="item rank-4"><div class="badge">#4</div><h3>สีเขียวอ่อน ตะไคร้หอม <span>สูตรร้อน</span></h3></div>
      <div class="item rank-5"><div class="badge">#5</div><h3>สีเขียว เสลดพังพอน <span>สูตรเย็น</span></h3></div>
      <div class="item rank-6"><div class="badge">#6</div><h3>สีขาว ต้นตำรับ <span>สูตรเย็น</span></h3></div>
      <div class="item rank-7"><div class="badge">#7</div><h3>สีเขียว หญ้าเอ็นยืด <span>สูตรร้อน</span></h3></div>
      <div class="item rank-8"><div class="badge">#8</div><h3>สีม่วงอ่อน ลาเวนเดอร์ <span>สูตรร้อน</span></h3></div>
      <div class="item rank-9"><div class="badge">#9</div><h3>สีขาว ยูคาลิปตัส <span>สูตรร้อน</span></h3></div>
      <div class="item rank-10"><div class="badge">#10</div><h3>สีเขียวอ่อน ตะไคร้หอม <span>สูตรเย็น</span></h3></div>
    </div>

    <p class="note">🪶 ข้อมูลอ้างอิงจากยอดขายรวมประจำเดือนล่าสุดของ YakPho Aroma</p>
  </div>
</section>





<section id="delivery" class="section ">
  <div class="wrap">
  <div class="card">
  <div class="body">
    <h3>ข้อมูลการจัดส่ง / Shipping Policy:</h3>

        <ul >
          <li><span class="fi fi-th"></span> <strong>ภายในประเทศไทย (Domestic – Thailand):</strong> รวมค่าจัดส่งแล้ว 60 บาทต่อออเดอร์<br>
              <em>Shipping cost of 60 THB per order is already included in the total.</em></li>
          <li>🏢 <strong>มารับสินค้าเองที่บริษัทฯ (Self Pickup):</strong> ไม่คิดค่าจัดส่ง<br>
              <em>No shipping fee for self-pickup at our warehouse.</em></li>
          <li>🌏 <strong>จัดส่งต่างประเทศ (International Delivery):</strong> คิดตามค่าขนส่งจริงจากบริษัทขนส่ง โดยไม่บวกเพิ่ม<br>
              <em>Charged at the actual shipping rate from the courier, with no extra markup.</em><br>
              <small><em>(For international orders, the 60 THB domestic shipping fee will be automatically deducted.)</em></small></li>
        </ul>

    </div>
    </div>
    </div>
  </section>

<section id="contact" class="section endofsection has-bg-white">
  <div class="wrap">

       <h3>พูดคุยกับเราทางแชทตอนนี้</h3>    
    <div class="contact-buttons" >
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
</main>

<footer class="sticky-footer-order" role="contentinfo">
  <div class="sticky-inner-order">
    <div class="summary" id="summaryText">รวม: 0 kg  |  กกละ.: 0 ฿   |  รวม: 0 ฿ </div>
    <div class="actions">
      <button class="btn order-btn" id="copyBtn"><i data-lucide="shopping-bag" style="vertical-align: middle; position: relative; top: -1px; margin-right: 6px;"></i> ส่งคำสั่งซื้อ</button>
    </div>
  </div>
</footer>
<!-- ============================================================
💎 YakPho Aroma – Order Script v3.3 Premium Motion UX
รวมระบบ: Update Summary + Two-Way Fly + Highlight + Sound + Toast + Copy Order
============================================================ -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const PRICE_RATE = <?= $PRICE_RATE_JSON ?>;
const SCENTS     = <?= $SCENTS_JSON ?>;

// 🧮 คำนวณราคาตามจำนวน
function getRate(totalKg){
  let unit = 0;
  for(const r of PRICE_RATE){ if(totalKg >= r.min) unit = r.price; }
  return unit;
}

// 🔁 อัปเดตรวมทั้งหมด (แยกสีของราคาต่อกก. และราคารวม + รวมค่าขนส่ง)
function updateSummary(){
  const qtyEls = document.querySelectorAll(".qty");
  let total = 0;
  qtyEls.forEach(q => total += parseInt(q.textContent,10));
  const unit = total ? getRate(total) : 0;
  const grand = unit * total;
  const shipping = 60;
  const totalWithShip = total > 0 ? grand + shipping : 0;

  document.getElementById("summaryText").innerHTML =
    `รวม: <span class="highlight-total">${total}</span> kg | 
     ราคา/กก.: <span class="highlight-unit">${unit}</span> ฿ | 
     รวม: <span class="highlight-grand">${totalWithShip.toLocaleString()}</span> ฿
     <br><small style="color:#0F5F91;">(รวมค่าขนส่งในประเทศ ${shipping} บาทแล้ว)</small>`;
  // ท้ายฟังก์ชัน updateSummary() ของคุณ
if (window.yakcartRefresh) window.yakcartRefresh();
}


// 🚀 ตั้งค่าเริ่มต้น: ถ้าจำนวนเป็น 0 ให้ปุ่มลบ disabled
document.querySelectorAll('.cell').forEach(cell => {
  const qtyEl = cell.querySelector('.qty');
  const minusBtn = cell.querySelector('.btn.minus');
  const val = parseInt(qtyEl.textContent, 10) || 0;

  if (val <= 0) {
    minusBtn.disabled = true;
    minusBtn.classList.add('btn-disabled');
  } else {
    minusBtn.disabled = false;
    minusBtn.classList.remove('btn-disabled');
  }
});



// 📦 คลิกปุ่ม + / – (พร้อมเสียง + Toast + Animation + Minus Disabled)
document.querySelector("tbody").addEventListener("click",(e)=>{
  const btn = e.target.closest(".btn");
  if(!btn) return;

  const cell  = btn.closest(".cell");
  const qtyEl = cell.querySelector(".qty");
  const minusBtn = cell.querySelector(".btn.minus");
  let val = parseInt(qtyEl.textContent,10);

  if(btn.classList.contains("plus")) val++;
  if(btn.classList.contains("minus")) val = Math.max(0, val - 1);

  qtyEl.textContent = val;
  cell.classList.toggle("active", val > 0);

  // ✅ ปรับสถานะปุ่มลบ
  if (val <= 0) {
    minusBtn.disabled = true;
    minusBtn.classList.add('btn-disabled');
  } else {
    minusBtn.disabled = false;
    minusBtn.classList.remove('btn-disabled');
  }

  updateSummary();

  // 🌿 เสียง Soft UX
  const sound = {
    up:   new Audio("<?= URL_PATH ?>assets/sounds/success.mp3"),
    down: new Audio("<?= URL_PATH ?>assets/sounds/error.mp3")
  };

  // 🍃 Toast แจ้งเตือน
  const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 1000,
    timerProgressBar: true
  });

  const formula = cell.dataset.formula;
  const sku     = cell.dataset.sku;
  const label =
    formula === "H" ? "สูตรร้อน" :
    formula === "C" ? "สูตรเย็น" :
    "สูตรนวดตัว";

  const summary = document.querySelector('.sticky-footer-order');
  const rectStart = btn.getBoundingClientRect();
  const rectEnd   = summary.getBoundingClientRect();

  // 🪶 สร้างวัตถุบิน
  const fly = document.createElement('div');
  fly.className = 'fly-item';
  fly.style.background = window.getComputedStyle(btn).backgroundColor;
  fly.style.left = rectStart.left + 'px';
  fly.style.top  = rectStart.top  + 'px';
  document.body.appendChild(fly);

  // 🔺 คำนวณปลายทาง
  const translateX = rectEnd.left + rectEnd.width/2 - rectStart.left;
  const translateY = rectEnd.top - rectStart.top;

  // ➕ เพิ่มสินค้า → โค้งขึ้นก่อนตก
  if(btn.classList.contains("plus")){
    fly.animate([
      { transform:'translate(0,0) scale(1)', opacity:1 },
       { transform:`translate(${translateX/4}px, ${translateY*0.25 - 150}px) scale(1.15)`, opacity:0.9, offset:0.45 },
      { transform:`translate(${translateX}px, ${translateY}px) scale(0.3)`, opacity:0.2 }
    ], { duration:800, easing:'cubic-bezier(.42,0,.58,1)' });

    setTimeout(()=>{
      summary.style.animation='shake 0.4s ease';
      setTimeout(()=>{ summary.style.animation=''; },400);
      const summaryText=document.getElementById("summaryText");
      summaryText.classList.add('active');
      setTimeout(()=>summaryText.classList.remove('active'),500);
    },800);

    setTimeout(()=>fly.remove(),850);
    sound.up.play();
    Toast.fire({ icon:"success", title:`+1 ${label} (${sku})` });

  // ➖ ลบสินค้า → บินออกจาก summary
  } else {
    const rectSrc = rectEnd;
    fly.style.left = rectSrc.left + rectSrc.width/2 + 'px';
    fly.style.top  = rectSrc.top + 'px';
    fly.animate([
      { transform:'translate(0,0) scale(0.5)', opacity:0.8 },
      { transform:'translate(-80px,-120px) scale(0.8)', opacity:0.6, offset:0.4 },
      { transform:'translate(120px,-180px) scale(1.2)', opacity:0 }
    ], { duration:700, easing:'cubic-bezier(.55,-0.4,.72,1.4)' });
    setTimeout(()=>fly.remove(),750);
    sound.down.play();
    Toast.fire({ icon:"info", title:`−1 ${label} (${sku})` });
  }
});


// 🪶 แกนชื่อภาษาไทย (ตัดคำว่า “สี...” ออก)
function thaiNameCore(th){ return th.replace(/^สี[^.]*\.\s*/,'').trim(); }

// 🧾 สร้างข้อความสรุปคำสั่งซื้อ
function buildSummaryText(ref){
  const lines = [];
  const now = new Date();
  const dateStr = now.toLocaleString('th-TH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });

  lines.push("YAKPHO AROMA – ORDER SUMMARY");
  lines.push("วันที่สั่งซื้อ: " + dateStr);
  lines.push("#Ref"+ref);
  lines.push("-------------------------------");

  let total = 0;

  document.querySelectorAll(".cell").forEach(cell=>{
    const qty = parseInt(cell.querySelector(".qty").textContent,10);
    if(qty>0){
      const sku  = cell.dataset.sku;
      const code = sku.slice(1);
      const s = SCENTS.find(x=>x.code===code);
      const thCore = s ? thaiNameCore(s.th) : "";
      lines.push(`${sku} ${s?s.en:""} (${thCore}) × ${qty} kg`.trim());
      total += qty;
    }
  });

  const unit  = total ? getRate(total) : 0;
  const grand = unit * total;

 lines.push("-------------------------------");
  lines.push(`รวมทั้งหมด: ${total} kg`);
  lines.push(`ราคาต่อกก.: ${unit} THB`);

  const shipping = <?= $LOCAL_SHIP_TH ?>; // ค่าจัดส่งจาก PHP
  const grandTotal = grand + (total > 0 ? shipping : 0); // บวกค่าขนส่งถ้ามีสินค้า

  lines.push(`รวมสินค้า: ${grand.toLocaleString()} THB`);
  lines.push(`ค่าจัดส่ง: ${shipping.toLocaleString()} THB`);
  lines.push(`รวมสุทธิทั้งหมด: ${grandTotal.toLocaleString()} THB`);
  lines.push("-------------------------------");
  lines.push("**หมายเหตุ:** รวมค่าจัดส่งภายในประเทศแล้ว (Self Pickup ฟรี)");

  return lines.join("\n");
}

// 📋 คัดลอก + บันทึกไฟล์ (พร้อมตรวจว่าสินค้ายังไม่ได้เลือก)
async function doCopyAndSave(){
  // ✅ ตรวจสอบก่อนว่ามีสินค้าที่เลือกหรือไม่
  const totalQty = Array.from(document.querySelectorAll(".qty"))
    .reduce((sum, el) => sum + parseInt(el.textContent, 10), 0);

  if (totalQty === 0) {
    Swal.fire({
      icon: "warning",
      title: "กรุณาเลือกสินค้าอย่างน้อย 1 รายการค่ะ",
      confirmButtonText: "ตกลง",
      confirmButtonColor: "#6d28d9",
      background: "#fff",
      color: "#333"
    }).then(() => {
      // 🚀 เลื่อนขึ้นบนสุดของหน้า
      window.scrollTo({ top: 0, behavior: "smooth" });
    });
    return; // ❌ หยุดการทำงานต่อ
  }

  // ✅ ถ้ามีสินค้า → สร้างสรุปคำสั่งซื้อ
  const ref = Math.floor(Date.now()/1000).toString();
  const text = buildSummaryText(ref);

  // copy to clipboard
  try {
    await navigator.clipboard.writeText(text);
  } catch(err){
    const ta = document.createElement("textarea");
    ta.value = text;
    document.body.appendChild(ta);
    ta.select(); document.execCommand("copy");
    document.body.removeChild(ta);
  }

  // save file via POST
  try {
    const res = await fetch("", {
      method:"POST",
      headers:{ "Content-Type":"application/x-www-form-urlencoded;charset=UTF-8" },
      body: new URLSearchParams({ order_text:text, ref })
    });
    await res.json();
  } catch(e){}

  Swal.fire({
    title: "🪶 คัดลอกคำสั่งซื้อเรียบร้อยแล้ว",
    html: `
      <p style="margin:6px 0 12px;font-size:14px;color:#333;">
        นำข้อความที่คัดลอกไป <b>วางในช่องแชต</b> เพื่อแจ้งคำสั่งซื้อได้เลยค่ะ
      </p>
      <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:6px;margin-top:4px;">
        <a href="https://lin.ee/kGIUwnf2" target="_blank" onclick="Swal.close()" data-no-loader="true" 
          style="display:inline-flex;align-items:center;gap:5px;padding:6px 10px;font-size:13px;background:#00b900;color:#fff;border-radius:6px;text-decoration:none;font-weight:600;">
          <i data-lucide='message-square' style="width:14px;height:14px;"></i> LINE
        </a>
        <a href="https://www.facebook.com/YakPho.Aroma" target="_blank" onclick="Swal.close()" data-no-loader="true" 
          style="display:inline-flex;align-items:center;gap:5px;padding:6px 10px;font-size:13px;background:#1877f2;color:#fff;border-radius:6px;text-decoration:none;font-weight:600;">
          <i data-lucide='facebook' style="width:14px;height:14px;"></i> Messenger
        </a>
        <a href="https://wa.me/660800617073" target="_blank" onclick="Swal.close()" data-no-loader="true" 
          style="display:inline-flex;align-items:center;gap:5px;padding:6px 10px;font-size:13px;background:#25D366;color:#fff;border-radius:6px;text-decoration:none;font-weight:600;">
          <i data-lucide='phone' style="width:14px;height:14px;"></i> WhatsApp
        </a>
        <a href="mailto:thaiherbfc@gmail.com" onclick="Swal.close()" data-no-loader="true" 
          style="display:inline-flex;align-items:center;gap:5px;padding:6px 10px;font-size:13px;background:#a50b00;color:#fff;border-radius:6px;text-decoration:none;font-weight:600;">
          <i data-lucide='mail' style="width:14px;height:14px;"></i> Email
        </a>
      </div>
      <small style="display:block;margin-top:8px;color:#777;font-size:12px;">
        รองรับทุกช่องทางติดต่อ YakPho Aroma
      </small>
    `,
    confirmButtonText: "ปิดหน้าต่าง",
    confirmButtonColor: "#6d28d9",
    background: "#ffffff",
    color: "#2b2440",
    width: "340px",  /* ✅ จำกัดความกว้าง popup */
    didOpen: () => {
      if (window.lucide) lucide.createIcons();
    }
  }); 

  


}


document.getElementById("copyBtn").addEventListener("click", doCopyAndSave);
updateSummary();
</script>

<script>
/* ================= YakPho Floating Mini Cart – Safe Patch v1 ================= */
(function(){
  const ready = (fn)=> (document.readyState === 'loading')
    ? document.addEventListener('DOMContentLoaded', fn, { once:true })
    : fn();

  ready(function(){
    // --- ดึง element แบบปลอดภัย ---
    const $  = (s,c=document)=>c.querySelector(s);
    const $$ = (s,c=document)=>Array.from(c.querySelectorAll(s));

    const panel   = $('#yakcartPanel');
    const fab     = $('#yakcartToggle');
    const badge   = $('#yakcartBadge');
    const list    = $('#yakcartList');
    const kgEl    = $('#yakcartKg');
    const sumEl   = $('#yakcartSum');
    const closeBt = panel ? $('.yakcart-close', panel) : null;
    const goBtn   = $('#yakcartCheckout');

    // ถ้าขาด element ใด ๆ ให้หยุดทำงานและ log บอก (กันพัง)
    const missing = [];
    [['#yakcartPanel',panel],['#yakcartToggle',fab],['#yakcartBadge',badge],
     ['#yakcartList',list],['#yakcartKg',kgEl],['#yakcartSum',sumEl]]
     .forEach(([name,el])=>{ if(!el) missing.push(name); });

    if (missing.length){
      console.warn('YakCart: missing elements ->', missing.join(', '));
      return; // ไม่ทำงานต่อ เพื่อไม่ให้ error
    }

    const formulaTH = f => f==='H' ? 'สูตรร้อน' : (f==='C' ? 'สูตรเย็น' : 'สูตรนวดตัว');

    function yakcartGather(){
      const rows = [];
      let totalKg = 0;
      $$('.cell').forEach(cell=>{
        const qtyEl = $('.qty', cell);
        if(!qtyEl) return;
        const qty = parseInt(qtyEl.textContent || '0', 10);
        if(qty>0){
          const sku = cell.getAttribute('data-sku') || '';
          const f   = cell.getAttribute('data-formula') || sku.charAt(0);
          const tr  = cell.closest('tr');
          const name = tr ? (tr.querySelector('th')?.innerText.trim() || sku) : sku;
          rows.push({ sku, formula:f, name, qty });
          totalKg += qty;
        }
      });
      return { rows, totalKg };
    }

    function yakcartCompute(totalKg){
      try{
        const unit = totalKg ? getRate(totalKg) : 0; // อาศัยฟังก์ชันเดิมของคุณ
        const sum  = unit * totalKg;
        return { unit, sum };
      }catch(e){
        console.warn('YakCart: getRate() not found, fallback 0');
        return { unit:0, sum:0 };
      }
    }

    function yakcartRender(){
      const { rows, totalKg } = yakcartGather();
      const { sum } = yakcartCompute(totalKg);

      // ปลอดภัยก่อนเขียนค่า
      if (badge)  badge.textContent = String(rows.length);
      if (kgEl)   kgEl.textContent  = String(totalKg);
      if (sumEl)  sumEl.textContent = (sum||0).toLocaleString();

      if (list){
        if(rows.length===0){
          list.innerHTML = '<li class="yakcart-empty">ยังไม่มีสินค้า</li>';
        }else{
          list.innerHTML = rows.map(r=>{
            const scent = r.name.replace(/\s+/g,' ').trim();
            return `<li><span>${scent} <small style="color:#6b7280">(${formulaTH(r.formula)})</small></span><span><strong>${r.qty}</strong> kg</span></li>`;
          }).join('');
        }
      }

      if (goBtn){
        goBtn.disabled = totalKg===0;
        goBtn.style.opacity = totalKg===0? .6 : 1;
      }
    }

    function yakcartToggle(open){
      const willOpen = (typeof open==='boolean') ? open : !panel.classList.contains('active');
      panel.classList.toggle('active', willOpen);
      panel.setAttribute('aria-hidden', willOpen ? 'false':'true');
      try{ if(window.lucide) lucide.createIcons(); }catch{}
    }

    // fab.addEventListener('click', ()=>yakcartToggle(true));
    fab.addEventListener('click', ()=>yakcartToggle());
    if (closeBt) closeBt.addEventListener('click', ()=>yakcartToggle(false));

    if (goBtn){
      goBtn.addEventListener('click', ()=>{
        if (typeof doCopyAndSave === 'function') doCopyAndSave();
      });
    }

    // อัปเดตเมื่อกด +/− (ปล่อยให้ handler เดิมของคุณทำงานก่อน)
    document.addEventListener('click', (e)=>{
      if(e.target.closest('.btn.plus') || e.target.closest('.btn.minus')){
        setTimeout(yakcartRender, 0);
      }
    });

    // hook เสริม: ให้เรียกจาก updateSummary() เดิมของคุณได้ด้วย
    window.yakcartRefresh = yakcartRender;

    // เริ่มต้น
    yakcartRender();
  });
})();
</script>


<!-- 💫 Motion + Highlight Style -->
<style>
.fly-item {
  position: fixed;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  pointer-events: none;
  z-index: 9999;
  opacity: 0.9;
  will-change: transform, opacity;
  box-shadow: 0 0 6px rgba(0,0,0,0.2);
}

/* Shake summary */
@keyframes shake {
  0%,100%{transform:translateX(0);}
  25%{transform:translateX(-3px);}
  50%{transform:translateX(3px);}
  75%{transform:translateX(-2px);}
}

/* Summary color highlight */
#summaryText .highlight-total {
  color:#b86ef3; font-weight:700;
}
#summaryText .highlight-unit {
  color:#28a4b0; font-weight:700;
}
#summaryText .highlight-grand {
  color:#ff6600; font-weight:800;
  transition:color .3s,transform .3s;
}
#summaryText.active .highlight-grand {
  color:#ff884d; transform:scale(1.15);
}


</style>
<!-- 🍃 Floating Menu Button -->
<button id="menuToggle" class="floating-menu-btn" aria-label="Toggle menu">
  <div class="bar"></div>
  <div class="bar"></div>
  <div class="bar"></div>
</button>

<!-- 🌿 Slide-out Menu -->
<nav id="sideMenu" class="side-menu">
  <a href="<?php echo URL_PATH;?>"><i data-lucide="home"></i> หน้าแรก</a>
  <a href="#home"><i data-lucide="list-checks"></i> วิธีการสั่งซื้อ</a>
  <a href="#delivery"><i data-lucide="truck"></i> ข้อมูลการจัดส่ง</a>
  <a href="#order"><i data-lucide="flame"></i> เลือกสูตรเลือกลิ่น</a>
  <a href="#beseller"><i data-lucide="leaf"></i> 10 อันดับขายดี</a>
  <a href="#pricing"><i data-lucide="images"></i> เรทราคา</a>
  <a href="#contact"><i data-lucide="message-circle"></i> คุยกับเรา</a>
  
</nav>
<!-- ==================== YakPho Floating Mini Cart ==================== -->
<button id="yakcartToggle" class="yakcart-fab" type="button" aria-label="เปิดตะกร้า" data-no-loader="true">
  <i data-lucide="shopping-cart"></i>
  <span id="yakcartBadge">0</span>
</button>

<div id="yakcartPanel" class="yakcart-panel" aria-hidden="true">
  <div class="yakcart-head">
    <div class="title"><i data-lucide="shopping-cart"></i> ตะกร้าของคุณ</div>
    <button class="yakcart-close" type="button" aria-label="ปิด">&times;</button>
  </div>

  <div class="yakcart-body">
    <ul id="yakcartList" class="yakcart-list"><li class="yakcart-empty">ยังไม่มีสินค้า</li></ul>
  </div>

  <div class="yakcart-foot">
    <div class="yakcart-total">
      รวม: <strong><span id="yakcartKg">0</span> kg</strong>
      <span class="sep">|</span>
      รวมเงิน: <strong><span id="yakcartSum">0</span> THB</strong>
    </div>
    <button id="yakcartCheckout" type="button" class="yakcart-checkout" data-no-loader="true">
      <i data-lucide="credit-card"></i> ส่งคำสั่งซื้อ
    </button>
  </div>
</div>
<!-- ================== /YakPho Floating Mini Cart =================== -->

<!-- lucide icons -->
<script src="https://unpkg.com/lucide@latest"></script>
<script>if(window.lucide){lucide.createIcons();}</script>
<script src="<?= URL_PATH ?>assets/js/app.js?<?php echo $ver;?>"></script>

</body>
</html>


