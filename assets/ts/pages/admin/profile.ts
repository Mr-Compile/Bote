import { gotoUrl, triggerFileInput } from '../../shared.js';

declare global {
    interface Window {
        gotoUrl: typeof gotoUrl;
        triggerFileInput: typeof triggerFileInput;
        showPhotoModal: () => void;
        hidePhotoModal: () => void;
        clearPreview: () => void;
        uploadPhoto: () => void;
        searchAddress: () => void;
        testSearch: () => void;
        getCurrentLocation: () => void;
        centerMap: () => void;
        cancelLocationUpdate: () => void;
        confirmLocationUpdate: () => void;
    }
}

function showPhotoModal(): void {
    const modal = document.getElementById('photoModal');
    if (modal) {
        modal.classList.remove('hidden');
    }
}

function hidePhotoModal(): void {
    const modal = document.getElementById('photoModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

function clearPreview(): void {
    const preview = document.getElementById('photoPreview');
    const img = document.getElementById('previewImage') as HTMLImageElement | null;
    const input = document.getElementById('photoInput') as HTMLInputElement | null;
    const uploadBtn = document.getElementById('uploadBtn') as HTMLButtonElement | null;

    if (preview) preview.classList.add('hidden');
    if (img) img.src = '';
    if (input) input.value = '';
    if (uploadBtn) uploadBtn.disabled = true;
}

function uploadPhoto(): void {
    // TODO: wire to shared/upload_photo.php endpoint
    console.warn('Photo upload not yet implemented');
}

function searchAddress(): void {
    const input = document.getElementById('address-search') as HTMLInputElement | null;
    if (!input || !input.value.trim()) {
        return;
    }
    console.warn('Address search not yet implemented');
}

function testSearch(): void {
    console.warn('Test search not yet implemented');
}

function getCurrentLocation(): void {
    if (!navigator.geolocation) {
        console.warn('Geolocation is not supported by this browser');
        return;
    }

    navigator.geolocation.getCurrentPosition(
        (position) => {
            const latInput = document.getElementById('latitude') as HTMLInputElement | null;
            const lngInput = document.getElementById('longitude') as HTMLInputElement | null;
            const latDisplay = document.getElementById('latitude-display');
            const lngDisplay = document.getElementById('longitude-display');

            if (latInput) latInput.value = String(position.coords.latitude);
            if (lngInput) lngInput.value = String(position.coords.longitude);
            if (latDisplay) latDisplay.textContent = position.coords.latitude.toFixed(6);
            if (lngDisplay) lngDisplay.textContent = position.coords.longitude.toFixed(6);
        },
        (error) => {
            console.error('Geolocation error:', error.message);
        },
    );
}

function centerMap(): void {
    // TODO: center Leaflet map on the selected coordinates
    console.warn('Map centering not yet implemented');
}

function cancelLocationUpdate(): void {
    const modal = document.getElementById('locationConfirmModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

function confirmLocationUpdate(): void {
    const modal = document.getElementById('locationConfirmModal');
    if (modal) {
        modal.classList.add('hidden');
    }
    // The form already has hidden lat/lng inputs; a real implementation would
    // submit these to the server.
}

Object.assign(window, {
    gotoUrl,
    triggerFileInput,
    showPhotoModal,
    hidePhotoModal,
    clearPreview,
    uploadPhoto,
    searchAddress,
    testSearch,
    getCurrentLocation,
    centerMap,
    cancelLocationUpdate,
    confirmLocationUpdate,
});
