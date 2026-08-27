import { getEl, getInput } from './shared.js';

type PurokData = Record<string, unknown>;

function showAddPurokModal(): void {
    const modal = getEl<HTMLElement>('addPurokModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function hideAddPurokModal(): void {
    const modal = getEl<HTMLElement>('addPurokModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');

    const form = modal.querySelector('form') as HTMLFormElement | null;
    if (form) {
        form.reset();
    }
}

function showEditModal(purok: PurokData): void {
    getInput('edit_purok_id').value = String(purok.purok_id ?? '');
    getInput('edit_name').value = String(purok.name ?? '');
    getInput('edit_description').value = String(purok.description ?? '');

    const modal = getEl<HTMLElement>('editModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function hideEditModal(): void {
    const modal = getEl<HTMLElement>('editModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function confirmDelete(purokId: number | string): void {
    getInput('delete_purok_id').value = String(purokId);
    const modal = getEl<HTMLElement>('deleteModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function hideDeleteModal(): void {
    const modal = getEl<HTMLElement>('deleteModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

['addPurokModal', 'editModal', 'deleteModal'].forEach(modalId => {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.addEventListener('click', function (this: HTMLElement, e: MouseEvent) {
            if (e.target === this) {
                this.classList.add('hidden');
                this.classList.remove('flex');
            }
        });
    }
});

lucide.createIcons();

Object.assign(window, {
    showAddPurokModal,
    hideAddPurokModal,
    showEditModal,
    hideEditModal,
    confirmDelete,
    hideDeleteModal
});

export {};
