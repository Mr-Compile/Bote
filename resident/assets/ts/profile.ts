import { getPageData } from './shared.js';

interface UserData {
    latitude: number;
    longitude: number;
    hasCoordinates: boolean;
}

interface NominatimResult {
    display_name: string;
    lat: string;
    lon: string;
}

interface PendingLocation {
    lat: number;
    lng: number;
    address?: string;
}

const pageData = getPageData<UserData>('profile-data');
const userData = pageData ?? { latitude: 6.4217, longitude: 124.7801, hasCoordinates: false };

let selectedFile: File | null = null;
let map: LeafletMap | null = null;
let marker: LeafletMarker | null = null;
let searchTimeout: ReturnType<typeof setTimeout> | null = null;
let pendingLocation: PendingLocation | null = null;

function initializeMap() {
    try {
        if (map) return;

        if (typeof L === 'undefined') {
            console.error('Leaflet is not loaded');
            return;
        }

        const defaultLat = userData.latitude;
        const defaultLng = userData.longitude;

        console.log('Initializing map with coordinates:', defaultLat, defaultLng);

        map = L.map('map').setView([defaultLat, defaultLng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        console.log('Map initialized successfully');

        if (userData.hasCoordinates) {
            addMarker(defaultLat, defaultLng);
            updateCoordinates(defaultLat, defaultLng);
            console.log('Added existing marker');
        }

        map.on('click', (e: unknown) => {
            const event = e as { latlng: { lat: number; lng: number } };
            const lat = event.latlng.lat;
            const lng = event.latlng.lng;

            console.log('Map clicked at:', lat, lng);

            pendingLocation = { lat, lng };

            reverseGeocode(lat, lng, (address) => {
                showLocationConfirmation(lat, lng, address);
            });
        });

        map.whenReady(() => {
            console.log('Map is ready');
            setTimeout(() => {
                map?.invalidateSize();
            }, 100);
        });
    } catch (error) {
        console.error('Error initializing map:', error);
        showNotification('Error initializing map. Please refresh the page.', 'error');
    }
}

function addMarker(lat: number, lng: number) {
    if (!map) return;

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

function updateCoordinates(lat: number, lng: number) {
    const latInput = document.getElementById('latitude') as HTMLInputElement | null;
    const lngInput = document.getElementById('longitude') as HTMLInputElement | null;
    const latDisplay = document.getElementById('latitude-display');
    const lngDisplay = document.getElementById('longitude-display');

    if (latInput) latInput.value = String(lat);
    if (lngInput) lngInput.value = String(lng);
    if (latDisplay) latDisplay.textContent = lat.toFixed(6);
    if (lngDisplay) lngDisplay.textContent = lng.toFixed(6);
}

function centerMap() {
    if (!map) return;

    const latInput = document.getElementById('latitude') as HTMLInputElement | null;
    const lngInput = document.getElementById('longitude') as HTMLInputElement | null;

    const lat = latInput ? parseFloat(latInput.value) : NaN;
    const lng = lngInput ? parseFloat(lngInput.value) : NaN;

    if (!Number.isNaN(lat) && !Number.isNaN(lng) && lat && lng) {
        map.setView([lat, lng], 16);
        if (marker) {
            marker.openPopup();
        }
    } else {
        map.setView([6.4216359, 124.7809971], 15);
        showNotification('No location set. Showing default area.', 'info');
    }
}

function searchAddress() {
    const searchInput = document.getElementById('address-search') as HTMLInputElement | null;
    if (!searchInput) return;

    const query = searchInput.value.trim();
    if (!query) {
        showNotification('Please enter an address to search', 'error');
        return;
    }

    showSearchLoader();

    const searchUrl = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5&countrycodes=PH&bounded=1&viewbox=116.9283,4.5693,126.6043,21.1217&addressdetails=1`;

    console.log('Searching for:', query);

    fetch(searchUrl, {
        method: 'GET',
        headers: {
            'User-Agent': 'BoteBank/1.0 (Your Application)',
            'Accept': 'application/json'
        }
    })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Search results:', data);
            hideSearchLoader();

            if (Array.isArray(data)) {
                displaySearchResults(data as NominatimResult[]);
            } else {
                displaySearchResults([]);
            }
        })
        .catch(error => {
            hideSearchLoader();
            console.error('Geocoding error:', error);
            showNotification('Error searching for address. Please check your internet connection and try again.', 'error');
            displaySearchResults([]);
        });
}

function displaySearchResults(results: NominatimResult[]) {
    const resultsContainer = document.getElementById('search-results');
    if (!resultsContainer) return;

    resultsContainer.innerHTML = '';

    console.log('Displaying results:', results);

    if (!results.length) {
        resultsContainer.innerHTML = `
            <div class="search-result-item text-bote-stone-500 text-center py-4">
                <i data-lucide="map-pin-off" class="w-5 h-5 mx-auto mb-2 text-bote-stone-400"></i>
                <div>No results found</div>
                <div class="text-xs text-bote-stone-400 mt-1">Try a different search term</div>
            </div>
        `;
        resultsContainer.classList.remove('hidden');
        if (typeof lucide !== 'undefined') lucide.createIcons();
        return;
    }

    results.forEach((result) => {
        const item = document.createElement('div');
        item.className = 'search-result-item hover:bg-bote-green-50 cursor-pointer transition-all duration-200';

        let displayName = result.display_name;
        if (displayName.length > 80) {
            displayName = displayName.substring(0, 80) + '...';
        }

        item.innerHTML = `
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

        item.addEventListener('click', () => {
            console.log('Selected result:', result);
            selectSearchResult(result);
        });

        resultsContainer.appendChild(item);
    });

    resultsContainer.classList.remove('hidden');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function selectSearchResult(result: NominatimResult) {
    const lat = parseFloat(result.lat);
    const lng = parseFloat(result.lon);

    pendingLocation = { lat, lng, address: result.display_name };

    showLocationConfirmation(lat, lng, result.display_name);

    const resultsContainer = document.getElementById('search-results');
    const searchInput = document.getElementById('address-search') as HTMLInputElement | null;

    if (resultsContainer) resultsContainer.classList.add('hidden');
    if (searchInput) searchInput.value = '';
}

function reverseGeocode(lat: number, lng: number, callback?: (address: string) => void) {
    console.log('Reverse geocoding:', lat, lng);

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
            console.log('Reverse geocoding result:', data);
            const address = (data as { display_name?: string }).display_name ?? 'Unknown location';
            if (callback) {
                callback(address);
            } else {
                const addressField = document.getElementById('address') as HTMLInputElement | null;
                if (addressField && (!addressField.value.trim() || confirm('Do you want to update the address field with the selected location?'))) {
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
    const searchButton = document.querySelector('button[onclick*="searchAddress"]') as HTMLButtonElement | null;
    if (!searchButton) return;

    const icon = searchButton.querySelector('i[data-lucide="search"]') as HTMLElement | null;
    const text = searchButton.querySelector('span');

    searchButton.disabled = true;
    searchButton.classList.add('opacity-75');
    if (icon) {
        icon.setAttribute('data-lucide', 'loader-2');
        icon.classList.add('animate-spin');
    }
    if (text) text.textContent = 'Searching...';
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function hideSearchLoader() {
    const searchButton = document.querySelector('button[onclick*="searchAddress"]') as HTMLButtonElement | null;
    if (!searchButton) return;

    const icon = searchButton.querySelector('i') as HTMLElement | null;
    const text = searchButton.querySelector('span');

    searchButton.disabled = false;
    searchButton.classList.remove('opacity-75');
    if (icon) {
        icon.setAttribute('data-lucide', 'search');
        icon.classList.remove('animate-spin');
    }
    if (text) text.textContent = 'Search';
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function testSearch() {
    console.log('Testing search functionality...');

    const testQuery = 'Mabini, Davao del Sur, Philippines';
    const searchInput = document.getElementById('address-search') as HTMLInputElement | null;
    if (searchInput) searchInput.value = testQuery;

    showNotification('Testing search with: ' + testQuery, 'info');

    const searchUrl = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(testQuery)}&limit=5&countrycodes=PH&bounded=1&viewbox=116.9283,4.5693,126.6043,21.1217&addressdetails=1`;

    console.log('Test search URL:', searchUrl);

    fetch(searchUrl, {
        method: 'GET',
        headers: {
            'User-Agent': 'BoteBank/1.0 (Your Application)',
            'Accept': 'application/json'
        }
    })
        .then(response => {
            console.log('Test response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Test search results:', data);
            showNotification(`Test successful! Found ${(data as NominatimResult[]).length} results.`, 'success');
            displaySearchResults(data as NominatimResult[]);
        })
        .catch(error => {
            console.error('Test search error:', error);
            showNotification('Test failed: ' + (error instanceof Error ? error.message : String(error)), 'error');
        });
}

function showLocationConfirmation(lat: number, lng: number, address: string) {
    const modal = document.getElementById('locationConfirmModal');
    if (!modal) return;

    const modalContent = modal.querySelector('.bg-white');

    const confirmLat = document.getElementById('confirm-lat');
    const confirmLng = document.getElementById('confirm-lng');
    const confirmAddress = document.getElementById('confirm-address');

    if (confirmLat) confirmLat.textContent = lat.toFixed(6);
    if (confirmLng) confirmLng.textContent = lng.toFixed(6);
    if (confirmAddress) confirmAddress.textContent = address || 'Selected location';

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    setTimeout(() => {
        if (modalContent) {
            modalContent.classList.remove('scale-95', 'opacity-0');
            modalContent.classList.add('scale-100', 'opacity-100');
        }
    }, 10);
}

function confirmLocationUpdate() {
    if (!pendingLocation || !map) return;

    addMarker(pendingLocation.lat, pendingLocation.lng);
    updateCoordinates(pendingLocation.lat, pendingLocation.lng);
    map.setView([pendingLocation.lat, pendingLocation.lng], 16);

    if (pendingLocation.address) {
        const addressField = document.getElementById('address') as HTMLInputElement | null;
        if (addressField && (!addressField.value.trim() || confirm('Do you want to update the address field with this location?'))) {
            addressField.value = pendingLocation.address;
        }
    }

    showNotification('Location updated successfully!', 'success');
    pendingLocation = null;
    hideLocationConfirmModal();
}

function cancelLocationUpdate() {
    pendingLocation = null;
    hideLocationConfirmModal();
}

function hideLocationConfirmModal() {
    const modal = document.getElementById('locationConfirmModal');
    if (!modal) return;

    const modalContent = modal.querySelector('.bg-white');
    if (modalContent) {
        modalContent.classList.remove('scale-100', 'opacity-100');
        modalContent.classList.add('scale-95', 'opacity-0');
    }

    setTimeout(() => {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }, 300);
}

function getCurrentLocation() {
    if (!('geolocation' in navigator)) {
        showNotification('Geolocation is not supported by this browser.', 'error');
        return;
    }

    showNotification('Getting your current location...', 'info');

    navigator.geolocation.getCurrentPosition(
        (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            console.log('Current location:', lat, lng);

            pendingLocation = { lat, lng };

            reverseGeocode(lat, lng, (address) => {
                pendingLocation = { lat, lng, address };
                showLocationConfirmation(lat, lng, address);
            });
        },
        (error) => {
            console.error('Geolocation error:', error);
            let errorMessage = 'Could not get your location.';
            if (error.code === 1) {
                errorMessage = 'Location access denied. Please enable location services.';
            }
            showNotification(errorMessage, 'error');
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 60000
        }
    );
}

// Initialize animations
document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    if (document.getElementById('map')) {
        initializeMap();
    } else {
        console.error('Map container not found in DOM');
    }

    const slideElements = document.querySelectorAll('.slide-up');
    slideElements.forEach((element, index) => {
        (element as HTMLElement).style.opacity = '0';
        setTimeout(() => {
            (element as HTMLElement).style.opacity = '1';
            (element as HTMLElement).style.transform = 'translateY(0)';
        }, 100 * (index + 1));
    });

    const floatingElements = document.querySelectorAll('.float-animation');
    floatingElements.forEach(element => {
        (element as HTMLElement).style.animationDelay = Math.random() * 2 + 's';
    });

    // Address search debounce
    const addressSearch = document.getElementById('address-search') as HTMLInputElement | null;
    if (addressSearch) {
        addressSearch.addEventListener('input', (e) => {
            if (searchTimeout) clearTimeout(searchTimeout);
            const query = (e.currentTarget as HTMLInputElement).value.trim();

            const searchResults = document.getElementById('search-results');
            if (query.length < 3) {
                if (searchResults) searchResults.classList.add('hidden');
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
                    .then(data => {
                        if (Array.isArray(data)) {
                            displaySearchResults(data as NominatimResult[]);
                        }
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
    }

    document.addEventListener('click', (e) => {
        const searchContainer = document.querySelector('.address-search');
        const searchResults = document.getElementById('search-results');
        if (searchContainer && searchResults && !searchContainer.contains(e.target as Node)) {
            searchResults.classList.add('hidden');
        }
    });
});

function showPhotoModal() {
    const modal = document.getElementById('photoModal');
    if (!modal) return;

    const modalContent = modal.querySelector('div > div');

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    setTimeout(() => {
        if (modalContent) {
            modalContent.classList.remove('scale-95', 'opacity-0');
            modalContent.classList.add('scale-100', 'opacity-100');
        }
    }, 10);
}

function hidePhotoModal() {
    const modal = document.getElementById('photoModal');
    if (!modal) return;

    const modalContent = modal.querySelector('div > div');
    if (modalContent) {
        modalContent.classList.remove('scale-100', 'opacity-100');
        modalContent.classList.add('scale-95', 'opacity-0');
    }

    setTimeout(() => {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }, 500);

    clearPreview();
}

function openPhotoInput() {
    const photoInput = document.getElementById('photoInput') as HTMLInputElement | null;
    if (photoInput) {
        photoInput.click();
    }
}

function clearPreview() {
    const photoInput = document.getElementById('photoInput') as HTMLInputElement | null;
    const photoPreview = document.getElementById('photoPreview');
    const uploadBtn = document.getElementById('uploadBtn') as HTMLButtonElement | null;
    const uploadBtnText = document.getElementById('uploadBtnText');

    if (photoInput) photoInput.value = '';
    if (photoPreview) photoPreview.classList.add('hidden');
    if (uploadBtn) uploadBtn.disabled = true;
    if (uploadBtnText) uploadBtnText.textContent = 'Upload Photo';
    selectedFile = null;
}

function handleFileSelection(file: File | undefined) {
    if (!file) return;

    if (!file.type.startsWith('image/')) {
        showNotification('Please select an image file (JPG, PNG, GIF)', 'error');
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        showNotification('File is too large. Maximum size is 5MB', 'error');
        return;
    }

    selectedFile = file;

    const reader = new FileReader();
    reader.onload = (e) => {
        const previewContainer = document.getElementById('photoPreview');
        const previewImage = document.getElementById('previewImage') as HTMLImageElement | null;
        const uploadBtn = document.getElementById('uploadBtn') as HTMLButtonElement | null;
        const uploadBtnText = document.getElementById('uploadBtnText');

        if (previewImage) previewImage.src = e.target?.result as string;
        if (previewContainer) previewContainer.classList.remove('hidden');
        if (uploadBtn) uploadBtn.disabled = false;
        if (uploadBtnText) uploadBtnText.textContent = 'Upload Photo';

        setTimeout(() => {
            if (previewImage) {
                previewImage.style.transform = 'scale(1)';
                previewImage.style.opacity = '1';
            }
        }, 100);
    };
    reader.readAsDataURL(file);
}

function uploadPhoto() {
    if (!selectedFile) return;

    const csrfInput = document.querySelector('input[name="csrf_token"]') as HTMLInputElement | null;
    const formData = new FormData();
    if (csrfInput) formData.append('csrf_token', csrfInput.value);
    formData.append('profile_photo', selectedFile);

    const uploadBtn = document.getElementById('uploadBtn') as HTMLButtonElement | null;
    const uploadBtnText = document.getElementById('uploadBtnText');

    if (uploadBtn) uploadBtn.disabled = true;
    if (uploadBtnText) uploadBtnText.textContent = 'Uploading...';
    if (uploadBtn) uploadBtn.classList.add('animate-pulse');

    fetch('../shared/upload_photo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (uploadBtn) uploadBtn.classList.remove('animate-pulse');

        if (data.success) {
            showNotification('Profile photo updated successfully!', 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showNotification('Upload failed: ' + data.message, 'error');
            if (uploadBtn) uploadBtn.disabled = false;
            if (uploadBtnText) uploadBtnText.textContent = 'Try Again';
        }
    })
    .catch(error => {
        console.error('Upload error:', error);
        if (uploadBtn) uploadBtn.classList.remove('animate-pulse');
        if (uploadBtn) uploadBtn.disabled = false;
        if (uploadBtnText) uploadBtnText.textContent = 'Try Again';
        showNotification('Upload failed. Please try again.', 'error');
    });
}

function showNotification(message: string, type: 'success' | 'error' | 'info' = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 max-w-sm w-full bg-white rounded-xl shadow-2xl border-l-4 p-4 transform translate-x-full transition-all duration-500 ${
        type === 'success' ? 'border-bote-green-500' :
        type === 'error' ? 'border-red-500' : 'border-bote-info-500'
    }`;

    notification.innerHTML = `
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 rounded-full flex items-center justify-center ${
                    type === 'success' ? 'bg-bote-green-100' :
                    type === 'error' ? 'bg-red-100' : 'bg-bote-info-100'
                }">
                    <i data-lucide="${
                        type === 'success' ? 'check' :
                        type === 'error' ? 'x' : 'info'
                    }" class="w-5 h-5 ${
                        type === 'success' ? 'text-bote-green-600' :
                        type === 'error' ? 'text-red-600' : 'text-bote-info-600'
                    }"></i>
                </div>
            </div>
            <div class="ml-3 flex-1">
                <p class="text-sm font-medium text-bote-stone-900">${message}</p>
            </div>
            <button type="button" class="ml-3 text-bote-stone-400 hover:text-bote-stone-600 remove-notification">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    `;

    document.body.appendChild(notification);
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    const removeBtn = notification.querySelector('.remove-notification');
    if (removeBtn) {
        removeBtn.addEventListener('click', () => {
            notification.remove();
        });
    }

    setTimeout(() => {
        notification.classList.remove('translate-x-full');
        notification.classList.add('translate-x-0');
    }, 100);

    setTimeout(() => {
        notification.classList.remove('translate-x-0');
        notification.classList.add('translate-x-full');
        setTimeout(() => notification.remove(), 500);
    }, 5000);
}

// Close modal when clicking outside
document.addEventListener('DOMContentLoaded', () => {
    const photoModal = document.getElementById('photoModal');
    if (photoModal) {
        photoModal.addEventListener('click', (e) => {
            if (e.target === photoModal) {
                hidePhotoModal();
            }
        });
    }

    const photoInput = document.getElementById('photoInput') as HTMLInputElement | null;
    if (photoInput) {
        photoInput.addEventListener('change', (e) => {
            const files = (e.currentTarget as HTMLInputElement).files;
            if (files && files.length > 0) {
                handleFileSelection(files[0]);
            }
        });
    }

    const dropZone = document.querySelector('button[onclick*="openPhotoInput"]') as HTMLElement | null;
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
                handleFileSelection(files[0]);
            }
        });
    }

    const formInputs = document.querySelectorAll('input, textarea');
    formInputs.forEach(input => {
        input.addEventListener('focus', function() {
            (this as HTMLElement).classList.add('ring-2', 'ring-green-500/20');
        });

        input.addEventListener('blur', function() {
            (this as HTMLElement).classList.remove('ring-2', 'ring-green-500/20');
            validateField(this as HTMLInputElement | HTMLTextAreaElement);
        });
    });

    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', (e) => {
            const addressField = document.getElementById('address') as HTMLInputElement | null;
            const latInput = document.getElementById('latitude') as HTMLInputElement | null;
            const lngInput = document.getElementById('longitude') as HTMLInputElement | null;

            if (addressField && addressField.value.trim() && (!latInput?.value || !lngInput?.value)) {
                e.preventDefault();
                showNotification('Please set your location on the map by searching for your address or clicking on the map.', 'error');
                const mapContainer = document.getElementById('map');
                if (mapContainer) mapContainer.scrollIntoView({ behavior: 'smooth' });
            }
        });
    }

    const newPasswordField = document.getElementById('new_password') as HTMLInputElement | null;
    if (newPasswordField) {
        newPasswordField.addEventListener('input', (e) => {
            const password = (e.currentTarget as HTMLInputElement).value;
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
    }
});

function validateField(field: HTMLInputElement | HTMLTextAreaElement) {
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
    } else if (fieldName === 'email' && value && !isValidEmail(value)) {
        isValid = false;
        message = 'Please enter a valid email address';
    } else if (fieldName === 'new_password' && value && value.length < 6) {
        isValid = false;
        message = 'Password must be at least 6 characters';
    } else if (fieldName === 'confirm_password' && value) {
        const newPassword = (document.getElementById('new_password') as HTMLInputElement | null)?.value ?? '';
        if (value !== newPassword) {
            isValid = false;
            message = 'Passwords do not match';
        }
    }

    if (!isValid) {
        field.classList.add('border-red-300', 'ring-red-500');
        field.classList.remove('border-bote-stone-300');

        const messageElement = document.createElement('p');
        messageElement.className = 'validation-message text-sm text-red-600 mt-1';
        messageElement.textContent = message;
        field.parentElement?.appendChild(messageElement);
    } else {
        field.classList.remove('border-red-300', 'ring-red-500');
        field.classList.add('border-bote-stone-300');
    }

    return isValid;
}

function isValidEmail(email: string) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function createPasswordStrengthIndicator(passwordField: HTMLInputElement) {
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
    passwordField.parentElement?.appendChild(indicator);
    return indicator;
}

function calculatePasswordStrength(password: string) {
    let score = 0;
    if (password.length >= 6) score++;
    if (password.length >= 10) score++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
    if (/\d/.test(password)) score++;
    if (/[^a-zA-Z0-9]/.test(password)) score++;

    return Math.min(score, 4);
}

function updatePasswordStrengthIndicator(indicator: HTMLElement, strength: number) {
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

function goTo(url: string) {
    window.location.href = url;
}

window.showPhotoModal = showPhotoModal;
window.hidePhotoModal = hidePhotoModal;
window.openPhotoInput = openPhotoInput;
window.clearPreview = clearPreview;
window.uploadPhoto = uploadPhoto;
window.searchAddress = searchAddress;
window.testSearch = testSearch;
window.getCurrentLocation = getCurrentLocation;
window.centerMap = centerMap;
window.confirmLocationUpdate = confirmLocationUpdate;
window.cancelLocationUpdate = cancelLocationUpdate;
window.goTo = goTo;

export {};
