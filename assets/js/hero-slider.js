/**
 * Frontend Hero Slider
 * Display dynamic hero slides with fade transitions
 */

const HeroSlider = {
    currentIndex: 0,
    slides: [],
    autoPlayInterval: null,

    /**
     * Initialize slider
     */
    async init() {
        await this.loadSlides();
        if (this.slides.length > 0) {
            this.renderSlides();
            this.startAutoPlay();
            this.initControls();
        }
    },

    /**
     * Load slides from API
     */
    async loadSlides() {
        try {
            const lang = document.documentElement.lang || 'th';
            const response = await fetch(`/api/get_hero_slides.php?lang=${lang}`);
            const data = await response.json();

            if (data.success) {
                this.slides = data.slides;
            }
        } catch (error) {
            console.error('Error loading hero slides:', error);
        }
    },

    /**
     * Render slides HTML
     */
    renderSlides() {
        const heroSection = document.querySelector('.hero');
        if (!heroSection) return;

        heroSection.innerHTML = `
            <div class="hero-slider">
                ${this.slides.map((slide, index) => `
                    <div class="hero-slide ${index === 0 ? 'active' : ''}" 
                         style="background-color: ${slide.slide_bg_color}; ${slide.slide_image ? `background-image: url('/uploads/hero/${slide.slide_image}');` : ''}">
                        <div class="container">
                            <div class="hero-content">
                                <h1 class="hero-title animate-fade-in-up">${slide.slide_title || ''}</h1>
                                <p class="hero-subtitle animate-fade-in-up delay-1">${slide.slide_subtitle || ''}</p>
                                <div class="hero-actions animate-fade-in-up delay-2">
                                    ${slide.button1_text ? `
                                        <a href="${slide.button1_link}" class="btn btn-accent btn-lg">
                                            <i data-lucide="shopping-bag" width="20" height="20"></i>
                                            ${slide.button1_text}
                                        </a>
                                    ` : ''}
                                    ${slide.button2_text ? `
                                        <a href="${slide.button2_link}" class="btn btn-outline btn-lg">
                                            <i data-lucide="book-open" width="20" height="20"></i>
                                            ${slide.button2_text}
                                        </a>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
            
            ${this.slides.length > 1 ? `
                <div class="hero-controls">
                    <button class="hero-prev" onclick="HeroSlider.prevSlide()">
                        <i data-lucide="chevron-left" width="24" height="24"></i>
                    </button>
                    <div class="hero-dots">
                        ${this.slides.map((_, index) => `
                            <button class="hero-dot ${index === 0 ? 'active' : ''}" 
                                    onclick="HeroSlider.goToSlide(${index})">
                            </button>
                        `).join('')}
                    </div>
                    <button class="hero-next" onclick="HeroSlider.nextSlide()">
                        <i data-lucide="chevron-right" width="24" height="24"></i>
                    </button>
                </div>
            ` : ''}
        `;

        // Reinitialize icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    },

    /**
     * Next slide
     */
    nextSlide() {
        if (this.slides.length <= 1) return;

        this.currentIndex = (this.currentIndex + 1) % this.slides.length;
        this.updateSlide();
    },

    /**
     * Previous slide
     */
    prevSlide() {
        if (this.slides.length <= 1) return;

        this.currentIndex = (this.currentIndex - 1 + this.slides.length) % this.slides.length;
        this.updateSlide();
    },

    /**
     * Go to specific slide
     */
    goToSlide(index) {
        if (this.slides.length <= 1) return;

        this.currentIndex = index;
        this.updateSlide();
    },

    /**
     * Update active slide
     */
    updateSlide() {
        const slides = document.querySelectorAll('.hero-slide');
        const dots = document.querySelectorAll('.hero-dot');

        slides.forEach((slide, index) => {
            slide.classList.toggle('active', index === this.currentIndex);
        });

        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === this.currentIndex);
        });

        // Reset autoplay
        this.stopAutoPlay();
        this.startAutoPlay();
    },

    /**
     * Start auto play
     */
    startAutoPlay() {
        if (this.slides.length <= 1) return;

        this.autoPlayInterval = setInterval(() => {
            this.nextSlide();
        }, 5000); // Change slide every 5 seconds
    },

    /**
     * Stop auto play
     */
    stopAutoPlay() {
        if (this.autoPlayInterval) {
            clearInterval(this.autoPlayInterval);
        }
    },

    /**
     * Initialize controls
     */
    initControls() {
        const heroSection = document.querySelector('.hero');
        if (!heroSection) return;

        // Pause on hover
        heroSection.addEventListener('mouseenter', () => this.stopAutoPlay());
        heroSection.addEventListener('mouseleave', () => this.startAutoPlay());

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') this.prevSlide();
            if (e.key === 'ArrowRight') this.nextSlide();
        });
    }
};

// Initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => HeroSlider.init());
} else {
    HeroSlider.init();
}
