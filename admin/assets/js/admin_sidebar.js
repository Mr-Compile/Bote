document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    window.AdminSidebar = {
        modal: document.getElementById('adminLogoutModal'),
        showLogoutModal() {
            if (!this.modal)
                return;
            this.modal.classList.remove('hidden');
            this.modal.classList.add('flex');
            setTimeout(() => {
                const innerDiv = this.modal?.querySelector('div > div');
                if (innerDiv) {
                    innerDiv.classList.replace('scale-95', 'scale-100');
                }
            }, 10);
        },
        hideLogoutModal() {
            if (!this.modal)
                return;
            const innerDiv = this.modal.querySelector('div > div');
            if (innerDiv) {
                innerDiv.classList.replace('scale-100', 'scale-95');
            }
            setTimeout(() => {
                if (!this.modal)
                    return;
                this.modal.classList.add('hidden');
                this.modal.classList.remove('flex');
            }, 300);
        },
        confirmLogout() {
            window.location.href = '../logout.php';
        },
        highlightCurrentPage() {
            const currentPage = window.location.pathname.split('/').pop();
            document.querySelectorAll('.nav-link').forEach(link => {
                if (link.getAttribute('href') === currentPage) {
                    link.classList.add('sidebar-active');
                }
            });
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
        init() {
            this.bindEvents();
            this.highlightCurrentPage();
        }
    };
    window.AdminSidebar.init();
});
export {};
//# sourceMappingURL=admin_sidebar.js.map