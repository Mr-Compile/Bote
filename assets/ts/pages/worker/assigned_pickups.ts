import { selectElement, selectElements } from '../../shared.js';

function sortPickups(section: string, order: string): void {
    const containerID = section === 'available' ? 'availablePickupsGrid' : 'assignedPickupsGrid';
    const container = selectElement<HTMLElement>(containerID);

    if (!container) return;

    const cards = Array.from(container.querySelectorAll<HTMLElement>('.pickup-card'));

    if (cards.length === 0) return;

    cards.sort((a, b) => {
        const dateA = parseInt(a.dataset.date ?? '0', 10);
        const dateB = parseInt(b.dataset.date ?? '0', 10);

        if (order === 'asc') {
            return dateA - dateB;
        }
        return dateB - dateA;
    });

    container.style.opacity = '0';
    container.style.transition = 'opacity 0.3s ease';

    container.innerHTML = '';
    cards.forEach((card) => container.appendChild(card));

    setTimeout(() => {
        container.style.opacity = '1';
    }, 50);

    updateSortButtons(section, order);
}

function updateSortButtons(section: string, activeOrder: string): void {
    const ascBtn = selectElement<HTMLButtonElement>(section + 'Asc');
    const descBtn = selectElement<HTMLButtonElement>(section + 'Desc');

    if (!ascBtn || !descBtn) return;

    ascBtn.classList.remove('active');
    descBtn.classList.remove('active');

    if (activeOrder === 'asc') {
        ascBtn.classList.add('active');
    } else {
        descBtn.classList.add('active');
    }
}

function animateCards(): void {
    const cards = selectElements<HTMLElement>('.pickup-card');

    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(10px)';

        setTimeout(() => {
            card.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 50 * (index % 10));

        card.addEventListener('mouseenter', function () {
            this.style.transform = 'translateY(-5px) scale(1.01)';
            this.style.boxShadow = 'var(--shadow-lg)';
            this.style.zIndex = '1';
            this.style.borderColor = 'var(--primary-light)';
        });

        card.addEventListener('mouseleave', function () {
            this.style.transform = 'translateY(0) scale(1)';
            this.style.boxShadow = 'var(--shadow-sm)';
            this.style.zIndex = '0';
            this.style.borderColor = '#f1f5f9';
        });
    });
}

function openCompleteModal(requestId: number, unit: string, residentName: string): void {
    const modal = selectElement<HTMLDivElement>('completeModal');
    const requestIdInput = selectElement<HTMLInputElement>('modalRequestId');
    const residentNameElement = selectElement<HTMLElement>('modalResidentName');
    const weightInput = selectElement<HTMLDivElement>('weightInput');
    const quantityInput = selectElement<HTMLDivElement>('quantityInput');
    const actualWeight = selectElement<HTMLInputElement>('actual_weight');
    const actualQuantity = selectElement<HTMLInputElement>('actual_quantity');

    if (!modal || !requestIdInput || !residentNameElement || !weightInput || !quantityInput || !actualWeight || !actualQuantity) {
        return;
    }

    requestIdInput.value = String(requestId);
    residentNameElement.textContent = residentName;

    if (unit === 'kg') {
        weightInput.style.display = 'block';
        quantityInput.style.display = 'none';
        actualWeight.required = true;
        actualQuantity.required = false;
        setTimeout(() => actualWeight.focus(), 400);
    } else {
        weightInput.style.display = 'none';
        quantityInput.style.display = 'block';
        actualWeight.required = false;
        actualQuantity.required = true;
        setTimeout(() => actualQuantity.focus(), 400);
    }

    modal.style.display = 'flex';

    const modalContainer = modal.querySelector<HTMLElement>('.modal-container');
    if (modalContainer) {
        modalContainer.style.opacity = '0';
        modalContainer.style.transform = 'translateY(20px)';

        setTimeout(() => {
            modalContainer.style.transition = 'all 0.3s cubic-bezier(0.16, 1, 0.3, 1)';
            modalContainer.style.opacity = '1';
            modalContainer.style.transform = 'translateY(0)';
        }, 10);
    }

    if (typeof feather !== 'undefined') {
        feather.replace();
    }
}

function closeCompleteModal(): void {
    const modal = selectElement<HTMLDivElement>('completeModal');
    if (!modal) return;

    const modalContainer = modal.querySelector<HTMLElement>('.modal-container');
    if (modalContainer) {
        modalContainer.style.opacity = '0';
        modalContainer.style.transform = 'translateY(20px)';
    }

    setTimeout(() => {
        modal.style.display = 'none';
        if (modalContainer) {
            modalContainer.style.opacity = '1';
            modalContainer.style.transform = 'translateY(0)';
        }

        const completeForm = selectElement<HTMLFormElement>('completeForm');
        if (completeForm) {
            completeForm.reset();
        }
    }, 300);
}

function scrollToAvailableSection(): void {
    const section = document.querySelector<HTMLElement>('.available-section');
    if (section) {
        section.scrollIntoView({ behavior: 'smooth' });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    sortPickups('available', 'asc');
    sortPickups('assigned', 'asc');
    animateCards();

    const completeModal = selectElement<HTMLDivElement>('completeModal');
    if (completeModal) {
        completeModal.addEventListener('click', (e) => {
            if (e.target === completeModal) {
                closeCompleteModal();
            }
        });
    }

    const completeForm = selectElement<HTMLFormElement>('completeForm');
    if (completeForm) {
        completeForm.addEventListener('submit', () => {
            const weightInput = selectElement<HTMLInputElement>('actual_weight');
            const quantityInput = selectElement<HTMLInputElement>('actual_quantity');

            if (!weightInput || !quantityInput) return;

            if (weightInput.required && !weightInput.value) return;
            if (quantityInput.required && !quantityInput.value) return;

            if (weightInput.style.display !== 'none' && weightInput.value) {
                // weight path
            } else if (quantityInput.style.display !== 'none' && quantityInput.value) {
                // quantity path
            }
        });
    }
});

window.sortPickups = sortPickups;
window.openCompleteModal = openCompleteModal;
window.closeCompleteModal = closeCompleteModal;
window.scrollToAvailableSection = scrollToAvailableSection;

export {};
