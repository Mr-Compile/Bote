import { selectElement } from './shared.js';

const OPEN_EYE_ICON = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>`;

const CLOSED_EYE_ICON = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>`;

const LOADING_SPINNER = `<svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span>Creating Account...</span>`;

function togglePassword(inputId: string, iconId: string): void {
    const input = selectElement<HTMLInputElement>(inputId);
    const icon = document.getElementById(iconId) as SVGSVGElement | null;
    if (!input || !icon) return;

    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = OPEN_EYE_ICON;
    } else {
        input.type = 'password';
        icon.innerHTML = CLOSED_EYE_ICON;
    }
}

function initPasswordStrength(): void {
    const passwordInput = selectElement<HTMLInputElement>('password');
    const strengthMeter = document.querySelector<HTMLDivElement>('.strength-meter');
    const strengthText = document.querySelector<HTMLParagraphElement>('#passwordStrength p');
    if (!passwordInput || !strengthMeter || !strengthText) return;

    const updateStrength = (): void => {
        const password = passwordInput.value;
        let strength = 0;
        let strengthLabel = '';
        let color = '';

        if (password.length >= 8) strength++;
        if (password.match(/[a-z]/)) strength++;
        if (password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;

        switch (strength) {
            case 0:
            case 1:
                strengthLabel = 'Weak password';
                color = 'bg-red-500';
                strengthMeter.style.width = '25%';
                break;
            case 2:
            case 3:
                strengthLabel = 'Medium password';
                color = 'bg-yellow-500';
                strengthMeter.style.width = '60%';
                break;
            case 4:
            case 5:
                strengthLabel = 'Strong password';
                color = 'bg-bote-green-500';
                strengthMeter.style.width = '100%';
                break;
        }

        strengthMeter.className = `strength-meter ${color}`;
        strengthText.textContent = strengthLabel;
        strengthText.className = `text-xs mt-1 ${strength <= 1 ? 'text-red-600' : strength <= 3 ? 'text-yellow-600' : 'text-bote-green-600'}`;
    };

    passwordInput.addEventListener('input', updateStrength);
}

function initPasswordMatch(): void {
    const passwordInput = selectElement<HTMLInputElement>('password');
    const confirmPasswordInput = selectElement<HTMLInputElement>('confirm_password');
    const passwordMatchText = selectElement<HTMLParagraphElement>('passwordMatch');
    if (!passwordInput || !confirmPasswordInput || !passwordMatchText) return;

    const MATCH_ICON = `<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>`;

    const MISMATCH_ICON = `<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>`;

    function checkPasswordMatch(): void {
        if (!passwordInput || !confirmPasswordInput || !passwordMatchText) return;

        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        if (confirmPassword.length === 0) {
            passwordMatchText.textContent = '';
            confirmPasswordInput.classList.remove('border-red-500', 'border-bote-green-500');
            return;
        }

        if (password === confirmPassword) {
            passwordMatchText.innerHTML = `<span class="flex items-center gap-1 text-bote-green-600">${MATCH_ICON} Passwords match!</span>`;
            confirmPasswordInput.classList.remove('border-red-500');
            confirmPasswordInput.classList.add('border-bote-green-500');
        } else {
            passwordMatchText.innerHTML = `<span class="flex items-center gap-1 text-red-600">${MISMATCH_ICON} Passwords do not match</span>`;
            confirmPasswordInput.classList.remove('border-bote-green-500');
            confirmPasswordInput.classList.add('border-red-500');
        }
    }

    passwordInput.addEventListener('input', checkPasswordMatch);
    confirmPasswordInput.addEventListener('input', checkPasswordMatch);
}

function initPhoneValidation(): void {
    const phoneInput = selectElement<HTMLInputElement>('phoneInput');
    if (!phoneInput) return;

    phoneInput.addEventListener('input', () => {
        phoneInput.value = phoneInput.value.replace(/\D/g, '');

        if (phoneInput.value.length > 0 && phoneInput.value[0] !== '9') {
            phoneInput.value = '9' + phoneInput.value.slice(1);
        }

        if (phoneInput.value.length > 10) {
            phoneInput.value = phoneInput.value.slice(0, 10);
        }

        if (phoneInput.value.length === 10 && phoneInput.value[0] === '9') {
            phoneInput.classList.remove('border-red-500');
            phoneInput.classList.add('border-bote-green-500');
        } else if (phoneInput.value.length > 0) {
            phoneInput.classList.remove('border-bote-green-500');
            phoneInput.classList.add('border-red-500');
        } else {
            phoneInput.classList.remove('border-red-500', 'border-bote-green-500');
        }
    });
}

function initFormValidation(): void {
    const form = selectElement<HTMLFormElement>('registrationForm');
    const submitBtn = selectElement<HTMLButtonElement>('submitBtn');
    const phoneInput = selectElement<HTMLInputElement>('phoneInput');
    const passwordInput = selectElement<HTMLInputElement>('password');
    const confirmPasswordInput = selectElement<HTMLInputElement>('confirm_password');
    if (!form || !submitBtn || !phoneInput || !passwordInput || !confirmPasswordInput) return;

    form.addEventListener('submit', (event: SubmitEvent) => {
        const phone = phoneInput.value;
        if (phone.length !== 10 || phone[0] !== '9') {
            event.preventDefault();
            alert('Please enter a valid 10-digit phone number starting with 9');
            phoneInput.focus();
            return;
        }

        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        if (password !== confirmPassword) {
            event.preventDefault();
            alert('Passwords do not match!');
            confirmPasswordInput.focus();
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = LOADING_SPINNER;
    });
}

function initEmailValidation(): void {
    const emailInput = document.querySelector<HTMLInputElement>('input[name="email"]');
    if (!emailInput) return;

    emailInput.addEventListener('blur', () => {
        const email = emailInput.value;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (email && !emailRegex.test(email)) {
            emailInput.classList.add('border-red-500');
        } else if (email) {
            emailInput.classList.remove('border-red-500');
            emailInput.classList.add('border-bote-green-500');
        }
    });
}

function initPage(): void {
    initPasswordStrength();
    initPasswordMatch();
    initPhoneValidation();
    initFormValidation();
    initEmailValidation();
    window.togglePassword = togglePassword;
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPage);
} else {
    initPage();
}

export {};
