import { selectElement } from '../../shared.js';

function showReportModal(): void {
    const modal = selectElement<HTMLDivElement>('reportModal');
    if (!modal) return;

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    setTimeout(() => {
        const innerDiv = modal.querySelector('div > div');
        if (innerDiv) {
            innerDiv.classList.remove('scale-95');
            innerDiv.classList.add('scale-100');
        }
    }, 10);
}

function hideReportModal(): void {
    const modal = selectElement<HTMLDivElement>('reportModal');
    if (!modal) return;

    const innerDiv = modal.querySelector('div > div');
    if (innerDiv) {
        innerDiv.classList.remove('scale-100');
        innerDiv.classList.add('scale-95');
    }

    document.body.style.overflow = '';
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }, 300);
}

function viewDayDetails(date: string): void {
    // eslint-disable-next-line no-alert
    alert(`Viewing details for ${date}`);
}

document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    const modal = selectElement<HTMLDivElement>('reportModal');
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                hideReportModal();
            }
        });
    }
});

window.showReportModal = showReportModal;
window.hideReportModal = hideReportModal;
window.viewDayDetails = viewDayDetails;

export {};
