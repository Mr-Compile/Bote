/// <reference types="leaflet" />
import { getEl, getInput, getPageData, getTextArea } from './shared.js';
let map = null;
let marker = null;
let searchTimeout = null;
let pendingLocation = null;
let selectedFile = null;
const data = getPageData('admin-profile-data');
function initializeMap() {
    try {
        const defaultLat = data?.user?.latitude ?? 6.4217;
        const defaultLng = data?.user?.longitude ?? 124.7801;
        map = L.map('map').setView([defaultLat, defaultLng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);
        if (data?.user?.latitude !== null && data?.user?.longitude !== null) {
            addMarker(defaultLat, defaultLng);
        }
        map.on('click', (e) => {
            pendingLocation = { lat: e.latlng.lat, lng: e.latlng.lng };
            reverseGeocode(e.latlng.lat, e.latlng.lng, (address) => {
                showLocationConfirmation(e.latlng.lat, e.latlng.lng, address);
            });
        });
        map.whenReady(() => {
            setTimeout(() => {
                map?.invalidateSize();
            }, 100);
        });
    }
    catch (error) {
        console.error('Error initializing map:', error);
        showNotification('Error initializing map. Please refresh the page.', 'error');
    }
}
function addMarker(lat, lng) {
    if (!map)
        return;
    if (marker) {
        map.removeLayer(marker);
    }
    const greenIcon = L.divIcon({
        className: 'custom-marker',
        html: `<div style="background-color: #10b981; width: 24px; height: 24px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center;">
                 <div style="background-color: white; width: 8px; height: 8px; border-radius: 50%;"></div>
               </div>`,
        iconSize: [24, 24],
        iconAnchor: [12, 12]
    });
    marker = L.marker([lat, lng], { icon: greenIcon }).addTo(map);
    marker.bindPopup(`<div class="text-center p-2">
                        <strong>Your Home Location</strong><br>
                        <small>Lat: ${lat.toFixed(6)}<br>Lng: ${lng.toFixed(6)}</small>
                      </div>`).openPopup();
}
function updateCoordinates(lat, lng) {
    getInput('latitude').value = lat.toFixed(6);
    getInput('longitude').value = lng.toFixed(6);
    getEl('latitude-display').textContent = lat.toFixed(6);
    getEl('longitude-display').textContent = lng.toFixed(6);
}
function centerMap() {
    if (!map)
        return;
    const lat = parseFloat(getInput('latitude').value);
    const lng = parseFloat(getInput('longitude').value);
    if (lat && lng) {
        map.setView([lat, lng], 16);
        marker?.openPopup();
    }
    else {
        map.setView([6.4216359, 124.7809971], 15);
        showNotification('No location set. Showing default area.', 'info');
    }
}
function searchAddress() {
    const query = getInput('address-search').value.trim();
    if (!query) {
        showNotification('Please enter an address to search', 'error');
        return;
    }
    showSearchLoader();
    const searchUrl = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5&countrycodes=PH&bounded=1&viewbox=116.9283,4.5693,126.6043,21.1217&addressdetails=1`;
    fetch(searchUrl, {
        method: 'GET',
        headers: {
            'User-Agent': 'BoteBank/1.0 (Your Application)',
            'Accept': 'application/json'
        }
    })
        .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
        .then(results => {
        hideSearchLoader();
        displaySearchResults(results);
    })
        .catch(error => {
        hideSearchLoader();
        console.error('Geocoding error:', error);
        showNotification('Error searching for address. Please check your internet connection and try again.', 'error');
        displaySearchResults([]);
    });
}
function displaySearchResults(results) {
    const resultsContainer = getEl('search-results');
    resultsContainer.innerHTML = '';
    if (results.length === 0) {
        resultsContainer.innerHTML = `
            <div class="search-result-item text-bote-stone-500 text-center py-4">
                <i data-lucide="map-pin-off" class="w-5 h-5 mx-auto mb-2 text-bote-stone-400"></i>
                <div>No results found</div>
                <div class="text-xs text-bote-stone-400 mt-1">Try a different search term</div>
            </div>
        `;
        resultsContainer.classList.remove('hidden');
        lucide.createIcons();
        return;
    }
    results.forEach(item => {
        const result = item;
        const displayName = result.display_name.length > 80 ? result.display_name.substring(0, 80) + '...' : result.display_name;
        const el = document.createElement('div');
        el.className = 'search-result-item hover:bg-bote-green-50 cursor-pointer transition-all duration-200';
        el.innerHTML = `
            <div class="flex items-start space-x-3">
                <div class="w-8 h-8 bg-bote-green-100 rounded-lg flex items-center justify-center flex-shrink-0 mt-1">
                    <i data-lucide="map-pin" class="w-4 h-4 text-bote-green-600"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-medium text-bote-stone-900 truncate">${displayName}</div>
                    <div class="text-sm text-bote-stone-500 mt-1">
                        <span class="inline-flex items-center space-x-2">
                            <span>📍 ${parseFloat(result.lat).toFixed(6)}, ${parseFloat(result.lon).toFixed(6)}</span>
                        </span>
                    </div>
                </div>
            </div>
        `;
        el.addEventListener('click', () => {
            selectSearchResult(result);
        });
        resultsContainer.appendChild(el);
    });
    resultsContainer.classList.remove('hidden');
    lucide.createIcons();
}
function selectSearchResult(result) {
    const lat = parseFloat(result.lat);
    const lng = parseFloat(result.lon);
    pendingLocation = { lat, lng, address: result.display_name };
    showLocationConfirmation(lat, lng, result.display_name);
    getEl('search-results').classList.add('hidden');
    getInput('address-search').value = '';
}
function reverseGeocode(lat, lng, callback) {
    const reverseUrl = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`;
    fetch(reverseUrl, {
        method: 'GET',
        headers: {
            'User-Agent': 'BoteBank/1.0 (Your Application)',
            'Accept': 'application/json'
        }
    })
        .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
        .then(data => {
        const address = String(data.display_name || 'Unknown location');
        if (callback) {
            callback(address);
        }
        else {
            const addressField = getTextArea('address');
            if (!addressField.value.trim() || confirm('Do you want to update the address field with the selected location?')) {
                addressField.value = address;
            }
        }
    })
        .catch(error => {
        console.error('Reverse geocoding error:', error);
        if (callback) {
            callback('Unknown location');
        }
    });
}
function showSearchLoader() {
    const searchButton = document.querySelector('button[onclick="searchAddress()"]');
    if (!searchButton)
        return;
    const icon = searchButton.querySelector('i');
    const text = searchButton.querySelector('span');
    searchButton.disabled = true;
    searchButton.classList.add('opacity-75');
    if (icon) {
        icon.setAttribute('data-lucide', 'loader-2');
        icon.classList.add('animate-spin');
    }
    if (text) {
        text.textContent = 'Searching...';
    }
    lucide.createIcons();
}
function hideSearchLoader() {
    const searchButton = document.querySelector('button[onclick="searchAddress()"]');
    if (!searchButton)
        return;
    const icon = searchButton.querySelector('i');
    const text = searchButton.querySelector('span');
    searchButton.disabled = false;
    searchButton.classList.remove('opacity-75');
    if (icon) {
        icon.setAttribute('data-lucide', 'search');
        icon.classList.remove('animate-spin');
    }
    if (text) {
        text.textContent = 'Search';
    }
    lucide.createIcons();
}
function testSearch() {
    const testQuery = 'Mabini, Davao del Sur, Philippines';
    getInput('address-search').value = testQuery;
    showNotification('Testing search with: ' + testQuery, 'info');
    const searchUrl = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(testQuery)}&limit=5&countrycodes=PH&bounded=1&viewbox=116.9283,4.5693,126.6043,21.1217&addressdetails=1`;
    fetch(searchUrl, {
        method: 'GET',
        headers: {
            'User-Agent': 'BoteBank/1.0 (Your Application)',
            'Accept': 'application/json'
        }
    })
        .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
        .then(results => {
        showNotification(`Test successful! Found ${results.length} results.`, 'success');
        displaySearchResults(results);
    })
        .catch(error => {
        console.error('Test search error:', error);
        showNotification('Test failed: ' + String(error.message), 'error');
    });
}
function showLocationConfirmation(lat, lng, address) {
    const modal = getEl('locationConfirmModal');
    const modalContent = modal.querySelector('.bg-white');
    getEl('confirm-lat').textContent = lat.toFixed(6);
    getEl('confirm-lng').textContent = lng.toFixed(6);
    getEl('confirm-address').textContent = address || 'Selected location';
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    if (modalContent) {
        setTimeout(() => {
            modalContent.classList.remove('scale-95', 'opacity-0');
            modalContent.classList.add('scale-100', 'opacity-100');
        }, 10);
    }
}
function confirmLocationUpdate() {
    if (!pendingLocation || !map)
        return;
    map.setView([pendingLocation.lat, pendingLocation.lng], 16);
    addMarker(pendingLocation.lat, pendingLocation.lng);
    updateCoordinates(pendingLocation.lat, pendingLocation.lng);
    if (pendingLocation.address) {
        const addressField = getTextArea('address');
        if (!addressField.value.trim() || confirm('Do you want to update the address field with this location?')) {
            addressField.value = pendingLocation.address;
        }
    }
    showNotification('Location updated successfully!', 'success');
    hideLocationConfirmModal();
    pendingLocation = null;
}
function cancelLocationUpdate() {
    pendingLocation = null;
    hideLocationConfirmModal();
}
function hideLocationConfirmModal() {
    const modal = getEl('locationConfirmModal');
    const modalContent = modal.querySelector('.bg-white');
    if (modalContent) {
        modalContent.classList.remove('scale-100', 'opacity-100');
        modalContent.classList.add('scale-95', 'opacity-0');
    }
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }, 300);
}
function getCurrentLocation() {
    if (!('geolocation' in navigator)) {
        showNotification('Geolocation is not supported by this browser.', 'error');
        return;
    }
    showNotification('Getting your location...', 'info');
    navigator.geolocation.getCurrentPosition((position) => {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        pendingLocation = { lat, lng };
        reverseGeocode(lat, lng, (address) => {
            showLocationConfirmation(lat, lng, address);
        });
    }, (error) => {
        console.error('Geolocation error:', error);
        showNotification('Could not get your location. Please search manually.', 'error');
    }, {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 60000
    });
}
function showPhotoModal() {
    const modal = getEl('photoModal');
    const modalContent = modal.querySelector('div > div');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    if (modalContent) {
        setTimeout(() => {
            modalContent.classList.remove('scale-95', 'opacity-0');
            modalContent.classList.add('scale-100', 'opacity-100');
        }, 10);
    }
}
function hidePhotoModal() {
    const modal = getEl('photoModal');
    const modalContent = modal.querySelector('div > div');
    if (modalContent) {
        modalContent.classList.remove('scale-100', 'opacity-100');
        modalContent.classList.add('scale-95', 'opacity-0');
    }
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }, 500);
    clearPreview();
}
function clearPreview() {
    getInput('photoInput').value = '';
    getEl('photoPreview').classList.add('hidden');
    getEl('uploadBtn').disabled = true;
    getEl('uploadBtnText').textContent = 'Upload Photo';
    selectedFile = null;
}
function handleFileSelection(file) {
    if (!file)
        return;
    if (!file.type.startsWith('image/')) {
        showNotification('Please select an image file', 'error');
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        showNotification('File size must be less than 5MB', 'error');
        return;
    }
    selectedFile = file;
    const reader = new FileReader();
    reader.onload = (e) => {
        if (!e.target?.result)
            return;
        const previewContainer = getEl('photoPreview');
        const previewImage = getEl('previewImage');
        previewImage.src = String(e.target.result);
        previewContainer.classList.remove('hidden');
        getEl('uploadBtn').disabled = false;
        setTimeout(() => {
            previewImage.style.transform = 'scale(1)';
            previewImage.style.opacity = '1';
        }, 100);
    };
    reader.readAsDataURL(file);
}
function uploadPhoto() {
    if (!selectedFile)
        return;
    const formData = new FormData();
    const csrfInput = document.querySelector('input[name="csrf_token"]');
    if (csrfInput) {
        formData.append('csrf_token', csrfInput.value);
    }
    formData.append('profile_photo', selectedFile);
    formData.append('user_role', 'admin');
    const uploadBtn = getEl('uploadBtn');
    const uploadBtnText = getEl('uploadBtnText');
    uploadBtn.disabled = true;
    uploadBtnText.textContent = 'Uploading...';
    uploadBtn.classList.add('animate-pulse');
    fetch('../shared/upload_photo.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
        uploadBtn.classList.remove('animate-pulse');
        if (data.success) {
            showNotification('Profile photo updated successfully!', 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        }
        else {
            showNotification('Upload failed: ' + String(data.message), 'error');
            uploadBtn.disabled = false;
            uploadBtnText.textContent = 'Upload Photo';
        }
    })
        .catch(error => {
        console.error('Error:', error);
        uploadBtn.classList.remove('animate-pulse');
        showNotification('An error occurred during upload', 'error');
        uploadBtn.disabled = false;
        uploadBtnText.textContent = 'Upload Photo';
    });
}
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 max-w-sm w-full bg-white rounded-xl shadow-2xl border-l-4 p-4 transform translate-x-full transition-all duration-500 ${type === 'success' ? 'border-bote-green-500' :
        type === 'error' ? 'border-red-500' : 'border-bote-info-500'}`;
    notification.innerHTML = `
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 rounded-full flex items-center justify-center ${type === 'success' ? 'bg-bote-green-100' :
        type === 'error' ? 'bg-red-100' : 'bg-bote-info-100'}">
                    <i data-lucide="${type === 'success' ? 'check' :
        type === 'error' ? 'x' : 'info'}" class="w-5 h-5 ${type === 'success' ? 'text-bote-green-600' :
        type === 'error' ? 'text-red-600' : 'text-bote-info-600'}"></i>
                </div>
            </div>
            <div class="ml-3 flex-1">
                <p class="text-sm font-medium text-bote-stone-900">${message}</p>
            </div>
            <button type="button" class="ml-3 text-bote-stone-400 hover:text-bote-stone-600" data-dismiss="notification">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    `;
    const closeBtn = notification.querySelector('[data-dismiss="notification"]');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            notification.remove();
        });
    }
    document.body.appendChild(notification);
    lucide.createIcons();
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
        notification.classList.add('translate-x-0');
    }, 100);
    setTimeout(() => {
        notification.classList.remove('translate-x-0');
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 500);
    }, 5000);
}
function validateField(field) {
    const value = field.value.trim();
    const fieldName = field.name;
    const existingMessage = field.parentElement?.querySelector('.validation-message');
    if (existingMessage) {
        existingMessage.remove();
    }
    let isValid = true;
    let message = '';
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        message = 'This field is required';
    }
    else if (fieldName === 'email' && value && !isValidEmail(value)) {
        isValid = false;
        message = 'Please enter a valid email address';
    }
    else if (fieldName === 'new_password' && value && value.length < 6) {
        isValid = false;
        message = 'Password must be at least 6 characters';
    }
    else if (fieldName === 'confirm_password' && value) {
        const newPassword = getInput('new_password').value;
        if (value !== newPassword) {
            isValid = false;
            message = 'Passwords do not match';
        }
    }
    const parent = field.parentElement;
    if (!isValid && parent) {
        field.classList.add('border-red-300', 'ring-red-500');
        field.classList.remove('border-bote-stone-300');
        const messageElement = document.createElement('p');
        messageElement.className = 'validation-message text-sm text-red-600 mt-1';
        messageElement.textContent = message;
        parent.appendChild(messageElement);
    }
    else {
        field.classList.remove('border-red-300', 'ring-red-500');
        field.classList.add('border-bote-stone-300');
    }
    return isValid;
}
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}
function createPasswordStrengthIndicator(passwordField) {
    const indicator = document.createElement('div');
    indicator.id = 'password-strength';
    indicator.className = 'mt-2 hidden';
    indicator.innerHTML = `
        <div class="flex space-x-1 mb-1">
            <div class="h-2 flex-1 bg-bote-stone-200 rounded"></div>
            <div class="h-2 flex-1 bg-bote-stone-200 rounded"></div>
            <div class="h-2 flex-1 bg-bote-stone-200 rounded"></div>
            <div class="h-2 flex-1 bg-bote-stone-200 rounded"></div>
        </div>
        <p class="text-sm text-bote-stone-600"></p>
    `;
    const parent = passwordField.parentElement;
    if (parent) {
        parent.appendChild(indicator);
    }
    return indicator;
}
function calculatePasswordStrength(password) {
    let score = 0;
    if (password.length >= 6)
        score++;
    if (password.length >= 10)
        score++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password))
        score++;
    if (/\d/.test(password))
        score++;
    if (/[^a-zA-Z0-9]/.test(password))
        score++;
    return Math.min(score, 4);
}
function updatePasswordStrengthIndicator(indicator, strength) {
    const bars = indicator.querySelectorAll('.h-2');
    const text = indicator.querySelector('p');
    const colors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-bote-green-500'];
    const labels = ['Very Weak', 'Weak', 'Fair', 'Strong'];
    bars.forEach((bar, index) => {
        bar.className = `h-2 flex-1 rounded ${index < strength ? colors[strength - 1] : 'bg-bote-stone-200'}`;
    });
    if (strength > 0 && text) {
        text.textContent = `Password strength: ${labels[strength - 1]}`;
        text.className = `text-sm ${strength >= 3 ? 'text-bote-green-600' : strength >= 2 ? 'text-yellow-600' : 'text-red-600'}`;
    }
}
document.addEventListener('DOMContentLoaded', () => {
    initializeMap();
    document.querySelectorAll('.slide-up').forEach((element, index) => {
        setTimeout(() => {
            element.style.opacity = '1';
            element.style.transform = 'translateY(0)';
        }, 100 * (index + 1));
    });
    document.querySelectorAll('.float-animation').forEach(element => {
        element.style.animationDelay = Math.random() * 2 + 's';
    });
    const photoInput = getInput('photoInput');
    photoInput.addEventListener('change', (e) => {
        const target = e.target;
        handleFileSelection(target.files?.item(0) ?? null);
    });
    const dropZone = document.querySelector('button[onclick="document.getElementById(\'photoInput\').click()"]');
    if (dropZone) {
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('border-bote-green-500', 'bg-bote-green-100');
        });
        dropZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-bote-green-500', 'bg-bote-green-100');
        });
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-bote-green-500', 'bg-bote-green-100');
            const files = e.dataTransfer?.files;
            if (files && files.length > 0) {
                handleFileSelection(files.item(0));
            }
        });
    }
    getEl('photoModal').addEventListener('click', (e) => {
        const modal = getEl('photoModal');
        if (e.target === modal) {
            hidePhotoModal();
        }
    });
    document.querySelectorAll('input, textarea').forEach(input => {
        input.addEventListener('focus', function () {
            this.classList.add('ring-2', 'ring-green-500/20');
        });
        input.addEventListener('blur', function () {
            this.classList.remove('ring-2', 'ring-green-500/20');
            validateField(this);
        });
    });
    const mainForm = document.querySelector('form');
    if (mainForm) {
        mainForm.addEventListener('submit', (e) => {
            const address = getTextArea('address').value.trim();
            const latitude = getInput('latitude').value;
            const longitude = getInput('longitude').value;
            if (address && (!latitude || !longitude)) {
                e.preventDefault();
                showNotification('Please set your location on the map by searching for your address or clicking on the map.', 'error');
                getEl('map').scrollIntoView({ behavior: 'smooth' });
            }
        });
    }
    const newPasswordField = getInput('new_password');
    newPasswordField.addEventListener('input', () => {
        const password = newPasswordField.value;
        let strengthIndicator = document.getElementById('password-strength');
        if (!strengthIndicator) {
            strengthIndicator = createPasswordStrengthIndicator(newPasswordField);
        }
        if (password.length === 0) {
            strengthIndicator.style.display = 'none';
            return;
        }
        strengthIndicator.style.display = 'block';
        const strength = calculatePasswordStrength(password);
        updatePasswordStrengthIndicator(strengthIndicator, strength);
    });
    const addressSearch = getInput('address-search');
    addressSearch.addEventListener('input', () => {
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        const query = addressSearch.value.trim();
        if (query.length < 3) {
            getEl('search-results').classList.add('hidden');
            return;
        }
        searchTimeout = setTimeout(() => {
            const searchUrl = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=3&countrycodes=PH&bounded=1&viewbox=116.9283,4.5693,126.6043,21.1217&addressdetails=1`;
            fetch(searchUrl, {
                method: 'GET',
                headers: {
                    'User-Agent': 'BoteBank/1.0 (Your Application)',
                    'Accept': 'application/json'
                }
            })
                .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
                .then(results => {
                displaySearchResults(results);
            })
                .catch(error => {
                console.error('Auto-search error:', error);
            });
        }, 800);
    });
    addressSearch.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchAddress();
        }
    });
    document.addEventListener('click', (e) => {
        const searchContainer = document.querySelector('.address-search');
        if (searchContainer && !searchContainer.contains(e.target)) {
            getEl('search-results').classList.add('hidden');
        }
    });
    lucide.createIcons();
});
Object.assign(window, {
    searchAddress,
    testSearch,
    getCurrentLocation,
    centerMap,
    showPhotoModal,
    hidePhotoModal,
    clearPreview,
    uploadPhoto,
    confirmLocationUpdate,
    cancelLocationUpdate
});
//# sourceMappingURL=profile.js.map