import { getEl, getInput, getSelect } from './shared.js';

type RequestData = Record<string, unknown>;

function showAssignWorkerModal(request: RequestData): void {
    const modal = getEl<HTMLElement>('assignWorkerModal');
    getInput('assign_request_id').value = String(request.request_id ?? '');
    const info = getEl<HTMLElement>('assign_request_info');
    info.textContent = `#${String(request.request_id)} - ${String(request.resident_name)} - ${String(request.type_name)}`;
    modal.classList.remove('hidden', 'modal-hidden');
    modal.classList.add('flex', 'modal-visible');
}

function hideAssignWorkerModal(): void {
    const modal = getEl<HTMLElement>('assignWorkerModal');
    modal.classList.add('hidden', 'modal-hidden');
    modal.classList.remove('flex', 'modal-visible');
}

function showCompleteModal(request: RequestData): void {
    const modal = getEl<HTMLElement>('completeModal');
    getInput('complete_request_id').value = String(request.request_id ?? '');
    const info = getEl<HTMLElement>('complete_request_info');
    info.textContent = `#${String(request.request_id)} - ${String(request.resident_name)} - ${String(request.type_name)} (Est: ${String(request.weight_kg)}kg)`;
    modal.classList.remove('hidden', 'modal-hidden');
    modal.classList.add('flex', 'modal-visible');
}

function hideCompleteModal(): void {
    const modal = getEl<HTMLElement>('completeModal');
    modal.classList.add('hidden', 'modal-hidden');
    modal.classList.remove('flex', 'modal-visible');
}

function showStatusModal(request: RequestData): void {
    const modal = getEl<HTMLElement>('statusModal');
    getInput('status_request_id').value = String(request.request_id ?? '');
    const info = getEl<HTMLElement>('status_request_info');
    info.textContent = `#${String(request.request_id)} - ${String(request.resident_name)} - ${String(request.type_name)}`;
    getSelect('status_select').value = String(request.status ?? '');
    modal.classList.remove('hidden', 'modal-hidden');
    modal.classList.add('flex', 'modal-visible');
}

function hideStatusModal(): void {
    const modal = getEl<HTMLElement>('statusModal');
    modal.classList.add('hidden', 'modal-hidden');
    modal.classList.remove('flex', 'modal-visible');
}

document.addEventListener('DOMContentLoaded', () => {
    ['assignWorkerModal', 'completeModal', 'statusModal'].forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.addEventListener('click', function (this: HTMLElement, e: MouseEvent) {
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

export {};
