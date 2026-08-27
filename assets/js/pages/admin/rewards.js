import { getEl, getInput } from '../../shared.js';
function showAddModal() {
    const modal = getEl('addModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function hideAddModal() {
    const modal = getEl('addModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
function showEditModal(reward) {
    getInput('edit_reward_id').value = String(reward.reward_id ?? '');
    getInput('edit_name').value = String(reward.name ?? '');
    getInput('edit_points').value = String(reward.points_required ?? '');
    getInput('edit_stock').value = String(reward.stock ?? '');
    getInput('edit_description').value = String(reward.description ?? '');
    const currentImageSection = getEl('currentImageSection');
    const currentImage = getEl('currentImage');
    const imageLabel = getEl('imageLabel');
    if (reward.image) {
        currentImage.src = '../uploads/' + String(reward.image);
        currentImageSection.style.display = 'block';
        imageLabel.textContent = 'Replace Image (Optional)';
    }
    else {
        currentImageSection.style.display = 'none';
        imageLabel.textContent = 'Reward Image (Optional)';
    }
    getInput('edit_reward_image').value = '';
    getEl('editImagePreview').classList.add('hidden');
    const modal = getEl('editModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function hideEditModal() {
    const modal = getEl('editModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
function confirmDelete(rewardId) {
    getInput('delete_reward_id').value = String(rewardId);
    const modal = getEl('deleteModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function hideDeleteModal() {
    const modal = getEl('deleteModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
function setupImagePreview() {
    const addImageInput = getInput('reward_image');
    const addPreview = getEl('imagePreview');
    const addPreviewImg = getEl('previewImg');
    addImageInput.addEventListener('change', (e) => {
        const target = e.target;
        const file = target.files?.item(0);
        if (!file) {
            addPreview.classList.add('hidden');
            return;
        }
        const reader = new FileReader();
        reader.onload = (ev) => {
            if (!ev.target?.result)
                return;
            addPreviewImg.src = String(ev.target.result);
            addPreview.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    });
    const editImageInput = getInput('edit_reward_image');
    const editPreview = getEl('editImagePreview');
    const editPreviewImg = getEl('editPreviewImg');
    editImageInput.addEventListener('change', (e) => {
        const target = e.target;
        const file = target.files?.item(0);
        if (!file) {
            editPreview.classList.add('hidden');
            return;
        }
        const reader = new FileReader();
        reader.onload = (ev) => {
            if (!ev.target?.result)
                return;
            editPreviewImg.src = String(ev.target.result);
            editPreview.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    });
}
document.addEventListener('DOMContentLoaded', () => {
    ['addModal', 'editModal', 'deleteModal'].forEach(modalId => {
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
    setupImagePreview();
    lucide.createIcons();
});
Object.assign(window, {
    showAddModal,
    hideAddModal,
    showEditModal,
    hideEditModal,
    confirmDelete,
    hideDeleteModal
});
//# sourceMappingURL=rewards.js.map