
        lucide.createIcons();

        // Enhanced Modal Functions
        function showAddTypeModal() {
            document.getElementById('addTypeModal').classList.remove('hidden');
            document.getElementById('addTypeModal').classList.add('flex');
            resetImagePreview('add');
        }

        function hideAddTypeModal() {
            document.getElementById('addTypeModal').classList.add('hidden');
            document.getElementById('addTypeModal').classList.remove('flex');
            document.querySelector('#addTypeModal form').reset();
            resetImagePreview('add');
        }

        function showEditTypeModal(type) {
            document.getElementById('edit_type_id').value = type.type_id;
            document.getElementById('edit_name').value = type.name;
            document.getElementById('edit_unit').value = type.unit;
            
            // Handle unit-specific fields
            if (type.unit === 'kg') {
                document.getElementById('edit_points_kg_field').classList.remove('hidden');
                document.getElementById('edit_points_unit_field').classList.add('hidden');
                document.getElementById('edit_points_per_kg').value = type.points_per_kg || '';
            } else {
                document.getElementById('edit_points_kg_field').classList.add('hidden');
                document.getElementById('edit_points_unit_field').classList.remove('hidden');
                document.getElementById('edit_points_per_unit').value = type.points_per_unit || '';
            }
            
            document.getElementById('editTypeModal').classList.remove('hidden');
            document.getElementById('editTypeModal').classList.add('flex');
        }

        function hideEditTypeModal() {
            document.getElementById('editTypeModal').classList.add('hidden');
            document.getElementById('editTypeModal').classList.remove('flex');
        }

        function confirmDeleteType(typeId) {
            document.getElementById('delete_type_id').value = typeId;
            document.getElementById('deleteTypeModal').classList.remove('hidden');
            document.getElementById('deleteTypeModal').classList.add('flex');
        }

        function hideDeleteTypeModal() {
            document.getElementById('deleteTypeModal').classList.add('hidden');
            document.getElementById('deleteTypeModal').classList.remove('flex');
        }

        // Close modals when clicking outside
        ['addTypeModal', 'editTypeModal', 'deleteTypeModal'].forEach(modalId => {
            document.getElementById(modalId).addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.add('hidden');
                    this.classList.remove('flex');
                }
            });
        });

        // Handle unit changes in add modal
        document.getElementById('unit').addEventListener('change', function () {
            if (this.value === 'kg') {
                document.getElementById('points_kg_field').classList.remove('hidden');
                document.getElementById('points_unit_field').classList.add('hidden');
                document.querySelector('#points_unit_field input').required = false;
                document.querySelector('#points_kg_field input').required = true;
                document.querySelector('#points_unit_field input').value = ''; // Clear value when hidden
            } else {
                document.getElementById('points_kg_field').classList.add('hidden');
                document.getElementById('points_unit_field').classList.remove('hidden');
                document.querySelector('#points_kg_field input').required = false;
                document.querySelector('#points_unit_field input').required = true;
                document.querySelector('#points_kg_field input').value = ''; // Clear value when hidden
            }
        });

        // Handle unit changes in edit modal
        document.getElementById('edit_unit').addEventListener('change', function () {
            if (this.value === 'kg') {
                document.getElementById('edit_points_kg_field').classList.remove('hidden');
                document.getElementById('edit_points_unit_field').classList.add('hidden');
                document.querySelector('#edit_points_unit_field input').required = false;
                document.querySelector('#edit_points_kg_field input').required = true;
                document.querySelector('#edit_points_unit_field input').value = ''; // Clear value when hidden
            } else {
                document.getElementById('edit_points_kg_field').classList.add('hidden');
                document.getElementById('edit_points_unit_field').classList.remove('hidden');
                document.querySelector('#edit_points_kg_field input').required = false;
                document.querySelector('#edit_points_unit_field input').required = true;
                document.querySelector('#edit_points_kg_field input').value = ''; // Clear value when hidden
            }
        });

        // Image Preview Functions
        function previewAddImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('add_preview_img').src = e.target.result;
                    document.getElementById('add_image_preview').classList.remove('hidden');
                    document.getElementById('add_image_placeholder').classList.add('hidden');
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function previewEditImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('edit_preview_img').src = e.target.result;
                    document.getElementById('edit_image_preview').classList.remove('hidden');
                    document.getElementById('edit_image_placeholder').classList.add('hidden');
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function resetImagePreview(prefix) {
            document.getElementById(prefix + '_image_preview').classList.add('hidden');
            document.getElementById(prefix + '_image_placeholder').classList.remove('hidden');
            document.getElementById(prefix + '_image').value = '';
        }

        // Drag and Drop functionality
        function setupDragDrop() {
            const dropAreas = document.querySelectorAll('.file-drop-area');
            
            dropAreas.forEach(dropArea => {
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    dropArea.addEventListener(eventName, preventDefaults, false);
                });

                function preventDefaults(e) {
                    e.preventDefault();
                    e.stopPropagation();
                }

                ['dragenter', 'dragover'].forEach(eventName => {
                    dropArea.addEventListener(eventName, highlight, false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    dropArea.addEventListener(eventName, unhighlight, false);
                });

                function highlight(e) {
                    dropArea.classList.add('border-green-500', 'bg-green-50');
                }

                function unhighlight(e) {
                    dropArea.classList.remove('border-green-500', 'bg-green-50');
                }

                dropArea.addEventListener('drop', handleDrop, false);

                function handleDrop(e) {
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        const input = dropArea.parentElement.querySelector('input[type="file"]');
                        input.files = files;
                        
                        // Trigger change event
                        const event = new Event('change', { bubbles: true });
                        input.dispatchEvent(event);
                    }
                }
            });
        }

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            setupDragDrop();
        });

        // Auto-hide success/error messages
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert-auto-hide');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s ease-in-out';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            });
        });
