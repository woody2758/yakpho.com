/**
 * ============================================================
 * Esther Aroma & Yak Pho — Master Frontend Controller (anona-esther.js)
 * ============================================================
 * Standards:
 * - Zero native alert()/confirm()/prompt() — SweetAlert2 (Swal.fire) ONLY
 * - Zero inline style mutations — uses CSS classes (.is-open, .is-active, .d-none)
 * - Async fetch() with button disable + spinner lock and finally{} unlock
 */

(function () {
    'use strict';

    const APP_CONFIG = {
        baseUrl: document.body.dataset.baseUrl || '',
        lang: document.documentElement.lang || 'th',
    };

    const I18N = {
        th: {
            loading: 'กำลังประมวลผล...',
            addedTitle: 'เพิ่มลงตะกร้าแล้ว',
            errorTitle: 'แจ้งเตือน',
            emptyCart: 'ยังไม่มีสินค้าในตะกร้า',
            wholesaleBadge: 'เรทราคาส่ง 1 กก.',
            removeText: 'ลบออก',
            orderSuccessTitle: 'สั่งซื้อสำเร็จ!',
            viewTrackBtn: 'ติดตามสถานะพัสดุ / แจ้งโอนเงิน',
            viewReceiptBtn: 'ดูใบเสร็จรับเงิน',
            copiedTitle: 'คัดลอกรายการสั่งซื้อแล้ว',
            copiedText: 'คุณสามารถวาง (Paste) ส่งให้แอดมินทาง LINE @yakpho ได้ทันที',
        },
        en: {
            loading: 'Processing...',
            addedTitle: 'Added to Cart',
            errorTitle: 'Notice',
            emptyCart: 'Your shopping cart is currently empty',
            wholesaleBadge: '1kg Wholesale Tier',
            removeText: 'Remove',
            orderSuccessTitle: 'Order Placed Successfully!',
            viewTrackBtn: 'Track Order / Upload Slip',
            viewReceiptBtn: 'View Receipt',
            copiedTitle: 'Order Summary Copied',
            copiedText: 'You can now paste and send this summary via LINE @yakpho or WhatsApp.',
        }
    };

    function t(key) {
        const dict = I18N[APP_CONFIG.lang] || I18N.en;
        return dict[key] || I18N.en[key] || key;
    }

    function formatNumber(num) {
        return Number(num || 0).toLocaleString('en-US', { maximumFractionDigits: 0 });
    }

    /* ------------------------------------------------------------
       1. THEME SWITCHER (LIGHT = ANONA, DARK = ESTHER EMERALD)
       ------------------------------------------------------------ */
    function initThemeSwitcher() {
        const savedTheme = localStorage.getItem('yakpho_theme') || 'light';
        applyTheme(savedTheme);

        document.querySelectorAll('[data-action="toggle-theme"]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const current = document.documentElement.getAttribute('data-theme') || 'light';
                const next = current === 'dark' ? 'light' : 'dark';
                localStorage.setItem('yakpho_theme', next);
                applyTheme(next);
            });
        });
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        document.querySelectorAll('[data-theme-label]').forEach((el) => {
            if (APP_CONFIG.lang === 'th') {
                el.textContent = theme === 'dark' ? 'โหมดสว่าง' : 'โหมดมืด';
            } else {
                el.textContent = theme === 'dark' ? 'Light' : 'Emerald';
            }
        });
    }

    /* ------------------------------------------------------------
       2. HEADER SCROLL, MOBILE DRAWER, LANGUAGE DROPDOWN & BACK TO TOP
       ------------------------------------------------------------ */
    function initNavigationAndDrawers() {
        const header = document.getElementById('siteHeader');
        const backToTop = document.getElementById('backToTopBtn');
        const backdrop = document.getElementById('drawerBackdrop');
        const mobileDrawer = document.getElementById('mobileNavDrawer');
        const cartDrawer = document.getElementById('cartSideDrawer');
        const langWrap = document.getElementById('langDropdownWrap');

        window.addEventListener('scroll', () => {
            const y = window.scrollY || window.pageYOffset;
            if (header) {
                header.classList.toggle('is-scrolled', y > 20);
            }
            if (backToTop) {
                backToTop.classList.toggle('is-visible', y > 360);
            }
        }, { passive: true });

        if (backToTop) {
            backToTop.addEventListener('click', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }

        function closeAllDrawers() {
            if (mobileDrawer) mobileDrawer.classList.remove('is-open');
            if (cartDrawer) cartDrawer.classList.remove('is-open');
            if (backdrop) backdrop.classList.remove('is-open');
            document.body.classList.remove('lock-scroll');
        }

        document.querySelectorAll('[data-action="open-mobile-drawer"]').forEach((btn) => {
            btn.addEventListener('click', () => {
                closeAllDrawers();
                if (mobileDrawer) mobileDrawer.classList.add('is-open');
                if (backdrop) backdrop.classList.add('is-open');
                document.body.classList.add('lock-scroll');
            });
        });

        document.querySelectorAll('[data-action="open-cart-drawer"]').forEach((btn) => {
            btn.addEventListener('click', () => {
                closeAllDrawers();
                if (cartDrawer) cartDrawer.classList.add('is-open');
                if (backdrop) backdrop.classList.add('is-open');
                document.body.classList.add('lock-scroll');
            });
        });

        document.querySelectorAll('[data-action="close-drawers"]').forEach((btn) => {
            btn.addEventListener('click', closeAllDrawers);
        });

        if (backdrop) {
            backdrop.addEventListener('click', closeAllDrawers);
        }

        // Mobile Accordion Submenus
        document.querySelectorAll('[data-action="toggle-mobile-submenu"]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('data-target');
                const sub = document.getElementById(targetId);
                if (sub) {
                    sub.classList.toggle('is-open');
                }
            });
        });

        // Language Dropdown
        if (langWrap) {
            const trigger = langWrap.querySelector('[data-action="toggle-lang-menu"]');
            if (trigger) {
                trigger.addEventListener('click', (e) => {
                    e.stopPropagation();
                    langWrap.classList.toggle('is-open');
                });
            }
            document.addEventListener('click', () => {
                langWrap.classList.remove('is-open');
            });
        }

        // FAQ Accordions
        document.querySelectorAll('[data-action="toggle-faq"]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const box = btn.closest('.faq-item-box');
                if (box) {
                    box.classList.toggle('is-open');
                }
            });
        });
    }

    /* ------------------------------------------------------------
       3. SHOPPING CART API & DRAWER RENDERER
       ------------------------------------------------------------ */
    async function sendCartRequest(payload, triggerBtn = null) {
        let originalHtml = '';
        if (triggerBtn) {
            originalHtml = triggerBtn.innerHTML;
            triggerBtn.disabled = true;
            triggerBtn.classList.add('is-loading');
        }

        try {
            const response = await fetch(`${APP_CONFIG.baseUrl}/api/cart.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(Object.assign({ lang: APP_CONFIG.lang }, payload)),
            });
            const result = await response.json();

            if (!response.ok || result.status !== true) {
                throw new Error(result.message || 'Cart operation failed');
            }

            renderCartUI(result.data);
            return result;
        } catch (err) {
            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: t('errorTitle'),
                    text: err.message,
                    confirmButtonColor: '#0B3B35',
                });
            }
            return null;
        } finally {
            if (triggerBtn) {
                triggerBtn.disabled = false;
                triggerBtn.classList.remove('is-loading');
                triggerBtn.innerHTML = originalHtml;
            }
        }
    }

    function renderCartUI(cart) {
        if (!cart) return;

        // Update header badges
        document.querySelectorAll('[data-cart-badge]').forEach((badge) => {
            badge.textContent = cart.total_qty || 0;
        });

        // Update tier banner
        const tierRateEl = document.getElementById('cartDrawerTierRate');
        if (tierRateEl) {
            tierRateEl.textContent = `฿${formatNumber(cart.wholesale_kg_rate)}`;
        }

        const listEl = document.getElementById('cartDrawerItemsList');
        if (listEl) {
            if (!cart.items || cart.items.length === 0) {
                listEl.innerHTML = `<div class="text-center section-pad-sm text-muted-sm">${t('emptyCart')}</div>`;
            } else {
                listEl.innerHTML = cart.items.map((item) => `
                    <div class="cart-item-row">
                        <img src="${item.image}" alt="${item.product_name}" class="cart-item-thumb">
                        <div class="cart-item-info">
                            <h4>${item.product_name}</h4>
                            <div class="cart-item-meta">
                                ฿${formatNumber(item.unit_price)} ${item.is_wholesale_1kg ? `(${t('wholesaleBadge')})` : ''}
                            </div>
                            <div class="cart-qty-control">
                                <button type="button" class="cart-qty-btn" data-cart-update="${item.product_id}" data-qty="${item.qty - 1}">-</button>
                                <span class="cart-qty-val">${item.qty}</span>
                                <button type="button" class="cart-qty-btn" data-cart-update="${item.product_id}" data-qty="${item.qty + 1}">+</button>
                            </div>
                        </div>
                        <div class="cart-item-price-col">
                            <span class="cart-item-total">฿${formatNumber(item.line_total)}</span>
                            <button type="button" class="cart-item-remove" data-cart-remove="${item.product_id}">${t('removeText')}</button>
                        </div>
                    </div>
                `).join('');
            }
        }

        const subEl = document.getElementById('cartDrawerSubtotal');
        const savRow = document.getElementById('cartDrawerSavingsRow');
        const savEl = document.getElementById('cartDrawerSavings');
        const grandEl = document.getElementById('cartDrawerGrandTotal');

        if (subEl) subEl.textContent = `฿${formatNumber(cart.subtotal)}`;
        if (savRow && savEl) {
            savRow.classList.toggle('d-none', !(cart.wholesale_savings > 0));
            savEl.textContent = `-฿${formatNumber(cart.wholesale_savings)}`;
        }
        if (grandEl) grandEl.textContent = `฿${formatNumber(cart.grand_total)}`;
    }

    function triggerFlyingCart(triggerBtn) {
        if (!triggerBtn) return;
        const cartTarget = document.querySelector('[data-action="open-cart"]');
        if (!cartTarget) return;

        // Find closest product card or image
        const card = triggerBtn.closest('.product-card-anona') || triggerBtn.closest('.bestseller-row') || triggerBtn.closest('.wholesale-order-matrix');
        const imgEl = card ? card.querySelector('img') : null;
        const imgSrc = imgEl ? imgEl.src : (APP_CONFIG.baseUrl + '/catalog/assets/img/YakPhoAromaLogo.webp');

        const startRect = triggerBtn.getBoundingClientRect();
        const endRect = cartTarget.getBoundingClientRect();

        const flyingEl = document.createElement('img');
        flyingEl.src = imgSrc;
        flyingEl.className = 'flying-cart-ghost';
        flyingEl.style.width = '55px';
        flyingEl.style.height = '55px';
        flyingEl.style.left = `${startRect.left + startRect.width / 2 - 27}px`;
        flyingEl.style.top = `${startRect.top + startRect.height / 2 - 27}px`;

        document.body.appendChild(flyingEl);

        // Force reflow and animate
        requestAnimationFrame(() => {
            flyingEl.style.transform = 'scale(0.3) rotate(360deg)';
            flyingEl.style.left = `${endRect.left + endRect.width / 2 - 15}px`;
            flyingEl.style.top = `${endRect.top + endRect.height / 2 - 15}px`;
            flyingEl.style.opacity = '0.2';
        });

        setTimeout(() => {
            flyingEl.remove();
            cartTarget.classList.add('cart-icon-bounce');
            setTimeout(() => cartTarget.classList.remove('cart-icon-bounce'), 500);
        }, 750);
    }

    function initCartActions() {
        // Delegated click listener for Add to Cart, Update Qty, Remove Item
        document.addEventListener('click', async (e) => {
            const addBtn = e.target.closest('[data-add-to-cart]');
            if (addBtn) {
                e.preventDefault();
                const productId = parseInt(addBtn.getAttribute('data-add-to-cart'), 10);
                const qtyInputId = addBtn.getAttribute('data-qty-input');
                let qty = 1;
                if (qtyInputId) {
                    const inputEl = document.getElementById(qtyInputId);
                    if (inputEl) qty = Math.max(1, parseInt(inputEl.value, 10) || 1);
                }

                triggerFlyingCart(addBtn);
                const res = await sendCartRequest({ action: 'add', product_id: productId, qty: qty }, addBtn);
                if (res && window.Swal) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: res.message || t('addedTitle'),
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                    });
                }
                const cartDrawer = document.getElementById('cartSideDrawer');
                const backdrop = document.getElementById('drawerBackdrop');
                if (res && cartDrawer && backdrop) {
                    cartDrawer.classList.add('is-open');
                    backdrop.classList.add('is-open');
                    document.body.classList.add('lock-scroll');
                }
                return;
            }

            const updBtn = e.target.closest('[data-cart-update]');
            if (updBtn) {
                e.preventDefault();
                const pid = parseInt(updBtn.getAttribute('data-cart-update'), 10);
                const newQty = parseInt(updBtn.getAttribute('data-qty'), 10);
                await sendCartRequest({ action: 'update', product_id: pid, qty: newQty }, updBtn);
                return;
            }

            const remBtn = e.target.closest('[data-cart-remove]');
            if (remBtn) {
                e.preventDefault();
                const pid = parseInt(remBtn.getAttribute('data-cart-remove'), 10);
                await sendCartRequest({ action: 'remove', product_id: pid }, remBtn);
            }
        });

        // Product Detail Page Quantity Stepper & Gallery Thumbs
        document.querySelectorAll('[data-detail-qty-step]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const step = parseInt(btn.getAttribute('data-detail-qty-step'), 10);
                const input = document.getElementById('detailQtyInput');
                if (input) {
                    const cur = parseInt(input.value, 10) || 1;
                    input.value = Math.max(1, Math.min(999, cur + step));
                }
            });
        });

        document.querySelectorAll('[data-gallery-src]').forEach((thumbBtn) => {
            thumbBtn.addEventListener('click', () => {
                const src = thumbBtn.getAttribute('data-gallery-src');
                const mainImg = document.getElementById('productMainImage');
                if (mainImg && src) {
                    mainImg.src = src;
                }
                document.querySelectorAll('[data-gallery-src]').forEach((b) => b.classList.remove('is-active'));
                thumbBtn.classList.add('is-active');
            });
        });
    }

    /* ------------------------------------------------------------
       4. INTERACTIVE B2B WHOLESALE MATRIX (12 SCENTS x 3 FORMULAS)
       ------------------------------------------------------------ */
    function initWholesaleMatrix() {
        const matrixForm = document.getElementById('wholesaleMatrixWrapper');
        if (!matrixForm) return;

        const inputs = matrixForm.querySelectorAll('.matrix-qty-input');

        function calculateRate(totalKg) {
            if (totalKg >= 100) return 470;
            if (totalKg >= 50) return 490;
            if (totalKg >= 30) return 500;
            if (totalKg >= 20) return 530;
            if (totalKg >= 10) return 560;
            if (totalKg >= 6) return 590;
            return 690;
        }

        function recalcMatrix() {
            let totalKg = 0;
            inputs.forEach((inp) => {
                const v = Math.max(0, parseInt(inp.value, 10) || 0);
                totalKg += v;
            });

            const rate = calculateRate(totalKg);
            const totalBaht = totalKg * rate;
            const savedBaht = totalKg * (690 - rate);

            const totalKgEl = document.getElementById('matrixTotalKg');
            const rateEl = document.getElementById('matrixCurrentRate');
            const totalPriceEl = document.getElementById('matrixTotalPrice');
            const savedEl = document.getElementById('matrixTotalSaved');

            if (totalKgEl) totalKgEl.textContent = `${formatNumber(totalKg)} kg`;
            if (rateEl) rateEl.textContent = `฿${formatNumber(rate)} / kg`;
            if (totalPriceEl) totalPriceEl.textContent = `฿${formatNumber(totalBaht)}`;
            if (savedEl) savedEl.textContent = `฿${formatNumber(savedBaht)}`;

            // Highlight active tier box
            document.querySelectorAll('[data-tier-min]').forEach((box) => {
                const min = parseInt(box.getAttribute('data-tier-min'), 10);
                const max = parseInt(box.getAttribute('data-tier-max'), 10);
                box.classList.toggle('is-active-tier', totalKg >= min && totalKg <= max);
            });

            const stickyBar = document.getElementById('wholesaleStickyFooter');
            if (stickyBar) {
                stickyBar.classList.toggle('is-active-bar', totalKg > 0);
            }
        }

        matrixForm.addEventListener('click', (e) => {
            const stepBtn = e.target.closest('[data-matrix-step]');
            if (stepBtn) {
                const delta = parseInt(stepBtn.getAttribute('data-matrix-step'), 10);
                const wrap = stepBtn.closest('.matrix-qty-box');
                const input = wrap ? wrap.querySelector('.matrix-qty-input') : null;
                if (input) {
                    const cur = parseInt(input.value, 10) || 0;
                    input.value = Math.max(0, cur + delta);
                    recalcMatrix();
                }
            }
        });

        inputs.forEach((inp) => {
            inp.addEventListener('input', recalcMatrix);
        });

        // Bulk Add to Cart Button
        const bulkAddBtn = document.getElementById('btnMatrixAddToCart');
        if (bulkAddBtn) {
            bulkAddBtn.addEventListener('click', async () => {
                const items = [];
                inputs.forEach((inp) => {
                    const pid = parseInt(inp.getAttribute('data-product-id'), 10);
                    const qty = parseInt(inp.value, 10) || 0;
                    if (pid > 0 && qty > 0) {
                        items.push({ product_id: pid, qty: qty });
                    }
                });

                const res = await sendCartRequest({ action: 'add_bulk', items: items }, bulkAddBtn);
                if (res && window.Swal) {
                    Swal.fire({
                        icon: 'success',
                        title: t('addedTitle'),
                        text: res.message,
                        confirmButtonColor: '#0B3B35',
                    });
                }
                const cartDrawer = document.getElementById('cartSideDrawer');
                const backdrop = document.getElementById('drawerBackdrop');
                if (res && cartDrawer && backdrop) {
                    cartDrawer.classList.add('is-open');
                    backdrop.classList.add('is-open');
                    document.body.classList.add('lock-scroll');
                }
            });
        }

        // Copy Summary for LINE / WhatsApp Button
        const copyBtn = document.getElementById('btnMatrixCopySummary');
        if (copyBtn) {
            copyBtn.addEventListener('click', async () => {
                const lines = ['[YakPho Aroma - B2B Wholesale Order]'];
                let totalKg = 0;
                inputs.forEach((inp) => {
                    const qty = parseInt(inp.value, 10) || 0;
                    const code = inp.getAttribute('data-code') || '';
                    const scent = inp.getAttribute('data-scent') || '';
                    if (qty > 0) {
                        lines.push(`- ${code} (${scent}): ${qty} kg`);
                        totalKg += qty;
                    }
                });

                if (totalKg === 0) {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'warning',
                            title: t('errorTitle'),
                            text: APP_CONFIG.lang === 'th' ? 'กรุณาระบุจำนวนสินค้าอย่างน้อย 1 กก.' : 'Please select at least 1 kg.',
                            confirmButtonColor: '#0B3B35',
                        });
                    }
                    return;
                }

                const rate = calculateRate(totalKg);
                lines.push(`------------------------`);
                lines.push(`Total Volume: ${totalKg} kg`);
                lines.push(`Wholesale Rate: ${rate} THB/kg`);
                lines.push(`Estimated Total: ${formatNumber(totalKg * rate)} THB`);

                try {
                    await navigator.clipboard.writeText(lines.join('\n'));
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: t('copiedTitle'),
                            text: t('copiedText'),
                            confirmButtonColor: '#0B3B35',
                        });
                    }
                } catch (ignore) {}
            });
        }

        recalcMatrix();
    }

    /* ------------------------------------------------------------
       5. CHECKOUT FORM SUBMISSION (REAL ORDER CREATION)
       ------------------------------------------------------------ */
    function initCheckoutForm() {
        const checkoutForm = document.getElementById('checkoutOrderForm');
        if (!checkoutForm) return;

        // Payment radio card highlight & COD calculation
        const payRadios = checkoutForm.querySelectorAll('input[name="paycat_id"]');
        payRadios.forEach((radio) => {
            radio.addEventListener('change', () => {
                checkoutForm.querySelectorAll('.payment-option-card').forEach((card) => {
                    card.classList.remove('is-selected');
                });
                const parentCard = radio.closest('.payment-option-card');
                if (parentCard) parentCard.classList.add('is-selected');

                const codNotice = document.getElementById('codFeeNoticeRow');
                if (codNotice) {
                    codNotice.classList.toggle('d-none', parseInt(radio.value, 10) !== 2);
                }
            });
        });

        checkoutForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = checkoutForm.querySelector('button[type="submit"]');
            const originalHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = t('loading');
            }

            try {
                const formData = new FormData(checkoutForm);
                const payload = Object.fromEntries(formData.entries());
                payload.lang = APP_CONFIG.lang;

                const response = await fetch(`${APP_CONFIG.baseUrl}/api/checkout.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });
                const result = await response.json();

                if (!response.ok || result.status !== true) {
                    throw new Error(result.message || 'Failed to place order');
                }

                if (window.Swal) {
                    const modalRes = await Swal.fire({
                        icon: 'success',
                        title: t('orderSuccessTitle'),
                        html: `
                            <p class="mb-2">${result.message}</p>
                            <p class="fw-600 text-emerald">Grand Total: ฿${result.data.grand_total_formatted}</p>
                        `,
                        showCancelButton: true,
                        confirmButtonText: t('viewTrackBtn'),
                        cancelButtonText: t('viewReceiptBtn'),
                        confirmButtonColor: '#0B3B35',
                        cancelButtonColor: '#D1A55A',
                        allowOutsideClick: false,
                    });

                    if (modalRes.isConfirmed) {
                        window.location.href = result.data.track_url;
                    } else {
                        window.location.href = result.data.receipt_url;
                    }
                } else {
                    window.location.href = result.data.track_url;
                }
            } catch (err) {
                if (window.Swal) {
                    Swal.fire({
                        icon: 'error',
                        title: t('errorTitle'),
                        text: err.message,
                        confirmButtonColor: '#0B3B35',
                    });
                }
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHtml;
                }
            }
        });
    }

    /* ------------------------------------------------------------
       6. CONTACT & INQUIRY FORMS + ORDER TRACKING LOOKUP
       ------------------------------------------------------------ */
    function initContactAndTrackForms() {
        // Contact / Corporate Gift Inquiry Form
        document.querySelectorAll('.js-inquiry-form').forEach((form) => {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = form.querySelector('button[type="submit"]');
                const orig = btn ? btn.innerHTML : '';
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = t('loading');
                }

                try {
                    const fd = new FormData(form);
                    const payload = Object.fromEntries(fd.entries());
                    payload.lang = APP_CONFIG.lang;

                    const response = await fetch(`${APP_CONFIG.baseUrl}/api/contact_submit.php`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload),
                    });
                    const result = await response.json();

                    if (!response.ok || result.status !== true) {
                        throw new Error(result.message || 'Submission failed');
                    }

                    form.reset();
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: result.message,
                            confirmButtonColor: '#0B3B35',
                        });
                    }
                } catch (err) {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'error',
                            title: t('errorTitle'),
                            text: err.message,
                            confirmButtonColor: '#0B3B35',
                        });
                    }
                } finally {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = orig;
                    }
                }
            });
        });

        // Order Tracking Lookup Form
        const trackForm = document.getElementById('orderTrackLookupForm');
        if (trackForm) {
            trackForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = trackForm.querySelector('button[type="submit"]');
                const input = trackForm.querySelector('input[name="q"]');
                const q = input ? input.value.trim() : '';
                const orig = btn ? btn.innerHTML : '';

                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = t('loading');
                }

                try {
                    const response = await fetch(`${APP_CONFIG.baseUrl}/api/track_lookup.php?q=${encodeURIComponent(q)}&lang=${APP_CONFIG.lang}`);
                    const result = await response.json();

                    if (!response.ok || result.status !== true) {
                        throw new Error(result.message || 'Order not found');
                    }

                    window.location.href = result.data.track_url;
                } catch (err) {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'warning',
                            title: t('errorTitle'),
                            text: err.message,
                            confirmButtonColor: '#0B3B35',
                        });
                    }
                } finally {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = orig;
                    }
                }
            });
        }
    }

    /* ------------------------------------------------------------
       7. INITIALIZE ON DOM READY
       ------------------------------------------------------------ */
    document.addEventListener('DOMContentLoaded', () => {
        initThemeSwitcher();
        initNavigationAndDrawers();
        initCartActions();
        initWholesaleMatrix();
        initCheckoutForm();
        initContactAndTrackForms();

        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
        if (window.AOS && typeof window.AOS.init === 'function') {
            window.AOS.init({ duration: 650, once: true, offset: 40 });
        }
    });
})();

