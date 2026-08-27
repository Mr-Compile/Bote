import { selectElement } from './shared.js';

function toggleFAQ(faqId: string): void {
    const content = selectElement<HTMLDivElement>(`${faqId}-content`);
    const icon = selectElement<HTMLElement>(`${faqId}-icon`);

    if (!content || !icon) return;

    if (content.classList.contains('hidden')) {
        content.classList.remove('hidden');
        icon.style.transform = 'rotate(180deg)';
    } else {
        content.classList.add('hidden');
        icon.style.transform = 'rotate(0deg)';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});

window.toggleFAQ = toggleFAQ;

export {};
