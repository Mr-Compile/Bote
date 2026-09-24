import { selectElement } from '../shared/dom.js';

export {};

interface LogoutModal {
    show: () => void;
    hide: () => void;
    confirm: () => void;
}

class LogoutController implements LogoutModal {
    private readonly modal: HTMLDivElement | null;

    constructor() {
        this.modal = selectElement<HTMLDivElement>('logoutModal');
        this.bindEvents();
    }

    show(): void {
        if (!this.modal) return;
        this.modal.classList.remove('hidden');
        this.modal.classList.add('flex');
    }

    hide(): void {
        if (!this.modal) return;
        this.modal.classList.add('hidden');
        this.modal.classList.remove('flex');
    }

    confirm(): void {
        window.location.href = '../logout.php';
    }

    private bindEvents(): void {
        this.modal?.addEventListener('click', (e: MouseEvent) => {
            if (e.target === this.modal) this.hide();
        });

        document.addEventListener('keydown', (e: KeyboardEvent) => {
            if (e.key === 'Escape' && this.modal && !this.modal.classList.contains('hidden')) {
                this.hide();
            }
        });
    }
}

const logout = new LogoutController();

window.BoteApp = Object.assign(window.BoteApp ?? {}, {
    showLogoutModal: () => logout.show(),
    hideLogoutModal: () => logout.hide(),
    confirmLogout: () => logout.confirm(),
});

export { logout };
