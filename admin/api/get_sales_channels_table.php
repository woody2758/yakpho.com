<?php
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/auth.php";

header('Content-Type: application/json');

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = 20;
$offset = ($page - 1) * $limit;

try {
    $whereClause = "";
    $params = [];
    
    if ($search !== '') {
        $whereClause = "WHERE channel_name LIKE ? OR channel_code LIKE ?";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    // Get total rows
    $countSql = "SELECT COUNT(*) FROM sales_channels $whereClause";
    $stmtCount = $db->prepare($countSql);
    $stmtCount->execute($params);
    $totalRows = $stmtCount->fetchColumn();
    $totalPages = ceil($totalRows / $limit);
    
    // Get data
    $sql = "SELECT * FROM sales_channels $whereClause ORDER BY channel_index ASC, channel_id DESC LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $channels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $html = '<div class="table-responsive"><table class="table table-hover align-middle mb-0">';
    $html .= '<thead class="table-light">
                <tr>
                    <th width="40"></th>
                    <th width="80" class="text-center">ลำดับ</th>
                    <th>ชื่อช่องทางขาย</th>
                    <th>รหัสช่องทาง</th>
                    <th width="120" class="text-center">สถานะ</th>
                    <th width="150" class="text-end">จัดการ</th>
                </tr>
              </thead><tbody>';
              
    if (count($channels) > 0) {
        foreach ($channels as $row) {
            $statusBadge = $row['channel_status'] == 1 
                ? '<span class="badge bg-success bg-opacity-10 text-success"><i data-lucide="check-circle" class="icon-sm"></i> เปิดใช้งาน</span>'
                : '<span class="badge bg-secondary bg-opacity-10 text-secondary"><i data-lucide="x-circle" class="icon-sm"></i> ปิดใช้งาน</span>';
                
            $channel_name = $row['channel_name'];
            $lower_channel = strtolower($channel_name);
            $channel_color = '#6c757d';
            $channel_icon = 'tag';
            if (strpos($lower_channel, 'line') !== false) {
                $channel_color = '#06C755';
                $channel_icon = 'message-circle';
            } else if (strpos($lower_channel, 'facebook') !== false || strpos($lower_channel, 'fb') !== false) {
                $channel_color = '#1877F2';
                $channel_icon = 'facebook';
            } else if (strpos($lower_channel, 'tiktok') !== false) {
                $channel_color = '#010101';
                $channel_icon = 'music';
            } else if (strpos($lower_channel, 'shopee') !== false) {
                $channel_color = '#EE4D2D';
                $channel_icon = 'shopping-bag';
            } else if (strpos($lower_channel, 'lazada') !== false) {
                $channel_color = '#101566';
                $channel_icon = 'shopping-cart';
            } else if (strpos($lower_channel, 'website') !== false) {
                $channel_color = '#0ea5e9';
                $channel_icon = 'globe';
            } else if (strpos($lower_channel, 'phone') !== false) {
                $channel_color = '#0d6efd';
                $channel_icon = 'phone';
            } else if (strpos($lower_channel, 'therapist') !== false) {
                $channel_color = '#6f42c1';
                $channel_icon = 'heart';
            }

            $html .= '<tr class="sortable-row" data-id="'.$row['channel_id'].'">';
            $html .= '<td class="text-center"><i data-lucide="grip-vertical" class="text-muted drag-handle" style="cursor: grab; width: 18px;"></i></td>';
            $html .= '<td class="text-center">'.$row['channel_index'].'</td>';
            $sc_icon_html = ($channel_icon === 'facebook')
    ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="color: '.$channel_color.';"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>'
    : '<i data-lucide="'.$channel_icon.'" style="width: 16px; height: 16px; color: '.$channel_color.';"></i>';
            $html .= '<td><div class="d-flex align-items-center gap-2">'.$sc_icon_html.' <strong>'.htmlspecialchars($channel_name).'</strong></div></td>';
            $html .= '<td><span class="badge bg-light text-dark border">'.htmlspecialchars($row['channel_code']).'</span></td>';
            $html .= '<td class="text-center">'.$statusBadge.'</td>';
            $html .= '<td class="text-end">
                        <button onclick="editChannel('.$row['channel_id'].')" class="btn btn-sm btn-outline-primary" title="แก้ไข">
                            <i data-lucide="edit-2" class="icon-sm"></i>
                        </button>
                        <button onclick="deleteChannel('.$row['channel_id'].', \''.htmlspecialchars(addslashes($row['channel_name'])).'\')" class="btn btn-sm btn-outline-danger" title="ลบ">
                            <i data-lucide="trash-2" class="icon-sm"></i>
                        </button>
                      </td>';
            $html .= '</tr>';
        }
    } else {
        $html .= '<tr><td colspan="5" class="text-center py-4 text-muted">ไม่พบข้อมูล</td></tr>';
    }
    
    $html .= '</tbody></table></div>';
    
    // Pagination HTML
    $pagination = '';
    if ($totalPages > 1) {
        $pagination .= '<nav><ul class="pagination justify-content-center mb-0">';
        
        // Prev
        $prevDisabled = ($page <= 1) ? 'disabled' : '';
        $prevPage = $page - 1;
        $pagination .= "<li class='page-item $prevDisabled'>
                          <a class='page-link' href='javascript:void(0)' onclick='loadTable($prevPage, \"".htmlspecialchars($search)."\")'>ก่อนหน้า</a>
                        </li>";
                        
        // Pages
        for ($i = 1; $i <= $totalPages; $i++) {
            $active = ($i == $page) ? 'active' : '';
            $pagination .= "<li class='page-item $active'>
                              <a class='page-link' href='javascript:void(0)' onclick='loadTable($i, \"".htmlspecialchars($search)."\")'>$i</a>
                            </li>";
        }
        
        // Next
        $nextDisabled = ($page >= $totalPages) ? 'disabled' : '';
        $nextPage = $page + 1;
        $pagination .= "<li class='page-item $nextDisabled'>
                          <a class='page-link' href='javascript:void(0)' onclick='loadTable($nextPage, \"".htmlspecialchars($search)."\")'>ถัดไป</a>
                        </li>";
                        
        $pagination .= '</ul></nav>';
    }
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'pagination' => $pagination
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
