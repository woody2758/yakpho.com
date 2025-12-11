/**
 * Language Tabs Manager with LocalStorage
 * จัดการ Tab ภาษาและจำภาษาที่เลือกล่าสุด
 * 
 * Features:
 * - จำภาษาที่เลือกล่าสุดด้วย LocalStorage
 * - รองรับหลายภาษา (ไม่จำกัด)
 * - Performance ดี (ไม่ต้อง reload หน้า)
 * - ใช้งานง่าย (Auto-initialize)
 */

(function () {
    'use strict';

    const STORAGE_KEY = 'yakpho_last_selected_language';

    /**
     * บันทึกภาษาที่เลือกล่าสุด
     */
    function saveLastSelectedLanguage(langCode) {
        try {
            localStorage.setItem(STORAGE_KEY, langCode);
            console.log('✅ Saved language:', langCode);
        } catch (e) {
            console.warn('⚠️ Cannot save to localStorage:', e);
        }
    }

    /**
     * ดึงภาษาที่เลือกล่าสุด
     */
    function getLastSelectedLanguage() {
        try {
            return localStorage.getItem(STORAGE_KEY);
        } catch (e) {
            console.warn('⚠️ Cannot read from localStorage:', e);
            return null;
        }
    }

    /**
     * แสดง indicator ว่าภาษาไหนมีข้อมูล
     */
    function updateLanguageIndicators() {
        // หา language tabs ทั้งหมด
        const langTabs = document.querySelectorAll('[data-bs-toggle="tab"][data-bs-target^="#lang-"]');

        langTabs.forEach(tab => {
            const targetId = tab.getAttribute('data-bs-target');
            const tabPane = document.querySelector(targetId);

            if (!tabPane) return;

            // เช็คว่ามีข้อมูลในภาษานี้หรือไม่
            const inputs = tabPane.querySelectorAll('input[type="text"], textarea');
            let hasData = false;

            inputs.forEach(input => {
                if (input.value && input.value.trim() !== '') {
                    hasData = true;
                }
            });

            // เพิ่ม badge ถ้ามีข้อมูล
            const existingBadge = tab.querySelector('.lang-indicator');
            if (hasData && !existingBadge) {
                const badge = document.createElement('span');
                badge.className = 'lang-indicator badge bg-success ms-1';
                badge.style.fontSize = '0.6em';
                badge.textContent = '✓';
                tab.appendChild(badge);
            } else if (!hasData && existingBadge) {
                existingBadge.remove();
            }
        });
    }

    /**
     * เริ่มต้นระบบ Language Tabs
     */
    function initLanguageTabs() {
        // หา language tabs ทั้งหมด
        const langTabs = document.querySelectorAll('[data-bs-toggle="tab"][data-bs-target^="#lang-"]');

        if (langTabs.length === 0) {
            console.log('ℹ️ No language tabs found on this page');
            return;
        }

        console.log(`🌐 Found ${langTabs.length} language tabs`);

        // เพิ่ม event listener สำหรับบันทึกภาษาที่เลือก
        langTabs.forEach(tab => {
            tab.addEventListener('shown.bs.tab', function (e) {
                const targetId = e.target.getAttribute('data-bs-target');
                const langCode = targetId.replace('#lang-', '');
                saveLastSelectedLanguage(langCode);

                // Update indicators
                updateLanguageIndicators();
            });
        });

        // โหลดภาษาที่เลือกล่าสุด
        const lastLang = getLastSelectedLanguage();

        if (lastLang) {
            const targetTab = document.querySelector(`[data-bs-target="#lang-${lastLang}"]`);

            if (targetTab) {
                // ใช้ Bootstrap Tab API
                const tab = new bootstrap.Tab(targetTab);
                tab.show();
                console.log(`🎯 Restored last selected language: ${lastLang}`);
            } else {
                console.log(`⚠️ Last selected language "${lastLang}" not found, using default`);
            }
        }

        // Update indicators เมื่อมีการพิมพ์
        const allInputs = document.querySelectorAll('[id^="lang-"] input, [id^="lang-"] textarea');
        allInputs.forEach(input => {
            input.addEventListener('input', debounce(updateLanguageIndicators, 500));
        });

        // Update indicators ครั้งแรก
        updateLanguageIndicators();
    }

    /**
     * Debounce function เพื่อลด performance impact
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * เพิ่มฟังก์ชันสำหรับ reset language preference
     */
    window.resetLanguagePreference = function () {
        try {
            localStorage.removeItem(STORAGE_KEY);
            console.log('✅ Language preference reset');
            location.reload();
        } catch (e) {
            console.warn('⚠️ Cannot reset language preference:', e);
        }
    };

    // เริ่มต้นเมื่อ DOM พร้อม
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLanguageTabs);
    } else {
        initLanguageTabs();
    }

    console.log('🚀 Language Tabs Manager loaded');
})();
