<?php
/**
 * Render Pagination (Bootstrap 5 Style)
 * 
 * @param int $currentPage  หน้าปัจจุบัน
 * @param int $totalPages   จำนวนหน้าทั้งหมด
 * @param string $urlPattern  Pattern ของ URL โดยใช้ %d แทนเลขหน้า เช่น "?page=%d&search=abc"
 * @return string HTML Pagination
 */
function render_pagination($currentPage, $totalPages, $urlPattern) {
    if ($totalPages <= 1) return "";

    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-end mb-0">';

    // ปุ่ม Previous
    if ($currentPage > 1) {
        $prevUrl = str_replace('%d', $currentPage - 1, $urlPattern);
        $html .= '<li class="page-item"><a class="page-link" href="' . $prevUrl . '">Prev</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Prev</span></li>';
    }

    // Logic การแสดงเลขหน้า (Sliding Window)
    // แสดง: 1 ... [current-2] [current-1] [current] [current+1] [current+2] ... [last]
    
    $adjacents = 2; // จำนวนหน้าที่จะแสดงรอบๆ หน้าปัจจุบัน

    // กรณีหน้าทั้งหมดน้อยกว่า 7 หน้า (แสดงหมดเลย)
    if ($totalPages <= 7) {
        for ($i = 1; $i <= $totalPages; $i++) {
            $active = ($i == $currentPage) ? 'active' : '';
            $url = str_replace('%d', $i, $urlPattern);
            $html .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $url . '">' . $i . '</a></li>';
        }
    } else {
        // กรณีหน้าเยอะๆ ตัดด้วย ...
        
        // ช่วงต้น: 1 2 3 4 5 ... Last
        if ($currentPage < 4) {
            for ($i = 1; $i <= 5; $i++) {
                $active = ($i == $currentPage) ? 'active' : '';
                $url = str_replace('%d', $i, $urlPattern);
                $html .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $url . '">' . $i . '</a></li>';
            }
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            $lastUrl = str_replace('%d', $totalPages, $urlPattern);
            $html .= '<li class="page-item"><a class="page-link" href="' . $lastUrl . '">' . $totalPages . '</a></li>';
        }
        // ช่วงท้าย: 1 ... [Last-4] [Last-3] [Last-2] [Last-1] [Last]
        elseif ($currentPage > ($totalPages - 3)) {
            $firstUrl = str_replace('%d', 1, $urlPattern);
            $html .= '<li class="page-item"><a class="page-link" href="' . $firstUrl . '">1</a></li>';
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            
            for ($i = $totalPages - 4; $i <= $totalPages; $i++) {
                $active = ($i == $currentPage) ? 'active' : '';
                $url = str_replace('%d', $i, $urlPattern);
                $html .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $url . '">' . $i . '</a></li>';
            }
        }
        // ช่วงกลาง: 1 ... [curr-1] [curr] [curr+1] ... Last
        else {
            $firstUrl = str_replace('%d', 1, $urlPattern);
            $html .= '<li class="page-item"><a class="page-link" href="' . $firstUrl . '">1</a></li>';
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            
            for ($i = $currentPage - 1; $i <= $currentPage + 1; $i++) {
                $active = ($i == $currentPage) ? 'active' : '';
                $url = str_replace('%d', $i, $urlPattern);
                $html .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $url . '">' . $i . '</a></li>';
            }
            
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            $lastUrl = str_replace('%d', $totalPages, $urlPattern);
            $html .= '<li class="page-item"><a class="page-link" href="' . $lastUrl . '">' . $totalPages . '</a></li>';
        }
    }

    // ปุ่ม Next
    if ($currentPage < $totalPages) {
        $nextUrl = str_replace('%d', $currentPage + 1, $urlPattern);
        $html .= '<li class="page-item"><a class="page-link" href="' . $nextUrl . '">Next</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Next</span></li>';
    }

    $html .= '</ul></nav>';
    return $html;
}
?>
