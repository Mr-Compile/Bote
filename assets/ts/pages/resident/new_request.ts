import { getPageData } from '../../shared.js';

interface CollectionPointData {
    points: Array<{ pointId: number; name: string; lat: number; lng: number; description: string }>;
}

interface MaterialInfo {
    id: number;
    name: string;
    unit: string;
    pointsPerKg: number;
    pointsPerUnit: number;
}

interface CollectionPoint {
    id: number;
    name: string;
    lat: number;
    lng: number;
    description: string;
}

let selectedMaterial: MaterialInfo | null = null;
let map: LeafletMap | null = null;
let userMarker: LeafletMarker | null = null;
let collectionPointMap: LeafletMap | null = null;
let collectionPointMarker: LeafletMarker | null = null;
let selectedPickupType: string | null = null;
let selectedCollectionPoint: CollectionPoint | null = null;

const pageData = getPageData<CollectionPointData>('new-request-data');
const collectionPoints = pageData?.points ?? [];

function lucideCreateIcons() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function selectMaterial(typeId: number, name: string, unit: string, pointsPerKg: number, pointsPerUnit: number, card: HTMLElement) {
    document.querySelectorAll('.material-card').forEach(c => {
        c.classList.remove('selected');
        (c as HTMLElement).style.transform = 'scale(1)';
    });

    card.classList.add('selected');
    card.style.transform = 'scale(1.05)';

    setTimeout(() => {
        card.style.transform = 'scale(1)';
    }, 200);

    const radio = card.querySelector('input[type="radio"]') as HTMLInputElement | null;
    if (radio) radio.checked = true;

    selectedMaterial = {
        id: typeId,
        name,
        unit,
        pointsPerKg,
        pointsPerUnit
    };

    updateMaterialInputs(unit, name);
    showMaterialSelectionFeedback(name);
    calculatePoints();
    validateForm();
}

function updateMaterialInputs(unit: string, materialName: string) {
    const weightDiv = document.getElementById('weightInputDiv');
    const quantityDiv = document.getElementById('quantityInputDiv');
    const label = document.getElementById('materialInputLabel');
    const hint = document.getElementById('materialInputHint');
    const weightInput = document.getElementById('weight_input') as HTMLInputElement | null;
    const quantityInput = document.getElementById('quantity_input') as HTMLInputElement | null;

    if (!weightDiv || !quantityDiv || !label || !hint || !weightInput || !quantityInput) return;

    weightInput.value = '';
    quantityInput.value = '';

    weightInput.removeAttribute('required');
    quantityInput.removeAttribute('required');

    const iconContainer = label.previousElementSibling;
    const icon = iconContainer?.querySelector('i');

    if (unit === 'kg') {
        weightDiv.classList.remove('hidden');
        quantityDiv.classList.add('hidden');
        weightInput.setAttribute('required', 'required');

        label.textContent = `Weight for ${materialName} (kg)`;
        hint.innerHTML = '<i data-lucide="info" class="w-4 h-4 mr-2"></i>Minimum requirement: 10 kilograms';

        if (icon) icon.setAttribute('data-lucide', 'weight');
    } else if (unit === 'pcs') {
        weightDiv.classList.add('hidden');
        quantityDiv.classList.remove('hidden');
        quantityInput.setAttribute('required', 'required');

        label.textContent = `Quantity for ${materialName} (pieces)`;
        hint.innerHTML = '<i data-lucide="info" class="w-4 h-4 mr-2"></i>Minimum requirement: 30 pieces';

        if (icon) icon.setAttribute('data-lucide', 'hash');
    }

    lucideCreateIcons();
}

function selectPickupType(type: string, card: HTMLElement) {
    selectedPickupType = type;
    console.log('Selected pickup type:', type);

    document.querySelectorAll('.pickup-type-card').forEach(c => {
        c.classList.remove('selected');
    });

    card.classList.add('selected');

    let radioId: string;
    if (type === 'Home') {
        radioId = 'pickup_home';
    } else if (type === 'CollectionPoint') {
        radioId = 'pickup_collection';
    } else {
        return;
    }

    console.log('Checking radio button:', radioId);
    const radioElement = document.getElementById(radioId) as HTMLInputElement | null;
    if (radioElement) {
        radioElement.checked = true;
        console.log('Radio button checked successfully');
    } else {
        console.error('Radio button not found:', radioId);
    }

    const gpsSection = document.getElementById('gpsSection');
    const collectionPointSection = document.getElementById('collectionPointSection');

    console.log('GPS Section element:', gpsSection);
    console.log('Collection Point Section element:', collectionPointSection);

    if (type === 'Home') {
        console.log('Showing GPS section, hiding collection point section');
        if (gpsSection) {
            gpsSection.classList.remove('hidden');
            const latInput = document.getElementById('latitude') as HTMLInputElement | null;
            const lngInput = document.getElementById('longitude') as HTMLInputElement | null;
            if (latInput) latInput.setAttribute('required', 'required');
            if (lngInput) lngInput.setAttribute('required', 'required');
        }
        if (collectionPointSection) collectionPointSection.classList.add('hidden');

        document.querySelectorAll('.collection-point-card').forEach(c => {
            c.classList.remove('selected');
            const radio = c.querySelector('input[name="point_id"]') as HTMLInputElement | null;
            if (radio) {
                radio.checked = false;
                radio.removeAttribute('required');
            }
        });

        if (!map) {
            setTimeout(initializeMap, 100);
        }

        setTimeout(getCurrentLocation, 500);
    } else {
        console.log('Hiding GPS section, showing collection point section');
        if (gpsSection) gpsSection.classList.add('hidden');
        if (collectionPointSection) {
            collectionPointSection.classList.remove('hidden');
            console.log('Collection point section should now be visible');
        } else {
            console.error('Collection point section element not found!');
        }

        const latInput = document.getElementById('latitude') as HTMLInputElement | null;
        const lngInput = document.getElementById('longitude') as HTMLInputElement | null;
        if (latInput) {
            latInput.value = '';
            latInput.removeAttribute('required');
        }
        if (lngInput) {
            lngInput.value = '';
            lngInput.removeAttribute('required');
        }

        document.querySelectorAll('input[name="point_id"]').forEach(radio => {
            radio.setAttribute('required', 'required');
        });

        const gpsWarning = document.getElementById('gpsWarning');
        if (gpsWarning) gpsWarning.classList.add('hidden');
    }

    validateForm();
}

function initializeMap() {
    if (map) return;

    if (typeof L === 'undefined') {
        console.error('Leaflet is not loaded');
        return;
    }

    const defaultLat = 6.4214;
    const defaultLng = 124.7811;

    map = L.map('map').setView([defaultLat, defaultLng], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    map.on('click', (e: unknown) => {
        const event = e as { latlng: { lat: number; lng: number } };
        updateLocation(event.latlng.lat, event.latlng.lng);
    });
}

function getCurrentLocation() {
    const locationStatus = document.getElementById('locationStatus');
    const gpsWarning = document.getElementById('gpsWarning');

    if (!navigator.geolocation) {
        showLocationError('Geolocation is not supported by this browser');
        return;
    }

    if (locationStatus) {
        locationStatus.innerHTML = '<i data-lucide="loader" class="w-4 h-4 mr-2 animate-spin"></i><span>Getting your location...</span>';
    }
    if (gpsWarning) gpsWarning.classList.add('hidden');
    lucideCreateIcons();

    navigator.geolocation.getCurrentPosition(
        (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            updateLocation(lat, lng);

            if (locationStatus) {
                locationStatus.innerHTML = '<i data-lucide="check-circle" class="w-4 h-4 mr-2"></i><span class="text-bote-green-600">Location detected successfully</span>';
            }
            lucideCreateIcons();
        },
        (error) => {
            let errorMessage = 'Unable to get location';
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    errorMessage = 'Location access denied by user';
                    showGPSWarning();
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMessage = 'Location information unavailable';
                    break;
                case error.TIMEOUT:
                    errorMessage = 'Location request timed out';
                    break;
            }

            showLocationError(errorMessage);
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
}

function useUserLocation() {
    const userLatInput = document.getElementById('userLat') as HTMLInputElement | null;
    const userLngInput = document.getElementById('userLng') as HTMLInputElement | null;

    const userLat = userLatInput?.value;
    const userLng = userLngInput?.value;

    if (userLat && userLng) {
        const lat = parseFloat(userLat);
        const lng = parseFloat(userLng);
        updateLocation(lat, lng);

        const locationStatus = document.getElementById('locationStatus');
        if (locationStatus) {
            locationStatus.innerHTML = '<i data-lucide="user-check" class="w-4 h-4 mr-2"></i><span class="text-bote-info-600">Using your profile location</span>';
        }
        lucideCreateIcons();
    } else {
        showLocationError('No saved location in your profile');
    }
}

function showGPSWarning() {
    const gpsWarning = document.getElementById('gpsWarning');
    const locationStatus = document.getElementById('locationStatus');

    if (gpsWarning) gpsWarning.classList.remove('hidden');
    if (locationStatus) {
        locationStatus.innerHTML = '<i data-lucide="x-circle" class="w-4 h-4 mr-2"></i><span class="text-red-600">GPS access denied</span>';
    }
    lucideCreateIcons();
}

function showLocationError(message: string) {
    const locationStatus = document.getElementById('locationStatus');
    if (locationStatus) {
        locationStatus.innerHTML = `<i data-lucide="x-circle" class="w-4 h-4 mr-2"></i><span class="text-red-600">${message}</span>`;
    }
    lucideCreateIcons();
}

function switchToCollectionPoint() {
    const cards = document.querySelectorAll('.pickup-type-card');
    if (cards[1]) {
        (cards[1] as HTMLElement).click();
    }
}

function selectCollectionPointCard(pointId: number, name: string, lat: number, lng: number, description: string, card: HTMLElement) {
    document.querySelectorAll('.collection-point-card').forEach(c => {
        c.classList.remove('selected');
        (c as HTMLElement).style.transform = 'scale(1)';
        const radio = c.querySelector('input[name="point_id"]') as HTMLInputElement | null;
        if (radio) radio.checked = false;
    });

    card.classList.add('selected');
    card.style.transform = 'scale(1.02)';

    const radioButton = card.querySelector('input[name="point_id"]') as HTMLInputElement | null;
    if (radioButton) {
        radioButton.checked = true;
        console.log('Collection point radio checked:', pointId);
    }

    selectedCollectionPoint = { id: pointId, name, lat, lng, description };

    const selectedPointName = document.getElementById('selectedPointName');
    const selectedPointDescription = document.getElementById('selectedPointDescription');
    const selectedPointDetails = document.getElementById('selectedPointDetails');

    if (selectedPointName) selectedPointName.textContent = name;
    if (selectedPointDescription) selectedPointDescription.textContent = description || 'Ready for scheduling';
    if (selectedPointDetails) selectedPointDetails.classList.remove('hidden');

    showCollectionPointOnMap(lat, lng, name);
    calculateDistanceToPoint(lat, lng);
    validateForm();
}

function clearCollectionPointSelection() {
    selectedCollectionPoint = null;
    const selectedPointDetails = document.getElementById('selectedPointDetails');
    if (selectedPointDetails) selectedPointDetails.classList.add('hidden');

    document.querySelectorAll('.collection-point-card').forEach(c => {
        c.classList.remove('selected');
        (c as HTMLElement).style.transform = 'scale(1)';
        const radio = c.querySelector('input[name="point_id"]') as HTMLInputElement | null;
        if (radio) radio.checked = false;
    });

    hideCollectionPointMap();
    validateForm();
}

function refreshCollectionPoints(button?: HTMLElement) {
    const refreshBtn = button ?? (window.event?.currentTarget as HTMLElement | undefined);
    if (!refreshBtn) return;

    const icon = refreshBtn.querySelector('i');
    if (icon) {
        (icon as HTMLElement).style.animation = 'spin 1s linear infinite';
    }

    setTimeout(() => {
        if (icon) {
            (icon as HTMLElement).style.animation = '';
        }
        console.log('Collection points refreshed');
    }, 1000);
}

function filterCollectionPoints() {
    const searchInput = document.getElementById('collectionPointSearch') as HTMLInputElement | null;
    if (!searchInput) return;

    const searchTerm = searchInput.value.toLowerCase();
    const cards = document.querySelectorAll('.collection-point-card');

    cards.forEach(card => {
        const c = card as HTMLElement;
        const name = c.getAttribute('data-name')?.toLowerCase() ?? '';
        const description = c.getAttribute('data-description')?.toLowerCase() ?? '';

        if (name.includes(searchTerm) || description.includes(searchTerm)) {
            c.style.display = 'block';
        } else {
            c.style.display = 'none';
        }
    });
}

function sortCollectionPoints() {
    const sortSelect = document.getElementById('collectionPointSort') as HTMLSelectElement | null;
    const grid = document.getElementById('collectionPointsGrid');
    if (!sortSelect || !grid) return;

    const sortBy = sortSelect.value;
    const cards = Array.from(grid.querySelectorAll('.collection-point-card')) as HTMLElement[];

    cards.sort((a, b) => {
        switch (sortBy) {
            case 'name':
                return (a.getAttribute('data-name') ?? '').localeCompare(b.getAttribute('data-name') ?? '');
            case 'distance':
                return 0;
            case 'availability':
                return 0;
            default:
                return 0;
        }
    });

    cards.forEach(card => grid.appendChild(card));
}

function calculateDistanceToPoint(pointLat: number, pointLng: number) {
    const userLatInput = document.getElementById('userLat') as HTMLInputElement | null;
    const userLngInput = document.getElementById('userLng') as HTMLInputElement | null;

    const userLat = userLatInput ? parseFloat(userLatInput.value) : NaN;
    const userLng = userLngInput ? parseFloat(userLngInput.value) : NaN;

    const indicators = document.querySelectorAll('.distance-indicator');

    if (!Number.isNaN(userLat) && !Number.isNaN(userLng) && userLat && userLng) {
        const distance = calculateDistance(userLat, userLng, pointLat, pointLng);
        indicators.forEach(indicator => {
            indicator.textContent = `${distance.toFixed(1)} km`;
        });
    } else {
        indicators.forEach(indicator => {
            indicator.textContent = 'Location unavailable';
        });
    }
}

function calculateDistance(lat1: number, lng1: number, lat2: number, lng2: number) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLng/2) * Math.sin(dLng/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

function centerMapOnSelectedPoint() {
    if (selectedCollectionPoint && collectionPointMap) {
        collectionPointMap.setView([selectedCollectionPoint.lat, selectedCollectionPoint.lng], 16);
    }
}

function getDirectionsToPoint() {
    if (selectedCollectionPoint) {
        const url = `https://www.google.com/maps/dir/?api=1&destination=${selectedCollectionPoint.lat},${selectedCollectionPoint.lng}`;
        window.open(url, '_blank');
    }
}

function showCollectionPointOnMap(lat: number, lng: number, name: string) {
    const mapContainer = document.getElementById('collectionPointMap');
    if (!mapContainer) return;

    mapContainer.classList.remove('hidden');

    if (typeof L === 'undefined') return;

    if (!collectionPointMap) {
        collectionPointMap = L.map('collectionPointMap').setView([lat, lng], 16);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(collectionPointMap);
    } else {
        collectionPointMap.setView([lat, lng], 16);
    }

    if (collectionPointMarker) {
        collectionPointMap.removeLayer(collectionPointMarker);
    }

    collectionPointMarker = L.marker([lat, lng])
        .addTo(collectionPointMap)
        .bindPopup(`📍 ${name}`)
        .openPopup();

    setTimeout(() => {
        collectionPointMap?.invalidateSize();
    }, 100);
}

function hideCollectionPointMap() {
    const mapContainer = document.getElementById('collectionPointMap');
    if (mapContainer) {
        mapContainer.classList.add('hidden');
    }
}

function updateLocation(lat: number, lng: number) {
    const latInput = document.getElementById('latitude') as HTMLInputElement | null;
    const lngInput = document.getElementById('longitude') as HTMLInputElement | null;

    if (latInput) latInput.value = lat.toFixed(7);
    if (lngInput) lngInput.value = lng.toFixed(7);

    if (map) {
        map.setView([lat, lng], 16);

        if (userMarker) {
            map.removeLayer(userMarker);
        }

        userMarker = L.marker([lat, lng])
            .addTo(map)
            .bindPopup('📍 Pickup Location')
            .openPopup();
    }

    validateForm();
}

function showMaterialSelectionFeedback(materialName: string) {
    const feedback = document.createElement('div');
    feedback.className = 'fixed top-4 right-4 bg-bote-green-500 text-white px-6 py-3 rounded-xl shadow-lg z-50 transform translate-x-full transition-transform duration-500';
    feedback.innerHTML = `
        <div class="flex items-center">
            <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i>
            <span>Selected: ${materialName}</span>
        </div>
    `;
    document.body.appendChild(feedback);
    lucideCreateIcons();

    setTimeout(() => feedback.style.transform = 'translateX(0)', 100);
    setTimeout(() => {
        feedback.style.transform = 'translateX(100%)';
        setTimeout(() => feedback.remove(), 500);
    }, 2000);
}

function calculatePoints() {
    const pointsPreview = document.getElementById('points_preview');
    const estimatedPoints = document.getElementById('estimated_points');

    if (!pointsPreview || !estimatedPoints) return;

    if (!selectedMaterial) {
        pointsPreview.classList.add('hidden');
        return;
    }

    let inputValue = 0;
    let points = 0;

    if (selectedMaterial.unit === 'kg') {
        const weightInput = document.getElementById('weight_input') as HTMLInputElement | null;
        if (weightInput?.value) {
            inputValue = parseFloat(weightInput.value);
            points = inputValue * selectedMaterial.pointsPerKg;
        }
    } else if (selectedMaterial.unit === 'pcs') {
        const quantityInput = document.getElementById('quantity_input') as HTMLInputElement | null;
        if (quantityInput?.value) {
            inputValue = parseInt(quantityInput.value, 10);
            points = inputValue * selectedMaterial.pointsPerUnit;
        }
    }

    if (inputValue > 0 && points > 0) {
        const current = parseFloat(estimatedPoints.textContent ?? '0') || 0;
        animateNumber(estimatedPoints, current, points);
        pointsPreview.classList.remove('hidden');
        pointsPreview.style.animation = 'slideInRight 0.5s ease-out';
    } else {
        pointsPreview.classList.add('hidden');
    }
}

function animateNumber(element: HTMLElement, from: number, to: number) {
    const duration = 1000;
    const steps = 30;
    const stepValue = (to - from) / steps;
    let current = from;
    let step = 0;

    const timer = setInterval(() => {
        current += stepValue;
        element.textContent = Math.round(current).toLocaleString();
        step++;

        if (step >= steps) {
            clearInterval(timer);
            element.textContent = to.toLocaleString();
        }
    }, duration / steps);
}

function validateForm() {
    const submitBtn = document.getElementById('submitBtn') as HTMLButtonElement | null;
    let isValid = true;

    if (!selectedMaterial) {
        isValid = false;
    } else {
        if (selectedMaterial.unit === 'kg') {
            const weightInput = document.getElementById('weight_input') as HTMLInputElement | null;
            if (!weightInput?.value || parseFloat(weightInput.value) <= 0) {
                isValid = false;
            } else if (parseFloat(weightInput.value) < 10) {
                isValid = false;
                weightInput.classList.add('border-red-500');
                weightInput.focus();
            } else {
                weightInput.classList.remove('border-red-500');
            }
        } else if (selectedMaterial.unit === 'pcs') {
            const quantityInput = document.getElementById('quantity_input') as HTMLInputElement | null;
            if (!quantityInput?.value || parseInt(quantityInput.value, 10) <= 0) {
                isValid = false;
            } else if (parseInt(quantityInput.value, 10) < 30) {
                isValid = false;
                quantityInput.classList.add('border-red-500');
                quantityInput.focus();
            } else {
                quantityInput.classList.remove('border-red-500');
            }
        }
    }

    const pickupTypeRadios = document.querySelectorAll('input[name="pickup_type"]:checked') as NodeListOf<HTMLInputElement>;
    if (pickupTypeRadios.length === 0) {
        isValid = false;
    }

    const dateInput = document.querySelector('[name="preferred_date"]') as HTMLInputElement | null;
    if (!dateInput?.value) {
        isValid = false;
    }

    const selectedPickupTypeValue = pickupTypeRadios.length > 0 ? pickupTypeRadios[0].value : null;

    if (selectedPickupTypeValue === 'Home') {
        const latInput = document.getElementById('latitude') as HTMLInputElement | null;
        const lngInput = document.getElementById('longitude') as HTMLInputElement | null;
        if (!latInput?.value || !lngInput?.value) {
            isValid = false;
        }

        document.querySelectorAll('input[name="point_id"]').forEach(radio => {
            (radio as HTMLInputElement).checked = false;
        });
    } else if (selectedPickupTypeValue === 'CollectionPoint') {
        const pointRadios = document.querySelectorAll('input[name="point_id"]:checked');
        if (pointRadios.length === 0) {
            isValid = false;
        } else {
            const selectedPointCard = document.querySelector('.collection-point-card.selected') as HTMLElement | null;
            if (selectedPointCard) {
                const lat = parseFloat(selectedPointCard.getAttribute('data-lat') ?? '');
                const lng = parseFloat(selectedPointCard.getAttribute('data-lng') ?? '');

                if (!Number.isNaN(lat) && !Number.isNaN(lng) && lat && lng) {
                    calculateDistanceToPoint(lat, lng);
                }
            }
        }

        const latInput = document.getElementById('latitude') as HTMLInputElement | null;
        const lngInput = document.getElementById('longitude') as HTMLInputElement | null;
        if (latInput) latInput.value = '';
        if (lngInput) lngInput.value = '';
    }

    if (submitBtn) {
        submitBtn.classList.toggle('opacity-50', !isValid);
        submitBtn.classList.toggle('cursor-not-allowed', !isValid);
    }

    return isValid;
}

function validateFormComplete() {
    const isValid = validateForm();

    if (!isValid) {
        let errorMessage = 'Please complete all required fields:';
        const errors: string[] = [];

        if (!selectedMaterial) {
            errors.push('Select a material type');
        } else {
            if (selectedMaterial.unit === 'kg') {
                const weightInput = document.getElementById('weight_input') as HTMLInputElement | null;
                if (!weightInput?.value) {
                    errors.push('Enter weight in kilograms');
                } else if (parseFloat(weightInput.value) < 10) {
                    errors.push('Minimum weight requirement is 10 kilograms');
                }
            } else if (selectedMaterial.unit === 'pcs') {
                const quantityInput = document.getElementById('quantity_input') as HTMLInputElement | null;
                if (!quantityInput?.value) {
                    errors.push('Enter quantity in pieces');
                } else if (parseInt(quantityInput.value, 10) < 30) {
                    errors.push('Minimum quantity requirement is 30 pieces');
                }
            }
        }

        const pickupTypeRadios = document.querySelectorAll('input[name="pickup_type"]:checked') as NodeListOf<HTMLInputElement>;
        if (pickupTypeRadios.length === 0) {
            errors.push('Choose pickup method (Home or Collection Point)');
        } else {
            const selectedPickupTypeValue = pickupTypeRadios[0].value;

            if (selectedPickupTypeValue === 'Home') {
                const latInput = document.getElementById('latitude') as HTMLInputElement | null;
                const lngInput = document.getElementById('longitude') as HTMLInputElement | null;
                if (!latInput?.value || !lngInput?.value) {
                    errors.push('Get your GPS location for home pickup');
                }
            } else if (selectedPickupTypeValue === 'CollectionPoint') {
                const pointRadios = document.querySelectorAll('input[name="point_id"]:checked');
                if (pointRadios.length === 0) {
                    errors.push('Select a collection point for pickup');
                }
                const latInput = document.getElementById('latitude') as HTMLInputElement | null;
                const lngInput = document.getElementById('longitude') as HTMLInputElement | null;
                if (latInput) latInput.value = '';
                if (lngInput) lngInput.value = '';
            }
        }

        const dateInput = document.querySelector('[name="preferred_date"]') as HTMLInputElement | null;
        if (!dateInput?.value) {
            errors.push('Select pickup date');
        }

        if (errors.length > 0) {
            alert(errorMessage + '\n• ' + errors.join('\n• '));
        }
    }

    return isValid;
}

function showInputError(inputElement: HTMLInputElement, message: string) {
    hideInputError(inputElement);

    const errorTooltip = document.createElement('div');
    errorTooltip.className = 'error-tooltip absolute mt-2 bg-red-50 border border-red-200 text-red-600 px-3 py-2 rounded-lg text-sm z-10 fade-in';
    errorTooltip.innerHTML = `
        <div class="flex items-center">
            <i data-lucide="alert-circle" class="w-4 h-4 mr-2"></i>
            <span>${message}</span>
        </div>
    `;

    const parent = inputElement.closest('div');
    if (parent) {
        parent.style.position = 'relative';
        parent.appendChild(errorTooltip);
    }

    lucideCreateIcons();
    inputElement.dataset.hasError = 'true';
}

function hideInputError(inputElement: HTMLInputElement) {
    const parent = inputElement.closest('div');
    const existingTooltip = parent?.querySelector('.error-tooltip');
    if (existingTooltip) {
        existingTooltip.remove();
    }
    inputElement.dataset.hasError = 'false';
}

function showWelcomeMessage() {
    const welcome = document.createElement('div');
    welcome.className = 'fixed bottom-4 left-4 bg-gradient-to-r from-bote-green-500 to-bote-green-600 text-white px-6 py-4 rounded-2xl shadow-xl z-50 transform -translate-x-full transition-transform duration-500';
    welcome.innerHTML = `
        <div class="flex items-center">
            <i data-lucide="recycle" class="w-6 h-6 mr-3"></i>
            <div>
                <div class="font-semibold">Let's make a difference!</div>
                <div class="text-sm opacity-90">Schedule your pickup and earn rewards</div>
            </div>
        </div>
    `;
    document.body.appendChild(welcome);
    lucideCreateIcons();

    setTimeout(() => welcome.style.transform = 'translateX(0)', 100);
    setTimeout(() => {
        welcome.style.transform = 'translateX(-100%)';
        setTimeout(() => welcome.remove(), 500);
    }, 4000);
}

window.selectMaterial = selectMaterial;
window.selectPickupType = selectPickupType;
window.refreshCollectionPoints = refreshCollectionPoints;
window.selectCollectionPointCard = selectCollectionPointCard;
window.clearCollectionPointSelection = clearCollectionPointSelection;
window.centerMapOnSelectedPoint = centerMapOnSelectedPoint;
window.getDirectionsToPoint = getDirectionsToPoint;
window.getCurrentLocation = getCurrentLocation;
window.useUserLocation = useUserLocation;
window.switchToCollectionPoint = switchToCollectionPoint;
window.calculatePoints = calculatePoints;
window.filterCollectionPoints = filterCollectionPoints;
window.sortCollectionPoints = sortCollectionPoints;

document.addEventListener('DOMContentLoaded', () => {
    lucideCreateIcons();

    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const minDateTime = tomorrow.toISOString().slice(0, 16);
    const dateInput = document.querySelector('[name="preferred_date"]') as HTMLInputElement | null;
    if (dateInput) dateInput.min = minDateTime;

    const weightInput = document.getElementById('weight_input') as HTMLInputElement | null;
    const quantityInput = document.getElementById('quantity_input') as HTMLInputElement | null;

    [weightInput, quantityInput].forEach(input => {
        if (!input) return;
        input.addEventListener('input', () => {
            calculatePoints();
            validateForm();
        });
        input.addEventListener('blur', validateForm);
    });

    if (dateInput) {
        dateInput.addEventListener('change', validateForm);
    }

    if (weightInput) {
        weightInput.addEventListener('input', function() {
            const input = this as HTMLInputElement;
            if (parseFloat(input.value) > 1000) {
                input.value = '1000';
            }

            if (parseFloat(input.value) < 10) {
                input.classList.add('border-red-500');
                showInputError(input, 'Minimum weight requirement is 10 kilograms');
            } else {
                input.classList.remove('border-red-500');
                hideInputError(input);
            }
        });
    }

    if (quantityInput) {
        quantityInput.addEventListener('input', function() {
            const input = this as HTMLInputElement;
            if (parseInt(input.value, 10) > 10000) {
                input.value = '10000';
            }

            if (parseInt(input.value, 10) < 30) {
                input.classList.add('border-red-500');
                showInputError(input, 'Minimum quantity requirement is 30 pieces');
            } else {
                input.classList.remove('border-red-500');
                hideInputError(input);
            }
        });
    }

    document.querySelectorAll('.material-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            const c = this as HTMLElement;
            if (!c.classList.contains('selected')) {
                c.style.transform = 'translateY(-4px) scale(1.02)';
            }
        });

        card.addEventListener('mouseleave', function() {
            const c = this as HTMLElement;
            if (!c.classList.contains('selected')) {
                c.style.transform = 'translateY(0) scale(1)';
            }
        });
    });

    setTimeout(() => {
        if (!selectedPickupType) {
            const firstCard = document.querySelector('.pickup-type-card') as HTMLElement | null;
            if (firstCard) firstCard.click();
        }
    }, 1000);

    setTimeout(() => {
        showWelcomeMessage();
    }, 1500);

    const pickupForm = document.getElementById('pickupForm') as HTMLFormElement | null;
    if (pickupForm) {
        pickupForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const pickupType = document.querySelector('input[name="pickup_type"]:checked') as HTMLInputElement | null;
            const point = document.querySelector('input[name="point_id"]:checked') as HTMLInputElement | null;
            const weight = (document.getElementById('weight_input') as HTMLInputElement | null)?.value;
            const quantity = (document.getElementById('quantity_input') as HTMLInputElement | null)?.value;

            console.log('Pickup Type:', pickupType?.value);
            console.log('Collection Point:', point?.value);
            console.log('Material Type:', selectedMaterial);
            console.log('Weight/Quantity:', selectedMaterial?.unit === 'kg' ? weight : quantity);

            if (!validateFormComplete()) {
                console.error('Form validation failed');
                return;
            }

            const submitBtn = document.getElementById('submitBtn') as HTMLButtonElement | null;
            const submitLoader = document.getElementById('submitLoader');
            const submitText = submitBtn?.querySelector('span');

            if (submitBtn) submitBtn.disabled = true;
            if (submitBtn) submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
            if (submitText) submitText.classList.add('hidden');
            if (submitLoader) submitLoader.classList.remove('hidden');
            if (submitBtn) submitBtn.classList.add('animate-pulse');

            pickupForm.submit();
        });
    }

    collectionPoints.forEach(point => {
        const card = document.querySelector(`.collection-point-card[data-point-id="${point.pointId}"]`) as HTMLElement | null;
        if (card) {
            card.setAttribute('data-lat', String(point.lat));
            card.setAttribute('data-lng', String(point.lng));
        }
    });

    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(100px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes fade-in {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .fade-in {
            animation: fade-in 0.3s ease-in-out;
        }

        .error-tooltip {
            box-shadow: 0 4px 10px rgba(229, 62, 62, 0.1);
            max-width: 90%;
        }

        input.border-red-500 {
            animation: shake 0.4s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }

        .form-input:focus {
            transform: translateY(-2px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .collection-point-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .collection-point-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .collection-point-card:hover::before {
            left: 100%;
        }

        .collection-point-card.selected {
            border-color: #3b82f6;
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.15);
            background: linear-gradient(135deg, #f8fafc 0%, #e0f2fe 100%);
        }

        .collection-point-card.selected::after {
            content: '✓';
            position: absolute;
            top: 8px;
            right: 8px;
            width: 24px;
            height: 24px;
            background: #3b82f6;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            animation: checkmark 0.3s ease-in-out;
        }

        @keyframes checkmark {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        .collection-point-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .distance-indicator {
            font-weight: 600;
            color: #059669;
        }

        #collectionPointMap {
            transition: all 0.3s ease;
        }

        #collectionPointMap:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        #collectionPointSearch:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            border-color: #3b82f6;
        }

        .collection-point-card .bg-bote-green-100 {
            animation: pulse-green 2s infinite;
        }

        @keyframes pulse-green {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        .loading-shimmer {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }

        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
    `;
    document.head.appendChild(style);
});

export {};
