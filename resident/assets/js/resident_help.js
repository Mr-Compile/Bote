function lucideCreateIcons() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}
function toggleFAQ(faqId) {
    const content = document.getElementById(faqId + '-content');
    const icon = document.getElementById(faqId + '-icon');
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
function toggleAnswer(element) {
    const answer = element.nextElementSibling;
    const icon = element.querySelector('i');
    if (!answer || !icon)
        return;
    if (answer.classList.contains('hidden')) {
        answer.classList.remove('hidden');
        icon.style.transform = 'rotate(180deg)';
        element.classList.add('bg-bote-green-50');
    }
    else {
        answer.classList.add('hidden');
        icon.style.transform = 'rotate(0deg)';
        element.classList.remove('bg-bote-green-50');
    }
}
function searchFAQ() {
    const searchInput = document.getElementById('faqSearch');
    const searchResults = document.getElementById('searchResults');
    if (!searchInput || !searchResults)
        return;
    const query = searchInput.value.toLowerCase();
    if (query.length > 0) {
        const allFaqs = document.querySelectorAll('.faq-card');
        let count = 0;
        allFaqs.forEach(faq => {
            const text = (faq.textContent ?? '').toLowerCase();
            if (text.includes(query)) {
                faq.classList.remove('hidden');
                count++;
            }
            else {
                faq.classList.add('hidden');
            }
        });
        searchResults.classList.remove('hidden');
        searchResults.innerHTML = `<span class="font-semibold">${count}</span> result${count !== 1 ? 's' : ''} found for "${query}"`;
    }
    else {
        document.querySelectorAll('.faq-card').forEach(faq => {
            faq.classList.remove('hidden');
        });
        searchResults.classList.add('hidden');
    }
}
function contactSupport() {
    const modal = document.getElementById('contactModal');
    if (modal) {
        modal.classList.remove('hidden');
        setTimeout(() => {
            const content = modal.querySelector('.bg-white');
            if (content) {
                content.classList.remove('scale-95', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');
            }
        }, 10);
    }
}
function closeContactModal() {
    const modal = document.getElementById('contactModal');
    if (!modal)
        return;
    const content = modal.querySelector('.bg-white');
    if (content) {
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
    }
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}
function openChatWidget() {
    const widget = document.getElementById('chatWidget');
    if (widget) {
        widget.classList.remove('translate-y-full', 'opacity-0');
    }
}
function closeChatWidget() {
    const widget = document.getElementById('chatWidget');
    if (widget) {
        widget.classList.add('translate-y-full', 'opacity-0');
    }
}
function sendChatMessage(event) {
    if (event) {
        if (event.key && event.key !== 'Enter')
            return;
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
        }
    }
    const input = document.getElementById('chatInput');
    const messages = document.getElementById('chatMessages');
    if (!input || !messages)
        return;
    const text = input.value.trim();
    if (!text)
        return;
    appendMessage(text, 'user');
    input.value = '';
    setTimeout(() => {
        const responses = [
            'Thanks for your message! A member of our team will respond soon.',
            'I understand. Please provide more details so we can help better.',
            'You can also check our FAQs section for quick answers.',
            'Our support hours are Monday to Friday, 8 AM to 5 PM.'
        ];
        const randomResponse = responses[Math.floor(Math.random() * responses.length)];
        appendMessage(randomResponse, 'support');
    }, 1000);
}
function appendMessage(text, type) {
    const messages = document.getElementById('chatMessages');
    if (!messages)
        return;
    const wrapper = document.createElement('div');
    wrapper.className = `flex w-full mb-4 ${type === 'user' ? 'justify-end' : 'justify-start'}`;
    const bubble = document.createElement('div');
    bubble.className = `max-w-[80%] px-4 py-3 rounded-2xl text-sm ${type === 'user'
        ? 'bg-gradient-to-r from-bote-green-600 to-bote-green-600 text-white rounded-br-none'
        : 'bg-white border border-bote-stone-200 text-bote-stone-700 rounded-bl-none shadow-sm'}`;
    bubble.textContent = text;
    wrapper.appendChild(bubble);
    messages.appendChild(wrapper);
    messages.scrollTop = messages.scrollHeight;
}
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-xl shadow-2xl transform translate-x-full transition-all duration-500 max-w-sm border-l-4 ${type === 'success' ? 'bg-bote-green-50 border-bote-green-500 text-bote-green-800' :
        type === 'error' ? 'bg-red-50 border-red-500 text-red-800' :
            'bg-bote-info-50 border-bote-info-500 text-bote-info-800'}`;
    toast.innerHTML = `
        <div class="flex items-center">
            <i data-lucide="${type === 'success' ? 'check-circle' : type === 'error' ? 'x-circle' : 'info'}" class="w-6 h-6 mr-3"></i>
            <div class="font-medium">${message}</div>
            <button type="button" class="ml-4 text-bote-stone-400 hover:text-bote-stone-600 close-toast">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    `;
    document.body.appendChild(toast);
    lucideCreateIcons();
    const closeBtn = toast.querySelector('.close-toast');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => removeToast(toast));
    }
    setTimeout(() => toast.style.transform = 'translateX(0)', 100);
    setTimeout(() => removeToast(toast), 4000);
}
function removeToast(toast) {
    toast.style.transform = 'translateX(100%)';
    setTimeout(() => toast.remove(), 500);
}
function submitContactForm(event) {
    event.preventDefault();
    const nameInput = document.getElementById('contactName');
    const emailInput = document.getElementById('contactEmail');
    const messageInput = document.getElementById('contactMessage');
    const name = nameInput?.value.trim() ?? '';
    const email = emailInput?.value.trim() ?? '';
    const message = messageInput?.value.trim() ?? '';
    if (name && email && message) {
        showToast('Message sent successfully! We will get back to you soon.', 'success');
        const form = document.getElementById('contactForm');
        if (form)
            form.reset();
        closeContactModal();
    }
    else {
        showToast('Please fill in all fields', 'error');
    }
}
function goTo(url) {
    window.location.href = url;
}
window.toggleFAQ = toggleFAQ;
window.toggleAnswer = toggleAnswer;
window.searchFAQ = searchFAQ;
window.contactSupport = contactSupport;
window.closeContactModal = closeContactModal;
window.openChatWidget = openChatWidget;
window.closeChatWidget = closeChatWidget;
window.sendChatMessage = sendChatMessage;
window.submitContactForm = submitContactForm;
window.goTo = goTo;
document.addEventListener('DOMContentLoaded', () => {
    lucideCreateIcons();
    const searchInput = document.getElementById('faqSearch');
    if (searchInput) {
        searchInput.addEventListener('input', searchFAQ);
    }
    const chatInput = document.getElementById('chatInput');
    if (chatInput) {
        chatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                sendChatMessage(e);
            }
        });
    }
    const style = document.createElement('style');
    style.textContent = `
        @keyframes pulse-ring {
            0% { transform: scale(0.8); opacity: 1; }
            100% { transform: scale(1.3); opacity: 0; }
        }

        .pulse-ring {
            animation: pulse-ring 1.5s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
        }

        .faq-card {
            transition: all 0.3s ease;
        }

        .faq-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.12);
        }

        .faq-answer {
            transition: all 0.3s ease;
        }

        .chat-bubble {
            animation: popIn 0.3s ease-out;
        }

        @keyframes popIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
    `;
    document.head.appendChild(style);
});
export {};
//# sourceMappingURL=resident_help.js.map