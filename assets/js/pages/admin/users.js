import { getEl, getPageData, hideParent } from '../../shared.js';
const data = getPageData('admin-users-data');
const currentSort = {
    column: data?.column ?? 'first_name',
    order: data?.order ?? 'asc'
};
function sortTable(column) {
    let newOrder = 'asc';
    if (currentSort.column === column && currentSort.order === 'asc') {
        newOrder = 'desc';
    }
    const url = new URL(window.location.href);
    url.searchParams.set('sort_by', column);
    url.searchParams.set('sort_order', newOrder);
    window.location.href = url.toString();
}
function applyFilters() {
    const roleFilter = getEl('roleFilter').value;
    const url = new URL(window.location.href);
    if (roleFilter) {
        url.searchParams.set('role_filter', roleFilter);
    }
    else {
        url.searchParams.delete('role_filter');
    }
    url.searchParams.set('sort_by', currentSort.column);
    url.searchParams.set('sort_order', currentSort.order);
    window.location.href = url.toString();
}
function updateSortIcons() {
    document.querySelectorAll('.sort-icon').forEach(icon => {
        icon.setAttribute('data-lucide', 'chevrons-up-down');
        icon.className = 'w-3 h-3 sort-icon text-bote-stone-400';
    });
    const activeIcon = document.getElementById('sort-' + currentSort.column);
    if (activeIcon) {
        if (currentSort.order === 'asc') {
            activeIcon.setAttribute('data-lucide', 'chevron-up');
            activeIcon.className = 'w-3 h-3 sort-icon text-bote-info-600';
        }
        else {
            activeIcon.setAttribute('data-lucide', 'chevron-down');
            activeIcon.className = 'w-3 h-3 sort-icon text-bote-info-600';
        }
    }
    lucide.createIcons();
}
function showAddWorkerModal() {
    const modal = getEl('addWorkerModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function hideAddWorkerModal() {
    const modal = getEl('addWorkerModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    const form = modal.querySelector('form');
    if (form) {
        form.reset();
    }
}
function showEditModal(user) {
    getEl('edit_user_id').value = String(user.user_id ?? '');
    getEl('edit_user_name').textContent = `${user.first_name ?? ''} ${user.last_name ?? ''}`;
    getEl('edit_user_email').textContent = String(user.email ?? '');
    getEl('edit_role').value = String(user.role ?? 'resident');
    const modal = getEl('editModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function hideEditModal() {
    const modal = getEl('editModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
function confirmDelete(userId) {
    getEl('delete_user_id').value = String(userId);
    const modal = getEl('deleteModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function hideDeleteModal() {
    const modal = getEl('deleteModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
function togglePassword(inputId, button) {
    const input = getEl(inputId);
    if (input.type === 'password') {
        input.type = 'text';
        const icon = button.querySelector('i');
        if (icon) {
            icon.setAttribute('data-lucide', 'eye-off');
            lucide.createIcons();
        }
    }
    else {
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
    document.querySelectorAll('th .cursor-pointer').forEach(header => {
        header.addEventListener('mouseenter', function () {
            this.classList.add('bg-bote-stone-100');
        });
        header.addEventListener('mouseleave', function () {
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
//# sourceMappingURL=users.js.map