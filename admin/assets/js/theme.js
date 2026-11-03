/* ===========================================================
   YakPho Admin - Theme Toggle System (Dark/Light)
   File: theme.js
   =========================================================== */

function getAdminIsDark() {
    return document.documentElement.getAttribute('data-theme') === 'dark' ||
           (document.body && document.body.getAttribute('data-theme') === 'dark') ||
           (localStorage.getItem("yakpho-theme") || "dark") === "dark";
}
window.getAdminIsDark = getAdminIsDark;

function setTheme(theme) {
    document.documentElement.dataset.theme = theme;
    document.documentElement.setAttribute("data-theme", theme);
    if (document.body) {
        document.body.dataset.theme = theme;
        document.body.setAttribute("data-theme", theme);
    }
    localStorage.setItem("yakpho-theme", theme);
    updateThemeIcon(theme);
}

function toggleTheme() {
    const current = document.documentElement.dataset.theme || localStorage.getItem("yakpho-theme") || "dark";
    const next = current === "dark" ? "light" : "dark";
    setTheme(next);
}

function updateThemeIcon(theme) {
    const icon = document.getElementById("theme-icon");
    if (!icon) return;
    icon.setAttribute("data-lucide", theme === "dark" ? "sun" : "moon");
    if (window.lucide) {
        lucide.createIcons();
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const savedTheme = localStorage.getItem("yakpho-theme") || "dark";
    setTheme(savedTheme);
});
