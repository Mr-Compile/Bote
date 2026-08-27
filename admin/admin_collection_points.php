<?php
require_once '../config/database.php';
require_once '../includes/auth_admin.php';
include 'admin_sidebar.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collection Points Management - Waste Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script type="module" src="../assets/js/theme.js"></script>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" 
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" 
          crossorigin=""/>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    
    <style>
        body {
            box-sizing: border-box;
        }
        
        .success-message, .error-message {
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .form-card {
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
        }
        
        .input-focus:focus {
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
        }
        
        /* Map Styles */
        #map {
            height: 400px;
            width: 100%;
            border-radius: 0.75rem;
            border: 2px solid #e5e7eb;
        }
        
        .map-container {
            position: relative;
            background: #f3f4f6;
            border-radius: 0.75rem;
            overflow: hidden;
        }
        
        .map-loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 1rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
        
        /* Modal Styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .modal.show {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-content {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            max-width: 90vw;
            max-height: 90vh;
            overflow-y: auto;
            transform: scale(0.9) translateY(20px);
            transition: transform 0.3s ease;
        }
        
        .modal.show .modal-content {
            transform: scale(1) translateY(0);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-bottom: 1px solid #e2e8f0;
            border-radius: 1rem 1rem 0 0;
            padding: 1.5rem;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            border-radius: 0 0 1rem 1rem;
            padding: 1rem 1.5rem;
        }
        
        /* Ensure map is behind modal */
        .leaflet-container {
            z-index: 1;
        }
        
        /* Small modal for confirmations */
        .modal-sm .modal-content {
            max-width: 28rem;
        }
        
        /* Large modal for forms */
        .modal-lg .modal-content {
            max-width: 48rem;
        }
        
        /* Extra large modal for detailed views */
        .modal-xl .modal-content {
            max-width: 72rem;
        }
        
        .coordinate-display {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid #e5e7eb;
        }
        
        /* Loading animation */
        .loading-spinner {
            border: 3px solid #f3f4f6;
            border-top: 3px solid #10b981;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .status-badge {
            @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium;
        }
        
        .status-active { @apply bg-bote-green-100 text-bote-green-800; }
        .status-inactive { @apply bg-red-100 text-red-800; }
        .status-maintenance { @apply bg-yellow-100 text-yellow-800; }
        
        /* Custom Tooltip Styles for Map Labels */
        .custom-tooltip {
            background: rgba(255, 255, 255, 0.95) !important;
            border: 2px solid #10b981 !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
            color: #1f2937 !important;
            font-weight: 600 !important;
            font-size: 12px !important;
            padding: 6px 10px !important;
            white-space: nowrap !important;
        }
        
        .custom-tooltip::before {
            border-top-color: #10b981 !important;
        }
        
        /* Custom Marker Styles */
        .custom-marker {
            background: transparent !important;
            border: none !important;
        }
        
        /* Leaflet Popup Styling */
        .leaflet-popup-content-wrapper {
            border-radius: 12px !important;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15) !important;
        }
        
        .leaflet-popup-tip {
            box-shadow: 0 3px 14px rgba(0,0,0,0.15) !important;
        }
        
        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0 !important;
                padding-top: 80px; /* Space for mobile header if needed */
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-bote-green-50 to-bote-info-50 min-h-screen">
    <!-- Main Content Area with Sidebar Offset -->
    <div class="ml-64 p-4 md:p-6 main-content">
        <div class="max-w-6xl mx-auto">
        <!-- Alert Messages -->
        <div id="alertContainer" class="mb-6"></div>

        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-bote-stone-800 mb-2 flex items-center">
                        <i data-lucide="map-pin" class="w-8 h-8 mr-3 text-bote-green-600"></i>
                        Collection Points Management
                    </h1>
                    <p class="text-bote-stone-600 text-lg">Create and manage designated locations for waste collection in each Purok.</p>
                </div>
                <div class="flex space-x-4">
                    <button onclick="openAddModal()" 
                            class="bg-bote-green-600 hover:bg-bote-green-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors duration-200 flex items-center">
                        <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                        Add Collection Point
                    </button>
                </div>
            </div>
        </div>

        <!-- Existing Collection Points List -->
        <div class="bg-white rounded-xl shadow-lg border border-bote-stone-200 mb-8">
            <div class="p-6 border-b border-bote-stone-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-bote-info-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="list" class="w-4 h-4 text-bote-info-600"></i>
                        </div>
                        <h2 class="text-xl font-semibold text-bote-stone-800">Existing Collection Points</h2>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button onclick="refreshCollectionPointsList()" 
                                class="px-3 py-1.5 bg-bote-stone-100 hover:bg-bote-stone-200 text-bote-stone-700 rounded-lg transition-colors duration-200 flex items-center text-sm">
                            <i data-lucide="refresh-cw" class="w-4 h-4 mr-1"></i>
                            Refresh
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="p-6">
                <div id="collectionPointsTable" class="overflow-x-auto">
                    <div class="flex items-center justify-center py-8">
                        <div class="loading-spinner mr-2"></div>
                        <span class="text-bote-stone-500">Loading collection points...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Map View Section -->
        <div class="bg-white rounded-xl shadow-lg border border-bote-stone-200 p-6 md:p-8">
            <h2 class="text-xl font-semibold text-bote-stone-800 mb-4 flex items-center gap-2">
                <i data-lucide="map" class="w-6 h-6 text-bote-green-600"></i>
                Collection Points Map View
            </h2>
            
            <div class="map-container mb-4">
                <div id="mapLoading" class="map-loading">
                    <div class="flex items-center space-x-2">
                        <div class="loading-spinner"></div>
                        <span>Loading map...</span>
                    </div>
                </div>
                <div id="map"></div>
            </div>
            
            <!-- Legend -->
            <div class="bg-bote-stone-50 border border-bote-stone-200 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-bote-stone-700 mb-2 flex items-center gap-2">
                    <i data-lucide="info" class="w-4 h-4"></i>
                    Map Legend
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="flex items-center space-x-2 text-sm">
                        <div class="w-3 h-3 bg-bote-green-500 rounded-full"></div>
                        <span>Active</span>
                    </div>
                    <div class="flex items-center space-x-2 text-sm">
                        <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                        <span>Inactive</span>
                    </div>
                    <div class="flex items-center space-x-2 text-sm">
                        <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                        <span>Maintenance</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Collection Point Modal -->
    <div id="collectionPointModal" class="modal modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <div class="flex items-center justify-between">
                    <h2 id="modalTitle" class="text-2xl font-bold text-bote-stone-800">Add Collection Point</h2>
                    <button onclick="closeModal('collectionPointModal')" class="text-bote-stone-400 hover:text-bote-stone-600 transition-colors">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>
            
            <div class="modal-body">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Form Section -->
                    <div class="space-y-6">
                        <form id="modalCollectionPointForm" class="space-y-6">
                            <?php echo \App\Core\Csrf::field(); ?>
                            <input type="hidden" id="editPointId" name="point_id">
                            
                            <!-- Basic Information Section -->
                            <div class="border-b border-bote-stone-200 pb-6">
                                <h3 class="text-lg font-semibold text-bote-stone-800 mb-4 flex items-center">
                                    <i data-lucide="info" class="w-5 h-5 mr-2 text-bote-info-600"></i>
                                    Basic Information
                                </h3>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label for="modalCollectionPointName" class="block text-sm font-medium text-bote-stone-700 mb-2">
                                            Collection Point Name *
                                        </label>
                                        <input type="text" id="modalCollectionPointName" name="name" required
                                            class="w-full px-4 py-3 border border-bote-stone-300 rounded-lg focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500 input-focus transition-colors"
                                            placeholder="e.g., Main Street Collection Hub">
                                    </div>

                                    <div>
                                        <label for="modalPurok" class="block text-sm font-medium text-bote-stone-700 mb-2">
                                            Purok *
                                        </label>
                                        <select id="modalPurok" name="purok_id" required
                                            class="w-full px-4 py-3 border border-bote-stone-300 rounded-lg focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500 input-focus transition-colors">
                                            <option value="">Loading puroks...</option>
                                        </select>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label for="modalStatus" class="block text-sm font-medium text-bote-stone-700 mb-2">
                                                Status *
                                            </label>
                                            <select id="modalStatus" name="status" required
                                                class="w-full px-4 py-3 border border-bote-stone-300 rounded-lg focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500 input-focus transition-colors">
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                                <option value="maintenance">Maintenance</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label for="modalCapacity" class="block text-sm font-medium text-bote-stone-700 mb-2">
                                                Capacity (kg)
                                            </label>
                                            <input type="number" id="modalCapacity" name="capacity" min="0" step="0.1"
                                                class="w-full px-4 py-3 border border-bote-stone-300 rounded-lg focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500 input-focus transition-colors"
                                                placeholder="e.g., 500">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Location Section -->
                            <div class="border-b border-bote-stone-200 pb-6">
                                <h3 class="text-lg font-semibold text-bote-stone-800 mb-4 flex items-center">
                                    <i data-lucide="navigation" class="w-5 h-5 mr-2 text-bote-info-600"></i>
                                    Location Coordinates
                                </h3>
                                
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label for="modalLatitude" class="block text-sm font-medium text-bote-stone-700 mb-2">
                                            Latitude *
                                        </label>
                                        <input type="number" id="modalLatitude" name="latitude" required step="any"
                                            class="w-full px-4 py-3 border border-bote-stone-300 rounded-lg focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500 input-focus transition-colors"
                                            placeholder="e.g., 14.5995" readonly>
                                    </div>

                                    <div>
                                        <label for="modalLongitude" class="block text-sm font-medium text-bote-stone-700 mb-2">
                                            Longitude *
                                        </label>
                                        <input type="number" id="modalLongitude" name="longitude" required step="any"
                                            class="w-full px-4 py-3 border border-bote-stone-300 rounded-lg focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500 input-focus transition-colors"
                                            placeholder="e.g., 120.9842" readonly>
                                    </div>
                                </div>
                                
                                <div class="bg-bote-info-50 border border-bote-info-200 rounded-lg p-3 text-sm text-bote-info-800 mb-4">
                                    <i data-lucide="info" class="w-4 h-4 inline mr-1"></i>
                                    <strong>Instructions:</strong> Click on the map to set the exact location of your collection point.
                                </div>
                                
                                <!-- Map Controls -->
                                <div class="flex gap-2 mb-4">
                                    <button type="button" onclick="getModalCurrentLocation()" 
                                            class="px-4 py-2 bg-bote-info-100 text-bote-info-700 rounded-lg hover:bg-bote-info-200 transition-colors flex items-center gap-2">
                                        <i data-lucide="crosshair" class="w-4 h-4"></i>
                                        Use My Location
                                    </button>
                                    <button type="button" onclick="clearModalLocation()" 
                                            class="px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors flex items-center gap-2">
                                        <i data-lucide="x-circle" class="w-4 h-4"></i>
                                        Clear
                                    </button>
                                </div>
                            </div>

                            <!-- Contact & Operations Section -->
                            <div class="border-b border-bote-stone-200 pb-6">
                                <h3 class="text-lg font-semibold text-bote-stone-800 mb-4 flex items-center">
                                    <i data-lucide="phone" class="w-5 h-5 mr-2 text-bote-green-600"></i>
                                    Contact & Operations
                                </h3>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label for="modalContactPerson" class="block text-sm font-medium text-bote-stone-700 mb-2">
                                            Contact Person
                                        </label>
                                        <input type="text" id="modalContactPerson" name="contact_person"
                                            class="w-full px-4 py-3 border border-bote-stone-300 rounded-lg focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500 input-focus transition-colors"
                                            placeholder="e.g., Juan Dela Cruz">
                                    </div>

                                    <div>
                                        <label for="modalContactPhone" class="block text-sm font-medium text-bote-stone-700 mb-2">
                                            Contact Phone
                                        </label>
                                        <input type="tel" id="modalContactPhone" name="contact_phone"
                                            class="w-full px-4 py-3 border border-bote-stone-300 rounded-lg focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500 input-focus transition-colors"
                                            placeholder="e.g., +63 912 345 6789">
                                    </div>

                                    <div>
                                        <label for="modalOperatingHours" class="block text-sm font-medium text-bote-stone-700 mb-2">
                                            Operating Hours
                                        </label>
                                        <input type="text" id="modalOperatingHours" name="operating_hours" value="24/7"
                                            class="w-full px-4 py-3 border border-bote-stone-300 rounded-lg focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500 input-focus transition-colors"
                                            placeholder="e.g., 6:00 AM - 6:00 PM">
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Information Section -->
                            <div>
                                <h3 class="text-lg font-semibold text-bote-stone-800 mb-4 flex items-center">
                                    <i data-lucide="file-text" class="w-5 h-5 mr-2 text-bote-info-600"></i>
                                    Additional Information
                                </h3>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label for="modalDescription" class="block text-sm font-medium text-bote-stone-700 mb-2">
                                            Description
                                        </label>
                                        <textarea id="modalDescription" name="description" rows="3"
                                            class="w-full px-4 py-3 border border-bote-stone-300 rounded-lg focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500 input-focus transition-colors resize-vertical"
                                            placeholder="Brief description of the collection point location and accessibility..."></textarea>
                                    </div>

                                    <div>
                                        <label for="modalNotes" class="block text-sm font-medium text-bote-stone-700 mb-2">
                                            Notes
                                        </label>
                                        <textarea id="modalNotes" name="notes" rows="3"
                                            class="w-full px-4 py-3 border border-bote-stone-300 rounded-lg focus:ring-2 focus:ring-bote-green-500 focus:border-bote-green-500 input-focus transition-colors resize-vertical"
                                            placeholder="Additional notes, special instructions, or maintenance reminders..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Modal Map Section -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-bote-stone-800 flex items-center">
                                <i data-lucide="map" class="w-5 h-5 mr-2 text-bote-green-600"></i>
                                Interactive Map
                            </h3>
                        </div>
                        
                        <div class="map-container">
                            <div id="modalMapLoading" class="map-loading hidden">
                                <div class="flex items-center space-x-2">
                                    <div class="loading-spinner"></div>
                                    <span>Loading map...</span>
                                </div>
                            </div>
                            <div id="modalMap" style="height: 500px; width: 100%; border-radius: 0.75rem;"></div>
                        </div>
                        
                        <!-- Current Location Display -->
                        <div class="coordinate-display rounded-lg p-4">
                            <h4 class="text-sm font-semibold text-bote-stone-700 mb-2">Selected Location</h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-bote-stone-500">Latitude:</span>
                                    <div id="modalCurrentLat" class="font-mono text-bote-stone-800">Not selected</div>
                                </div>
                                <div>
                                    <span class="text-bote-stone-500">Longitude:</span>
                                    <div id="modalCurrentLng" class="font-mono text-bote-stone-800">Not selected</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Map Legend -->
                        <div class="bg-bote-stone-50 border border-bote-stone-200 rounded-lg p-4">
                            <h4 class="text-sm font-semibold text-bote-stone-700 mb-2">Map Legend</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 bg-bote-green-500 rounded-full"></div>
                                    <span>Active Collection Points</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                    <span>Inactive Collection Points</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                    <span>Maintenance Collection Points</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 bg-bote-green-500 rounded-full"></div>
                                    <span>New Location (Click to place)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <div class="flex flex-col sm:flex-row gap-4 justify-end">
                    <button type="button" onclick="closeModal('collectionPointModal')"
                        class="px-6 py-2 bg-bote-stone-500 hover:bg-bote-stone-600 text-white font-semibold rounded-lg transition-colors duration-200 flex items-center justify-center">
                        <i data-lucide="x" class="w-4 h-4 mr-2"></i>
                        Cancel
                    </button>
                    <button type="button" onclick="submitModalForm()" id="modalSubmitBtn"
                        class="px-6 py-2 bg-bote-green-600 hover:bg-bote-green-700 disabled:bg-bote-stone-400 text-white font-semibold rounded-lg transition-colors duration-200 flex items-center justify-center">
                        <span class="btn-text">
                            <i data-lucide="save" class="w-4 h-4 mr-2"></i>
                            <span id="modalSubmitText">Save Collection Point</span>
                        </span>
                        <span class="btn-loading hidden">
                            <div class="loading-spinner mr-2"></div>
                            Saving...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Collection Point Modal -->
    <div id="viewCollectionPointModal" class="modal modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-bote-stone-800">Collection Point Details</h2>
                    <button onclick="closeModal('viewCollectionPointModal')" class="text-bote-stone-400 hover:text-bote-stone-600 transition-colors">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>
            
            <div class="modal-body">
                <div id="viewCollectionPointContent">
                    <!-- Content will be populated by JavaScript -->
                </div>
            </div>
            
            <div class="modal-footer">
                <div class="flex justify-end gap-4">
                    <button type="button" onclick="closeModal('viewCollectionPointModal')"
                        class="px-4 py-2 bg-bote-stone-500 hover:bg-bote-stone-600 text-white rounded-lg transition-colors">
                        Close
                    </button>
                    <button type="button" onclick="editFromView()" id="editFromViewBtn"
                        class="px-4 py-2 bg-bote-green-600 hover:bg-bote-green-700 text-white rounded-lg transition-colors flex items-center">
                        <i data-lucide="edit" class="w-4 h-4 mr-2"></i>
                        Edit
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="modal modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-red-600 flex items-center">
                        <i data-lucide="alert-triangle" class="w-6 h-6 mr-2"></i>
                        Confirm Delete
                    </h2>
                    <button onclick="closeModal('deleteConfirmModal')" class="text-bote-stone-400 hover:text-bote-stone-600 transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
            
            <div class="modal-body">
                <p class="text-bote-stone-600 mb-4">Are you sure you want to delete this collection point?</p>
                <div id="deleteItemName" class="bg-red-50 border border-red-200 rounded-lg p-3 mb-4">
                    <p class="font-semibold text-red-800"></p>
                </div>
                <p class="text-sm text-red-600">⚠️ This action cannot be undone.</p>
            </div>
            
            <div class="modal-footer">
                <div class="flex justify-end gap-4">
                    <button type="button" onclick="closeModal('deleteConfirmModal')"
                        class="px-4 py-2 bg-bote-stone-500 hover:bg-bote-stone-600 text-white rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button type="button" onclick="confirmDelete()" id="confirmDeleteBtn"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors flex items-center">
                        <span class="btn-text">
                            <i data-lucide="trash-2" class="w-4 h-4 mr-2"></i>
                            Delete
                        </span>
                        <span class="btn-loading hidden">
                            <div class="loading-spinner mr-2"></div>
                            Deleting...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaflet JavaScript -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" 
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" 
            crossorigin=""></script>

    
<script type="module" src="assets/js/admin_collection_points.js"></script>
</body>
</html>

