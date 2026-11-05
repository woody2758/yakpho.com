/**
 * ESTHER AROMA & YAK PHO — Interactive UX/UI Prototype Script
 * Handles Mobile Drawer, Submenu Accordion, Category Filtering, and Smooth Scroll
 */

document.addEventListener('DOMContentLoaded', () => {
  // Initialize Lucide icons if loaded
  if (window.lucide) {
    window.lucide.createIcons();
  }

  // 1. Mobile Sidebar Drawer Toggle (#ItopplusMainSidebar behavior)
  const menuToggle = document.getElementById('menuToggle');
  const drawerClose = document.getElementById('drawerClose');
  const mobileDrawer = document.getElementById('mobileDrawer');
  const drawerBackdrop = document.getElementById('drawerBackdrop');

  function openDrawer() {
    if (!mobileDrawer || !drawerBackdrop) return;
    mobileDrawer.classList.add('open');
    drawerBackdrop.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeDrawer() {
    if (!mobileDrawer || !drawerBackdrop) return;
    mobileDrawer.classList.remove('open');
    drawerBackdrop.classList.remove('open');
    document.body.style.overflow = '';
  }

  menuToggle?.addEventListener('click', openDrawer);
  drawerClose?.addEventListener('click', closeDrawer);
  drawerBackdrop?.addEventListener('click', closeDrawer);

  // 2. Mobile Submenu Accordion
  const drawerDropdownBtns = document.querySelectorAll('.drawer-dropdown-btn');
  drawerDropdownBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const submenu = btn.nextElementSibling;
      if (submenu) {
        submenu.classList.toggle('open');
        const icon = btn.querySelector('.caret-icon');
        if (icon) {
          icon.style.transform = submenu.classList.contains('open') ? 'rotate(180deg)' : 'rotate(0deg)';
        }
      }
    });
  });

  // 3. Product Category Filter Tabs (for Products & Gift Sets pages)
  const filterTabs = document.querySelectorAll('[data-filter-btn]');
  const filterItems = document.querySelectorAll('[data-filter-item]');

  if (filterTabs.length > 0 && filterItems.length > 0) {
    filterTabs.forEach(tab => {
      tab.addEventListener('click', () => {
        const target = tab.getAttribute('data-filter-btn');
        filterTabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');

        filterItems.forEach(item => {
          const cat = item.getAttribute('data-filter-item');
          if (target === 'all' || cat.includes(target)) {
            item.style.display = '';
          } else {
            item.style.display = 'none';
          }
        });
      });
    });
  }

  // 4. Add to Cart Simulation (Interactive Feedback with Double-Click Prevention)
  const cartBadges = document.querySelectorAll('.cart-badge');
  let cartCount = 2;

  document.querySelectorAll('.js-add-cart').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      if (btn.disabled) return;
      btn.disabled = true;
      const originalHTML = btn.innerHTML;
      btn.innerHTML = '<span>⏳ Adding...</span>';

      setTimeout(() => {
        cartCount++;
        cartBadges.forEach(b => { b.textContent = cartCount; });
        btn.innerHTML = '<span>✓ Added</span>';
        setTimeout(() => {
          btn.innerHTML = originalHTML;
          btn.disabled = false;
        }, 900);
      }, 350);
    });
  });

  // 5. Back to Top
  const backToTop = document.getElementById('backToTop');
  backToTop?.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
});

