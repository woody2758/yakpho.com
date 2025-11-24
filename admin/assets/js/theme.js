/* ===========================================================
   YakPho Admin - Theme Toggle System (Dark/Light)
   File: theme.js
   =========================================================== */

function setTheme(theme) {
    document.documentElement.dataset.theme = theme;
    localStorage.setItem("yakpho-theme", theme);
    updateThemeIcon(theme);
}

function toggleTheme() {
    const current = document.documentElement.dataset.theme;
    const next = current === "dark" ? "light" : "dark";
    setTheme(next);
}

function updateThemeIcon(theme) {
    const icon = document.getElementById("theme-icon");
    if (!icon) return;
    icon.setAttribute("data-lucide", theme === "dark" ? "sun" : "moon");
    lucide.createIcons();
}

document.addEventListener("DOMContentLoaded", () => {
    const savedTheme = localStorage.getItem("yakpho-theme") || "dark";
    setTheme(savedTheme);
});
