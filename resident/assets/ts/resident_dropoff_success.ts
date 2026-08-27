function lucideCreateIcons() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function goTo(url: string) {
    window.location.href = url;
}

function copyCode() {
    const codeElement = document.getElementById('dropoffCode');
    const code = codeElement?.textContent?.trim() ?? '';

    if (navigator.clipboard) {
        navigator.clipboard.writeText(code)
            .then(() => showToast('Code copied to clipboard!', 'success'))
            .catch(() => showToast('Could not copy code', 'error'));
    } else {
        const textArea = document.createElement('textarea');
        textArea.value = code;
        textArea.style.position = 'fixed';
        textArea.style.opacity = '0';
        document.body.appendChild(textArea);
        textArea.select();

        try {
            const successful = document.execCommand('copy');
            if (successful) {
                showToast('Code copied to clipboard!', 'success');
            } else {
                showToast('Could not copy code', 'error');
            }
        } catch (err) {
            showToast('Could not copy code', 'error');
        } finally {
            textArea.remove();
        }
    }
}

function showToast(message: string, type: 'success' | 'error' | 'info' = 'info') {
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-xl shadow-2xl transform translate-x-full transition-all duration-500 max-w-sm border-l-4 ${
        type === 'success' ? 'bg-bote-green-50 border-bote-green-500 text-bote-green-800' :
        type === 'error' ? 'bg-red-50 border-red-500 text-red-800' :
        'bg-bote-info-50 border-bote-info-500 text-bote-info-800'
    }`;
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
    setTimeout(() => removeToast(toast), 3000);
}

function removeToast(toast: HTMLDivElement) {
    toast.style.transform = 'translateX(100%)';
    setTimeout(() => toast.remove(), 500);
}

function shareDropoff() {
    if (navigator.share) {
        const codeElement = document.getElementById('dropoffCode');
        const code = codeElement?.textContent?.trim() ?? '';

        navigator.share({
            title: 'My BoteBank Drop-off',
            text: `I just dropped off recyclables at BoteBank. My tracking code is: ${code}`,
            url: window.location.href
        })
        .then(() => showToast('Shared successfully!', 'success'))
        .catch(() => showToast('Sharing cancelled', 'info'));
    } else {
        showToast('Web Share API not supported', 'info');
    }
}

function viewDropoffDetails() {
    const link = document.getElementById('viewDetailsLink') as HTMLAnchorElement | null;
    if (link) {
        window.location.href = link.href;
    }
}

window.goTo = goTo;
window.copyCode = copyCode;
window.shareDropoff = shareDropoff;
window.viewDropoffDetails = viewDropoffDetails;

document.addEventListener('DOMContentLoaded', () => {
    lucideCreateIcons();

    const confettiContainer = document.getElementById('confettiContainer');
    if (confettiContainer) {
        for (let i = 0; i < 50; i++) {
            const confetti = document.createElement('div');
            confetti.className = 'confetti';
            confetti.style.left = Math.random() * 100 + '%';
            confetti.style.animationDelay = Math.random() * 2 + 's';
            confetti.style.backgroundColor = `hsl(${Math.random() * 360}, 70%, 50%)`;
            confettiContainer.appendChild(confetti);
        }
    }

    const codeElement = document.getElementById('dropoffCode');
    if (codeElement && codeElement.textContent) {
        codeElement.addEventListener('click', copyCode);
    }

    const style = document.createElement('style');
    style.textContent = `
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .float-animation {
            animation: float 3s ease-in-out infinite;
        }

        @keyframes confetti-fall {
            0% { transform: translateY(-100vh) rotate(0deg); }
            100% { transform: translateY(100vh) rotate(720deg); }
        }

        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            top: -10px;
            animation: confetti-fall 3s linear forwards;
            opacity: 0.7;
        }

        @keyframes checkmark-scale {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(1); opacity: 1; }
        }

        .success-check {
            animation: checkmark-scale 0.6s ease-out;
        }

        .tracking-code {
            letter-spacing: 0.2em;
            font-variant-numeric: tabular-nums;
        }
    `;
    document.head.appendChild(style);
});

export {};
