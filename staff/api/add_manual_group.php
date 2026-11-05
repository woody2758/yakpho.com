<?php
require_once "../db.php";
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Method Not Allowed']);
    exit;
}

$group_name = trim($_POST['group_name'] ?? '');
$group_url = trim($_POST['group_url'] ?? '');
$member_count = (int)($_POST['member_count'] ?? 0);
$privacy_type = trim($_POST['privacy_type'] ?? 'Public');
$join_status = trim($_POST['join_status'] ?? 'Not Joined');
$category_tag = trim($_POST['category_tag'] ?? 'Manual');
$target_zone = trim($_POST['target_zone'] ?? 'Uncategorized');

if (empty($group_name) || empty($group_url)) {
    echo json_encode(['ok' => false, 'msg' => 'กรุณากรอกชื่อกลุ่มและ URL ให้ครบถ้วน']);
    exit;
}

try {
    $stmt = $db->prepare("
        INSERT INTO facebook_groups 
        (group_name, group_url, member_count, privacy_type, join_status, category_tag, target_zone, suitability_score, post_quality, strategy_marker, post_ready) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $group_name, 
        $group_url, 
        $member_count, 
        $privacy_type, 
        $join_status, 
        $category_tag, 
        $target_zone,
        5, // default suitability score
        'Good', // default post quality
        'Observe', // default strategy marker
        0 // default post_ready
    ]);
    
    $new_id = $db->lastInsertId();
    
    // Fetch the inserted record to generate HTML
    $stmt = $db->prepare("
        SELECT g.*, 0 as total_posts 
        FROM facebook_groups g
        WHERE id = ?
    ");
    $stmt->execute([$new_id]);
    $g = $stmt->fetch();
    
    // Generate HTML for the new row
    ob_start();
    ?>
    <tr>
      <td><input type="checkbox" class="form-check-input m-0 row-checkbox" value="<?= $g['id'] ?>"></td>
      <td class="text-center">
        <?php $is_joined = ($g['join_status'] === 'Joined'); ?>
        <a href="javascript:void(0)" class="text-decoration-none toggle-ready" data-id="<?= $g['id'] ?>" data-ready="0" style="<?= $is_joined ? '' : 'pointer-events: none; opacity: 0.3; cursor: not-allowed;' ?>" title="<?= $is_joined ? 'พร้อมโพสต์' : 'ยังไม่ได้เข้าร่วมกลุ่ม' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon text-muted" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9" /></svg>
        </a>
      </td>
      <td><span class="text-muted small">#<?= $g['id'] ?></span></td>
      <td>
        <div class="d-flex align-items-center gap-1">
          <span class="badge bg-warning-lt p-1 d-flex align-items-center">
            <input type="number" class="inline-edit-field bg-transparent border-0 text-center fw-bold" style="width: 30px; outline: none; color: inherit; appearance: none; -moz-appearance: textfield;" value="5" data-id="<?= $g['id'] ?>" data-field="suitability_score" min="0" max="10">
            /10
          </span>
        </div>
      </td>
      <td class="text-center">
        <select class="form-select form-select-sm badge bg-danger fw-bold strategy-select" data-id="<?=$g['id']?>" style="width:125px; cursor:pointer;">
          <option value="Observe" selected>🔴 Observer</option>
          <option value="Soft">🟡 Case Yellow</option>
          <option value="Hard">🟢 Case Green</option>
          <option value="Blacklist">⚪ Blacklist</option>
        </select>
      </td>
      <td>
        <div class="fw-bold text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($g['group_name']) ?>"><?= htmlspecialchars($g['group_name']) ?></div>
        <div class="small text-muted mt-1 d-flex align-items-center">
          <a href="<?= htmlspecialchars($g['group_url']) ?>" target="_blank" class="text-decoration-none me-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 6l-8 4l8 -4l8 -4l-8 -4" /><path d="M4 14l8 4l8 -4" /></svg>
          </a>
          <span class="text-muted" style="font-size: 0.65rem;" title="เวลาที่ดึงข้อมูล">
            <?= date('d M y') ?>
          </span>
        </div>
      </td>
      <td class="text-center text-nowrap">
          <span class="text-muted" style="font-size: 10px;">-</span>
      </td>
      <td id="tags-cell-<?= $g['id'] ?>">
        <div class="d-flex flex-wrap align-items-center gap-1">
          <span class="badge bg-secondary-lt text-muted" style="cursor: pointer; padding: 2px 4px; font-size: 10px; font-weight: normal;" onclick="openTagModal(<?= $g['id'] ?>, '')" title="เพิ่ม/แก้ไขแท็ก">+</span>
        </div>
      </td>
      <td class="text-center">
        <div class="fw-medium">
          <input type="text" class="inline-edit-field form-control form-control-sm text-center border-0 bg-transparent p-0 fw-medium" value="<?= number_format((float)$g['member_count']) ?>" data-id="<?= $g['id'] ?>" data-field="member_count" style="width: 60px; margin: 0 auto; outline: none; font-size: 0.8rem;" placeholder="0">
        </div>
      </td>
      <td class="text-center">
        <select class="form-select form-select-sm badge bg-<?= $g['privacy_type'] == 'Public' ? 'azure' : 'purple' ?>-lt fw-bold border-0 inline-edit-field" data-id="<?=$g['id']?>" data-field="privacy_type" style="width:90px; text-align:center; cursor:pointer;" onclick="event.stopPropagation();">
          <option value="Public" <?= $g['privacy_type']=='Public'?'selected':'' ?> class="text-dark bg-white">Public</option>
          <option value="Private" <?= $g['privacy_type']=='Private'?'selected':'' ?> class="text-dark bg-white">Private</option>
          <option value="Hidden" <?= $g['privacy_type']=='Hidden'?'selected':'' ?> class="text-dark bg-white">Hidden</option>
        </select>
      </td>
      <td>
          <div class="d-flex flex-wrap gap-1">
              <span class="badge badge-outline text-secondary" style="font-size: .6rem;"><?= htmlspecialchars($g['category_tag']) ?></span>
          </div>
      </td>
      <td class="text-center">
        <?php
        $z_color = 'secondary';
        if($g['target_zone'] === 'Thailand') $z_color = 'info';
        if($g['target_zone'] === 'Global') $z_color = 'orange';
        ?>
        <div class="dropdown">
          <div class="badge bg-<?=$z_color?>-lt fw-bold border-0 text-center dropdown-toggle" data-bs-toggle="dropdown" style="width:45px; cursor:pointer; padding: 4px;" id="zone-btn-<?=$g['id']?>">
            <?php if($g['target_zone']=='Thailand'): ?>
              <img src="https://flagcdn.com/16x12/th.png" alt="TH" style="vertical-align: middle; border-radius: 2px;">
            <?php elseif($g['target_zone']=='Global'): ?>
              <img src="https://flagcdn.com/16x12/un.png" alt="Global" style="vertical-align: middle; border-radius: 2px;">
            <?php else: ?>
              ❓
            <?php endif; ?>
          </div>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width: 120px; font-size: 0.8rem;">
            <li><a class="dropdown-item py-1 zone-dropdown" href="javascript:void(0)" data-id="<?=$g['id']?>" data-val="Uncategorized">❓ Uncategorized</a></li>
            <li><a class="dropdown-item py-1 zone-dropdown" href="javascript:void(0)" data-id="<?=$g['id']?>" data-val="Thailand"><img src="https://flagcdn.com/16x12/th.png" alt="TH" class="me-2" style="width:16px;">🇹🇭 ในประเทศ</a></li>
            <li><a class="dropdown-item py-1 zone-dropdown" href="javascript:void(0)" data-id="<?=$g['id']?>" data-val="Global"><img src="https://flagcdn.com/16x12/un.png" alt="Global" class="me-2" style="width:16px;">🌍 ต่างประเทศ</a></li>
          </ul>
        </div>
      </td>
      <td class="text-center">
        <select class="form-select form-select-sm badge bg-primary-lt fw-bold border-0 quality-select" data-id="<?=$g['id']?>" data-current="Good" style="width:105px; text-align:center; cursor:pointer;" onclick="event.stopPropagation();">
          <option value="Selected" class="text-dark bg-white">✅ Selected</option>
          <option value="Best" class="text-dark bg-white">⭐ สุดยอด</option>
          <option value="Good" selected class="text-dark bg-white">👍 Good</option>
          <option value="Poor" class="text-dark bg-white">👎 Poor</option>
          <option value="Delete" class="text-dark bg-white text-danger">🗑 Delete</option>
        </select>
      </td>
      <td class="text-center">
        <?php
          $st_color = 'secondary';
          if($g['join_status'] == 'Joined') $st_color = 'success';
          if($g['join_status'] == 'Pending') $st_color = 'warning';
          if($g['join_status'] == 'Blocked') $st_color = 'danger';
          if($g['join_status'] == 'Queued') $st_color = 'warning';
        ?>
        <span class="badge status-badge bg-<?= $st_color ?>-lt"><?= $g['join_status'] ?></span>
      </td>
      <td class="text-end">
        <div class="btn-group">
          <button class="btn btn-icon btn-ghost-primary me-1 px-1 py-1" style="font-size:0.7rem;" title="ร่างโพสต์แนะนำ" onclick="draftIntro(<?= $g['id'] ?>)">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-pencil-plus m-0" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" /><path d="M13.5 6.5l4 4" /><path d="M16 19h6" /><path d="M19 16v6" /></svg>
          </button>
          <?php if($g['join_status'] == 'Not Joined'): ?>
            <button class="btn btn-icon px-1 py-1 me-1 q-join text-indigo border" style="font-size:0.7rem;" data-id="<?= $g['id'] ?>" title="Queue">
              <svg xmlns="http://www.w3.org/2000/svg" class="icon m-0" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12v-9" /><path d="M12 12l-4 -4" /><path d="M12 12l4 -4" /><path d="M4 16v4h16v-4" /></svg>
            </button>
            <button class="btn btn-icon btn-ghost-primary px-1 py-1 update-status" style="font-size:0.7rem;" data-id="<?= $g['id'] ?>" data-status="Pending" title="Join">
              <svg xmlns="http://www.w3.org/2000/svg" class="icon m-0" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
            </button>
          <?php endif; ?>
          <button class="btn btn-icon btn-ghost-success px-1 py-1 update-status" style="font-size:0.7rem;" data-id="<?= $g['id'] ?>" data-status="Joined" title="Mark Joined">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon m-0" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
          </button>
          <button class="btn btn-icon btn-ghost-danger px-1 py-1 delete-group" style="font-size:0.7rem;" data-id="<?= $g['id'] ?>" title="Delete">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="7" x2="20" y2="7" /><line x1="10" y1="11" x2="10" y2="17" /><line x1="14" y1="11" x2="14" y2="17" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
          </button>
        </div>
      </td>
    </tr>
    <?php
    $html = ob_get_clean();
    
    echo json_encode(['ok' => true, 'html' => $html]);

} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => 'Database Error: ' . $e->getMessage()]);
}
?>
