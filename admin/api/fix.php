<?php
$f = 'd:/xampp/htdocs/esther.rest/admin/assets/js/orders.js';
$c = file_get_contents($f);

$pattern = '/if \(data\.success\) \{\s*Swal\.fire\(\{\s*icon: \'success\',\s*title: \'สำเร็จ\',\s*text: \'อัปเดตสถานะเรียบร้อยแล้ว\',\s*\}\s*\}\s*catch \(error\) \{/su';

$replace = "if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ',
                text: 'อัปเดตสถานะเรียบร้อยแล้ว',
                timer: 1500,
                showConfirmButton: false
            });
            const searchInput = document.getElementById('searchInput');
            const searchVal = searchInput ? searchInput.value : '';
            if (typeof loadOrderTable === 'function' && typeof currentPage !== 'undefined') {
                loadOrderTable(currentPage, searchVal);
            }
            const modalEl = document.getElementById('orderDetailModal');
            if (modalEl) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }
        } else {
            Swal.fire('Error', data.message || 'เกิดข้อผิดพลาด', 'error');
        }
        btn.innerHTML = originalContent;
        btn.disabled = false;
    } catch (error) {";

if (preg_match($pattern, $c)) {
    $c = preg_replace($pattern, $replace, $c);
    file_put_contents($f, $c);
    echo "Done!";
} else {
    echo "Regex not matched!";
}
?>
