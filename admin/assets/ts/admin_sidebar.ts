document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    window.AdminSidebar = {
        modal: document.getElementById('adminLogoutModal'),

        showLogoutModal(): void {
            if (!this.modal) return;
            this.modal.classList.remove('hidden');
            this.modal.classList.add('flex');
            setTimeout(() => {
                const innerDiv = this.modal?.querySelector('div > div') as HTMLElement | null;
                if (innerDiv) {
                    innerDiv.classList.replace('scale-95', 'scale-100');
                }
            }, 10);
        },

        hideLogoutModal(): void {
            if (!this.modal) return;
            const innerDiv = this.modal.querySelector('div > div') as HTMLElement | null;
            if (innerDiv) {
                innerDiv.classList.replace('scale-100', 'scale-95');
            }
            setTimeout(() => {
                if (!this.modal) return;
                this.modal.classList.add('hidden');
                this.modal.classList.remove('flex');
            }, 300);
        },

        confirmLogout(): void {
            window.location.href = '../logout.php';
        },

        highlightCurrentPage(): void {
            const currentPage = window.location.pathname.split('/').pop();
            document.querySelectorAll<HTMLElement>('.nav-link').forEach(link => {
                if (link.getAttribute('href') === currentPage) {
                    link.classList.add('sidebar-active');
                }
            });
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

        init(): void {
            this.bindEvents();
            this.highlightCurrentPage();
        }
    };

    window.AdminSidebar.init();
});

declare global {
    interface Window {
        AdminSidebar: {
            modal: HTMLElement | null;
            showLogoutModal(): void;
            hideLogoutModal(): void;
            confirmLogout(): void;
            highlightCurrentPage(): void;
            bindEvents(): void;
            init(): void;
        };
    }
}

export {};
