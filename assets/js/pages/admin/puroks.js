import { getEl, getInput } from '../../shared.js';
function showAddPurokModal() {
    const modal = getEl('addPurokModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function hideAddPurokModal() {
    const modal = getEl('addPurokModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    const form = modal.querySelector('form');
    if (form) {
        form.reset();
    }
}
function showEditModal(purok) {
    getInput('edit_purok_id').value = String(purok.purok_id ?? '');
    getInput('edit_name').value = String(purok.name ?? '');
    getInput('edit_description').value = String(purok.description ?? '');
    const modal = getEl('editModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function hideEditModal() {
    const modal = getEl('editModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
function confirmDelete(purokId) {
    getInput('delete_purok_id').value = String(purokId);
    const modal = getEl('deleteModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function hideDeleteModal() {
    const modal = getEl('deleteModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
['addPurokModal', 'editModal', 'deleteModal'].forEach(modalId => {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.addEventListener('click', function (e) {
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
//# sourceMappingURL=puroks.js.map