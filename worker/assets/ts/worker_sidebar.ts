import { selectElement } from './shared.js';

// CDN load fallbacks
if (typeof tailwind === 'undefined') {
    console.warn('Tailwind CSS failed to load from CDN');
}

setTimeout(() => {
    if (typeof lucide === 'undefined') {
        console.warn('Lucide icons failed to load from CDN');
    }
}, 1000);

interface WorkerSidebarApi {
    modal: HTMLDivElement | null;
    statusInterval: ReturnType<typeof setInterval> | null;
    init(): void;
    showLogoutModal(): void;
    hideLogoutModal(): void;
    confirmLogout(): void;
    highlightCurrentPage(): void;
    updateStatus(): void;
    startStatusUpdate(): void;
    bindEvents(): void;
}

function createWorkerSidebar(): WorkerSidebarApi {
    return {
        modal: null,
        statusInterval: null,

        init(): void {
            this.modal = selectElement<HTMLDivElement>('workerLogoutModal');
            this.bindEvents();
            this.highlightCurrentPage();
            this.startStatusUpdate();
        },

        showLogoutModal(): void {
            if (!this.modal) return;

            this.modal.classList.remove('hidden');
            this.modal.classList.add('flex');

            setTimeout(() => {
                const innerDiv = this.modal?.querySelector('div > div');
                if (innerDiv) {
                    innerDiv.classList.remove('scale-95');
                    innerDiv.classList.add('scale-100');
                }
            }, 10);
        },

        hideLogoutModal(): void {
            if (!this.modal) return;

            const innerDiv = this.modal.querySelector('div > div');
            if (innerDiv) {
                innerDiv.classList.remove('scale-100');
                innerDiv.classList.add('scale-95');
            }

            setTimeout(() => {
                if (this.modal) {
                    this.modal.classList.add('hidden');
                    this.modal.classList.remove('flex');
                }
            }, 300);
        },

        confirmLogout(): void {
            try {
                if (this.statusInterval) {
                    clearInterval(this.statusInterval);
                }
                window.location.href = '../logout.php';
            } catch (error: unknown) {
                console.error('Logout redirect failed:', error);
                window.location.assign('../logout.php');
            }
        },

        highlightCurrentPage(): void {
            try {
                const currentPage = window.location.pathname.split('/').pop() ?? '';
                const links = document.querySelectorAll<HTMLAnchorElement>('nav a');

                links.forEach((link) => {
                    if (link.getAttribute('href') === currentPage) {
                        link.classList.add('sidebar-active');
                        link.classList.remove('hover:bg-bote-green-50', 'text-bote-stone-700', 'hover:text-bote-green-700');
                    }
                });
            } catch (error: unknown) {
                console.error('Error highlighting current page:', error);
            }
        },

        updateStatus(): void {
            try {
                const statusIndicator = document.querySelector<HTMLElement>('.animate-pulse');
                if (statusIndicator) {
                    statusIndicator.style.opacity = '0.5';
                    setTimeout(() => {
                        statusIndicator.style.opacity = '1';
                    }, 500);
                }
            } catch (error: unknown) {
                console.error('Error updating status:', error);
            }
        },

        startStatusUpdate(): void {
            this.statusInterval = setInterval(() => {
                this.updateStatus();
            }, 30000);
        },

        bindEvents(): void {
            if (this.modal) {
                this.modal.addEventListener('click', (e: MouseEvent) => {
                    if (e.target === this.modal) {
                        this.hideLogoutModal();
                    }
                });
            }

            document.addEventListener('keydown', (e: KeyboardEvent) => {
                if (e.key === 'Escape' && this.modal && !this.modal.classList.contains('hidden')) {
                    this.hideLogoutModal();
                }
            });

            window.addEventListener('beforeunload', () => {
                if (this.statusInterval) {
                    clearInterval(this.statusInterval);
                }
            });
        }
    };
}

const WorkerSidebar = createWorkerSidebar();

function showLogoutModal(): void {
    WorkerSidebar.showLogoutModal();
}

function hideLogoutModal(): void {
    WorkerSidebar.hideLogoutModal();
}

function confirmLogout(): void {
    WorkerSidebar.confirmLogout();
}

document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    WorkerSidebar.init();
});

(window as unknown as Record<string, unknown>)['WorkerSidebar'] = WorkerSidebar;
window.showLogoutModal = showLogoutModal;
window.hideLogoutModal = hideLogoutModal;
window.confirmLogout = confirmLogout;

export {};
