<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_admin.php';
include 'admin_sidebar.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collection Points Management - Waste Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    
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
        
        .status-active { @apply bg-green-100 text-green-800; }
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
<body class="bg-gradient-to-br from-green-50 to-blue-50 min-h-screen">
    <!-- Main Content Area with Sidebar Offset -->
    <div class="ml-64 p-4 md:p-6 main-content">
        <div class="max-w-6xl mx-auto">
        <!-- Alert Messages -->
        <div id="alertContainer" class="mb-6"></div>

        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2 flex items-center">
                        <i data-lucide="map-pin" class="w-8 h-8 mr-3 text-green-600"></i>
                        Collection Points Management
                    </h1>
                    <p class="text-gray-600 text-lg">Create and manage designated locations for waste collection in each Purok.</p>
                </div>
                <div class="flex space-x-4">
                    <button onclick="openAddModal()" 
                            class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors duration-200 flex items-center">
                        <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                        Add Collection Point
                    </button>
                </div>
            </div>
        </div>

        <!-- Existing Collection Points List -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 mb-8">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="list" class="w-4 h-4 text-blue-600"></i>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-800">Existing Collection Points</h2>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button onclick="refreshCollectionPointsList()" 
                                class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors duration-200 flex items-center text-sm">
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
                        <span class="text-gray-500">Loading collection points...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Map View Section -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 md:p-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i data-lucide="map" class="w-6 h-6 text-green-600"></i>
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
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                    <i data-lucide="info" class="w-4 h-4"></i>
                    Map Legend
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="flex items-center space-x-2 text-sm">
                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
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
                    <h2 id="modalTitle" class="text-2xl font-bold text-gray-800">Add Collection Point</h2>
                    <button onclick="closeModal('collectionPointModal')" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>
            
            <div class="modal-body">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Form Section -->
                    <div class="space-y-6">
                        <form id="modalCollectionPointForm" class="space-y-6">
                            <input type="hidden" id="editPointId" name="point_id">
                            
                            <!-- Basic Information Section -->
                            <div class="border-b border-gray-200 pb-6">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                    <i data-lucide="info" class="w-5 h-5 mr-2 text-green-600"></i>
                                    Basic Information
                                </h3>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label for="modalCollectionPointName" class="block text-sm font-medium text-gray-700 mb-2">
                                            Collection Point Name *
                                        </label>
                                        <input type="text" id="modalCollectionPointName" name="name" required
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 input-focus transition-colors"
                                            placeholder="e.g., Main Street Collection Hub">
                                    </div>

                                    <div>
                                        <label for="modalPurok" class="block text-sm font-medium text-gray-700 mb-2">
                                            Purok *
                                        </label>
                                        <select id="modalPurok" name="purok_id" required
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 input-focus transition-colors">
                                            <option value="">Loading puroks...</option>
                                        </select>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label for="modalStatus" class="block text-sm font-medium text-gray-700 mb-2">
                                                Status *
                                            </label>
                                            <select id="modalStatus" name="status" required
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 input-focus transition-colors">
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                                <option value="maintenance">Maintenance</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label for="modalCapacity" class="block text-sm font-medium text-gray-700 mb-2">
                                                Capacity (kg)
                                            </label>
                                            <input type="number" id="modalCapacity" name="capacity" min="0" step="0.1"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 input-focus transition-colors"
                                                placeholder="e.g., 500">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Location Section -->
                            <div class="border-b border-gray-200 pb-6">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                    <i data-lucide="navigation" class="w-5 h-5 mr-2 text-blue-600"></i>
                                    Location Coordinates
                                </h3>
                                
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label for="modalLatitude" class="block text-sm font-medium text-gray-700 mb-2">
                                            Latitude *
                                        </label>
                                        <input type="number" id="modalLatitude" name="latitude" required step="any"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 input-focus transition-colors"
                                            placeholder="e.g., 14.5995" readonly>
                                    </div>

                                    <div>
                                        <label for="modalLongitude" class="block text-sm font-medium text-gray-700 mb-2">
                                            Longitude *
                                        </label>
                                        <input type="number" id="modalLongitude" name="longitude" required step="any"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 input-focus transition-colors"
                                            placeholder="e.g., 120.9842" readonly>
                                    </div>
                                </div>
                                
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm text-blue-800 mb-4">
                                    <i data-lucide="info" class="w-4 h-4 inline mr-1"></i>
                                    <strong>Instructions:</strong> Click on the map to set the exact location of your collection point.
                                </div>
                                
                                <!-- Map Controls -->
                                <div class="flex gap-2 mb-4">
                                    <button type="button" onclick="getModalCurrentLocation()" 
                                            class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors flex items-center gap-2">
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
                            <div class="border-b border-gray-200 pb-6">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                    <i data-lucide="phone" class="w-5 h-5 mr-2 text-green-600"></i>
                                    Contact & Operations
                                </h3>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label for="modalContactPerson" class="block text-sm font-medium text-gray-700 mb-2">
                                            Contact Person
                                        </label>
                                        <input type="text" id="modalContactPerson" name="contact_person"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 input-focus transition-colors"
                                            placeholder="e.g., Juan Dela Cruz">
                                    </div>

                                    <div>
                                        <label for="modalContactPhone" class="block text-sm font-medium text-gray-700 mb-2">
                                            Contact Phone
                                        </label>
                                        <input type="tel" id="modalContactPhone" name="contact_phone"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 input-focus transition-colors"
                                            placeholder="e.g., +63 912 345 6789">
                                    </div>

                                    <div>
                                        <label for="modalOperatingHours" class="block text-sm font-medium text-gray-700 mb-2">
                                            Operating Hours
                                        </label>
                                        <input type="text" id="modalOperatingHours" name="operating_hours" value="24/7"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 input-focus transition-colors"
                                            placeholder="e.g., 6:00 AM - 6:00 PM">
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Information Section -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                    <i data-lucide="file-text" class="w-5 h-5 mr-2 text-blue-600"></i>
                                    Additional Information
                                </h3>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label for="modalDescription" class="block text-sm font-medium text-gray-700 mb-2">
                                            Description
                                        </label>
                                        <textarea id="modalDescription" name="description" rows="3"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 input-focus transition-colors resize-vertical"
                                            placeholder="Brief description of the collection point location and accessibility..."></textarea>
                                    </div>

                                    <div>
                                        <label for="modalNotes" class="block text-sm font-medium text-gray-700 mb-2">
                                            Notes
                                        </label>
                                        <textarea id="modalNotes" name="notes" rows="3"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 input-focus transition-colors resize-vertical"
                                            placeholder="Additional notes, special instructions, or maintenance reminders..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Modal Map Section -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                                <i data-lucide="map" class="w-5 h-5 mr-2 text-green-600"></i>
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
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Selected Location</h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500">Latitude:</span>
                                    <div id="modalCurrentLat" class="font-mono text-gray-800">Not selected</div>
                                </div>
                                <div>
                                    <span class="text-gray-500">Longitude:</span>
                                    <div id="modalCurrentLng" class="font-mono text-gray-800">Not selected</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Map Legend -->
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Map Legend</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
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
                                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
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
                        class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg transition-colors duration-200 flex items-center justify-center">
                        <i data-lucide="x" class="w-4 h-4 mr-2"></i>
                        Cancel
                    </button>
                    <button type="button" onclick="submitModalForm()" id="modalSubmitBtn"
                        class="px-6 py-2 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white font-semibold rounded-lg transition-colors duration-200 flex items-center justify-center">
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
                    <h2 class="text-2xl font-bold text-gray-800">Collection Point Details</h2>
                    <button onclick="closeModal('viewCollectionPointModal')" class="text-gray-400 hover:text-gray-600 transition-colors">
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
                        class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors">
                        Close
                    </button>
                    <button type="button" onclick="editFromView()" id="editFromViewBtn"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors flex items-center">
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
                    <button onclick="closeModal('deleteConfirmModal')" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
            
            <div class="modal-body">
                <p class="text-gray-600 mb-4">Are you sure you want to delete this collection point?</p>
                <div id="deleteItemName" class="bg-red-50 border border-red-200 rounded-lg p-3 mb-4">
                    <p class="font-semibold text-red-800"></p>
                </div>
                <p class="text-sm text-red-600">⚠️ This action cannot be undone.</p>
            </div>
            
            <div class="modal-footer">
                <div class="flex justify-end gap-4">
                    <button type="button" onclick="closeModal('deleteConfirmModal')"
                        class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors">
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

    <script>
        // Global variables
        let map;
        let modalMap;
        let currentMarker;
        let modalCurrentMarker;
        let collectionPoints = [];
        let editingPointId = null;
        let deletePointId = null;
        
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Initialize the application when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeMap();
            loadExistingCollectionPoints();
            loadCollectionPointsTable();
            setupModalEventListeners();
        });

        // Modal Management Functions
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
                
                // Initialize modal map if this is the collection point modal
                if (modalId === 'collectionPointModal') {
                    setTimeout(() => {
                        initializeModalMap();
                        loadModalPuroks();
                    }, 300);
                }
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('show');
                document.body.style.overflow = '';
                
                // Clean up modal data
                if (modalId === 'collectionPointModal') {
                    resetModalForm();
                    editingPointId = null;
                }
                if (modalId === 'deleteConfirmModal') {
                    deletePointId = null;
                }
            }
        }

        function setupModalEventListeners() {
            // Close modal when clicking outside
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal')) {
                    const modalId = e.target.id;
                    closeModal(modalId);
                }
            });

            // Close modal with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const openModal = document.querySelector('.modal.show');
                    if (openModal) {
                        closeModal(openModal.id);
                    }
                }
            });
        }

        // Collection Point Modal Functions
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Collection Point';
            document.getElementById('modalSubmitText').textContent = 'Save Collection Point';
            resetModalForm();
            editingPointId = null;
            openModal('collectionPointModal');
        }

        async function editCollectionPoint(pointId) {
            console.log('=== EDIT COLLECTION POINT ===');
            console.log('Point ID:', pointId);
            
            try {
                const url = `collection_points_handler.php?action=get_collection_point&id=${pointId}`;
                console.log('Fetching from:', url);
                
                const response = await fetch(url);
                console.log('Response status:', response.status);
                
                // Get raw response text first
                const responseText = await response.text();
                console.log('Raw response:', responseText);
                
                // Try to parse JSON
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    console.error('Response was not valid JSON:', responseText);
                    throw new Error('Server returned invalid JSON. Response: ' + responseText.substring(0, 200));
                }
                
                console.log('Parsed data:', data);
                
                if (data.success) {
                    const point = data.data;
                    console.log('Collection point data:', point);
                    editingPointId = pointId;
                    
                    // Update modal title
                    document.getElementById('modalTitle').textContent = 'Edit Collection Point';
                    document.getElementById('modalSubmitText').textContent = 'Update Collection Point';
                    
                    // Populate form fields with detailed logging (except purok - we'll set that after dropdown loads)
                    console.log('Setting editPointId to:', point.point_id);
                    document.getElementById('editPointId').value = point.point_id;
                    
                    console.log('Setting modalCollectionPointName to:', point.name);
                    document.getElementById('modalCollectionPointName').value = point.name;
                    
                    console.log('Setting modalStatus to:', point.status);
                    document.getElementById('modalStatus').value = point.status;
                    
                    console.log('Setting modalCapacity to:', point.capacity || '');
                    document.getElementById('modalCapacity').value = point.capacity || '';
                    
                    console.log('Setting modalContactPerson to:', point.contact_person || '');
                    document.getElementById('modalContactPerson').value = point.contact_person || '';
                    
                    console.log('Setting modalContactPhone to:', point.contact_phone || '');
                    document.getElementById('modalContactPhone').value = point.contact_phone || '';
                    
                    console.log('Setting modalOperatingHours to:', point.operating_hours || '24/7');
                    document.getElementById('modalOperatingHours').value = point.operating_hours || '24/7';
                    
                    console.log('Setting modalDescription to:', point.description || '');
                    document.getElementById('modalDescription').value = point.description || '';
                    
                    console.log('Setting modalNotes to:', point.notes || '');
                    document.getElementById('modalNotes').value = point.notes || '';
                    
                    console.log('Basic form fields populated');
                    
                    console.log('Opening modal first');
                    openModal('collectionPointModal');
                    
                    // Wait for modal to open and purok dropdown to load, then set purok and location
                    setTimeout(() => {
                        console.log('Setting modalPurok to:', point.purok_id);
                        document.getElementById('modalPurok').value = point.purok_id;
                        
                        console.log('Setting location after map initialization');
                        console.log('Latitude:', parseFloat(point.latitude), 'Longitude:', parseFloat(point.longitude));
                        setModalLocation(parseFloat(point.latitude), parseFloat(point.longitude));
                    }, 600);
                } else {
                    console.error('Server returned error:', data.message);
                    showAlert('Failed to load collection point data: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Error loading collection point:', error);
                showAlert('Error loading collection point data: ' + error.message, 'error');
            }
        }

        async function viewCollectionPoint(pointId) {
            console.log('=== VIEW COLLECTION POINT ===');
            console.log('Point ID:', pointId);
            
            try {
                const url = `collection_points_handler.php?action=get_collection_point&id=${pointId}`;
                console.log('Fetching from:', url);
                
                const response = await fetch(url);
                console.log('Response status:', response.status);
                
                // Get raw response text first
                const responseText = await response.text();
                console.log('Raw response:', responseText.substring(0, 500));
                
                // Try to parse JSON
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    console.error('Response was not valid JSON:', responseText);
                    throw new Error('Server returned invalid JSON. Response: ' + responseText.substring(0, 200));
                }
                
                console.log('Parsed data:', data);
                
                if (data.success) {
                    const point = data.data;
                    console.log('Collection point data:', point);
                    
                    // Store the point ID for edit functionality
                    document.getElementById('editFromViewBtn').setAttribute('data-point-id', pointId);
                    
                    const content = `
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
                                        <i data-lucide="info" class="w-5 h-5 mr-2 text-blue-600"></i>
                                        Basic Information
                                    </h3>
                                    <div class="space-y-2 text-sm">
                                        <div><span class="font-medium text-gray-600">Name:</span> ${point.name}</div>
                                        <div><span class="font-medium text-gray-600">Purok:</span> ${point.purok_name || 'Unknown'}</div>
                                        <div><span class="font-medium text-gray-600">Status:</span> 
                                            <span class="status-badge status-${point.status}">${point.status.charAt(0).toUpperCase() + point.status.slice(1)}</span>
                                        </div>
                                        <div><span class="font-medium text-gray-600">Capacity:</span> ${point.capacity ? point.capacity + ' kg' : 'Not set'}</div>
                                    </div>
                                </div>
                                
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
                                        <i data-lucide="map-pin" class="w-5 h-5 mr-2 text-green-600"></i>
                                        Location
                                    </h3>
                                    <div class="space-y-2 text-sm">
                                        <div><span class="font-medium text-gray-600">Latitude:</span> <span class="font-mono">${point.latitude}</span></div>
                                        <div><span class="font-medium text-gray-600">Longitude:</span> <span class="font-mono">${point.longitude}</span></div>
                                    </div>
                                </div>
                                
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
                                        <i data-lucide="phone" class="w-5 h-5 mr-2 text-blue-600"></i>
                                        Contact & Operations
                                    </h3>
                                    <div class="space-y-2 text-sm">
                                        <div><span class="font-medium text-gray-600">Contact Person:</span> ${point.contact_person || 'Not set'}</div>
                                        <div><span class="font-medium text-gray-600">Phone:</span> ${point.contact_phone || 'Not set'}</div>
                                        <div><span class="font-medium text-gray-600">Operating Hours:</span> ${point.operating_hours || '24/7'}</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="space-y-4">
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
                                        <i data-lucide="file-text" class="w-5 h-5 mr-2 text-green-600"></i>
                                        Description
                                    </h3>
                                    <p class="text-sm text-gray-600">${point.description || 'No description provided.'}</p>
                                </div>
                                
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
                                        <i data-lucide="clipboard" class="w-5 h-5 mr-2 text-blue-600"></i>
                                        Notes
                                    </h3>
                                    <p class="text-sm text-gray-600">${point.notes || 'No additional notes.'}</p>
                                </div>
                                
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
                                        <i data-lucide="clock" class="w-5 h-5 mr-2 text-gray-600"></i>
                                        Timestamps
                                    </h3>
                                    <div class="space-y-2 text-sm text-gray-600">
                                        <div><span class="font-medium">Created:</span> ${point.created_at ? new Date(point.created_at).toLocaleString() : 'Unknown'}</div>
                                        <div><span class="font-medium">Updated:</span> ${point.updated_at ? new Date(point.updated_at).toLocaleString() : 'Unknown'}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('viewCollectionPointContent').innerHTML = content;
                    lucide.createIcons(); // Reinitialize icons
                    openModal('viewCollectionPointModal');
                } else {
                    console.error('Server returned error:', data.message);
                    showAlert('Failed to load collection point details: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Error loading collection point details:', error);
                showAlert('Error loading collection point details: ' + error.message, 'error');
            }
        }

        function editFromView() {
            const pointId = document.getElementById('editFromViewBtn').getAttribute('data-point-id');
            closeModal('viewCollectionPointModal');
            setTimeout(() => {
                editCollectionPoint(pointId);
            }, 300);
        }

        function deleteCollectionPoint(pointId, name) {
            deletePointId = pointId;
            document.getElementById('deleteItemName').querySelector('p').textContent = name;
            openModal('deleteConfirmModal');
        }

        async function confirmDelete() {
            if (!deletePointId) {
                console.error('No point ID selected for deletion');
                return;
            }
            
            console.log('Deleting collection point:', deletePointId);
            
            const deleteBtn = document.getElementById('confirmDeleteBtn');
            const btnText = deleteBtn.querySelector('.btn-text');
            const btnLoading = deleteBtn.querySelector('.btn-loading');
            
            // Set loading state
            btnText.classList.add('hidden');
            btnLoading.classList.remove('hidden');
            deleteBtn.disabled = true;
            
            try {
                const deleteData = {
                    action: 'delete',
                    point_id: deletePointId
                };
                
                console.log('Sending delete request:', deleteData);
                
                const response = await fetch('collection_points_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(deleteData)
                });
                
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                // Get raw response text first
                const responseText = await response.text();
                console.log('Raw response:', responseText);
                
                // Try to parse JSON
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    console.error('Response was not valid JSON:', responseText);
                    throw new Error('Server returned invalid JSON. Check console for details.');
                }
                
                console.log('Delete response:', result);
                
                if (result.success) {
                    showAlert(result.message, 'success');
                    closeModal('deleteConfirmModal');
                    loadCollectionPointsTable();
                    loadExistingCollectionPoints();
                } else {
                    showAlert(result.message || 'Failed to delete collection point', 'error');
                }
            } catch (error) {
                console.error('Error deleting collection point:', error);
                showAlert('An error occurred while deleting: ' + error.message, 'error');
            } finally {
                // Reset loading state
                btnText.classList.remove('hidden');
                btnLoading.classList.add('hidden');
                deleteBtn.disabled = false;
            }
        }

        // Modal Form Functions
        async function submitModalForm() {
            console.log('=== SUBMIT FORM CALLED ===');
            
            if (!validateModalForm()) {
                console.log('Form validation failed - stopping submission');
                return;
            }
            
            console.log('Form validation passed');
            setModalLoadingState(true);
            
            try {
                const formData = new FormData(document.getElementById('modalCollectionPointForm'));
                const data = Object.fromEntries(formData);
                
                console.log('Form data collected:', data);
                
                // Add action type
                data.action = editingPointId ? 'update' : 'create';
                if (editingPointId) {
                    data.point_id = editingPointId;
                }
                
                console.log('Final data to send:', data);
                console.log('Action:', data.action);
                
                const response = await fetch('collection_points_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                // Get raw response text first
                const responseText = await response.text();
                console.log('Raw response:', responseText);
                
                // Try to parse JSON
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    console.error('Response was not valid JSON:', responseText);
                    throw new Error('Server returned invalid JSON. Response: ' + responseText.substring(0, 200));
                }
                
                console.log('Parsed server response:', result);
                
                if (result.success) {
                    showAlert(result.message, 'success');
                    closeModal('collectionPointModal');
                    loadExistingCollectionPoints();
                    loadCollectionPointsTable();
                } else {
                    showAlert(result.message || 'Failed to save collection point', 'error');
                }
            } catch (error) {
                console.error('Error submitting modal form:', error);
                showAlert('An error occurred while saving: ' + error.message, 'error');
            } finally {
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
            let errors = [];

            console.log('Validating form fields...');
            
            // Check each required field
            Object.keys(requiredFields).forEach(fieldId => {
                const input = document.getElementById(fieldId);
                const value = input ? input.value : null;
                
                console.log(`Field ${fieldId} (${requiredFields[fieldId]}):`, value);
                
                if (!input || !value || value.trim() === '') {
                    if (input) {
                        input.classList.add('border-red-500');
                    }
                    isValid = false;
                    errors.push(requiredFields[fieldId]);
                } else {
                    if (input) {
                        input.classList.remove('border-red-500');
                    }
                }
            });

            // Validate coordinates are within Philippines
            const lat = parseFloat(document.getElementById('modalLatitude').value);
            const lng = parseFloat(document.getElementById('modalLongitude').value);
            
            console.log('Coordinates:', lat, lng);
            
            if (lat && lng) {
                if (lat < 4 || lat > 21 || lng < 116 || lng > 127) {
                    showAlert('Coordinates must be within the Philippines (4°N-21°N, 116°E-127°E)', 'error');
                    isValid = false;
                }
            }

            if (!isValid && errors.length > 0) {
                console.log('Missing fields:', errors);
                showAlert('Please fill in all required fields: ' + errors.join(', '), 'error');
            }

            console.log('Validation result:', isValid);
            return isValid;
        }

        function setModalLoadingState(loading) {
            const submitBtn = document.getElementById('modalSubmitBtn');
            const btnText = submitBtn.querySelector('.btn-text');
            const btnLoading = submitBtn.querySelector('.btn-loading');
            
            if (loading) {
                btnText.classList.add('hidden');
                btnLoading.classList.remove('hidden');
                submitBtn.disabled = true;
            } else {
                btnText.classList.remove('hidden');
                btnLoading.classList.add('hidden');
                submitBtn.disabled = false;
            }
        }

        function resetModalForm() {
            document.getElementById('modalCollectionPointForm').reset();
            document.getElementById('modalOperatingHours').value = '24/7';
            clearModalLocation();
            
            // Remove validation error styling
            const inputs = document.querySelectorAll('#collectionPointModal input, #collectionPointModal select, #collectionPointModal textarea');
            inputs.forEach(input => {
                input.classList.remove('border-red-500');
            });
        }

        // Modal Map Functions
        function initializeModalMap() {
            if (modalMap) {
                modalMap.remove();
            }
            
            try {
                const defaultLat = 6.4216;
                const defaultLng = 124.7810;
                
                modalMap = L.map('modalMap').setView([defaultLat, defaultLng], 15);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    maxZoom: 19
                }).addTo(modalMap);
                
                modalMap.on('click', function(e) {
                    setModalLocation(e.latlng.lat, e.latlng.lng);
                });
                
                // Load existing collection points on modal map
                collectionPoints.forEach(point => {
                    const color = getStatusColor(point.status);
                    
                    // Create a custom divIcon marker
                    const markerIcon = L.divIcon({
                        className: 'custom-marker',
                        html: `
                            <div style="position: relative;">
                                <div style="
                                    width: 24px;
                                    height: 24px;
                                    background-color: ${color};
                                    border: 2px solid white;
                                    border-radius: 50%;
                                    box-shadow: 0 2px 6px rgba(0,0,0,0.3);
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    font-size: 12px;
                                    color: white;
                                    font-weight: bold;
                                ">📍</div>
                            </div>
                        `,
                        iconSize: [24, 24],
                        iconAnchor: [12, 12]
                    });
                    
                    const marker = L.marker([point.latitude, point.longitude], {
                        icon: markerIcon
                    }).addTo(modalMap);
                    
                    // Enhanced popup
                    marker.bindPopup(`
                        <div class="text-center p-2">
                            <strong class="text-base">${point.name}</strong><br>
                            <span class="text-sm text-blue-600">📍 ${point.purok_name || 'Unknown Purok'}</span>
                        </div>
                    `);
                    
                    // Add permanent tooltip
                    marker.bindTooltip(`${point.name}<br><small>${point.purok_name || 'Unknown'}</small>`, {
                        permanent: true,
                        direction: 'top',
                        offset: [0, -15],
                        className: 'custom-tooltip',
                        opacity: 0.85
                    });
                });
                
                console.log('Modal map initialized successfully');
            } catch (error) {
                console.error('Error initializing modal map:', error);
            }
        }

        async function loadModalPuroks() {
            try {
                const response = await fetch('collection_points_handler.php?action=get_puroks');
                const data = await response.json();
                
                if (data.success) {
                    const purokSelect = document.getElementById('modalPurok');
                    purokSelect.innerHTML = '<option value="">Select Purok</option>';
                    
                    data.data.forEach(purok => {
                        const option = document.createElement('option');
                        option.value = purok.purok_id;
                        option.textContent = purok.name;
                        purokSelect.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading modal puroks:', error);
            }
        }

        function setModalLocation(lat, lng) {
            if (modalCurrentMarker) {
                modalMap.removeLayer(modalCurrentMarker);
            }
            
            modalCurrentMarker = L.marker([lat, lng], {
                draggable: true
            }).addTo(modalMap);
            
            modalCurrentMarker.on('dragend', function(e) {
                const position = e.target.getLatLng();
                updateModalLocationInputs(position.lat, position.lng);
            });
            
            updateModalLocationInputs(lat, lng);
            modalMap.setView([lat, lng], modalMap.getZoom());
        }

        function updateModalLocationInputs(lat, lng) {
            document.getElementById('modalLatitude').value = lat.toFixed(7);
            document.getElementById('modalLongitude').value = lng.toFixed(7);
            document.getElementById('modalCurrentLat').textContent = lat.toFixed(7);
            document.getElementById('modalCurrentLng').textContent = lng.toFixed(7);
        }

        function getModalCurrentLocation() {
            if (navigator.geolocation) {
                showAlert('Getting your location...', 'info');
                
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        setModalLocation(lat, lng);
                        showAlert('Location set successfully!', 'success');
                    },
                    function(error) {
                        console.error('Geolocation error:', error);
                        showAlert('Unable to get your location. Please click on the map to set location.', 'error');
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 60000
                    }
                );
            } else {
                showAlert('Geolocation is not supported by this browser.', 'error');
            }
        }

        function clearModalLocation() {
            if (modalCurrentMarker) {
                modalMap.removeLayer(modalCurrentMarker);
                modalCurrentMarker = null;
            }
            
            document.getElementById('modalLatitude').value = '';
            document.getElementById('modalLongitude').value = '';
            document.getElementById('modalCurrentLat').textContent = 'Not selected';
            document.getElementById('modalCurrentLng').textContent = 'Not selected';
        }

        // Initialize the map
        function initializeMap() {
            try {
                // Default center (Philippines - Yangco area)
                const defaultLat = 6.4216;
                const defaultLng = 124.7810;
                
                map = L.map('map').setView([defaultLat, defaultLng], 15);
                
                // Add tile layer
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    maxZoom: 19
                }).addTo(map);
                
                // Add click event listener
                map.on('click', function(e) {
                    setLocation(e.latlng.lat, e.latlng.lng);
                });
                
                // Hide loading indicator
                document.getElementById('mapLoading').style.display = 'none';
                
                console.log('Map initialized successfully');
            } catch (error) {
                console.error('Error initializing map:', error);
                showAlert('Error loading map. Please refresh the page.', 'error');
            }
        }

        // Load puroks from database
        async function loadPuroks() {
            try {
                const response = await fetch('collection_points_handler.php?action=get_puroks');
                const data = await response.json();
                
                if (data.success) {
                    const purokSelect = document.getElementById('purok');
                    purokSelect.innerHTML = '<option value="">Select Purok</option>';
                    
                    data.data.forEach(purok => {
                        const option = document.createElement('option');
                        option.value = purok.purok_id;
                        option.textContent = purok.name;
                        purokSelect.appendChild(option);
                    });
                } else {
                    console.error('Failed to load puroks:', data.message);
                    showAlert('Failed to load puroks. Please refresh the page.', 'error');
                }
            } catch (error) {
                console.error('Error loading puroks:', error);
                showAlert('Error loading puroks. Please check your connection.', 'error');
            }
        }

        // Load and display collection points in table format
        async function loadCollectionPointsTable() {
            try {
                const response = await fetch('collection_points_handler.php?action=get_collection_points');
                const data = await response.json();
                
                if (data.success) {
                    displayCollectionPointsTable(data.data);
                } else {
                    console.error('Failed to load collection points for table:', data.message);
                    document.getElementById('collectionPointsTable').innerHTML = `
                        <div class="text-center py-8 text-red-600">
                            <i data-lucide="alert-circle" class="w-8 h-8 mx-auto mb-2"></i>
                            <p>Failed to load collection points</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading collection points for table:', error);
                document.getElementById('collectionPointsTable').innerHTML = `
                    <div class="text-center py-8 text-red-600">
                        <i data-lucide="wifi-off" class="w-8 h-8 mx-auto mb-2"></i>
                        <p>Error loading collection points</p>
                    </div>
                `;
            }
        }

        // Display collection points in table format
        function displayCollectionPointsTable(points) {
            const tableContainer = document.getElementById('collectionPointsTable');
            
            if (points.length === 0) {
                tableContainer.innerHTML = `
                    <div class="text-center py-8 text-gray-500">
                        <i data-lucide="map-pin" class="w-12 h-12 mx-auto mb-4 text-gray-300"></i>
                        <h3 class="text-lg font-medium text-gray-600 mb-2">No Collection Points Yet</h3>
                        <p class="text-gray-500 mb-4">Create your first collection point using the form below.</p>
                        <button onclick="document.getElementById('collectionPointName').focus()" 
                                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                            Create First Collection Point
                        </button>
                    </div>
                `;
                return;
            }

            let tableHTML = `
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name & Location</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purok</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Capacity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
            `;

            points.forEach(point => {
                const statusBadgeClass = `status-${point.status}`;
                const statusText = point.status.charAt(0).toUpperCase() + point.status.slice(1);
                
                tableHTML += `
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center ${getStatusColor(point.status) === '#10b981' ? 'bg-green-100' : getStatusColor(point.status) === '#ef4444' ? 'bg-red-100' : 'bg-yellow-100'}">
                                    <i data-lucide="map-pin" class="w-5 h-5" style="color: ${getStatusColor(point.status)}"></i>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">${point.name}</div>
                                    <div class="text-sm text-gray-500">${point.latitude}, ${point.longitude}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">${point.purok_name || 'Unknown'}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="status-badge ${statusBadgeClass}">${statusText}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${point.capacity ? point.capacity + ' kg' : 'Not set'}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">${point.contact_person || 'No contact'}</div>
                            <div class="text-sm text-gray-500">${point.contact_phone || ''}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick="viewCollectionPoint(${point.point_id})" 
                                    class="text-blue-600 hover:text-blue-900 mr-3" title="View Details">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                            <button onclick="viewOnMap(${point.latitude}, ${point.longitude})" 
                                    class="text-indigo-600 hover:text-indigo-900 mr-3" title="View on Map">
                                <i data-lucide="map-pin" class="w-4 h-4"></i>
                            </button>
                            <button onclick="editCollectionPoint(${point.point_id})" 
                                    class="text-green-600 hover:text-green-900 mr-3" title="Edit">
                                <i data-lucide="edit" class="w-4 h-4"></i>
                            </button>
                            <button onclick="deleteCollectionPoint(${point.point_id}, '${point.name}')" 
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
            
            // Reinitialize Lucide icons for the new content
            lucide.createIcons();
        }

        // Refresh collection points list
        function refreshCollectionPointsList() {
            loadCollectionPointsTable();
            loadExistingCollectionPoints(); // Also refresh map markers
        }

        // View collection point on map
        function viewOnMap(lat, lng) {
            map.setView([lat, lng], 16);
            // Scroll to map
            document.getElementById('map').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        // Edit collection point (placeholder)
        function editCollectionPointOld(pointId) {
            showAlert('Edit functionality will be implemented in a future update.', 'info');
        }

        // Delete collection point (placeholder)
        function deleteCollectionPointOld(pointId, name) {
            if (confirm(`Are you sure you want to delete "${name}"?`)) {
                showAlert('Delete functionality will be implemented in a future update.', 'info');
            }
        }

        // Load existing collection points to display on map
        async function loadExistingCollectionPoints() {
            try {
                const response = await fetch('collection_points_handler.php?action=get_collection_points');
                const data = await response.json();
                
                if (data.success) {
                    collectionPoints = data.data;
                    displayCollectionPointsOnMap();
                } else {
                    console.error('Failed to load collection points:', data.message);
                }
            } catch (error) {
                console.error('Error loading collection points:', error);
            }
        }

        // Display existing collection points on the map
        function displayCollectionPointsOnMap() {
            collectionPoints.forEach(point => {
                const color = getStatusColor(point.status);
                
                // Create a custom divIcon marker with better visibility
                const markerIcon = L.divIcon({
                    className: 'custom-marker',
                    html: `
                        <div style="position: relative;">
                            <div style="
                                width: 30px;
                                height: 30px;
                                background-color: ${color};
                                border: 3px solid white;
                                border-radius: 50%;
                                box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                font-size: 16px;
                                color: white;
                                font-weight: bold;
                            ">📍</div>
                        </div>
                    `,
                    iconSize: [30, 30],
                    iconAnchor: [15, 15]
                });
                
                const marker = L.marker([point.latitude, point.longitude], {
                    icon: markerIcon
                }).addTo(map);
                
                // Enhanced popup content
                const popupContent = `
                    <div class="p-3" style="min-width: 200px;">
                        <h3 class="font-bold text-lg text-gray-800 mb-2">${point.name}</h3>
                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-blue-600">
                                📍 ${point.purok_name || 'Unknown Purok'}
                            </p>
                            <p class="text-sm">
                                Status: <span class="status-badge status-${point.status}">${point.status}</span>
                            </p>
                            ${point.capacity ? `<p class="text-sm text-gray-600">💪 Capacity: ${point.capacity} kg</p>` : ''}
                            ${point.contact_person ? `<p class="text-sm text-gray-600">👤 Contact: ${point.contact_person}</p>` : ''}
                            ${point.operating_hours ? `<p class="text-sm text-gray-600">🕐 Hours: ${point.operating_hours}</p>` : ''}
                        </div>
                    </div>
                `;
                
                marker.bindPopup(popupContent);
                
                // Add permanent tooltip showing purok name
                marker.bindTooltip(`<strong>${point.name}</strong><br>${point.purok_name || 'Unknown Purok'}`, {
                    permanent: true,
                    direction: 'top',
                    offset: [0, -20],
                    className: 'custom-tooltip',
                    opacity: 0.9
                });
            });
        }

        // Get color based on status
        function getStatusColor(status) {
            switch (status) {
                case 'active': return '#10b981'; // green
                case 'inactive': return '#ef4444'; // red
                case 'maintenance': return '#f59e0b'; // yellow
                default: return '#6b7280'; // gray
            }
        }

        // Set location on map and update form
        function setLocation(lat, lng) {
            // Remove existing marker
            if (currentMarker) {
                map.removeLayer(currentMarker);
            }
            
            // Add new marker
            currentMarker = L.marker([lat, lng], {
                draggable: true
            }).addTo(map);
            
            // Add drag event to marker
            currentMarker.on('dragend', function(e) {
                const position = e.target.getLatLng();
                updateLocationInputs(position.lat, position.lng);
            });
            
            // Update form inputs
            updateLocationInputs(lat, lng);
            
            // Center map on new location
            map.setView([lat, lng], map.getZoom());
        }

        // Update location inputs and display
        function updateLocationInputs(lat, lng) {
            document.getElementById('latitude').value = lat.toFixed(7);
            document.getElementById('longitude').value = lng.toFixed(7);
            document.getElementById('currentLat').textContent = lat.toFixed(7);
            document.getElementById('currentLng').textContent = lng.toFixed(7);
        }

        // Show alert message
        function showAlert(message, type = 'info') {
            const alertContainer = document.getElementById('alertContainer');
            
            const alertColors = {
                success: 'bg-green-100 border-green-400 text-green-700',
                error: 'bg-red-100 border-red-400 text-red-700',
                warning: 'bg-yellow-100 border-yellow-400 text-yellow-700',
                info: 'bg-blue-100 border-blue-400 text-blue-700'
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
            
            // Initialize icons for the new alert
            lucide.createIcons();
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 5000);
            
            // Scroll to top to show alert
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>
<script>(function(){function c(){var b=a.contentDocument||a.contentWindow.document;if(b){var d=b.createElement('script');d.innerHTML="window.__CF$cv$params={r:'98abb6e861e204cf',t:'MTc1OTgyMjQ1MC4wMDAwMDA='};var a=document.createElement('script');a.nonce='';a.src='/cdn-cgi/challenge-platform/scripts/jsd/main.js';document.getElementsByTagName('head')[0].appendChild(a);";b.getElementsByTagName('head')[0].appendChild(d)}}if(document.body){var a=document.createElement('iframe');a.height=1;a.width=1;a.style.position='absolute';a.style.top=0;a.style.left=0;a.style.border='none';a.style.visibility='hidden';document.body.appendChild(a);if('loading'!==document.readyState)c();else if(window.addEventListener)document.addEventListener('DOMContentLoaded',c);else{var e=document.onreadystatechange||function(){};document.onreadystatechange=function(b){e(b);'loading'!==document.readyState&&(document.onreadystatechange=e,c())}}}})();</script></body>
</html>
