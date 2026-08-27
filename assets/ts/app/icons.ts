export {};

declare global {
    interface Window {
        lucide: {
            createIcons: () => void;
        };
        BoteApp?: {
            refreshIcons?: () => void;
            [key: string]: unknown;
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

window.BoteApp = Object.assign(window.BoteApp ?? {}, {
    refreshIcons: createIcons,
});
