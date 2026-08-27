import { selectElement } from './shared.js';
function openVerifyModal(dropoffId) {
    const dropoffInput = selectElement('verify_dropoff_id');
    const modal = selectElement('verifyModal');
    if (dropoffInput) {
        dropoffInput.value = String(dropoffId);
    }
    if (modal) {
        modal.classList.remove('hidden');
    }
}
function closeVerifyModal() {
    const modal = selectElement('verifyModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}
function openRejectModal(dropoffId) {
    const dropoffInput = selectElement('reject_dropoff_id');
    const modal = selectElement('rejectModal');
    if (dropoffInput) {
        dropoffInput.value = String(dropoffId);
    }
    if (modal) {
        modal.classList.remove('hidden');
    }
}
function closeRejectModal() {
    const modal = selectElement('rejectModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}
document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    const verifyModal = selectElement('verifyModal');
    if (verifyModal) {
        verifyModal.addEventListener('click', (e) => {
            if (e.target === verifyModal) {
                closeVerifyModal();
            }
        });
    }
    const rejectModal = selectElement('rejectModal');
    if (rejectModal) {
        rejectModal.addEventListener('click', (e) => {
            if (e.target === rejectModal) {
                closeRejectModal();
            }
        });
    }
});
window.openVerifyModal = openVerifyModal;
window.closeVerifyModal = closeVerifyModal;
window.openRejectModal = openRejectModal;
window.closeRejectModal = closeRejectModal;
//# sourceMappingURL=worker_verification.js.map