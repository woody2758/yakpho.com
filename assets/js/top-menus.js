/**
 * Top Menus Frontend Integration
 * Dynamically loads and renders header navigation
 */

document.addEventListener('DOMContentLoaded', function () {
    const topNav = document.getElementById('top-nav-menu');
    if (!topNav) return;

    // Get current language from HTML lang attribute or session
    const currentLang = document.documentElement.lang || 'th';
    const currentPage = window.location.pathname.split('/').pop().replace('.php', '') || 'index';

    async function loadTopMenus() {
        try {
            const response = await fetch(`${BASE_URL}/api/get_top_menus.php?lang=${currentLang}`);
            const data = await response.json();

            if (data.success && data.menus) {
                renderMenus(data.menus);
            }
        } catch (error) {
            console.error('Error loading top menus:', error);
        }
    }

    function renderMenus(menus) {
        topNav.innerHTML = menus.map(menu => {
            const hasChildren = menu.children && menu.children.length > 0;
            const isActive = menu.menu_link.includes(currentPage) || (currentPage === 'index' && menu.menu_link === '/');

            if (hasChildren) {
                return `
                    <li class="nav-item dropdown">
                        <a href="${BASE_URL}${menu.menu_link}" class="nav-link dropdown-toggle ${isActive ? 'active' : ''}" id="nav-${menu.menu_id}">
                            ${menu.menu_text}
                            <i data-lucide="chevron-down" class="ms-1" width="14" height="14"></i>
                        </a>
                        <ul class="dropdown-menu">
                            ${menu.children.map(child => `
                                <li><a href="${BASE_URL}${child.menu_link}" class="dropdown-item">${child.menu_text}</a></li>
                            `).join('')}
                        </ul>
                    </li>
                `;
            } else {
                return `
                    <li>
                        <a href="${BASE_URL}${menu.menu_link}" class="nav-link ${isActive ? 'active' : ''}">
                            ${menu.menu_text}
                        </a>
                    </li>
                `;
            }
        }).join('');

        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    loadTopMenus();
});
