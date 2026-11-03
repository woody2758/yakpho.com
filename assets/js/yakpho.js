/**
 * ============================================================
 * Yak Pho Aroma & Esther Synergy — App-Style Vanilla JS Core
 * Zero External Dependencies • Blazing Fast • Mobile-First
 * ============================================================
 */

(function () {
    'use strict';

    // Global App State
    const App = {
        baseUrl: document.body.getAttribute('data-base-url') || '',
        lang: document.documentElement.lang || 'th',
        cart: {
            items: [],
            totalQty: 0,
            grandTotal: 0
        }
    };

    // ============================================================
    // 1. Toast Notification System (Zero CDN Bloat)
    // ============================================================
    function showToast(message, type = 'success') {
        let container = document.getElementById('ypToastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'ypToastContainer';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `yp-toast ${type}`;
        const icon = type === 'success' 
            ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>'
            : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';

        toast.innerHTML = `${icon}<span>${message}</span>`;
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px) scale(0.95)';
            setTimeout(() => toast.remove(), 250);
        }, 2800);
    }
    window.showToast = showToast;

    // ============================================================
    // 1.1 Flying To Cart Animation
    // ============================================================
    function animateFlyToCart(originEl, onComplete) {
        const targetCart = document.querySelector('.app-header [data-action="open-cart"]') ||
                           document.querySelector('.product-sticky-bottom-bar [data-action="open-cart"]') ||
                           document.querySelector('.bottom-nav-dock [data-action="open-cart"]');

        if (!originEl || !targetCart) {
            if (typeof onComplete === 'function') onComplete();
            return;
        }

        // Find the image from product card or product detail
        let imgEl = null;
        if (originEl.tagName === 'IMG') {
            imgEl = originEl;
        } else {
            const card = originEl.closest('.product-item-card') || originEl.closest('.detail-info-card')?.parentElement || document.querySelector('.detail-gallery-wrap');
            imgEl = card ? (card.querySelector('.product-thumb-img') || card.querySelector('.detail-gallery-img') || card.querySelector('img')) : null;
        }

        const startRect = imgEl ? imgEl.getBoundingClientRect() : originEl.getBoundingClientRect();
        const endRect = targetCart.getBoundingClientRect();

        const flyEl = document.createElement('img');
        flyEl.src = imgEl ? imgEl.src : (App.baseUrl + '/catalog/assets/img/YakPhoAromaLogo.webp');
        flyEl.className = 'flying-cart-item';

        const startSize = Math.min(Math.max(startRect.width || 60, 50), 75);
        const startTop = startRect.top + ((startRect.height || startSize) - startSize) / 2;
        const startLeft = startRect.left + ((startRect.width || startSize) - startSize) / 2;

        flyEl.style.width = `${startSize}px`;
        flyEl.style.height = `${startSize}px`;
        flyEl.style.top = `${startTop}px`;
        flyEl.style.left = `${startLeft}px`;
        flyEl.style.opacity = '1';

        document.body.appendChild(flyEl);

        // Force reflow
        flyEl.getBoundingClientRect();

        const endSize = 22;
        const endTop = endRect.top + (endRect.height - endSize) / 2;
        const endLeft = endRect.left + (endRect.width - endSize) / 2;

        requestAnimationFrame(() => {
            flyEl.style.top = `${endTop}px`;
            flyEl.style.left = `${endLeft}px`;
            flyEl.style.width = `${endSize}px`;
            flyEl.style.height = `${endSize}px`;
            flyEl.style.opacity = '0.3';
            flyEl.style.transform = 'scale(0.35) rotate(35deg)';
        });

        setTimeout(() => {
            flyEl.remove();

            // Cart bounce animation
            targetCart.classList.add('cart-bounce-anim');
            setTimeout(() => targetCart.classList.remove('cart-bounce-anim'), 450);

            if (typeof onComplete === 'function') {
                onComplete();
            }
        }, 720);
    }

    // ============================================================
    // 2. Shopping Cart Client & Slide-Up Drawer
    // ============================================================
    const CartManager = {
        init() {
            this.bindEvents();
            this.refreshCart(false);
        },

        bindEvents() {
            // Open Cart Drawer
            document.querySelectorAll('[data-action="open-cart"]').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.openDrawer();
                });
            });

            // Close Cart Drawer
            const overlay = document.getElementById('cartDrawerOverlay');
            if (overlay) {
                overlay.addEventListener('click', (e) => {
                    if (e.target === overlay || e.target.closest('[data-action="close-cart"]')) {
                        this.closeDrawer();
                    }
                });
            }

            // Global Add To Cart Delegate
            document.addEventListener('click', (e) => {
                const addBtn = e.target.closest('[data-action="add-to-cart"]');
                if (addBtn) {
                    e.preventDefault();
                    const productId = parseInt(addBtn.getAttribute('data-product-id') || '0', 10);
                    const qty = parseInt(addBtn.getAttribute('data-qty') || '1', 10);
                    const openAfter = addBtn.getAttribute('data-open-drawer') === 'true';
                    if (productId > 0) {
                        this.addItem(productId, qty, openAfter, null, addBtn);
                    }
                }

                // Buy Now Direct
                const buyBtn = e.target.closest('[data-action="buy-now"]');
                if (buyBtn) {
                    e.preventDefault();
                    const productId = parseInt(buyBtn.getAttribute('data-product-id') || '0', 10);
                    const qty = parseInt(buyBtn.getAttribute('data-qty') || '1', 10);
                    if (productId > 0) {
                        this.addItem(productId, qty, false, () => {
                            window.location.href = `${App.baseUrl}/${App.lang}/checkout`;
                        }, buyBtn);
                    }
                }
            });
        },

        openDrawer() {
            const overlay = document.getElementById('cartDrawerOverlay');
            if (overlay) {
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
                this.renderDrawer();
            }
        },

        closeDrawer() {
            const overlay = document.getElementById('cartDrawerOverlay');
            if (overlay) {
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        },

        async addItem(productId, qty = 1, openAfter = false, onSuccess = null, triggerEl = null) {
            let animCompleted = false;
            let apiResponse = null;

            const onFinish = () => {
                if (!animCompleted || !apiResponse) return;
                const cart = apiResponse.data?.cart || apiResponse.data || {};
                this.cartData = cart;
                this.updateBadges(cart.total_qty || 0);
                showToast(apiResponse.message || 'เพิ่มสินค้าลงในตะกร้าเรียบร้อยแล้ว', 'success');

                if (openAfter) {
                    this.openDrawer();
                }
                if (typeof onSuccess === 'function') {
                    onSuccess();
                }
            };

            // Start flying animation to cart
            if (triggerEl) {
                animateFlyToCart(triggerEl, () => {
                    animCompleted = true;
                    onFinish();
                });
            } else {
                animCompleted = true;
            }

            try {
                const res = await fetch(`${App.baseUrl}/api/cart.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'add', product_id: productId, qty: qty, lang: App.lang })
                });
                const data = await res.json();
                if (data.status) {
                    apiResponse = data;
                    // Google Analytics 4 (GA4) E-Commerce Event
                    if (typeof window.gtag === 'function') {
                        const itemObj = data.data?.item || data.data?.added_item || {};
                        window.gtag('event', 'add_to_cart', {
                            currency: 'THB',
                            value: (itemObj.price || 0) * qty,
                            items: [{
                                item_id: String(productId),
                                item_name: itemObj.name || ('Product #' + productId),
                                price: itemObj.price || 0,
                                quantity: qty
                            }]
                        });
                    }
                    if (animCompleted) {
                        onFinish();
                    }
                } else {
                    showToast(data.message || 'เกิดข้อผิดพลาดในการเพิ่มสินค้า', 'error');
                }
            } catch (err) {
                console.error('Cart API error:', err);
                showToast('ไม่สามารถเชื่อมต่อระบบได้ กรุณาลองใหม่', 'error');
            }
        },

        async updateQty(productId, qty) {
            try {
                const res = await fetch(`${App.baseUrl}/api/cart.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'update', product_id: productId, qty: qty, lang: App.lang })
                });
                const data = await res.json();
                if (data.status) {
                    const cart = data.data?.cart || data.data || {};
                    this.cartData = cart;
                    this.updateBadges(cart.total_qty || 0);
                    this.renderDrawer();
                }
            } catch (err) {
                console.error(err);
            }
        },

        async refreshCart(open = false) {
            try {
                const res = await fetch(`${App.baseUrl}/api/cart.php?action=get&lang=${App.lang}`);
                const data = await res.json();
                if (data.status) {
                    const cart = data.data?.cart || data.data || {};
                    this.cartData = cart;
                    this.updateBadges(cart.total_qty || 0);
                    if (open) this.openDrawer();
                }
            } catch (err) {
                console.error(err);
            }
        },

        updateBadges(totalQty) {
            document.querySelectorAll('[data-cart-badge]').forEach(badge => {
                badge.textContent = totalQty;
                badge.style.display = totalQty > 0 ? 'flex' : 'none';
            });
        },

        renderDrawer() {
            const listEl = document.getElementById('cartDrawerItemsList');
            const subtotalEl = document.getElementById('cartDrawerSubtotal');
            const totalEl = document.getElementById('cartDrawerTotal');
            const tierNoticeEl = document.getElementById('cartDrawerTierNotice');

            if (!listEl) return;
            const cart = this.cartData;

            if (!cart || !cart.items || cart.items.length === 0) {
                listEl.innerHTML = `
                    <div style="text-align: center; padding: 40px 10px; color: var(--yp-text-muted);">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 12px auto; opacity: 0.6;"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                        <p style="font-size: 0.95rem; font-weight: 600; color: var(--yp-text);">ยังไม่มีสินค้าในตะกร้า</p>
                        <p style="font-size: 0.8rem; margin-top: 4px;">เลือกชมสมุนไพรและบาล์มสปาเพื่อเริ่มสั่งซื้อ</p>
                    </div>
                `;
                if (subtotalEl) subtotalEl.textContent = '฿0.00';
                if (totalEl) totalEl.textContent = '฿0.00';
                if (tierNoticeEl) tierNoticeEl.style.display = 'none';
                return;
            }

            // Dynamic Wholesale Savings Notice
            if (tierNoticeEl) {
                const total1kg = cart.total_1kg_qty || 0;
                if (total1kg >= 6) {
                    tierNoticeEl.style.display = 'flex';
                    tierNoticeEl.innerHTML = `
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                        <span>คุณได้รับเรทราคาส่ง <strong>฿${cart.wholesale_kg_rate}.-/กก.</strong> (ประหยัด ฿${Math.round(cart.wholesale_savings)}.-)</span>
                    `;
                } else if (total1kg > 0) {
                    const needMore = 6 - total1kg;
                    tierNoticeEl.style.display = 'flex';
                    tierNoticeEl.innerHTML = `
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline></svg>
                        <span>สั่งบาล์ม 1 กก. เพิ่มอีก <strong>${needMore} กก.</strong> รับเรทส่งพิเศษ ฿590/กก. ทันที!</span>
                    `;
                } else {
                    tierNoticeEl.style.display = 'none';
                }
            }

            // Items List
            const defaultFallback = (window.YAKPHO_CONFIG && window.YAKPHO_CONFIG.urlPath ? window.YAKPHO_CONFIG.urlPath : '/yakpho.com') + '/catalog/assets/img/best_h001.webp';
            listEl.innerHTML = cart.items.map(item => {
                const img = item.image_url || item.image || defaultFallback;
                const price = parseFloat(item.unit_price || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });
                return `
                <div class="cart-item-row" data-product-id="${item.product_id}">
                    <img src="${img}" alt="${item.product_name}" class="cart-item-thumb" loading="lazy" onerror="this.onerror=null; this.src='${defaultFallback}';">
                    <div class="cart-item-info">
                        <div class="cart-item-name">${item.product_name}</div>
                        <div class="cart-item-price">฿${price}</div>
                    </div>
                    <div class="cart-stepper">
                        <button type="button" class="cart-stepper-btn" onclick="CartManager.updateQty(${item.product_id}, ${item.qty - 1})">-</button>
                        <span class="cart-stepper-val">${item.qty}</span>
                        <button type="button" class="cart-stepper-btn" onclick="CartManager.updateQty(${item.product_id}, ${item.qty + 1})">+</button>
                    </div>
                </div>
            `;
            }).join('');

            if (subtotalEl) subtotalEl.textContent = '฿' + parseFloat(cart.subtotal).toLocaleString('en-US', { minimumFractionDigits: 2 });
            if (totalEl) totalEl.textContent = '฿' + parseFloat(cart.grand_total).toLocaleString('en-US', { minimumFractionDigits: 2 });

            const shippingEl = document.getElementById('cartDrawerShipping');
            const shippingLabelEl = document.getElementById('cartDrawerShippingLabel');
            if (shippingEl) {
                const sCost = parseFloat(cart.shipping_cost || 0);
                const sWeight = parseInt(cart.total_weight_grams || 0, 10);
                if (shippingLabelEl) {
                    shippingLabelEl.textContent = sWeight > 0 ? `การจัดส่ง (${sWeight.toLocaleString()}g)` : 'การจัดส่ง';
                }
                if (sCost === 0) {
                    shippingEl.textContent = sWeight > 12000 ? 'ส่งฟรี (> 12 กก.)' : (sWeight === 0 ? '฿0.00' : 'ส่งฟรี');
                    shippingEl.style.color = 'var(--yp-green)';
                } else {
                    shippingEl.textContent = '฿' + sCost.toLocaleString('en-US', { minimumFractionDigits: 2 });
                    shippingEl.style.color = 'var(--yp-text)';
                }
            }
        }
    };
    window.CartManager = CartManager;

    // ============================================================
    // 3. Hero Touch Slider / Carousel
    // ============================================================
    function initHeroSlider() {
        const wrap = document.getElementById('heroSliderWrap');
        const track = document.getElementById('heroSliderTrack');
        const dots = document.querySelectorAll('#heroDotsWrap .hero-dot');
        if (!wrap || !track || dots.length <= 1) return;

        let currentIndex = 0;
        const totalSlides = dots.length;
        let startX = 0;
        let isSwiping = false;
        let autoPlayTimer = null;

        function goToSlide(idx) {
            currentIndex = (idx + totalSlides) % totalSlides;
            track.style.transform = `translateX(-${currentIndex * 100}%)`;
            dots.forEach((dot, i) => {
                dot.classList.toggle('active', i === currentIndex);
            });
        }

        function nextSlide() {
            goToSlide(currentIndex + 1);
        }

        function startAutoPlay() {
            stopAutoPlay();
            autoPlayTimer = setInterval(nextSlide, 5000);
        }

        function stopAutoPlay() {
            if (autoPlayTimer) clearInterval(autoPlayTimer);
        }

        // Dot Clicks
        dots.forEach((dot, idx) => {
            dot.addEventListener('click', () => {
                goToSlide(idx);
                startAutoPlay();
            });
        });

        // Touch Swipe
        track.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            isSwiping = true;
            stopAutoPlay();
        }, { passive: true });

        track.addEventListener('touchend', (e) => {
            if (!isSwiping) return;
            const diffX = e.changedTouches[0].clientX - startX;
            if (Math.abs(diffX) > 40) {
                if (diffX > 0) {
                    goToSlide(currentIndex - 1);
                } else {
                    goToSlide(currentIndex + 1);
                }
            }
            isSwiping = false;
            startAutoPlay();
        });

        startAutoPlay();
    }

    // ============================================================
    // 4. App Header Search Autocomplete
    // ============================================================
    function initHeaderSearch() {
        const input = document.getElementById('headerSearchInput');
        const dropdown = document.getElementById('searchDropdownMenu');
        if (!input || !dropdown) return;

        input.addEventListener('focus', () => {
            dropdown.classList.add('active');
        });

        document.addEventListener('click', (e) => {
            if (!input.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });

        // Enter key to search
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                const val = input.value.trim();
                if (val) {
                    window.location.href = `${App.baseUrl}/${App.lang}/products?q=${encodeURIComponent(val)}`;
                }
            }
        });
    }

    // ============================================================
    // 5. Product Detail Photo Gallery & Tab Switcher
    // ============================================================
    function initProductDetail() {
        // Tab buttons
        const tabBtns = document.querySelectorAll('[data-detail-tab]');
        const tabPanes = document.querySelectorAll('.detail-tab-pane');
        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const target = btn.getAttribute('data-detail-tab');
                tabBtns.forEach(b => b.classList.remove('active'));
                tabPanes.forEach(p => p.classList.remove('active'));
                btn.classList.add('active');
                const targetPane = document.getElementById(target);
                if (targetPane) targetPane.classList.add('active');
            });
        });

        // Quantity Stepper on Detail Page
        const minusBtn = document.getElementById('detailQtyMinus');
        const plusBtn = document.getElementById('detailQtyPlus');
        const qtyInput = document.getElementById('detailQtyInput');
        if (minusBtn && plusBtn && qtyInput) {
            minusBtn.addEventListener('click', () => {
                let v = parseInt(qtyInput.value || '1', 10);
                if (v > 1) {
                    qtyInput.value = v - 1;
                    syncDetailQtyButtons(v - 1);
                }
            });
            plusBtn.addEventListener('click', () => {
                let v = parseInt(qtyInput.value || '1', 10);
                qtyInput.value = v + 1;
                syncDetailQtyButtons(v + 1);
            });
        }

        function syncDetailQtyButtons(qty) {
            document.querySelectorAll('[data-action="add-to-cart"], [data-action="buy-now"]').forEach(btn => {
                btn.setAttribute('data-qty', qty);
            });
        }

        // Global Order Track Form Hook
        const trackForm = document.getElementById('orderTrackLookupForm');
        if (trackForm) {
            trackForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const q = (trackForm.querySelector('input[name="q"]')?.value || '').trim();
                if (!q) {
                    showToast('กรุณากรอกเลขที่คำสั่งซื้อ หรือเบอร์โทรศัพท์', 'error');
                    return;
                }
                try {
                    const res = await fetch(`${App.baseUrl}/api/track_lookup.php?q=${encodeURIComponent(q)}`);
                    const json = await res.json();
                    if (json.status && json.data?.track_url) {
                        window.location.href = json.data.track_url;
                    } else {
                        showToast(json.message || 'ไม่พบข้อมูลคำสั่งซื้อ', 'error');
                    }
                } catch (err) {
                    showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
                }
            });
        }
    }

    // ============================================================
    // 6. Wholesale B2B Matrix Live Calculation (Direct Cart Only)
    // ============================================================
    function initWholesaleMatrix() {
        const matrixWrapper = document.getElementById('wholesaleMatrixWrapper');
        if (!matrixWrapper) return;

        function getTierRate(totalKg) {
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
            const items = [];
            matrixWrapper.querySelectorAll('.cell').forEach(cell => {
                const qtyEl = cell.querySelector('.qty');
                const q = parseInt(qtyEl?.textContent || '0', 10);
                if (q > 0) {
                    totalKg += q;
                    items.push({
                        product_id: parseInt(cell.getAttribute('data-product-id') || '0', 10),
                        product_code: cell.getAttribute('data-product-code') || '',
                        code: cell.getAttribute('data-sku') || '',
                        sku: cell.getAttribute('data-sku') || '',
                        scent: cell.getAttribute('data-scent') || '',
                        formula: cell.getAttribute('data-formula') || '',
                        qty: q
                    });
                }
            });

            const rate = getTierRate(totalKg);
            const subtotal = totalKg * rate;
            const retailVal = totalKg * 690;
            const saved = Math.max(0, retailVal - subtotal);

            const totalKgEl = document.getElementById('matrixTotalKg');
            const currentRateEl = document.getElementById('matrixCurrentRate');
            const totalSavedEl = document.getElementById('matrixTotalSaved');
            const totalPriceEl = document.getElementById('matrixTotalPrice');

            if (totalKgEl) totalKgEl.textContent = `${totalKg} kg`;
            if (currentRateEl) currentRateEl.textContent = `฿${rate} / kg`;
            if (totalSavedEl) totalSavedEl.textContent = `฿${saved.toLocaleString()}`;
            if (totalPriceEl) totalPriceEl.textContent = `฿${subtotal.toLocaleString()}`;

            // Highlight active tier strip
            document.querySelectorAll('.tier-rate-box').forEach(box => {
                const min = parseInt(box.getAttribute('data-tier-min') || '0', 10);
                const max = parseInt(box.getAttribute('data-tier-max') || '9999', 10);
                box.classList.toggle('is-active-tier', totalKg >= min && totalKg <= max);
            });

            const stickyBar = document.getElementById('wholesaleStickyFooter');
            if (stickyBar) {
                stickyBar.classList.toggle('is-active-bar', totalKg > 0);
            }

            return { totalKg, rate, subtotal, items };
        }

        // Stepper buttons with sound & flying animation
        matrixWrapper.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn');
            if (!btn) return;

            const cell = btn.closest('.cell');
            if (!cell) return;

            const qtyEl = cell.querySelector('.qty');
            const minusBtn = cell.querySelector('.btn.minus');
            let val = parseInt(qtyEl.textContent || '0', 10);

            if (btn.classList.contains('plus')) {
                val++;
                // Flying motion
                const rectStart = btn.getBoundingClientRect();
                const targetCart = document.querySelector('.bottom-dock-item:has(i[data-lucide="shopping-cart"])') 
                                || document.querySelector('.header-cart-btn')
                                || document.getElementById('wholesaleStickyFooter');
                const rectEnd = targetCart ? targetCart.getBoundingClientRect() : { left: window.innerWidth / 2, top: window.innerHeight - 50 };

                const fly = document.createElement('div');
                fly.className = 'fly-item';
                fly.style.background = window.getComputedStyle(btn).backgroundColor || '#C7A15A';
                fly.style.left = rectStart.left + 'px';
                fly.style.top = rectStart.top + 'px';
                document.body.appendChild(fly);

                const translateX = (rectEnd.left + (rectEnd.width ? rectEnd.width / 2 : 0)) - rectStart.left;
                const translateY = (rectEnd.top + (rectEnd.height ? rectEnd.height / 2 : 0)) - rectStart.top;

                fly.animate([
                    { transform: 'translate(0, 0) scale(1)', opacity: 1 },
                    { transform: `translate(${translateX / 3}px, ${translateY * 0.3 - 100}px) scale(1.15)`, opacity: 0.9, offset: 0.4 },
                    { transform: `translate(${translateX}px, ${translateY}px) scale(0.25)`, opacity: 0.1 }
                ], { duration: 650, easing: 'cubic-bezier(.42, 0, .58, 1)' });

                setTimeout(() => fly.remove(), 700);

                const fCode = cell.getAttribute('data-formula');
                const fName = fCode === 'H' ? 'สูตรร้อน' : (fCode === 'C' ? 'สูตรเย็น' : 'สูตรนวดตัว');
                const sku = cell.getAttribute('data-sku') || '';
                showToast(`+1 ${fName} (${sku})`, 'success');
            } else if (btn.classList.contains('minus')) {
                val = Math.max(0, val - 1);
                const fCode = cell.getAttribute('data-formula');
                const fName = fCode === 'H' ? 'สูตรร้อน' : (fCode === 'C' ? 'สูตรเย็น' : 'สูตรนวดตัว');
                const sku = cell.getAttribute('data-sku') || '';
                showToast(`−1 ${fName} (${sku})`, 'info');
            }

            qtyEl.textContent = val;
            cell.classList.toggle('active', val > 0);

            if (minusBtn) {
                if (val <= 0) {
                    minusBtn.disabled = true;
                    minusBtn.classList.add('btn-disabled');
                } else {
                    minusBtn.disabled = false;
                    minusBtn.classList.remove('btn-disabled');
                }
            }

            recalcMatrix();
        });

        // Add All To Cart Button (Online Cart Only, NO LINE)
        const addAllBtn = document.getElementById('btnMatrixAddToCart');
        if (addAllBtn) {
            addAllBtn.addEventListener('click', async () => {
                const calc = recalcMatrix();
                if (calc.totalKg <= 0 || calc.items.length === 0) {
                    showToast('กรุณาระบุจำนวนสินค้าอย่างน้อย 1 กก.', 'error');
                    return;
                }
                addAllBtn.disabled = true;
                const origHtml = addAllBtn.innerHTML;
                addAllBtn.innerHTML = '<span class="loading-spinner-sm"></span> กำลังบันทึกลงตะกร้า...';

                try {
                    const res = await fetch(`${App.baseUrl}/api/cart.php`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'add_bulk',
                            items: calc.items,
                            lang: App.lang
                        })
                    });
                    const json = await res.json();
                    if (json.status) {
                        showToast(json.message || `เพิ่มรายการสั่งซื้อ ${calc.totalKg} กก. ลงในตะกร้าแล้ว!`, 'success');
                        CartManager.cartData = json.data.cart;
                        CartManager.updateBadges(json.data.cart.total_qty || 0);
                        CartManager.renderDrawer();
                        CartManager.openDrawer();
                    } else {
                        showToast(json.message || 'เกิดข้อผิดพลาดในการบันทึก', 'error');
                    }
                } catch (e) {
                    showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
                } finally {
                    addAllBtn.disabled = false;
                    addAllBtn.innerHTML = origHtml;
                    if (window.lucide) lucide.createIcons();
                }
            });
        }
    }

    // ============================================================
    // 7. Language & Theme Switcher
    // ============================================================
    function toggleTheme() {
        const current = document.documentElement.getAttribute('data-theme') || 'dark';
        const next = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('yakpho_theme', next);
        document.cookie = "yakpho_theme=" + next + "; path=/; max-age=31536000; SameSite=Lax";
        showToast(next === 'dark' ? 'เปิดโหมดมืด (Emerald Night)' : 'เปิดโหมดสว่าง (Clean Sand)', 'success');
    }
    window.toggleTheme = toggleTheme;

    function initThemeAndLang() {
        document.querySelectorAll('[data-action="toggle-theme"]').forEach(btn => {
            btn.addEventListener('click', toggleTheme);
        });

        // Restore saved theme
        const savedTheme = localStorage.getItem('yakpho_theme');
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
        }
    }

    function toggleHeaderChatDropdown(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        const dd = document.getElementById('headerChatDropdown');
        if (dd) {
            dd.classList.toggle('is-open');
        }
    }
    window.toggleHeaderChatDropdown = toggleHeaderChatDropdown;

    document.addEventListener('click', (e) => {
        const wrap = document.querySelector('.header-chat-dropdown-wrap');
        const dd = document.getElementById('headerChatDropdown');
        if (dd && wrap && !wrap.contains(e.target)) {
            dd.classList.remove('is-open');
        }
    });

    // ============================================================
    // Category Drawer & Catalog Functions
    // ============================================================
    function openCategoryDrawer() {
        const overlay = document.getElementById('categoryDrawerOverlay');
        if (overlay) {
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }
    window.openCategoryDrawer = openCategoryDrawer;

    function closeCategoryDrawer(e) {
        if (e && e.target && e.target !== e.currentTarget && !e.target.closest('.category-drawer-close')) {
            return;
        }
        const overlay = document.getElementById('categoryDrawerOverlay');
        if (overlay) {
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
    window.closeCategoryDrawer = closeCategoryDrawer;

    function cleanFormSubmit(form) {
        if (!form) return;
        const q = form.querySelector('#catalogSearchInput');
        const cat = form.querySelector('#catalogCatInput');
        if (q && !q.value.trim()) q.removeAttribute('name');
        if (cat && (!cat.value || cat.value === '0')) cat.removeAttribute('name');
    }
    window.cleanFormSubmit = cleanFormSubmit;

    async function toggleProductLike(e, productId, btn) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        if (!productId || !btn) return;
        if (btn.dataset.loading === '1') return;
        btn.dataset.loading = '1';

        try {
            const res = await fetch(`${App.baseUrl}/api/product_like.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: productId })
            });
            const json = await res.json();
            if (json.status && json.data) {
                const countEl = btn.querySelector('.product-like-count');
                if (countEl) countEl.textContent = Number(json.data.likes_count).toLocaleString();
                if (json.data.liked) {
                    btn.classList.add('is-liked');
                } else {
                    btn.classList.remove('is-liked');
                }
            }
        } catch (err) {
            console.error('Like toggle error:', err);
        } finally {
            btn.dataset.loading = '0';
        }
    }
    window.toggleProductLike = toggleProductLike;

    // Delegated click listener for category drawer triggers
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('.btn-cat-drawer-trigger');
        if (trigger) {
            e.preventDefault();
            openCategoryDrawer();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeCategoryDrawer();
        }
    });

    // Initialize all modules on DOM ready
    document.addEventListener('DOMContentLoaded', () => {
        CartManager.init();
        initHeroSlider();
        initHeaderSearch();
        initProductDetail();
        initWholesaleMatrix();
        initThemeAndLang();
    });

})();
