import { getPageData } from './shared.js';
function lucideCreateIcons() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}
function goTo(url) {
    window.location.href = url;
}
function filterHistory(filter) {
    document.querySelectorAll('.history-card').forEach(card => {
        const c = card;
        if (filter === 'all' || c.getAttribute('data-type') === filter) {
            c.classList.remove('hidden');
        }
        else {
            c.classList.add('hidden');
        }
    });
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('bg-bote-green-600', 'text-white');
        btn.classList.add('bg-bote-stone-100', 'text-bote-stone-600');
    });
    const activeBtn = document.getElementById('filter-' + filter);
    if (activeBtn) {
        activeBtn.classList.remove('bg-bote-stone-100', 'text-bote-stone-600');
        activeBtn.classList.add('bg-bote-green-600', 'text-white');
    }
}
function searchHistory() {
    const searchInput = document.getElementById('historySearch');
    if (!searchInput)
        return;
    const query = searchInput.value.toLowerCase();
    document.querySelectorAll('.history-card').forEach(card => {
        const c = card;
        const text = (c.textContent ?? '').toLowerCase();
        if (text.includes(query)) {
            c.classList.remove('hidden');
        }
        else {
            c.classList.add('hidden');
        }
    });
}
function cancelRequest(requestId) {
    const modal = document.getElementById('cancelModal');
    if (modal) {
        const form = modal.querySelector('form');
        if (form) {
            form.action = `cancel_request.php?id=${requestId}`;
        }
        modal.classList.remove('hidden');
        setTimeout(() => {
            const content = modal.querySelector('.bg-white');
            if (content) {
                content.classList.remove('scale-95', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');
            }
        }, 10);
    }
}
function closeCancelModal() {
    const modal = document.getElementById('cancelModal');
    if (!modal)
        return;
    const content = modal.querySelector('.bg-white');
    if (content) {
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
    }
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}
function openStatusDetails(requestId, type) {
    const card = document.querySelector(`[data-request-id="${requestId}"][data-type="${type}"]`);
    if (!card)
        return;
    const status = card.getAttribute('data-status');
    const modal = document.getElementById('statusModal');
    const modalTitle = document.getElementById('statusModalTitle');
    const modalBody = document.getElementById('statusModalBody');
    if (!modal || !modalTitle || !modalBody)
        return;
    const statusDescriptions = {
        'Pending': 'Your request is pending approval from our team.',
        'Scheduled': 'Your pickup has been scheduled. Please prepare your recyclables.',
        'In Progress': 'Your request is currently being processed.',
        'Completed': 'This request has been completed successfully.',
        'Cancelled': 'This request has been cancelled.',
        'Verified': 'Your drop-off has been verified and points awarded.',
        'Rejected': 'Your drop-off was rejected. Please contact support for details.',
        'pending': 'Your drop-off is pending verification.',
        'verified': 'Your drop-off has been verified and points awarded.',
        'rejected': 'Your drop-off was rejected. Please contact support for details.'
    };
    modalTitle.textContent = `${type === 'pickup' ? 'Pickup' : 'Drop-off'} Request #${requestId}`;
    modalBody.innerHTML = `
        <div class="text-center py-6">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full ${getStatusColor(status)} mb-4">
                <i data-lucide="${getStatusIcon(status)}" class="w-8 h-8 text-white"></i>
            </div>
            <h4 class="text-lg font-semibold text-bote-stone-900 mb-2">Status: ${status}</h4>
            <p class="text-bote-stone-600">${statusDescriptions[status ?? ''] ?? 'Status details unavailable.'}</p>
        </div>
    `;
    modal.classList.remove('hidden');
    lucideCreateIcons();
    setTimeout(() => {
        const content = modal.querySelector('.bg-white');
        if (content) {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }
    }, 10);
}
function closeStatusModal() {
    const modal = document.getElementById('statusModal');
    if (!modal)
        return;
    const content = modal.querySelector('.bg-white');
    if (content) {
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
    }
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}
function getStatusColor(status) {
    switch (status) {
        case 'Completed':
        case 'Verified':
        case 'verified':
            return 'bg-bote-green-500';
        case 'In Progress':
        case 'Scheduled':
            return 'bg-bote-green-500';
        case 'Pending':
        case 'pending':
            return 'bg-yellow-500';
        case 'Cancelled':
        case 'Rejected':
        case 'rejected':
            return 'bg-red-500';
        default:
            return 'bg-bote-stone-500';
    }
}
function getStatusIcon(status) {
    switch (status) {
        case 'Completed':
        case 'Verified':
        case 'verified':
            return 'check-circle';
        case 'In Progress':
        case 'Scheduled':
            return 'clock';
        case 'Pending':
        case 'pending':
            return 'hourglass';
        case 'Cancelled':
            return 'x-circle';
        case 'Rejected':
        case 'rejected':
            return 'x-octagon';
        default:
            return 'help-circle';
    }
}
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-xl shadow-2xl transform translate-x-full transition-all duration-500 max-w-sm border-l-4 ${type === 'success' ? 'bg-bote-green-50 border-bote-green-500 text-bote-green-800' :
        type === 'error' ? 'bg-red-50 border-red-500 text-red-800' :
            'bg-bote-info-50 border-bote-info-500 text-bote-info-800'}`;
    toast.innerHTML = `
        <div class="flex items-center">
            <i data-lucide="${type === 'success' ? 'check-circle' : type === 'error' ? 'x-circle' : 'info'}" class="w-6 h-6 mr-3"></i>
            <div class="font-medium">${message}</div>
        </div>
    `;
    document.body.appendChild(toast);
    lucideCreateIcons();
    setTimeout(() => toast.style.transform = 'translateX(0)', 100);
    setTimeout(() => {
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 500);
    }, 4000);
}
function showConfirmModal(message, onConfirm) {
    const modal = document.getElementById('confirmModal');
    const confirmBody = document.getElementById('confirmModalBody');
    const confirmBtn = document.getElementById('confirmModalBtn');
    if (!modal || !confirmBody || !confirmBtn)
        return;
    confirmBody.textContent = message;
    confirmBtn.replaceWith(confirmBtn.cloneNode(true));
    const newConfirmBtn = document.getElementById('confirmModalBtn');
    if (newConfirmBtn) {
        newConfirmBtn.addEventListener('click', () => {
            onConfirm();
            closeConfirmModal();
        });
    }
    modal.classList.remove('hidden');
    setTimeout(() => {
        const content = modal.querySelector('.bg-white');
        if (content) {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }
    }, 10);
}
function closeConfirmModal() {
    const modal = document.getElementById('confirmModal');
    if (!modal)
        return;
    const content = modal.querySelector('.bg-white');
    if (content) {
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
    }
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}
window.goTo = goTo;
window.filterHistory = filterHistory;
window.searchHistory = searchHistory;
window.cancelRequest = cancelRequest;
window.closeCancelModal = closeCancelModal;
window.openStatusDetails = openStatusDetails;
window.closeStatusModal = closeStatusModal;
window.closeConfirmModal = closeConfirmModal;
document.addEventListener('DOMContentLoaded', () => {
    lucideCreateIcons();
    const pageData = getPageData('history-data');
    if (pageData) {
        pageData.pickupRequests.forEach((request) => {
            const card = document.querySelector(`[data-request-id="${request.id}"][data-type="pickup"]`);
            if (card)
                card.setAttribute('data-status', request.status);
        });
        pageData.dropoffs.forEach((dropoff) => {
            const card = document.querySelector(`[data-request-id="${dropoff.id}"][data-type="dropoff"]`);
            if (card)
                card.setAttribute('data-status', dropoff.status);
        });
    }
    document.querySelectorAll('.history-card').forEach(card => {
        card.addEventListener('click', () => {
            const c = card;
            const requestId = parseInt(c.getAttribute('data-request-id') ?? '0', 10);
            const type = (c.getAttribute('data-type') ?? 'pickup');
            if (requestId)
                openStatusDetails(requestId, type);
        });
    });
    const searchInput = document.getElementById('historySearch');
    if (searchInput) {
        searchInput.addEventListener('input', searchHistory);
    }
    const cancelModal = document.getElementById('cancelModal');
    if (cancelModal) {
        cancelModal.addEventListener('click', (e) => {
            if (e.target === cancelModal) {
                closeCancelModal();
            }
        });
    }
    const statusModal = document.getElementById('statusModal');
    if (statusModal) {
        statusModal.addEventListener('click', (e) => {
            if (e.target === statusModal) {
                closeStatusModal();
            }
        });
    }
    const confirmModal = document.getElementById('confirmModal');
    if (confirmModal) {
        confirmModal.addEventListener('click', (e) => {
            if (e.target === confirmModal) {
                closeConfirmModal();
            }
        });
    }
    const cancelForm = document.getElementById('cancelForm');
    if (cancelForm) {
        cancelForm.addEventListener('submit', (e) => {
            const checkbox = document.getElementById('confirmCancel');
            if (!checkbox?.checked) {
                e.preventDefault();
                showToast('Please confirm cancellation', 'error');
            }
        });
    }
});
//# sourceMappingURL=resident_history.js.map