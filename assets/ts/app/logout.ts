import { selectElement } from '../shared/dom';

export {};

export interface LogoutModal {
    show: () => void;
    hide: () => void;
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

    private bindEvents(): void {
        const logoutBtn = selectElement<HTMLButtonElement>('logoutBtn');
        const cancelBtn = selectElement<HTMLButtonElement>('cancelLogout');
        const confirmBtn = selectElement<HTMLButtonElement>('confirmLogout');

        logoutBtn?.addEventListener('click', () => this.show());
        cancelBtn?.addEventListener('click', () => this.hide());
        confirmBtn?.addEventListener('click', () => {
            window.location.href = '../logout.php';
        });

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

export { logout };
