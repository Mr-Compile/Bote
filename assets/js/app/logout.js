import { selectElement } from '../shared/dom';
class LogoutController {
    modal;
    constructor() {
        this.modal = selectElement('logoutModal');
        this.bindEvents();
    }
    show() {
        if (!this.modal)
            return;
        this.modal.classList.remove('hidden');
        this.modal.classList.add('flex');
    }
    hide() {
        if (!this.modal)
            return;
        this.modal.classList.add('hidden');
        this.modal.classList.remove('flex');
    }
    bindEvents() {
        const logoutBtn = selectElement('logoutBtn');
        const cancelBtn = selectElement('cancelLogout');
        const confirmBtn = selectElement('confirmLogout');
        logoutBtn?.addEventListener('click', () => this.show());
        cancelBtn?.addEventListener('click', () => this.hide());
        confirmBtn?.addEventListener('click', () => {
            window.location.href = '../logout.php';
        });
        this.modal?.addEventListener('click', (e) => {
            if (e.target === this.modal)
                this.hide();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal && !this.modal.classList.contains('hidden')) {
                this.hide();
            }
        });
    }
}
const logout = new LogoutController();
export { logout };
//# sourceMappingURL=logout.js.map