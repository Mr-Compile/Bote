
        // Initialize Lucide icons
        lucide.createIcons();

        // Enhanced modal functions with animations
        function showAddWorkerModal() {
            const modal = document.getElementById('addWorkerModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            // Add entrance animation
            const modalContent = modal.querySelector('.modal-enter');
            modalContent.style.animation = 'modalEnter 0.3s ease-out forwards';
            
            // Focus first input
            setTimeout(() => {
                modal.querySelector('input[name="first_name"]').focus();
            }, 100);
        }

        function hideAddWorkerModal() {
            const modal = document.getElementById('addWorkerModal');
            const modalContent = modal.querySelector('.modal-enter');
            
            // Add exit animation
            modalContent.style.animation = 'modalExit 0.3s ease-in forwards';
            
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                // Reset form and animation
                document.querySelector('#addWorkerModal form').reset();
                modalContent.style.animation = '';
            }, 300);
        }

        function showEditModal(user) {
            const modal = document.getElementById('editModal');
            
            // Populate modal with user data
            document.getElementById('edit_user_id').value = user.user_id;
            document.getElementById('edit_user_name').textContent = user.first_name + ' ' + user.last_name;
            document.getElementById('edit_user_email').textContent = user.email;
            document.getElementById('edit_role').value = user.role;
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            // Add entrance animation
            const modalContent = modal.querySelector('.modal-enter');
            modalContent.style.animation = 'modalEnter 0.3s ease-out forwards';
        }

        function hideEditModal() {
            const modal = document.getElementById('editModal');
            const modalContent = modal.querySelector('.modal-enter');
            
            // Add exit animation
            modalContent.style.animation = 'modalExit 0.3s ease-in forwards';
            
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                modalContent.style.animation = '';
            }, 300);
        }

        function confirmDelete(userId) {
            const modal = document.getElementById('deleteModal');
            document.getElementById('delete_user_id').value = userId;
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            // Add entrance animation
            const modalContent = modal.querySelector('.modal-enter');
            modalContent.style.animation = 'modalEnter 0.3s ease-out forwards';
        }

        function hideDeleteModal() {
            const modal = document.getElementById('deleteModal');
            const modalContent = modal.querySelector('.modal-enter');
            
            // Add exit animation
            modalContent.style.animation = 'modalExit 0.3s ease-in forwards';
            
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                modalContent.style.animation = '';
            }, 300);
        }

        // Password visibility toggle
        function togglePassword(fieldId, button) {
            const field = document.getElementById(fieldId);
            const icon = button.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                field.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            
            // Reinitialize the icon
            lucide.createIcons();
        }

        // Enhanced search functionality
        function initializeSearch() {
            const searchInput = document.getElementById('userSearch');
            const table = document.getElementById('usersTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                let visibleRows = 0;

                Array.from(rows).forEach(row => {
                    if (row.cells.length === 1) return; // Skip empty state row
                    
                    const name = row.cells[0].textContent.toLowerCase();
                    const email = row.cells[1].textContent.toLowerCase();
                    const role = row.cells[2].textContent.toLowerCase();
                    
                    const isVisible = name.includes(searchTerm) || 
                                    email.includes(searchTerm) || 
                                    role.includes(searchTerm);
                    
                    row.style.display = isVisible ? '' : 'none';
                    if (isVisible) visibleRows++;
                });

                // Update table footer
                updateTableFooter(visibleRows);
            });

            // Add clear search button functionality
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    this.value = '';
                    this.dispatchEvent(new Event('input'));
                }
            });
        }

        function updateTableFooter(visibleCount) {
            const footer = document.querySelector('.bg-gray-50.border-t span');
            if (footer) {
                const totalUsers = document.querySelectorAll('#usersTable tbody tr').length;
                const filterText = visibleCount < totalUsers ? ` (filtered from ${totalUsers})` : '';
                footer.innerHTML = `<i data-lucide="users" class="w-4 h-4 mr-1"></i>Showing ${visibleCount} users${filterText}`;
                lucide.createIcons();
            }
        }

        // Auto-dismiss notifications
        function initializeNotifications() {
            const notifications = document.querySelectorAll('.notification');
            notifications.forEach(notification => {
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.style.animation = 'slideOutRight 0.5s ease-in forwards';
                        setTimeout(() => {
                            notification.remove();
                        }, 500);
                    }
                }, 5000);
            });
        }

        // Form validation
        function initializeFormValidation() {
            const form = document.getElementById('addWorkerForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const password = this.querySelector('input[name="password"]').value;
                    const email = this.querySelector('input[name="email"]').value;
                    
                    // Email validation
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(email)) {
                        e.preventDefault();
                        showNotification('Please enter a valid email address.', 'error');
                        return;
                    }
                    
                    // Password validation
                    if (password.length < 6) {
                        e.preventDefault();
                        showNotification('Password must be at least 6 characters long.', 'error');
                        return;
                    }
                });
            }
        }

        // Enhanced notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            const colors = {
                success: 'from-green-400 to-green-500',
                error: 'from-red-400 to-red-500',
                warning: 'from-yellow-400 to-yellow-500',
                info: 'from-blue-400 to-blue-500'
            };
            
            const icons = {
                success: 'check-circle',
                error: 'x-circle',
                warning: 'alert-triangle',
                info: 'info'
            };
            
            notification.className = `fixed top-4 right-4 bg-gradient-to-r ${colors[type] || colors.info} text-white px-6 py-4 rounded-xl shadow-lg z-50 notification flex items-center max-w-md`;
            notification.innerHTML = `
                <i data-lucide="${icons[type] || icons.info}" class="w-5 h-5 mr-3"></i>
                <span class="font-medium">${message}</span>
                <button onclick="this.parentElement.remove()" class="ml-auto">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            `;
            
            document.body.appendChild(notification);
            lucide.createIcons();
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.style.animation = 'slideOutRight 0.5s ease-in forwards';
                    setTimeout(() => {
                        notification.remove();
                    }, 500);
                }
            }, 5000);
        }

        // Close modals when clicking outside
        ['addWorkerModal', 'editModal', 'deleteModal'].forEach(modalId => {
            document.getElementById(modalId).addEventListener('click', function(e) {
                if (e.target === this) {
                    // Trigger the appropriate hide function
                    switch(modalId) {
                        case 'addWorkerModal': hideAddWorkerModal(); break;
                        case 'editModal': hideEditModal(); break;
                        case 'deleteModal': hideDeleteModal(); break;
                    }
                }
            });
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // ESC to close modals
            if (e.key === 'Escape') {
                const modals = ['addWorkerModal', 'editModal', 'deleteModal'];
                modals.forEach(modalId => {
                    const modal = document.getElementById(modalId);
                    if (!modal.classList.contains('hidden')) {
                        switch(modalId) {
                            case 'addWorkerModal': hideAddWorkerModal(); break;
                            case 'editModal': hideEditModal(); break;
                            case 'deleteModal': hideDeleteModal(); break;
                        }
                    }
                });
            }
            
            // Ctrl+N to add new worker
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                showAddWorkerModal();
            }
        });

        // Initialize everything when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            initializeSearch();
            initializeNotifications();
            initializeFormValidation();
            
            // Initialize tooltips if needed
            const tooltipElements = document.querySelectorAll('[title]');
            tooltipElements.forEach(element => {
                element.addEventListener('mouseenter', function() {
                    // Could add custom tooltip implementation here
                });
            });
        });

        // Add keyboard navigation for accessibility
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                // Ensure proper tab order in modals
                const activeModal = document.querySelector('.fixed:not(.hidden)');
                if (activeModal) {
                    const focusableElements = activeModal.querySelectorAll('input, button, select, textarea');
                    const firstElement = focusableElements[0];
                    const lastElement = focusableElements[focusableElements.length - 1];
                    
                    if (e.shiftKey && document.activeElement === firstElement) {
                        e.preventDefault();
                        lastElement.focus();
                    } else if (!e.shiftKey && document.activeElement === lastElement) {
                        e.preventDefault();
                        firstElement.focus();
                    }
                }
            }
        });

