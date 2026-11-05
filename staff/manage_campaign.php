<?php
require_once "db.php";
require_login();

$campaign_id = (int)($_GET['id'] ?? 0);
if (!$campaign_id) {
    die("Invalid Campaign ID");
}

// ── Handle AJAX Requests ──
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $action = $_POST['ajax_action'];
    
    // Add Variation
    if ($action === 'add_variation') {
        $msg = $_POST['message'] ?? '';
        $url = $_POST['target_url'] ?? '';
        
        try {
            $db->beginTransaction();
            $short_code = 'c' . $campaign_id . '_' . substr(md5(uniqid(rand(), true)), 0, 6);
            
            $upload_dir = 'uploads/campaigns/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $media_urls = [];
            $media_type = 'none';
            $file_input_name = "media_files";

            if (isset($_FILES[$file_input_name]) && !empty($_FILES[$file_input_name]['name'][0])) {
                $total_files = count($_FILES[$file_input_name]['name']);
                for ($i = 0; $i < $total_files; $i++) {
                    $tmp_name = $_FILES[$file_input_name]['tmp_name'][$i];
                    $original_name = $_FILES[$file_input_name]['name'][$i];
                    $file_type = $_FILES[$file_input_name]['type'][$i];
                    
                    if ($tmp_name) {
                        $ext = pathinfo($original_name, PATHINFO_EXTENSION);
                        $new_name = uniqid('cmp_') . '.' . $ext;
                        $dest = $upload_dir . $new_name;
                        
                        if (move_uploaded_file($tmp_name, $dest)) {
                            $media_urls[] = $dest;
                            if (strpos($file_type, 'video') !== false) {
                                $media_type = 'video';
                            } else {
                                $media_type = 'image';
                            }
                        }
                    }
                }
            }

            $stmt = $db->prepare("INSERT INTO fb_campaign_contents (campaign_id, message, media_type, media_urls, target_url, short_code) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$campaign_id, $msg, $media_type, json_encode($media_urls), $url, $short_code]);
            
            $db->commit();
            echo json_encode(['ok' => true, 'msg' => 'เพิ่มเนื้อหาเรียบร้อยแล้ว']);
        } catch(Exception $e) {
            $db->rollBack();
            echo json_encode(['ok' => false, 'msg' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // Delete Variation
    if ($action === 'delete_variation') {
        $content_id = $_POST['content_id'] ?? 0;
        try {
            $stmt = $db->prepare("DELETE FROM fb_campaign_contents WHERE id = ? AND campaign_id = ?");
            $stmt->execute([$content_id, $campaign_id]);
            echo json_encode(['ok' => true]);
        } catch(Exception $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }
    
    // Edit Variation
    if ($action === 'edit_variation') {
        $content_id = $_POST['content_id'] ?? 0;
        $msg = $_POST['message'] ?? '';
        $url = $_POST['target_url'] ?? '';
        $kept_media = json_decode($_POST['kept_media'] ?? '[]', true);
        
        try {
            $db->beginTransaction();
            
            $stmtCurrent = $db->prepare("SELECT media_urls, media_type FROM fb_campaign_contents WHERE id = ? AND campaign_id = ?");
            $stmtCurrent->execute([$content_id, $campaign_id]);
            $currentData = $stmtCurrent->fetch();
            if (!$currentData) throw new Exception("ไม่พบข้อมูลเนื้อหานี้");
            
            $media_type = $currentData['media_type'];
            if (empty($kept_media) && empty($_FILES['media_files']['name'][0])) {
                $media_type = 'none';
            }
            
            $upload_dir = 'uploads/campaigns/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $media_urls = $kept_media;
            $file_input_name = "media_files";

            if (isset($_FILES[$file_input_name]) && !empty($_FILES[$file_input_name]['name'][0])) {
                $total_files = count($_FILES[$file_input_name]['name']);
                for ($i = 0; $i < $total_files; $i++) {
                    $tmp_name = $_FILES[$file_input_name]['tmp_name'][$i];
                    $original_name = $_FILES[$file_input_name]['name'][$i];
                    $file_type = $_FILES[$file_input_name]['type'][$i];
                    
                    if ($tmp_name) {
                        $ext = pathinfo($original_name, PATHINFO_EXTENSION);
                        $new_name = uniqid('cmp_') . '.' . $ext;
                        $dest = $upload_dir . $new_name;
                        
                        if (move_uploaded_file($tmp_name, $dest)) {
                            $media_urls[] = $dest;
                            if (strpos($file_type, 'video') !== false) {
                                $media_type = 'video';
                            } else if ($media_type !== 'video') {
                                $media_type = 'image';
                            }
                        }
                    }
                }
            }

            $stmt = $db->prepare("UPDATE fb_campaign_contents SET message = ?, media_type = ?, media_urls = ?, target_url = ? WHERE id = ? AND campaign_id = ?");
            $stmt->execute([$msg, $media_type, json_encode($media_urls), $url, $content_id, $campaign_id]);
            
            $db->commit();
            echo json_encode(['ok' => true, 'msg' => 'บันทึกการแก้ไขเรียบร้อยแล้ว']);
        } catch(Exception $e) {
            $db->rollBack();
            echo json_encode(['ok' => false, 'msg' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    // Update Media Order
    if ($action === 'update_media_order') {
        $content_id = $_POST['content_id'] ?? 0;
        $media_urls = json_decode($_POST['media_urls'] ?? '[]', true);
        try {
            $stmt = $db->prepare("UPDATE fb_campaign_contents SET media_urls = ? WHERE id = ? AND campaign_id = ?");
            $stmt->execute([json_encode($media_urls), $content_id, $campaign_id]);
            echo json_encode(['ok' => true]);
        } catch(Exception $e) {
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }

    // Remove Target Group
    if ($action === 'remove_target') {
        $target_id = $_POST['target_id'] ?? 0;
        try {
            $db->beginTransaction();
            
            // 1. Get the fb_group_id for this target
            $stmt_group = $db->prepare("SELECT fb_group_id FROM fb_campaign_targets WHERE id = ? AND campaign_id = ?");
            $stmt_group->execute([$target_id, $campaign_id]);
            $group_id = $stmt_group->fetchColumn();
            
            // 2. Delete any 'pending' schedule for this group so the bot won't post to it
            if ($group_id) {
                $stmt_sch = $db->prepare("DELETE FROM fb_campaign_schedules WHERE group_id = ? AND campaign_id = ? AND status = 'pending'");
                $stmt_sch->execute([$group_id, $campaign_id]);
            }
            
            // 3. Delete from campaign targets
            $stmt = $db->prepare("DELETE FROM fb_campaign_targets WHERE id = ? AND campaign_id = ?");
            $stmt->execute([$target_id, $campaign_id]);
            
            $db->commit();
            echo json_encode(['ok' => true]);
        } catch(Exception $e) {
            if($db->inTransaction()) $db->rollBack();
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }
    
    // Launch Campaign (Create Schedules)
    if ($action === 'launch_campaign') {
        $launch_type = $_POST['launch_type'] ?? 'now';
        $schedule_time = $_POST['schedule_time'] ?? '';
        
        if ($launch_type === 'now') {
            $schedule_time = date('Y-m-d H:i:s');
        } else {
            if(empty($schedule_time)) {
                echo json_encode(['ok' => false, 'msg' => 'กรุณาระบุเวลาที่ต้องการตั้งเวลาโพสต์']);
                exit;
            }
            $schedule_time = str_replace('T', ' ', $schedule_time);
        }
        
        try {
            $db->beginTransaction();
            
            // 1. Get all contents
            $contents = $db->prepare("SELECT id FROM fb_campaign_contents WHERE campaign_id = ?");
            $contents->execute([$campaign_id]);
            $content_ids = $contents->fetchAll(PDO::FETCH_COLUMN);
            
            if(empty($content_ids)) {
                throw new Exception("ไม่สามารถเริ่มแคมเปญได้: ยังไม่มีเนื้อหา (A/B Test)");
            }
            
            // 2. Get all targets (excluding those already successfully posted)
            $targets = $db->prepare("
                SELECT fb_group_id 
                FROM fb_campaign_targets 
                WHERE campaign_id = ? 
                AND fb_group_id NOT IN (
                    SELECT group_id FROM fb_campaign_schedules 
                    WHERE campaign_id = ? AND status = 'success'
                )
            ");
            $targets->execute([$campaign_id, $campaign_id]);
            $group_ids = $targets->fetchAll(PDO::FETCH_COLUMN);
            
            if(empty($group_ids)) {
                throw new Exception("ไม่สามารถเริ่มแคมเปญได้: ยังไม่ได้เลือกกลุ่มเป้าหมาย");
            }
            
            // 3. Delete existing PENDING schedules for this campaign just in case
            $db->prepare("DELETE FROM fb_campaign_schedules WHERE campaign_id = ? AND status = 'pending'")->execute([$campaign_id]);
            
            // 4. Generate schedules (Round Robin) with +7 mins staggered time
            $stmt_schedule = $db->prepare("INSERT INTO fb_campaign_schedules (campaign_id, group_id, content_id, scheduled_time, status) VALUES (?, ?, ?, ?, 'pending')");
            
            $content_count = count($content_ids);
            $base_time = strtotime($schedule_time);
            
            foreach($group_ids as $index => $gid) {
                $selected_content_id = $content_ids[$index % $content_count];
                
                // Add approx 7 minutes (420 seconds) stagger for each subsequent post
                $staggered_time = date('Y-m-d H:i:s', $base_time + ($index * 420));
                
                $stmt_schedule->execute([$campaign_id, $gid, $selected_content_id, $staggered_time]);
            }
            
            // 5. Auto-pause other active campaigns and set this one to active
            $db->prepare("UPDATE fb_campaigns SET status = 'paused' WHERE status = 'active' AND id != ?")->execute([$campaign_id]);
            $db->prepare("UPDATE fb_campaigns SET status = 'active' WHERE id = ?")->execute([$campaign_id]);
            
            $db->commit();
            echo json_encode(['ok' => true, 'msg' => 'สร้างคิวโพสต์สำเร็จ!']);
        } catch(Exception $e) {
            $db->rollBack();
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }

    // Repost Campaign (Queue all targets that are not already pending)
    if ($action === 'repost_campaign') {
        try {
            $db->beginTransaction();
            
            // Re-generate schedules
            $contents = $db->prepare("SELECT id FROM fb_campaign_contents WHERE campaign_id = ?");
            $contents->execute([$campaign_id]);
            $content_ids = $contents->fetchAll(PDO::FETCH_COLUMN);
            
            if(empty($content_ids)) {
                throw new Exception("ไม่สามารถเริ่มแคมเปญได้: ยังไม่มีเนื้อหา");
            }
            
            // Get targets that DO NOT currently have a 'pending' schedule for this campaign
            $targets = $db->prepare("
                SELECT fb_group_id 
                FROM fb_campaign_targets 
                WHERE campaign_id = ? 
                AND fb_group_id NOT IN (
                    SELECT group_id FROM fb_campaign_schedules 
                    WHERE campaign_id = ? AND status = 'pending'
                )
            ");
            $targets->execute([$campaign_id, $campaign_id]);
            $group_ids = $targets->fetchAll(PDO::FETCH_COLUMN);
            
            if(!empty($group_ids)) {
                $stmt_schedule = $db->prepare("INSERT INTO fb_campaign_schedules (campaign_id, group_id, content_id, scheduled_time, status) VALUES (?, ?, ?, ?, 'pending')");
                $content_count = count($content_ids);
                $base_time = time(); // NOW
                
                foreach($group_ids as $index => $gid) {
                    $selected_content_id = $content_ids[$index % $content_count];
                    
                    // Add approx 7 minutes (420 seconds) stagger for each subsequent post
                    $staggered_time = date('Y-m-d H:i:s', $base_time + ($index * 420));
                    
                    $stmt_schedule->execute([$campaign_id, $gid, $selected_content_id, $staggered_time]);
                }
            }
            
            // Auto-pause other active campaigns and set this one to active
            $db->prepare("UPDATE fb_campaigns SET status = 'paused' WHERE status = 'active' AND id != ?")->execute([$campaign_id]);
            $db->prepare("UPDATE fb_campaigns SET status = 'active' WHERE id = ?")->execute([$campaign_id]);
            
            $db->commit();
            echo json_encode(['ok' => true, 'msg' => 'สั่งโพสต์ซ้ำเรียบร้อยแล้ว กลุ่มที่ยังไม่รอคิวจะถูกสร้างคิวใหม่ทั้งหมด']);
        } catch(Exception $e) {
            $db->rollBack();
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }

    // Retry Failed Campaign (Queue only targets that failed)
    if ($action === 'retry_failed') {
        try {
            $db->beginTransaction();
            
            $contents = $db->prepare("SELECT id FROM fb_campaign_contents WHERE campaign_id = ?");
            $contents->execute([$campaign_id]);
            $content_ids = $contents->fetchAll(PDO::FETCH_COLUMN);
            
            if(empty($content_ids)) {
                throw new Exception("ไม่สามารถเริ่มแคมเปญได้: ยังไม่มีเนื้อหา");
            }
            
            // Get targets that have 'failed' as their latest status and are not currently pending
            $targets = $db->prepare("
                SELECT fb_group_id 
                FROM fb_campaign_targets t
                WHERE campaign_id = ? 
                AND (SELECT status FROM fb_campaign_schedules s WHERE s.campaign_id = t.campaign_id AND s.group_id = t.fb_group_id ORDER BY id DESC LIMIT 1) = 'failed'
                AND fb_group_id NOT IN (
                    SELECT group_id FROM fb_campaign_schedules 
                    WHERE campaign_id = ? AND status = 'pending'
                )
            ");
            $targets->execute([$campaign_id, $campaign_id]);
            $group_ids = $targets->fetchAll(PDO::FETCH_COLUMN);
            
            if(!empty($group_ids)) {
                $stmt_schedule = $db->prepare("INSERT INTO fb_campaign_schedules (campaign_id, group_id, content_id, scheduled_time, status) VALUES (?, ?, ?, ?, 'pending')");
                $content_count = count($content_ids);
                $base_time = time(); // NOW
                
                foreach($group_ids as $index => $gid) {
                    $selected_content_id = $content_ids[$index % $content_count];
                    $staggered_time = date('Y-m-d H:i:s', $base_time + ($index * 420));
                    $stmt_schedule->execute([$campaign_id, $gid, $selected_content_id, $staggered_time]);
                }
            } else {
                throw new Exception("ไม่พบกลุ่มเป้าหมายที่ล้มเหลว หรือกลุ่มเหล่านั้นกำลังรอคิวอยู่แล้ว");
            }
            
            $db->prepare("UPDATE fb_campaigns SET status = 'paused' WHERE status = 'active' AND id != ?")->execute([$campaign_id]);
            $db->prepare("UPDATE fb_campaigns SET status = 'active' WHERE id = ?")->execute([$campaign_id]);
            
            $db->commit();
            echo json_encode(['ok' => true, 'msg' => 'สั่งรีโพสต์เฉพาะกลุ่มที่ล้มเหลวเรียบร้อยแล้ว']);
        } catch(Exception $e) {
            $db->rollBack();
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }

    // Retry Single Failed Campaign Target
    if ($action === 'retry_single_failed') {
        try {
            $group_id = $_POST['group_id'] ?? 0;
            if (empty($group_id)) throw new Exception("ไม่พบรหัสกลุ่ม");
            
            $db->beginTransaction();
            
            $contents = $db->prepare("SELECT id FROM fb_campaign_contents WHERE campaign_id = ? ORDER BY RAND() LIMIT 1");
            $contents->execute([$campaign_id]);
            $content_id = $contents->fetchColumn();
            
            if(empty($content_id)) {
                throw new Exception("ไม่พบเนื้อหาในแคมเปญนี้");
            }
            
            // Check if it's already pending
            $check = $db->prepare("SELECT id FROM fb_campaign_schedules WHERE campaign_id = ? AND group_id = ? AND status = 'pending'");
            $check->execute([$campaign_id, $group_id]);
            if ($check->fetch()) {
                throw new Exception("กลุ่มนี้กำลังรอคิวอยู่แล้ว");
            }
            
            $stmt_schedule = $db->prepare("INSERT INTO fb_campaign_schedules (campaign_id, group_id, content_id, scheduled_time, status) VALUES (?, ?, ?, NOW(), 'pending')");
            $stmt_schedule->execute([$campaign_id, $group_id, $content_id]);
            
            $db->prepare("UPDATE fb_campaigns SET status = 'paused' WHERE status = 'active' AND id != ?")->execute([$campaign_id]);
            $db->prepare("UPDATE fb_campaigns SET status = 'active' WHERE id = ?")->execute([$campaign_id]);
            
            $db->commit();
            echo json_encode(['ok' => true, 'msg' => 'สั่งรีโพสต์กลุ่มนี้เรียบร้อยแล้ว']);
        } catch(Exception $e) {
            $db->rollBack();
            echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
        }
        exit;
    }
}

// ── Load Campaign Data ──
$stmt = $db->prepare("SELECT * FROM fb_campaigns WHERE id = ?");
$stmt->execute([$campaign_id]);
$campaign = $stmt->fetch();

if (!$campaign) {
    die("Campaign not found");
}

// ── Load Contents ──
$stmt_contents = $db->prepare("SELECT * FROM fb_campaign_contents WHERE campaign_id = ? ORDER BY id ASC");
$stmt_contents->execute([$campaign_id]);
$contents = $stmt_contents->fetchAll();

// ── Load Targets ──
$stmt_targets = $db->prepare("
    SELECT t.id as target_id, g.id as group_id, g.group_name, g.member_count, g.group_url, g.notes,
           (SELECT status FROM fb_campaign_schedules s WHERE s.campaign_id = t.campaign_id AND s.group_id = g.id ORDER BY id DESC LIMIT 1) as post_status,
           (SELECT error_msg FROM fb_campaign_schedules s WHERE s.campaign_id = t.campaign_id AND s.group_id = g.id ORDER BY id DESC LIMIT 1) as error_msg,
           (SELECT COUNT(*) FROM creative_post_queue q WHERE q.target_id = g.id AND q.target_type = 'group' AND q.status = 'completed') +
           (SELECT COUNT(*) FROM fb_campaign_schedules s2 WHERE s2.group_id = g.id AND s2.status = 'success') +
           IFNULL(g.manual_post_count, 0) as total_historical_posts
    FROM fb_campaign_targets t
    JOIN facebook_groups g ON t.fb_group_id = g.id
    WHERE t.campaign_id = ?
");
$stmt_targets->execute([$campaign_id]);
$targets = $stmt_targets->fetchAll();

// Calculate target statistics and sort them
$target_stats = [
    'total' => count($targets),
    'success' => 0,
    'failed' => 0,
    'pending' => 0,
    'none' => 0
];

foreach ($targets as $t) {
    if ($t['post_status'] === 'success') $target_stats['success']++;
    elseif ($t['post_status'] === 'failed') $target_stats['failed']++;
    elseif ($t['post_status'] === 'pending') $target_stats['pending']++;
    else $target_stats['none']++;
}

// Sort targets: failed first, then pending, none, success
usort($targets, function($a, $b) {
    $order = ['failed' => 1, 'pending' => 2, 'none' => 3, 'success' => 4];
    $statusA = $a['post_status'] ?: 'none';
    $statusB = $b['post_status'] ?: 'none';
    return $order[$statusA] <=> $order[$statusB];
});

// ── Load Schedules (For Info) ──
$stmt_schedules = $db->prepare("SELECT COUNT(*) as pending_count, MIN(scheduled_time) as next_run FROM fb_campaign_schedules WHERE campaign_id = ? AND status = 'pending'");
$stmt_schedules->execute([$campaign_id]);
$schedule_info = $stmt_schedules->fetch();

// ── Load Analytics ──
$stmt_clicks = $db->prepare("
    SELECT SUM(click_count) as total_clicks
    FROM fb_link_tracking
    WHERE campaign_id = ?
");
$stmt_clicks->execute([$campaign_id]);
$stats = $stmt_clicks->fetch();
$stats['unique_clicks'] = $stats['total_clicks'] ?? 0; // Approximate unique clicks

$top_groups = []; // We can't track Top Groups because short links are per content, not per group.

$stmt_ab_test = $db->prepare("
    SELECT content_id, SUM(click_count) as clicks
    FROM fb_link_tracking
    WHERE campaign_id = ?
    GROUP BY content_id
    ORDER BY clicks DESC
");
$stmt_ab_test->execute([$campaign_id]);
$ab_stats = [];
foreach ($stmt_ab_test->fetchAll() as $row) {
    $ab_stats[$row['content_id']] = $row['clicks'];
}

include "components/header.php";
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" />


<div class="page-header d-print-none">
  <div class="container-fluid">
    <div class="row g-2 align-items-center">
      <div class="col">
        <div class="page-pretitle">
          <a href="post_campaigns.php" class="text-muted">← กลับหน้าแคมเปญรวม</a>
        </div>
        <h2 class="page-title">
          จัดการแคมเปญ: <?= htmlspecialchars($campaign['name']) ?>
        </h2>
      </div>
      <div class="col-auto ms-auto d-print-none">
        <span class="badge bg-<?= $campaign['status'] == 'active' ? 'success' : 'secondary' ?> fs-3 p-2">
            สถานะ: <?= strtoupper($campaign['status']) ?>
        </span>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-fluid">
    <div class="row g-3">
        
      <!-- Left Column: Content Management -->
      <div class="col-md-7">
        <div class="card mb-3">
            <div class="card-header border-bottom-0">
                <h3 class="card-title">1. เนื้อหาแคมเปญ (A/B Test Variations)</h3>
            </div>
            <div class="card-body bg-light py-3">
                <?php if(empty($contents)): ?>
                    <div class="text-center text-muted py-4">ยังไม่มีเนื้อหาในแคมเปญนี้ กรุณาเพิ่มเนื้อหาอย่างน้อย 1 แบบ</div>
                <?php else: ?>
                    <?php 
                    $letters = range('A', 'Z');
                    foreach($contents as $index => $c): 
                        $letter = $letters[$index] ?? $index;
                        $media = json_decode($c['media_urls'] ?? '[]');
                    ?>
                    <div class="card mb-3 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong class="text-primary fs-4">Variation <?= $letter ?></strong>
                                <div>
                                    <button class="btn btn-sm btn-outline-secondary py-1 px-2" onclick='openEditVariation(<?= json_encode($c) ?>)'>
                                        ✏️ แก้ไข
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger py-1 px-2 ms-1" onclick="deleteVariation(<?= $c['id'] ?>)">
                                        🗑️ ลบ
                                    </button>
                                </div>
                            </div>
                            <p class="mb-2 text-dark" style="white-space: pre-wrap; font-size: 0.9rem;"><?= htmlspecialchars($c['message']) ?></p>
                            
                            <?php if(!empty($media)): ?>
                                <div class="d-flex align-items-center mb-2">
                                    <div class="d-flex flex-wrap gap-2 flex-grow-1 media-list" data-content-id="<?= $c['id'] ?>">
                                    <?php foreach($media as $m): ?>
                                        <div class="border rounded p-1 media-item" data-url="<?= htmlspecialchars($m) ?>" style="width: 80px; height: 80px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #000; cursor: grab;">
                                            <?php if($c['media_type'] == 'video'): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon text-white" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" style="pointer-events: none;"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 10l4.553 -2.276a1 1 0 0 1 1.447 .894v6.764a1 1 0 0 1 -1.447 .894l-4.553 -2.276v-4z" /><path d="M3 6m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z" /></svg>
                                            <?php else: ?>
                                                <img src="<?= htmlspecialchars($m) ?>" style="max-width: 100%; max-height: 100%; pointer-events: none;">
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                    </div>
                                    <div class="ms-3 d-flex flex-column align-items-center justify-content-center bg-danger-lt border border-danger border-dashed rounded trash-list" style="width: 80px; height: 80px; min-width: 80px; cursor: pointer;" data-content-id="<?= $c['id'] ?>" title="ลากรูปมาที่นี่เพื่อลบ">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon text-danger" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                                        <small class="text-danger mt-1" style="font-size: 10px;">ลากมาทิ้ง</small>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="text-muted small">
                                <strong>URL:</strong> <?= htmlspecialchars($c['target_url'] ?: 'ไม่ระบุ') ?> <br>
                                <strong>Short Code:</strong> <span class="user-select-all bg-yellow-lt px-1 rounded"><?= $c['short_code'] ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <button class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#modal-add-content">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                    เพิ่มเนื้อหา Variation
                </button>
            </div>
        </div>
      </div>
      
      <!-- Right Column: Targets & Launch -->
      <div class="col-md-5">
          
        <!-- Targets Block -->
        <div class="card mb-3">
            <div class="card-header border-bottom-0 pb-1">
                <h3 class="card-title">2. กลุ่มเป้าหมาย (Audience)</h3>
                <div class="card-actions">
                    <span class="badge bg-blue-lt">ทั้งหมด <?= $target_stats['total'] ?></span>
                </div>
            </div>
            <div class="card-body p-0 border-bottom">
                <div class="d-flex justify-content-between align-items-center px-3 py-2 bg-light text-muted small">
                    <div>
                        <span class="text-success me-2">✅ สำเร็จ: <?= $target_stats['success'] ?></span>
                        <span class="text-danger me-2">❌ ล้มเหลว: <?= $target_stats['failed'] ?></span>
                        <span class="text-warning">⏳ รอคิว: <?= $target_stats['pending'] ?></span>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">
                    <?php if(empty($targets)): ?>
                        <div class="p-4 text-center text-muted">
                            ยังไม่มีกลุ่มเป้าหมาย<br>
                            <a href="fb_groups.php" class="btn btn-sm btn-light mt-2">ไปที่หน้าเลือกกลุ่ม (fb_groups.php)</a>
                        </div>
                    <?php else: ?>
                        <?php foreach($targets as $t): ?>
                        <div class="list-group-item d-flex align-items-center">
                            <div class="text-truncate flex-grow-1" style="max-width: 80%;">
                                <a href="<?= htmlspecialchars($t['group_url']) ?>" target="_blank" class="text-body fw-bold d-block text-truncate"><?= htmlspecialchars($t['group_name']) ?></a>
                                <div class="text-muted small mt-1">
                                    <?= number_format($t['member_count']) ?> สมาชิก
                                    <span class="ms-2 badge bg-azure-lt" style="font-size: 0.7rem;">ที่ผ่านมา <?= $t['total_historical_posts'] ?> ครั้ง</span>
                                    
                                    <!-- Edit Note Icon -->
                                    <span style="cursor:pointer;" class="text-secondary ms-2" onclick="editGroupNote(<?= $t['group_id'] ?>, '<?= htmlspecialchars(addslashes($t['notes'] ?? '')) ?>')" title="บันทึกย่อ (Note)">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-pencil" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" /><path d="M13.5 6.5l4 4" /></svg>
                                        เพิ่มโน้ต
                                    </span>
                                </div>
                                <?php if(!empty($t['notes'])): ?>
                                    <div class="mt-1">
                                        <span class="badge bg-yellow-lt text-wrap text-start" style="font-size: 0.7rem; font-weight: normal; max-width: 100%; cursor:pointer;" onclick="editGroupNote(<?= $t['group_id'] ?>, '<?= htmlspecialchars(addslashes($t['notes'])) ?>')">
                                            📝 <?= htmlspecialchars($t['notes']) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="ms-auto d-flex align-items-center gap-2">
                                <?php if($t['post_status'] == 'success'): ?>
                                    <span class="badge bg-success-lt" style="font-size: 0.7rem;">✅ โพสต์แล้ว</span>
                                <?php elseif($t['post_status'] == 'pending'): ?>
                                    <span class="badge bg-warning-lt" style="font-size: 0.7rem;">⏳ รอคิว</span>
                                <?php elseif($t['post_status'] == 'failed'): ?>
                                    <span class="badge bg-danger-lt" style="font-size: 0.7rem;" title="<?= htmlspecialchars($t['error_msg'] ?? 'เกิดข้อผิดพลาด') ?>">❌ ล้มเหลว</span>
                                    <button class="btn btn-sm btn-icon btn-ghost-warning m-0" onclick="retrySingleTarget(<?= $campaign_id ?>, <?= $t['group_id'] ?>)" title="รีโพสต์เฉพาะกลุ่มนี้">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" /><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" /></svg>
                                    </button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-icon btn-ghost-danger m-0" onclick="removeTarget(<?= $t['target_id'] ?>)" title="ลบออก">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer p-2 text-center bg-light">
                <div class="small text-muted">
                    คุณสามารถเพิ่มกลุ่มเป้าหมายได้โดยไปที่ <a href="fb_groups.php">จัดการกลุ่ม (fb_groups.php)</a> และเลือก <b>Bulk Action > นำเข้าแคมเปญ</b>
                </div>
            </div>
        </div>

        <!-- Launch Block -->
        <div class="card border-primary shadow-sm">
            <div class="card-header bg-primary-lt">
                <h3 class="card-title text-primary fw-bold">3. 🚀 สั่งรันแคมเปญ (Launch)</h3>
            </div>
            <div class="card-body">
                <?php if($schedule_info['pending_count'] > 0): ?>
                    <div class="alert alert-success">
                        <strong>แคมเปญกำลังทำงาน!</strong><br>
                        มีคิวรอโพสต์ทั้งหมด <?= $schedule_info['pending_count'] ?> กลุ่ม<br>
                        คิวถัดไปจะเริ่มตอน: <?= date('d M Y H:i', strtotime($schedule_info['next_run'])) ?>
                    </div>
                    <div class="text-center">
                        <small class="text-muted">บอทจะคำนวณเวลาและทยอยโพสต์ให้อัตโนมัติ</small>
                    </div>
                    <button class="btn btn-outline-success w-100 mt-3" onclick="repostCampaign(<?= $campaign_id ?>)">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" /><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" /></svg>
                        รีเซ็ตและโพสต์ใหม่อีกครั้ง
                    </button>
                <?php else: ?>
                    <p class="text-muted small mb-3">เมื่อเนื้อหาและกลุ่มเป้าหมายพร้อมแล้ว คุณสามารถสร้างคิวให้บอทเริ่มทำงานได้ทันที</p>
                    
                    <div class="mb-3">
                        <label class="form-check form-radio-bg">
                            <input type="radio" class="form-check-input" name="launch_type" value="now" checked onchange="toggleSchedule(false)">
                            <span class="form-check-label">
                                <span class="fw-bold text-dark">เริ่มโพสต์ทันที (Now)</span>
                                <span class="d-block text-muted small">ระบบจะสร้างคิวและบอทจะเริ่มทะยอยโพสต์เดี๋ยวนี้</span>
                            </span>
                        </label>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-check form-radio-bg">
                            <input type="radio" class="form-check-input" name="launch_type" value="schedule" onchange="toggleSchedule(true)">
                            <span class="form-check-label">
                                <span class="fw-bold text-dark">ตั้งเวลาล่วงหน้า (Schedule)</span>
                                <span class="d-block text-muted small">ระบบจะสร้างคิวล่วงหน้า บอทจะรอจนกว่าจะถึงเวลา</span>
                            </span>
                        </label>
                    </div>
                    
                    <div id="schedule_picker" style="display: none;" class="mb-3 p-3 bg-light rounded border">
                        <label class="form-label">กำหนดเวลาเริ่มโพสต์</label>
                        <input type="datetime-local" id="schedule_time" class="form-control" value="<?= date('Y-m-d\TH:i') ?>">
                    </div>

                    <button class="btn btn-primary w-100 mb-2" onclick="launchCampaign(this)" <?= (empty($contents) || empty($targets)) ? 'disabled' : '' ?>>
                        สร้างคิว และเริ่มทำงาน
                    </button>
                    <?php if($target_stats['failed'] > 0): ?>
                    <button class="btn btn-outline-danger w-100 mb-2" onclick="retryFailedCampaign(<?= $campaign_id ?>)">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" /><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" /></svg>
                        รีโพสต์เฉพาะกลุ่มที่ล้มเหลว (<?= $target_stats['failed'] ?>)
                    </button>
                    <?php endif; ?>
                    <button class="btn btn-outline-success w-100" onclick="repostCampaign(<?= $campaign_id ?>)" <?= (empty($contents) || empty($targets)) ? 'disabled' : '' ?>>
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" /><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" /></svg>
                        เริ่มใหม่ทั้งหมด (รวมกลุ่มที่สำเร็จแล้ว)
                    </button>
                    <?php if(empty($contents) || empty($targets)): ?>
                        <div class="text-danger small text-center mt-2">ต้องมีเนื้อหาและกลุ่มเป้าหมายอย่างน้อย 1 รายการก่อนเริ่มแคมเปญ</div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Analytics Block -->
        <div class="card border-info shadow-sm mt-3">
            <div class="card-header bg-info-lt border-bottom-0">
                <h3 class="card-title text-info fw-bold">4. 📊 สถิติแคมเปญ (Analytics)</h3>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-around mb-4 text-center">
                    <div>
                        <div class="text-muted small">คลิกทั้งหมด</div>
                        <div class="fs-1 fw-bold text-dark"><?= number_format($stats['total_clicks'] ?? 0) ?></div>
                    </div>
                    <div>
                        <div class="text-muted small">จำนวนคน (Unique)</div>
                        <div class="fs-1 fw-bold text-dark"><?= number_format($stats['unique_clicks'] ?? 0) ?></div>
                    </div>
                </div>

                <hr>
                <h4 class="mb-2">🏆 กลุ่มที่ได้ผลดีที่สุด (Top Groups)</h4>
                <?php if (empty($top_groups)): ?>
                    <p class="text-muted small">ยังไม่มีข้อมูลการคลิก</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush mb-3">
                        <?php foreach($top_groups as $tg): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <a href="<?= htmlspecialchars($tg['group_url']) ?>" target="_blank" class="text-truncate" style="max-width: 75%;"><?= htmlspecialchars($tg['group_name']) ?></a>
                                <span class="badge bg-primary rounded-pill"><?= number_format($tg['clicks']) ?> คลิก</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <hr>
                <h4 class="mb-2">🎭 A/B Test (แยกตาม Content)</h4>
                <?php if (empty($ab_stats)): ?>
                    <p class="text-muted small">ยังไม่มีข้อมูลการคลิก</p>
                <?php else: ?>
                    <?php 
                    $letters = range('A', 'Z');
                    $total_ab_clicks = array_sum($ab_stats);
                    ?>
                    <?php foreach($contents as $index => $c): 
                        $letter = $letters[$index] ?? $index;
                        $clicks = $ab_stats[$c['id']] ?? 0;
                        $percent = $total_ab_clicks > 0 ? round(($clicks / $total_ab_clicks) * 100) : 0;
                    ?>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Variation <?= $letter ?></span>
                                <span><?= number_format($clicks) ?> คลิก (<?= $percent ?>%)</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?= $percent ?>%" aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>
        </div>

      </div>

    </div>
  </div>
</div>

<!-- Modal: Add Content Variation -->
<div class="modal modal-blur fade" id="modal-add-content" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">เพิ่มเนื้อหา Variation</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label">ข้อความโพสต์</label>
            <textarea class="form-control" id="var_message" rows="4" placeholder="พิมพ์ข้อความ... ใส่ [LINK] เพื่อแทนที่ด้วยลิงก์เข้าเว็บ"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">ลิงก์ปลายทาง (Target URL)</label>
            <input type="text" class="form-control" id="var_url" placeholder="https://...">
        </div>
        <div class="mb-3">
            <label class="form-label">รูปภาพ / วิดีโอ (เลือกได้หลายไฟล์)</label>
            <input type="file" class="form-control" id="var_media" multiple accept="image/*,video/*">
            <div id="media-preview-container" class="mt-2 d-flex flex-wrap gap-2"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn me-auto" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="button" class="btn btn-primary" onclick="addVariation(this)">บันทึกเนื้อหา</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Edit Content Variation -->
<div class="modal modal-blur fade" id="modal-edit-content" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">แก้ไขเนื้อหา Variation</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="edit_var_id">
        <div class="mb-3">
            <label class="form-label">ข้อความโพสต์</label>
            <textarea class="form-control" id="edit_var_message" rows="4"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">ลิงก์ปลายทาง (Target URL)</label>
            <input type="text" class="form-control" id="edit_var_url">
        </div>
        
        <div class="mb-3">
            <label class="form-label">รูปภาพเดิม (กดกากบาทเพื่อลบ)</label>
            <div id="edit-kept-media-container" class="d-flex flex-wrap gap-2"></div>
        </div>

        <div class="mb-3">
            <label class="form-label">อัปโหลดรูปภาพ / วิดีโอเพิ่ม</label>
            <input type="file" class="form-control" id="edit_var_media" multiple accept="image/*,video/*">
            <div id="edit-media-preview-container" class="mt-2 d-flex flex-wrap gap-2"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn me-auto" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="button" class="btn btn-primary" onclick="saveEditVariation(this)">บันทึกการแก้ไข</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Cropper -->
<div class="modal modal-blur fade" id="modal-cropper" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">ครอบตัดรูปภาพ (Facebook Standards)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0" style="background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAAXNSR0IArs4c6QAAADFJREFUOE9jZGBgEGHADv7//x/GQD1MRgwwGE4NhBvIBGzEqMGjBkNnBhwQGDFo1OCBAP2fM18Yd6NRAAAAAElFTkSuQmCC')">
        <div style="height: 60vh; width: 100%;">
            <img id="cropper-image" src="" style="max-width: 100%; display: block;">
        </div>
      </div>
      <div class="modal-footer d-flex justify-content-between flex-wrap gap-2">
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setCropRatio(1, 1)">1:1 (Square)</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setCropRatio(4, 5)">4:5 (Portrait)</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setCropRatio(1.91, 1)">1.91:1 (Landscape)</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setCropRatio(NaN, NaN)">Free</button>
            
            <button type="button" class="btn btn-outline-info btn-sm ms-2" onclick="rotateCropper(-90)">↺ หมุนซ้าย</button>
            <button type="button" class="btn btn-outline-info btn-sm" onclick="rotateCropper(90)">↻ หมุนขวา</button>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted" id="crop-counter">ภาพที่ 1 จาก 1</span>
            <button type="button" class="btn btn-primary" id="btn-save-crop" onclick="saveCropAndNext()">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                บันทึกและถัดไป
            </button>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function toggleSchedule(show) {
    document.getElementById('schedule_picker').style.display = show ? 'block' : 'none';
}

function addVariation(btn) {
    const msg = document.getElementById('var_message').value.trim();
    const url = document.getElementById('var_url').value.trim();
    
    if(!msg && croppedBlobs.length === 0) {
        return Swal.fire('คำเตือน', 'กรุณาระบุข้อความหรืออัปโหลดไฟล์อย่างน้อย 1 อย่าง', 'warning');
    }

    const fd = new FormData();
    fd.append('ajax_action', 'add_variation');
    fd.append('message', msg);
    fd.append('target_url', url);
    for(let i=0; i<croppedBlobs.length; i++) {
        fd.append('media_files[]', croppedBlobs[i], croppedBlobs[i].name || 'upload.jpg');
    }

    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'กำลังบันทึก...';

    fetch('manage_campaign.php?id=<?= $campaign_id ?>', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if(res.ok) {
                location.reload();
            } else {
                Swal.fire('Error', res.msg, 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(err => {
            Swal.fire('Error', 'Connection failed', 'error');
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
}

function removeTarget(targetId) {
    Swal.fire({
        title: 'ยืนยันลบกลุ่มนี้?',
        text: "ลบออกจากแคมเปญนี้เท่านั้น ไม่ได้ลบออกจากระบบฐานข้อมูล",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ลบออก',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('ajax_action', 'remove_target');
            fd.append('target_id', targetId);
            
            fetch('manage_campaign.php?id=<?= $campaign_id ?>', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if(res.ok) location.reload();
                    else Swal.fire('Error', res.msg, 'error');
                });
        }
    });
}

function launchCampaign(btn) {
    Swal.fire({
        title: 'ยืนยันการเริ่มแคมเปญ?',
        text: "ระบบจะสร้างคิวส่งให้บอททำงานตามที่คุณกำหนด",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'ยืนยันเริ่มแคมเปญ',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            const launchType = document.querySelector('input[name="launch_type"]:checked').value;
            const scheduleTime = document.getElementById('schedule_time').value;
            
            const fd = new FormData();
            fd.append('ajax_action', 'launch_campaign');
            fd.append('launch_type', launchType);
            fd.append('schedule_time', scheduleTime);
            
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = 'กำลังสร้างคิว...';

            fetch('manage_campaign.php?id=<?= $campaign_id ?>', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if(res.ok) {
                        Swal.fire('สำเร็จ', res.msg, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', res.msg, 'error');
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    }
                })
                .catch(err => {
                    Swal.fire('Error', 'Connection failed', 'error');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
        }
    });
}

// ------------------------------------
// Delete & Edit Variations
// ------------------------------------
function deleteVariation(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "คุณต้องการลบเนื้อหานี้ใช่หรือไม่?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'ลบเลย',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('ajax_action', 'delete_variation');
            fd.append('content_id', id);
            fetch('manage_campaign.php?id=<?= $campaign_id ?>', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if(res.ok) location.reload();
                    else Swal.fire('Error', res.msg, 'error');
                });
        }
    });
}

let keptMediaForEdit = [];

function openEditVariation(data) {
    document.getElementById('edit_var_id').value = data.id;
    document.getElementById('edit_var_message').value = data.message;
    document.getElementById('edit_var_url').value = data.target_url;
    
    // Clear new media preview
    editMediaInput.value = '';
    editPreviewContainer.innerHTML = '';
    croppedBlobs = [];
    currentUploadMode = 'edit';
    
    // Parse kept media
    keptMediaForEdit = [];
    try { keptMediaForEdit = JSON.parse(data.media_urls || '[]'); } catch(e){}
    renderKeptMedia();
    
    const modal = new bootstrap.Modal(document.getElementById('modal-edit-content'));
    modal.show();
}

function renderKeptMedia() {
    const container = document.getElementById('edit-kept-media-container');
    container.innerHTML = '';
    if(keptMediaForEdit.length === 0) {
        container.innerHTML = '<div class="text-muted small">ไม่มีรูปเดิม</div>';
        return;
    }
    
    keptMediaForEdit.forEach((url, idx) => {
        const div = document.createElement('div');
        div.className = 'border rounded p-1 position-relative';
        div.style.width = '80px';
        div.style.height = '80px';
        div.style.display = 'flex';
        div.style.alignItems = 'center';
        div.style.justifyContent = 'center';
        div.style.background = '#000';
        
        // Simplified check, could check data.media_type instead
        if(url.includes('.mp4') || url.includes('.mov')) {
            div.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="icon text-white" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 10l4.553 -2.276a1 1 0 0 1 1.447 .894v6.764a1 1 0 0 1 -1.447 .894l-4.553 -2.276v-4z" /><path d="M3 6m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z" /></svg>`;
        } else {
            const img = document.createElement('img');
            img.src = url;
            img.style.maxWidth = '100%';
            img.style.maxHeight = '100%';
            div.appendChild(img);
        }
        
        const removeBtn = document.createElement('span');
        removeBtn.className = 'badge bg-danger position-absolute top-0 end-0 m-1';
        removeBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="icon m-0" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>';
        removeBtn.style.padding = '4px';
        removeBtn.style.cursor = 'pointer';
        removeBtn.onclick = (e) => {
            e.preventDefault();
            const currentIdx = Array.from(container.children).indexOf(div);
            keptMediaForEdit.splice(currentIdx, 1);
            renderKeptMedia();
        };
        div.appendChild(removeBtn);
        container.appendChild(div);
    });
    
    // Make edit kept media sortable
    if(window.Sortable) {
        new Sortable(container, {
            animation: 150,
            ghostClass: 'bg-light',
            onEnd: function(evt) {
                const item = keptMediaForEdit.splice(evt.oldIndex, 1)[0];
                keptMediaForEdit.splice(evt.newIndex, 0, item);
            }
        });
    }
}

function saveEditVariation(btn) {
    const id = document.getElementById('edit_var_id').value;
    const msg = document.getElementById('edit_var_message').value.trim();
    const url = document.getElementById('edit_var_url').value.trim();
    
    if(!msg && keptMediaForEdit.length === 0 && croppedBlobs.length === 0) {
        return Swal.fire('คำเตือน', 'กรุณาระบุข้อความหรือรูปภาพอย่างน้อย 1 อย่าง', 'warning');
    }

    const fd = new FormData();
    fd.append('ajax_action', 'edit_variation');
    fd.append('content_id', id);
    fd.append('message', msg);
    fd.append('target_url', url);
    fd.append('kept_media', JSON.stringify(keptMediaForEdit));
    
    for(let i=0; i<croppedBlobs.length; i++) {
        fd.append('media_files[]', croppedBlobs[i], croppedBlobs[i].name || 'upload.jpg');
    }

    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'กำลังบันทึก...';

    fetch('manage_campaign.php?id=<?= $campaign_id ?>', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if(res.ok) {
                location.reload();
            } else {
                Swal.fire('Error', res.msg, 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(err => {
            Swal.fire('Error', 'Connection failed', 'error');
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
}

</script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.media-list').forEach(function(el) {
        const contentId = el.getAttribute('data-content-id');
        const trashEl = document.querySelector('.trash-list[data-content-id="'+contentId+'"]');
        
        new Sortable(el, {
            group: 'shared-' + contentId,
            animation: 150,
            ghostClass: 'bg-light',
            onEnd: function (evt) {
                updateMediaOrderAJAX(el, contentId);
            }
        });
        
        if(trashEl) {
            new Sortable(trashEl, {
                group: 'shared-' + contentId,
                animation: 150,
                onAdd: function (evt) {
                    const item = evt.item;
                    item.parentNode.removeChild(item); // Delete the item
                }
            });
        }
    });

    function updateMediaOrderAJAX(el, contentId) {
        const items = el.querySelectorAll('.media-item');
        let newMediaUrls = [];
        items.forEach(item => {
            newMediaUrls.push(item.getAttribute('data-url'));
        });
        
        const fd = new FormData();
        fd.append('ajax_action', 'update_media_order');
        fd.append('content_id', contentId);
        fd.append('media_urls', JSON.stringify(newMediaUrls));
        
        fetch('manage_campaign.php?id=<?= $campaign_id ?>', {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(res => {
            if(!res.ok) {
                Swal.fire('Error', res.msg, 'error');
            }
        });
    }
});
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script>
let currentCropIndex = 0;
let filesToCrop = [];
let croppedBlobs = [];
let cropperInstance = null;

const varMediaInput = document.getElementById('var_media');
const previewContainer = document.getElementById('media-preview-container');
const editMediaInput = document.getElementById('edit_var_media');
const editPreviewContainer = document.getElementById('edit-media-preview-container');

// Flag to know which modal triggered the cropper
let currentUploadMode = 'add'; // 'add' or 'edit'

varMediaInput.addEventListener('change', function(e) {
    if (!this.files || this.files.length === 0) return;
    currentUploadMode = 'add';
    filesToCrop = Array.from(this.files);
    croppedBlobs = [];
    currentCropIndex = 0;
    previewContainer.innerHTML = ''; 
    startCroppingNext();
});

editMediaInput.addEventListener('change', function(e) {
    if (!this.files || this.files.length === 0) return;
    currentUploadMode = 'edit';
    filesToCrop = Array.from(this.files);
    croppedBlobs = [];
    currentCropIndex = 0;
    editPreviewContainer.innerHTML = ''; 
    startCroppingNext();
});

function startCroppingNext() {
    if (currentCropIndex >= filesToCrop.length) {
        const modalEl = document.getElementById('modal-cropper');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
        renderPreviews();
        return;
    }
    
    const file = filesToCrop[currentCropIndex];
    
    if (file.type.startsWith('video/')) {
        croppedBlobs.push(file); 
        currentCropIndex++;
        startCroppingNext();
        return;
    }
    
    const url = URL.createObjectURL(file);
    const imgEl = document.getElementById('cropper-image');
    
    document.getElementById('crop-counter').innerText = `ภาพที่ ${currentCropIndex + 1} จาก ${filesToCrop.length}`;
    
    const modalEl = document.getElementById('modal-cropper');
    let modal = bootstrap.Modal.getInstance(modalEl);
    
    // If modal is already shown, just replace the image in cropper
    if (modalEl.classList.contains('show')) {
        if (cropperInstance) {
            cropperInstance.replace(url);
        } else {
            imgEl.src = url;
            cropperInstance = new Cropper(imgEl, {
                viewMode: 1, dragMode: 'move', autoCropArea: 1, restore: false,
                guides: true, center: true, highlight: false, cropBoxMovable: true,
                cropBoxResizable: true, toggleDragModeOnDblclick: false,
            });
        }
    } else {
        // First time opening the modal
        if (!modal) modal = new bootstrap.Modal(modalEl);
        imgEl.src = url;
        
        const onShow = function() {
            modalEl.removeEventListener('shown.bs.modal', onShow);
            if (cropperInstance) {
                cropperInstance.destroy();
                cropperInstance = null;
            }
            cropperInstance = new Cropper(imgEl, {
                viewMode: 1, dragMode: 'move', autoCropArea: 1, restore: false,
                guides: true, center: true, highlight: false, cropBoxMovable: true,
                cropBoxResizable: true, toggleDragModeOnDblclick: false,
            });
        };
        modalEl.addEventListener('shown.bs.modal', onShow);
        modal.show();
    }
}

function setCropRatio(w, h) {
    if (cropperInstance) cropperInstance.setAspectRatio(w / h);
}

function rotateCropper(degree) {
    if (cropperInstance) cropperInstance.rotate(degree);
}

function saveCropAndNext() {
    if (cropperInstance) {
        const btn = document.getElementById('btn-save-crop');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> กำลังประมวลผล...';
        
        setTimeout(() => {
            cropperInstance.getCroppedCanvas({
                maxWidth: 2048,
                maxHeight: 2048,
                fillColor: '#fff',
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            }).toBlob((blob) => {
                blob.name = filesToCrop[currentCropIndex].name;
                croppedBlobs.push(blob);
                
                currentCropIndex++;
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                startCroppingNext();
            }, 'image/jpeg', 0.9);
        }, 50);
    }
}

function renderPreviews() {
    const targetContainer = currentUploadMode === 'add' ? previewContainer : editPreviewContainer;
    targetContainer.innerHTML = '';
    croppedBlobs.forEach((blob, idx) => {
        const url = URL.createObjectURL(blob);
        const div = document.createElement('div');
        div.className = 'border rounded p-1 position-relative';
        div.style.width = '80px';
        div.style.height = '80px';
        div.style.display = 'flex';
        div.style.alignItems = 'center';
        div.style.justifyContent = 'center';
        div.style.background = '#000';
        
        if (blob.type.startsWith('video/')) {
            div.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="icon text-white" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 10l4.553 -2.276a1 1 0 0 1 1.447 .894v6.764a1 1 0 0 1 -1.447 .894l-4.553 -2.276v-4z" /><path d="M3 6m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z" /></svg>`;
        } else {
            const img = document.createElement('img');
            img.src = url;
            img.style.maxWidth = '100%';
            img.style.maxHeight = '100%';
            div.appendChild(img);
        }
        
        const removeBtn = document.createElement('span');
        removeBtn.className = 'badge bg-danger position-absolute top-0 end-0 m-1';
        removeBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="icon m-0" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>';
        removeBtn.style.padding = '4px';
        removeBtn.style.cursor = 'pointer';
        removeBtn.onclick = (e) => {
            e.preventDefault();
            croppedBlobs.splice(idx, 1);
            renderPreviews();
        };
        div.appendChild(removeBtn);
        
        targetContainer.appendChild(div);
    });
}

document.getElementById('modal-cropper').addEventListener('hidden.bs.modal', function () {
    renderPreviews();
});

function repostCampaign(id) {
    Swal.fire({
        title: 'โพสต์แคมเปญนี้อีกครั้ง?',
        text: "ระบบจะล้างประวัติการโพสต์ของแคมเปญนี้และสั่งให้บอทเริ่มโพสต์ใหม่ทั้งหมด คุณแน่ใจหรือไม่?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#d33',
        confirmButtonText: 'ใช่, โพสต์ซ้ำเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({title: 'กำลังประมวลผล...', allowOutsideClick: false});
            Swal.showLoading();
            
            const fd = new FormData();
            fd.append('ajax_action', 'repost_campaign');
            fd.append('campaign_id', id);
            
            fetch('manage_campaign.php?id=' + id, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if(res.ok) {
                    Swal.fire('สำเร็จ', res.msg, 'success').then(() => location.reload());
                } else {
                    Swal.fire('ข้อผิดพลาด', res.msg, 'error');
                }
            })
            .catch(e => {
                Swal.fire('ข้อผิดพลาด', e.message, 'error');
            });
        }
    });
}

function retryFailedCampaign(id) {
    Swal.fire({
        title: 'รีโพสต์เฉพาะที่ล้มเหลว?',
        text: "ระบบจะสร้างคิวโพสต์ใหม่เฉพาะกลุ่มที่ล้มเหลวเท่านั้น คุณแน่ใจหรือไม่?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ใช่, รีโพสต์ที่ล้มเหลว!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({title: 'กำลังประมวลผล...', allowOutsideClick: false});
            Swal.showLoading();
            
            const fd = new FormData();
            fd.append('ajax_action', 'retry_failed');
            fd.append('campaign_id', id);
            
            fetch('manage_campaign.php?id=' + id, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if(res.ok) {
                    Swal.fire('สำเร็จ', res.msg, 'success').then(() => location.reload());
                } else {
                    Swal.fire('ข้อผิดพลาด', res.msg, 'error');
                }
            })
            .catch(e => {
                Swal.fire('ข้อผิดพลาด', e.message, 'error');
            });
        }
    });
}

function retrySingleTarget(campaignId, groupId) {
    Swal.fire({
        title: 'นำเข้าคิวใหม่?',
        text: "ต้องการนำกลุ่มนี้ไปต่อคิวโพสต์ใหม่อีกครั้งใช่ไหม?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'ใช่, สร้างคิว',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({title: 'กำลังประมวลผล...', allowOutsideClick: false});
            Swal.showLoading();
            
            const fd = new FormData();
            fd.append('ajax_action', 'retry_single_failed');
            fd.append('campaign_id', campaignId);
            fd.append('group_id', groupId);
            
            fetch('manage_campaign.php?id=' + campaignId, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if(res.ok) {
                    Swal.fire('สำเร็จ', res.msg, 'success').then(() => location.reload());
                } else {
                    Swal.fire('ข้อผิดพลาด', res.msg, 'error');
                }
            })
            .catch(e => {
                Swal.fire('ข้อผิดพลาด', e.message, 'error');
            });
        }
    });
}

// Edit Group Note Function
window.editGroupNote = async function(groupId, currentNote) {
    const { value: text } = await Swal.fire({
        title: 'บันทึกย่อ (Note)',
        input: 'textarea',
        inputLabel: 'ข้อความโน๊ต (เช่น รออนุมัติ, โพสต์ติดชัวร์)',
        inputValue: currentNote,
        inputPlaceholder: 'ข้อความ...',
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก'
    });

    if (text !== undefined) {
        try {
            const response = await fetch('api/update_group_info.php?api_key=yakpho2026', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: groupId, field: 'notes', value: text })
            });
            const res = await response.json();
            if (res.ok) {
                Swal.fire('สำเร็จ', 'บันทึกโน๊ตเรียบร้อย', 'success').then(() => location.reload());
            } else {
                Swal.fire('ผิดพลาด', res.msg || 'ไม่สามารถบันทึกได้', 'error');
            }
        } catch (err) {
            Swal.fire('ผิดพลาด', 'เชื่อมต่อขัดข้อง', 'error');
        }
    }
};
</script>

<?php include "components/footer.php"; ?>
