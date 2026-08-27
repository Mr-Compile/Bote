<div class="p-8">
        <div class="max-w-2xl mx-auto">
            <!-- Success Header -->
            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-bote-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i data-lucide="check-circle" class="w-10 h-10 text-bote-green-600"></i>
                </div>
                <h1 class="text-3xl font-bold text-bote-stone-900 mb-2">Drop-off Submitted!</h1>
                <p class="text-bote-stone-600">Your recyclables have been logged for verification</p>
            </div>

            <!-- Drop-off Details Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-bote-stone-100 p-8 mb-8">
                <h2 class="text-xl font-bold text-bote-stone-900 mb-6 flex items-center">
                    <i data-lucide="package" class="w-5 h-5 mr-2 text-bote-green-600"></i>
                    Drop-off Details
                </h2>

                <div class="space-y-6">
                    <!-- Collection Point -->
                    <div class="flex items-center space-x-4 p-4 bg-bote-info-50 rounded-xl">
                        <div class="w-12 h-12 bg-bote-info-100 rounded-xl flex items-center justify-center">
                            <i data-lucide="map-pin" class="w-6 h-6 text-bote-info-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-bote-stone-900"><?php echo htmlspecialchars($dropoff['collection_point']); ?></h3>
                            <p class="text-sm text-bote-stone-600"><?php echo htmlspecialchars($dropoff['point_description']); ?></p>
                        </div>
                    </div>

                    <!-- Material Type -->
                    <div class="flex items-center space-x-4 p-4 bg-bote-green-50 rounded-xl">
                        <div class="w-12 h-12 bg-bote-green-100 rounded-xl flex items-center justify-center">
                            <i data-lucide="recycle" class="w-6 h-6 text-bote-green-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-bote-stone-900"><?php echo htmlspecialchars($dropoff['recyclable_type']); ?></h3>
                            <p class="text-sm text-bote-stone-600">
                                <?php if ($dropoff['unit'] === 'kg'): ?>
                                    Weight: <?php echo e($dropoff['weight_kg']); ?> kg
                                <?php else: ?>
                                    Quantity: <?php echo e($dropoff['quantity_pcs']); ?> pieces
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <!-- Points Earned -->
                    <div class="flex items-center space-x-4 p-4 bg-yellow-50 rounded-xl">
                        <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
                            <i data-lucide="star" class="w-6 h-6 text-yellow-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-bote-stone-900">Points Earned</h3>
                            <p class="text-sm text-bote-stone-600"><?php echo e($dropoff['points_earned']); ?> points (pending verification)</p>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="flex items-center space-x-4 p-4 bg-bote-stone-50 rounded-xl">
                        <div class="w-12 h-12 bg-bote-stone-100 rounded-xl flex items-center justify-center">
                            <i data-lucide="clock" class="w-6 h-6 text-bote-stone-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-bote-stone-900">Status</h3>
                            <p class="text-sm text-bote-stone-600">
                                <?php 
                                switch($dropoff['status']) {
                                    case 'Pending':
                                        echo 'Pending verification by collection point staff';
                                        break;
                                    case 'Verified':
                                        echo 'Verified and points credited';
                                        break;
                                    case 'Completed':
                                        echo 'Completed';
                                        break;
                                    default:
                                        echo ucfirst($dropoff['status']);
                                }
                                ?>
                            </p>
                        </div>
                    </div>

                    <!-- Submission Time -->
                    <div class="flex items-center space-x-4 p-4 bg-bote-green-50 rounded-xl">
                        <div class="w-12 h-12 bg-bote-green-100 rounded-xl flex items-center justify-center">
                            <i data-lucide="calendar" class="w-6 h-6 text-bote-green-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-bote-stone-900">Submitted</h3>
                            <p class="text-sm text-bote-stone-600"><?php echo date('M j, Y \a\t g:i A', strtotime($dropoff['created_at'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Next Steps -->
            <div class="bg-bote-info-50 border border-bote-info-200 rounded-2xl p-6 mb-8">
                <h3 class="font-semibold text-bote-info-900 mb-3 flex items-center">
                    <i data-lucide="info" class="w-5 h-5 mr-2"></i>
                    What happens next?
                </h3>
                <ul class="space-y-2 text-sm text-bote-info-800">
                    <li class="flex items-start">
                        <span class="w-2 h-2 bg-bote-info-400 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                        Collection point staff will verify your drop-off
                    </li>
                    <li class="flex items-start">
                        <span class="w-2 h-2 bg-bote-info-400 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                        Points will be credited to your account once verified
                    </li>
                    <li class="flex items-start">
                        <span class="w-2 h-2 bg-bote-info-400 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                        You'll receive SMS notification when points are credited
                    </li>
                </ul>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-6">
                <a href="resident_dashboard.php" class="px-8 py-4 bg-white hover:bg-bote-stone-50 text-bote-stone-800 rounded-2xl font-semibold transition-all duration-300 text-center hover:shadow-lg hover:scale-105 border border-bote-stone-200">
                    <i data-lucide="home" class="w-5 h-5 inline mr-2"></i>
                    Back to Dashboard
                </a>
                <a href="resident_direct_dropoff.php" class="px-8 py-4 bg-gradient-to-r from-bote-green-500 via-bote-green-600 to-bote-green-600 hover:from-bote-green-600 hover:via-bote-green-700 hover:to-bote-green-700 text-white rounded-2xl font-semibold transition-all duration-300 text-center hover:shadow-lg hover:scale-105">
                    <i data-lucide="plus" class="w-5 h-5 inline mr-2"></i>
                    Submit Another Drop-off
                </a>
            </div>
        </div>
    </div>
