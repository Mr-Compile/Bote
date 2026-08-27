function toggleFAQ(faqId) {
    const content = document.getElementById(`${faqId}-content`);
    const icon = document.getElementById(`${faqId}-icon`);
    if (!content || !icon)
        return;
    if (content.classList.contains('hidden')) {
        content.classList.remove('hidden');
        icon.style.transform = 'rotate(180deg)';
    }
    else {
        content.classList.add('hidden');
        icon.style.transform = 'rotate(0deg)';
    }
}
window.BoteAppHelp = { toggleFAQ };
export {};
//# sourceMappingURL=help.js.map