import { getEl, getPageData, hideParent } from '../../shared.js';

interface UsersSortData {
    column: string;
    order: string;
}

type UserData = Record<string, unknown>;

const data = getPageData<UsersSortData>('admin-users-data');

const currentSort = {
    column: data?.column ?? 'first_name',
    order: data?.order ?? 'asc'
};

function sortTable(column: string): void {
    let newOrder = 'asc';
    if (currentSort.column === column && currentSort.order === 'asc') {
        newOrder = 'desc';
    }

    const url = new URL(window.location.href);
    url.searchParams.set('sort_by', column);
    url.searchParams.set('sort_order', newOrder);

    window.location.href = url.toString();
}

function applyFilters(): void {
    const roleFilter = getEl<HTMLSelectElement>('roleFilter').value;

    const url = new URL(window.location.href);
    if (roleFilter) {
        url.searchParams.set('role_filter', roleFilter);
    } else {
        url.searchParams.delete('role_filter');
    }

    url.searchParams.set('sort_by', currentSort.column);
    url.searchParams.set('sort_order', currentSort.order);

    window.location.href = url.toString();
}

function updateSortIcons(): void {
    document.querySelectorAll<HTMLElement>('.sort-icon').forEach(icon => {
        icon.setAttribute('data-lucide', 'chevrons-up-down');
        icon.className = 'w-3 h-3 sort-icon text-bote-stone-400';
    });

    const activeIcon = document.getElementById('sort-' + currentSort.column);
    if (activeIcon) {
        if (currentSort.order === 'asc') {
            activeIcon.setAttribute('data-lucide', 'chevron-up');
            activeIcon.className = 'w-3 h-3 sort-icon text-bote-info-600';
        } else {
            activeIcon.setAttribute('data-lucide', 'chevron-down');
            activeIcon.className = 'w-3 h-3 sort-icon text-bote-info-600';
        }
    }

    lucide.createIcons();
}

function showAddWorkerModal(): void {
    const modal = getEl<HTMLElement>('addWorkerModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function hideAddWorkerModal(): void {
    const modal = getEl<HTMLElement>('addWorkerModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');

    const form = modal.querySelector('form') as HTMLFormElement | null;
    if (form) {
        form.reset();
    }
}

function showEditModal(user: UserData): void {
    getEl<HTMLInputElement>('edit_user_id').value = String(user.user_id ?? '');
    getEl<HTMLElement>('edit_user_name').textContent = `${user.first_name ?? ''} ${user.last_name ?? ''}`;
    getEl<HTMLElement>('edit_user_email').textContent = String(user.email ?? '');
    getEl<HTMLSelectElement>('edit_role').value = String(user.role ?? 'resident');

    const modal = getEl<HTMLElement>('editModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function hideEditModal(): void {
    const modal = getEl<HTMLElement>('editModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function confirmDelete(userId: number | string): void {
    getEl<HTMLInputElement>('delete_user_id').value = String(userId);
    const modal = getEl<HTMLElement>('deleteModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function hideDeleteModal(): void {
    const modal = getEl<HTMLElement>('deleteModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function togglePassword(inputId: string, button: HTMLButtonElement): void {
    const input = getEl<HTMLInputElement>(inputId);
    if (input.type === 'password') {
        input.type = 'text';
        const icon = button.querySelector('i');
        if (icon) {
            icon.setAttribute('data-lucide', 'eye-off');
            lucide.createIcons();
        }
    } else {
        input.type = 'password';
        const icon = button.querySelector('i');
        if (icon) {
            icon.setAttribute('data-lucide', 'eye');
            lucide.createIcons();
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    updateSortIcons();

    document.querySelectorAll<HTMLElement>('th .cursor-pointer').forEach(header => {
        header.addEventListener('mouseenter', function (this: HTMLElement) {
            this.classList.add('bg-bote-stone-100');
        });
        header.addEventListener('mouseleave', function (this: HTMLElement) {
            this.classList.remove('bg-bote-stone-100');
        });
    });
});

Object.assign(window, {
    sortTable,
    applyFilters,
    updateSortIcons,
    showAddWorkerModal,
    hideAddWorkerModal,
    showEditModal,
    hideEditModal,
    confirmDelete,
    hideDeleteModal,
    togglePassword,
    hideParent
});

export {};
