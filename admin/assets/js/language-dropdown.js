/**
 * Language Dropdown Component
 * Reusable dropdown selector for multi-language forms
 * 
 * Usage:
 * 1. Add container: <div class="language-dropdown-container" data-content-selector="#myContent"></div>
 * 2. Add content panes with data-lang attribute: <div data-lang="th">...</div>
 * 
 * Features:
 * - Auto-detects all language panes
 * - LocalStorage persistence
 * - RTL support for Arabic/Hebrew
 * - Beautiful UI with flags
 */

(function () {
    'use strict';

    const STORAGE_KEY = 'yakpho_last_selected_language';

    // Language configuration - Perfect 10 Languages
    const LANGUAGES = [
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

    /**
     * Initialize language dropdown for a container
     */
    function initLanguageDropdown(container) {
        const contentSelector = container.dataset.contentSelector;
        if (!contentSelector) {
            console.error('❌ language-dropdown-container missing data-content-selector');
            return;
        }

        const contentContainer = document.querySelector(contentSelector);
        if (!contentContainer) {
            console.error('❌ Content container not found:', contentSelector);
            return;
        }

        // Find all language panes
        const panes = contentContainer.querySelectorAll('[data-lang]');
        if (panes.length === 0) {
            console.error('❌ No language panes found with [data-lang] attribute');
            return;
        }

        // Get available languages from panes
        const availableLangs = Array.from(panes).map(pane => pane.dataset.lang);
        const languages = LANGUAGES.filter(lang => availableLangs.includes(lang.code));

        if (languages.length === 0) {
            console.error('❌ No matching languages found');
            return;
        }

        // Get last selected language or default to first
        let selectedLang = languages[0];
        try {
            const lastLang = localStorage.getItem(STORAGE_KEY);
            if (lastLang) {
                const found = languages.find(l => l.code === lastLang);
                if (found) selectedLang = found;
            }
        } catch (e) {
            console.warn('⚠️ Could not read from localStorage');
        }

        // Build dropdown HTML
        container.innerHTML = `
            <div class="dropdown lang-dropdown">
                <button class="btn btn-outline-primary dropdown-toggle d-flex align-items-center" 
                        type="button" 
                        data-bs-toggle="dropdown">
                    <img src="https://flagcdn.com/w20/${selectedLang.flag}.png" 
                         class="me-2 lang-flag" 
                         alt="${selectedLang.name}">
                    <span class="lang-name">${selectedLang.name}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow lang-menu" style="max-height: 400px; overflow-y: auto;">
                    ${languages.map(lang => `
                        <li>
                            <button class="dropdown-item d-flex align-items-center ${lang.code === selectedLang.code ? 'active' : ''}" 
                                    type="button" 
                                    data-lang="${lang.code}" 
                                    data-flag="${lang.flag}" 
                                    data-name="${lang.name}">
                                <img src="https://flagcdn.com/w20/${lang.flag}.png" class="me-2" style="width:20px;">
                                ${lang.name}
                                ${lang.code === selectedLang.code ? '<i data-lucide="check" class="ms-auto" style="width:16px;"></i>' : ''}
                            </button>
                        </li>
                    `).join('')}
                </ul>
            </div>
        `;

        // Show selected pane, hide others
        panes.forEach(pane => {
            pane.style.display = pane.dataset.lang === selectedLang.code ? 'block' : 'none';
        });

        // Handle language selection
        const menu = container.querySelector('.lang-menu');
        const flagImg = container.querySelector('.lang-flag');
        const nameSpan = container.querySelector('.lang-name');

        menu.addEventListener('click', function (e) {
            const item = e.target.closest('[data-lang]');
            if (!item) return;

            const langCode = item.dataset.lang;
            const langFlag = item.dataset.flag;
            const langName = item.dataset.name;

            // Save to localStorage
            try {
                localStorage.setItem(STORAGE_KEY, langCode);
                console.log('✅ Saved language:', langCode);
            } catch (err) {
                console.warn('⚠️ Cannot save to localStorage:', err);
            }

            // Update dropdown button
            flagImg.src = `https://flagcdn.com/w20/${langFlag}.png`;
            flagImg.alt = langName;
            nameSpan.textContent = langName;

            // Update active menu item
            menu.querySelectorAll('.dropdown-item').forEach(el => {
                el.classList.remove('active');
                const checkIcon = el.querySelector('[data-lucide="check"]');
                if (checkIcon) checkIcon.remove();
            });
            item.classList.add('active');
            item.insertAdjacentHTML('beforeend', '<i data-lucide="check" class="ms-auto" style="width:16px;"></i>');

            // Show selected pane, hide others
            panes.forEach(pane => {
                pane.style.display = pane.dataset.lang === langCode ? 'block' : 'none';
            });

            // Re-init Lucide icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });

        // Init Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        console.log(`🌐 Language dropdown initialized with ${languages.length} languages`);
    }

    /**
     * Auto-initialize all language dropdowns on page
     */
    function autoInit() {
        const containers = document.querySelectorAll('.language-dropdown-container');
        containers.forEach(container => {
            initLanguageDropdown(container);
        });
    }

    // Auto-init when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoInit);
    } else {
        autoInit();
    }

    // Expose for manual initialization
    window.LanguageDropdown = {
        init: initLanguageDropdown,
        autoInit: autoInit
    };

    console.log('🚀 Language Dropdown Component loaded');
})();
