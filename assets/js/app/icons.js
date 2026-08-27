export function createIcons() {
    if (typeof window.lucide !== 'undefined') {
        window.lucide.createIcons();
    }
}
document.addEventListener('DOMContentLoaded', () => {
    createIcons();
});
//# sourceMappingURL=icons.js.map