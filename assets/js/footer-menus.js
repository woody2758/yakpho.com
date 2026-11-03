/**
 * Footer Menus Loader
 * Dynamically loads footer menu items based on language
 */

const FooterMenus = {
    /**
     * Initialize loading
     */
    init() {
        this.loadColumn('company');
        this.loadColumn('quicklinks');
        this.loadColumn('support');
        this.loadColumn('followus');
    },

    /**
     * Load specific column data
     */
    async loadColumn(column) {
        const lang = document.documentElement.lang || 'th';
        try {
            const response = await fetch(`/api/get_footer_menus.php?column=${column}&lang=${lang}`);
            const data = await response.json();

            if (data.success) {
                this.renderColumn(column, data.menus);
            }
        } catch (error) {
            console.error(`Error loading footer menu [${column}]:`, error);
        }
    },

    /**
     * Render column HTML
     */
    renderColumn(column, menus) {
        if (menus.length === 0) return;

        // Find containers for this column
        const containers = [];

        if (column === 'company') {
            const socialContainer = document.getElementById('footer-company-social');
            if (socialContainer) containers.push({ el: socialContainer, type: 'social' });

            // If we want a list for company column too
            const listContainer = document.getElementById('footer-company-menu');
            if (listContainer) containers.push({ el: listContainer, type: 'list' });
        } else if (column === 'followus') {
            const bottomSocial = document.getElementById('footer-followus-menu');
            if (bottomSocial) containers.push({ el: bottomSocial, type: 'social' });
        } else {
            const listContainer = document.getElementById(`footer-${column}-menu`);
            if (listContainer) containers.push({ el: listContainer, type: 'list' });
        }

        containers.forEach(container => {
            if (container.type === 'social') {
                container.el.innerHTML = menus.map(menu => `
                    <a href="${menu.menu_link}" 
                       class="social-icon" 
                       target="${menu.menu_target}" 
                       aria-label="${menu.menu_text}">
                        <i data-lucide="${menu.menu_icon || 'external-link'}" width="20" height="20"></i>
                    </a>
                `).join('');
            } else {
                container.el.innerHTML = menus.map(menu => `
                    <li><a href="${menu.menu_link}" target="${menu.menu_target}">${menu.menu_text}</a></li>
                `).join('');
            }
        });

        // Re-initialize icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
};

// Start loading when ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => FooterMenus.init());
} else {
    FooterMenus.init();
}
