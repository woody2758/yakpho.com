<?php
header("Content-Type: text/html; charset=utf-8");
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
  ["th"=>"สีขาว. สูตรต้นตำรับ",   "en"=>"Original",             "code"=>"001"],
  ["th"=>"สีเหลือง. ไพล",        "en"=>"Zingiber cassumunar", "code"=>"002"],
  ["th"=>"สีเขียว. เสลดพังพอน",  "en"=>"Barleria Oil",        "code"=>"003"],
  ["th"=>"สีขาว. ดอกโมก",        "en"=>"Water jasmine",       "code"=>"004"],
  ["th"=>"สีเขียว. ตะไคร้หอม",  "en"=>"Lemongrass",          "code"=>"005"],
  ["th"=>"สีม่วง. ลาเวนเดอร์",   "en"=>"Lavender",            "code"=>"006"],
  ["th"=>"สีเขียว. หญ้าเอ็นยืด", "en"=>"Plantain",            "code"=>"007"],
  ["th"=>"สีขาว. ยูคาลิปตัส",    "en"=>"Eucalyptus",          "code"=>"008"],
  ["th"=>"สีขาว. มะลิ",          "en"=>"Jasmine",             "code"=>"009"],
  ["th"=>"สีชมพู. กุหลาบ",       "en"=>"Rose",                "code"=>"010"],
  ["th"=>"สีเหลืองอ่อน. ขิงมินท์", "en"=>"Ginger Mint",       "code"=>"011"],
  ["th"=>"สีขาว. ลีลาวดี",       "en"=>"Frangipani",          "code"=>"012"],
  ["th"=>"สีขาว. น้ำมันมะพร้าว", "en"=>"Coconut Oil",         "code"=>"013"],
  ["th"=>"สีฟ้า. โรสแมรี่",       "en"=>"Rosemary",            "code"=>"014"],
  ["th"=>"สีส้มอ่อน. น้ำอบไทย",  "en"=>"Thai Perfume",        "code"=>"015"],
  ["th"=>"สีขาว. ดอกปีบ",        "en"=>"Cork Tree Blossom",   "code"=>"016"],
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
?><!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Yakpho Aroma – E-Catalog Order Form (<?= htmlspecialchars($VERSION) ?>)</title>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
  :root{ --warm:#FDE68A; --cool:#BAE6FD; --bal:#E9D5FF; --brand:#54398a; }
  *{box-sizing:border-box}
  body{ font-family: system-ui, -apple-system, Segoe UI, Roboto, Inter, "Prompt", sans-serif; background:#fafafa; color:#222; margin:0; padding-bottom:110px; }
  .hero{ background: linear-gradient(135deg, #ffffff, #f4f0ff 60%); border-bottom:1px solid #eee; padding:24px 16px 18px; text-align:center; }
  .brand{ font-weight:800; letter-spacing:.3px; color:var(--brand); font-size: clamp(20px, 3.6vw, 32px); margin:2px 0 6px; }
  .sub{ color:#5b556d; font-size:clamp(12px, 2.6vw, 15px); opacity:.9; }
  .meta{ color:#6b7280; font-size:12px; margin-top:6px; }
  .wrapper{max-width:1000px;margin:18px auto;padding:0 10px;}
  table{ width:100%; border-collapse:collapse; background:#fff; border-radius:14px; overflow:hidden; box-shadow:0 4px 18px rgba(0,0,0,.06); }
  thead th{ background:#f3f2f8; padding:10px 8px; font-weight:700; font-size:14px; color:#4b3d6b; text-align:center; border-bottom:1px solid #ecebf5; }
  tbody td, tbody th{ border-bottom:1px solid #f2f2f2; padding:10px 8px; font-size:14px; }
  tbody th{ text-align:left; font-weight:600; color:#333; width:44%; }
  .scent-sub{ display:block; font-weight:400; color:#6b7280; font-size:12px; margin-top:2px; }
  .cell{ text-align:center; white-space:nowrap; }
  .qty{ display:inline-block; min-width:28px; text-align:center; font-weight:700; }
  .btn{ border:none; border-radius:8px; padding:6px 10px; line-height:1; cursor:pointer; font-weight:700; transition:transform .05s ease, box-shadow .15s ease; }
  .btn:active{ transform: translateY(1px); }
  .btn.minus{ background:#efefef; }
  .btn.plus{ background:#8b5cf6; color:#fff; }
  .cell.active[data-formula="H"]{ background: var(--warm); }
  .cell.active[data-formula="C"]{ background: var(--cool); }
  .cell.active[data-formula="B"]{ background: var(--bal); }
  .sku{ display:block; font-size:11px; color:#6b7280; margin-top:4px; }
  .sticky{ position:fixed; left:0; right:0; bottom:0; background:#ffffffea; backdrop-filter: blur(8px); border-top:1px solid #e8e8ee; padding:10px; }
  .sticky-inner{ max-width:1000px; margin:0 auto; display:flex; flex-wrap:wrap; gap:8px; align-items:center; justify-content:space-between; }
  .summary{ font-weight:700; color:#2b2440; font-size:15px; }
  .actions{ display:flex; gap:8px; }
  .copy-btn{ background:#6d28d9; color:#fff; border:none; border-radius:10px; padding:10px 14px; font-weight:800; cursor:pointer; font-size:15px; }
  .info{ max-width:1000px; margin:18px auto 120px; padding:0 10px; color:#4b5563; font-size:14px; }
  .info h3{ color:#3b2e66; }
  .note{ background:#fff; padding:14px; border:1px dashed #d9d5e6; border-radius:10px; }
  @media (max-width:720px){ thead .hide-sm{ display:none; } tbody .opt{ display:none; } tbody th{ width:auto; } }
</style>
</head>
<body>

<header class="hero">
  <div class="brand">🪶 Yakpho Aroma – Herbal Balm Order Form</div>
  <div class="sub">สั่งซื้อยาหม่อง 3 สูตร × 16 กลิ่น • คละได้ทั้งตาราง • ระบบเรทราคาตามจำนวนรวม (kg)</div>
  <div class="meta"><?= htmlspecialchars($COMPANY_NAME) ?> • เวอร์ชัน <?= htmlspecialchars($VERSION) ?> • อัปเดต: <?= htmlspecialchars($UPDATED_AT) ?></div>
</header>

<main class="wrapper">
  <table id="orderTable" aria-describedby="tableHelp">
    <thead>
      <tr>
        <th>ลำดับ / กลิ่น (TH/EN)</th>
        <th class="hide-sm">สูตรร้อน<br><small>Hot (H)</small></th>
        <th class="hide-sm">สูตรเย็น<br><small>Cool (C)</small></th>
        <th class="hide-sm">สูตรนวดตัว<br><small>Balanced (B)</small></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($SCENTS as $i=>$s): ?>
        <tr>
          <th scope="row">
            <?= str_pad($i+1,2,"0",STR_PAD_LEFT) ?>. <?= htmlspecialchars($s["th"]) ?>
            <span class="scent-sub"><?= htmlspecialchars($s["en"]) ?></span>
          </th>
          <?php
            $code = $s["code"];
            foreach (["H","C","B"] as $f) {
              $sku = $f . $code;
              echo '<td>';
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

  <section class="info">
    <h3>Information</h3>
    <div class="note">
      <ul style="margin:8px 0 0 18px; line-height:1.6;">
        <li><strong>ระบบราคา / Pricing:</strong> ใช้เรทราคาต่อกิโลกรัมตามปริมาณรวมทุกสูตรและกลิ่น<br>
          <em>The wholesale price rate is based on total kilograms across all formulas and scents.</em></li>
        <li><strong>ค่าจัดส่งสินค้า / Shipping Policy:</strong></li>
        <ul style="margin-left:22px;">
          <li>🇹🇭 <strong>ภายในประเทศไทย (Domestic – Thailand):</strong> รวมค่าจัดส่งแล้ว 60 บาทต่อออเดอร์<br>
              <em>Shipping cost of 60 THB per order is already included in the total.</em></li>
          <li>🏢 <strong>มารับสินค้าเอง (Self Pickup):</strong> ไม่คิดค่าจัดส่ง<br>
              <em>No shipping fee for self-pickup at our warehouse.</em></li>
          <li>🌏 <strong>จัดส่งต่างประเทศ (International Delivery):</strong> คิดตามค่าขนส่งจริงจากบริษัทขนส่ง โดยไม่บวกเพิ่ม<br>
              <em>Charged at the actual shipping rate from the courier, with no extra markup.</em><br>
              <small><em>(For international orders, the 60 THB domestic shipping fee will be automatically deducted.)</em></small></li>
        </ul>
      </ul>
    </div>
  </section>
</main>

<footer class="sticky" role="contentinfo">
  <div class="sticky-inner">
    <div class="summary" id="summaryText">รวมทั้งหมด: 0 kg  |  ราคาต่อกก.: 0 THB  |  รวมสุทธิ: 0 THB</div>
    <div class="actions">
      <button class="copy-btn" id="copyBtn">📋 คัดลอกคำสั่งซื้อ</button>
    </div>
  </div>
</footer>

<script>
const PRICE_RATE = <?= $PRICE_RATE_JSON ?>;
const SCENTS     = <?= $SCENTS_JSON ?>;
function getRate(totalKg){ let unit=0; for(const r of PRICE_RATE){ if(totalKg>=r.min) unit=r.price; } return unit; }
function updateSummary(){
  const qtyEls = document.querySelectorAll(".qty"); let total=0;
  qtyEls.forEach(q=> total+=parseInt(q.textContent,10));
  const unit = total ? getRate(total) : 0; const grand = unit*total;
  document.getElementById("summaryText").textContent = `รวมทั้งหมด: ${total} kg  |  ราคาต่อกก.: ${unit} THB  |  รวมสุทธิ: ${grand.toLocaleString()} THB`;
}
document.querySelector("tbody").addEventListener("click",(e)=>{
  const btn=e.target.closest(".btn"); if(!btn) return;
  const cell=btn.closest(".cell"); const qtyEl=cell.querySelector(".qty");
  let val=parseInt(qtyEl.textContent,10);
  if(btn.classList.contains("plus")) val++; if(btn.classList.contains("minus")) val=Math.max(0,val-1);
  qtyEl.textContent=val; cell.classList.toggle("active", val>0); updateSummary();
});
function thaiNameCore(th){ return th.replace(/^สี[^.]*\.\s*/,'').trim(); }
function buildSummaryText(ref){
  const lines=[];
  lines.push("🪶 YAKPHO AROMA – ORDER SUMMARY");
  lines.push("#Ref"+ref);
  lines.push("-------------------------------");
  let total=0;
  document.querySelectorAll(".cell").forEach(cell=>{
    const qty=parseInt(cell.querySelector(".qty").textContent,10);
    if(qty>0){
      const sku=cell.dataset.sku; const code=sku.slice(1);
      const s=SCENTS.find(x=>x.code===code); const thCore=s?thaiNameCore(s.th):"";
      lines.push(`${sku} ${s?s.en:""} (${thCore}) × ${qty} kg`.trim());
      total+=qty;
    }
  });
  const unit= total? getRate(total):0; const grand=unit*total;
  lines.push("-------------------------------");
  lines.push(`รวมทั้งหมด: ${total} kg`);
  lines.push(`ราคาต่อกก.: ${unit} THB`);
  lines.push(`รวมสุทธิ: ${grand.toLocaleString()} THB`);
  lines.push("(รวมค่าจัดส่งภายในประเทศ 60 บาท)");
  lines.push("-------------------------------");
  lines.push("<?= htmlspecialchars($COMPANY_NAME) ?>");
  return lines.join("\n");
}
async function doCopyAndSave(){
  const ref = Math.floor(Date.now()/1000).toString();
  const text = buildSummaryText(ref);
  try{ await navigator.clipboard.writeText(text); }
  catch(err){
    const ta=document.createElement("textarea"); ta.value=text; document.body.appendChild(ta);
    ta.select(); document.execCommand("copy"); document.body.removeChild(ta);
  }
  try {
    const res = await fetch("", {
      method:"POST",
      headers:{ "Content-Type":"application/x-www-form-urlencoded;charset=UTF-8" },
      body: new URLSearchParams({ order_text:text, ref })
    });
    await res.json();
  } catch(e){}
  Swal.fire({
    title:"🪶 คัดลอกคำสั่งซื้อเรียบร้อยแล้ว",
    html:"นำข้อความที่คัดลอกไป <b>วางในช่องแชต</b> เพื่อแจ้งคำสั่งซื้อได้เลยค่ะ<br><small>รองรับ LINE / Messenger / WhatsApp</small>",
    confirmButtonText:"ตกลง",
    confirmButtonColor:"#6d28d9",
    background:"#ffffff",
    color:"#2b2440"
  });
}
document.getElementById("copyBtn").addEventListener("click", doCopyAndSave);
updateSummary();
</script>

</body>
</html>
