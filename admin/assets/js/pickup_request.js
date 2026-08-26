
// Initialize Lucide icons when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});

function showAssignWorkerModal(request) {
    console.log('Opening assign worker modal for request:', request);
    const modal = document.getElementById('assignWorkerModal');
    if (modal) {
        document.getElementById('assign_request_id').value = request.request_id;
        document.getElementById('assign_request_info').textContent = 
            `#${request.request_id} - ${request.resident_name} - ${request.type_name}`;
        modal.classList.remove('hidden', 'modal-hidden');
        modal.classList.add('flex', 'modal-visible');
    } else {
        console.error('Assign worker modal not found');
    }
}

function hideAssignWorkerModal() {
    const modal = document.getElementById('assignWorkerModal');
    if (modal) {
        modal.classList.add('hidden', 'modal-hidden');
        modal.classList.remove('flex', 'modal-visible');
    }
}

function showCompleteModal(request) {
    console.log('Opening complete modal for request:', request);
    const modal = document.getElementById('completeModal');
    if (modal) {
        document.getElementById('complete_request_id').value = request.request_id;
        document.getElementById('complete_request_info').textContent = 
            `#${request.request_id} - ${request.resident_name} - ${request.type_name} (Est: ${request.weight_kg}kg)`;
        modal.classList.remove('hidden', 'modal-hidden');
        modal.classList.add('flex', 'modal-visible');
    } else {
        console.error('Complete modal not found');
    }
}

function hideCompleteModal() {
    const modal = document.getElementById('completeModal');
    if (modal) {
        modal.classList.add('hidden', 'modal-hidden');
        modal.classList.remove('flex', 'modal-visible');
    }
}

function showStatusModal(request) {
    console.log('Opening status modal for request:', request);
    const modal = document.getElementById('statusModal');
    if (modal) {
        document.getElementById('status_request_id').value = request.request_id;
        document.getElementById('status_request_info').textContent = 
            `#${request.request_id} - ${request.resident_name} - ${request.type_name}`;
        document.getElementById('status_select').value = request.status;
        modal.classList.remove('hidden', 'modal-hidden');
        modal.classList.add('flex', 'modal-visible');
    } else {
        console.error('Status modal not found');
    }
}

function hideStatusModal() {
    const modal = document.getElementById('statusModal');
    if (modal) {
        modal.classList.add('hidden', 'modal-hidden');
        modal.classList.remove('flex', 'modal-visible');
    }
}

// Close modals when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    ['assignWorkerModal', 'completeModal', 'statusModal'].forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.add('hidden', 'modal-hidden');
                    this.classList.remove('flex', 'modal-visible');
                }
            });
        }
    });
});
