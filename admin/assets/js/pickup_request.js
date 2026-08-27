import { getEl, getInput, getSelect } from './shared.js';
function showAssignWorkerModal(request) {
    const modal = getEl('assignWorkerModal');
    getInput('assign_request_id').value = String(request.request_id ?? '');
    const info = getEl('assign_request_info');
    info.textContent = `#${String(request.request_id)} - ${String(request.resident_name)} - ${String(request.type_name)}`;
    modal.classList.remove('hidden', 'modal-hidden');
    modal.classList.add('flex', 'modal-visible');
}
function hideAssignWorkerModal() {
    const modal = getEl('assignWorkerModal');
    modal.classList.add('hidden', 'modal-hidden');
    modal.classList.remove('flex', 'modal-visible');
}
function showCompleteModal(request) {
    const modal = getEl('completeModal');
    getInput('complete_request_id').value = String(request.request_id ?? '');
    const info = getEl('complete_request_info');
    info.textContent = `#${String(request.request_id)} - ${String(request.resident_name)} - ${String(request.type_name)} (Est: ${String(request.weight_kg)}kg)`;
    modal.classList.remove('hidden', 'modal-hidden');
    modal.classList.add('flex', 'modal-visible');
}
function hideCompleteModal() {
    const modal = getEl('completeModal');
    modal.classList.add('hidden', 'modal-hidden');
    modal.classList.remove('flex', 'modal-visible');
}
function showStatusModal(request) {
    const modal = getEl('statusModal');
    getInput('status_request_id').value = String(request.request_id ?? '');
    const info = getEl('status_request_info');
    info.textContent = `#${String(request.request_id)} - ${String(request.resident_name)} - ${String(request.type_name)}`;
    getSelect('status_select').value = String(request.status ?? '');
    modal.classList.remove('hidden', 'modal-hidden');
    modal.classList.add('flex', 'modal-visible');
}
function hideStatusModal() {
    const modal = getEl('statusModal');
    modal.classList.add('hidden', 'modal-hidden');
    modal.classList.remove('flex', 'modal-visible');
}
document.addEventListener('DOMContentLoaded', () => {
    ['assignWorkerModal', 'completeModal', 'statusModal'].forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === this) {
                    this.classList.add('hidden', 'modal-hidden');
                    this.classList.remove('flex', 'modal-visible');
                }
            });
        }
    });
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
Object.assign(window, {
    showAssignWorkerModal,
    hideAssignWorkerModal,
    showCompleteModal,
    hideCompleteModal,
    showStatusModal,
    hideStatusModal
});
//# sourceMappingURL=pickup_request.js.map