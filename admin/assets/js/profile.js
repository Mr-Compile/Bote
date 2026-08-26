
    lucide.createIcons();
    
    let selectedFile = null;
    let map = null;
    let marker = null;
    let searchTimeout = null;
    let pendingLocation = null;

    // Initialize map
    function initializeMap() {
        try {
            // Get coordinates from the global phpData object or use default values
            const defaultLat = (phpData && phpData.user && phpData.user.latitude) ? phpData.user.latitude : 6.4217;
            const defaultLng = (phpData && phpData.user && phpData.user.longitude) ? phpData.user.longitude : 124.7801;
            
            console.log('Initializing map with coordinates:', defaultLat, defaultLng);
            
            // Initialize the map
            map = L.map('map').setView([defaultLat, defaultLng], 15);
            
            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(map);
            
            console.log('Map initialized successfully');
            
            // Add existing marker if coordinates exist
            if (phpData && phpData.user && phpData.user.latitude) {
                addMarker(defaultLat, defaultLng);
                console.log('Added existing marker');
            }
            
            // Add click event to map with confirmation
            map.on('click', function(e) {
                const lat = e.latlng.lat;
                const lng = e.latlng.lng;
                
                console.log('Map clicked at:', lat, lng);
                
                // Store pending location
                pendingLocation = { lat, lng };
                
                // Get address for confirmation
                reverseGeocode(lat, lng, function(address) {
                    showLocationConfirmation(lat, lng, address);
                });
            });
            
            // Add map ready event
            map.whenReady(function() {
                console.log('Map is ready');
                // Force a resize to ensure map renders correctly
                setTimeout(() => {
                    map.invalidateSize();
                }, 100);
            });
            
        } catch (error) {
            console.error('Error initializing map:', error);
            showNotification('Error initializing map. Please refresh the page.', 'error');
        }
    }
    
    // Add or update marker
    function addMarker(lat, lng) {
        if (marker) {
            map.removeLayer(marker);
        }
        
        // Custom green marker icon
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
    
    // Update coordinate displays and hidden inputs
    function updateCoordinates(lat, lng) {
        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lng;
        document.getElementById('latitude-display').textContent = lat.toFixed(6);
        document.getElementById('longitude-display').textContent = lng.toFixed(6);
    }

            function centerMap() {
            const lat = parseFloat(document.getElementById('latitude').value);
            const lng = parseFloat(document.getElementById('longitude').value);
            
            if (lat && lng) {
                map.setView([lat, lng], 16);
                if (marker) {
                    marker.openPopup();
                }
            } else {
                // Center on default location
                map.setView([6.4216359, 124.7809971], 15);
                showNotification('No location set. Showing default area.', 'info');
            }
        }
    
    // Search for address using Nominatim
    function searchAddress() {
        const query = document.getElementById('address-search').value.trim();
        if (!query) {
            showNotification('Please enter an address to search', 'error');
            return;
        }
        
        showSearchLoader();
        
        // Add Philippines bounds and better formatting for search
        const searchUrl = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5&countrycodes=PH&bounded=1&viewbox=116.9283,4.5693,126.6043,21.1217&addressdetails=1`;
        
        console.log('Searching for:', query);
        console.log('Search URL:', searchUrl);
        
        fetch(searchUrl, {
            method: 'GET',
            headers: {
                'User-Agent': 'BoteBank/1.0 (Your Application)',
                'Accept': 'application/json',
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
                
                if (data && Array.isArray(data)) {
                    displaySearchResults(data);
                } else {
                    displaySearchResults([]);
                }
            })
            .catch(error => {
                hideSearchLoader();
                console.error('Geocoding error:', error);
                showNotification('Error searching for address. Please check your internet connection and try again.', 'error');
                
                // Fallback: show empty results
                displaySearchResults([]);
            });
    }
    
    // Display search results
    function displaySearchResults(results) {
        const resultsContainer = document.getElementById('search-results');
        resultsContainer.innerHTML = '';
        
        console.log('Displaying results:', results);
        
        if (!results || results.length === 0) {
            resultsContainer.innerHTML = `
                <div class="search-result-item text-gray-500 text-center py-4">
                    <i data-lucide="map-pin-off" class="w-5 h-5 mx-auto mb-2 text-gray-400"></i>
                    <div>No results found</div>
                    <div class="text-xs text-gray-400 mt-1">Try a different search term</div>
                </div>
            `;
            resultsContainer.classList.remove('hidden');
            lucide.createIcons();
            return;
        }
        
        results.forEach((result, index) => {
            const item = document.createElement('div');
            item.className = 'search-result-item hover:bg-green-50 cursor-pointer transition-all duration-200';
            
            // Better formatting for display name
            let displayName = result.display_name;
            if (displayName.length > 80) {
                displayName = displayName.substring(0, 80) + '...';
            }
            
            item.innerHTML = `
                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0 mt-1">
                        <i data-lucide="map-pin" class="w-4 h-4 text-green-600"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-gray-900 truncate">${displayName}</div>
                        <div class="text-sm text-gray-500 mt-1">
                            <span class="inline-flex items-center space-x-2">
                                <span>📍 ${parseFloat(result.lat).toFixed(6)}, ${parseFloat(result.lon).toFixed(6)}</span>
                            </span>
                        </div>
                    </div>
                </div>
            `;
            
            item.onclick = () => {
                console.log('Selected result:', result);
                selectSearchResult(result);
            };
            
            resultsContainer.appendChild(item);
        });
        
        resultsContainer.classList.remove('hidden');
        lucide.createIcons();
    }
    
    // Select a search result
    function selectSearchResult(result) {
        const lat = parseFloat(result.lat);
        const lng = parseFloat(result.lon);
        
        // Store pending location
        pendingLocation = { lat, lng, address: result.display_name };
        
        // Show confirmation modal
        showLocationConfirmation(lat, lng, result.display_name);
        
        // Hide search results
        document.getElementById('search-results').classList.add('hidden');
        document.getElementById('address-search').value = '';
    }
    
    // Reverse geocode coordinates to get address
    function reverseGeocode(lat, lng, callback) {
        console.log('Reverse geocoding:', lat, lng);
        
        const reverseUrl = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`;
        
        fetch(reverseUrl, {
            method: 'GET',
            headers: {
                'User-Agent': 'BoteBank/1.0 (Your Application)',
                'Accept': 'application/json',
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
                const address = data.display_name || 'Unknown location';
                if (callback) {
                    callback(address);
                } else {
                    // Legacy behavior for backward compatibility
                    const addressField = document.getElementById('address');
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
                // Don't show notification for reverse geocoding errors as it's not critical
            });
    }
    
    // Show search loader
    function showSearchLoader() {
        const searchButton = document.querySelector('button[onclick="searchAddress()"]');
        const icon = searchButton.querySelector('i[data-lucide="search"]');
        const text = searchButton.querySelector('span');
        
        searchButton.disabled = true;
        searchButton.classList.add('opacity-75');
        icon.setAttribute('data-lucide', 'loader-2');
        icon.classList.add('animate-spin');
        text.textContent = 'Searching...';
        lucide.createIcons();
    }
    
    // Hide search loader
    function hideSearchLoader() {
        const searchButton = document.querySelector('button[onclick="searchAddress()"]');
        const icon = searchButton.querySelector('i');
        const text = searchButton.querySelector('span');
        
        searchButton.disabled = false;
        searchButton.classList.remove('opacity-75');
        icon.setAttribute('data-lucide', 'search');
        icon.classList.remove('animate-spin');
        text.textContent = 'Search';
        lucide.createIcons();
    }
    
    // Real-time address search with debouncing
    document.getElementById('address-search').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length < 3) {
            document.getElementById('search-results').classList.add('hidden');
            return;
        }
        
        searchTimeout = setTimeout(() => {
            console.log('Auto-searching for:', query);
            const searchUrl = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=3&countrycodes=PH&bounded=1&viewbox=116.9283,4.5693,126.6043,21.1217&addressdetails=1`;
            
            fetch(searchUrl, {
                method: 'GET',
                headers: {
                    'User-Agent': 'BoteBank/1.0 (Your Application)',
                    'Accept': 'application/json',
                }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Auto-search results:', data);
                    if (data && Array.isArray(data)) {
                        displaySearchResults(data);
                    }
                })
                .catch(error => {
                    console.error('Auto-search error:', error);
                    // Don't show notification for auto-search errors, just log them
                });
        }, 800); // Increased delay to reduce API calls
    });
    
    // Handle Enter key in search field
    document.getElementById('address-search').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchAddress();
        }
    });
    
    // Hide search results when clicking outside
    document.addEventListener('click', function(e) {
        const searchContainer = document.querySelector('.address-search');
        if (!searchContainer.contains(e.target)) {
            document.getElementById('search-results').classList.add('hidden');
        }
    });
    
    // Test search function for debugging
    function testSearch() {
        console.log('Testing search functionality...');
        
        const testQuery = 'Mabini, Davao del Sur, Philippines';
        document.getElementById('address-search').value = testQuery;
        
        showNotification('Testing search with: ' + testQuery, 'info');
        
        const searchUrl = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(testQuery)}&limit=5&countrycodes=PH&bounded=1&viewbox=116.9283,4.5693,126.6043,21.1217&addressdetails=1`;
        
        console.log('Test search URL:', searchUrl);
        
        fetch(searchUrl, {
            method: 'GET',
            headers: {
                'User-Agent': 'BoteBank/1.0 (Your Application)',
                'Accept': 'application/json',
            }
        })
            .then(response => {
                console.log('Test response status:', response.status);
                console.log('Test response headers:', response.headers);
                return response.json();
            })
            .then(data => {
                console.log('Test search results:', data);
                showNotification(`Test successful! Found ${data.length} results.`, 'success');
                displaySearchResults(data);
            })
            .catch(error => {
                console.error('Test search error:', error);
                showNotification('Test failed: ' + error.message, 'error');
            });
    }
    
    // Show location confirmation modal
    function showLocationConfirmation(lat, lng, address) {
        const modal = document.getElementById('locationConfirmModal');
        const modalContent = modal.querySelector('.bg-white');
        
        document.getElementById('confirm-lat').textContent = lat.toFixed(6);
        document.getElementById('confirm-lng').textContent = lng.toFixed(6);
        document.getElementById('confirm-address').textContent = address || 'Selected location';
        
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        setTimeout(() => {
            modalContent.classList.remove('scale-95', 'opacity-0');
            modalContent.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    // Confirm location update
    function confirmLocationUpdate() {
        if (pendingLocation) {
            // Update map
            map.setView([pendingLocation.lat, pendingLocation.lng], 16);
            addMarker(pendingLocation.lat, pendingLocation.lng);
            updateCoordinates(pendingLocation.lat, pendingLocation.lng);
            
            // Update address field if provided
            if (pendingLocation.address) {
                const addressField = document.getElementById('address');
                if (!addressField.value.trim() || confirm('Do you want to update the address field with this location?')) {
                    addressField.value = pendingLocation.address;
                }
            }
            
            showNotification('Location updated successfully!', 'success');
            hideLocationConfirmModal();
            pendingLocation = null;
        }
    }

    // Cancel location update
    function cancelLocationUpdate() {
        pendingLocation = null;
        hideLocationConfirmModal();
    }

    // Hide location confirmation modal
    function hideLocationConfirmModal() {
        const modal = document.getElementById('locationConfirmModal');
        const modalContent = modal.querySelector('.bg-white');
        
        modalContent.classList.remove('scale-100', 'opacity-100');
        modalContent.classList.add('scale-95', 'opacity-0');
        
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 300);
    }

    // Check if geolocation is available
    function getCurrentLocation() {
        if ("geolocation" in navigator) {
            showNotification('Getting your location...', 'info');
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    console.log('Current location:', lat, lng);
                    
                    // Store pending location
                    pendingLocation = { lat, lng };
                    
                    // Get address for confirmation
                    reverseGeocode(lat, lng, function(address) {
                        showLocationConfirmation(lat, lng, address);
                    });
                },
                function(error) {
                    console.error('Geolocation error:', error);
                    showNotification('Could not get your location. Please search manually.', 'error');
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 60000
                }
            );
        } else {
            showNotification('Geolocation is not supported by this browser.', 'error');
        }
    }

    // Initialize animations
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize map
        initializeMap();
        
        // Animate elements on load
        const slideElements = document.querySelectorAll('.slide-up');
        slideElements.forEach((element, index) => {
            setTimeout(() => {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, 100 * (index + 1));
        });

        // Add floating animation to background elements
        const floatingElements = document.querySelectorAll('.float-animation');
        floatingElements.forEach(element => {
            element.style.animationDelay = Math.random() * 2 + 's';
        });
    });

    function showPhotoModal() {
        const modal = document.getElementById('photoModal');
        const modalContent = modal.querySelector('div > div');
        
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        setTimeout(() => {
            modalContent.classList.remove('scale-95', 'opacity-0');
            modalContent.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function hidePhotoModal() {
        const modal = document.getElementById('photoModal');
        const modalContent = modal.querySelector('div > div');
        
        modalContent.classList.remove('scale-100', 'opacity-100');
        modalContent.classList.add('scale-95', 'opacity-0');
        
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 500);
        
        // Reset form
        clearPreview();
    }

    function clearPreview() {
        document.getElementById('photoInput').value = '';
        document.getElementById('photoPreview').classList.add('hidden');
        document.getElementById('uploadBtn').disabled = true;
        document.getElementById('uploadBtnText').textContent = 'Upload Photo';
        selectedFile = null;
    }

    // Enhanced file input handling with drag and drop
    document.getElementById('photoInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        handleFileSelection(file);
    });

    // Drag and drop functionality
    const dropZone = document.querySelector('button[onclick="document.getElementById(\'photoInput\').click()"]');
    
    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('border-green-500', 'bg-green-100');
    });

    dropZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('border-green-500', 'bg-green-100');
    });

    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('border-green-500', 'bg-green-100');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFileSelection(files[0]);
        }
    });

    function handleFileSelection(file) {
        if (!file) return;

        // Validate file type
        if (!file.type.startsWith('image/')) {
            showNotification('Please select an image file', 'error');
            return;
        }

        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            showNotification('File size must be less than 5MB', 'error');
            return;
        }

        selectedFile = file;
        
        // Show preview with animation
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewContainer = document.getElementById('photoPreview');
            const previewImage = document.getElementById('previewImage');
            
            previewImage.src = e.target.result;
            previewContainer.classList.remove('hidden');
            document.getElementById('uploadBtn').disabled = false;
            
            // Animate preview appearance
            setTimeout(() => {
                previewImage.style.transform = 'scale(1)';
                previewImage.style.opacity = '1';
            }, 100);
        };
        reader.readAsDataURL(file);
    }

    function uploadPhoto() {
        if (!selectedFile) return;

        const formData = new FormData();
        formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
        formData.append('profile_photo', selectedFile);
        formData.append('user_role', 'admin'); // Add role info for proper handling

        const uploadBtn = document.getElementById('uploadBtn');
        const uploadBtnText = document.getElementById('uploadBtnText');
        
        uploadBtn.disabled = true;
        uploadBtnText.textContent = 'Uploading...';
        
        // Add loading animation
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
            } else {
                showNotification('Upload failed: ' + data.message, 'error');
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

    // Enhanced notification system
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 max-w-sm w-full bg-white rounded-xl shadow-2xl border-l-4 p-4 transform translate-x-full transition-all duration-500 ${
            type === 'success' ? 'border-green-500' : 
            type === 'error' ? 'border-red-500' : 'border-blue-500'
        }`;
        
        notification.innerHTML = `
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center ${
                        type === 'success' ? 'bg-green-100' : 
                        type === 'error' ? 'bg-red-100' : 'bg-blue-100'
                    }">
                        <i data-lucide="${
                            type === 'success' ? 'check' : 
                            type === 'error' ? 'x' : 'info'
                        }" class="w-5 h-5 ${
                            type === 'success' ? 'text-green-600' : 
                            type === 'error' ? 'text-red-600' : 'text-blue-600'
                        }"></i>
                    </div>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-gray-900">${message}</p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-3 text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        `;
        
        document.body.appendChild(notification);
        lucide.createIcons();
        
        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
            notification.classList.add('translate-x-0');
        }, 100);
        
        // Auto remove after 5 seconds
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

    // Close modal when clicking outside
    document.getElementById('photoModal').addEventListener('click', function(e) {
        if (e.target === this) {
            hidePhotoModal();
        }
    });

    // Enhanced form validation with real-time feedback
    const formInputs = document.querySelectorAll('input, textarea');
    formInputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.classList.add('ring-2', 'ring-green-500/20');
        });
        
        input.addEventListener('blur', function() {
            this.classList.remove('ring-2', 'ring-green-500/20');
            validateField(this);
        });
    });

    // Form submission handler
    document.querySelector('form').addEventListener('submit', function(e) {
        // Validate that coordinates are set if address is provided
        const address = document.getElementById('address').value.trim();
        const latitude = document.getElementById('latitude').value;
        const longitude = document.getElementById('longitude').value;
        
        if (address && (!latitude || !longitude)) {
            e.preventDefault();
            showNotification('Please set your location on the map by searching for your address or clicking on the map.', 'error');
            document.getElementById('map').scrollIntoView({ behavior: 'smooth' });
            return false;
        }
    });

    function validateField(field) {
        const value = field.value.trim();
        const fieldName = field.name;
        
        // Remove any existing validation messages
        const existingMessage = field.parentElement.querySelector('.validation-message');
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
            const newPassword = document.getElementById('new_password').value;
            if (value !== newPassword) {
                isValid = false;
                message = 'Passwords do not match';
            }
        }
        
        if (!isValid) {
            field.classList.add('border-red-300', 'ring-red-500');
            field.classList.remove('border-gray-300');
            
            const messageElement = document.createElement('p');
            messageElement.className = 'validation-message text-sm text-red-600 mt-1';
            messageElement.textContent = message;
            field.parentElement.appendChild(messageElement);
        } else {
            field.classList.remove('border-red-300', 'ring-red-500');
            field.classList.add('border-gray-300');
        }
        
        return isValid;
    }

    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Password strength indicator
    const newPasswordField = document.getElementById('new_password');
    if (newPasswordField) {
        newPasswordField.addEventListener('input', function() {
            const password = this.value;
            const strengthIndicator = document.getElementById('password-strength') || createPasswordStrengthIndicator(this);
            
            if (password.length === 0) {
                strengthIndicator.style.display = 'none';
                return;
            }
            
            strengthIndicator.style.display = 'block';
            const strength = calculatePasswordStrength(password);
            updatePasswordStrengthIndicator(strengthIndicator, strength);
        });
    }

    function createPasswordStrengthIndicator(passwordField) {
        const indicator = document.createElement('div');
        indicator.id = 'password-strength';
        indicator.className = 'mt-2 hidden';
        indicator.innerHTML = `
            <div class="flex space-x-1 mb-1">
                <div class="h-2 flex-1 bg-gray-200 rounded"></div>
                <div class="h-2 flex-1 bg-gray-200 rounded"></div>
                <div class="h-2 flex-1 bg-gray-200 rounded"></div>
                <div class="h-2 flex-1 bg-gray-200 rounded"></div>
            </div>
            <p class="text-sm text-gray-600"></p>
        `;
        passwordField.parentElement.appendChild(indicator);
        return indicator;
    }

    function calculatePasswordStrength(password) {
        let score = 0;
        if (password.length >= 6) score++;
        if (password.length >= 10) score++;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
        if (/\d/.test(password)) score++;
        if (/[^a-zA-Z0-9]/.test(password)) score++;
        
        return Math.min(score, 4);
    }

    function updatePasswordStrengthIndicator(indicator, strength) {
        const bars = indicator.querySelectorAll('.h-2');
        const text = indicator.querySelector('p');
        
        const colors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-green-500'];
        const labels = ['Very Weak', 'Weak', 'Fair', 'Strong'];
        
        bars.forEach((bar, index) => {
            bar.className = `h-2 flex-1 rounded ${index < strength ? colors[strength - 1] : 'bg-gray-200'}`;
        });
        
        if (strength > 0) {
            text.textContent = `Password strength: ${labels[strength - 1]}`;
            text.className = `text-sm ${strength >= 3 ? 'text-green-600' : strength >= 2 ? 'text-yellow-600' : 'text-red-600'}`;
        }
    }
