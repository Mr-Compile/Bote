const ResidentSidebar = {
    modal: null,
    showLogoutModal() {
        if (!this.modal)
            return;
        this.modal.classList.replace('hidden', 'flex');
        setTimeout(() => {
            if (!this.modal)
                return;
            const inner = this.modal.querySelector('div > div');
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
            this.modal?.classList.replace('flex', 'hidden');
        }, 300);
    },
    confirmLogout() {
        window.location.href = '../logout.php';
    },
    init() {
        this.modal = document.getElementById('residentLogoutModal');
        if (!this.modal)
            return;
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal)
                this.hideLogoutModal();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !this.modal?.classList.contains('hidden')) {
                this.hideLogoutModal();
            }
        });
    }
};
document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    ResidentSidebar.init();
    const points = document.querySelector('.points-glow');
    if (points) {
        points.style.transform = 'scale(1.05)';
        setTimeout(() => {
            points.style.transform = 'scale(1)';
        }, 300);
    }
    document.querySelectorAll('nav a').forEach(link => {
        if (link.getAttribute('href')?.endsWith(window.location.pathname.split('/').pop() ?? '')) {
            link.classList.add('sidebar-active');
        }
    });
});
window.ResidentSidebar = ResidentSidebar;
export {};
//# sourceMappingURL=resident_sidebar.js.map