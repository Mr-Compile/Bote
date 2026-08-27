import { getEl } from './shared.js';
function showTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.querySelectorAll('[id^="tab-"]').forEach(tab => {
        tab.classList.remove('tab-active');
        tab.classList.add('text-bote-stone-600', 'hover:text-bote-stone-800', 'hover:bg-bote-stone-200');
    });
    const content = document.getElementById('content-' + tabName);
    if (content) {
        content.classList.add('active');
    }
    const activeTab = document.getElementById('tab-' + tabName);
    if (activeTab) {
        activeTab.classList.add('tab-active');
        activeTab.classList.remove('text-bote-stone-600', 'hover:text-bote-stone-800', 'hover:bg-bote-stone-200');
    }
}
function previewImage(input, previewId) {
    const preview = getEl(previewId);
    const file = input.files?.item(0);
    if (!file) {
        preview.innerHTML = '';
        return;
    }
    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!validTypes.includes(file.type)) {
        alert('Please select a valid image file (JPG, PNG, GIF)');
        input.value = '';
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        alert('File size must be less than 5MB');
        input.value = '';
        return;
    }
    const reader = new FileReader();
    reader.onload = (e) => {
        if (!e.target?.result)
            return;
        const isLogo = previewId.includes('logo');
        const imgClass = isLogo ? 'mx-auto h-24 w-24 object-cover rounded-lg shadow-md' : 'mx-auto h-20 w-40 object-cover rounded-lg shadow-md';
        preview.innerHTML = `
            <img src="${String(e.target.result)}" alt="Preview" class="${imgClass}">
            <p class="text-sm text-bote-stone-600 mt-2">New ${isLogo ? 'Logo' : 'Banner'} Preview</p>
        `;
    };
    reader.readAsDataURL(file);
}
function showNotification(message, type = 'info', duration = 3000) {
    const colors = {
        success: 'bg-bote-green-500',
        error: 'bg-red-500',
        warning: 'bg-yellow-500',
        info: 'bg-bote-info-500'
    };
    const icons = {
        success: 'check-circle',
        error: 'x-circle',
        warning: 'alert-triangle',
        info: 'info'
    };
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-xl shadow-lg z-50 max-w-md transform translate-x-full transition-transform duration-300`;
    notification.innerHTML = `<i data-lucide="${icons[type]}" class="w-4 h-4 inline mr-2"></i>${message}`;
    document.body.appendChild(notification);
    lucide.createIcons();
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => notification.remove(), 300);
    }, duration);
}
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.alert-auto-hide').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease-out';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
    const passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', (e) => {
            const newPasswordInput = passwordForm.querySelector('input[name="new_password"]');
            const confirmPasswordInput = passwordForm.querySelector('input[name="confirm_password"]');
            const currentPasswordInput = passwordForm.querySelector('input[name="current_password"]');
            if (!newPasswordInput || !confirmPasswordInput || !currentPasswordInput)
                return;
            const newPassword = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            const currentPassword = currentPasswordInput.value;
            if (!currentPassword) {
                e.preventDefault();
                showNotification('Please enter your current password', 'error');
                return;
            }
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                showNotification('New password and confirmation password do not match!', 'error');
                return;
            }
            if (newPassword.length < 8) {
                e.preventDefault();
                showNotification('Password must be at least 8 characters long!', 'error');
                return;
            }
            const strongPassword = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/;
            if (!strongPassword.test(newPassword)) {
                const proceed = confirm('Your password is weak. For better security, use a mix of uppercase, lowercase, numbers, and special characters. Continue anyway?');
                if (!proceed) {
                    e.preventDefault();
                }
            }
        });
    }
    document.querySelectorAll('input[required], textarea[required]').forEach(input => {
        input.addEventListener('blur', () => {
            if (input.value.trim() === '') {
                input.classList.add('border-red-500');
                input.classList.remove('border-bote-stone-300');
            }
            else {
                input.classList.remove('border-red-500');
                input.classList.add('border-bote-stone-300');
            }
        });
    });
    document.querySelectorAll('input[type="email"]').forEach(input => {
        input.addEventListener('blur', () => {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (input.value && !emailRegex.test(input.value)) {
                input.classList.add('border-red-500');
                showNotification('Please enter a valid email address', 'warning');
            }
            else {
                input.classList.remove('border-red-500');
            }
        });
    });
    document.querySelectorAll('.file-drop-area').forEach(area => {
        const preventDefaults = (e) => {
            e.preventDefault();
            e.stopPropagation();
        };
        const highlight = () => {
            area.classList.add('border-bote-info-500', 'bg-bote-info-50');
        };
        const unhighlight = () => {
            area.classList.remove('border-bote-info-500', 'bg-bote-info-50');
        };
        const handleDrop = (e) => {
            const dt = e.dataTransfer;
            if (!dt)
                return;
            const files = dt.files;
            const input = area.querySelector('input[type="file"]');
            if (input && files.length > 0) {
                input.files = files;
                const previewId = input.id === 'logoInput' ? 'logo-preview' : 'banner-preview';
                previewImage(input, previewId);
            }
        };
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            area.addEventListener(eventName, preventDefaults, false);
        });
        ['dragenter', 'dragover'].forEach(eventName => {
            area.addEventListener(eventName, highlight, false);
        });
        ['dragleave', 'drop'].forEach(eventName => {
            area.addEventListener(eventName, unhighlight, false);
        });
        area.addEventListener('drop', handleDrop, false);
    });
});
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('success') === '1') {
    showNotification('Settings saved successfully!', 'success');
}
const error = urlParams.get('error');
if (error) {
    showNotification(decodeURIComponent(error), 'error', 5000);
}
let autoSaveTimeout;
document.querySelectorAll('input[type="number"][name^="points_"]').forEach(field => {
    field.addEventListener('input', () => {
        if (autoSaveTimeout) {
            clearTimeout(autoSaveTimeout);
        }
        field.style.borderColor = '#fbbf24';
        autoSaveTimeout = setTimeout(() => {
            field.style.borderColor = '#10b981';
        }, 2000);
    });
});
document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        const activeTab = document.querySelector('.tab-content.active');
        const form = activeTab?.querySelector('form');
        if (form) {
            showNotification('Saving settings...', 'info', 1000);
            form.submit();
        }
    }
    if (e.key === 'Escape') {
        document.querySelectorAll('.fixed.top-4.right-4').forEach(notification => {
            notification.remove();
        });
    }
});
setTimeout(() => {
    document.querySelectorAll('[onclick^="showTab"]').forEach(button => {
        button.addEventListener('click', () => {
            const main = document.querySelector('main');
            if (main) {
                main.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    });
}, 100);
document.addEventListener('DOMContentLoaded', () => {
    const latInput = document.getElementById('barangay_latitude');
    const lngInput = document.getElementById('barangay_longitude');
    const mapContainer = document.getElementById('barangay-map');
    if (!latInput || !lngInput || !mapContainer)
        return;
    const lat = parseFloat(latInput.value) || 6.4216;
    const lng = parseFloat(lngInput.value) || 124.7809;
    const map = L.map('barangay-map').setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    const marker = L.marker([lat, lng], { draggable: true }).addTo(map);
    marker.on('dragend', () => {
        const pos = marker.getLatLng();
        latInput.value = pos.lat.toFixed(6);
        lngInput.value = pos.lng.toFixed(6);
    });
});
lucide.createIcons();
Object.assign(window, {
    showTab,
    previewImage
});
//# sourceMappingURL=settings.js.map