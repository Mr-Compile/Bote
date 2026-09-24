// @ts-nocheck
import { getPageData } from '../../shared.js';

const pageData = getPageData<{ selectedDate: string; workerId: number }>('collection-routes-data');

if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
    
    // Initialize map
    let map;
    let markers = [];
    let routeLines = [];
    let currentRoute = null;
    let currentStopIndex = 0;
    let routeStops = [];
    let stopMarkers = [];

    // Date selector functionality
    function changeDate() {
        const selectedDate = document.getElementById('dateSelector').value;
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('date', selectedDate);
        window.location.href = currentUrl.toString();
    }

    // Complete pickup modal functions
    function showCompletePickupModal(requestId, residentName, materialType, unit, estimatedWeight, estimatedQuantity) {
        document.getElementById('completeRequestId').value = requestId;
        
        // Populate pickup details
        const detailsHtml = `
            <h4 class="font-semibold text-bote-stone-900 mb-2">Pickup Details</h4>
            <div class="space-y-2 text-sm">
                <p><span class="text-bote-stone-600">Resident:</span> <span class="font-medium">${residentName}</span></p>
                <p><span class="text-bote-stone-600">Material:</span> <span class="font-medium">${materialType}</span></p>
                <p><span class="text-bote-stone-600">Unit:</span> <span class="font-medium">${unit}</span></p>
                <p><span class="text-bote-stone-600">Estimated ${unit === 'kg' ? 'Weight' : 'Quantity'}:</span> <span class="font-medium">${unit === 'kg' ? estimatedWeight + ' kg' : estimatedQuantity + ' pcs'}</span></p>
            </div>
        `;
        document.getElementById('pickupDetails').innerHTML = detailsHtml;
        
        // Show appropriate input fields based on unit
        const weightSection = document.getElementById('weightSection');
        const quantitySection = document.getElementById('quantitySection');
        const weightInput = document.getElementById('actualWeight');
        const quantityInput = document.getElementById('actualQuantity');
        
        if (unit === 'kg') {
            weightSection.classList.remove('hidden');
            quantitySection.classList.add('hidden');
            weightInput.required = true;
            quantityInput.required = false;
            weightInput.value = estimatedWeight;
        } else {
            weightSection.classList.add('hidden');
            quantitySection.classList.remove('hidden');
            weightInput.required = false;
            quantityInput.required = true;
            quantityInput.value = estimatedQuantity;
        }
        
        // Clear notes
        document.getElementById('completionNotes').value = '';
        
        // Show modal
        const modal = document.getElementById('completePickupModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        setTimeout(() => {
            modal.querySelector('.bg-white').classList.remove('scale-95');
            modal.querySelector('.bg-white').classList.add('scale-100');
        }, 10);
    }

    function hideCompletePickupModal() {
        const modal = document.getElementById('completePickupModal');
        modal.querySelector('.bg-white').classList.remove('scale-100');
        modal.querySelector('.bg-white').classList.add('scale-95');
        document.body.style.overflow = '';
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 300);
    }

    function submitPickupCompletion(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 mr-2 animate-spin"></i>Completing...';
        submitBtn.disabled = true;
        
        // Convert to JSON for the existing completion endpoint
        const data = {
            action: 'complete_pickup',
            request_id: parseInt(formData.get('request_id')),
            actual_weight: formData.get('actual_weight') ? parseFloat(formData.get('actual_weight')) : null,
            actual_quantity: formData.get('actual_quantity') ? parseInt(formData.get('actual_quantity')) : null,
            notes: formData.get('notes'),
            csrf_token: formData.get('csrf_token')
        };
        
        fetch('worker_assigned_pickups.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(data)
        })
        .then(response => response.text())
        .then(result => {
            // Reset button
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            
            // Check if completion was successful (page reload indicates success)
            if (result.includes('success') || result.includes('completed')) {
                hideCompletePickupModal();
                showNotification('Pickup completed successfully!', 'success');
                
                // Reload page to reflect changes
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showNotification('Failed to complete pickup. Please try again.', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            showNotification('An error occurred. Please try again.', 'error');
        });
    }

    // Sorting functions for pickup requests
    function sortPickupsByTime(order) {
        const container = document.getElementById('pickupRequestsContainer');
        const items = Array.from(container.children);
        
        // Update button states
        document.querySelectorAll('.sort-btn').forEach(btn => {
            btn.classList.remove('active', 'bg-bote-green-500', 'text-white');
            btn.classList.add('bg-bote-stone-200', 'text-bote-stone-700');
        });
        
        const activeBtn = document.getElementById(order === 'earliest' ? 'sortEarliestBtn' : 'sortLatestBtn');
        activeBtn.classList.remove('bg-bote-stone-200', 'text-bote-stone-700');
        activeBtn.classList.add('active', 'bg-bote-green-500', 'text-white');
        
        // Sort items
        items.sort((a, b) => {
            const timeA = parseInt(a.dataset.preferredTime);
            const timeB = parseInt(b.dataset.preferredTime);
            
            if (order === 'earliest') {
                return timeA - timeB;
            } else {
                return timeB - timeA;
            }
        });
        
        // Re-append sorted items
        items.forEach(item => container.appendChild(item));
    }

    // Wrapper function to handle loading state for show on map
    function showOnMapWithLoading(button, lat, lng, residentName) {
        // Add loading state to button
        const originalContent = button.innerHTML;
        button.classList.add('button-loading');
        button.disabled = true;
        button.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 mr-2 animate-spin"></i><span class="button-text">Navigating...</span>';
        
        // Call the actual function
        showOnMap(lat, lng, residentName);
        
        // Reset button after animation
        setTimeout(() => {
            button.classList.remove('button-loading');
            button.disabled = false;
            button.innerHTML = originalContent;
            
            // Re-initialize lucide icons
            if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
        }, 2000);
    }

    // Show pickup location on map
    function showOnMap(lat, lng, residentName) {
        if (map) {
            // Scroll to map section smoothly
            const mapSection = document.getElementById('routeMap').parentElement.parentElement;
            mapSection.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start' 
            });
            
            // Add visual indicator that we're navigating to location
            showNotification(`Navigating to ${residentName}'s location...`, 'info');
            
            // Wait for scroll animation then show location
            setTimeout(() => {
                // Center map on the location with appropriate zoom
                map.setView([lat, lng], 17, {
                    animate: true,
                    pan: {
                        animate: true,
                        duration: 1.5
                    },
                    zoom: {
                        animate: true
                    }
                });
                
                // Remove any existing temporary markers
                map.eachLayer(layer => {
                    if (layer.options && layer.options.className && layer.options.className.includes('temp-location-marker')) {
                        map.removeLayer(layer);
                    }
                });
                
                // Add an enhanced temporary marker with pulsing animation
                const tempMarker = L.marker([lat, lng], {
                    icon: L.divIcon({
                        className: 'temp-location-marker',
                        html: `
                            <div class="relative">
                                <div class="w-12 h-12 bg-red-500 rounded-full border-4 border-white shadow-2xl flex items-center justify-center animate-bounce" style="animation: bounce 1s infinite;">
                                    <i class="fas fa-map-pin text-white text-lg"></i>
                                </div>
                                <div class="absolute inset-0 w-12 h-12 bg-red-400 rounded-full animate-ping opacity-75"></div>
                                <div class="absolute -bottom-1 left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-red-500"></div>
                            </div>
                        `,
                        iconSize: [48, 48],
                        iconAnchor: [24, 48],
                        className: 'temp-location-marker-container'
                    }),
                    zIndexOffset: 1000
                }).addTo(map);
                
                // Enhanced popup with more details
                tempMarker.bindPopup(`
                    <div class="p-4 min-w-48">
                        <div class="flex items-center space-x-2 mb-3">
                            <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-white text-sm"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-red-600 text-lg">${residentName}</h3>
                                <p class="text-xs text-bote-stone-500">Pickup Location</p>
                            </div>
                        </div>
                        <div class="text-sm text-bote-stone-600 space-y-1">
                            <p><strong>Coordinates:</strong> ${lat.toFixed(6)}, ${lng.toFixed(6)}</p>
                            <p><strong>Status:</strong> <span class="text-bote-info-600">Selected for Navigation</span></p>
                        </div>
                        <div class="mt-3 pt-3 border-t border-bote-stone-200">
                            <button onclick="centerMapOnLocation(${lat}, ${lng})" class="w-full bg-bote-green-500 hover:bg-bote-green-600 text-white px-3 py-2 rounded text-sm font-medium transition-colors">
                                <i class="fas fa-crosshairs mr-2"></i>Re-center
                            </button>
                        </div>
                    </div>
                `, {
                    maxWidth: 300,
                    className: 'custom-popup'
                }).openPopup();
                
                // Add hover effects for all existing pickup markers
                highlightNearbyPickups(lat, lng);
                
                // Auto-remove marker after 15 seconds with fade out
                setTimeout(() => {
                    if (tempMarker && map.hasLayer(tempMarker)) {
                        // Fade out animation
                        const markerElement = tempMarker.getElement();
                        if (markerElement) {
                            markerElement.style.transition = 'opacity 1s ease-out';
                            markerElement.style.opacity = '0';
                            setTimeout(() => {
                                map.removeLayer(tempMarker);
                            }, 1000);
                        } else {
                            map.removeLayer(tempMarker);
                        }
                    }
                }, 15000);
                
            }, 800); // Wait for scroll animation
        } else {
            showNotification('Map not yet loaded. Please wait and try again.', 'warning');
        }
    }
    
    // Helper function to re-center map
    function centerMapOnLocation(lat, lng) {
        if (map) {
            map.setView([lat, lng], 18, {
                animate: true,
                pan: { animate: true, duration: 1 }
            });
        }
    }
    
    // Helper function to highlight nearby pickups
    function highlightNearbyPickups(targetLat, targetLng) {
        markers.forEach(marker => {
            if (marker.getLatLng) {
                const markerPos = marker.getLatLng();
                const distance = getDistance(
                    { lat: targetLat, lng: targetLng },
                    { lat: markerPos.lat, lng: markerPos.lng }
                );
                
                // Highlight markers within 500 meters
                if (distance < 0.5) {
                    const markerElement = marker.getElement();
                    if (markerElement && !markerElement.classList.contains('temp-location-marker-container')) {
                        // Add pulsing highlight effect
                        markerElement.style.transition = 'all 0.3s ease';
                        markerElement.style.transform = 'scale(1.2)';
                        markerElement.style.filter = 'drop-shadow(0 0 10px rgba(59, 130, 246, 0.8))';
                        
                        // Remove highlight after 5 seconds
                        setTimeout(() => {
                            markerElement.style.transform = 'scale(1)';
                            markerElement.style.filter = 'none';
                        }, 5000);
                    }
                }
            }
        });
    }

    // Notification system
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full`;
        
        const colors = {
            success: 'bg-bote-green-600 text-white',
            error: 'bg-red-600 text-white',
            info: 'bg-bote-info-600 text-white',
            warning: 'bg-yellow-600 text-white'
        };
        
        const icons = {
            success: 'check-circle',
            error: 'x-circle',
            info: 'info',
            warning: 'alert-triangle'
        };
        
        notification.className += ` ${colors[type]}`;
        notification.innerHTML = `
            <div class="flex items-center space-x-2">
                <i data-lucide="${icons[type]}" class="w-5 h-5"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Trigger animation
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);
        
        // Auto remove
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 4000);
        
        // Re-create lucide icons
        if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
    }

    // Close modal when clicking outside
    document.getElementById('completePickupModal').addEventListener('click', function(e) {
        if (e.target === this) {
            hideCompletePickupModal();
        }
    });

    // Initialize Leaflet map
    function initializeMap() {
        // Default center to Philippines if no barangay coordinates
        const defaultLat = 6.4216;
        const defaultLng = 124.7810;
        
        map = L.map('routeMap').setView([defaultLat, defaultLng], 13);
        
        // Add tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        // Load current routes and pickup requests
        loadRouteData();
    }

    // Load route data and display on map
    function loadRouteData() {
        if (!pageData) return;
        fetch(`get_map_data.php?worker_id=${pageData.workerId}&date=${pageData.selectedDate}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                clearMapData();
                
                // Add starting point (barangay office or admin location)
                if (data.admin_location) {
                    const startMarker = L.marker([data.admin_location.lat, data.admin_location.lng], {
                        icon: L.divIcon({
                            className: 'custom-marker start-marker',
                            html: '<div class="w-8 h-8 bg-bote-green-500 rounded-full border-3 border-white shadow-lg flex items-center justify-center"><i class="fas fa-home text-white text-sm"></i></div>',
                            iconSize: [32, 32],
                            iconAnchor: [16, 16]
                        })
                    }).addTo(map);
                    
                    startMarker.bindPopup(`
                        <div class="p-3">
                            <h3 class="font-semibold text-bote-green-600 text-lg">Starting Point</h3>
                            <p class="text-sm text-bote-stone-600">All routes begin and end here</p>
                            <p class="text-xs text-bote-stone-500 mt-2">Barangay Office Location</p>
                        </div>
                    `);
                    
                    markers.push(startMarker);
                    map.setView([data.admin_location.lat, data.admin_location.lng], 13);
                }
                
                // Add selected date's pickup request markers for this worker
                if (data.today_pickups && data.today_pickups.length > 0) {
                    data.today_pickups.forEach(pickup => {
                        const statusColors = {
                            'Scheduled': { color: 'blue', bg: 'bg-bote-info-500' },
                            'In Progress': { color: 'orange', bg: 'bg-orange-500' },
                            'Completed': { color: 'green', bg: 'bg-bote-green-500' }
                        };
                        
                        const statusInfo = statusColors[pickup.status] || { color: 'blue', bg: 'bg-bote-info-500' };
                        
                        // Different marker style for completed pickups
                        const markerClass = pickup.status === 'Completed' ? 'completed-pickup-marker' : 'active-pickup-marker';
                        const iconHtml = pickup.status === 'Completed' 
                            ? `<div class="w-8 h-8 ${statusInfo.bg} rounded-full border-3 border-white shadow-lg flex items-center justify-center opacity-70"><i class="fas fa-check text-white text-sm"></i></div>`
                            : `<div class="w-8 h-8 ${statusInfo.bg} rounded-full border-3 border-white shadow-lg flex items-center justify-center"><i class="fas fa-home text-white text-sm"></i></div>`;
                        
                        const marker = L.marker([pickup.lat, pickup.lng], {
                            icon: L.divIcon({
                                className: `custom-marker pickup-marker ${markerClass}`,
                                html: iconHtml,
                                iconSize: [32, 32],
                                iconAnchor: [16, 16]
                            })
                        }).addTo(map);
                        
                        const estimatedPoints = pickup.unit === 'kg' 
                            ? Math.floor(pickup.weight_kg * pickup.points_per_kg)
                            : Math.floor(pickup.quantity_pcs * pickup.points_per_unit);
                        
                        const completionInfo = pickup.status === 'Completed' 
                            ? `<div class="mt-2 p-2 bg-bote-green-50 border border-bote-green-200 rounded">
                                 <p class="text-xs text-bote-green-800 font-medium">✓ Completed on ${new Date(pickup.updated_at).toLocaleDateString()}</p>
                                 ${pickup.actual_weight ? `<p class="text-xs text-bote-green-700">Actual Weight: ${pickup.actual_weight} kg</p>` : ''}
                                 ${pickup.points_earned ? `<p class="text-xs text-bote-green-700">Points Earned: ${pickup.points_earned}</p>` : ''}
                               </div>`
                            : `<div class="mt-2">
                                 <button onclick="showCompletePickupModal(${pickup.request_id}, '${pickup.resident_name.replace(/'/g, "\\'")}', '${pickup.material_type.replace(/'/g, "\\'")}', '${pickup.unit}', ${pickup.weight_kg}, ${pickup.quantity_pcs || 0})" 
                                         class="w-full bg-bote-green-500 hover:bg-bote-green-600 text-white px-3 py-1 rounded text-xs font-medium transition-colors">
                                   Complete Pickup
                                 </button>
                               </div>`;
                        
                        marker.bindPopup(`
                            <div class="p-3 min-w-64">
                                <h3 class="font-semibold text-${statusInfo.color}-600 text-lg mb-2">${pickup.resident_name}</h3>
                                <div class="space-y-1 text-sm mb-3">
                                    <p><span class="font-medium">Address:</span> ${pickup.address}</p>
                                    <p><span class="font-medium">Material:</span> ${pickup.material_type}</p>
                                    <p><span class="font-medium">Weight:</span> ${pickup.weight_kg} kg</p>
                                    <p><span class="font-medium">Est. Points:</span> ${estimatedPoints}</p>
                                    <p><span class="font-medium">Status:</span> <span class="text-${statusInfo.color}-600">${pickup.status}</span></p>
                                    <p><span class="font-medium">Preferred Time:</span> ${new Date(pickup.preferred_date).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}</p>
                                </div>
                                ${completionInfo}
                            </div>
                        `);
                        
                        markers.push(marker);
                    });
                }
                
                // Add collection points markers
                if (data.collection_points) {
                    data.collection_points.forEach(point => {
                        const icon = L.divIcon({
                            html: `<div class="collection-point-marker bg-bote-green-600 text-white rounded-lg w-8 h-6 flex items-center justify-center text-xs font-bold shadow-lg">
                                     <i class="fas fa-warehouse"></i>
                                   </div>`,
                            className: 'custom-marker',
                            iconSize: [32, 24],
                            iconAnchor: [16, 12]
                        });
                        
                        const marker = L.marker([point.lat, point.lng], { icon: icon }).addTo(map);
                        
                        marker.bindPopup(`
                            <div class="p-3 min-w-64">
                                <h4 class="font-bold text-bote-stone-900 mb-2 flex items-center">
                                    <i class="fas fa-warehouse text-bote-green-600 mr-2"></i>
                                    ${point.name}
                                </h4>
                                <div class="space-y-1 text-sm">
                                    <p><strong>Type:</strong> Collection Point</p>
                                    <p><strong>Purok:</strong> ${point.purok_name || 'N/A'}</p>
                                    <p><strong>Description:</strong> ${point.description || 'No description'}</p>
                                    <div class="street-routing-indicator">
                                        <i class="fas fa-route mr-1"></i>Collection Point
                                    </div>
                                </div>
                            </div>
                        `);
                        
                        markers.push(marker);
                    });
                }
                
                // Draw optimized routes if available
                if (data.routes && data.routes.length > 0) {
                    data.routes.forEach(route => {
                        drawOptimizedRoute(route);
                    });
                }
                
                // Fit map to show all markers with padding
                if (markers.length > 0) {
                    const group = new L.featureGroup(markers);
                    map.fitBounds(group.getBounds().pad(0.1));
                } else {
                    // If no markers, show a message on the map
                    showNotification(`No pickup requests found for ${pageData.selectedDate}`, 'info');
                }
            } else {
                showNotification('Failed to load map data: ' + (data.message || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Error loading map data:', error);
            showNotification('Failed to load map data', 'error');
        });
    }

    // Draw optimized route on map with street routing
    function drawOptimizedRoute(route) {
        if (route.stops && route.stops.length > 1) {
            // Store current route data for navigation
            currentRoute = route;
            routeStops = route.stops;
            currentStopIndex = 0;
            
            // Show navigation controls
            showRouteNavigation();
            
            // If we have street geometry, use it; otherwise fall back to direct lines
            const streetGeometry = route.street_geometry;
            
            if (streetGeometry && streetGeometry.segments && streetGeometry.segments.length > 0) {
                // Draw actual street routes
                drawStreetRoutingPath(streetGeometry, route);
            } else {
                // Fallback to direct line routing
                drawDirectLineRoute(route);
            }
            
            // Add numbered markers for pickup stops
            route.stops.forEach((stop, index) => {
                let markerColor, iconClass, stopType, bgColor;
                
                if (stop.type === 'collection_point') {
                    markerColor = 'green';
                    iconClass = 'recycle';
                    stopType = 'Collection Point';
                    bgColor = 'bg-bote-green-500';
                } else if (stop.type === 'pickup') {
                    markerColor = 'blue';
                    iconClass = 'home';
                    stopType = 'Home Pickup';
                    bgColor = 'bg-bote-info-500';
                } else {
                    markerColor = 'red';
                    iconClass = 'flag-triangle-right';
                    stopType = 'Starting Point';
                    bgColor = 'bg-red-500';
                }
                
                // Create enhanced marker with stop number and type indicator
                const isCurrentStop = index === currentStopIndex;
                const currentStopClass = isCurrentStop ? 'current-active-stop' : '';
                const zIndexStyle = isCurrentStop ? 'z-index: 2000;' : 'z-index: 1000;';
                const scaleClass = isCurrentStop ? 'scale-125' : '';
                const glowEffect = isCurrentStop ? 'box-shadow: 0 0 25px rgba(255, 193, 7, 0.8), 0 0 50px rgba(255, 193, 7, 0.4);' : '';
                
                const markerHtml = `
                    <div class="route-marker-container relative ${currentStopClass}" style="${zIndexStyle} ${glowEffect}">
                        <div class="w-10 h-10 ${bgColor} rounded-full border-3 border-white shadow-xl flex items-center justify-center relative marker-${index} ${scaleClass} transition-all duration-300">
                            <i data-lucide="${iconClass}" class="w-5 h-5 text-white"></i>
                            <div class="absolute -top-2 -right-2 w-6 h-6 bg-white rounded-full border-2 border-${markerColor}-500 flex items-center justify-center">
                                <span class="text-xs text-${markerColor}-700 font-bold">${index}</span>
                            </div>
                            ${isCurrentStop ? '<div class="absolute inset-0 border-4 border-yellow-400 rounded-full animate-pulse"></div>' : ''}
                        </div>
                        ${stop.status === 'Completed' ? 
                            '<div class="absolute -bottom-1 -right-1 w-4 h-4 bg-bote-green-500 rounded-full border-2 border-white flex items-center justify-center"><i data-lucide="check" class="w-2 h-2 text-white"></i></div>' : 
                            ''
                        }
                    </div>
                `;
                
                const numberMarker = L.divIcon({
                    className: 'route-number-marker custom-marker',
                    html: markerHtml,
                    iconSize: [40, 40],
                    iconAnchor: [20, 20]
                });
                
                const stopMarker = L.marker([stop.lat, stop.lng], { icon: numberMarker }).addTo(map);
                
                // Enhanced popup with more information
                const statusBadge = stop.status === 'Completed' ? 
                    '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-bote-green-100 text-bote-green-800"><i data-lucide="check-circle" class="w-3 h-3 mr-1"></i>Completed</span>' :
                    '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"><i data-lucide="clock" class="w-3 h-3 mr-1"></i>Scheduled</span>';
                
                const estimatedTime = index * 15; // Rough estimate: 15 minutes per stop
                const timeFromNow = new Date(Date.now() + estimatedTime * 60000).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                
                const popupContent = `
                    <div class="p-4 min-w-64">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 ${bgColor} rounded-full flex items-center justify-center">
                                    <i data-lucide="${iconClass}" class="w-4 h-4 text-white"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-bote-stone-900">Stop ${index}</div>
                                    <div class="text-xs text-bote-stone-500">${stopType}</div>
                                </div>
                            </div>
                            ${statusBadge}
                        </div>
                        
                        <div class="space-y-2 mb-3">
                            <div class="text-sm font-semibold text-bote-stone-800">${stop.resident_name || stop.name || 'Stop ' + index}</div>
                            ${stop.material_type ? `<div class="text-xs text-bote-info-600 bg-bote-info-50 px-2 py-1 rounded"><i data-lucide="package" class="w-3 h-3 inline mr-1"></i>Material: ${stop.material_type}</div>` : ''}
                            ${stop.address ? `<div class="text-xs text-bote-stone-600"><i data-lucide="map-pin" class="w-3 h-3 inline mr-1"></i>${stop.address}</div>` : ''}
                        </div>
                        
                        <div class="border-t pt-2 space-y-1">
                            <div class="text-xs text-bote-stone-500"><i data-lucide="clock" class="w-3 h-3 inline mr-1"></i>Est. arrival: ${timeFromNow}</div>
                            <div class="text-xs text-bote-stone-500"><i data-lucide="navigation" class="w-3 h-3 inline mr-1"></i>Lat: ${stop.lat.toFixed(4)}, Lng: ${stop.lng.toFixed(4)}</div>
                        </div>
                        
                        <div class="flex space-x-2 mt-3">
                            <button onclick="centerOnStop(${index})" class="flex-1 bg-bote-green-500 text-white px-3 py-2 rounded text-xs hover:bg-bote-green-600 transition-colors">
                                <i data-lucide="focus" class="w-3 h-3 inline mr-1"></i>Center Map
                            </button>
                           
                        </div>
                    </div>
                `;
                
                stopMarker.bindPopup(popupContent);
                stopMarkers.push(stopMarker);
                markers.push(stopMarker);
            });
            
            // Add route information control
            addRouteInfoControl(route);
            
            // Update navigation controls
            updateNavigationControls();
            
            // Ensure current stop is brought to front
            setTimeout(() => {
                updateMarkerZIndexes();
            }, 100);
        }
    }

    // Draw street routing path using OSRM geometry
    function drawStreetRoutingPath(streetGeometry, route) {
        if (!streetGeometry || !streetGeometry.segments || streetGeometry.segments.length === 0) {
            console.log('No street geometry available, falling back to direct lines');
            drawDirectLineRoute(route);
            return;
        }
        
        streetGeometry.segments.forEach((segment, index) => {
            if (segment.coordinates && segment.coordinates.length > 1) {
                // Convert coordinates to Leaflet format [lat, lng]
                const latLngCoords = segment.coordinates.map(coord => [coord[1], coord[0]]);
                
                const isReturnSegment = segment.is_return || false;
                const isFallback = segment.fallback || false;
                
                // Choose colors and styles based on segment type
                let routeColor, routeWeight, routeOpacity, routeDashArray;
                
                if (isReturnSegment) {
                    routeColor = '#16a34a'; // Green for return
                    routeWeight = isFallback ? 3 : 4;
                    routeOpacity = 0.8;
                    routeDashArray = '8, 12'; // Longer dashes for return
                } else {
                    routeColor = '#dc2626'; // Red for pickup route
                    routeWeight = isFallback ? 3 : 5;
                    routeOpacity = 0.9;
                    routeDashArray = isFallback ? '15, 10' : '10, 5';
                }
                
                const routeLine = L.polyline(latLngCoords, {
                    color: routeColor,
                    weight: routeWeight,
                    opacity: routeOpacity,
                    dashArray: routeDashArray,
                    className: isFallback ? 'fallback-route' : 'street-route'
                }).addTo(map);
                
                // Add popup with segment information
                const distance = segment.distance ? (segment.distance / 1000).toFixed(2) : 'Unknown';
                const duration = segment.duration ? Math.round(segment.duration / 60) : 'Unknown';
                
                routeLine.bindPopup(`
                    <div class="p-3">
                        <h4 class="font-semibold ${isReturnSegment ? 'text-bote-green-600' : 'text-red-600'} mb-2">
                            <i class="fas fa-route mr-2"></i>
                            ${isReturnSegment ? 'Return Journey' : `Route Segment ${index + 1}`}
                        </h4>
                        <div class="space-y-1 text-sm">
                            <p><strong>From:</strong> ${segment.from_stop || 'Previous Stop'}</p>
                            <p><strong>To:</strong> ${segment.to_stop || 'Next Stop'}</p>
                            <p><strong>Distance:</strong> ${distance} km</p>
                            <p><strong>Est. Time:</strong> ${duration} min</p>
                            ${isFallback ? 
                                '<p class="text-xs text-yellow-600 mt-2"><i class="fas fa-exclamation-triangle mr-1"></i>Direct route (street data unavailable)</p>' : 
                                '<p class="text-xs text-bote-green-600 mt-2"><i class="fas fa-check-circle mr-1"></i>Actual street routing</p>'
                            }
                        </div>
                    </div>
                `);
                
                routeLines.push(routeLine);
            }
        });
        
        // Add route statistics
        if (streetGeometry.total_distance > 0) {
            const totalDistanceKm = (streetGeometry.total_distance / 1000).toFixed(2);
            const totalDurationHours = Math.round(streetGeometry.total_duration / 3600 * 100) / 100;
            
            const routeStatsControl = L.control({position: 'topleft'});
            routeStatsControl.onAdd = function(map) {
                const div = L.DomUtil.create('div', 'route-stats-control');
                div.innerHTML = `
                    <div class="bg-white p-3 rounded-lg shadow-lg border-l-4 border-bote-info-500">
                        <h4 class="font-semibold text-bote-stone-900 mb-2 flex items-center">
                            <i class="fas fa-chart-line mr-2 text-bote-info-600"></i>
                            Route Statistics
                        </h4>
                        <div class="space-y-1 text-sm text-bote-stone-600">
                            <p><strong>Total Distance:</strong> ${totalDistanceKm} km</p>
                            <p><strong>Total Duration:</strong> ${totalDurationHours}h</p>
                            <p><strong>Routing Method:</strong> Street Navigation</p>
                        </div>
                        <div class="mt-2 text-xs text-bote-green-600">
                            <i class="fas fa-route mr-1"></i>Optimized path with street routing
                        </div>
                    </div>
                `;
                return div;
            };
            routeStatsControl.addTo(map);
        }
    }

    // Fallback to direct line routing
    function drawDirectLineRoute(route) {
        const routeCoords = route.stops.map(stop => [stop.lat, stop.lng]);
        
        // Add return path to starting point
        if (routeCoords.length > 0) {
            routeCoords.push(routeCoords[0]); // Return to start
        }
        
        // Create main route line (red dashed for pickup route)
        const outboundRoute = L.polyline(routeCoords.slice(0, -1), {
            color: '#dc2626',
            weight: 4,
            opacity: 0.8,
            dashArray: '10, 5'
        }).addTo(map);
        
        // Create return route line (green solid line)
        if (routeCoords.length > 1) {
            const returnRoute = L.polyline([routeCoords[routeCoords.length-2], routeCoords[routeCoords.length-1]], {
                color: '#16a34a',
                weight: 4,
                opacity: 0.8,
                dashArray: '5, 10'
            }).addTo(map);
            routeLines.push(returnRoute);
        }
        
        routeLines.push(outboundRoute);
    }

    // Add route information control to map
    function addRouteInfoControl(route) {
        if (route.total_distance || route.stops) {
            const infoControl = L.control({position: 'topright'});
            infoControl.onAdd = function(map) {
                const div = L.DomUtil.create('div', 'route-info-control');
                
                const pickupCount = route.stops ? route.stops.filter(s => s.type === 'pickup').length : 0;
                const collectionCount = route.stops ? route.stops.filter(s => s.type === 'collection_point').length : 0;
                const totalStops = pickupCount + collectionCount;
                
                div.innerHTML = `
                    <div class="bg-white p-4 rounded-lg shadow-lg border border-bote-stone-200">
                        <h4 class="font-semibold text-bote-stone-900 mb-3 flex items-center">
                            <i class="fas fa-route mr-2 text-bote-info-600"></i>
                            Route Summary
                        </h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-bote-stone-600">Total Distance:</span>
                                <span class="font-medium text-bote-stone-900">${route.total_distance ? route.total_distance.toFixed(2) : 'Calculating...'} km</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-bote-stone-600">Est. Duration:</span>
                                <span class="font-medium text-bote-stone-900">${route.estimated_time || 'Calculating...'}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-bote-stone-600">Home Pickups:</span>
                                <span class="font-medium text-bote-info-600">${pickupCount}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-bote-stone-600">Collection Points:</span>
                                <span class="font-medium text-bote-green-600">${collectionCount}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-bote-stone-600">Total Stops:</span>
                                <span class="font-medium text-bote-stone-900">${totalStops}</span>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-bote-stone-200">
                            <div class="flex items-center space-x-2 text-xs">
                                <div class="w-2 h-2 bg-bote-green-500 rounded-full"></div>
                                <span class="text-bote-green-600 font-medium">Street Navigation Enabled</span>
                            </div>
                            <div class="flex items-center space-x-2 text-xs mt-1">
                                <div class="w-2 h-2 bg-bote-green-500 rounded-full"></div>
                                <span class="text-bote-info-600 font-medium">Auto-Optimized Route</span>
                            </div>
                        </div>
                    </div>
                `;
                return div;
            };
            infoControl.addTo(map);
        }
    }

    // Clear existing map data
    function clearMapData() {
        markers.forEach(marker => map.removeLayer(marker));
        routeLines.forEach(line => map.removeLayer(line));
        markers = [];
        routeLines = [];
        stopMarkers = [];
        routeStops = [];
        currentStopIndex = 0;
        hideRouteNavigation();
    }

    // Calculate optimal route using nearest neighbor algorithm
    function calculateOptimalRoute(barangay, pickups) {
        if (!pickups || pickups.length === 0) return [];
        
        const remaining = [...pickups];
        const route = [barangay];
        let current = barangay;
        
        while (remaining.length > 0) {
            let nearestIndex = 0;
            let nearestDistance = getDistance(current, remaining[0]);
            
            for (let i = 1; i < remaining.length; i++) {
                const distance = getDistance(current, remaining[i]);
                if (distance < nearestDistance) {
                    nearestDistance = distance;
                    nearestIndex = i;
                }
            }
            
            current = remaining[nearestIndex];
            route.push(current);
            remaining.splice(nearestIndex, 1);
        }
        
        return route;
    }

    // Calculate distance between two points using Haversine formula
    function getDistance(point1, point2) {
        const R = 6371; // Earth's radius in km
        const dLat = (point2.lat - point1.lat) * Math.PI / 180;
        const dLng = (point2.lng - point1.lng) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(point1.lat * Math.PI / 180) * Math.cos(point2.lat * Math.PI / 180) *
                Math.sin(dLng/2) * Math.sin(dLng/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }

    // View all worker's pickups on the map
    function viewAllPickups() {
        // Focus map on worker's pickups and highlight them
        fetch('get_map_data.php?focus=worker_pickups')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.worker_pickups.length > 0) {
                clearMapData();
                loadRouteData();
                
                // Create a popup showing pickup summary
                setTimeout(() => {
                    const totalPickups = data.worker_pickups.length;
                    const scheduledPickups = data.worker_pickups.filter(p => p.status === 'Scheduled').length;
                    const completedPickups = data.worker_pickups.filter(p => p.status === 'Completed').length;
                    
                    const summaryPopup = L.popup()
                        .setLatLng(map.getCenter())
                        .setContent(`
                            <div class="p-3">
                                <h3 class="font-semibold text-bote-info-600 mb-2">Your Pickup Summary</h3>
                                <p class="text-sm text-bote-stone-600">Total Pickups: <span class="font-medium">${totalPickups}</span></p>
                                <p class="text-sm text-bote-stone-600">Scheduled: <span class="font-medium text-bote-info-600">${scheduledPickups}</span></p>
                                <p class="text-sm text-bote-stone-600">Completed: <span class="font-medium text-bote-green-600">${completedPickups}</span></p>
                                ${scheduledPickups > 0 ? '<p class="text-xs text-orange-600 mt-2">💡 Generate an optimal route to save fuel!</p>' : ''}
                            </div>
                        `)
                        .openOn(map);
                }, 1000);
            } else {
                alert('No pickups found for your account');
            }
        })
        .catch(error => {
            console.error('Error loading pickups:', error);
        });
    }
    function viewRouteDetails(routeId) {
        // Load route details via AJAX
        fetch(`get_route_details.php?route_id=${routeId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('routeContent').innerHTML = data.html;
                showRouteModal();
                
                // Highlight route on map if available
                highlightRouteOnMap(routeId);
            } else {
                alert('Failed to load route details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while loading route details');
        });
    }

    function highlightRouteOnMap(routeId) {
        // Fetch route coordinates and highlight on map
        fetch(`get_route_coordinates.php?route_id=${routeId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.coordinates) {
                // Clear existing highlights
                clearMapData();
                
                // Redraw with highlighted route
                loadRouteData();
                
                // Add special highlighting for selected route
                setTimeout(() => {
                    data.coordinates.forEach((coord, index) => {
                        const highlightMarker = L.circleMarker([coord.lat, coord.lng], {
                            radius: 8,
                            color: '#ff0000',
                            weight: 3,
                            fillColor: '#ff0000',
                            fillOpacity: 0.5
                        }).addTo(map);
                        
                        markers.push(highlightMarker);
                    });
                }, 500);
            }
        })
        .catch(error => console.error('Error highlighting route:', error));
    }

    function startRoute(routeId) {
        // Show route details in modal instead of separate page
        viewRouteDetails(routeId);
    }

    function showRouteModal() {
        const modal = document.getElementById('routeModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
        setTimeout(() => {
            modal.querySelector('div > div').classList.remove('scale-95');
            modal.querySelector('div > div').classList.add('scale-100');
        }, 10);
    }

    function hideRouteModal() {
        const modal = document.getElementById('routeModal');
        modal.querySelector('div > div').classList.remove('scale-100');
        modal.querySelector('div > div').classList.add('scale-95');
        document.body.style.overflow = ''; // Restore scrolling
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 300);
    }

    // Close modal when clicking outside
    document.getElementById('routeModal').addEventListener('click', function(e) {
        if (e.target === this) {
            hideRouteModal();
        }
    });

    // Initialize map when page loads
    document.addEventListener('DOMContentLoaded', function() {
        // Add Leaflet CSS and JS
        const leafletCSS = document.createElement('link');
        leafletCSS.rel = 'stylesheet';
        leafletCSS.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(leafletCSS);
        
        const leafletJS = document.createElement('script');
        leafletJS.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        leafletJS.onload = function() {
            initializeMap();
        };
        document.head.appendChild(leafletJS);
        
        // Add Font Awesome for icons
        const fontAwesome = document.createElement('link');
        fontAwesome.rel = 'stylesheet';
        fontAwesome.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css';
        document.head.appendChild(fontAwesome);
    });

    // Route Navigation Functions
    function showRouteNavigation() {
        document.getElementById('routeNavigation').classList.remove('hidden');
    }

    function hideRouteNavigation() {
        document.getElementById('routeNavigation').classList.add('hidden');
    }

    function updateNavigationControls() {
        if (!routeStops || routeStops.length === 0) return;
        
        const prevBtn = document.getElementById('prevStopBtn');
        const nextBtn = document.getElementById('nextStopBtn');
        const currentStopInfo = document.getElementById('currentStopInfo');
        
        // Update stop info
        const currentStop = routeStops[currentStopIndex];
        const stopType = currentStop?.type === 'pickup' ? 'Pickup' : currentStop?.type === 'collection_point' ? 'Collection' : 'Start';
        currentStopInfo.innerHTML = `Stop ${currentStopIndex + 1} of ${routeStops.length} <span class="text-xs">(${stopType})</span>`;
        
        // Update button states
        prevBtn.disabled = currentStopIndex === 0;
        nextBtn.disabled = currentStopIndex === routeStops.length - 1;
        
        // Update button text with icons
        if (currentStopIndex === 0) {
            nextBtn.innerHTML = '<i data-lucide="play" class="w-4 h-4"></i> Start <i data-lucide="chevron-right" class="w-4 h-4"></i>';
        } else if (currentStopIndex === routeStops.length - 1) {
            nextBtn.innerHTML = '<i data-lucide="flag" class="w-4 h-4"></i> Finish';
        } else {
            nextBtn.innerHTML = 'Next <i data-lucide="chevron-right" class="w-4 h-4"></i>';
        }
        
        // Update route summary
        updateRouteSummary();
        
        // Refresh lucide icons
        if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
    }
    
    function updateRouteSummary() {
        if (!routeStops) return;
        
        const totalStops = routeStops.length;
        const pickupCount = routeStops.filter(stop => stop.type === 'pickup').length;
        const collectionCount = routeStops.filter(stop => stop.type === 'collection_point').length;
        const estimatedTotalTime = totalStops * 15; // 15 minutes per stop estimate
        
        document.getElementById('totalStops').textContent = `${totalStops} stops`;
        document.getElementById('pickupCount').textContent = `${pickupCount} pickups`;
        document.getElementById('collectionCount').textContent = `${collectionCount} points`;
        document.getElementById('estimatedTime').textContent = `${estimatedTotalTime} min`;
        
        // Show the summary
        document.getElementById('routeSummary').classList.remove('hidden');
    }

    function navigateToNextStop() {
        if (!routeStops || currentStopIndex >= routeStops.length - 1) return;
        
        currentStopIndex++;
        centerOnStop(currentStopIndex);
        updateNavigationControls();
        highlightCurrentStop();
        updateMarkerZIndexes();
    }

    function navigateToPreviousStop() {
        if (!routeStops || currentStopIndex <= 0) return;
        
        currentStopIndex--;
        centerOnStop(currentStopIndex);
        updateNavigationControls();
        highlightCurrentStop();
        updateMarkerZIndexes();
    }

    function centerOnStop(stopIndex) {
        if (!routeStops || stopIndex < 0 || stopIndex >= routeStops.length) return;
        
        const stop = routeStops[stopIndex];
        currentStopIndex = stopIndex;
        
        // Center map on the stop
        map.setView([stop.lat, stop.lng], 16, {
            animate: true,
            pan: {
                animate: true,
                duration: 1
            }
        });
        
        // Open popup for current stop
        if (stopMarkers[stopIndex]) {
            stopMarkers[stopIndex].openPopup();
        }
        
        updateNavigationControls();
        highlightCurrentStop();
        updateMarkerZIndexes();
    }

    // Update marker z-indexes to bring current stop to front
    function updateMarkerZIndexes() {
        stopMarkers.forEach((marker, index) => {
            if (marker && marker._icon) {
                const container = marker._icon.querySelector('.route-marker-container');
                if (container) {
                    if (index === currentStopIndex) {
                        // Current stop gets highest z-index
                        container.style.zIndex = '2000';
                        container.style.position = 'relative';
                        // Add current stop styling
                        container.classList.add('current-active-stop');
                        const markerDiv = container.querySelector(`div[class*="marker-${index}"]`);
                        if (markerDiv) {
                            markerDiv.classList.add('scale-125', 'animate-pulse');
                            markerDiv.style.borderColor = '#fbbf24'; // yellow border
                            markerDiv.style.borderWidth = '4px';
                        }
                    } else {
                        // Other stops get normal z-index
                        container.style.zIndex = '1000';
                        // Remove current stop styling
                        container.classList.remove('current-active-stop');
                        const markerDiv = container.querySelector(`div[class*="marker-${index}"]`);
                        if (markerDiv) {
                            markerDiv.classList.remove('scale-125', 'animate-pulse');
                            markerDiv.style.borderColor = 'white';
                            markerDiv.style.borderWidth = '3px';
                        }
                    }
                }
            }
        });
    }

    function highlightCurrentStop() {
        // Remove previous highlighting
        stopMarkers.forEach((marker, index) => {
            if (marker._icon) {
                marker._icon.style.transform = 'scale(1)';
                marker._icon.style.zIndex = '1000';
                marker._icon.classList.remove('current-stop-active', 'pulse-animation');
                
                // Remove any existing glow effects
                const container = marker._icon.querySelector('.route-marker-container');
                if (container) {
                    container.style.boxShadow = '';
                    container.style.animation = '';
                }
            }
        });
        
        // Highlight current stop with enhanced effects
        if (stopMarkers[currentStopIndex] && stopMarkers[currentStopIndex]._icon) {
            const currentIcon = stopMarkers[currentStopIndex]._icon;
            const container = currentIcon.querySelector('.route-marker-container');
            
            // Apply enhanced styling
            currentIcon.style.transform = 'scale(1.4)';
            currentIcon.style.zIndex = '1001';
            currentIcon.classList.add('current-stop-active', 'pulse-animation');
            
            if (container) {
                // Add glowing effect
                container.style.boxShadow = '0 0 30px rgba(255, 193, 7, 0.8), 0 0 60px rgba(255, 193, 7, 0.4)';
                container.style.animation = 'currentStopPulse 2s infinite';
                
                // Add a rotating border effect
                const marker = container.querySelector(`div[class*="marker-${currentStopIndex}"]`);
                if (marker) {
                    marker.style.animation = 'currentStopRotate 3s linear infinite';
                }
            }
            
            // Auto-remove effects after a few seconds
            setTimeout(() => {
                if (stopMarkers[currentStopIndex] && stopMarkers[currentStopIndex]._icon) {
                    const icon = stopMarkers[currentStopIndex]._icon;
                    icon.classList.remove('pulse-animation');
                    
                    const cont = icon.querySelector('.route-marker-container');
                    if (cont) {
                        cont.style.animation = 'none';
                        const markerDiv = cont.querySelector(`div[class*="marker-${currentStopIndex}"]`);
                        if (markerDiv) {
                            markerDiv.style.animation = 'none';
                        }
                    }
                }
            }, 3000);
        }
    }

    function resetMapView() {
        if (markers.length > 0) {
            const group = new L.featureGroup(markers);
            map.fitBounds(group.getBounds().pad(0.1), {
                animate: true,
                pan: {
                    animate: true,
                    duration: 1
                }
            });
            
            // Reset to first stop
            currentStopIndex = 0;
            updateNavigationControls();
            
            // Remove highlighting
            stopMarkers.forEach((marker) => {
                if (marker._icon) {
                    marker._icon.style.transform = 'scale(1)';
                    marker._icon.style.zIndex = '1000';
                }
            });
        }
    }

window.changeDate = changeDate;
window.showCompletePickupModal = showCompletePickupModal;
window.hideCompletePickupModal = hideCompletePickupModal;
window.submitPickupCompletion = submitPickupCompletion;
window.sortPickupsByTime = sortPickupsByTime;
window.showOnMapWithLoading = showOnMapWithLoading;
window.showOnMap = showOnMap;
window.centerMapOnLocation = centerMapOnLocation;
window.viewAllPickups = viewAllPickups;
window.viewRouteDetails = viewRouteDetails;
window.highlightRouteOnMap = highlightRouteOnMap;
window.startRoute = startRoute;
window.showRouteModal = showRouteModal;
window.hideRouteModal = hideRouteModal;
window.navigateToPreviousStop = navigateToPreviousStop;
window.navigateToNextStop = navigateToNextStop;
window.resetMapView = resetMapView;
window.centerOnStop = centerOnStop;

export {};
