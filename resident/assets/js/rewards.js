
    lucide.createIcons();
    
    let currentRedemption = null;
    // This will be set from the PHP file
    const userPoints = window.userPoints || 0;

    function redeemReward(rewardId, rewardName, pointsRequired) {
        currentRedemption = {
            id: rewardId,
            name: rewardName,
            points: pointsRequired
        };
        
        document.getElementById('redemptionText').innerHTML = `
            <span class="font-semibold">${rewardName}</span><br>
            <span class="text-purple-600 font-bold">${pointsRequired.toLocaleString()} points</span>
        `;
        
        const remainingPoints = userPoints - pointsRequired;
        document.getElementById('remainingPoints').textContent = remainingPoints.toLocaleString();
        
        const modal = document.getElementById('redemptionModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            modal.querySelector('.bg-white').classList.remove('scale-95');
            modal.querySelector('.bg-white').classList.add('scale-100');
        }, 10);
    }

    function hideRedemptionModal() {
        const modal = document.getElementById('redemptionModal');
        modal.querySelector('.bg-white').classList.remove('scale-100');
        modal.querySelector('.bg-white').classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 300);
    }

    function confirmRedemption() {
        if (!currentRedemption) return;
        
        // Show loading state
        const confirmBtn = document.getElementById('confirmBtn');
        const confirmText = confirmBtn.querySelector('.confirm-text');
        const confirmLoader = confirmBtn.querySelector('.confirm-loader');
        
        confirmText.classList.add('hidden');
        confirmLoader.classList.remove('hidden');
        confirmBtn.disabled = true;
        confirmBtn.classList.add('opacity-75');
        
        // Simulate API call
        setTimeout(() => {
            hideRedemptionModal();
            showNotification('Redemption request submitted successfully!', 'success');
            
            // Reset button state
            confirmText.classList.remove('hidden');
            confirmLoader.classList.add('hidden');
            confirmBtn.disabled = false;
            confirmBtn.classList.remove('opacity-75');
            
            // Could reload page or update UI dynamically
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        }, 2000);
    }

    function refreshRewards() {
        const button = event.target.closest('button');
        const icon = button.querySelector('i');
        
        // Animate refresh icon
        icon.style.animation = 'spin 1s linear infinite';
        
        // Simulate refresh
        setTimeout(() => {
            icon.style.animation = '';
            showNotification('Rewards refreshed successfully', 'info');
        }, 1000);
    }

    function showNotification(message, type = 'info') {
        const colors = {
            success: 'from-green-500 to-emerald-600',
            error: 'from-red-500 to-pink-600',
            info: 'from-blue-500 to-indigo-600'
        };
        
        const icons = {
            success: 'check-circle',
            error: 'alert-circle',
            info: 'info'
        };
        
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 bg-gradient-to-r ${colors[type]} text-white px-6 py-4 rounded-2xl shadow-xl z-50 transform translate-x-full transition-transform duration-500`;
        notification.innerHTML = `
            <div class="flex items-center">
                <i data-lucide="${icons[type]}" class="w-5 h-5 mr-3"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        lucide.createIcons();
        
        setTimeout(() => notification.style.transform = 'translateX(0)', 100);
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => notification.remove(), 500);
        }, 4000);
    }

    // Sort functionality
    document.getElementById('sortRewards').addEventListener('change', function() {
        const sortValue = this.value;
        const rewardsGrid = document.getElementById('rewardsGrid');
        const cards = Array.from(rewardsGrid.children);
        
        cards.sort((a, b) => {
            const aPoints = parseInt(a.dataset.points);
            const bPoints = parseInt(b.dataset.points);
            const aStock = parseInt(a.dataset.stock);
            const bStock = parseInt(b.dataset.stock);
            
            switch(sortValue) {
                case 'points_asc':
                    return aPoints - bPoints;
                case 'points_desc':
                    return bPoints - aPoints;
                case 'stock':
                    return bStock - aStock;
                default:
                    return 0;
            }
        });
        
        // Clear and re-add sorted cards with animation
        rewardsGrid.innerHTML = '';
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            rewardsGrid.appendChild(card);
            
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });

    // Enhanced page interactions
    document.addEventListener('DOMContentLoaded', function() {
        // Animate progress bars
        const progressBars = document.querySelectorAll('.progress-bar');
        progressBars.forEach((bar, index) => {
            const width = bar.style.width;
            bar.style.width = '0%';
            setTimeout(() => {
                bar.style.transition = 'width 2s ease-in-out';
                bar.style.width = width;
            }, 500 + (index * 200));
        });

        // Add entrance animations
        const rewardCards = document.querySelectorAll('.reward-card');
        const redemptionCards = document.querySelectorAll('.redemption-card');
        
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animation = 'slideInUp 0.6s ease-out forwards';
                }
            });
        }, observerOptions);
        
        [...rewardCards, ...redemptionCards].forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            observer.observe(card);
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                hideRedemptionModal();
            }
        });

        // Auto-refresh points counter animation
        setTimeout(() => {
            const pointsCounter = document.querySelector('.points-counter');
            if (pointsCounter) {
                pointsCounter.style.animation = 'pulse 2s ease-in-out infinite';
            }
        }, 3000);

        // Welcome message for new users
        if (window.redemptionsCount === 0) {
            setTimeout(() => {
                showNotification('Welcome to the Reward Store! Start earning and redeeming today.', 'info');
            }, 2000);
        }
    });

    // Close modal when clicking outside
    document.getElementById('redemptionModal').addEventListener('click', function(e) {
        if (e.target === this) {
            hideRedemptionModal();
        }
    });

    // Add custom CSS animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .card-enter {
            animation: slideInUp 0.6s ease-out forwards;
        }
        
        .redemption-card {
            opacity: 0;
            transform: translateY(20px);
            animation: slideInUp 0.6s ease-out forwards;
        }
        
        .line-clamp-1 {
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    `;
    document.head.appendChild(style);
