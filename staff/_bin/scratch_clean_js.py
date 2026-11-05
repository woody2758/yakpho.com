import sys
import re

file_path = 'd:/xampp/htdocs/yakpho.com/staff/post_world_groups.php'
with open(file_path, 'r', encoding='utf-8') as f:
    c = f.read()

# Replace the entire script block
new_script = '''<!-- SweetAlert2 -->
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
</script>'''

# regex to replace from <!-- SweetAlert2 --> down to </script> before footer
pattern = re.compile(r'<!-- SweetAlert2 -->.*?</script>\s*(?=<\?php\s+include\s+"components/footer\.php";\s*\?>)', re.DOTALL)

if pattern.search(c):
    c = pattern.sub(new_script, c)
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(c)
    print("Replaced JS successfully.")
else:
    print("Could not find JS block.")
