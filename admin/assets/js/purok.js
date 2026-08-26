// Initialize icons
lucide.createIcons();

// Show Add Purok Modal
function showAddPurokModal() {
    const modal = document.getElementById('addPurokModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

// Hide Add Purok Modal
function hideAddPurokModal() {
    const modal = document.getElementById('addPurokModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    // Reset form inside modal
    modal.querySelector('form').reset();
}

// Show Edit Modal
function showEditModal(purok) {
    document.getElementById('edit_purok_id').value = purok.purok_id;
    document.getElementById('edit_name').value = purok.name;
    document.getElementById('edit_description').value = purok.description || '';
    const modal = document.getElementById('editModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

// Hide Edit Modal
function hideEditModal() {
    const modal = document.getElementById('editModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Show Delete Confirmation Modal
function confirmDelete(purokId) {
    document.getElementById('delete_purok_id').value = purokId;
    const modal = document.getElementById('deleteModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

// Hide Delete Confirmation Modal
function hideDeleteModal() {
    const modal = document.getElementById('deleteModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Close modals when clicking outside
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
