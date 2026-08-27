import { getEl, getInput } from '../../shared.js';
let map = null;
let modalMap = null;
let currentMarker = null;
let modalCurrentMarker = null;
let collectionPoints = [];
let editingPointId = null;
let deletePointId = null;
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal)
        return;
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
    if (modalId === 'collectionPointModal') {
        setTimeout(() => {
            initializeModalMap();
            void loadModalPuroks();
        }, 300);
    }
}
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal)
        return;
    modal.classList.remove('show');
    document.body.style.overflow = '';
    if (modalId === 'collectionPointModal') {
        resetModalForm();
        editingPointId = null;
    }
    if (modalId === 'deleteConfirmModal') {
        deletePointId = null;
    }
}
function setupModalEventListeners() {
    document.addEventListener('click', (e) => {
        const target = e.target;
        if (target.classList.contains('modal')) {
            closeModal(target.id);
        }
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal.show');
            if (openModal) {
                closeModal(openModal.id);
            }
        }
    });
}
function openAddModal() {
    getEl('modalTitle').textContent = 'Add Collection Point';
    getEl('modalSubmitText').textContent = 'Save Collection Point';
    resetModalForm();
    editingPointId = null;
    openModal('collectionPointModal');
}
function focusCollectionPointName() {
    getInput('collectionPointName').focus();
}
async function editCollectionPoint(pointId) {
    const url = `collection_points_handler.php?action=get_collection_point&id=${pointId}`;
    try {
        const response = await fetch(url);
        const responseText = await response.text();
        let data;
        try {
            data = JSON.parse(responseText);
        }
        catch (parseError) {
            console.error('JSON parse error:', parseError);
            throw new Error('Server returned invalid JSON. Response: ' + responseText.substring(0, 200));
        }
        if (data.success) {
            const point = data.data;
            editingPointId = pointId;
            getEl('modalTitle').textContent = 'Edit Collection Point';
            getEl('modalSubmitText').textContent = 'Update Collection Point';
            getInput('editPointId').value = String(point.point_id ?? '');
            getInput('modalCollectionPointName').value = String(point.name ?? '');
            getSelect('modalStatus').value = String(point.status ?? '');
            getInput('modalCapacity').value = String(point.capacity ?? '');
            getInput('modalContactPerson').value = String(point.contact_person ?? '');
            getInput('modalContactPhone').value = String(point.contact_phone ?? '');
            getInput('modalOperatingHours').value = String(point.operating_hours ?? '24/7');
            getTextArea('modalDescription').value = String(point.description ?? '');
            getTextArea('modalNotes').value = String(point.notes ?? '');
            openModal('collectionPointModal');
            setTimeout(() => {
                getSelect('modalPurok').value = String(point.purok_id ?? '');
                setModalLocation(parseFloat(String(point.latitude ?? 0)), parseFloat(String(point.longitude ?? 0)));
            }, 600);
        }
        else {
            showAlert(String(data.message ?? 'Failed to load collection point data'), 'error');
        }
    }
    catch (error) {
        console.error('Error loading collection point:', error);
        showAlert('Error loading collection point data: ' + String(error.message), 'error');
    }
}
function getTextArea(id) {
    const el = document.getElementById(id);
    if (!el)
        throw new Error(`Textarea #${id} not found`);
    return el;
}
function getSelect(id) {
    const el = document.getElementById(id);
    if (!el)
        throw new Error(`Select #${id} not found`);
    return el;
}
async function viewCollectionPoint(pointId) {
    const url = `collection_points_handler.php?action=get_collection_point&id=${pointId}`;
    try {
        const response = await fetch(url);
        const responseText = await response.text();
        let data;
        try {
            data = JSON.parse(responseText);
        }
        catch (parseError) {
            console.error('JSON parse error:', parseError);
            throw new Error('Server returned invalid JSON. Response: ' + responseText.substring(0, 200));
        }
        if (data.success) {
            const point = data.data;
            document.getElementById('editFromViewBtn')?.setAttribute('data-point-id', String(pointId));
            const status = String(point.status ?? 'unknown');
            const statusText = status.charAt(0).toUpperCase() + status.slice(1);
            const content = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div class="bg-bote-stone-50 rounded-lg p-4">
                            <h3 class="font-semibold text-bote-stone-800 mb-3 flex items-center">
                                <i data-lucide="info" class="w-5 h-5 mr-2 text-bote-info-600"></i>
                                Basic Information
                            </h3>
                            <div class="space-y-2 text-sm">
                                <div><span class="font-medium text-bote-stone-600">Name:</span> ${String(point.name ?? '')}</div>
                                <div><span class="font-medium text-bote-stone-600">Purok:</span> ${String(point.purok_name ?? 'Unknown')}</div>
                                <div><span class="font-medium text-bote-stone-600">Status:</span>
                                    <span class="status-badge status-${status}">${statusText}</span>
                                </div>
                                <div><span class="font-medium text-bote-stone-600">Capacity:</span> ${point.capacity ? String(point.capacity) + ' kg' : 'Not set'}</div>
                            </div>
                        </div>

                        <div class="bg-bote-stone-50 rounded-lg p-4">
                            <h3 class="font-semibold text-bote-stone-800 mb-3 flex items-center">
                                <i data-lucide="map-pin" class="w-5 h-5 mr-2 text-bote-green-600"></i>
                                Location
                            </h3>
                            <div class="space-y-2 text-sm">
                                <div><span class="font-medium text-bote-stone-600">Latitude:</span> <span class="font-mono">${String(point.latitude ?? '')}</span></div>
                                <div><span class="font-medium text-bote-stone-600">Longitude:</span> <span class="font-mono">${String(point.longitude ?? '')}</span></div>
                            </div>
                        </div>

                        <div class="bg-bote-stone-50 rounded-lg p-4">
                            <h3 class="font-semibold text-bote-stone-800 mb-3 flex items-center">
                                <i data-lucide="phone" class="w-5 h-5 mr-2 text-bote-info-600"></i>
                                Contact & Operations
                            </h3>
                            <div class="space-y-2 text-sm">
                                <div><span class="font-medium text-bote-stone-600">Contact Person:</span> ${String(point.contact_person ?? 'Not set')}</div>
                                <div><span class="font-medium text-bote-stone-600">Phone:</span> ${String(point.contact_phone ?? 'Not set')}</div>
                                <div><span class="font-medium text-bote-stone-600">Operating Hours:</span> ${String(point.operating_hours ?? '24/7')}</div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="bg-bote-stone-50 rounded-lg p-4">
                            <h3 class="font-semibold text-bote-stone-800 mb-3 flex items-center">
                                <i data-lucide="file-text" class="w-5 h-5 mr-2 text-bote-green-600"></i>
                                Description
                            </h3>
                            <p class="text-sm text-bote-stone-600">${String(point.description ?? 'No description provided.')}</p>
                        </div>

                        <div class="bg-bote-stone-50 rounded-lg p-4">
                            <h3 class="font-semibold text-bote-stone-800 mb-3 flex items-center">
                                <i data-lucide="clipboard" class="w-5 h-5 mr-2 text-bote-info-600"></i>
                                Notes
                            </h3>
                            <p class="text-sm text-bote-stone-600">${String(point.notes ?? 'No additional notes.')}</p>
                        </div>

                        <div class="bg-bote-stone-50 rounded-lg p-4">
                            <h3 class="font-semibold text-bote-stone-800 mb-3 flex items-center">
                                <i data-lucide="clock" class="w-5 h-5 mr-2 text-bote-stone-600"></i>
                                Timestamps
                            </h3>
                            <div class="space-y-2 text-sm text-bote-stone-600">
                                <div><span class="font-medium">Created:</span> ${point.created_at ? new Date(String(point.created_at)).toLocaleString() : 'Unknown'}</div>
                                <div><span class="font-medium">Updated:</span> ${point.updated_at ? new Date(String(point.updated_at)).toLocaleString() : 'Unknown'}</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            getEl('viewCollectionPointContent').innerHTML = content;
            lucide.createIcons();
            openModal('viewCollectionPointModal');
        }
        else {
            showAlert(String(data.message ?? 'Failed to load collection point details'), 'error');
        }
    }
    catch (error) {
        console.error('Error loading collection point details:', error);
        showAlert('Error loading collection point details: ' + String(error.message), 'error');
    }
}
function editFromView() {
    const pointId = document.getElementById('editFromViewBtn')?.getAttribute('data-point-id');
    if (!pointId)
        return;
    closeModal('viewCollectionPointModal');
    setTimeout(() => {
        void editCollectionPoint(pointId);
    }, 300);
}
function deleteCollectionPoint(pointId, name) {
    deletePointId = pointId;
    const nameContainer = getEl('deleteItemName').querySelector('p');
    if (nameContainer) {
        nameContainer.textContent = name;
    }
    openModal('deleteConfirmModal');
}
async function confirmDelete() {
    if (!deletePointId)
        return;
    const deleteBtn = getEl('confirmDeleteBtn');
    const btnText = deleteBtn.querySelector('.btn-text');
    const btnLoading = deleteBtn.querySelector('.btn-loading');
    if (btnText)
        btnText.classList.add('hidden');
    if (btnLoading)
        btnLoading.classList.remove('hidden');
    deleteBtn.disabled = true;
    try {
        const form = getEl('modalCollectionPointForm');
        const csrfInput = form.querySelector('input[name="csrf_token"]');
        const deleteData = {
            action: 'delete',
            point_id: deletePointId,
            csrf_token: csrfInput?.value ?? ''
        };
        const response = await fetch('collection_points_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(deleteData)
        });
        const responseText = await response.text();
        let result;
        try {
            result = JSON.parse(responseText);
        }
        catch (parseError) {
            console.error('JSON parse error:', parseError);
            throw new Error('Server returned invalid JSON. Check console for details.');
        }
        if (result.success) {
            showAlert(String(result.message), 'success');
            closeModal('deleteConfirmModal');
            void loadCollectionPointsTable();
            void loadExistingCollectionPoints();
        }
        else {
            showAlert(String(result.message ?? 'Failed to delete collection point'), 'error');
        }
    }
    catch (error) {
        console.error('Error deleting collection point:', error);
        showAlert('An error occurred while deleting: ' + String(error.message), 'error');
    }
    finally {
        if (btnText)
            btnText.classList.remove('hidden');
        if (btnLoading)
            btnLoading.classList.add('hidden');
        deleteBtn.disabled = false;
    }
}
async function submitModalForm() {
    if (!validateModalForm())
        return;
    setModalLoadingState(true);
    try {
        const form = getEl('modalCollectionPointForm');
        const formData = new FormData(form);
        const rawData = Object.fromEntries(formData);
        const data = { ...rawData };
        data.action = editingPointId ? 'update' : 'create';
        if (editingPointId) {
            data.point_id = editingPointId;
        }
        const response = await fetch('collection_points_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const responseText = await response.text();
        let result;
        try {
            result = JSON.parse(responseText);
        }
        catch (parseError) {
            console.error('JSON parse error:', parseError);
            throw new Error('Server returned invalid JSON. Response: ' + responseText.substring(0, 200));
        }
        if (result.success) {
            showAlert(String(result.message), 'success');
            closeModal('collectionPointModal');
            void loadExistingCollectionPoints();
            void loadCollectionPointsTable();
        }
        else {
            showAlert(String(result.message ?? 'Failed to save collection point'), 'error');
        }
    }
    catch (error) {
        console.error('Error submitting modal form:', error);
        showAlert('An error occurred while saving: ' + String(error.message), 'error');
    }
    finally {
        setModalLoadingState(false);
    }
}
function validateModalForm() {
    const requiredFields = {
        'modalCollectionPointName': 'Collection Point Name',
        'modalPurok': 'Purok',
        'modalLatitude': 'Latitude',
        'modalLongitude': 'Longitude',
        'modalStatus': 'Status'
    };
    let isValid = true;
    const errors = [];
    Object.keys(requiredFields).forEach(fieldId => {
        const input = document.getElementById(fieldId);
        const value = input?.value ?? '';
        if (!input || value.trim() === '') {
            input?.classList.add('border-red-500');
            isValid = false;
            errors.push(requiredFields[fieldId]);
        }
        else {
            input?.classList.remove('border-red-500');
        }
    });
    const lat = parseFloat(getInput('modalLatitude').value);
    const lng = parseFloat(getInput('modalLongitude').value);
    if (lat && lng) {
        if (lat < 4 || lat > 21 || lng < 116 || lng > 127) {
            showAlert('Coordinates must be within the Philippines (4°N-21°N, 116°E-127°E)', 'error');
            isValid = false;
        }
    }
    if (!isValid && errors.length > 0) {
        showAlert('Please fill in all required fields: ' + errors.join(', '), 'error');
    }
    return isValid;
}
function setModalLoadingState(loading) {
    const submitBtn = getEl('modalSubmitBtn');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoading = submitBtn.querySelector('.btn-loading');
    if (loading) {
        btnText?.classList.add('hidden');
        btnLoading?.classList.remove('hidden');
        submitBtn.disabled = true;
    }
    else {
        btnText?.classList.remove('hidden');
        btnLoading?.classList.add('hidden');
        submitBtn.disabled = false;
    }
}
function resetModalForm() {
    const form = getEl('modalCollectionPointForm');
    form.reset();
    getInput('modalOperatingHours').value = '24/7';
    clearModalLocation();
    form.querySelectorAll('input, select, textarea').forEach(input => {
        input.classList.remove('border-red-500');
    });
}
function initializeModalMap() {
    if (modalMap) {
        modalMap.remove();
    }
    if (modalCurrentMarker) {
        modalCurrentMarker = null;
    }
    const container = document.getElementById('modalMap');
    if (!container)
        return;
    const defaultLat = 6.4216;
    const defaultLng = 124.7810;
    modalMap = L.map('modalMap').setView([defaultLat, defaultLng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(modalMap);
    modalMap.on('click', (e) => {
        setModalLocation(e.latlng.lat, e.latlng.lng);
    });
    collectionPoints.forEach(point => {
        const color = getStatusColor(String(point.status ?? ''));
        const markerIcon = L.divIcon({
            className: 'custom-marker',
            html: `
                <div style="position: relative;">
                    <div style="
                        width: 24px; height: 24px; background-color: ${color};
                        border: 2px solid white; border-radius: 50%;
                        box-shadow: 0 2px 6px rgba(0,0,0,0.3);
                        display: flex; align-items: center; justify-content: center;
                        font-size: 12px; color: white; font-weight: bold;
                    ">📍</div>
                </div>
            `,
            iconSize: [24, 24],
            iconAnchor: [12, 12]
        });
        const pLat = parseFloat(String(point.latitude ?? 0));
        const pLng = parseFloat(String(point.longitude ?? 0));
        const marker = L.marker([pLat, pLng], { icon: markerIcon }).addTo(modalMap);
        marker.bindPopup(`
            <div class="text-center p-2">
                <strong class="text-base">${String(point.name ?? '')}</strong><br>
                <span class="text-sm text-bote-info-600">📍 ${String(point.purok_name ?? 'Unknown Purok')}</span>
            </div>
        `);
        marker.bindTooltip(`${String(point.name ?? '')}<br><small>${String(point.purok_name ?? 'Unknown')}</small>`, {
            permanent: true,
            direction: 'top',
            offset: [0, -15],
            className: 'custom-tooltip',
            opacity: 0.85
        });
    });
}
async function loadModalPuroks() {
    try {
        const response = await fetch('collection_points_handler.php?action=get_puroks');
        const data = await response.json();
        if (data.success) {
            const puroks = data.data;
            const purokSelect = getSelect('modalPurok');
            purokSelect.innerHTML = '<option value="">Select Purok</option>';
            puroks.forEach(purok => {
                const option = document.createElement('option');
                option.value = String(purok.purok_id ?? '');
                option.textContent = String(purok.name ?? '');
                purokSelect.appendChild(option);
            });
        }
    }
    catch (error) {
        console.error('Error loading modal puroks:', error);
    }
}
function setModalLocation(lat, lng) {
    if (!modalMap)
        return;
    if (modalCurrentMarker) {
        modalMap.removeLayer(modalCurrentMarker);
    }
    modalCurrentMarker = L.marker([lat, lng], { draggable: true }).addTo(modalMap);
    modalCurrentMarker.on('dragend', (e) => {
        const position = e.target.getLatLng();
        updateModalLocationInputs(position.lat, position.lng);
    });
    updateModalLocationInputs(lat, lng);
    modalMap.setView([lat, lng], modalMap.getZoom());
}
function updateModalLocationInputs(lat, lng) {
    getInput('modalLatitude').value = lat.toFixed(7);
    getInput('modalLongitude').value = lng.toFixed(7);
    getEl('modalCurrentLat').textContent = lat.toFixed(7);
    getEl('modalCurrentLng').textContent = lng.toFixed(7);
}
function getModalCurrentLocation() {
    if (!navigator.geolocation) {
        showAlert('Geolocation is not supported by this browser.', 'error');
        return;
    }
    showAlert('Getting your location...', 'info');
    navigator.geolocation.getCurrentPosition((position) => {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        setModalLocation(lat, lng);
        showAlert('Location set successfully!', 'success');
    }, (error) => {
        console.error('Geolocation error:', error);
        showAlert('Unable to get your location. Please click on the map to set location.', 'error');
    }, {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 60000
    });
}
function clearModalLocation() {
    if (modalMap && modalCurrentMarker) {
        modalMap.removeLayer(modalCurrentMarker);
        modalCurrentMarker = null;
    }
    getInput('modalLatitude').value = '';
    getInput('modalLongitude').value = '';
    getEl('modalCurrentLat').textContent = 'Not selected';
    getEl('modalCurrentLng').textContent = 'Not selected';
}
function initializeMap() {
    const container = document.getElementById('map');
    if (!container)
        return;
    const defaultLat = 6.4216;
    const defaultLng = 124.7810;
    map = L.map('map').setView([defaultLat, defaultLng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(map);
    map.on('click', (e) => {
        setLocation(e.latlng.lat, e.latlng.lng);
    });
    const loading = document.getElementById('mapLoading');
    if (loading) {
        loading.style.display = 'none';
    }
}
async function loadPuroks() {
    try {
        const response = await fetch('collection_points_handler.php?action=get_puroks');
        const data = await response.json();
        if (data.success) {
            const puroks = data.data;
            const purokSelect = getSelect('purok');
            purokSelect.innerHTML = '<option value="">Select Purok</option>';
            puroks.forEach(purok => {
                const option = document.createElement('option');
                option.value = String(purok.purok_id ?? '');
                option.textContent = String(purok.name ?? '');
                purokSelect.appendChild(option);
            });
        }
        else {
            console.error('Failed to load puroks:', data.message);
            showAlert('Failed to load puroks. Please refresh the page.', 'error');
        }
    }
    catch (error) {
        console.error('Error loading puroks:', error);
        showAlert('Error loading puroks. Please check your connection.', 'error');
    }
}
async function loadCollectionPointsTable() {
    try {
        const response = await fetch('collection_points_handler.php?action=get_collection_points');
        const data = await response.json();
        if (data.success) {
            displayCollectionPointsTable(data.data);
        }
        else {
            console.error('Failed to load collection points for table:', data.message);
            getEl('collectionPointsTable').innerHTML = `
                <div class="text-center py-8 text-red-600">
                    <i data-lucide="alert-circle" class="w-8 h-8 mx-auto mb-2"></i>
                    <p>Failed to load collection points</p>
                </div>
            `;
        }
    }
    catch (error) {
        console.error('Error loading collection points for table:', error);
        getEl('collectionPointsTable').innerHTML = `
            <div class="text-center py-8 text-red-600">
                <i data-lucide="wifi-off" class="w-8 h-8 mx-auto mb-2"></i>
                <p>Error loading collection points</p>
            </div>
        `;
    }
}
function displayCollectionPointsTable(points) {
    const tableContainer = getEl('collectionPointsTable');
    if (points.length === 0) {
        tableContainer.innerHTML = `
            <div class="text-center py-8 text-bote-stone-500">
                <i data-lucide="map-pin" class="w-12 h-12 mx-auto mb-4 text-bote-stone-300"></i>
                <h3 class="text-lg font-medium text-bote-stone-600 mb-2">No Collection Points Yet</h3>
                <p class="text-bote-stone-500 mb-4">Create your first collection point using the form below.</p>
                <button onclick="focusCollectionPointName()"
                        class="px-4 py-2 bg-bote-green-600 hover:bg-bote-green-700 text-white rounded-lg transition-colors">
                    Create First Collection Point
                </button>
            </div>
        `;
        lucide.createIcons();
        return;
    }
    let tableHTML = `
        <table class="min-w-full divide-y divide-bote-stone-200">
            <thead class="bg-bote-stone-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-bote-stone-500 uppercase tracking-wider">Name & Location</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-bote-stone-500 uppercase tracking-wider">Purok</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-bote-stone-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-bote-stone-500 uppercase tracking-wider">Capacity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-bote-stone-500 uppercase tracking-wider">Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-bote-stone-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-bote-stone-200">
    `;
    points.forEach(point => {
        const status = String(point.status ?? '');
        const statusBadgeClass = `status-${status}`;
        const statusText = status.charAt(0).toUpperCase() + status.slice(1);
        const color = getStatusColor(status);
        tableHTML += `
            <tr class="hover:bg-bote-stone-50">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center ${color === '#10b981' ? 'bg-bote-green-100' : color === '#ef4444' ? 'bg-red-100' : 'bg-yellow-100'}">
                            <i data-lucide="map-pin" class="w-5 h-5" style="color: ${color}"></i>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-bote-stone-900">${String(point.name ?? '')}</div>
                            <div class="text-sm text-bote-stone-500">${String(point.latitude ?? '')}, ${String(point.longitude ?? '')}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-bote-stone-900">${String(point.purok_name ?? 'Unknown')}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="status-badge ${statusBadgeClass}">${statusText}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-bote-stone-900">
                    ${point.capacity ? String(point.capacity) + ' kg' : 'Not set'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-bote-stone-900">${String(point.contact_person ?? 'No contact')}</div>
                    <div class="text-sm text-bote-stone-500">${String(point.contact_phone ?? '')}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button onclick="viewCollectionPoint(${String(point.point_id ?? '')})"
                            class="text-bote-info-600 hover:text-bote-info-900 mr-3" title="View Details">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                    </button>
                    <button onclick="viewOnMap(${String(point.latitude ?? '')}, ${String(point.longitude ?? '')})"
                            class="text-bote-info-600 hover:text-bote-info-900 mr-3" title="View on Map">
                        <i data-lucide="map-pin" class="w-4 h-4"></i>
                    </button>
                    <button onclick="editCollectionPoint(${String(point.point_id ?? '')})"
                            class="text-bote-green-600 hover:text-bote-green-900 mr-3" title="Edit">
                        <i data-lucide="edit" class="w-4 h-4"></i>
                    </button>
                    <button onclick="deleteCollectionPoint(${String(point.point_id ?? '')}, '${String(point.name ?? '').replace(/'/g, "\\'")}')"
                            class="text-red-600 hover:text-red-900" title="Delete">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    tableHTML += `
            </tbody>
        </table>
    `;
    tableContainer.innerHTML = tableHTML;
    lucide.createIcons();
}
function refreshCollectionPointsList() {
    void loadCollectionPointsTable();
    void loadExistingCollectionPoints();
}
function viewOnMap(lat, lng) {
    if (!map)
        return;
    map.setView([lat, lng], 16);
    getEl('map').scrollIntoView({ behavior: 'smooth', block: 'center' });
}
async function loadExistingCollectionPoints() {
    try {
        const response = await fetch('collection_points_handler.php?action=get_collection_points');
        const data = await response.json();
        if (data.success) {
            collectionPoints = data.data;
            displayCollectionPointsOnMap();
        }
        else {
            console.error('Failed to load collection points:', data.message);
        }
    }
    catch (error) {
        console.error('Error loading collection points:', error);
    }
}
function displayCollectionPointsOnMap() {
    if (!map)
        return;
    collectionPoints.forEach(point => {
        const color = getStatusColor(String(point.status ?? ''));
        const markerIcon = L.divIcon({
            className: 'custom-marker',
            html: `
                <div style="position: relative;">
                    <div style="
                        width: 30px; height: 30px; background-color: ${color};
                        border: 3px solid white; border-radius: 50%;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                        display: flex; align-items: center; justify-content: center;
                        font-size: 16px; color: white; font-weight: bold;
                    ">📍</div>
                </div>
            `,
            iconSize: [30, 30],
            iconAnchor: [15, 15]
        });
        const pLat = parseFloat(String(point.latitude ?? 0));
        const pLng = parseFloat(String(point.longitude ?? 0));
        const marker = L.marker([pLat, pLng], { icon: markerIcon }).addTo(map);
        const popupContent = `
            <div class="p-3" style="min-width: 200px;">
                <h3 class="font-bold text-lg text-bote-stone-800 mb-2">${String(point.name ?? '')}</h3>
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-bote-info-600">
                        📍 ${String(point.purok_name ?? 'Unknown Purok')}
                    </p>
                    <p class="text-sm">
                        Status: <span class="status-badge status-${String(point.status ?? '')}">${String(point.status ?? '')}</span>
                    </p>
                    ${point.capacity ? `<p class="text-sm text-bote-stone-600">💪 Capacity: ${String(point.capacity)} kg</p>` : ''}
                    ${point.contact_person ? `<p class="text-sm text-bote-stone-600">👤 Contact: ${String(point.contact_person)}</p>` : ''}
                    ${point.operating_hours ? `<p class="text-sm text-bote-stone-600">🕐 Hours: ${String(point.operating_hours)}</p>` : ''}
                </div>
            </div>
        `;
        marker.bindPopup(popupContent);
        marker.bindTooltip(`<strong>${String(point.name ?? '')}</strong><br>${String(point.purok_name ?? 'Unknown Purok')}`, {
            permanent: true,
            direction: 'top',
            offset: [0, -20],
            className: 'custom-tooltip',
            opacity: 0.9
        });
    });
}
function getStatusColor(status) {
    switch (status) {
        case 'active': return '#10b981';
        case 'inactive': return '#ef4444';
        case 'maintenance': return '#f59e0b';
        default: return '#6b7280';
    }
}
function setLocation(lat, lng) {
    if (!map)
        return;
    if (currentMarker) {
        map.removeLayer(currentMarker);
    }
    currentMarker = L.marker([lat, lng], { draggable: true }).addTo(map);
    currentMarker.on('dragend', (e) => {
        const position = e.target.getLatLng();
        updateLocationInputs(position.lat, position.lng);
    });
    updateLocationInputs(lat, lng);
    map.setView([lat, lng], map.getZoom());
}
function updateLocationInputs(lat, lng) {
    getInput('latitude').value = lat.toFixed(7);
    getInput('longitude').value = lng.toFixed(7);
    getEl('currentLat').textContent = lat.toFixed(7);
    getEl('currentLng').textContent = lng.toFixed(7);
}
function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alertContainer');
    if (!alertContainer)
        return;
    const alertColors = {
        success: 'bg-bote-green-100 border-bote-green-400 text-bote-green-700',
        error: 'bg-red-100 border-red-400 text-red-700',
        warning: 'bg-yellow-100 border-yellow-400 text-yellow-700',
        info: 'bg-bote-info-100 border-bote-info-400 text-bote-info-700'
    };
    const alertIcons = {
        success: 'check-circle',
        error: 'alert-circle',
        warning: 'alert-triangle',
        info: 'info'
    };
    const alert = document.createElement('div');
    alert.className = `mb-4 ${alertColors[type]} border px-4 py-3 rounded-lg ${type === 'success' || type === 'error' ? 'success-message' : ''}`;
    alert.innerHTML = `
        <div class="flex items-center">
            <i data-lucide="${alertIcons[type]}" class="w-5 h-5 mr-2"></i>
            <span class="font-medium">${message}</span>
        </div>
    `;
    alertContainer.appendChild(alert);
    lucide.createIcons();
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
    initializeMap();
    void loadPuroks();
    void loadExistingCollectionPoints();
    void loadCollectionPointsTable();
    setupModalEventListeners();
});
Object.assign(window, {
    openAddModal,
    focusCollectionPointName,
    editCollectionPoint,
    viewCollectionPoint,
    viewOnMap,
    deleteCollectionPoint,
    confirmDelete,
    submitModalForm,
    closeModal,
    getModalCurrentLocation,
    clearModalLocation,
    editFromView,
    refreshCollectionPointsList
});
//# sourceMappingURL=collection_points.js.map