import { selectElement } from './shared.js';

let selectedFile: File | null = null;

function goToDashboard(): void {
    window.location.href = 'worker_dashboard.php';
}

function triggerPhotoInput(): void {
    const input = selectElement<HTMLInputElement>('photoInput');
    if (input) {
        input.click();
    }
}

function showPhotoModal(): void {
    const modal = selectElement<HTMLDivElement>('photoModal');
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

function hidePhotoModal(): void {
    const modal = selectElement<HTMLDivElement>('photoModal');
    const photoInput = selectElement<HTMLInputElement>('photoInput');
    const photoPreview = selectElement<HTMLDivElement>('photoPreview');
    const uploadBtn = selectElement<HTMLButtonElement>('uploadBtn');

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

    if (photoInput) {
        photoInput.value = '';
    }
    if (photoPreview) {
        photoPreview.classList.add('hidden');
    }
    if (uploadBtn) {
        uploadBtn.disabled = true;
    }
    selectedFile = null;
}

function handlePhotoSelect(event: Event): void {
    const input = event.target as HTMLInputElement;
    const files = input.files;
    if (!files || files.length === 0) return;

    const file = files[0];
    if (!file) return;

    if (!file.type.startsWith('image/')) {
        // eslint-disable-next-line no-alert
        alert('Please select an image file');
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        // eslint-disable-next-line no-alert
        alert('File size must be less than 5MB');
        return;
    }

    selectedFile = file;

    const previewImage = selectElement<HTMLImageElement>('previewImage');
    const photoPreview = selectElement<HTMLDivElement>('photoPreview');
    const uploadBtn = selectElement<HTMLButtonElement>('uploadBtn');

    const reader = new FileReader();
    reader.onload = (e) => {
        if (previewImage && e.target && typeof e.target.result === 'string') {
            previewImage.src = e.target.result;
        }
        if (photoPreview) {
            photoPreview.classList.remove('hidden');
        }
        if (uploadBtn) {
            uploadBtn.disabled = false;
        }
    };
    reader.readAsDataURL(file);
}

function uploadPhoto(): void {
    if (!selectedFile) return;

    const csrfInput = document.querySelector<HTMLInputElement>('input[name="csrf_token"]');
    const uploadBtn = selectElement<HTMLButtonElement>('uploadBtn');

    if (!csrfInput || !uploadBtn) return;

    const formData = new FormData();
    formData.append('csrf_token', csrfInput.value);
    formData.append('profile_photo', selectedFile);

    uploadBtn.disabled = true;
    uploadBtn.textContent = 'Uploading...';

    fetch('../shared/upload_photo.php', {
        method: 'POST',
        body: formData
    })
    .then((response) => response.json())
    .then((data: { success?: boolean; message?: string }) => {
        if (data.success) {
            // eslint-disable-next-line no-alert
            alert('Profile photo updated successfully!');
            location.reload();
        } else {
            // eslint-disable-next-line no-alert
            alert('Upload failed: ' + (data.message ?? 'Unknown error'));
            uploadBtn.disabled = false;
            uploadBtn.textContent = 'Upload';
        }
    })
    .catch((error: unknown) => {
        console.error('Error:', error);
        // eslint-disable-next-line no-alert
        alert('An error occurred during upload');
        uploadBtn.disabled = false;
        uploadBtn.textContent = 'Upload';
    });
}

document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    const photoInput = selectElement<HTMLInputElement>('photoInput');
    if (photoInput) {
        photoInput.addEventListener('change', handlePhotoSelect);
    }

    const modal = selectElement<HTMLDivElement>('photoModal');
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                hidePhotoModal();
            }
        });
    }
});

window.goToDashboard = goToDashboard;
window.triggerPhotoInput = triggerPhotoInput;
window.showPhotoModal = showPhotoModal;
window.hidePhotoModal = hidePhotoModal;
window.uploadPhoto = uploadPhoto;

export {};
