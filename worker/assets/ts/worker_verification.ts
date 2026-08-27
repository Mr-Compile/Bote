import { selectElement } from './shared.js';

function openVerifyModal(dropoffId: number): void {
    const dropoffInput = selectElement<HTMLInputElement>('verify_dropoff_id');
    const modal = selectElement<HTMLDivElement>('verifyModal');
    if (dropoffInput) {
        dropoffInput.value = String(dropoffId);
    }
    if (modal) {
        modal.classList.remove('hidden');
    }
}

function closeVerifyModal(): void {
    const modal = selectElement<HTMLDivElement>('verifyModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

function openRejectModal(dropoffId: number): void {
    const dropoffInput = selectElement<HTMLInputElement>('reject_dropoff_id');
    const modal = selectElement<HTMLDivElement>('rejectModal');
    if (dropoffInput) {
        dropoffInput.value = String(dropoffId);
    }
    if (modal) {
        modal.classList.remove('hidden');
    }
}

function closeRejectModal(): void {
    const modal = selectElement<HTMLDivElement>('rejectModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    const verifyModal = selectElement<HTMLDivElement>('verifyModal');
    if (verifyModal) {
        verifyModal.addEventListener('click', (e) => {
            if (e.target === verifyModal) {
                closeVerifyModal();
            }
        });
    }

    const rejectModal = selectElement<HTMLDivElement>('rejectModal');
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

export {};
