document.addEventListener('DOMContentLoaded', function () {
    lucide.createIcons();
    loadShopInfo();
});

function previewLogo(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('logoPreview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function loadShopInfo() {
    // Initialize Language Tabs
    const languages = [
        { code: 'th', name: 'ไทย', flag: 'th' },
        { code: 'en', name: 'English', flag: 'gb' },
        { code: 'de', name: 'Deutsch', flag: 'de' },
        { code: 'fr', name: 'Français', flag: 'fr' },
        { code: 'cn', name: '中文', flag: 'cn' },
        { code: 'kr', name: '한국어', flag: 'kr' }
    ];

    const tabContainer = document.getElementById('langTabs');
    const contentContainer = document.getElementById('langTabContent');

    languages.forEach((lang, index) => {
        const isActive = index === 0 ? 'active' : '';

        // Create Tab
        const tabLi = document.createElement('li');
        tabLi.className = 'nav-item';
        tabLi.innerHTML = `
            <button class="nav-link ${isActive}" id="tab-${lang.code}" data-bs-toggle="tab" data-bs-target="#content-${lang.code}" type="button" role="tab">
                <img src="https://flagcdn.com/w20/${lang.flag}.png" class="me-1" style="width:20px;"> ${lang.name}
            </button>
        `;
        tabContainer.appendChild(tabLi);

        // Create Content
        const contentDiv = document.createElement('div');
        contentDiv.className = `tab-pane fade ${isActive ? 'show active' : ''}`;
        contentDiv.id = `content-${lang.code}`;
        contentDiv.innerHTML = `
            <div class="row g-3">
                <div class="col-12">
                    <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                        <i data-lucide="map-pin" class="me-2" style="width:16px;"></i>ที่อยู่ผู้ส่ง (Sender Address)
                    </h6>
                    <div class="mb-3">
                        <label class="form-label">ชื่อร้าน / ชื่อผู้ส่ง (${lang.name})</label>
                        <input type="text" class="form-control" name="shop_name_${lang.code}" placeholder="ระบุชื่อร้าน">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ที่อยู่ (${lang.name})</label>
                        <textarea class="form-control" name="shop_address_${lang.code}" rows="3" placeholder="ระบุที่อยู่"></textarea>
                    </div>
                </div>
                
                <div class="col-12 mt-4">
                    <h6 class="fw-bold text-success border-bottom pb-2 mb-3">
                        <i data-lucide="file-text" class="me-2" style="width:16px;"></i>ที่อยู่ออกบิล (Billing Address)
                    </h6>
                    <div class="mb-3">
                        <label class="form-label">ชื่อบริษัท / ชื่อทางการ (${lang.name})</label>
                        <input type="text" class="form-control" name="official_name_${lang.code}" placeholder="ระบุชื่อบริษัท">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ที่อยู่บริษัท (${lang.name})</label>
                        <textarea class="form-control" name="official_address_${lang.code}" rows="3" placeholder="ระบุที่อยู่บริษัท"></textarea>
                    </div>
                </div>
            </div>
        `;
        contentContainer.appendChild(contentDiv);
    });

    lucide.createIcons();

    // Fetch Data
    fetch(`${ADMIN_URL}/api/get_shop_info.php`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const shop = data.shop;
                const translations = data.translations;

                // Set General Info
                document.getElementById('shop_phone').value = shop.shop_phone;
                document.getElementById('shop_email').value = shop.shop_email;
                document.getElementById('shop_tax_id').value = shop.shop_tax_id;
                if (shop.shop_logo) {
                    document.getElementById('logoPreview').src = `${ROOT_URL}/uploads/shop/${shop.shop_logo}`;
                }

                // Set Translations
                languages.forEach(lang => {
                    if (translations[lang.code]) {
                        const t = translations[lang.code];
                        document.querySelector(`[name="shop_name_${lang.code}"]`).value = t.shop_name || '';
                        document.querySelector(`[name="shop_address_${lang.code}"]`).value = t.shop_address || '';
                        document.querySelector(`[name="official_name_${lang.code}"]`).value = t.official_name || '';
                        document.querySelector(`[name="official_address_${lang.code}"]`).value = t.official_address || '';
                    }
                });
            }
        })
        .catch(error => console.error('Error:', error));
}

// Handle Form Submit
document.getElementById('shopSettingsForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);

    Swal.fire({
        title: 'กำลังบันทึก...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    fetch(`${ADMIN_URL}/api/save_shop_info.php`, {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'บันทึกเรียบร้อย',
                    showConfirmButton: false,
                    timer: 1500
                });
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'เกิดข้อผิดพลาดในการบันทึก', 'error');
        });
});
