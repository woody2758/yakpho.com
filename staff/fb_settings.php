<?php
require_once "db.php";
require_login();

// ── Handle Config Updates ──
if (isset($_POST['update_configs'])) {
    $stmt = $db->prepare("REPLACE INTO fb_configs (cfg_key, cfg_value) VALUES (?, ?)");
    $stmt->execute(['scroll_depth', $_POST['scroll_depth'] ?? '3']);
    $stmt->execute(['use_cities_mode', isset($_POST['use_cities_mode']) ? '1' : '0']);
    $stmt->execute(['public_only', isset($_POST['public_only']) ? '1' : '0']);
    $stmt->execute(['enable_gemini', isset($_POST['enable_gemini']) ? '1' : '0']);
    $stmt->execute(['active_persona', $_POST['active_persona'] ?? 'worldwide']);
    
    // บันทึก Cookie Cloud
    if (isset($_POST['cookie_json_worldwide'])) $stmt->execute(['cookie_json_worldwide', $_POST['cookie_json_worldwide']]);
    if (isset($_POST['cookie_json_domestic']))  $stmt->execute(['cookie_json_domestic', $_POST['cookie_json_domestic']]);
    if (isset($_POST['cookie_json_esther']))    $stmt->execute(['cookie_json_esther', $_POST['cookie_json_esther']]);

    $stmt->execute(['gemini_api_key', $_POST['gemini_api_key'] ?? '']);
    $msg = "บันทึกการตั้งค่าทั้งหมดเรียบร้อยแล้ว";
}

if (isset($_POST['clear_history'])) {
    try {
        $db->exec("TRUNCATE TABLE fb_scrape_history");
        $msg = "ล้างประวัติการค้นหา (History) สำเร็จ บอทจะเริ่มค้นหาใหม่ทั้งหมดจากศูนย์ครับ";
    } catch (PDOException $e) {}
}

// ── Handle Keyword CRUD ──
if (isset($_POST['add_keyword'])) {
    $raw_kw = $_POST['keyword'] ?? '';
    $lines = explode("\n", str_replace("\r", "", $raw_kw));
    $added = 0;
    
    $stmt = $db->prepare("INSERT IGNORE INTO fb_keywords (keyword) VALUES (?)");
    foreach ($lines as $line) {
        $kw = trim($line);
        if ($kw !== '') {
            $stmt->execute([$kw]);
            if ($stmt->rowCount() > 0) $added++;
        }
    }
    $msg = "เพิ่มคีย์เวิร์ดใหม่ $added คำเรียบร้อยแล้ว";
}
// Edit and Delete are now handled via AJAX using api/fb_keywords.php

// ── Load Data ──
$keywords = $db->query("SELECT * FROM fb_keywords ORDER BY id DESC")->fetchAll();
$configs = $db->query("SELECT cfg_key, cfg_value FROM fb_configs")->fetchAll(PDO::FETCH_KEY_PAIR);

include "components/header.php";
?>

<style>
  .nav-tabs .nav-link.active {
    font-weight: 600;
    border-bottom: 2px solid var(--tblr-primary) !important;
    color: var(--tblr-primary) !important;
  }
  .card-settings {
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
  }
  .form-label-bold {
    font-weight: 600;
    color: #334155;
    margin-bottom: 0.5rem;
  }
  .cookie-textarea {
    background: #f8fafc !important;
    border: 1px solid #e2e8f0;
    font-family: 'JetBrains Mono', 'Fira Code', monospace;
    font-size: 11px !important;
    color: #1e293b;
  }
  .tab-card-body {
    padding: 1.5rem !important;
    min-height: 500px;
  }
</style>

<div class="page-header d-print-none">
  <div class="container-fluid">
    <div class="row g-2 align-items-center">
      <div class="col">
        <h2 class="page-title">
          <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-settings-automation text-primary me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37a1.724 1.724 0 0 0 2.572 -1.065z" /><path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" /></svg>
          ระบบควบคุมบอท & ตั้งค่าส่วนกลาง
        </h2>
        <div class="text-muted mt-1 small">จัดการตัวตน Persona, Cloud Cookies และเป้าหมายการค้นหาของทีมงาน AI</div>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-fluid">
    
    <?php if(isset($msg)): ?>
      <div class="alert alert-important alert-success alert-dismissible mb-4" role="alert">
        <div class="d-flex">
          <div><svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg></div>
          <div><?= $msg ?></div>
        </div>
        <a class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="close"></a>
      </div>
    <?php endif; ?>

    <div class="card card-settings">
      <div class="card-header p-0">
        <ul class="nav nav-tabs card-header-tabs m-0 px-3" data-bs-toggle="tabs" style="border-bottom: 1px solid #eee;">
          <li class="nav-item">
            <a href="#tab-general" class="nav-link active py-3" data-bs-toggle="tab">⚙️ พื้นฐาน & Keywords</a>
          </li>
          <li class="nav-item">
            <a href="#tab-identity" class="nav-link py-3" data-bs-toggle="tab">👤 ตัวตนบอท (Persona & Cookies)</a>
          </li>
          <li class="nav-item">
            <a href="#tab-geography" class="nav-link py-3" data-bs-toggle="tab">📍 พื้นที่เป้าหมาย (Geography)</a>
          </li>
        </ul>
      </div>
      
      <div class="tab-content">
        
        <!-- Tab 1: General -->
        <div class="tab-pane active show" id="tab-general">
          <div class="tab-card-body">
            <div class="row g-4">
              <div class="col-md-7">
                <div class="card border-0 shadow-none">
                  <div class="card-header px-0 bg-transparent border-0"><h3 class="card-title fw-bold">Search Keywords Management</h3></div>
                  <div class="card-body px-0">
                    <form method="POST" class="mb-4">
                      <div class="mb-3">
                        <label class="form-label-bold">คีย์เวิร์ดสำหรับระบุกลุ่มเป้าหมาย (บรรทัดละ 1 คำ)</label>
                        <textarea name="keyword" class="form-control border-primary" rows="4" placeholder="เช่น Massage Shop UK&#10;Spa Business..."></textarea>
                      </div>
                      <button type="submit" name="add_keyword" class="btn btn-primary px-4 fw-bold">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                        บันทึกคีย์เวิร์ดเข้าคลัง
                      </button>
                      <button type="submit" name="clear_history" class="btn btn-warning px-4 fw-bold ms-2" onclick="return confirm('ยืนยันการล้างประวัติ? บอทจะทำการเริ่มค้นหาคีย์เวิร์ดเก่าซ้ำอีกครั้งทั้งหมด')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                        ล้างประวัติการค้นหา
                      </button>
                    </form>

                    <div class="table-responsive" style="max-height: 350px; overflow-y: auto; border: 1px solid #f1f5f9; border-radius: 8px;">
                      <table class="table table-vcenter table-sm table-striped m-0">
                        <thead class="sticky-top bg-white">
                          <tr><th>Keyword</th><th class="text-end">Action</th></tr>
                        </thead>
                        <tbody>
                          <?php foreach($keywords as $k): ?>
                          <tr id="kw-row-<?= $k['id'] ?>">
                            <td><span class="fw-medium" id="kw-text-<?= $k['id'] ?>"><?= htmlspecialchars($k['keyword']) ?></span></td>
                            <td class="text-end">
                              <button class="btn btn-ghost-primary btn-sm" onclick="editKeyword(<?= $k['id'] ?>)">Edit</button>
                              <button class="btn btn-ghost-danger btn-sm" onclick="deleteKeyword(<?= $k['id'] ?>)">Delete</button>
                            </td>
                          </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-md-5">
                <form method="POST" class="card bg-primary-lt border-0">
                  <div class="card-body">
                    <h3 class="card-title fw-bold text-primary mb-3">AI & Bot Behaviors</h3>
                    
                    <div class="mb-3">
                      <label class="form-label-bold">Scroll Depth (ระดับการไถหน้าจอข้อมูล)</label>
                      <div class="input-group">
                        <input type="number" name="scroll_depth" class="form-control fw-bold border-primary" value="<?= $configs['scroll_depth'] ?? '3' ?>" min="1" max="50">
                        <span class="input-group-text bg-primary text-white border-primary">ครั้ง</span>
                      </div>
                      <small class="text-muted italic">ความลึกยิ่งมาก ยิ่งใช้เวลาและเสี่ยงต่อการถูกตรวจสอบ</small>
                    </div>

                    <div class="mb-4">
                      <label class="form-check form-switch cursor-pointer">
                        <input class="form-check-input border-primary" type="checkbox" name="use_cities_mode" <?= ($configs['use_cities_mode'] ?? '0') == '1' ? 'checked' : '' ?>>
                        <span class="form-check-label fw-bold">🔄 Search Cities Loop Mode</span>
                      </label>
                      <small class="text-muted d-block ms-5">หมุนเวียนคีย์เวิร์ดผสมกับรายชื่อเมืองเป้าหมาย</small>
                    </div>

                    <div class="mb-4 border-top pt-3">
                      <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label-bold mb-0">Gemini AI Engine</label>
                        <label class="form-check form-switch form-switch-lg cursor-pointer m-0">
                          <input class="form-check-input" type="checkbox" name="enable_gemini" <?= ($configs['enable_gemini'] ?? '1') == '1' ? 'checked' : '' ?>>
                          <span class="form-check-label fw-bold">เปิดใช้งาน AI</span>
                        </label>
                      </div>
                      <p class="text-muted small mb-2">หากปิดใช้งาน บอทจะข้ามการเรียก Gemini API และใช้ระบบ Regex (คัดกรองคำ) แทนเพื่อประหยัดโควตา</p>
                      <div class="input-group input-group-flat">
                        <input type="password" name="gemini_api_key" id="gemini_api_key" class="form-control border-primary" value="<?= htmlspecialchars($configs['gemini_api_key'] ?? '') ?>" placeholder="ใส่ API Key ที่นี่...">
                        <span class="input-group-text border-primary bg-white">
                          <a href="javascript:void(0)" class="link-secondary" onclick="togglePassword('gemini_api_key')" title="Show password">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                          </a>
                        </span>
                      </div>
                    </div>

                    <button type="submit" name="update_configs" class="btn btn-primary w-100 py-3 fw-bold">
                      <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2" /><path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M14 4l0 4l-4 0l0-4" /></svg>
                      บันทึกการตั้งค่าพฤติกรรม
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab 2: Identity & Cookies -->
        <div class="tab-pane" id="tab-identity">
          <div class="tab-card-body">
            <div class="row g-4">
              <div class="col-md-5">
                <form method="POST" class="card border-primary shadow-sm h-100 mb-0">
                  <div class="card-status-top bg-primary"></div>
                  <div class="card-body d-flex flex-column">
                    <h3 class="card-title fw-bold text-primary mb-4">🎭 สลับตัวตนบอท (Active Persona)</h3>
                    <div class="mb-4">
                      <p class="text-muted small mb-3">เลือกบทบาทบอทที่คุณต้องการให้ทำงานบน VPS ตอนนี้ ระบบจะเปลี่ยนสลับคุกกี้และเพจตรวจสอบอัตลักษณ์ให้โดยอัตโนมัติ</p>
                      <select name="active_persona" class="form-select form-select-lg border-primary fw-bold text-primary shadow-sm">
                        <option value="worldwide" <?= ($configs['active_persona'] ?? 'worldwide') == 'worldwide' ? 'selected' : '' ?>>🌐 Worldwide (Phil - YakPho.Aroma)</option>
                        <option value="domestic"  <?= ($configs['active_persona'] ?? '') == 'domestic' ? 'selected' : '' ?>>🇹🇭 Domestic (Som - YakPho Official)</option>
                        <option value="esther"    <?= ($configs['active_persona'] ?? '') == 'esther' ? 'selected' : '' ?>>🏢 Esther Aroma (Company Main)</option>
                      </select>
                    </div>
                    <div class="mt-auto">
                      <button type="submit" name="update_configs" class="btn btn-primary w-100 py-3 fw-bold shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 11l-4 4l4 4m-4 -4h11a4 4 0 0 0 0 -8h-1" /></svg>
                        สลับตัวตนทีมงาน AI ทันที
                      </button>
                    </div>
                  </div>
                </form>
              </div>

              <div class="col-md-7">
                <form method="POST" class="card border-dark shadow-sm h-100 mb-0">
                  <div class="card-status-top bg-dark"></div>
                  <div class="card-body">
                    <h3 class="card-title fw-bold text-dark mb-4">🔑 Cookie Cloud Storage (ความลับระดับสูง)</h3>
                    <p class="text-muted small mb-3 italic">* นำคุกกี้ JSON จาก Extension 'EditThisCookie' มาวางในช่องตามแบรนด์ ระบบบอทจะดึงไปใช้รันอัตโนมัติจากทุกที่</p>
                    
                    <div class="row g-3">
                      <div class="col-md-4">
                        <label class="form-label small text-success fw-bold">🌐 Worldwide JSON</label>
                        <textarea name="cookie_json_worldwide" class="form-control cookie-textarea" rows="12" placeholder='[{"name": "c_user", ...}]'><?= htmlspecialchars($configs['cookie_json_worldwide'] ?? '') ?></textarea>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label small text-info fw-bold">🇹🇭 Domestic JSON</label>
                        <textarea name="cookie_json_domestic" class="form-control cookie-textarea" rows="12" placeholder='[{"name": "c_user", ...}]'><?= htmlspecialchars($configs['cookie_json_domestic'] ?? '') ?></textarea>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label small text-warning fw-bold">🏢 Esther Aroma JSON</label>
                        <textarea name="cookie_json_esther" class="form-control cookie-textarea" rows="12" placeholder='[{"name": "c_user", ...}]'><?= htmlspecialchars($configs['cookie_json_esther'] ?? '') ?></textarea>
                      </div>
                    </div>
                    
                    <div class="mt-4">
                      <button type="submit" name="update_configs" class="btn btn-dark w-100 py-2 fw-bold shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3c7.2 0 9 1.8 9 9s-1.8 9 -9 9s-9 -1.8 -9 -9s1.8 -9 9 -9z" /><path d="M12 8l0 4" /><path d="M12 16l.01 0" /></svg>
                        🔒 บันทึกและซิงค์คุกกี้ไปยังบอททั้งหมด
                      </button>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab 3: Geography -->
        <div class="tab-pane" id="tab-geography">
          <div class="tab-card-body">
            <div class="row g-4">
              <div class="col-md-6">
                <div class="card border-info shadow-sm">
                  <div class="card-body p-4 text-center">
                    <div class="mb-3">
                      <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-info" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M21 12l-9 -9l-9 9a5 5 0 0 0 6.67 4.67" /><path d="M9 21h6a2 2 0 0 0 2 -2v-2" /><path d="M19 12h2l-9 -9l-9 9h2" /></svg>
                    </div>
                    <h3 class="fw-bold mb-2">จัดการเมืองเป้าหมาย (Target Cities)</h3>
                    <p class="text-muted mb-4">ระบุพิกัดเมืองที่ต้องการให้บอทเข้าไปเจาะข้อมูลเป็นพิเศษ บอทจะนำรายชื่อเมืองไปวนลูปค้นหากลุ่มและเพจในพื้นที่นั้นๆ</p>
                    <div class="d-grid gap-2">
                       <a href="cities.php" class="btn btn-info py-2 fw-bold">เข้าสู่เมนูจัดการรายชื่อเมือง</a>
                       <a href="import_cities.php" class="btn btn-outline-info">นำเข้าข้อมูลเมือง (CSV / Excel)</a>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-6 text-center d-flex align-items-center">
                <div class="p-4 w-100 bg-azure-lt border border-azure rounded-3">
                  <h4 class="mb-3 text-azure fw-bold">📍 เกร็ดความรู้เกี่ยวกับพื้นที่</h4>
                  <p class="mb-0 text-muted italic">การใช้ระบบ Search Cities Mode จะช่วยให้เราได้ลูกค้าที่ "ตรงกลุ่ม" ยิ่งขึ้น เช่น 'Massage London' 
                  จะช่วยให้ AI แยกแยะระหว่างบกลุ่มคนไทยในลอนดอนกับกลุ่มเมืองอื่นๆ ได้อย่างชัดเจนครับ</p>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
function editKeyword(id) {
    const el = document.getElementById('kw-text-' + id);
    const currentKw = el.innerText;
    let newKw = prompt("แก้ไขคีย์เวิร์ด:", currentKw);
    if (newKw !== null && newKw.trim() !== "" && newKw !== currentKw) {
        newKw = newKw.trim();
        fetch('api/fb_keywords.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'edit', id: id, keyword: newKw})
        })
        .then(r => r.json())
        .then(res => {
            if (res.status) {
                el.innerText = newKw;
            } else {
                alert("Error: " + res.message);
            }
        })
        .catch(err => alert("System Error: " + err));
    }
}

function deleteKeyword(id) {
    if (confirm('ยืนยันลบคีย์เวิร์ดนี้?')) {
        fetch('api/fb_keywords.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'delete', id: id})
        })
        .then(r => r.json())
        .then(res => {
            if (res.status) {
                document.getElementById('kw-row-' + id).remove();
            } else {
                alert("Error: " + res.message);
            }
        })
        .catch(err => alert("System Error: " + err));
    }
}

function togglePassword(id) {
    const input = document.getElementById(id);
    if (input.type === "password") input.type = "text";
    else input.type = "password";
}
</script>

<?php include "components/footer.php"; ?>
