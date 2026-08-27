interface ResidentSidebarType {
    modal: HTMLElement | null;
    showLogoutModal(this: ResidentSidebarType): void;
    hideLogoutModal(this: ResidentSidebarType): void;
    confirmLogout(this: ResidentSidebarType): void;
    init(this: ResidentSidebarType): void;
}

const ResidentSidebar: ResidentSidebarType = {
    modal: null,

    showLogoutModal(this: ResidentSidebarType) {
        if (!this.modal) return;
        this.modal.classList.replace('hidden', 'flex');
        setTimeout(() => {
            if (!this.modal) return;
            const inner = this.modal.querySelector('div > div') as HTMLElement | null;
            if (inner) {
                inner.classList.replace('scale-95', 'scale-100');
            }
        }, 10);
    },

    hideLogoutModal(this: ResidentSidebarType) {
        if (!this.modal) return;
        const inner = this.modal.querySelector('div > div') as HTMLElement | null;
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

    init(this: ResidentSidebarType) {
        this.modal = document.getElementById('residentLogoutModal');

        if (!this.modal) return;

        this.modal.addEventListener('click', (e: MouseEvent) => {
            if (e.target === this.modal) this.hideLogoutModal();
        });

        document.addEventListener('keydown', (e: KeyboardEvent) => {
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

    const points = document.querySelector('.points-glow') as HTMLElement | null;
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
