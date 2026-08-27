import { getPageData } from '../../shared.js';
const pageData = getPageData('rewards-data');
const userPoints = pageData?.userPoints ?? 0;
const redemptionsCount = pageData?.redemptionsCount ?? 0;
let currentRedemption = null;
function redeemReward(rewardId, rewardName, pointsRequired) {
    currentRedemption = {
        id: rewardId,
        name: rewardName,
        points: pointsRequired
    };
    const redemptionText = document.getElementById('redemptionText');
    const remainingPointsEl = document.getElementById('remainingPoints');
    const modal = document.getElementById('redemptionModal');
    if (!redemptionText || !remainingPointsEl || !modal) {
        return;
    }
    redemptionText.innerHTML = `
        <span class="font-semibold">${rewardName}</span><br>
        <span class="text-bote-green-600 font-bold">${pointsRequired.toLocaleString()} points</span>
    `;
    remainingPointsEl.textContent = (userPoints - pointsRequired).toLocaleString();
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    setTimeout(() => {
        const modalContent = modal.querySelector('.bg-white');
        if (modalContent) {
            modalContent.classList.remove('scale-95');
            modalContent.classList.add('scale-100');
        }
    }, 10);
}
function hideRedemptionModal() {
    const modal = document.getElementById('redemptionModal');
    if (!modal)
        return;
    const modalContent = modal.querySelector('.bg-white');
    if (modalContent) {
        modalContent.classList.remove('scale-100');
        modalContent.classList.add('scale-95');
    }
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }, 300);
}
function confirmRedemption() {
    if (!currentRedemption)
        return;
    const confirmBtn = document.getElementById('confirmBtn');
    if (!confirmBtn)
        return;
    const confirmText = confirmBtn.querySelector('.confirm-text');
    const confirmLoader = confirmBtn.querySelector('.confirm-loader');
    if (confirmText)
        confirmText.classList.add('hidden');
    if (confirmLoader)
        confirmLoader.classList.remove('hidden');
    confirmBtn.setAttribute('disabled', 'true');
    confirmBtn.classList.add('opacity-75');
    fetch('resident_redeem_reward.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            reward_id: currentRedemption.id,
            quantity: 1,
            csrf_token: pageData?.csrfToken ?? ''
        })
    })
        .then(response => {
        if (response.status === 403) {
            throw new Error('Session expired. Please refresh the page and try again.');
        }
        return response.json();
    })
        .then(data => {
        if (confirmText)
            confirmText.classList.remove('hidden');
        if (confirmLoader)
            confirmLoader.classList.add('hidden');
        confirmBtn.removeAttribute('disabled');
        confirmBtn.classList.remove('opacity-75');
        if (data.success) {
            hideRedemptionModal();
            showNotification(data.message || 'Redemption request submitted successfully!', 'success');
            setTimeout(() => window.location.reload(), 1500);
        }
        else {
            showNotification(data.message || 'Redemption failed.', 'error');
        }
    })
        .catch(error => {
        if (confirmText)
            confirmText.classList.remove('hidden');
        if (confirmLoader)
            confirmLoader.classList.add('hidden');
        confirmBtn.removeAttribute('disabled');
        confirmBtn.classList.remove('opacity-75');
        showNotification(error.message || 'Network error. Please try again.', 'error');
    });
}
function refreshRewards(button) {
    const target = button ?? window.event?.currentTarget;
    if (!target)
        return;
    const icon = target.querySelector('i');
    if (icon) {
        icon.style.animation = 'spin 1s linear infinite';
    }
    setTimeout(() => {
        if (icon)
            icon.style.animation = '';
        showNotification('Rewards refreshed successfully', 'info');
    }, 1000);
}
function showNotification(message, type = 'info') {
    const colors = {
        success: 'from-bote-green-500 to-bote-green-600',
        error: 'from-red-500 to-red-600',
        info: 'from-bote-info-500 to-bote-info-600'
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
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    setTimeout(() => notification.style.transform = 'translateX(0)', 100);
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => notification.remove(), 500);
    }, 4000);
}
window.redeemReward = redeemReward;
window.refreshRewards = refreshRewards;
window.hideRedemptionModal = hideRedemptionModal;
window.confirmRedemption = confirmRedemption;
document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    const sortSelect = document.getElementById('sortRewards');
    const rewardsGrid = document.getElementById('rewardsGrid');
    if (sortSelect && rewardsGrid) {
        sortSelect.addEventListener('change', (e) => {
            const sortValue = e.currentTarget.value;
            const cards = Array.from(rewardsGrid.children);
            cards.sort((a, b) => {
                const aPoints = parseInt(a.dataset.points ?? '0', 10);
                const bPoints = parseInt(b.dataset.points ?? '0', 10);
                const aStock = parseInt(a.dataset.stock ?? '0', 10);
                const bStock = parseInt(b.dataset.stock ?? '0', 10);
                switch (sortValue) {
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
    }
    // Animate progress bars
    const progressBars = document.querySelectorAll('.progress-bar');
    progressBars.forEach((bar, index) => {
        const el = bar;
        const width = el.style.width;
        el.style.width = '0%';
        setTimeout(() => {
            el.style.transition = 'width 2s ease-in-out';
            el.style.width = width;
        }, 500 + (index * 200));
    });
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
    [...Array.from(rewardCards), ...Array.from(redemptionCards)].forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        observer.observe(card);
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            hideRedemptionModal();
        }
    });
    const redemptionModal = document.getElementById('redemptionModal');
    if (redemptionModal) {
        redemptionModal.addEventListener('click', (e) => {
            if (e.target === redemptionModal) {
                hideRedemptionModal();
            }
        });
    }
    setTimeout(() => {
        const pointsCounter = document.querySelector('.points-counter');
        if (pointsCounter) {
            pointsCounter.style.animation = 'pulse 2s ease-in-out infinite';
        }
    }, 3000);
    if (redemptionsCount === 0) {
        setTimeout(() => {
            showNotification('Welcome to the Reward Store! Start earning and redeeming today.', 'info');
        }, 2000);
    }
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
});
//# sourceMappingURL=rewards.js.map