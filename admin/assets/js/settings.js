
        // Initialize Lucide icons
        lucide.createIcons();

        function showTab(tabName) {
            // Hide all tab contents
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => {
                content.classList.remove('active');
            });

            // Remove active class from all tabs
            const tabs = document.querySelectorAll('[id^="tab-"]');
            tabs.forEach(tab => {
                tab.classList.remove('tab-active');
                tab.classList.add('text-gray-600', 'hover:text-gray-800', 'hover:bg-gray-200');
            });

            // Show selected tab content
            document.getElementById('content-' + tabName).classList.add('active');

            // Add active class to selected tab
            const activeTab = document.getElementById('tab-' + tabName);
            activeTab.classList.add('tab-active');
            activeTab.classList.remove('text-gray-600', 'hover:text-gray-800', 'hover:bg-gray-200');
        }

        // Enhanced image preview function
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            const file = input.files[0];
            
            if (file) {
                // Validate file type
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG, PNG, GIF)');
                    input.value = '';
                    return;
                }
                
                // Validate file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    input.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const isLogo = previewId.includes('logo');
                    const imgClass = isLogo ? 'mx-auto h-24 w-24 object-cover rounded-lg shadow-md' : 'mx-auto h-20 w-40 object-cover rounded-lg shadow-md';
                    
                    preview.innerHTML = `
                        <img src="${e.target.result}" alt="Preview" class="${imgClass}">
                        <p class="text-sm text-gray-600 mt-2">New ${isLogo ? 'Logo' : 'Banner'} Preview</p>
                    `;
                };
                reader.readAsDataURL(file);
            }
        }

        // Enhanced form validation with better UX
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert-auto-hide');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s ease-out';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            });

            // Password form validation
            const passwordForm = document.getElementById('passwordForm');
            if (passwordForm) {
                passwordForm.addEventListener('submit', function(e) {
                    const newPassword = this.querySelector('input[name="new_password"]').value;
                    const confirmPassword = this.querySelector('input[name="confirm_password"]').value;
                    const currentPassword = this.querySelector('input[name="current_password"]').value;
                    
                    if (!currentPassword) {
                        e.preventDefault();
                        showNotification('Please enter your current password', 'error');
                        return false;
                    }
                    
                    if (newPassword !== confirmPassword) {
                        e.preventDefault();
                        showNotification('New password and confirmation password do not match!', 'error');
                        return false;
                    }
                    
                    if (newPassword.length < 8) {
                        e.preventDefault();
                        showNotification('Password must be at least 8 characters long!', 'error');
                        return false;
                    }

                    // Check password strength
                    const strongPassword = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/;
                    if (!strongPassword.test(newPassword)) {
                        const proceed = confirm('Your password is weak. For better security, use a mix of uppercase, lowercase, numbers, and special characters. Continue anyway?');
                        if (!proceed) {
                            e.preventDefault();
                            return false;
                        }
                    }
                });
            }

            // Real-time form validation
            const inputs = document.querySelectorAll('input[required], textarea[required]');
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.value.trim() === '') {
                        this.classList.add('border-red-500');
                        this.classList.remove('border-gray-300');
                    } else {
                        this.classList.remove('border-red-500');
                        this.classList.add('border-gray-300');
                    }
                });
            });

            // Email validation
            const emailInputs = document.querySelectorAll('input[type="email"]');
            emailInputs.forEach(input => {
                input.addEventListener('blur', function() {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (this.value && !emailRegex.test(this.value)) {
                        this.classList.add('border-red-500');
                        showNotification('Please enter a valid email address', 'warning');
                    } else {
                        this.classList.remove('border-red-500');
                    }
                });
            });

            // Drag and drop for file uploads
            const dropAreas = document.querySelectorAll('.file-drop-area');
            dropAreas.forEach(area => {
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    area.addEventListener(eventName, preventDefaults, false);
                });

                function preventDefaults(e) {
                    e.preventDefault();
                    e.stopPropagation();
                }

                ['dragenter', 'dragover'].forEach(eventName => {
                    area.addEventListener(eventName, highlight, false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    area.addEventListener(eventName, unhighlight, false);
                });

                function highlight(e) {
                    area.classList.add('border-blue-500', 'bg-blue-50');
                }

                function unhighlight(e) {
                    area.classList.remove('border-blue-500', 'bg-blue-50');
                }

                area.addEventListener('drop', handleDrop, false);

                function handleDrop(e) {
                    const dt = e.dataTransfer;
                    const files = dt.files;
                    const input = area.querySelector('input[type="file"]');
                    
                    if (input && files.length > 0) {
                        input.files = files;
                        const previewId = input.id === 'logoInput' ? 'logo-preview' : 'banner-preview';
                        previewImage(input, previewId);
                    }
                }
            });
        });

        // Enhanced notification system
        function showNotification(message, type = 'info', duration = 3000) {
            const notification = document.createElement('div');
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                warning: 'bg-yellow-500',
                info: 'bg-blue-500'
            };
            
            const icons = {
                success: 'check-circle',
                error: 'x-circle',
                warning: 'alert-triangle',
                info: 'info'
            };
            
            notification.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-xl shadow-lg z-50 max-w-md transform translate-x-full transition-transform duration-300`;
            notification.innerHTML = `<i data-lucide="${icons[type]}" class="w-4 h-4 inline mr-2"></i>${message}`;
            
            document.body.appendChild(notification);
            lucide.createIcons();
            
            // Slide in
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);
            
            // Slide out and remove
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => notification.remove(), 300);
            }, duration);
        }

        // URL parameter handling for success/error messages
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('success') === '1') {
            showNotification('Settings saved successfully!', 'success');
        }
        
        if (urlParams.get('error')) {
            showNotification(decodeURIComponent(urlParams.get('error')), 'error', 5000);
        }

        // Auto-save functionality for certain fields
        let autoSaveTimeout;
        const autoSaveFields = document.querySelectorAll('input[type="number"][name^="points_"]');
        autoSaveFields.forEach(field => {
            field.addEventListener('input', function() {
                clearTimeout(autoSaveTimeout);
                this.style.borderColor = '#fbbf24'; // Yellow border to indicate pending changes
                
                autoSaveTimeout = setTimeout(() => {
                    // Auto-save after 2 seconds of no input
                    this.style.borderColor = '#10b981'; // Green border to indicate saved
                    // Note: Actual auto-save implementation would require AJAX
                }, 2000);
            });
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + S to save current form
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                const activeTab = document.querySelector('.tab-content.active');
                const form = activeTab?.querySelector('form');
                if (form) {
                    showNotification('Saving settings...', 'info', 1000);
                    form.submit();
                }
            }
            
            // Escape to close any open modals or notifications
            if (e.key === 'Escape') {
                const notifications = document.querySelectorAll('.fixed.top-4.right-4');
                notifications.forEach(notification => notification.remove());
            }
        });

        // Initialize tooltips and enhanced UX
        setTimeout(() => {
            // Add smooth scrolling to tab switching
            const tabButtons = document.querySelectorAll('[onclick^="showTab"]');
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    document.querySelector('main').scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                });
            });
        }, 100);

            document.addEventListener('DOMContentLoaded', function() {
        // Map for barangay location
        var lat = parseFloat(document.getElementById('barangay_latitude').value) || 6.4216;
        var lng = parseFloat(document.getElementById('barangay_longitude').value) || 124.7809;
        var map = L.map('barangay-map').setView([lat, lng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        var marker = L.marker([lat, lng], {draggable:true}).addTo(map);
        marker.on('dragend', function(e) {
            var pos = marker.getLatLng();
            document.getElementById('barangay_latitude').value = pos.lat.toFixed(6);
            document.getElementById('barangay_longitude').value = pos.lng.toFixed(6);
        });
    });