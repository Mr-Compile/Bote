import { selectElement, selectElements } from '../../shared/dom.js';
function showNotificationsComingSoon(event) {
    event.preventDefault();
    alert('Notifications feature coming soon!');
}
document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    const routeItems = selectElements('.border-l-4');
    routeItems.forEach((item) => {
        item.addEventListener('mouseenter', function () {
            this.style.transform = 'translateX(4px)';
            this.style.transition = 'transform 0.2s cubic-bezier(0.4, 0, 0.2, 1)';
            this.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1)';
        });
        item.addEventListener('mouseleave', function () {
            this.style.transform = 'translateX(0)';
            this.style.boxShadow = 'none';
        });
    });
    const hoverCards = selectElements('.hover-lift');
    hoverCards.forEach((card) => {
        card.addEventListener('mouseenter', function () {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)';
        });
        card.addEventListener('mouseleave', function () {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)';
        });
    });
    const pickupCards = selectElements('.relative.bg-white.rounded-xl.hover\\:bg-bote-info-50\\/70');
    pickupCards.forEach((card, index) => {
        card.classList.add('pickup-card');
        const completeBtn = card.querySelector('a[href^="complete_pickup.php"]');
        if (completeBtn) {
            completeBtn.addEventListener('click', () => {
                card.classList.add('bg-bote-green-50', 'border-bote-green-300');
                card.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    card.classList.add('opacity-60');
                }, 100);
            });
        }
        if (index === 0) {
            const badge = card.querySelector('.bg-gradient-to-r.from-yellow-500');
            if (badge) {
                badge.classList.add('next-pickup-badge');
            }
        }
    });
    const impactBar = selectElement('.bg-gradient-to-r.from-bote-green-500.to-bote-green-600.h-3');
    if (impactBar) {
        const finalWidth = impactBar.style.width;
        impactBar.style.width = '0%';
        setTimeout(() => {
            impactBar.style.transition = 'width 1.5s cubic-bezier(0.4, 0, 0.2, 1)';
            impactBar.style.width = finalWidth;
        }, 300);
    }
    const ecoIcons = selectElements('.w-10.h-10.bg-bote-green-100, .w-10.h-10.bg-bote-green-100, .w-10.h-10.bg-orange-100');
    ecoIcons.forEach((icon) => {
        icon.classList.add('eco-icon');
    });
});
window.showNotificationsComingSoon = showNotificationsComingSoon;
//# sourceMappingURL=dashboard.js.map