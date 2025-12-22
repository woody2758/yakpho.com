/**
 * Homepage JavaScript
 * Load featured products, handle interactions
 */

const Homepage = {
    /**
     * Initialize homepage
     */
    init() {
        this.loadFeaturedProducts();
        this.initializeBrandCards();
    },

    /**
     * Load featured products via AJAX
     */
    async loadFeaturedProducts() {
        const productsGrid = document.getElementById('featured-products');
        if (!productsGrid) return;

        try {
            const response = await fetch('/api/get_products.php?featured=1&limit=4');
            const data = await response.json();

            if (data.success && data.products && data.products.length > 0) {
                productsGrid.innerHTML = data.products.map(product => `
                    <div class="product-card">
                        <div class="product-card-image">
                            <img src="${product.picture || '/assets/images/placeholder.jpg'}" 
                                 alt="${product.name}" 
                                 loading="lazy">
                            <span class="product-card-badge">${product.brand || 'Yak Pho'}</span>
                        </div>
                        <div class="product-card-content">
                            <h3 class="product-card-title">${product.name}</h3>
                            <div class="product-card-price">${App.formatCurrency(product.price)}</div>
                            <button class="btn btn-primary btn-sm" onclick="Cart.addToCart(${product.id})">
                                <i data-lucide="shopping-cart" width="16" height="16"></i>
                                เพิ่มลงตะกร้า
                            </button>
                        </div>
                    </div>
                `).join('');

                // Reinitialize Lucide icons
                lucide.createIcons();
            } else {
                // Show placeholder if no products
                productsGrid.innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; padding: var(--space-12);">
                        <i data-lucide="package-x" width="64" height="64" style="color: var(--color-text-muted); margin: 0 auto var(--space-4);"></i>
                        <p style="color: var(--color-text-muted);">ยังไม่มีสินค้าแนะนำในขณะนี้</p>
                    </div>
                `;
                lucide.createIcons();
            }
        } catch (error) {
            console.error('Error loading products:', error);
            productsGrid.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: var(--space-12);">
                    <p style="color: var(--color-text-muted);">ไม่สามารถโหลดสินค้าได้ กรุณาลองใหม่ภายหลัง</p>
                </div>
            `;
        }
    },

    /**
     * Initialize brand card interactions
     */
    initializeBrandCards() {
        const brandCards = document.querySelectorAll('.brand-card');

        brandCards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                // Optional: Add sound effect or additional animation
            });
        });
    }
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    Homepage.init();
});
