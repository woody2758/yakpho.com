</div><!-- container -->

<!-- Language Tabs Manager (with LocalStorage) -->
<script src="<?= ADMIN_ASSETS ?>/js/language-tabs.js<?= $ver ?>"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Summernote JS -->
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs5.min.js"></script>

<script>
// Initialize Lucide icons
lucide.createIcons();

// Global function to update user stars
function updateUserStars(userId, stars) {
    fetch(ADMIN_URL + '/api/update_user_stars.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'user_id=' + userId + '&stars=' + stars
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Find all star containers for this user
            const containers = document.querySelectorAll('[id^="stars-container-"][id$="-' + userId + '"]');
            containers.forEach(container => {
                const icons = container.querySelectorAll('.star-icon');
                icons.forEach((icon, index) => {
                    const isFilled = index < stars;
                    icon.setAttribute('fill', isFilled ? '#ffc107' : 'none');
                    icon.setAttribute('stroke', isFilled ? '#ffc107' : '#adb5bd');
                });
            });
            // Also notify visually (optional)
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true, position: 'top-end', showConfirmButton: false, timer: 1500,
                    icon: 'success', title: 'อัพเดทระดับลูกค้าสำเร็จ'
                });
            }
        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire('ข้อผิดพลาด', data.message || 'ไม่สามารถอัพเดทข้อมูลได้', 'error');
            } else {
                alert('Error: ' + data.message);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
        }
    });
}
</script>
</body>
</html>
