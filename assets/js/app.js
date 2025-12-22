/**
 * Esther Aroma - Main App Controller
 * AJAX Framework + Global Functions
 */

const App = {
  /**
   * Initialize application
   */
  init() {
    this.initializeHeader();
    this.loadCartCount();
    this.initializeModals();
    console.log('🌿 Esther Aroma App Initialized');
  },

  /**
   * Initialize header scroll effects
   */
  initializeHeader() {
    const header = document.getElementById('site-header');
    let lastScroll = 0;

    window.addEventListener('scroll', () => {
      const currentScroll = window.pageYOffset;

      if (currentScroll > 50) {
        header.classList.add('scrolled');
      } else {
        header.classList.remove('scrolled');
      }

      lastScroll = currentScroll;
    });

    // Mobile menu toggle
    const toggle = document.getElementById('mobile-menu-toggle');
    const nav = document.getElementById('site-nav');

    if (toggle) {
      toggle.addEventListener('click', () => {
        nav.classList.toggle('open');
        toggle.classList.toggle('active');
      });
    }
  },

  /**
   * Language Switcher
   */
  toggleLanguageSwitcher() {
    const switcher = document.getElementById('lang-switcher');
    switcher.classList.toggle('open');
  },

  changeLanguage(langCode) {
    // Set language in session via AJAX
    fetch(`${window.location.origin}/api/set_language.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ lang: langCode })
    })
      .then(() => {
        // Reload page with new language
        window.location.reload();
      })
      .catch(error => {
        console.error('Error changing language:', error);
      });
  },

  /**
   * Load cart count from localStorage or API
   */
  loadCartCount() {
    const cartBadge = document.getElementById('cart-count');
    if (!cartBadge) return;

    // Get cart from localStorage
    const cart = JSON.parse(localStorage.getItem('cart') || '[]');
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);

    cartBadge.textContent = totalItems;
    cartBadge.style.display = totalItems > 0 ? 'flex' : 'none';
  },

  /**
   * Update cart count
   */
  updateCartCount(count) {
    const cartBadge = document.getElementById('cart-count');
    if (cartBadge) {
      cartBadge.textContent = count;
      cartBadge.style.display = count > 0 ? 'flex' : 'none';
    }
  },

  /**
   * Show toast notification
   */
  showToast(message, type = 'success') {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `alert alert-${type}`;
    toast.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideInRight 0.3s ease-out;
        `;
    toast.textContent = message;

    document.body.appendChild(toast);

    // Auto remove after 3 seconds
    setTimeout(() => {
      toast.style.animation = 'slideOutRight 0.3s ease-out';
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  },

  /**
   * Show loading spinner
   */
  showLoading(message = 'กำลังโหลด...') {
    const backdrop = document.createElement('div');
    backdrop.id = 'loading-backdrop';
    backdrop.className = 'modal-backdrop show';
    backdrop.innerHTML = `
            <div style="text-align: center; color: white;">
                <div class="spinner" style="margin: 0 auto 16px;"></div>
                <p>${message}</p>
            </div>
        `;
    document.body.appendChild(backdrop);
  },

  /**
   * Hide loading spinner
   */
  hideLoading() {
    const backdrop = document.getElementById('loading-backdrop');
    if (backdrop) {
      backdrop.remove();
    }
  },

  /**
   * Initialize modal functionality
   */
  initializeModals() {
    // Close modal on backdrop click
    document.addEventListener('click', (e) => {
      if (e.target.classList.contains('modal-backdrop')) {
        this.closeModal();
      }
    });

    // Close modal on ESC key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        this.closeModal();
      }
    });
  },

  /**
   * Show modal
   */
  showModal(modalId) {
    const backdrop = document.getElementById(modalId);
    if (backdrop) {
      backdrop.classList.add('show');
      document.body.style.overflow = 'hidden';
    }
  },

  /**
   * Close modal
   */
  closeModal(modalId = null) {
    if (modalId) {
      const backdrop = document.getElementById(modalId);
      if (backdrop) {
        backdrop.classList.remove('show');
      }
    } else {
      // Close all modals
      document.querySelectorAll('.modal-backdrop.show').forEach(modal => {
        modal.classList.remove('show');
      });
    }
    document.body.style.overflow = '';
  },

  /**
   * Newsletter subscription
   */
  async subscribeNewsletter(event) {
    event.preventDefault();
    const form = event.target;
    const email = form.querySelector('input[name="email"]').value;

    this.showLoading('กำลังสมัคร...');

    try {
      const response = await fetch('/api/subscribe_newsletter.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email })
      });

      const data = await response.json();

      this.hideLoading();

      if (data.success) {
        this.showToast('สมัครรับข่าวสารสำเร็จ! ขอบคุณครับ', 'success');
        form.reset();
      } else {
        this.showToast(data.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง', 'error');
      }
    } catch (error) {
      this.hideLoading();
      this.showToast('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง', 'error');
      console.error('Newsletter error:', error);
    }
  },

  /**
   * Smooth scroll to element
   */
  scrollTo(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
      element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  },

  /**
   * Format number as Thai Baht currency
   */
  formatCurrency(amount) {
    return new Intl.NumberFormat('th-TH', {
      style: 'currency',
      currency: 'THB',
      minimumFractionDigits: 0
    }).format(amount);
  },

  /**
   * Debounce function for search
   */
  debounce(func, wait) {
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
};

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Initialize app on DOM ready
document.addEventListener('DOMContentLoaded', () => {
  App.init();
});

// Close language switcher when clicking outside
document.addEventListener('click', (e) => {
  const switcher = document.getElementById('lang-switcher');
  if (switcher && !switcher.contains(e.target)) {
    switcher.classList.remove('open');
  }
});
