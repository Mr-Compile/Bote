import { selectElement } from '../shared/dom.js';

interface SidebarApi {
    modal: HTMLDivElement | null;
    init(): void;
    showLogoutModal(): void;
    hideLogoutModal(): void;
    confirmLogout(): void;
    highlightCurrentPage(): void;
    guessColor(link: HTMLAnchorElement): string;
    refreshIcons(): void;
    bindEvents(): void;
}

function createSidebar(): SidebarApi {
    return {
        modal: null,

        init(): void {
            this.modal = selectElement<HTMLDivElement>('logoutModal');
            this.bindEvents();
            this.highlightCurrentPage();
            this.refreshIcons();
        },

        showLogoutModal(): void {
            if (!this.modal) return;
            this.modal.classList.remove('hidden');
            this.modal.classList.add('flex');
            setTimeout(() => {
                const inner = this.modal?.querySelector('div > div') as HTMLElement | null;
                if (inner) {
                    inner.classList.replace('scale-95', 'scale-100');
                }
            }, 10);
        },

        hideLogoutModal(): void {
            if (!this.modal) return;
            const inner = this.modal.querySelector('div > div') as HTMLElement | null;
            if (inner) {
                inner.classList.replace('scale-100', 'scale-95');
            }
            setTimeout(() => {
                this.modal?.classList.add('hidden');
                this.modal?.classList.remove('flex');
            }, 300);
        },

        confirmLogout(): void {
            window.location.href = '../logout.php';
        },

        highlightCurrentPage(): void {
            const currentPage = window.location.pathname.split('/').pop() ?? '';
            document.querySelectorAll<HTMLAnchorElement>('nav a[href]').forEach((link) => {
                const href = link.getAttribute('href');
                if (href === currentPage) {
                    link.classList.add('sidebar-active');
                    link.querySelector('div')?.classList.remove('bg-' + this.guessColor(link) + '-100');
                }
            });
        },

        guessColor(link: HTMLAnchorElement): string {
            const classes = link.querySelector('div')?.className ?? '';
            const match = classes.match(/bg-([\w-]+)-100/);
            return match ? match[1] : 'bote-green';
        },

        refreshIcons(): void {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            } else {
                setTimeout(() => this.refreshIcons(), 1000);
            }
        },

        bindEvents(): void {
            if (this.modal) {
                this.modal.addEventListener('click', (e: MouseEvent) => {
                    if (e.target === this.modal) this.hideLogoutModal();
                });
            }

            document.addEventListener('keydown', (e: KeyboardEvent) => {
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

export {};
