    <!-- Feather Icons -->
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <link rel="stylesheet" href="assets/css/assigned_pickups.css">
    <!-- Custom JavaScript -->
    <script type="module" src="assets/js/assigned_pickups.js"></script>
<div class="main-content">
        <div class="page-container">
            <div class="page-header">
                <h1 class="page-title">Recycling Pickup Management</h1>
                <p class="page-subtitle">Manage pickup requests and track collections</p>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <span class="alert-icon">
                        <i data-feather="check-circle"></i>
                    </span>
                    <div class="alert-content">
                        <div class="alert-title">Success</div>
                        <?= htmlspecialchars($success_message) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <span class="alert-icon">
                        <i data-feather="alert-triangle"></i>
                    </span>
                    <div class="alert-content">
                        <div class="alert-title">Error</div>
                        <?= htmlspecialchars($error_message) ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card available">
                    <div class="stat-value"><?= e($total_available) ?></div>
                    <div class="stat-label">
                        <i data-feather="inbox"></i>
                        Available Requests
                    </div>
                    <div class="stat-icon">
                        <i data-feather="package"></i>
                    </div>
                </div>
                
                <div class="stat-card assigned">
                    <div class="stat-value"><?= e($total_assigned) ?></div>
                    <div class="stat-label">
                        <i data-feather="clipboard"></i>
                        Assigned to You
                    </div>
                    <div class="stat-icon">
                        <i data-feather="user-check"></i>
                    </div>
                </div>
                
                <div class="stat-card today">
                    <div class="stat-value"><?= e($today_count) ?></div>
                    <div class="stat-label">
                        <i data-feather="calendar"></i>
                        Due Today
                    </div>
                    <div class="stat-icon">
                        <i data-feather="clock"></i>
                    </div>
                </div>
                
                <div class="stat-card urgent">
                    <div class="stat-value"><?= count(array_filter($assigned_pickups, function($p) { 
                        return strtotime($p['preferred_date']) < time();
                    })) ?></div>
                    <div class="stat-label">
                        <i data-feather="alert-circle"></i>
                        Overdue Pickups
                    </div>
                    <div class="stat-icon">
                        <i data-feather="alert-triangle"></i>
                    </div>
                </div>
            </div>

            <!-- Available Pickup Requests Section -->
            <div class="section-container available-section">
                <div class="section-header">
                    <div class="section-title">
                        <div class="section-icon">
                            <i data-feather="inbox"></i>
                        </div>
                        Available Pickup Requests
                    </div>
                    <div class="sort-controls">
                        <span class="sort-label">Sort by Date:</span>
                        <button class="sort-btn active" onclick="sortPickups('available', 'asc')" id="availableAsc">
                            <i data-feather="arrow-up"></i> Soonest First
                        </button>
                        <button class="sort-btn" onclick="sortPickups('available', 'desc')" id="availableDesc">
                            <i data-feather="arrow-down"></i> Latest First
                        </button>
                    </div>
                </div>
                
                <?php if (empty($available_pickups)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i data-feather="inbox"></i>
                        </div>
                        <h3 class="empty-title">No Available Pickup Requests</h3>
                        <p class="empty-message">There are currently no new pickup requests available for you to accept. Check back later for new requests.</p>
                    </div>
                <?php else: ?>
                    <div class="section-body">
                        <div class="pickup-grid" id="availablePickupsGrid">
                            <?php foreach ($available_pickups as $index => $pickup): ?>
                                <div class="pickup-card" data-date="<?= strtotime($pickup['preferred_date']) ?>">
                                    <div class="pickup-header">
                                        <div class="pickup-info">
                                            <h3 class="resident-name">
                                                <i data-feather="user"></i>
                                                <?= htmlspecialchars($pickup['first_name'] . ' ' . $pickup['last_name']) ?>
                                            </h3>
                                            <div class="pickup-date">
                                                <i data-feather="calendar"></i>
                                                <?= date('M j, Y g:i A', strtotime($pickup['preferred_date'])) ?>
                                            </div>
                                        </div>
                                        
                                        <div class="pickup-status status-pending">
                                            <span class="status-indicator"></span>
                                            Pending
                                        </div>
                                    </div>
                                    
                                    <?php 
                                    // Check if this is today or urgent
                                    $pickup_date = strtotime($pickup['preferred_date']);
                                    $is_today = date('Y-m-d') == date('Y-m-d', $pickup_date);
                                    $is_urgent = $pickup_date < time();
                                    
                                    if ($is_urgent): ?>
                                        <div class="pickup-priority" style="background: linear-gradient(135deg, #ef4444, #b91c1c);">
                                            <i data-feather="alert-triangle" style="width: 12px; height: 12px;"></i>
                                            Urgent
                                        </div>
                                    <?php elseif ($is_today): ?>
                                        <div class="pickup-priority">
                                            <i data-feather="clock" style="width: 12px; height: 12px;"></i>
                                            Today
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="pickup-body">
                                        <div class="pickup-detail">
                                            <div class="detail-icon">
                                                <i data-feather="package"></i>
                                            </div>
                                            <div class="detail-content">
                                                <div class="detail-label">Material Type</div>
                                                <div class="detail-value">
                                                    <span class="material-badge">
                                                        <i data-feather="recycle" style="width: 14px; height: 14px;"></i>
                                                        <?= htmlspecialchars($pickup['material_type']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="pickup-detail">
                                            <div class="detail-icon">
                                                <i data-feather="hash"></i>
                                            </div>
                                            <div class="detail-content">
                                                <div class="detail-label">Quantity/Weight</div>
                                                <div class="detail-value">
                                                    <?= e($pickup['weight_kg']) ?> <?= htmlspecialchars($pickup['unit']) ?>
                                                    <?php if ($pickup['quantity_pcs']): ?>
                                                        (<?= e($pickup['quantity_pcs']) ?> pcs)
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="pickup-detail">
                                            <div class="detail-icon">
                                                <i data-feather="map-pin"></i>
                                            </div>
                                            <div class="detail-content">
                                                <div class="detail-label">Address</div>
                                                <div class="detail-value">
                                                    <?= htmlspecialchars($pickup['address']) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="pickup-footer">
                                        <form method="POST" style="width: 100%;">
                                            <?php echo \App\Core\Csrf::field(); ?>
                                            <input type="hidden" name="action" value="accept_pickup">
                                            <input type="hidden" name="request_id" value="<?= htmlspecialchars((string) $pickup['request_id'], ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit" class="action-btn btn-primary" style="width: 100%;">
                                                <i data-feather="check-circle"></i>
                                                Accept Pickup Request
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Assigned Pickup Requests Section -->
            <div class="section-container">
                <div class="section-header">
                    <div class="section-title">
                        <div class="section-icon">
                            <i data-feather="clipboard"></i>
                        </div>
                        Assigned Pickup Requests
                    </div>
                    <div class="sort-controls">
                        <span class="sort-label">Sort by Date:</span>
                        <button class="sort-btn active" onclick="sortPickups('assigned', 'asc')" id="assignedAsc">
                            <i data-feather="arrow-up"></i> Soonest First
                        </button>
                        <button class="sort-btn" onclick="sortPickups('assigned', 'desc')" id="assignedDesc">
                            <i data-feather="arrow-down"></i> Latest First
                        </button>
                    </div>
                </div>
                
                <?php if (empty($assigned_pickups)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i data-feather="clipboard"></i>
                        </div>
                        <h3 class="empty-title">No Assigned Pickup Requests</h3>
                        <p class="empty-message">You don't have any assigned pickup requests yet. Accept pickup requests from the available section above.</p>
                        <button class="action-btn btn-secondary" onclick="scrollToAvailableSection()">
                            <i data-feather="arrow-up"></i> View Available Pickups
                        </button>
                    </div>
                <?php else: ?>
                    <div class="section-body">
                        <div class="pickup-grid" id="assignedPickupsGrid">
                            <?php foreach ($assigned_pickups as $index => $pickup): ?>
                                <?php
                                $pickup_date = strtotime($pickup['preferred_date']);
                                $is_today = date('Y-m-d') == date('Y-m-d', $pickup_date);
                                $is_urgent = $pickup_date < time();
                                ?>
                                <div class="pickup-card" data-date="<?= htmlspecialchars($pickup_date) ?>">
                                    <div class="pickup-header">
                                        <div class="pickup-info">
                                            <h3 class="resident-name">
                                                <i data-feather="user"></i>
                                                <?= htmlspecialchars($pickup['first_name'] . ' ' . $pickup['last_name']) ?>
                                            </h3>
                                            <div class="pickup-date">
                                                <i data-feather="calendar"></i>
                                                <?= date('M j, Y g:i A', $pickup_date) ?>
                                                <?php if ($is_today): ?>
                                                    <span style="color: #059669; font-weight: 600;"> (Today)</span>
                                                <?php elseif ($is_urgent): ?>
                                                    <span style="color: #dc2626; font-weight: 600;"> (Overdue)</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="pickup-status status-scheduled">
                                            <span class="status-indicator"></span>
                                            Scheduled
                                        </div>
                                    </div>
                                    
                                    <?php if ($is_urgent): ?>
                                        <div class="pickup-priority" style="background: linear-gradient(135deg, #ef4444, #b91c1c);">
                                            <i data-feather="alert-triangle" style="width: 12px; height: 12px;"></i>
                                            Overdue
                                        </div>
                                    <?php elseif ($is_today): ?>
                                        <div class="pickup-priority">
                                            <i data-feather="clock" style="width: 12px; height: 12px;"></i>
                                            Today
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="pickup-body">
                                        <div class="pickup-detail">
                                            <div class="detail-icon">
                                                <i data-feather="package"></i>
                                            </div>
                                            <div class="detail-content">
                                                <div class="detail-label">Material Type</div>
                                                <div class="detail-value">
                                                    <span class="material-badge">
                                                        <i data-feather="recycle" style="width: 14px; height: 14px;"></i>
                                                        <?= htmlspecialchars($pickup['material_type']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="pickup-detail">
                                            <div class="detail-icon">
                                                <i data-feather="hash"></i>
                                            </div>
                                            <div class="detail-content">
                                                <div class="detail-label">Expected Amount</div>
                                                <div class="detail-value">
                                                    <?php if ($pickup['unit'] === 'kg'): ?>
                                                        <?= e($pickup['weight_kg']) ?> kg (estimated)
                                                    <?php else: ?>
                                                        <?= e($pickup['quantity_pcs']) ?> pcs (estimated)
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="pickup-detail">
                                            <div class="detail-icon">
                                                <i data-feather="map-pin"></i>
                                            </div>
                                            <div class="detail-content">
                                                <div class="detail-label">Address</div>
                                                <div class="detail-value">
                                                    <?= htmlspecialchars($pickup['address']) ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($pickup['phone'])): ?>
                                            <div class="pickup-detail">
                                                <div class="detail-icon">
                                                    <i data-feather="phone"></i>
                                                </div>
                                                <div class="detail-content">
                                                    <div class="detail-label">Contact Number</div>
                                                    <div class="detail-value">
                                                        <?= htmlspecialchars($pickup['phone']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="pickup-footer">
                                        <button onclick="openCompleteModal(<?= (int) $pickup['request_id'] ?>, <?= json_encode($pickup['unit'], JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>, <?= json_encode($pickup['first_name'] . ' ' . $pickup['last_name'], JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>)" class="action-btn btn-secondary">
                                            <i data-feather="check-circle"></i>
                                            Complete Pickup
                                        </button>
                                        
                                        <div class="secondary-action">
                                            <?php if (!empty($pickup['phone'])): ?>
                                                <a href="tel:<?= htmlspecialchars($pickup['phone']) ?>" class="icon-btn phone" title="Call Resident">
                                                    <i data-feather="phone"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($pickup['latitude']) && !empty($pickup['longitude'])): ?>
                                                <a href="https://maps.google.com/?q=<?= e($pickup['latitude']) ?>,<?= e($pickup['longitude']) ?>" target="_blank" class="icon-btn map" title="View Location">
                                                    <i data-feather="map"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div> <!-- End page-container -->
    </div> <!-- End main-content -->

    <!-- Complete Pickup Modal -->
    <div id="completeModal" class="modal-overlay">
        <div class="modal-container">
            <button type="button" class="modal-close" onclick="closeCompleteModal()">
                <i data-feather="x"></i>
            </button>
            
            <div class="modal-header">
                <h3 class="modal-title">Complete Pickup</h3>
                <p class="modal-subtitle">Enter the actual collected materials information</p>
            </div>
            
            <form method="POST" id="completeForm">
                <?php echo \App\Core\Csrf::field(); ?>
                <input type="hidden" name="action" value="complete_pickup">
                <input type="hidden" name="request_id" id="modalRequestId">
                
                <div class="resident-info">
                    <span class="resident-info-title">Resident:</span>
                    <p class="resident-info-value" id="modalResidentName"></p>
                </div>
                
                <div class="form-group" id="weightInput" style="display: none;">
                    <label for="actual_weight" class="form-label">Actual Weight (kg):</label>
                    <input type="number" name="actual_weight" id="actual_weight" step="0.01" min="0.01" 
                           class="form-input" placeholder="Enter actual weight in kilograms">
                    <p style="color: #059669; margin-top: 8px; font-size: 14px;">
                        <i data-feather="info" style="width: 14px; height: 14px; vertical-align: -2px;"></i>
                        Points will be awarded based on the actual weight
                    </p>
                </div>
                
                <div class="form-group" id="quantityInput" style="display: none;">
                    <label for="actual_quantity" class="form-label">Actual Quantity (pieces):</label>
                    <input type="number" name="actual_quantity" id="actual_quantity" min="1"
                           class="form-input" placeholder="Enter actual quantity in pieces">
                    <p style="color: #059669; margin-top: 8px; font-size: 14px;">
                        <i data-feather="info" style="width: 14px; height: 14px; vertical-align: -2px;"></i>
                        Points will be awarded based on the actual quantity
                    </p>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="action-btn btn-outline" onclick="closeCompleteModal()">
                        Cancel
                    </button>
                    <button type="submit" class="action-btn btn-primary">
                        <i data-feather="check-circle"></i>
                        Complete Pickup
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- JS functionality moved to external file: assets/js/assigned_pickups.js -->
