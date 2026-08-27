import { selectElement } from './shared.js';
function viewCollectionDetails(requestId) {
    const details = selectElement('collectionDetails');
    if (!details)
        return;
    details.innerHTML = `<p>Loading collection details for request #${requestId}...</p>`;
    showCollectionModal();
    setTimeout(() => {
        details.innerHTML = `<p>Detailed information about collection request #${requestId} would be displayed here, including photos, notes, and verification data.</p>`;
    }, 1000);
}
function showCollectionModal() {
    const modal = selectElement('collectionModal');
    if (!modal)
        return;
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
function hideCollectionModal() {
    const modal = selectElement('collectionModal');
    if (!modal)
        return;
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
document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    const modal = selectElement('collectionModal');
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                hideCollectionModal();
            }
        });
    }
});
window.viewCollectionDetails = viewCollectionDetails;
window.showCollectionModal = showCollectionModal;
window.hideCollectionModal = hideCollectionModal;
//# sourceMappingURL=worker_completed_collections.js.map