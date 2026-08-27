import { selectElement } from '../shared/dom.js';
function createSidebar() {
    return {
        modal: null,
        init() {
            this.modal = selectElement('logoutModal');
            this.bindEvents();
            this.highlightCurrentPage();
            this.refreshIcons();
        },
        showLogoutModal() {
            if (!this.modal)
                return;
            this.modal.classList.remove('hidden');
            this.modal.classList.add('flex');
            setTimeout(() => {
                const inner = this.modal?.querySelector('div > div');
                if (inner) {
                    inner.classList.replace('scale-95', 'scale-100');
                }
            }, 10);
        },
        hideLogoutModal() {
            if (!this.modal)
                return;
            const inner = this.modal.querySelector('div > div');
            if (inner) {
                inner.classList.replace('scale-100', 'scale-95');
            }
            setTimeout(() => {
                this.modal?.classList.add('hidden');
                this.modal?.classList.remove('flex');
            }, 300);
        },
        confirmLogout() {
            window.location.href = '../logout.php';
        },
        highlightCurrentPage() {
            const currentPage = window.location.pathname.split('/').pop() ?? '';
            document.querySelectorAll('nav a[href]').forEach((link) => {
                const href = link.getAttribute('href');
                if (href === currentPage) {
                    link.classList.add('sidebar-active');
                    link.querySelector('div')?.classList.remove('bg-' + this.guessColor(link) + '-100');
                }
            });
        },
        guessColor(link) {
            const classes = link.querySelector('div')?.className ?? '';
            const match = classes.match(/bg-([\w-]+)-100/);
            return match ? match[1] : 'bote-green';
        },
        refreshIcons() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
            else {
                setTimeout(() => this.refreshIcons(), 1000);
            }
        },
        bindEvents() {
            if (this.modal) {
                this.modal.addEventListener('click', (e) => {
                    if (e.target === this.modal)
                        this.hideLogoutModal();
                });
            }
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.modal && !this.modal.classList.contains('hidden')) {
                    this.hideLogoutModal();
                }
            });
        },
    };
}
const BoteApp = createSidebar();
document.addEventListener('DOMContentLoaded', () => {
    BoteApp.init();
});
Object.assign(window, {
    BoteApp: {
        showLogoutModal: () => BoteApp.showLogoutModal(),
        hideLogoutModal: () => BoteApp.hideLogoutModal(),
        confirmLogout: () => BoteApp.confirmLogout(),
    },
});
//# sourceMappingURL=sidebar.js.map