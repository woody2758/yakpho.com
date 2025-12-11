// ============================================================
// 💎 Yakpho Aroma Lightbox – Final Stable Build
// Author: Woody & ChatGPT
// ============================================================
document.addEventListener("DOMContentLoaded", () => {
  const overlay = document.getElementById("yakphoLightbox") || document.querySelector(".lightbox");
  const img = overlay?.querySelector("img");

  // ✅ ซ่อน overlay ทุกครั้งที่โหลดหน้า
  if (overlay) {
    overlay.style.display = "none";
    overlay.classList.remove("active");
  }
  document.body.style.overflow = "";

  console.log("✅ Yakpho Lightbox Loaded (Final Stable)");

  // เปิดรูป
  document.addEventListener("click", e => {
    const link = e.target.closest("a[data-lightbox]");
    if (!link || !overlay || !img) return;
    e.preventDefault();

    img.src = link.getAttribute("href");
    img.alt = link.querySelector("img")?.alt || "Yakpho Image";
    overlay.style.display = "flex";
    overlay.classList.add("active");
    document.body.style.overflow = "hidden";
  });

  // ปิดเมื่อคลิกพื้นที่มืด
  overlay?.addEventListener("click", e => {
    if (e.target === overlay) closeBox();
  });

  // ปิดด้วยปุ่ม ESC
  document.addEventListener("keydown", e => {
    if (e.key === "Escape") closeBox();
  });

  // 🧩 กัน overlay ค้าง (ตรวจซ้ำทุก 500ms)
  setInterval(() => {
    if (!overlay) return;
    const isVisible = overlay.style.display === "flex";
    const hasActive = overlay.classList.contains("active");
    if (isVisible && !hasActive) {
      overlay.style.display = "none";
      document.body.style.overflow = "";
    }
  }, 500);

  // ฟังก์ชันปิด
  function closeBox() {
    if (!overlay || !img) return;
    overlay.style.display = "none";
    overlay.classList.remove("active");
    img.src = "";
    document.body.style.overflow = "";
  }
});
