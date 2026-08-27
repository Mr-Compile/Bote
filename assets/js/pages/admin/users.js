import { getEl, getPageData } from '../../shared.js';
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
    updateSortIcons
});
//# sourceMappingURL=users.js.map