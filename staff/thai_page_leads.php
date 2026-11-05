<?php
require_once "db.php";
require_login();

// AJAX Update Inline Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_update'])) {
    $id = (int)$_POST['id'];
    $field = preg_replace('/[^a-zA-Z_]/', '', $_POST['field']);
    $value = trim($_POST['value']);
    
    $allowed = ['province', 'followers', 'phone', 'email', 'website', 'page_name', 'page_url'];
    if (in_array($field, $allowed)) {
        if ($field === 'followers') {
            $value = str_replace(',', '', $value); // เอาลูกน้ำออกก่อนบันทึกลง int
        }
        $stmt = $db->prepare("UPDATE thai_massage_pages SET $field = ? WHERE id = ?");
        $stmt->execute([$value, $id]);
        echo "OK";
    }
    exit;
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $del_id = (int)$_POST['delete_id'];
    $stmt = $db->prepare("DELETE FROM thai_massage_pages WHERE id = ?");
    $stmt->execute([$del_id]);
    
    // Redirect back without POST data
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

include "components/header.php";

$search = $_GET['q'] ?? '';
$province_f = $_GET['province'] ?? '';

// Pagination
$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$where = "WHERE 1=1";
$params = [];

if($search) {
    $where .= " AND (page_name LIKE ? OR page_url LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if($province_f) {
    $where .= " AND province = ?";
    $params[] = $province_f;
}

$count_sql = "SELECT COUNT(*) FROM thai_massage_pages $where";
$stmt = $db->prepare($count_sql);
$stmt->execute($params);
$total_rows = $stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

$sql = "SELECT * FROM thai_massage_pages $where ORDER BY id DESC LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get provinces for filter
$provinces = $db->query("SELECT DISTINCT province FROM thai_massage_pages ORDER BY province ASC")->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="page-header d-print-none">
  <div class="container-fluid">
    <div class="row g-2 align-items-center">
      <div class="col">
        <h2 class="page-title text-indigo">
          <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-building-store" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0" /><path d="M3 7v1a3 3 0 0 0 6 0v-1m0 1a3 3 0 0 0 6 0v-1m0 1a3 3 0 0 0 6 0v-1h-18l2 -4h14l2 4" /><path d="M5 21l0 -10.15" /><path d="M19 21l0 -10.15" /><path d="M9 21l0 -4a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2l0 4" /></svg>
          Thai Page Leads 
          <span class="badge bg-indigo text-white ms-2" style="font-size: .7rem;"><?= number_format($total_rows) ?> ร้าน</span>
        </h2>
        <p class="text-muted small">ฐานข้อมูลเพจร้านนวด 77 จังหวัดทั่วไทย (Dept. 6)</p>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-fluid">
    <div class="card">
      <div class="card-body border-bottom py-3">
        <form action="" method="GET" class="row g-2">
          <div class="col-md-4">
            <input type="text" name="q" class="form-control" placeholder="ค้นหาชื่อเพจ..." value="<?= htmlspecialchars($search) ?>">
          </div>
          <div class="col-md-3">
            <select name="province" class="form-select">
              <option value="">ทุกจังหวัด (77 จังหวัด)</option>
              <?php foreach($provinces as $p): ?>
                <option value="<?= $p ?>" <?= $province_f == $p ? 'selected' : '' ?>><?= $p ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-auto">
            <button type="submit" class="btn btn-primary">กรองข้อมูล</button>
          </div>
        </form>
      </div>
      
      <div class="table-responsive">
        <table class="table table-vcenter card-table table-striped">
          <thead>
            <tr>
              <th>Page Name</th>
              <th>Province</th>
              <th class="text-end">Followers</th>
              <th>Contact Info</th>
              <th>Last Visited</th>
              <th class="w-1">Messenger</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($pages as $p): ?>
            <tr>
              <td>
                <div class="d-flex flex-column">
                  <span class="font-weight-medium text-primary h4 mb-0 editable-field" data-id="<?= $p['id'] ?>" data-field="page_name" contenteditable="true" style="cursor:text; border-bottom:1px dashed #ccc;" title="คลิกเพื่อแก้ไขชื่อเพจ">
                      <?= htmlspecialchars($p['page_name']) ?>
                  </span>
                  <div class="small mt-1 d-flex gap-1 align-items-center">
                      <a href="<?= htmlspecialchars($p['page_url']) ?>" target="_blank" class="text-muted" title="เปิดหน้าต่างใหม่">
                          <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 6l-8 4l8 4l8 -4l-8 -4" /><path d="M4 14l8 4l8 -4" /></svg>
                      </a>
                      <span class="text-muted text-truncate editable-field" style="max-width:200px; cursor:text; border-bottom:1px dashed #ccc;" data-id="<?= $p['id'] ?>" data-field="page_url" contenteditable="true" title="คลิกเพื่อแก้ไขลิงก์">
                          <?= htmlspecialchars($p['page_url']) ?>
                      </span>
                  </div>
                </div>
              </td>
              <td class="text-muted">
                <input type="text" class="editable-field bg-transparent border-0 text-muted" 
                       list="datalist-provinces" data-id="<?= $p['id'] ?>" data-field="province" 
                       value="<?= htmlspecialchars($p['province']) ?>" 
                       style="cursor:text; border-bottom:1px dashed #ccc; width:110px; font-size:.8rem; outline:none; padding-bottom:1px;" 
                       title="คลิกเพื่อแก้ไขจังหวัด (พิมพ์เพื่อค้นหา)">
              </td>
              <td class="text-end fw-bold">
                 <span class="editable-field" data-id="<?= $p['id'] ?>" data-field="followers" contenteditable="true" style="cursor:text; border-bottom:1px dashed #ccc; padding:2px 4px; display:inline-block; min-width:40px;" title="คลิกเพื่อแก้ไข"><?= $p['followers'] ? number_format($p['followers']) : 'N/A' ?></span>
              </td>
              <td>
                <div class="smallest">
                  <?php if($p['phone']): ?>
                    <div><svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-phone" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2" /></svg> <span class="editable-field" data-id="<?= $p['id'] ?>" data-field="phone" contenteditable="true" style="cursor:text; border-bottom:1px dashed #ccc;" title="คลิกเพื่อแก้ไข"><?= htmlspecialchars($p['phone']) ?></span></div>
                  <?php endif; ?>
                  <?php if($p['email']): ?>
                    <div class="text-truncate" style="max-width: 150px;"><svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-mail" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z" /><path d="M3 7l9 6l9 -6" /></svg> <span class="editable-field" data-id="<?= $p['id'] ?>" data-field="email" contenteditable="true" style="cursor:text; border-bottom:1px dashed #ccc;" title="คลิกเพื่อแก้ไข"><?= htmlspecialchars($p['email']) ?></span></div>
                  <?php endif; ?>
                  <?php if($p['website']): 
                      $display_url = preg_replace('#^https?://(www\.)?#i', '', $p['website']);
                      $display_url = rtrim($display_url, '/');
                  ?>
                    <div class="text-truncate" style="max-width: 250px;"><svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-world" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M3.6 9h16.8" /><path d="M3.6 15h16.8" /><path d="M11.5 3a17 17 0 0 0 0 18" /><path d="M12.5 3a17 17 0 0 1 0 18" /></svg> <a href="<?= $p['website'] ?>" target="_blank" class="text-reset" title="<?= htmlspecialchars($p['website']) ?>"><?= htmlspecialchars($display_url) ?></a></div>
                  <?php endif; ?>
                  <?php if(!$p['phone'] && !$p['email'] && !$p['website']): ?>
                    <span class="text-muted italic">- No Info -</span>
                  <?php endif; ?>
                </div>
              </td>
              <td>
                <span class="badge bg-green-lt">Active</span>
                <div class="text-muted smallest"><?= $p['last_visited_at'] ? date('d/m/Y H:i', strtotime($p['last_visited_at'])) : 'Just Scraped' ?></div>
              </td>
              <td>
                <div class="d-flex gap-2">
                    <a href="<?= $p['messenger_link'] ?>" target="_blank" class="btn btn-sm btn-outline-primary btn-pill">
                      <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-brand-messenger" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 20l1.3 -3.9a9 8 0 1 1 3.4 2.9l-4.7 1" /><path d="M8 13l3 -2l2 2l3 -2" /></svg>
                      Chat
                    </a>
                    <form method="POST" action="" class="m-0" onsubmit="return confirmForm(event, 'ยืนยันการลบรายชื่อนี้ใช่หรือไม่?');">
                        <input type="hidden" name="delete_id" value="<?= $p['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger btn-icon btn-pill" title="Delete">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                        </button>
                    </form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <?php if ($total_pages > 1): ?>
      <div class="card-footer d-flex align-items-center">
        <ul class="pagination m-0 ms-auto">
          <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $page - 1 ?>&q=<?= urlencode($search) ?>&province=<?= urlencode($province_f) ?>">prev</a>
          </li>
          <li class="page-item active"><a class="page-link" href="#"><?= $page ?></a></li>
          <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $page + 1 ?>&q=<?= urlencode($search) ?>&province=<?= urlencode($province_f) ?>">next</a>
          </li>
        </ul>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include "components/footer.php"; ?>

<!-- Datalist for Provinces -->
<datalist id="datalist-provinces">
    <?php foreach($provinces as $p_name): if($p_name): ?>
        <option value="<?= htmlspecialchars($p_name) ?>">
    <?php endif; endforeach; ?>
</datalist>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const editables = document.querySelectorAll('.editable-field');
    
    // Prevent Enter from causing new lines, auto-save instead
    editables.forEach(el => {
        el.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.blur();
            }
        });
        
        el.addEventListener('blur', function() {
            let id = this.getAttribute('data-id');
            let field = this.getAttribute('data-field');
            let value = this.tagName === 'INPUT' ? this.value.trim() : this.innerText.trim();
            
            // Show loading color
            let originalColor = this.style.backgroundColor;
            this.style.backgroundColor = '#fff3cd'; 
            
            // Post via AJAX
            let formData = new URLSearchParams();
            formData.append('ajax_update', '1');
            formData.append('id', id);
            formData.append('field', field);
            formData.append('value', value);
            
            fetch('thai_page_leads.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            })
            .then(res => res.text())
            .then(data => {
                if(data.trim() === 'OK') {
                    this.style.backgroundColor = '#d1e7dd'; // Green success
                    setTimeout(() => { this.style.backgroundColor = originalColor || 'transparent'; }, 1000);
                } else {
                    this.style.backgroundColor = '#f8d7da'; // Error red
                    setTimeout(() => { this.style.backgroundColor = originalColor || 'transparent'; }, 2000);
                }
            })
            .catch(err => {
                console.error(err);
                this.style.backgroundColor = '#f8d7da';
                setTimeout(() => { this.style.backgroundColor = originalColor || 'transparent'; }, 2000);
            });
        });
    });
});
</script>
