import { gotoUrl, triggerFileInput } from '../../shared.js';
function showPhotoModal() {
    const modal = document.getElementById('photoModal');
    if (modal) {
        modal.classList.remove('hidden');
    }
}
function hidePhotoModal() {
    const modal = document.getElementById('photoModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}
function clearPreview() {
    const preview = document.getElementById('photoPreview');
    const img = document.getElementById('previewImage');
    const input = document.getElementById('photoInput');
    const uploadBtn = document.getElementById('uploadBtn');
    if (preview)
        preview.classList.add('hidden');
    if (img)
        img.src = '';
    if (input)
        input.value = '';
    if (uploadBtn)
        uploadBtn.disabled = true;
}
function uploadPhoto() {
    // TODO: wire to shared/upload_photo.php endpoint
    console.warn('Photo upload not yet implemented');
}
function searchAddress() {
    const input = document.getElementById('address-search');
    if (!input || !input.value.trim()) {
        return;
    }
    console.warn('Address search not yet implemented');
}
function testSearch() {
    console.warn('Test search not yet implemented');
}
function getCurrentLocation() {
    if (!navigator.geolocation) {
        console.warn('Geolocation is not supported by this browser');
        return;
    }
    navigator.geolocation.getCurrentPosition((position) => {
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');
        const latDisplay = document.getElementById('latitude-display');
        const lngDisplay = document.getElementById('longitude-display');
        if (latInput)
            latInput.value = String(position.coords.latitude);
        if (lngInput)
            lngInput.value = String(position.coords.longitude);
        if (latDisplay)
            latDisplay.textContent = position.coords.latitude.toFixed(6);
        if (lngDisplay)
            lngDisplay.textContent = position.coords.longitude.toFixed(6);
    }, (error) => {
        console.error('Geolocation error:', error.message);
    });
}
function centerMap() {
    // TODO: center Leaflet map on the selected coordinates
    console.warn('Map centering not yet implemented');
}
function cancelLocationUpdate() {
    const modal = document.getElementById('locationConfirmModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}
function confirmLocationUpdate() {
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
//# sourceMappingURL=profile.js.map