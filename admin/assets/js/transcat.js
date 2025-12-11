let transcatModal;

document.addEventListener('DOMContentLoaded', function () {
    transcatModal = new bootstrap.Modal(document.getElementById('transcatModal'));
    loadTranscatTable();
    lucide.createIcons();

    // Search
    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', function (e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadTranscatTable(e.target.value);
        }, 300);
    });
});

function loadTranscatTable(search = '') {
    const container = document.getElementById('tableContainer');
    const currentLang = localStorage.getItem('activeLanguageTab') || 'th';

    fetch(`${ADMIN_URL}/api/get_transcat_table.php?search=${search}&lang=${currentLang}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                container.innerHTML = data.html;
                lucide.createIcons();
            } else {
                container.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            container.innerHTML = '<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
        });
}

function openModal() {
    document.getElementById('transcatForm').reset();
    document.getElementById('transcat_id').value = '';
    document.getElementById('modalTitle').innerText = 'เพิ่มการจัดส่ง';

    // Reset language tabs
    if (window.LanguageTabs) {
        window.LanguageTabs.reset();
    }

    transcatModal.show();
}

function editTranscat(id) {
    fetch(`${ADMIN_URL}/api/get_transcat.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const form = document.getElementById('transcatForm');
                const t = data.transcat;
                const trans = data.translations;

                document.getElementById('transcat_id').value = t.transcat_id;
                document.getElementById('modalTitle').innerText = 'แก้ไขการจัดส่ง';

                // Common fields
                form.elements['transcat_index'].value = t.transcat_index;
                form.elements['transcat_link'].value = t.transcat_link;
                form.elements['transcat_cod'].checked = t.transcat_cod == 1;
                form.elements['transcat_status'].checked = t.transcat_status == 1;

                // Translations
                ['th', 'en'].forEach(lang => {
                    if (trans[lang]) {
                        form.elements[`translations[${lang}][transcat_name]`].value = trans[lang].transcat_name || '';
                        form.elements[`translations[${lang}][transcat_nshort]`].value = trans[lang].transcat_nshort || '';
                        form.elements[`translations[${lang}][transcat_detail]`].value = trans[lang].transcat_detail || '';
                    }
                });

                // Reset language tabs
                if (window.LanguageTabs) {
                    window.LanguageTabs.reset();
                }

                transcatModal.show();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => console.error('Error:', error));
}

function saveTranscat() {
    const form = document.getElementById('transcatForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    const data = {
        transcat_id: formData.get('transcat_id'),
        transcat_index: formData.get('transcat_index'),
        transcat_link: formData.get('transcat_link'),
        transcat_cod: formData.get('transcat_cod') ? 1 : 0,
        transcat_status: formData.get('transcat_status') ? 1 : 0,
        translations: {}
    };

    ['th', 'en'].forEach(lang => {
        data.translations[lang] = {
            transcat_name: formData.get(`translations[${lang}][transcat_name]`),
            transcat_nshort: formData.get(`translations[${lang}][transcat_nshort]`),
            transcat_detail: formData.get(`translations[${lang}][transcat_detail]`)
        };
    });

    fetch(`${ADMIN_URL}/api/save_transcat.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'บันทึกสำเร็จ',
                    showConfirmButton: false,
                    timer: 1500
                });
                transcatModal.hide();
                loadTranscatTable();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'เกิดข้อผิดพลาดในการบันทึก', 'error');
        });
}

function deleteTranscat(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "คุณต้องการลบข้อมูลนี้ใช่หรือไม่?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`${ADMIN_URL}/api/delete_transcat.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: id })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire(
                            'ลบสำเร็จ!',
                            'ข้อมูลถูกลบเรียบร้อยแล้ว',
                            'success'
                        );
                        loadTranscatTable();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
        }
    });
}

function toggleStatus(id, status) {
    // Reuse save API for status toggle
    // First fetch current data to preserve other fields? 
    // Or create a specific toggle API? 
    // For simplicity, let's just use save_transcat but we need all data.
    // Actually, it's better to create a specific toggle endpoint or just fetch-modify-save.
    // Given the complexity, let's just fetch-modify-save for now or assume backend handles partial updates?
    // My save_transcat expects full data. Let's create a quick toggle function that fetches first.

    fetch(`${ADMIN_URL}/api/get_transcat.php?id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const payload = {
                    transcat_id: data.transcat.transcat_id,
                    transcat_index: data.transcat.transcat_index,
                    transcat_link: data.transcat.transcat_link,
                    transcat_cod: data.transcat.transcat_cod,
                    transcat_status: status ? 1 : 0,
                    translations: data.translations
                };

                return fetch(`${ADMIN_URL}/api/save_transcat.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
                Toast.fire({
                    icon: 'success',
                    title: 'อัปเดตสถานะเรียบร้อย'
                });
            } else {
                Swal.fire('Error', 'ไม่สามารถอัปเดตสถานะได้', 'error');
                loadTranscatTable(); // Revert toggle
            }
        });
}
