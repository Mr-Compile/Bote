import { getEl } from './dom';
export function createModal(modalId) {
    const modal = getEl(modalId);
    const closeOnEscape = (e) => {
        if (e.key === 'Escape' && isOpen())
            hide();
    };
    const closeOnBackdrop = (e) => {
        if (e.target === modal)
            hide();
    };
    function show() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.addEventListener('keydown', closeOnEscape);
        modal.addEventListener('click', closeOnBackdrop);
    }
    function hide() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.removeEventListener('keydown', closeOnEscape);
        modal.removeEventListener('click', closeOnBackdrop);
    }
    function isOpen() {
        return !modal.classList.contains('hidden');
    }
    return { show, hide, isOpen };
}
export function bindModalTriggers(modalId, openId, closeId) {
    const modal = createModal(modalId);
    const openBtn = document.getElementById(openId);
    const closeBtn = document.getElementById(closeId);
    openBtn?.addEventListener('click', () => modal.show());
    closeBtn?.addEventListener('click', () => modal.hide());
}
//# sourceMappingURL=modal.js.map