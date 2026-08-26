
        lucide.createIcons();

        function showAddModal() {
            document.getElementById('addModal').classList.remove('hidden');
            document.getElementById('addModal').classList.add('flex');
        }

        function hideAddModal() {
            document.getElementById('addModal').classList.add('hidden');
            document.getElementById('addModal').classList.remove('flex');
        }

        function showEditModal(reward) {
            document.getElementById('edit_reward_id').value = reward.reward_id;
            document.getElementById('edit_name').value = reward.name;
            document.getElementById('edit_points').value = reward.points_required;
            document.getElementById('edit_stock').value = reward.stock;
            document.getElementById('edit_description').value = reward.description || '';
            
            // Handle current image display
            const currentImageSection = document.getElementById('currentImageSection');
            const currentImage = document.getElementById('currentImage');
            const imageLabel = document.getElementById('imageLabel');
            
            if (reward.image) {
                currentImage.src = '../uploads/' + reward.image;
                currentImageSection.style.display = 'block';
                imageLabel.textContent = 'Replace Image (Optional)';
            } else {
                currentImageSection.style.display = 'none';
                imageLabel.textContent = 'Reward Image (Optional)';
            }
            
            // Reset file input and preview
            document.getElementById('edit_reward_image').value = '';
            document.getElementById('editImagePreview').classList.add('hidden');
            
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editModal').classList.add('flex');
        }

        function hideEditModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('editModal').classList.remove('flex');
        }

        function confirmDelete(rewardId) {
            document.getElementById('delete_reward_id').value = rewardId;
            document.getElementById('deleteModal').classList.remove('hidden');
            document.getElementById('deleteModal').classList.add('flex');
        }

        function hideDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            document.getElementById('deleteModal').classList.remove('flex');
        }

        // Close modals when clicking outside
        ['addModal', 'editModal', 'deleteModal'].forEach(modalId => {
            document.getElementById(modalId).addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.add('hidden');
                    this.classList.remove('flex');
                }
            });
        });

        // Image preview functionality
        function setupImagePreview() {
            // Add modal image preview
            const addImageInput = document.getElementById('reward_image');
            const addPreview = document.getElementById('imagePreview');
            const addPreviewImg = document.getElementById('previewImg');
            
            if (addImageInput) {
                addImageInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            addPreviewImg.src = e.target.result;
                            addPreview.classList.remove('hidden');
                        };
                        reader.readAsDataURL(file);
                    } else {
                        addPreview.classList.add('hidden');
                    }
                });
            }
            
            // Edit modal image preview
            const editImageInput = document.getElementById('edit_reward_image');
            const editPreview = document.getElementById('editImagePreview');
            const editPreviewImg = document.getElementById('editPreviewImg');
            
            if (editImageInput) {
                editImageInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            editPreviewImg.src = e.target.result;
                            editPreview.classList.remove('hidden');
                        };
                        reader.readAsDataURL(file);
                    } else {
                        editPreview.classList.add('hidden');
                    }
                });
            }
        }

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            setupImagePreview();
        });
