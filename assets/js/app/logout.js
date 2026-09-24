import { selectElement } from '../shared/dom.js';
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
    confirm() {
        window.location.href = '../logout.php';
    }
    bindEvents() {
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
window.BoteApp = Object.assign(window.BoteApp ?? {}, {
    showLogoutModal: () => logout.show(),
    hideLogoutModal: () => logout.hide(),
    confirmLogout: () => logout.confirm(),
});
export { logout };
//# sourceMappingURL=logout.js.map