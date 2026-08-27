import { getPageData } from '../../shared.js';
const pageData = getPageData('my-requests-data');
const csrfToken = pageData?.csrfToken ?? '';
const requestLocations = pageData?.requestLocations ?? [];
function cancelRequest(requestId) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.innerHTML = `
        <div class="bg-white rounded-2xl p-8 max-w-md mx-4 transform scale-0 transition-transform duration-300">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="alert-triangle" class="w-8 h-8 text-red-600"></i>
                </div>
                <h3 class="text-xl font-bold text-bote-stone-900 mb-2">Cancel Request</h3>
                <p class="text-bote-stone-600">Are you sure you want to cancel this pickup request? This action cannot be undone.</p>
            </div>
            <div class="flex space-x-4">
                <button onclick="closeModal()" class="flex-1 px-4 py-2 bg-bote-stone-100 hover:bg-bote-stone-200 text-bote-stone-800 rounded-xl font-medium transition-colors">
                    Keep Request
                </button>
                <button onclick="confirmCancel(${requestId})" class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-medium transition-colors">
                    Cancel Request
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    setTimeout(() => {
        const inner = modal.querySelector('.bg-white');
        if (inner) {
            inner.style.transform = 'scale(1)';
        }
    }, 100);
}
function closeModal() {
    const modal = document.querySelector('.fixed.inset-0');
    if (modal) {
        const content = modal.querySelector('.bg-white');
        if (content) {
            content.style.transform = 'scale(0)';
        }
        setTimeout(() => modal.remove(), 300);
    }
}
function confirmCancel(requestId) {
    const modal = document.querySelector('.fixed.inset-0');
    if (!modal)
        return;
    const buttons = modal.querySelectorAll('button');
    const confirmButton = buttons[buttons.length - 1];
    if (!confirmButton)
        return;
    confirmButton.innerHTML = '<div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin mx-auto"></div>';
    confirmButton.setAttribute('disabled', 'true');
    fetch('cancel_request.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            request_id: requestId,
            csrf_token: csrfToken
        })
    })
        .then(response => response.json())
        .then(data => {
        closeModal();
        if (data.success) {
            showNotification(data.message, 'success');
            const requestCard = document.querySelector(`[data-request-id="${requestId}"]`);
            if (requestCard) {
                requestCard.style.transform = 'translateX(-100%)';
                requestCard.style.opacity = '0';
                setTimeout(() => requestCard.remove(), 300);
            }
        }
        else {
            showNotification(data.message, 'error');
        }
    })
        .catch(error => {
        closeModal();
        console.error('Error:', error);
        showNotification('Failed to cancel request. Please try again.', 'error');
    });
}
function viewDetails(requestId) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
    modal.innerHTML = `
        <div class="bg-white rounded-2xl max-w-2xl w-full max-h-screen overflow-y-auto transform scale-0 transition-transform duration-300">
            <div class="bg-gradient-to-r from-bote-info-500 to-bote-info-600 text-white p-6 rounded-t-2xl">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-white bg-opacity-20 rounded-xl flex items-center justify-center mr-4">
                            <i data-lucide="file-text" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold">Request Details</h3>
                            <p class="text-bote-info-100">Request #${String(requestId).padStart(4, '0')}</p>
                        </div>
                    </div>
                    <button onclick="closeModal()" class="p-2 hover:bg-white hover:bg-opacity-20 rounded-lg transition-colors">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div class="space-y-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-bote-stone-50 rounded-xl p-4">
                            <h4 class="font-semibold text-bote-stone-900 mb-2">Status</h4>
                            <p class="text-bote-stone-600">Pending Review</p>
                        </div>
                        <div class="bg-bote-stone-50 rounded-xl p-4">
                            <h4 class="font-semibold text-bote-stone-900 mb-2">Priority</h4>
                            <p class="text-bote-stone-600">Standard</p>
                        </div>
                    </div>
                    <div class="bg-bote-green-50 rounded-xl p-4">
                        <h4 class="font-semibold text-bote-green-900 mb-2">Environmental Impact</h4>
                        <p class="text-bote-green-700 text-sm">This request helps reduce waste and supports sustainable recycling practices in your community.</p>
                    </div>
                    <div class="text-center">
                        <button onclick="closeModal()" class="px-6 py-2 bg-bote-green-600 hover:bg-bote-green-700 text-white rounded-xl font-medium transition-colors">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    setTimeout(() => {
        const inner = modal.querySelector('.bg-white');
        if (inner) {
            inner.style.transform = 'scale(1)';
        }
    }, 100);
}
function refreshRequests(button) {
    const target = button ?? window.event?.currentTarget;
    if (!target)
        return;
    const icon = target.querySelector('i');
    if (icon) {
        icon.style.animation = 'spin 1s linear infinite';
    }
    setTimeout(() => {
        if (icon) {
            icon.style.animation = '';
        }
        showNotification('Requests refreshed', 'info');
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
    }, 3000);
}
window.cancelRequest = cancelRequest;
window.closeModal = closeModal;
window.confirmCancel = confirmCancel;
window.viewDetails = viewDetails;
window.refreshRequests = refreshRequests;
document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    // Initialize the map when the DOM is fully loaded
    const mapContainer = document.getElementById('map');
    if (mapContainer && typeof L !== 'undefined') {
        const map = L.map('map').setView([6.421499, 124.7811294], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        requestLocations.forEach(function (location) {
            if (location.latitude && location.longitude) {
                L.marker([location.latitude, location.longitude])
                    .addTo(map)
                    .bindPopup('Status: ' + location.status);
            }
        });
    }
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', (e) => {
            const filterValue = e.currentTarget.value;
            const requestCards = document.querySelectorAll('[data-status]');
            requestCards.forEach((card, index) => {
                const shouldShow = !filterValue || card.dataset.status === filterValue;
                if (shouldShow) {
                    card.style.display = 'block';
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, index * 50);
                }
                else {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(-20px)';
                    setTimeout(() => {
                        card.style.display = 'none';
                    }, 300);
                }
            });
        });
    }
    const progressBars = document.querySelectorAll('.progress-bar');
    progressBars.forEach((bar, index) => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.width = width;
        }, 500 + (index * 200));
    });
    const requestCards = document.querySelectorAll('.request-card');
    requestCards.forEach(card => {
        card.addEventListener('mouseenter', function () {
            this.style.transform = 'translateY(-4px)';
        });
        card.addEventListener('mouseleave', function () {
            this.style.transform = 'translateY(0)';
        });
    });
    document.addEventListener('keydown', (e) => {
        if (e.ctrlKey || e.metaKey) {
            switch (e.key) {
                case 'n':
                    e.preventDefault();
                    window.location.href = 'resident_new_request.php';
                    break;
                case 'r':
                    e.preventDefault();
                    refreshRequests();
                    break;
            }
        }
    });
    if (document.querySelectorAll('.request-card').length === 0) {
        setTimeout(() => {
            showNotification('Welcome! Create your first pickup request to get started.', 'info');
        }, 2000);
    }
    const style = document.createElement('style');
    style.textContent = `
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .request-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card-enter {
            opacity: 0;
            transform: translateY(20px);
            animation: cardEnterAnimation 0.6s ease-out forwards;
        }

        @keyframes cardEnterAnimation {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    `;
    document.head.appendChild(style);
});
//# sourceMappingURL=my_requests.js.map