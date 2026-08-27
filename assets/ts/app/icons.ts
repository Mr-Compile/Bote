export {};

declare global {
    interface Window {
        lucide: {
            createIcons: () => void;
        };
    }
}

export function createIcons(): void {
    if (typeof window.lucide !== 'undefined') {
        window.lucide.createIcons();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    createIcons();
});
