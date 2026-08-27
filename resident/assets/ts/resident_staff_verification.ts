function lucideCreateIcons() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function openVerifyModal(dropoffId: number) {
    const input = document.getElementById('verifyDropoffId') as HTMLInputElement | null;
    const modal = document.getElementById('verifyModal');

    if (input) input.value = String(dropoffId);
    if (modal) modal.classList.remove('hidden');
}

function closeVerifyModal() {
    const modal = document.getElementById('verifyModal');
    if (modal) modal.classList.add('hidden');
}

function openRejectModal(dropoffId: number) {
    const input = document.getElementById('rejectDropoffId') as HTMLInputElement | null;
    const modal = document.getElementById('rejectModal');

    if (input) input.value = String(dropoffId);
    if (modal) modal.classList.remove('hidden');
}

function closeRejectModal() {
    const modal = document.getElementById('rejectModal');
    if (modal) modal.classList.add('hidden');
}

window.openVerifyModal = openVerifyModal;
window.closeVerifyModal = closeVerifyModal;
window.openRejectModal = openRejectModal;
window.closeRejectModal = closeRejectModal;

document.addEventListener('DOMContentLoaded', () => {
    lucideCreateIcons();

    const verifyModal = document.getElementById('verifyModal');
    if (verifyModal) {
        verifyModal.addEventListener('click', (e) => {
            if (e.target === verifyModal) closeVerifyModal();
        });
    }

    const rejectModal = document.getElementById('rejectModal');
    if (rejectModal) {
        rejectModal.addEventListener('click', (e) => {
            if (e.target === rejectModal) closeRejectModal();
        });
    }
});

export {};
