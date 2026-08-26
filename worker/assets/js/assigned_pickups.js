/**
 * Assigned Pickups JavaScript
 * Provides interactive functionality for the worker assigned pickups page
 */

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Feather icons
    feather.replace();
    
    // Initialize with soonest first (today's pickups first) for both sections
    sortPickups('available', 'asc');
    sortPickups('assigned', 'asc');
    
    // Add card hover animations
    animateCards();
});

/**
 * Sort pickup cards by date
 * 
 * @param {string} section - The section to sort ('available' or 'assigned')
 * @param {string} order - The sort order ('asc' or 'desc')
 */
function sortPickups(section, order) {
    const containerID = section === 'available' ? 'availablePickupsGrid' : 'assignedPickupsGrid';
    const container = document.getElementById(containerID);
    
    // If container doesn't exist (no pickups available), exit
    if (!container) return;
    
    const cards = Array.from(container.querySelectorAll('.pickup-card'));
    
    // Skip if no cards
    if (cards.length === 0) return;
    
    // Sort cards by preferred date
    cards.sort((a, b) => {
        const dateA = parseInt(a.getAttribute('data-date'));
        const dateB = parseInt(b.getAttribute('data-date'));
        
        if (order === 'asc') {
            return dateA - dateB; // Soonest first (today, tomorrow, etc.)
        } else {
            return dateB - dateA; // Latest first (furthest dates first)
        }
    });
    
    // Temporarily hide container for better performance during DOM manipulation
    container.style.opacity = '0';
    container.style.transition = 'opacity 0.3s ease';
    
    // Clear existing cards and append sorted cards
    container.innerHTML = '';
    cards.forEach(card => container.appendChild(card));
    
    // Show container with fade-in effect
    setTimeout(() => {
        container.style.opacity = '1';
    }, 50);
    
    // Update button states
    updateSortButtons(section, order);
}

/**
 * Update the active state of sort buttons
 * 
 * @param {string} section - The section ('available' or 'assigned')
 * @param {string} activeOrder - The active sort order ('asc' or 'desc')
 */
function updateSortButtons(section, activeOrder) {
    const ascBtn = document.getElementById(section + 'Asc');
    const descBtn = document.getElementById(section + 'Desc');
    
    if (!ascBtn || !descBtn) return;
    
    // Reset all buttons
    ascBtn.classList.remove('active');
    descBtn.classList.remove('active');
    
    // Activate selected button
    if (activeOrder === 'asc') {
        ascBtn.classList.add('active');
    } else {
        descBtn.classList.add('active');
    }
}

/**
 * Add animations to pickup cards
 */
function animateCards() {
    const cards = document.querySelectorAll('.pickup-card');
    
    cards.forEach((card, index) => {
        // Staggered animation delay for cards
        card.style.opacity = '0';
        card.style.transform = 'translateY(10px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 50 * (index % 10)); // Stagger effect, reset after 10 cards
        
        // Add hover interaction effect
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px) scale(1.01)';
            this.style.boxShadow = 'var(--shadow-lg)';
            this.style.zIndex = '1';
            this.style.borderColor = 'var(--primary-light)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
            this.style.boxShadow = 'var(--shadow-sm)';
            this.style.zIndex = '0';
            this.style.borderColor = '#f1f5f9';
        });
    });
}

/**
 * Open the complete pickup modal
 * 
 * @param {number} requestId - The request ID
 * @param {string} unit - The unit of measurement ('kg' or 'pcs')
 * @param {string} residentName - The resident's name
 */
function openCompleteModal(requestId, unit, residentName) {
    const modal = document.getElementById('completeModal');
    const requestIdInput = document.getElementById('modalRequestId');
    const residentNameElement = document.getElementById('modalResidentName');
    const weightInput = document.getElementById('weightInput');
    const quantityInput = document.getElementById('quantityInput');
    
    // Set the values
    requestIdInput.value = requestId;
    residentNameElement.textContent = residentName;
    
    // Show appropriate input based on unit type
    if (unit === 'kg') {
        weightInput.style.display = 'block';
        quantityInput.style.display = 'none';
        document.getElementById('actual_weight').required = true;
        document.getElementById('actual_quantity').required = false;
        // Focus on weight input after a short delay (for animation)
        setTimeout(() => document.getElementById('actual_weight').focus(), 400);
    } else {
        weightInput.style.display = 'none';
        quantityInput.style.display = 'block';
        document.getElementById('actual_weight').required = false;
        document.getElementById('actual_quantity').required = true;
        // Focus on quantity input after a short delay (for animation)
        setTimeout(() => document.getElementById('actual_quantity').focus(), 400);
    }
    
    // Show modal
    modal.style.display = 'flex';
    
    // Apply entrance animation to modal container
    const modalContainer = modal.querySelector('.modal-container');
    modalContainer.style.opacity = '0';
    modalContainer.style.transform = 'translateY(20px)';
    
    setTimeout(() => {
        modalContainer.style.transition = 'all 0.3s cubic-bezier(0.16, 1, 0.3, 1)';
        modalContainer.style.opacity = '1';
        modalContainer.style.transform = 'translateY(0)';
    }, 10);
    
    // Re-render feather icons in the modal
    feather.replace();
}

/**
 * Close the complete pickup modal
 */
function closeCompleteModal() {
    const modal = document.getElementById('completeModal');
    
    // Fade out effect
    const modalContainer = modal.querySelector('.modal-container');
    modalContainer.style.opacity = '0';
    modalContainer.style.transform = 'translateY(20px)';
    
    // Hide modal after animation completes
    setTimeout(() => {
        modal.style.display = 'none';
        modalContainer.style.opacity = '1';
        modalContainer.style.transform = 'translateY(0)';
        
        // Reset form
        document.getElementById('completeForm').reset();
    }, 300);
}

// Close modal when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('completeModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeCompleteModal();
        }
    });
});

/**
 * Add confirmation before completing pickup
 * Will be triggered on form submit
 */
document.addEventListener('DOMContentLoaded', function() {
    const completeForm = document.getElementById('completeForm');
    
    if (completeForm) {
        completeForm.addEventListener('submit', function(e) {
            const weightInput = document.getElementById('actual_weight');
            const quantityInput = document.getElementById('actual_quantity');
            
            // Check if either weight or quantity has a value
            if ((weightInput && weightInput.required && !weightInput.value) || 
                (quantityInput && quantityInput.required && !quantityInput.value)) {
                return; // Let the browser handle the required field validation
            }
            
            // Get the value that's being used
            let value;
            let unit;
            
            if (weightInput && weightInput.style.display !== 'none' && weightInput.value) {
                value = weightInput.value;
                unit = 'kg';
            } else if (quantityInput && quantityInput.style.display !== 'none' && quantityInput.value) {
                value = quantityInput.value;
                unit = 'pcs';
            }
            
            // Confirmation prompt is optional - could be enabled/disabled in settings
            // Uncomment to add confirmation dialog
            /*
            if (!confirm(`Are you sure you want to complete this pickup with ${value} ${unit}?`)) {
                e.preventDefault();
                return false;
            }
            */
        });
    }
});