import { getEl, getInput } from './shared.js';

type UserData = Record<string, unknown>;

function showAddWorkerModal(): void {
    const modal = getEl<HTMLElement>('addWorkerModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    const modalContent = modal.querySelector('.modal-enter') as HTMLElement | null;
    if (modalContent) {
        modalContent.style.animation = 'modalEnter 0.3s ease-out forwards';
    }

    const firstInput = modal.querySelector('input[name="first_name"]') as HTMLInputElement | null;
    if (firstInput) {
        setTimeout(() => firstInput.focus(), 100);
    }
}

function hideAddWorkerModal(): void {
    const modal = getEl<HTMLElement>('addWorkerModal');
    const modalContent = modal.querySelector('.modal-enter') as HTMLElement | null;
    if (modalContent) {
        modalContent.style.animation = 'modalExit 0.3s ease-in forwards';
    }

    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        const form = modal.querySelector('form') as HTMLFormElement | null;
        if (form) {
            form.reset();
        }
        if (modalContent) {
            modalContent.style.animation = '';
        }
    }, 300);
}

function showEditModal(user: UserData): void {
    const modal = getEl<HTMLElement>('editModal');
    getInput('edit_user_id').value = String(user.user_id ?? '');
    getEl<HTMLElement>('edit_user_name').textContent = `${String(user.first_name)} ${String(user.last_name)}`;
    getEl<HTMLElement>('edit_user_email').textContent = String(user.email ?? '');
    getSelect('edit_role').value = String(user.role ?? '');

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    const modalContent = modal.querySelector('.modal-enter') as HTMLElement | null;
    if (modalContent) {
        modalContent.style.animation = 'modalEnter 0.3s ease-out forwards';
    }
}

function getSelect(id: string): HTMLSelectElement {
    const el = document.getElementById(id) as HTMLSelectElement | null;
    if (!el) {
        throw new Error(`Select #${id} not found`);
    }
    return el;
}

function hideEditModal(): void {
    const modal = getEl<HTMLElement>('editModal');
    const modalContent = modal.querySelector('.modal-enter') as HTMLElement | null;
    if (modalContent) {
        modalContent.style.animation = 'modalExit 0.3s ease-in forwards';
    }

    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (modalContent) {
            modalContent.style.animation = '';
        }
    }, 300);
}

function confirmDelete(userId: number | string): void {
    const modal = getEl<HTMLElement>('deleteModal');
    getInput('delete_user_id').value = String(userId);
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    const modalContent = modal.querySelector('.modal-enter') as HTMLElement | null;
    if (modalContent) {
        modalContent.style.animation = 'modalEnter 0.3s ease-out forwards';
    }
}

function hideDeleteModal(): void {
    const modal = getEl<HTMLElement>('deleteModal');
    const modalContent = modal.querySelector('.modal-enter') as HTMLElement | null;
    if (modalContent) {
        modalContent.style.animation = 'modalExit 0.3s ease-in forwards';
    }

    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (modalContent) {
            modalContent.style.animation = '';
        }
    }, 300);
}

function togglePassword(fieldId: string, button: HTMLElement): void {
    const field = getInput(fieldId);
    const icon = button.querySelector('i') as HTMLElement | null;

    if (field.type === 'password') {
        field.type = 'text';
        if (icon) {
            icon.setAttribute('data-lucide', 'eye-off');
        }
    } else {
        field.type = 'password';
        if (icon) {
            icon.setAttribute('data-lucide', 'eye');
        }
    }
    lucide.createIcons();
}

function initializeSearch(): void {
    const searchInput = getInput('userSearch');
    const table = getEl<HTMLTableElement>('usersTable');
    const tbody = table.tBodies[0];
    if (!tbody) return;
    const rows = Array.from(tbody.rows);

    searchInput.addEventListener('input', () => {
        const searchTerm = searchInput.value.toLowerCase();
        let visibleRows = 0;

        rows.forEach(row => {
            if (row.cells.length === 1) return;

            const name = row.cells[0]?.textContent?.toLowerCase() ?? '';
            const email = row.cells[1]?.textContent?.toLowerCase() ?? '';
            const role = row.cells[2]?.textContent?.toLowerCase() ?? '';

            const isVisible = name.includes(searchTerm) || email.includes(searchTerm) || role.includes(searchTerm);
            row.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleRows++;
        });

        updateTableFooter(visibleRows);
    });

    searchInput.addEventListener('keydown', (e: KeyboardEvent) => {
        if (e.key === 'Escape') {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
        }
    });
}

function updateTableFooter(visibleCount: number): void {
    const footer = document.querySelector<HTMLElement>('.bg-bote-stone-50.border-t span');
    if (footer) {
        const totalUsers = document.querySelectorAll<HTMLTableRowElement>('#usersTable tbody tr').length;
        const filterText = visibleCount < totalUsers ? ` (filtered from ${totalUsers})` : '';
        footer.innerHTML = `<i data-lucide="users" class="w-4 h-4 mr-1"></i>Showing ${visibleCount} users${filterText}`;
        lucide.createIcons();
    }
}

function initializeNotifications(): void {
    document.querySelectorAll<HTMLElement>('.notification').forEach(notification => {
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

function initializeFormValidation(): void {
    const form = getEl<HTMLFormElement>('addWorkerForm');
    form.addEventListener('submit', (e: SubmitEvent) => {
        const passwordInput = form.querySelector('input[name="password"]') as HTMLInputElement | null;
        const emailInput = form.querySelector('input[name="email"]') as HTMLInputElement | null;

        if (!emailInput || !passwordInput) return;

        const email = emailInput.value;
        const password = passwordInput.value;

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            e.preventDefault();
            showNotification('Please enter a valid email address.', 'error');
            return;
        }

        if (password.length < 6) {
            e.preventDefault();
            showNotification('Password must be at least 6 characters long.', 'error');
        }
    });
}

function showNotification(message: string, type: 'success' | 'error' | 'warning' | 'info' = 'info'): void {
    const colors: Record<string, string> = {
        success: 'from-bote-green-400 to-bote-green-500',
        error: 'from-red-400 to-red-500',
        warning: 'from-yellow-400 to-yellow-500',
        info: 'from-bote-info-400 to-bote-info-500'
    };

    const icons: Record<string, string> = {
        success: 'check-circle',
        error: 'x-circle',
        warning: 'alert-triangle',
        info: 'info'
    };

    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 bg-gradient-to-r ${colors[type]} text-white px-6 py-4 rounded-xl shadow-lg z-50 notification flex items-center max-w-md`;
    notification.innerHTML = `
        <i data-lucide="${icons[type]}" class="w-5 h-5 mr-3"></i>
        <span class="font-medium">${message}</span>
        <button type="button" class="ml-auto" data-dismiss="notification">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    `;

    const closeBtn = notification.querySelector<HTMLButtonElement>('[data-dismiss="notification"]');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            notification.remove();
        });
    }

    document.body.appendChild(notification);
    lucide.createIcons();

    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.animation = 'slideOutRight 0.5s ease-in forwards';
            setTimeout(() => {
                notification.remove();
            }, 500);
        }
    }, 5000);
}

document.addEventListener('DOMContentLoaded', () => {
    ['addWorkerModal', 'editModal', 'deleteModal'].forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.addEventListener('click', function (this: HTMLElement, e: MouseEvent) {
                if (e.target === this) {
                    switch (modalId) {
                        case 'addWorkerModal': hideAddWorkerModal(); break;
                        case 'editModal': hideEditModal(); break;
                        case 'deleteModal': hideDeleteModal(); break;
                    }
                }
            });
        }
    });

    document.addEventListener('keydown', (e: KeyboardEvent) => {
        if (e.key === 'Escape') {
            const modals = ['addWorkerModal', 'editModal', 'deleteModal'] as const;
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (modal && !modal.classList.contains('hidden')) {
                    switch (modalId) {
                        case 'addWorkerModal': hideAddWorkerModal(); break;
                        case 'editModal': hideEditModal(); break;
                        case 'deleteModal': hideDeleteModal(); break;
                    }
                }
            });
        }

        if (e.ctrlKey && e.key === 'n') {
            e.preventDefault();
            showAddWorkerModal();
        }
    });

    initializeSearch();
    initializeNotifications();
    initializeFormValidation();
    lucide.createIcons();
});

document.addEventListener('keydown', (e: KeyboardEvent) => {
    if (e.key !== 'Tab') return;

    const activeModal = document.querySelector<HTMLElement>('.fixed:not(.hidden)');
    if (!activeModal) return;

    const focusableElements = Array.from(activeModal.querySelectorAll<HTMLElement>('input, button, select, textarea'));
    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];

    if (!firstElement || !lastElement) return;

    if (e.shiftKey && document.activeElement === firstElement) {
        e.preventDefault();
        lastElement.focus();
    } else if (!e.shiftKey && document.activeElement === lastElement) {
        e.preventDefault();
        firstElement.focus();
    }
});

Object.assign(window, {
    showAddWorkerModal,
    hideAddWorkerModal,
    showEditModal,
    hideEditModal,
    confirmDelete,
    hideDeleteModal,
    togglePassword
});

export {};
