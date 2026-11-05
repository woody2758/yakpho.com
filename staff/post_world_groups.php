<?php
require_once "db.php";

// Auto migration for completed_at
try { $db->exec("ALTER TABLE creative_post_queue ADD COLUMN completed_at DATETIME NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE creative_post_queue ADD COLUMN strategy_marker VARCHAR(50) NULL"); } catch (Exception $e) {}
try { $db->exec("ALTER TABLE creative_post_queue ADD COLUMN campaign_tag VARCHAR(255) NULL"); } catch (Exception $e) {}

// ── Handle AJAX Requests ──
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $action = $_POST['ajax_action'];
    
    if ($action === 'add') {
        $group_ids = $_POST['group_ids'] ?? [];
        $ai_strategy = $_POST['ai_strategy'] ?? 'Green';
        $form_campaign_tag = trim($_POST['campaign_tag'] ?? '');
        if ($form_campaign_tag === '') $form_campaign_tag = null;
        
        $added = 0;
        foreach ($group_ids as $gid) {
            $insert_strategy = $ai_strategy;
            $insert_tag = $form_campaign_tag;
            
            $g_info = $db->query("SELECT strategy_marker, campaign_tag FROM facebook_groups WHERE id = " . intval($gid))->fetch();
            
            // ถ้าเลือกเป็น All Case ให้ดึง strategy จากข้อมูลกลุ่มมาแทน
            if ($ai_strategy === 'All') {
                $sm = $g_info['strategy_marker'] ?? 'Observe';
                if($sm == 'Hard') $insert_strategy = 'Green';
                elseif($sm == 'Soft') $insert_strategy = 'Yellow';
                else $insert_strategy = 'Red';
            }
            
            $stmt = $db->prepare("INSERT INTO creative_post_queue (target_id, target_type, scheduled_at, status, strategy_marker, campaign_tag) VALUES (?, 'group', NOW(), 'pending', ?, ?)");
            if ($stmt->execute([$gid, $insert_strategy, $insert_tag])) {
                $added++;
                if ($insert_tag !== null) {
                    $db->prepare("UPDATE facebook_groups SET campaign_tag = ? WHERE id = ?")->execute([$insert_tag, $gid]);
                }
            }
        }
        echo json_encode(['ok' => true, 'msg' => "เพิ่มคิว $added รายการเรียบร้อยแล้ว"]);
        exit;
    }
    
    if ($action === 'update_group_campaign_tag') {
        $id = $_POST['id'];
        $tag = trim($_POST['tag']);
        if ($tag === '') $tag = null;
        $stmt = $db->prepare("UPDATE facebook_groups SET campaign_tag = ? WHERE id = ?");
        $stmt->execute([$tag, $id]);
        echo json_encode(['ok' => true]);
        exit;
    }
    

    
    if ($action === 'move') {
        $id = $_POST['id'];
        $to_status = $_POST['to_status']; // 'pending' or 'completed'
        if ($to_status === 'completed') {
            $stmt = $db->prepare("UPDATE creative_post_queue SET status = 'completed', completed_at = NOW() WHERE id = ?");
        } else {
            $stmt = $db->prepare("UPDATE creative_post_queue SET status = 'pending', completed_at = NULL WHERE id = ?");
        }
        $stmt->execute([$id]);
        echo json_encode(['ok' => true]);
        exit;
    }
    
    if ($action === 'delete') {
        $id = $_POST['id'];
        $stmt = $db->prepare("DELETE FROM creative_post_queue WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'load_queues') {
        $pending = $db->query("
            SELECT q.*, g.group_name, g.group_url, g.campaign_tag,
                   (SELECT COUNT(*) FROM creative_post_queue c2 WHERE c2.target_id = q.target_id AND c2.status = 'completed') as total_posts
            FROM creative_post_queue q 
            LEFT JOIN facebook_groups g ON q.target_id = g.id 
            WHERE q.target_type = 'group' AND q.status = 'pending'
            ORDER BY q.id ASC
        ")->fetchAll();

        $completed = $db->query("
            SELECT q.*, g.group_name, g.group_url, g.campaign_tag,
                   (SELECT COUNT(*) FROM creative_post_queue c2 WHERE c2.target_id = q.target_id AND c2.status = 'completed') as total_posts
            FROM creative_post_queue q 
            LEFT JOIN facebook_groups g ON q.target_id = g.id 
            WHERE q.target_type = 'group' AND q.status = 'completed'
            ORDER BY q.completed_at DESC
            LIMIT 500
        ")->fetchAll();
        
        // Output HTML for Pending
        ob_start();
        foreach($pending as $q): ?>
        <tr>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="deleteQueue(this, '<?= $q['id'] ?>', '<?= $q['target_id'] ?>')" title="เอาออกจากคิว">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l14 0"/><path d="M5 12l6 6"/><path d="M5 12l6 -6"/></svg>
                </button>
            </td>
            <td>
                <div class="d-flex align-items-center">
                    <div class="text-truncate fw-bold me-2" style="max-width: 180px;" title="<?= htmlspecialchars($q['group_name']) ?>">
                        <?= htmlspecialchars($q['group_name']) ?>
                    </div>
                    <a href="<?= htmlspecialchars($q['group_url'] ?? '#') ?>" target="_blank" class="text-muted" title="ดูหน้ากลุ่ม">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/></svg>
                    </a>
                </div>
            </td>
            <td class="text-center text-nowrap">
                <?php if($q['total_posts'] > 0): ?>
                    <span class="badge bg-purple-lt" style="font-size: 9px; font-weight: normal; padding: 2px 6px;"><?= $q['total_posts'] ?> ครั้ง</span>
                <?php else: ?>
                    <span class="text-muted">-</span>
                <?php endif; ?>
            </td>
            <td>
                <div class="d-flex flex-wrap align-items-center gap-1">
                    <?php $tagStr = $q['campaign_tag'] ?? ''; ?>
                    <span class="badge bg-secondary-lt text-muted" style="cursor: pointer; padding: 2px 4px; font-size: 10px; font-weight: normal;" onclick="openTagModal(<?= $q['target_id'] ?>, '<?= htmlspecialchars(addslashes($tagStr)) ?>')" title="เพิ่ม/แก้ไขแท็ก">+</span>
                    <?php 
                      $tags = $tagStr ? array_filter(array_map('trim', explode(',', $tagStr))) : [];
                      if($tags): 
                        foreach($tags as $t): ?>
                          <div class="badge bg-<?= getTagColor($t) ?>-lt" style="font-size: 9px; font-weight: normal; padding: 2px 6px;"><?= htmlspecialchars($t) ?></div>
                        <?php endforeach;
                      endif; ?>
                </div>
            </td>
            <td>
                <?php 
                    $sm = $q['strategy_marker'];
                    $bclass = 'bg-secondary';
                    if($sm=='Green') $bclass = 'bg-success';
                    if($sm=='Yellow') $bclass = 'bg-warning text-dark';
                    if($sm=='Red') $bclass = 'bg-danger';
                ?>
                <span class="badge <?= $bclass ?>" style="font-size: 9px; font-weight: normal; padding: 2px 6px;"><?= htmlspecialchars($sm) ?></span>
            </td>
            <td><span class="badge bg-warning" style="font-size: 9px; font-weight: normal; padding: 2px 6px;">รอคิว</span></td>
            <td class="text-end text-nowrap">
                <button type="button" class="btn btn-sm btn-icon btn-outline-success" onclick="moveQueue(this, '<?= $q['id'] ?>', 'completed', '<?= $q['target_id'] ?>')" title="ย้ายไปโพสต์แล้ว">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                </button>
            </td>
        </tr>
        <?php endforeach;
        if(empty($pending)): ?><tr><td colspan="4" class="text-center text-muted py-4">ไม่มีคิวงานที่รอดำเนินการ</td></tr><?php endif;
        $html_pending = ob_get_clean();

        // Output HTML for Completed
        ob_start();
        $i=1; foreach($completed as $q): ?>
        <tr>
            <td class="text-center text-muted small"><?= $i++ ?></td>
            <td>
                <div class="d-flex align-items-center">
                    <div class="text-truncate fw-bold me-2" style="max-width: 180px;" title="<?= htmlspecialchars($q['group_name']) ?>">
                        <?= htmlspecialchars($q['group_name']) ?>
                    </div>
                    <a href="<?= htmlspecialchars($q['group_url'] ?? '#') ?>" target="_blank" class="text-muted" title="ดูหน้ากลุ่ม">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/></svg>
                    </a>
                </div>
            </td>
            <td>
                <div class="d-flex flex-wrap align-items-center gap-1">
                    <?php $tagStr = $q['campaign_tag'] ?? ''; ?>
                    <span class="badge bg-secondary-lt text-muted" style="cursor: pointer; padding: 2px 4px; font-size: 10px; font-weight: normal;" onclick="openTagModal(<?= $q['target_id'] ?>, '<?= htmlspecialchars(addslashes($tagStr)) ?>')" title="เพิ่ม/แก้ไขแท็ก">+</span>
                    <?php 
                      $tags = $tagStr ? array_filter(array_map('trim', explode(',', $tagStr))) : [];
                      if($tags): 
                        foreach($tags as $t): ?>
                          <div class="badge bg-<?= getTagColor($t) ?>-lt" style="font-size: 9px; font-weight: normal; padding: 2px 6px;"><?= htmlspecialchars($t) ?></div>
                        <?php endforeach;
                      endif; ?>
                </div>
            </td>
            <td class="text-center text-nowrap">
                <?php if($q['total_posts'] > 0): ?>
                    <div class="badge bg-purple-lt" style="font-size: 9px; font-weight: normal; padding: 2px 6px;"><?= $q['total_posts'] ?> ครั้ง</div>
                <?php else: ?>
                    <span class="text-muted">-</span>
                <?php endif; ?>
            </td>
            <td class="text-center text-nowrap">
                <div class="small text-muted"><?= $q['completed_at'] ? date('d M, H:i', strtotime($q['completed_at'])) : '-' ?></div>
            </td>
            <td class="text-end text-nowrap">
                <button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="deleteQueue(this, '<?= $q['id'] ?>', '<?= $q['target_id'] ?>')" title="ลบออกจากประวัติ">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
                </button>
            </td>
        </tr>
        <?php endforeach;
        if(empty($completed)): ?><tr><td colspan="4" class="text-center text-muted py-4">ยังไม่มีประวัติ</td></tr><?php endif;
        $html_completed = ob_get_clean();

        echo json_encode([
            'ok' => true, 
            'pending_html' => $html_pending, 
            'completed_html' => $html_completed,
            'pending_count' => count($pending),
            'completed_count' => count($completed)
        ]);
        exit;
    }
    if ($action === 'delete_by_target') {
        $target_id = $_POST['target_id'];
        $stmt = $db->prepare("DELETE FROM creative_post_queue WHERE target_id = ? AND status = 'pending'");
        $stmt->execute([$target_id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'update_member_count') {
        $id = $_POST['id'];
        $count = (int)str_replace(',', '', $_POST['member_count']);
        $db->prepare("UPDATE facebook_groups SET member_count = ? WHERE id = ?")->execute([$count, $id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'remove_from_list') {
        $target_id = $_POST['target_id'];
        // Set post_ready = 0 to remove from this page's list
        $db->prepare("UPDATE facebook_groups SET post_ready = 0 WHERE id = ?")->execute([$target_id]);
        // Also remove from pending queue if it is in queue
        $db->prepare("DELETE FROM creative_post_queue WHERE target_id = ? AND status = 'pending'")->execute([$target_id]);
        echo json_encode(['ok' => true]);
        exit;
    }

}

// ── Load Initial Data ──
$groups_query = "
    SELECT g.*,
           (SELECT id FROM creative_post_queue WHERE target_id = g.id AND status = 'pending' LIMIT 1) as queue_id,
           (SELECT COUNT(*) FROM creative_post_queue c2 WHERE c2.target_id = g.id AND c2.status = 'completed') as total_posts,
           (SELECT MAX(completed_at) FROM creative_post_queue c3 WHERE c3.target_id = g.id AND c3.status = 'completed') as last_post_date
    FROM facebook_groups g 
    WHERE g.join_status = 'Joined' AND g.strategy_marker != 'Blacklist' AND g.post_ready = 1
    ORDER BY g.id DESC
";
$groups = $db->query($groups_query)->fetchAll();

$content_counts = $db->query("SELECT ai_strategy, COUNT(id) as c FROM creative_contents GROUP BY ai_strategy")->fetchAll(PDO::FETCH_KEY_PAIR);
$count_green = $content_counts['Green'] ?? 0;
$count_yellow = $content_counts['Yellow'] ?? 0;
$count_red = $content_counts['Red'] ?? 0;

$g_green = 0; $g_yellow = 0; $g_red = 0;
foreach($groups as $g) {
    $sm = $g['strategy_marker'] ?? 'Observe';
    if($sm == 'Hard') $g_green++;
    elseif($sm == 'Soft') $g_yellow++;
    else $g_red++;
}

$content_tags = $db->query("SELECT DISTINCT campaign_tag FROM creative_contents WHERE campaign_tag IS NOT NULL AND trim(campaign_tag) != '' ORDER BY campaign_tag ASC")->fetchAll(PDO::FETCH_COLUMN);

include "components/header.php";
?>

<div class="page-header d-print-none">
  <div class="container-fluid">
    <div class="row g-2 align-items-center">
      <div class="col">
        <h2 class="page-title">
          <svg xmlns="http://www.w3.org/2000/svg" class="icon text-primary me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M22 4.01c-1 .49-1.98 .689-3 .99c-1.121-1.265-2.783-1.335-4.38-.737s-2.643 2.06-2.62 3.737v1c-3.245.083-6.135-1.395-8-4c0 0-4.182 7.433 4 11c-1.872 1.247-3.739 2.088-6 2c3.308 1.803 6.913 2.423 10.034 1.517c3.58-1.04 6.522-3.723 7.651-7.742a13.84 13.84 0 0 0 .497-3.753c-.002-.249 1.51-2.772 1.818-4.013z"/></svg>
          โพสต์กลุ่มขายของทั่วโลก (Worldwide FB Groups)
        </h2>
        <div class="text-muted mt-1 small">สั่งงานให้ AI โพสต์โปรโมท หรือคอมเมนต์ตามน้ำ ในกลุ่ม Facebook ทั่วโลก</div>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-fluid">
    <div class="row">
      <!-- Job Queue -->
      <div class="col-md-12">
        <div class="card shadow-sm border-info">
          <div class="card-header bg-light">
            <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
              <li class="nav-item">
                <a href="#tabs-pending" class="nav-link active fw-bold text-info" data-bs-toggle="tab">
                  รอคิว (<span id="count-pending">0</span>)
                </a>
              </li>
              <li class="nav-item">
                <a href="#tabs-completed" class="nav-link fw-bold text-success" data-bs-toggle="tab">
                  โพสต์แล้ว (<span id="count-completed">0</span>)
                </a>
              </li>
            </ul>
          </div>
          <div class="card-body p-0">
            <div class="tab-content">
              
              <!-- Tab Pending -->
              <div class="tab-pane active show" id="tabs-pending">
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                  <table class="table table-vcenter table-sm">
                    <thead>
                      <tr>
                        <th class="w-1 text-center"></th>
                        <th>กลุ่มเป้าหมาย</th>
                        <th class="text-center">จำนวนโพสต์</th>
                        <th>TAGS</th>
                        <th>Strategy</th>
                        <th>สถานะ</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody id="tbody-pending">
                        <!-- Loaded via AJAX -->
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Tab Completed -->
              <div class="tab-pane" id="tabs-completed">
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                  <table class="table table-vcenter table-sm">
                    <thead>
                      <tr>
                        <th class="w-1 text-center">#</th>
                        <th>กลุ่มเป้าหมาย</th>
                        <th>TAGS</th>
                        <th class="text-center">จำนวนโพสต์</th>
                        <th class="text-center">โพสต์ล่าสุด</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody id="tbody-completed">
                        <!-- Loaded via AJAX -->
                    </tbody>
                  </table>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- DataTables -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadQueues();
});

// Load Queues via AJAX
function loadQueues() {
    const fd = new FormData();
    fd.append('ajax_action', 'load_queues');
    fetch('post_world_groups.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if(res.ok) {
                document.getElementById('tbody-pending').innerHTML = res.pending_html;
                document.getElementById('tbody-completed').innerHTML = res.completed_html;
                document.getElementById('count-pending').innerText = res.pending_count;
                document.getElementById('count-completed').innerText = res.completed_count;
            }
        });
}

// Move Queue
window.moveQueue = function(btn, id, to_status, targetId) {
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    const fd = new FormData();
    fd.append('ajax_action', 'move');
    fd.append('id', id);
    fd.append('to_status', to_status);

    fetch('post_world_groups.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            if(res.ok) {
                loadQueues();
            }
            else Swal.fire('Error', 'เกิดข้อผิดพลาดในการย้ายคิว', 'error');
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
};

// Delete Queue
window.deleteQueue = function(btn, id, targetId) {
    Swal.fire({
        title: 'ยืนยันการลบ',
        text: 'คุณต้องการลบคิวงานนี้ใช่หรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            const fd = new FormData();
            fd.append('ajax_action', 'delete');
            fd.append('id', id);
            fetch('post_world_groups.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if(res.ok) {
                        loadQueues();
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
        }
    });
};
</script><?php include "components/footer.php"; ?>
