<?php
require_once "db.php";
require_login();

// ดึงเมืองทั้งหมดที่มีในระบบ (lowercase สำหรับเปรียบเทียบ)
$existing_stmt = $db->query("SELECT LOWER(TRIM(city)) as city, LOWER(TRIM(country)) as country FROM target_cities");
$existing_raw = $existing_stmt->fetchAll();
$existing_map = [];
foreach ($existing_raw as $row) {
    $key = $row['city'] . '|||' . $row['country'];
    $existing_map[$key] = true;
}

// ฟังก์ชัน: อ่านไฟล์ Excel .xlsx แบบไม่ต้องใช้ Library
function parse_xlsx_to_rows($filepath) {
    $rows = [];
    $zip = new ZipArchive();
    if ($zip->open($filepath) !== true) return $rows;

    $sharedStrings = [];
    if (($xml = $zip->getFromName("xl/sharedStrings.xml")) !== false) {
        $ss = simplexml_load_string($xml);
        foreach ($ss->si as $si) {
            $sharedStrings[] = (string)$si->t ?? implode('', array_map(fn($r) => (string)$r->t, (array)($si->r ?? [])));
        }
    }

    $sheetXml = $zip->getFromName("xl/worksheets/sheet1.xml");
    $zip->close();
    if (!$sheetXml) return $rows;

    $sheet = simplexml_load_string($sheetXml);
    if (!$sheet) return $rows;

    foreach ($sheet->sheetData->row as $row) {
        $rowData = [];
        foreach ($row->c as $cell) {
            $t = (string)($cell['t'] ?? '');
            $v = (string)($cell->v ?? '');
            if ($t === 's') {
                $rowData[] = $sharedStrings[(int)$v] ?? '';
            } else {
                $rowData[] = $v;
            }
        }
        $rows[] = $rowData;
    }
    return $rows;
}

// ฟังก์ชัน: แปลง rows เป็น city/country/region
function extract_city_country($rows) {
    $result = [];
    foreach ($rows as $i => $row) {
        if (count($row) < 2) continue;
        $city = trim($row[0]);
        // Support 3-column input (City, State, Country). The Country is always the LAST column.
        $country = trim(end($row));
        $region = '';
        if (count($row) >= 3) {
            $region = trim($row[1]);
        }
        
        // ข้าม Header row
        if (strtolower($city) === 'city' && (strtolower($country) === 'country' || strtolower($region) === 'state')) continue;
        if ($city === '' || $country === '') continue;
        $result[] = ['city' => $city, 'region' => $region, 'country' => $country];
    }
    return $result;
}

$step = 'upload'; // upload | preview | save_success
$parsed_rows = [];
$new_rows = [];
$duplicate_rows = [];
$error = '';
$save_count = 0;

// ===== STEP 2: บันทึกข้อมูลที่ผ่านการตรวจสอบแล้ว =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_save'])) {
    $cities_to_save = json_decode($_POST['cities_json'], true);
    $insert_stmt = $db->prepare("INSERT IGNORE INTO target_cities (city, region, country, status) VALUES (?, ?, ?, 'pending')");
    foreach ($cities_to_save as $entry) {
        $region = isset($entry['region']) && $entry['region'] !== '' ? trim($entry['region']) : null;
        $insert_stmt->execute([trim($entry['city']), $region, trim($entry['country'])]);
        if ($insert_stmt->rowCount() > 0) $save_count++;
    }
    $step = 'save_success';
}

// ===== STEP 1: รับไฟล์หรือข้อความและ Preview =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_FILES['city_file']) || isset($_POST['city_text']))) {
    $rows = [];
    $is_upload = isset($_FILES['city_file']) && $_FILES['city_file']['error'] === UPLOAD_ERR_OK;
    $is_text = !empty(trim($_POST['city_text'] ?? ''));

    if ($is_upload) {
        $file = $_FILES['city_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $tmpPath = $file['tmp_name'];

        if (!in_array($ext, ['csv', 'txt', 'xlsx'])) {
            $error = "รองรับเฉพาะ .csv, .txt, .xlsx เท่านั้น";
        } else {
            if ($ext === 'xlsx') {
                $rows = parse_xlsx_to_rows($tmpPath);
            } else {
                if (($handle = fopen($tmpPath, "r")) !== false) {
                    $delimiter = ($ext === 'txt') ? "\t" : ",";
                    while (($line = fgetcsv($handle, 1000, $delimiter)) !== false) {
                        if ($ext === 'txt' && count($line) < 2) {
                            $parts = explode(',', $line[0]);
                            if (count($parts) >= 2) $line = $parts;
                        }
                        $rows[] = $line;
                    }
                    fclose($handle);
                }
            }
        }
    } elseif ($is_text) {
        $text = trim($_POST['city_text']);
        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!$line) continue;
            // ลองแยกด้วย comma หรือ tab
            $parts = preg_split('/[,\t]/', $line);
            if (count($parts) >= 2) {
                // เก็บ Array ไว้ทั้งหมดเผื่อมี 3 ส่วน (City, State, Country)
                $rows[] = array_map('trim', $parts);
            }
        }
    } else {
        if (isset($_FILES['city_file']) && $_FILES['city_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $error = "เกิดข้อผิดพลาดในการอัปโหลดไฟล์";
        } else {
            $error = "กรุณาเลือกไฟล์หรือระบุข้อความนำเข้า";
        }
    }

    if (!$error) {
        $parsed_rows = extract_city_country($rows);

        if (empty($parsed_rows)) {
            $error = "ไม่พบข้อมูล city, country ในไฟล์หรือข้อความที่ระบุ กรุณาตรวจสอบรูปแบบ (City, Country)";
        } else {
            foreach ($parsed_rows as $entry) {
                $key = strtolower(trim($entry['city'])) . '|||' . strtolower(trim($entry['country']));
                if (isset($existing_map[$key])) {
                    $duplicate_rows[] = $entry;
                } else {
                    $new_rows[] = $entry;
                    $existing_map[$key] = true;
                }
            }
            $step = 'preview';
        }
    }
}

include "components/header.php";
?>

<!-- Page header -->
<div class="page-header d-print-none">
  <div class="container-fluid">
    <div class="row g-2 align-items-center">
      <div class="col">
        <div class="page-pretitle"><a href="cities.php" class="text-muted">← กลับหน้ารายการเมือง</a></div>
        <h2 class="page-title">นำเข้าข้อมูลเมือง (Bulk Import)</h2>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-fluid">
    <div class="col-lg-8 mx-auto">

    <?php if ($step === 'upload'): ?>
      <!-- === STEP 1: Upload Form === -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">📂 อัปโหลดไฟล์รายชื่อเมือง</h3>
        </div>
        <div class="card-body">
          <?php if($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
          <?php endif; ?>

          <div class="alert alert-info mb-3">
            <strong>รูปแบบข้อมูลที่รองรับ (3 คอลัมน์):</strong>
            <ul class="mb-0 mt-1">
              <li><strong>แบบ 3 ช่อง (แนะนำ):</strong> <code>City, Region, Country</code><br> <em>(เช่น: Lviv, Lviv Oblast, Ukraine)</em></li>
              <li><strong>แบบ 2 ช่อง (รองรับ):</strong> <code>City, Country</code><br> <em>(เช่น: London, United Kingdom)</em></li>
            </ul>
            <div class="mt-2 text-muted small">💡 สามารถอัปโหลดไฟล์ <code>.csv</code>, <code>.txt</code> หรือ <code>.xlsx</code> (A=City, B=Region, C=Country)</div>
          </div>

          <form action="import_cities.php" method="POST" enctype="multipart/form-data">
            <div class="row g-3">
              <div class="col-md-6">
                <div class="card bg-transparent border-dashed">
                  <div class="card-body">
                    <label class="form-label fw-bold text-dark">1. อัปโหลดไฟล์ (File Upload)</label>
                    <input type="file" name="city_file" class="form-control bg-white text-dark" accept=".csv,.txt,.xlsx">
                    <div class="small text-muted mt-2">รองรับ .xlsx, .csv, .txt</div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="card bg-transparent border-dashed">
                  <div class="card-body">
                    <label class="form-label fw-bold text-dark">2. วางข้อความ (Manual Paste)</label>
                    <textarea name="city_text" class="form-control bg-white text-dark" rows="3" placeholder="Kyiv, Kyiv City, Ukraine&#10;Lviv, Lviv Oblast, Ukraine"></textarea>
                    <div class="small text-muted mt-2">พิมพ์ 1 แถวต่อเมือง (City, Region, Country)</div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="mt-4">
              <button type="submit" class="btn btn-primary w-100 py-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /><line x1="12" y1="11" x2="12" y2="17" /><polyline points="9 14 12 11 15 14" /></svg>
                ตรวจสอบและเตรียมนำเข้าข้อมูล
              </button>
            </div>
          </form>
        </div>
      </div>

    <?php elseif ($step === 'preview'): ?>
      <!-- === STEP 2: Preview with duplicate info === -->
      <div class="card mb-3">
        <div class="card-header">
          <h3 class="card-title">🔍 ผลการตรวจสอบ (Preview)</h3>
          <div class="card-options">
            <span class="badge bg-success me-1"><?= count($new_rows) ?> ใหม่</span>
            <span class="badge bg-warning"><?= count($duplicate_rows) ?> ซ้ำ</span>
          </div>
        </div>
        <div class="card-body p-0">
          
          <?php if (!empty($new_rows)): ?>
          <div class="table-responsive">
            <table class="table table-sm table-vcenter mb-0">
              <thead class="table-light">
                <tr>
                  <th colspan="3" class="py-2 px-3 text-success">✅ รายการใหม่ที่จะถูกบันทึก (<?= count($new_rows) ?> รายการ)</th>
                </tr>
                <tr><th>City</th><th>Region</th><th>Country</th><th>สถานะ</th></tr>
              </thead>
              <tbody>
                <?php foreach ($new_rows as $r): ?>
                <tr>
                  <td><?= htmlspecialchars($r['city']) ?></td>
                  <td><?= htmlspecialchars($r['region'] ?? '-') ?></td>
                  <td><?= htmlspecialchars($r['country']) ?></td>
                  <td><span class="badge bg-success-lt text-success">✅ เพิ่มใหม่</span></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>

          <?php if (!empty($duplicate_rows)): ?>
          <div class="table-responsive">
            <table class="table table-sm table-vcenter mb-0">
              <thead class="table-light">
                <tr>
                  <th colspan="3" class="py-2 px-3 text-warning">⚠️ รายการซ้ำ — ข้ามการบันทึก (<?= count($duplicate_rows) ?> รายการ)</th>
                </tr>
                <tr><th>City</th><th>Region</th><th>Country</th><th>สถานะ</th></tr>
              </thead>
              <tbody>
                <?php foreach ($duplicate_rows as $r): ?>
                <tr>
                  <td class="text-muted"><?= htmlspecialchars($r['city']) ?></td>
                  <td class="text-muted"><?= htmlspecialchars($r['region'] ?? '-') ?></td>
                  <td class="text-muted"><?= htmlspecialchars($r['country']) ?></td>
                  <td><span class="badge bg-warning-lt text-warning">⚠️ ซ้ำ — ข้าม</span></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>

        </div>
        <div class="card-footer">
          <div class="row">
            <div class="col">
              <a href="import_cities.php" class="btn">← อัปโหลดใหม่อีกครั้ง</a>
            </div>
            <div class="col-auto">
              <?php if (!empty($new_rows)): ?>
              <form action="import_cities.php" method="POST">
                <input type="hidden" name="confirm_save" value="1">
                <input type="hidden" name="cities_json" value="<?= htmlspecialchars(json_encode($new_rows)) ?>">
                <button type="submit" class="btn btn-primary">
                  💾 ยืนยัน บันทึก <?= count($new_rows) ?> เมือง
                </button>
              </form>
              <?php else: ?>
                <span class="text-muted">ไม่มีรายการใหม่ที่ต้องบันทึก</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

    <?php elseif ($step === 'save_success'): ?>
      <!-- === STEP 3: Success === -->
      <div class="card">
        <div class="card-body text-center py-5">
          <div class="mb-3" style="font-size: 4rem;">✅</div>
          <h2>บันทึกสำเร็จ!</h2>
          <p class="text-muted">เพิ่มเมืองเข้าคิว Pending ทั้งหมด <strong><?= $save_count ?></strong> เมือง</p>
          <div class="mt-4">
            <a href="cities.php" class="btn btn-outline-primary mx-1">← กลับหน้ารายการเมือง</a>
            <a href="import_cities.php" class="btn btn-primary mx-1">เพิ่มเมืองชุดต่อไป →</a>
          </div>
        </div>
      </div>

    <?php endif; ?>

    </div>
  </div>
</div>

<?php include "components/footer.php"; ?>
