import { getEl, getInput } from './shared.js';

type RewardData = Record<string, unknown>;

function showAddModal(): void {
    const modal = getEl<HTMLElement>('addModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function hideAddModal(): void {
    const modal = getEl<HTMLElement>('addModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function showEditModal(reward: RewardData): void {
    getInput('edit_reward_id').value = String(reward.reward_id ?? '');
    getInput('edit_name').value = String(reward.name ?? '');
    getInput('edit_points').value = String(reward.points_required ?? '');
    getInput('edit_stock').value = String(reward.stock ?? '');
    getInput('edit_description').value = String(reward.description ?? '');

    const currentImageSection = getEl<HTMLElement>('currentImageSection');
    const currentImage = getEl<HTMLImageElement>('currentImage');
    const imageLabel = getEl<HTMLElement>('imageLabel');

    if (reward.image) {
        currentImage.src = '../uploads/' + String(reward.image);
        currentImageSection.style.display = 'block';
        imageLabel.textContent = 'Replace Image (Optional)';
    } else {
        currentImageSection.style.display = 'none';
        imageLabel.textContent = 'Reward Image (Optional)';
    }

    getInput('edit_reward_image').value = '';
    getEl<HTMLElement>('editImagePreview').classList.add('hidden');

    const modal = getEl<HTMLElement>('editModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function hideEditModal(): void {
    const modal = getEl<HTMLElement>('editModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function confirmDelete(rewardId: number | string): void {
    getInput('delete_reward_id').value = String(rewardId);
    const modal = getEl<HTMLElement>('deleteModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function hideDeleteModal(): void {
    const modal = getEl<HTMLElement>('deleteModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function setupImagePreview(): void {
    const addImageInput = getInput('reward_image');
    const addPreview = getEl<HTMLElement>('imagePreview');
    const addPreviewImg = getEl<HTMLImageElement>('previewImg');

    addImageInput.addEventListener('change', (e: Event) => {
        const target = e.target as HTMLInputElement;
        const file = target.files?.item(0);
        if (!file) {
            addPreview.classList.add('hidden');
            return;
        }
        const reader = new FileReader();
        reader.onload = (ev: ProgressEvent<FileReader>) => {
            if (!ev.target?.result) return;
            addPreviewImg.src = String(ev.target.result);
            addPreview.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    });

    const editImageInput = getInput('edit_reward_image');
    const editPreview = getEl<HTMLElement>('editImagePreview');
    const editPreviewImg = getEl<HTMLImageElement>('editPreviewImg');

    editImageInput.addEventListener('change', (e: Event) => {
        const target = e.target as HTMLInputElement;
        const file = target.files?.item(0);
        if (!file) {
            editPreview.classList.add('hidden');
            return;
        }
        const reader = new FileReader();
        reader.onload = (ev: ProgressEvent<FileReader>) => {
            if (!ev.target?.result) return;
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
            modal.addEventListener('click', function (this: HTMLElement, e: MouseEvent) {
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

export {};
