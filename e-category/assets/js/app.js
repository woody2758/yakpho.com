


// Yakpho Live Search – Safe Optimized
const searchInput = document.getElementById('pageSearch');
const nextBtn = document.getElementById('nextResult');
let matches = [];
let currentIndex = 0;
let debounceTimer = null;

function clearHighlights() {
  document.querySelectorAll('mark.highlight').forEach(el => {
    const parent = el.parentNode;
    parent.replaceChild(document.createTextNode(el.textContent), el);
    parent.normalize();
  });
}

function highlightText(keyword) {
  clearHighlights();
  if (!keyword) return;

  const regex = new RegExp(`(${keyword})`, 'gi');
  matches = [];

  const walker = document.createTreeWalker(
    document.querySelector('main'),
    NodeFilter.SHOW_TEXT,
    null
  );

  const nodes = [];
  while (walker.nextNode()) nodes.push(walker.currentNode);

  nodes.forEach(node => {
    if (node.parentElement.closest('script,style')) return;
    if (regex.test(node.textContent)) {
      const span = document.createElement('span');
      span.innerHTML = node.textContent.replace(
        regex,
        '<mark class="highlight">$1</mark>'
      );
      node.parentNode.replaceChild(span, node);
    }
  });

  matches = Array.from(document.querySelectorAll('mark.highlight'));
  if (matches.length > 0) scrollToMatch(0);
}

function scrollToMatch(i) {
  matches.forEach((m, idx) => {
    m.style.background = idx === i ? '#FFD54F' : '#FFFF99';
  });
  matches[i]?.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

searchInput?.addEventListener('input', e => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => highlightText(e.target.value.trim()), 300);
});

nextBtn?.addEventListener('click', () => {
  if (matches.length === 0) return;
  currentIndex = (currentIndex + 1) % matches.length;
  scrollToMatch(currentIndex);
});



// 🧹 Yakpho Hard Reset – กัน overlay ทับหน้า
window.addEventListener('load', () => {
  const box = document.getElementById('yakphoLightbox') || document.querySelector('.lightbox');
  if (box) {
    box.style.display = 'none';
    box.classList.remove('active');
  }
  document.body.style.overflow = '';
  console.log('🧹 Yakpho Overlay Reset Done');
});

window.addEventListener('load', () => {
  const lb = document.querySelector('.lightbox');
  if (lb) lb.style.display = 'none';
  document.body.style.overflow = 'auto';
});


// ===============================
// Fancybox 5 – Lightbox Pro
// ===============================
Fancybox.bind('[data-fancybox="gallery"]', {
  animated: true,
  showClass: "fancybox-fadeIn",
  hideClass: "fancybox-fadeOut",
  dragToClose: true,
  Thumbs: { autoStart: false },
  Toolbar: {
    display: { left: [], middle: ['zoomIn','zoomOut'], right: ['close'] },
  },
  Image: { zoom: true },
});
// ===============================
// Lazy Load
// ===============================

document.addEventListener("DOMContentLoaded", () => { const lazyImgs = document.querySelectorAll("img.lazy"); const io = new IntersectionObserver(entries => { entries.forEach(entry => { if (entry.isIntersecting) { const img = entry.target; const src = img.dataset.src; if (src) { img.src = src; img.onload = () => img.classList.add("loaded"); io.unobserve(img); } } }); }, { threshold: 0.1 }); lazyImgs.forEach(img => io.observe(img)); });

/* ============================================================
💎 YakPho Aroma – app.js v1.1
รวมระบบ: Smooth Scroll + Global Loading + Menu Toggle + Click Sound
============================================================ */

// 🔹 Smooth Scroll (with topbar offset)
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function (e) {
    const target = document.querySelector(this.getAttribute('href'));
    if (target) {
      e.preventDefault();
      const topbarHeight = document.querySelector('.topbar')?.offsetHeight || 0;
      const elementPosition = target.getBoundingClientRect().top + window.pageYOffset;
      const offsetPosition = elementPosition - (topbarHeight + 10);
      window.scrollTo({ top: offsetPosition, behavior: "smooth" });
      history.pushState(null, null, this.getAttribute('href'));
    }
  });
});


// ============================================================
// 🔸 Global Loading Overlay – แสดงตอนเปลี่ยนหน้า
// ============================================================

const yakphoLoader = document.createElement('div');
yakphoLoader.id = 'yakpho-loader';
yakphoLoader.innerHTML = `
  <div class="loader-backdrop">
    <div class="loader-spinner"></div>
    <div class="loader-text">กำลังโหลด...</div>
  </div>
`;
document.body.appendChild(yakphoLoader);

function showYakphoLoader() {
  yakphoLoader.classList.add('active');
  document.body.classList.add('blurred');
}
function hideYakphoLoader() {
  yakphoLoader.classList.remove('active');
  document.body.classList.remove('blurred');
}

// ตรวจจับลิงก์ / ปุ่ม
document.addEventListener('click', function(e) {
  const el = e.target.closest('a, button');
  if (!el) return;
   // ✅ ถ้ามี data-no-loader="true" → ไม่ต้องแสดง Loader
  if (el.dataset.noLoader === "true") return;

  const href = el.getAttribute('href');
  if (href && href.startsWith('#')) return;
  if ((href && !href.startsWith('javascript:')) || el.dataset.loading === "true") {
    showYakphoLoader();
  }
});
window.addEventListener('load', hideYakphoLoader);


// ============================================================
// 🪶 Floating Side Menu + Click Sound
// ============================================================

document.addEventListener("DOMContentLoaded", () => {
  const btn = document.getElementById("menuToggle");
  const menu = document.getElementById("sideMenu");
  const clickSound = document.getElementById("softClick");

  if (!btn || !menu) return;

  // 🪶 แสดงปุ่มเมนูเมื่อ scroll ลง
  window.addEventListener("scroll", () => {
    if (window.scrollY > 50) {
      document.body.classList.add("show-float-menu");
    } else {
      document.body.classList.remove("show-float-menu");
      btn.classList.remove("active");
      menu.classList.remove("open");
    }
  });

  // 🔄 toggle เมนูเปิด/ปิด + เสียงคลิก
  btn.addEventListener("click", () => {
    btn.classList.toggle("active");
    menu.classList.toggle("open");
    if (clickSound) {
      clickSound.currentTime = 0;
      clickSound.volume = 0.25;
      clickSound.play().catch(()=>{});
    }
  });

  // 🚀 smooth scroll + ปิดเมนูเมื่อคลิก
  menu.querySelectorAll("a[href^='#']").forEach(link => {
    link.addEventListener("click", e => {
      e.preventDefault();
      const target = document.querySelector(link.getAttribute("href"));
      if (target) {
        const offset = target.offsetTop - 60;
        window.scrollTo({ top: offset, behavior: "smooth" });
      }
      btn.classList.remove("active");
      menu.classList.remove("open");
    });
  });

  // lucide icon refresh (เผื่อมี dynamic load)
  if (window.lucide) lucide.createIcons();
});

// ===============================
// YakPho Aroma – GA4 Custom Events
// ===============================
document.addEventListener("DOMContentLoaded", () => {

  // 📦 1. เมื่อกดปุ่มส่งคำสั่งซื้อ
  const orderBtn = document.getElementById("copyBtn");
  if (orderBtn) {
    orderBtn.addEventListener("click", () => {
      gtag('event', 'click_send_order', {
        event_category: 'Order',
        event_label: 'ส่งคำสั่งซื้อ YakPho Aroma'
      });
    });
  }

  // 💬 2. ปุ่ม LINE / Messenger / WhatsApp / Email (จาก SweetAlert)
  document.body.addEventListener("click", (e) => {
    const btn = e.target.closest('.contact-btn');
    if (!btn) return;
    const type = btn.classList.contains('line') ? 'LINE' :
                 btn.classList.contains('messenger') ? 'Messenger' :
                 btn.classList.contains('whatsapp') ? 'WhatsApp' :
                 btn.classList.contains('email') ? 'Email' : 'Other';
    gtag('event', 'click_contact', {
      event_category: 'Contact',
      event_label: type
    });
  });

  // ➕➖ 3. นับจำนวนคลิก + / −
  document.body.addEventListener("click", (e) => {
    const btn = e.target.closest('.btn.plus, .btn.minus');
    if (!btn) return;
    const type = btn.classList.contains('plus') ? 'เพิ่มสินค้า' : 'ลดสินค้า';
    const sku  = btn.closest('.cell')?.dataset.sku || 'unknown';
    gtag('event', 'click_adjust_qty', {
      event_category: 'Product Adjust',
      event_label: `${type} ${sku}`
    });
  });

});

