<?php
require_once "db.php";

// ── Handle AJAX Cropped Image Upload ──
if (isset($_FILES['cropped_image'])) {
    header('Content-Type: application/json');
    $upload_dir = 'uploads/creative/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    
    $tmp_name = $_FILES['cropped_image']['tmp_name'];
    if ($_FILES['cropped_image']['error'] === UPLOAD_ERR_OK) {
        $filename = time() . '_' . rand(1000,9999) . '.jpg';
        $target = $upload_dir . $filename;
        if (move_uploaded_file($tmp_name, $target)) {
            // Generate Thumbnail
            try {
                list($width, $height) = getimagesize($target);
                if ($width > 0 && $height > 0) {
                    $thumb_width = 300;
                    $thumb_height = floor($height * ($thumb_width / $width));
                    $thumb = imagecreatetruecolor($thumb_width, $thumb_height);
                    $source = imagecreatefromjpeg($target);
                    if ($source) {
                        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $thumb_width, $thumb_height, $width, $height);
                        $thumb_path = str_replace('.jpg', '_thumb.jpg', $target);
                        imagejpeg($thumb, $thumb_path, 80);
                        imagedestroy($thumb);
                        imagedestroy($source);
                    }
                }
            } catch (Exception $e) {}

            $zone = $_POST['target_zone'] ?? 'All';
            $stmt = $db->prepare("INSERT INTO creative_images (file_path, target_zone) VALUES (?, ?)");
            $stmt->execute([$target, $zone]);
            echo json_encode(['status' => 'success']);
            exit;
        }
    }
    echo json_encode(['status' => 'error']);
    exit;
}

// ── Handle AI Settings Save ──
if (isset($_POST['save_ai_prompts'])) {
    $stmt = $db->prepare("INSERT INTO fb_configs (cfg_key, cfg_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE cfg_value=VALUES(cfg_value)");
    $stmt->execute(['ai_prompt_role', trim($_POST['ai_prompt_role'])]);
    $stmt->execute(['ai_prompt_green', trim($_POST['ai_prompt_green'])]);
    $stmt->execute(['ai_prompt_yellow', trim($_POST['ai_prompt_yellow'])]);
    $stmt->execute(['ai_prompt_red', trim($_POST['ai_prompt_red'])]);
    
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success']);
        exit;
    }
}

// Auto migration for new columns
try { $db->exec("ALTER TABLE creative_contents ADD COLUMN campaign_tag VARCHAR(255) NULL"); } catch (Exception $e) {}

// ── Handle Content AJAX Actions ──
if (isset($_POST['ajax_content_action'])) {
    header('Content-Type: application/json');
    $action = $_POST['ajax_content_action'];
    
    if ($action === 'add') {
        $strategy = $_POST['ai_strategy'] ?? 'Green';
        $content_text = $_POST['content_text'] ?? '';
        $target_zone = $_POST['target_zone'] ?? 'All';
        $campaign_tag = !empty($_POST['campaign_tag']) ? $_POST['campaign_tag'] : null;
        if (trim($content_text) !== '') {
            $stmt = $db->prepare("INSERT INTO creative_contents (ai_strategy, target_zone, content_text, campaign_tag) VALUES (?, ?, ?, ?)");
            $stmt->execute([$strategy, $target_zone, $content_text, $campaign_tag]);
            echo json_encode(['ok' => true]);
            exit;
        }
        echo json_encode(['ok' => false, 'msg' => 'เนื้อหาว่างเปล่า']);
        exit;
    }
    
    if ($action === 'edit') {
        $id = $_POST['id'];
        $target_zone = $_POST['target_zone'] ?? 'All';
        $content_text = $_POST['content_text'] ?? '';
        $campaign_tag = !empty($_POST['campaign_tag']) ? $_POST['campaign_tag'] : null;
        if (trim($content_text) !== '') {
            $stmt = $db->prepare("UPDATE creative_contents SET target_zone = ?, content_text = ?, campaign_tag = ? WHERE id = ?");
            $stmt->execute([$target_zone, $content_text, $campaign_tag, $id]);
            echo json_encode(['ok' => true]);
            exit;
        }
        echo json_encode(['ok' => false, 'msg' => 'เนื้อหาว่างเปล่า']);
        exit;
    }
    
    if ($action === 'delete') {
        $id = $_POST['id'];
        $stmt = $db->prepare("SELECT custom_images FROM creative_contents WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row && !empty($row['custom_images'])) {
            $images = json_decode($row['custom_images'], true);
            if (is_array($images)) {
                foreach ($images as $img) {
                    if (file_exists($img)) unlink($img);
                }
            }
        }
        $stmt = $db->prepare("DELETE FROM creative_contents WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['ok' => true]);
        exit;
    }
    
    if ($action === 'upload_custom_image') {
        $id = $_POST['id'];
        if (isset($_FILES['custom_image']) && $_FILES['custom_image']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['custom_image']['tmp_name'];
            $filename = time() . '_' . rand(1000,9999) . '.jpg';
            $target = 'uploads/creative/custom/' . $filename;
            if (move_uploaded_file($tmp_name, $target)) {
                $stmt = $db->prepare("SELECT custom_images FROM creative_contents WHERE id = ?");
                $stmt->execute([$id]);
                $row = $stmt->fetch();
                $images = [];
                if ($row && !empty($row['custom_images'])) {
                    $images = json_decode($row['custom_images'], true) ?: [];
                }
                $images[] = $target;
                
                $stmt = $db->prepare("UPDATE creative_contents SET custom_images = ? WHERE id = ?");
                $stmt->execute([json_encode($images), $id]);
                echo json_encode(['ok' => true, 'img_path' => $target]);
                exit;
            }
        }
        echo json_encode(['ok' => false, 'msg' => 'Upload failed']);
        exit;
    }

    if ($action === 'delete_custom_image') {
        $id = $_POST['id'];
        $img_path = $_POST['img_path'];
        $stmt = $db->prepare("SELECT custom_images FROM creative_contents WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row && !empty($row['custom_images'])) {
            $images = json_decode($row['custom_images'], true) ?: [];
            $new_images = [];
            foreach ($images as $img) {
                if ($img === $img_path) {
                    if (file_exists($img)) unlink($img);
                } else {
                    $new_images[] = $img;
                }
            }
            $stmt = $db->prepare("UPDATE creative_contents SET custom_images = ? WHERE id = ?");
            $stmt->execute([json_encode($new_images), $id]);
            echo json_encode(['ok' => true]);
            exit;
        }
        echo json_encode(['ok' => false, 'msg' => 'Image not found']);
        exit;
    }
    
    if ($action === 'load') {
        $contents_green = $db->query("SELECT * FROM creative_contents WHERE ai_strategy='Green' ORDER BY id DESC")->fetchAll();
        $contents_yellow = $db->query("SELECT * FROM creative_contents WHERE ai_strategy='Yellow' ORDER BY id DESC")->fetchAll();
        $contents_red = $db->query("SELECT * FROM creative_contents WHERE ai_strategy='Red' ORDER BY id DESC")->fetchAll();
        
        function render_contents($arr, $border_class) {
            ob_start();
            if(empty($arr)) {
                echo '<div class="text-muted p-4 text-center">ไม่มีเนื้อหาในหมวดหมู่นี้</div>';
                return ob_get_clean();
            }
            ?>
            <div class="table-responsive">
              <table class="table table-vcenter table-hover table-sm mb-0">
                <tbody>
                  <?php $i=1; foreach($arr as $c): ?>
                  <tr>
                    <td class="w-1 text-center text-muted small" style="vertical-align: top; padding-top: 12px;"><?= $i++ ?></td>
                    <td style="font-size: 0.9rem; vertical-align: top; padding: 12px 10px; line-height: 1.6;">
                      <div style="white-space: pre-wrap;"><?= htmlspecialchars($c['content_text']) ?></div>
                      <div class="mt-2 d-flex flex-wrap gap-2">
                        <?php if($c['target_zone'] == 'Thailand'): ?>
                            <span class="badge bg-info-lt">🇹🇭 ไทย</span>
                        <?php elseif($c['target_zone'] == 'Global'): ?>
                            <span class="badge bg-orange-lt">🌍 ต่างประเทศ</span>
                        <?php else: ?>
                            <span class="badge bg-secondary-lt">🌐 ทั้งหมด</span>
                        <?php endif; ?>
                      </div>
                      <?php if(!empty($c['campaign_tag'])): ?>
                      <div class="mt-2"><span class="badge bg-indigo text-indigo-fg"><svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7.5 7.5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M3 6v5.172a2 2 0 0 0 .586 1.414l7.71 7.71a2.41 2.41 0 0 0 3.408 0l5.592 -5.592a2.41 2.41 0 0 0 0 -3.408l-7.71 -7.71a2 2 0 0 0 -1.414 -.586h-5.172a3 3 0 0 0 -3 3z" /></svg> แคมเปญ: <?= htmlspecialchars($c['campaign_tag']) ?></span></div>
                      <?php endif; ?>
                      <?php
                      $c_images = [];
                      if (!empty($c['custom_images'])) {
                          $c_images = json_decode($c['custom_images'], true) ?: [];
                      }
                      ?>
                      <div class="mt-2 d-flex flex-wrap gap-2" id="custom-images-container-<?= $c['id'] ?>">
                          <?php foreach($c_images as $img): ?>
                              <div class="img-card shadow-sm" style="width: 80px; height: 80px;">
                                  <img src="<?= htmlspecialchars($img) ?>" style="width: 100%; height: 100%; object-fit: cover; cursor: pointer; transition: opacity 0.2s;" onmouseover="this.style.opacity=0.8;" onmouseout="this.style.opacity=1;" onclick="openLightbox(this.src)">
                                  <div class="img-actions" style="top: 2px; right: 2px; padding: 1px;">
                                      <button type="button" class="btn btn-sm btn-ghost-danger" style="padding: 0px 4px; font-size: 0.75rem;" onclick="deleteCustomImage(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($img)) ?>', this)">ลบ</button>
                                  </div>
                              </div>
                          <?php endforeach; ?>
                      </div>
                    </td>
                    <td class="text-end text-nowrap w-1" style="vertical-align: top; padding-top: 10px;">
                      <button type="button" class="btn btn-sm btn-icon btn-outline-success me-1" onclick="document.getElementById('upload_custom_<?= $c['id'] ?>').click()" title="อัปโหลดรูปเฉพาะโพสต์นี้">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 8h.01" /><path d="M12.5 21h-6.5a3 3 0 0 1 -3 -3v-12a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v6.5" /><path d="M3 16l5 -5c.928 -.893 2.072 -.893 3 0l3.5 3.5" /><path d="M14 14l1 -1c.679 -.653 1.473 -.829 2.185 -.526" /><path d="M19 22v-6" /><path d="M22 19l-3 -3l-3 3" /></svg>
                      </button>
                      <input type="file" id="upload_custom_<?= $c['id'] ?>" class="d-none" accept="image/*" multiple onchange="uploadCustomImage(<?= $c['id'] ?>, this)">
                      <button type="button" class="btn btn-sm btn-icon btn-outline-primary me-1" onclick="openEditModal(<?= $c['id'] ?>, <?= htmlspecialchars(json_encode($c['content_text']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($c['campaign_tag'] ?? ''), ENT_QUOTES, 'UTF-8') ?>, '<?= htmlspecialchars($c['target_zone'] ?? 'All') ?>')" title="แก้ไข">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" /><path d="M13.5 6.5l4 4" /></svg>
                      </button>
                      <button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="deleteContent(<?= $c['id'] ?>)" title="ลบ">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
                      </button>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php
            return ob_get_clean();
        }
        
        echo json_encode([
            'ok' => true,
            'html_green' => render_contents($contents_green, 'border-green'),
            'html_yellow' => render_contents($contents_yellow, 'border-yellow'),
            'html_red' => render_contents($contents_red, 'border-red'),
            'count_green' => count($contents_green),
            'count_yellow' => count($contents_yellow),
            'count_red' => count($contents_red),
        ]);
        exit;
    }
}

// ── Handle Delete Image (AJAX) ──
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'delete_gallery_image') {
    header('Content-Type: application/json');
    try {
        $id = $_POST['id'];
        $stmt = $db->prepare("SELECT file_path FROM creative_images WHERE id = ?");
        $stmt->execute([$id]);
        $img = $stmt->fetch();
        if ($img && !empty($img['file_path'])) {
            if (file_exists($img['file_path'])) unlink($img['file_path']);
            $thumb_path = str_replace('.jpg', '_thumb.jpg', $img['file_path']);
            if (file_exists($thumb_path)) unlink($thumb_path);
        }
        
        $stmt = $db->prepare("DELETE FROM creative_images WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['ok' => true]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        exit;
    }
}

// ── Handle Delete Image (Fallback) ──
if (isset($_GET['del_img'])) {
    try {
        $id = $_GET['del_img'];
        $stmt = $db->prepare("SELECT file_path FROM creative_images WHERE id = ?");
        $stmt->execute([$id]);
        $img = $stmt->fetch();
        if ($img && !empty($img['file_path'])) {
            if (file_exists($img['file_path'])) unlink($img['file_path']);
            $thumb_path = str_replace('.jpg', '_thumb.jpg', $img['file_path']);
            if (file_exists($thumb_path)) unlink($thumb_path);
        }
        
        $stmt = $db->prepare("DELETE FROM creative_images WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: creative_manage.php");
        exit;
    } catch (Exception $e) {}
}

// ── Load Data ──
$images = $db->query("SELECT * FROM creative_images ORDER BY id DESC")->fetchAll();
$prompts_db = $db->query("SELECT cfg_key, cfg_value FROM fb_configs WHERE cfg_key LIKE 'ai_prompt_%'")->fetchAll(PDO::FETCH_KEY_PAIR);

include "components/header.php";
?>

<!-- Cropper.js CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">

<style>
  .nav-tabs .nav-link.active { font-weight: 600; border-bottom: 2px solid var(--tblr-primary) !important; color: var(--tblr-primary) !important; }
  .img-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem; }
  .img-card { border-radius: 8px; overflow: hidden; position: relative; border: 1px solid #e2e8f0; }
  .img-card img { width: 100%; height: 150px; object-fit: cover; }
  .img-actions { position: absolute; top: 5px; right: 5px; background: rgba(255,255,255,0.9); border-radius: 4px; padding: 2px; }
  .content-box { border-left: 4px solid #ccc; padding: 10px 15px; background: #f8fafc; margin-bottom: 15px; border-radius: 0 8px 8px 0; }
  .border-green { border-left-color: #2fb344; }
  .border-yellow { border-left-color: #f59f00; }
  .border-red { border-left-color: #d63939; }
  
  #image-to-crop { max-width: 100%; display: block; }
</style>

<div class="page-header d-print-none">
  <div class="container-fluid">
    <div class="row g-2 align-items-center">
      <div class="col">
        <h2 class="page-title">
          <svg xmlns="http://www.w3.org/2000/svg" class="icon text-primary me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 4h16v16H4z"/><path d="M4 15l4 -4a3 5 0 0 1 3 0l5 5"/><path d="M14 14l1 -1a3 5 0 0 1 3 0l2 2"/><circle cx="9" cy="9" r="2"/></svg>
          คลังเนื้อหา & รูปภาพ (Content Pool)
        </h2>
        <div class="text-muted mt-1 small">จัดการทรัพยากรล่วงหน้าให้ AI นำไปหยิบใช้ในการโพสต์กลุ่มและเพจ</div>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-fluid">
    <div class="card">
      <div class="card-header p-0">
        <ul class="nav nav-tabs card-header-tabs m-0 px-3" data-bs-toggle="tabs" style="border-bottom: 1px solid #eee;">
          <li class="nav-item">
            <a href="#tab-images" class="nav-link active py-3" data-bs-toggle="tab">🖼️ คลังรูปภาพ (Gallery)</a>
          </li>
          <li class="nav-item">
            <a href="#tab-contents" class="nav-link py-3" data-bs-toggle="tab">📝 คลังเนื้อหาโพสต์ (Content Pool)</a>
          </li>
          <li class="nav-item">
            <a href="#tab-ai-settings" class="nav-link py-3" data-bs-toggle="tab">⚙️ ตั้งค่าการเรียนรู้ AI</a>
          </li>
        </ul>
      </div>
      
      <div class="tab-content p-4">
        
        <!-- Tab: Images -->
        <div class="tab-pane active show" id="tab-images">
          <div class="row mb-4">
            <div class="col-md-12">
              <div id="drop-zone" class="p-4 mb-3 bg-light rounded text-center" style="border: 2px dashed #ccc; cursor: pointer; transition: all 0.3s;" onclick="document.getElementById('upload-input').click()">
                <div class="mb-2">
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon text-muted" width="48" height="48" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 18a4.6 4.4 0 0 1 0 -9a5 4.5 0 0 1 11 2h1a3.5 3.5 0 0 1 0 7h-1" /><path d="M9 15l3 -3l3 3" /><path d="M12 12l0 9" /></svg>
                </div>
                <h4 class="mb-1 text-primary">ลากไฟล์มาวางที่นี่ หรือคลิกเพื่อเลือกไฟล์</h4>
                <div class="d-flex justify-content-center align-items-center gap-3 mt-3" onclick="event.stopPropagation();">
                  <label class="form-label mb-0 fw-bold">โซนสำหรับรูปภาพ:</label>
                  <select id="gallery_zone_select" class="form-select fw-bold" style="width:160px; border-color: var(--tblr-primary);">
                    <option value="All">🌐 ใช้ได้ทั้งหมด</option>
                    <option value="Thailand">🇹🇭 ไทย</option>
                    <option value="Global">🌍 ต่างประเทศ</option>
                  </select>
                  <button type="button" class="btn btn-primary fw-bold text-nowrap" onclick="document.getElementById('upload-input').click()">เลือกไฟล์รูปภาพ</button>
                </div>
                <input type="file" id="upload-input" class="d-none" accept="image/*" multiple>
              </div>
              <small class="text-muted mt-2 d-block">รูปภาพจะถูก Crop ตามสัดส่วนของ Facebook และแปลงเป็น .jpg อัตโนมัติ (เลือกได้หลายไฟล์ ระบบจะให้ Crop ทีละรูป)</small>
            </div>
          </div>
          
          <div class="img-grid">
            <?php foreach($images as $img): 
                $thumb_path = str_replace('.jpg', '_thumb.jpg', $img['file_path']);
                $display_src = file_exists($thumb_path) ? $thumb_path : $img['file_path'];
            ?>
              <div class="img-card shadow-sm">
                <?php if($img['target_zone'] == 'Thailand'): ?>
                  <span class="badge bg-info-lt position-absolute top-0 start-0 m-1" style="z-index: 10;">🇹🇭 ไทย</span>
                <?php elseif($img['target_zone'] == 'Global'): ?>
                  <span class="badge bg-orange-lt position-absolute top-0 start-0 m-1" style="z-index: 10;">🌍 นอก</span>
                <?php endif; ?>
                <img src="<?= htmlspecialchars($display_src) ?>" loading="lazy" alt="Gallery Image" style="cursor: pointer; transition: opacity 0.2s;" onmouseover="this.style.opacity=0.8;" onmouseout="this.style.opacity=1;" onclick="openLightbox('<?= htmlspecialchars($img['file_path']) ?>')" title="คลิกเพื่อขยาย">
                <div class="img-actions">
                  <button type="button" class="btn btn-sm btn-ghost-danger px-2" onclick="confirmDeleteGalleryImage(event, <?= $img['id'] ?>, this);">ลบ</button>
                </div>
              </div>
            <?php endforeach; ?>
            <?php if(empty($images)): ?>
              <div class="text-muted p-4 col-12 text-center border rounded">ยังไม่มีรูปภาพในระบบ</div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Tab: Contents -->
        <div class="tab-pane" id="tab-contents">
          <div class="row">
            <div class="col-md-4">
              <form id="addContentForm" class="card shadow-sm">
                <div class="card-body">
                  <h3 class="card-title fw-bold">สร้างคอนเทนต์ใหม่</h3>
                  <div class="mb-3">
                    <label class="form-label">หมวดหมู่ (AI Strategy)</label>
                    <select name="ai_strategy" id="ai_strategy_select" class="form-select fw-bold">
                      <option value="Green" class="text-success">🟢 Case Green (ขายตรง / โปรโมชัน)</option>
                      <option value="Yellow" class="text-warning">🟡 Case Yellow (ให้ความรู้ / ป้ายยา)</option>
                      <option value="Red" class="text-danger">🔴 Case Red (คีย์เวิร์ด / แนวทางตอบกลับ)</option>
                    </select>
                  </div>
                  <button type="button" class="btn btn-outline-indigo w-100 mb-3 fw-bold" id="btnAiGen">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z" /><path d="M17 4a2 2 0 0 0 2 2a2 2 0 0 0 -2 2a2 2 0 0 0 -2 -2a2 2 0 0 0 2 -2" /><path d="M19 11h2m-1 -1v2" /></svg>
                    ✨ ให้ AI (Gemini) ช่วยคิดเนื้อหา
                  </button>
                  <div class="mb-3">
                    <label class="form-label">โซน (Zone)</label>
                    <select name="target_zone" id="content_zone_select" class="form-select fw-bold">
                      <option value="All">🌐 ใช้ได้ทั้งหมด</option>
                      <option value="Thailand">🇹🇭 ไทย (Thailand)</option>
                      <option value="Global">🌍 ต่างประเทศ (Global)</option>
                    </select>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">ชื่อเนื้อหา (Content Name)</label>
                    <input type="text" name="campaign_tag" id="campaign_tag" class="form-control" placeholder="เช่น 'นิวซีแลนด์' (เว้นว่างได้)">
                  </div>
                  <div class="mb-3">
                    <label class="form-label">เนื้อหาโพสต์ / คอนเทนต์</label>
                    <textarea name="content_text" id="content_text" class="form-control" rows="8" placeholder="พิมพ์หรือวางเนื้อหาที่นี่..."></textarea>
                  </div>
                  <button type="submit" class="btn btn-primary w-100 fw-bold">บันทึกคอนเทนต์</button>
                </div>
              </form>
            </div>
            
            <div class="col-md-8">
              <div class="card shadow-sm">
                <div class="card-header p-0">
                  <ul class="nav nav-tabs card-header-tabs m-0 px-3" data-bs-toggle="tabs">
                    <li class="nav-item">
                      <a href="#content-green" class="nav-link active fw-bold text-success py-3" data-bs-toggle="tab">🟢 Green (<span id="c-green">0</span>)</a>
                    </li>
                    <li class="nav-item">
                      <a href="#content-yellow" class="nav-link fw-bold text-warning py-3" data-bs-toggle="tab">🟡 Yellow (<span id="c-yellow">0</span>)</a>
                    </li>
                    <li class="nav-item">
                      <a href="#content-red" class="nav-link fw-bold text-danger py-3" data-bs-toggle="tab">🔴 Red (<span id="c-red">0</span>)</a>
                    </li>
                  </ul>
                </div>
                <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                  <div class="tab-content">
                    <div class="tab-pane active show" id="content-green"></div>
                    <div class="tab-pane" id="content-yellow"></div>
                    <div class="tab-pane" id="content-red"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab: AI Settings -->
        <div class="tab-pane" id="tab-ai-settings">
          <form id="aiPromptForm" class="card shadow-sm mb-4">
            <div class="card-body">
              <h3 class="card-title fw-bold text-primary">ตั้งค่าบริบทและบทบาท (Context & Role)</h3>
              <p class="text-muted small">คำสั่งนี้จะถูกส่งไปพร้อมกับทุกคำขอ เพื่อให้ AI เข้าใจแบรนด์และบทบาทของตัวเอง</p>
              <div class="mb-4">
                <textarea name="ai_prompt_role" rows="4" class="form-control"><?= htmlspecialchars($prompts_db['ai_prompt_role'] ?? '') ?></textarea>
              </div>

              <hr>

              <h3 class="card-title fw-bold text-success mt-4">ตั้งค่า Prompt: Case 🟢 Green (ขายตรง / โปรโมชัน)</h3>
              <div class="mb-4">
                <textarea name="ai_prompt_green" rows="3" class="form-control"><?= htmlspecialchars($prompts_db['ai_prompt_green'] ?? '') ?></textarea>
              </div>

              <h3 class="card-title fw-bold text-warning">ตั้งค่า Prompt: Case 🟡 Yellow (ให้ความรู้ / ป้ายยา)</h3>
              <div class="mb-4">
                <textarea name="ai_prompt_yellow" rows="3" class="form-control"><?= htmlspecialchars($prompts_db['ai_prompt_yellow'] ?? '') ?></textarea>
              </div>

              <h3 class="card-title fw-bold text-danger">ตั้งค่า Prompt: Case 🔴 Red (คอมเมนต์ตามน้ำ / หน้าม้า)</h3>
              <div class="mb-4">
                <textarea name="ai_prompt_red" rows="3" class="form-control"><?= htmlspecialchars($prompts_db['ai_prompt_red'] ?? '') ?></textarea>
              </div>
              
              <button type="submit" name="save_ai_prompts" class="btn btn-primary w-100 fw-bold py-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2" /><path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M14 4l0 4l-6 0l0 -4" /></svg>
                บันทึกการตั้งค่า AI Prompt
              </button>
            </div>
          </form>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- Modal สำหรับ Edit Content -->
<div class="modal modal-blur fade" id="editContentModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form id="editContentForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">แก้ไขคอนเทนต์</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="edit_content_id">
        <div class="mb-3">
            <label class="form-label">โซน (Zone)</label>
            <select id="edit_target_zone" class="form-select fw-bold">
                <option value="All">🌐 ใช้ได้ทั้งหมด</option>
                <option value="Thailand">🇹🇭 ไทย (Thailand)</option>
                <option value="Global">🌍 ต่างประเทศ (Global)</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">ชื่อเนื้อหา (Content Name)</label>
            <input type="text" id="edit_campaign_tag" class="form-control" placeholder="เช่น 'นิวซีแลนด์' (เว้นว่างได้)">
        </div>
        <div class="mb-3">
            <label class="form-label">เนื้อหาโพสต์</label>
            <textarea id="edit_content_text" class="form-control" rows="8" required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="submit" class="btn btn-primary ms-auto">บันทึกการแก้ไข</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal สำหรับ Crop ภาพ -->
<div class="modal modal-blur fade" id="cropModal" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">ครอบตัดรูปภาพ (Facebook Standards)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0" style="max-height: 60vh; background: #333; display: flex; justify-content: center; align-items: center; overflow: hidden;">
        <img id="image-to-crop" src="" alt="Picture">
      </div>
      <div class="modal-footer d-flex justify-content-between">
        <div>
          <button class="btn btn-outline-secondary btn-sm" onclick="setRatio(1)">1:1 (Square)</button>
          <button class="btn btn-outline-secondary btn-sm" onclick="setRatio(4/5)">4:5 (Portrait)</button>
          <button class="btn btn-outline-secondary btn-sm" onclick="setRatio(1.91/1)">1.91:1 (Landscape)</button>
          <button class="btn btn-outline-secondary btn-sm" onclick="setRatio(NaN)">Free</button>
          <div class="btn-group ms-2">
              <button class="btn btn-outline-info btn-sm" onclick="rotateImage(-90)" title="หมุนซ้าย">↺ หมุนซ้าย</button>
              <button class="btn btn-outline-info btn-sm" onclick="rotateImage(90)" title="หมุนขวา">↻ หมุนขวา</button>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span id="queue-status" class="text-muted small me-2"></span>
            <button type="button" class="btn btn-primary" onclick="cropAndUpload()">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                บันทึกและถัดไป
            </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Lightbox Modal -->
<div class="modal modal-blur fade" id="lightboxModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content shadow-lg bg-transparent border-0">
      <div class="modal-body p-0 text-center position-relative">
        <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close" style="z-index: 1050; opacity: 1; text-shadow: 0 0 5px rgba(0,0,0,0.5);"></button>
        <img id="lightbox-img" src="" alt="Enlarged Image" class="img-fluid rounded" style="max-height: 90vh; object-fit: contain; box-shadow: 0 0 20px rgba(0,0,0,0.5);">
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<?php include "components/footer.php"; ?>

<script>
/* ── Contents AJAX ── */
function loadContents() {
    const fd = new FormData();
    fd.append('ajax_content_action', 'load');
    fetch('creative_manage.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if(res.ok) {
                document.getElementById('content-green').innerHTML = res.html_green;
                document.getElementById('content-yellow').innerHTML = res.html_yellow;
                document.getElementById('content-red').innerHTML = res.html_red;
                document.getElementById('c-green').innerText = res.count_green;
                document.getElementById('c-yellow').innerText = res.count_yellow;
                document.getElementById('c-red').innerText = res.count_red;
            }
        });
}

document.addEventListener('DOMContentLoaded', loadContents);

document.getElementById('addContentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>กำลังบันทึก...';

    const fd = new FormData(this);
    fd.append('ajax_content_action', 'add');
    
    fetch('creative_manage.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            if(res.ok) {
                document.getElementById('content_text').value = '';
                Swal.fire({ title: 'สำเร็จ', text: 'บันทึกเนื้อหาแล้ว', icon: 'success', timer: 1500, showConfirmButton: false });
                loadContents();
            } else {
                Swal.fire('เกิดข้อผิดพลาด', res.msg, 'error');
            }
        });
});

function openEditModal(id, text, tag, zone) {
    document.getElementById('edit_target_zone').value = zone || 'All';
    // Parse json string back to normal text
    let actualText = '';
    try { actualText = JSON.parse(text); } catch(e) { actualText = text; }
    
    let actualTag = '';
    try { actualTag = JSON.parse(tag); } catch(e) { actualTag = tag; }
    
    document.getElementById('edit_content_id').value = id;
    document.getElementById('edit_content_text').value = actualText;
    document.getElementById('edit_campaign_tag').value = actualTag || '';
    new bootstrap.Modal(document.getElementById('editContentModal')).show();
}

document.getElementById('editContentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'กำลังบันทึก...';

    const fd = new FormData();
    fd.append('ajax_content_action', 'edit');
    fd.append('id', document.getElementById('edit_content_id').value);
    fd.append('content_text', document.getElementById('edit_content_text').value);
    fd.append('campaign_tag', document.getElementById('edit_campaign_tag').value);
    fd.append('target_zone', document.getElementById('edit_target_zone').value);
    
    fetch('creative_manage.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            if(res.ok) {
                bootstrap.Modal.getInstance(document.getElementById('editContentModal')).hide();
                Swal.fire({ title: 'สำเร็จ', text: 'อัปเดตเนื้อหาแล้ว', icon: 'success', timer: 1500, showConfirmButton: false });
                loadContents();
            } else {
                Swal.fire('เกิดข้อผิดพลาด', res.msg, 'error');
            }
        });
});

window.deleteContent = function(id) {
    Swal.fire({
        title: 'ยืนยันการลบ',
        text: 'คุณต้องการลบเนื้อหานี้ใช่หรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ใช่, ลบเลย!'
    }).then((result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('ajax_content_action', 'delete');
            fd.append('id', id);
            fetch('creative_manage.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if(res.ok) loadContents();
                });
        }
    });
};

/* ── AI Gen ── */
document.getElementById('btnAiGen').addEventListener('click', async function() {
    const strat = document.getElementById('ai_strategy_select').value;
    const txtArea = document.getElementById('content_text');
    const originalText = this.innerHTML;
    
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> กำลังคิดเนื้อหา...';
    this.disabled = true;
    
    try {
        const response = await fetch('api/generate_content.php?api_key=yakpho2026&strategy=' + strat);
        const data = await response.json();
        if (data.ok) {
            if (txtArea.value.trim() !== '') txtArea.value += '\n\n' + data.draft;
            else txtArea.value = data.draft;
        } else alert('Error: ' + data.msg);
    } catch (e) { alert('Connection error'); } 
    finally {
        this.innerHTML = originalText;
        this.disabled = false;
    }
});

/* ── Cropper ── */
let cropper;
let filesQueue = [];
let currentFileIndex = 0;
let uploadMode = 'gallery';
let uploadContentId = null;
const imageToCrop = document.getElementById('image-to-crop');
let cropModalObj = null;

document.addEventListener('DOMContentLoaded', () => {
    cropModalObj = new bootstrap.Modal(document.getElementById('cropModal'));
});

const dropZone = document.getElementById('drop-zone');
if (dropZone) {
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.style.borderColor = 'var(--tblr-primary)';
        dropZone.style.backgroundColor = '#eef2f6';
    });
    dropZone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        dropZone.style.borderColor = '#ccc';
        dropZone.style.backgroundColor = '#f8f9fa';
    });
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.style.borderColor = '#ccc';
        dropZone.style.backgroundColor = '#f8f9fa';
        if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            handleUploadFiles(e.dataTransfer.files);
        }
    });
}

document.getElementById('upload-input').addEventListener('change', function(e) {
    if (e.target.files.length > 0) {
        handleUploadFiles(e.target.files);
    }
});

function handleUploadFiles(files) {
    filesQueue = Array.from(files);
    currentFileIndex = 0;
    uploadMode = 'gallery';
    uploadContentId = null;
    processNextImage();
}

function uploadCustomImage(id, input) {
    if (input.files && input.files.length > 0) {
        filesQueue = Array.from(input.files);
        currentFileIndex = 0;
        uploadMode = 'custom';
        uploadContentId = id;
        processNextImage();
    }
}

function processNextImage() {
    if (currentFileIndex >= filesQueue.length) {
        cropModalObj.hide();
        if (uploadMode === 'gallery') {
            window.location.reload();
        } else {
            const input = document.getElementById('upload_custom_' + uploadContentId);
            if (input) input.value = '';
        }
        return;
    }
    
    document.getElementById('queue-status').innerText = `ภาพที่ ${currentFileIndex + 1} จาก ${filesQueue.length}`;
    
    const file = filesQueue[currentFileIndex];
    const reader = new FileReader();
    reader.onload = function(event) {
        imageToCrop.src = event.target.result;
        cropModalObj.show();
        
        setTimeout(() => {
            if (cropper) cropper.destroy();
            cropper = new Cropper(imageToCrop, {
                aspectRatio: 1,
                viewMode: 2, dragMode: 'move', autoCropArea: 1,
                restore: false, guides: true, center: true, highlight: false,
                cropBoxMovable: true, cropBoxResizable: true, toggleDragModeOnDblclick: false,
            });
        }, 200);
    };
    reader.readAsDataURL(file);
}

function setRatio(ratio) {
    if (cropper) cropper.setAspectRatio(ratio);
}

function rotateImage(degree) {
    if (cropper) cropper.rotate(degree);
}

function cropAndUpload() {
    if (!cropper) return;
    const canvas = cropper.getCroppedCanvas({ maxWidth: 1200, maxHeight: 1200 });
    canvas.toBlob(function(blob) {
        const formData = new FormData();
        
        if (uploadMode === 'gallery') {
            formData.append('cropped_image', blob, 'image.jpg');
            const zoneSel = document.getElementById('gallery_zone_select');
            if(zoneSel) formData.append('target_zone', zoneSel.value);
        } else {
            formData.append('ajax_content_action', 'upload_custom_image');
            formData.append('id', uploadContentId);
            formData.append('custom_image', blob, 'image.jpg');
        }
        
        const btn = document.querySelector('#cropModal .btn-primary');
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> กำลังอัปโหลด...';
        btn.disabled = true;

        fetch('creative_manage.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            btn.innerHTML = 'บันทึกและถัดไป';
            btn.disabled = false;
            
            if (uploadMode === 'custom') {
                if(data.ok) {
                    const container = document.getElementById('custom-images-container-' + uploadContentId);
                    if (container && data.img_path) {
                        const div = document.createElement('div');
                        div.className = 'img-card shadow-sm';
                        div.style.width = '80px';
                        div.style.height = '80px';
                        const safePath = data.img_path.replace(/'/g, "\\'");
                        div.innerHTML = `
                            <img src="${data.img_path}" style="width: 100%; height: 100%; object-fit: cover; cursor: pointer; transition: opacity 0.2s;" onmouseover="this.style.opacity=0.8;" onmouseout="this.style.opacity=1;" onclick="openLightbox(this.src)">
                            <div class="img-actions" style="top: 2px; right: 2px; padding: 1px;">
                                <button type="button" class="btn btn-sm btn-ghost-danger" style="padding: 0px 4px; font-size: 0.75rem;" onclick="deleteCustomImage(${uploadContentId}, '${safePath}', this)">ลบ</button>
                            </div>
                        `;
                        container.appendChild(div);
                    }
                    currentFileIndex++;
                    processNextImage();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.msg);
                }
            } else {
                if (data.status === 'success') {
                    currentFileIndex++;
                    processNextImage();
                } else alert('เกิดข้อผิดพลาดในการอัปโหลดภาพ');
            }
        })
        .catch(err => {
            btn.innerHTML = 'บันทึกและถัดไป';
            btn.disabled = false;
            alert('Upload Error!');
        });
    }, 'image/jpeg', 0.90);
}

document.getElementById('cropModal').addEventListener('hidden.bs.modal', function () {
    if (cropper) { cropper.destroy(); cropper = null; }
    if (currentFileIndex > 0 && currentFileIndex < filesQueue.length) {
        if (uploadMode === 'gallery') window.location.reload();
        else {
            const input = document.getElementById('upload_custom_' + uploadContentId);
            if (input) input.value = '';
        }
    }
});

/* ── Lightbox & Image Delete ── */
function openLightbox(src) {
    document.getElementById('lightbox-img').src = src;
    new bootstrap.Modal(document.getElementById('lightboxModal')).show();
}

function confirmDeleteGalleryImage(e, id, btnElement) {
    e.preventDefault();
    Swal.fire({
        title: 'ยืนยันการลบ?', text: 'ลบรูปภาพนี้?', icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#d63939',
        cancelButtonColor: '#6c7a91', confirmButtonText: 'ใช่, ลบเลย!', cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('ajax_action', 'delete_gallery_image');
            fd.append('id', id);
            fetch('creative_manage.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.ok) {
                        if (btnElement) {
                            btnElement.closest('.img-card').remove();
                        }
                    } else {
                        Swal.fire('เกิดข้อผิดพลาด', res.msg || 'ไม่สามารถลบได้', 'error');
                    }
                }).catch(() => {
                    Swal.fire('เกิดข้อผิดพลาด', 'เชื่อมต่อเซิร์ฟเวอร์ไม่ได้', 'error');
                });
        }
    });
}

/* ── Save AI Prompts ── */
document.getElementById('aiPromptForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> กำลังบันทึก...';
    btn.disabled = true;
    
    const formData = new FormData(this);
    formData.append('save_ai_prompts', '1');
    formData.append('ajax', '1');
    
    try {
        const response = await fetch('creative_manage.php', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.status === 'success') {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'บันทึกสำเร็จแล้ว', showConfirmButton: false, timer: 2000 });
        }
    } catch (error) { alert('เกิดข้อผิดพลาดในการเชื่อมต่อ'); } 
    finally { btn.innerHTML = originalText; btn.disabled = false; }
});
function deleteCustomImage(id, imgPath, btnElement) {
    Swal.fire({
        title: 'ยืนยันการลบรูปภาพ?',
        text: 'รูประบุนี้จะถูกลบออกจากโพสต์นี้',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ลบรูปภาพ',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('ajax_content_action', 'delete_custom_image');
            fd.append('id', id);
            fd.append('img_path', imgPath);
            fetch('creative_manage.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if(res.ok) {
                        if (btnElement) {
                            btnElement.closest('.img-card').remove();
                        } else {
                            // Fallback if btnElement is not passed
                            const container = document.getElementById('custom-images-container-' + id);
                            if (container) {
                                const imgs = container.querySelectorAll('img');
                                imgs.forEach(img => {
                                    if (img.src.includes(imgPath)) {
                                        img.closest('.img-card').remove();
                                    }
                                });
                            }
                        }
                    } else {
                        Swal.fire('เกิดข้อผิดพลาด', res.msg, 'error');
                    }
                });
        }
    });
}

// (uploadCustomImage is now handled above through the Cropper queue)
</script>
