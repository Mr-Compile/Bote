document.addEventListener('DOMContentLoaded', () => {
    const currentPage = window.location.pathname.split('/').pop() ?? '';

    document.querySelectorAll<HTMLAnchorElement>('nav a[href]').forEach((link) => {
        const href = link.getAttribute('href');
        if (href === currentPage) {
            link.classList.add('sidebar-active');
            const badge = link.querySelector('div');
            if (badge) {
                const classes = badge.className;
                const match = classes.match(/bg-([\w-]+)-100/);
                const color = match ? match[1] : 'bote-green';
                badge.classList.remove('bg-' + color + '-100');
            }
        }
    });
});

export {};
