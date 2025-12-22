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
    // Initialize Language Tabs - Perfect 10 Languages
    const languages = [
        { code: 'th', name: 'ไทย', flag: 'th', rtl: false },
        { code: 'en', name: 'English', flag: 'gb', rtl: false },
        { code: 'de', name: 'Deutsch', flag: 'de', rtl: false },
        { code: 'fr', name: 'Français', flag: 'fr', rtl: false },
        { code: 'zh', name: '中文', flag: 'cn', rtl: false },
        { code: 'ko', name: '한국어', flag: 'kr', rtl: false },
        { code: 'ja', name: '日本語', flag: 'jp', rtl: false },
        { code: 'ru', name: 'Русский', flag: 'ru', rtl: false },
        { code: 'ar', name: 'العربية', flag: 'ae', rtl: true },
        { code: 'he', name: 'עברית', flag: 'il', rtl: true }
    ];

    const languageMenu = document.getElementById('languageMenu');
    const contentContainer = document.getElementById('langTabContent');

    // Create dropdown items and content panes
    languages.forEach((lang, index) => {
        const isActive = index === 0;
        const dirAttr = lang.rtl ? 'dir="rtl"' : '';

        // Create dropdown item (using button instead of <a> to avoid pre-loader)
        const menuItem = document.createElement('li');
        menuItem.innerHTML = `
            <button class="dropdown-item d-flex align-items-center ${isActive ? 'active' : ''}" type="button" data-lang="${lang.code}" data-flag="${lang.flag}" data-name="${lang.name}">
                <img src="https://flagcdn.com/w20/${lang.flag}.png" class="me-2" style="width:20px;">
                ${lang.name}
                ${isActive ? '<i data-lucide="check" class="ms-auto" style="width:16px;"></i>' : ''}
            </button>
        `;
        languageMenu.appendChild(menuItem);

        // Create content pane
        const contentDiv = document.createElement('div');
        contentDiv.className = index === 0 ? '' : 'd-none';
        contentDiv.id = `lang-${lang.code}`;
        contentDiv.dataset.lang = lang.code;
        contentDiv.innerHTML = `
            <div class="row g-4">
                <div class="col-12">
                    <div class="bg-light border-start border-primary border-4 p-3 rounded mb-4">
                        <h6 class="fw-bold text-primary mb-0">
                            <i data-lucide="map-pin" class="me-2" style="width:18px;"></i>ที่อยู่ผู้ส่ง (Sender Address)
                        </h6>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">ชื่อร้าน / ชื่อผู้ส่ง</label>
                        <input type="text" class="form-control form-control-lg" name="shop_name_${lang.code}" placeholder="ระบุชื่อร้าน (${lang.name})" ${dirAttr}>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">ที่อยู่</label>
                        <textarea class="form-control" name="shop_address_${lang.code}" rows="3" placeholder="ระบุที่อยู่ (${lang.name})" ${dirAttr}></textarea>
                    </div>
                </div>
                
                <div class="col-12">
                    <div class="bg-light border-start border-success border-4 p-3 rounded mb-4">
                        <h6 class="fw-bold text-success mb-0">
                            <i data-lucide="file-text" class="me-2" style="width:18px;"></i>ที่อยู่ออกบิล (Billing Address)
                        </h6>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">ชื่อบริษัท / ชื่อทางการ</label>
                        <input type="text" class="form-control form-control-lg" name="official_name_${lang.code}" placeholder="ระบุชื่อบริษัท (${lang.name})" ${dirAttr}>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">ที่อยู่บริษัท</label>
                        <textarea class="form-control" name="official_address_${lang.code}" rows="3" placeholder="ระบุที่อยู่บริษัท (${lang.name})" ${dirAttr}></textarea>
                    </div>
                </div>
            </div>
        `;
        contentContainer.appendChild(contentDiv);
    });

    // Handle language selection
    languageMenu.addEventListener('click', function (e) {
        const item = e.target.closest('[data-lang]');
        if (!item) return;

        const langCode = item.dataset.lang;
        const langFlag = item.dataset.flag;
        const langName = item.dataset.name;

        // Save to localStorage
        try {
            localStorage.setItem('yakpho_last_selected_language', langCode);
            console.log('✅ Saved language:', langCode);
        } catch (err) {
            console.warn('⚠️ Cannot save to localStorage:', err);
        }

        // Update dropdown button
        document.getElementById('currentFlag').src = `https://flagcdn.com/w20/${langFlag}.png`;
        document.getElementById('currentLangName').textContent = langName;

        // Update active menu item
        languageMenu.querySelectorAll('.dropdown-item').forEach(el => {
            el.classList.remove('active');
            const checkIcon = el.querySelector('[data-lucide="check"]');
            if (checkIcon) checkIcon.remove();
        });
        item.classList.add('active');
        item.insertAdjacentHTML('beforeend', '<i data-lucide="check" class="ms-auto" style="width:16px;"></i>');

        // Show selected content pane
        contentContainer.querySelectorAll('[data-lang]').forEach(pane => {
            pane.classList.add('d-none');
        });
        document.getElementById(`lang-${langCode}`).classList.remove('d-none');

        lucide.createIcons();
    });

    // Restore last selected language from localStorage
    try {
        const lastLang = localStorage.getItem('yakpho_last_selected_language');
        if (lastLang) {
            const lastLangItem = languageMenu.querySelector(`[data-lang="${lastLang}"]`);
            if (lastLangItem) {
                // Trigger selection programmatically
                const langFlag = lastLangItem.dataset.flag;
                const langName = lastLangItem.dataset.name;

                document.getElementById('currentFlag').src = `https://flagcdn.com/w20/${langFlag}.png`;
                document.getElementById('currentLangName').textContent = langName;

                languageMenu.querySelectorAll('.dropdown-item').forEach(el => {
                    el.classList.remove('active');
                    const checkIcon = el.querySelector('[data-lucide="check"]');
                    if (checkIcon) checkIcon.remove();
                });
                lastLangItem.classList.add('active');
                lastLangItem.insertAdjacentHTML('beforeend', '<i data-lucide="check" class="ms-auto" style="width:16px;"></i>');

                contentContainer.querySelectorAll('[data-lang]').forEach(pane => {
                    pane.classList.add('d-none');
                });
                document.getElementById(`lang-${lastLang}`).classList.remove('d-none');

                console.log(`🎯 Restored last selected language: ${lastLang}`);
            } else {
                console.log(`⚠️ Last selected language "${lastLang}" not found, using default`);
            }
        }
    } catch (err) {
        console.warn('⚠️ Cannot read from localStorage:', err);
    }

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
                        const nameInput = document.querySelector(`[name="shop_name_${lang.code}"]`);
                        const addressInput = document.querySelector(`[name="shop_address_${lang.code}"]`);
                        const officialNameInput = document.querySelector(`[name="official_name_${lang.code}"]`);
                        const officialAddressInput = document.querySelector(`[name="official_address_${lang.code}"]`);

                        if (nameInput) nameInput.value = t.shop_name || '';
                        if (addressInput) addressInput.value = t.shop_address || '';
                        if (officialNameInput) officialNameInput.value = t.official_name || '';
                        if (officialAddressInput) officialAddressInput.value = t.official_address || '';
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
