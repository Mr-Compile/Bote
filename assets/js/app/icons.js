export function createIcons() {
    if (typeof window.lucide !== 'undefined') {
        window.lucide.createIcons();
    }
}
document.addEventListener('DOMContentLoaded', () => {
    createIcons();
});
window.BoteApp = Object.assign(window.BoteApp ?? {}, {
    refreshIcons: createIcons,
});
//# sourceMappingURL=icons.js.map