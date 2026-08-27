let selectedMaterial = null;
let selectedPoint = null;
function lucideCreateIcons() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}
function selectCollectionPoint(pointId, card) {
    selectedPoint = pointId;
    document.querySelectorAll('.dropoff-card').forEach(c => {
        c.classList.remove('selected-dropoff');
    });
    card.classList.add('selected-dropoff');
    const radio = document.getElementById('point_' + pointId);
    if (radio)
        radio.checked = true;
    updateSubmitButton();
}
function selectMaterial(typeId, unit, materialName, card) {
    selectedMaterial = { typeId, unit, materialName };
    document.querySelectorAll('.bg-white.border-2').forEach(c => {
        c.classList.remove('selected-dropoff');
    });
    card.classList.add('selected-dropoff');
    const radio = document.getElementById('type_' + typeId);
    if (radio)
        radio.checked = true;
    const weightDiv = document.getElementById('weightInput');
    const quantityDiv = document.getElementById('quantityInput');
    const weightInput = document.getElementById('weight_kg');
    const quantityInput = document.getElementById('quantity_pcs');
    const weightLabel = document.getElementById('weightLabel');
    const quantityLabel = document.getElementById('quantityLabel');
    const weightHint = document.getElementById('weightHint');
    const quantityHint = document.getElementById('quantityHint');
    if (!weightDiv || !quantityDiv || !weightInput || !quantityInput || !weightLabel || !quantityLabel || !weightHint || !quantityHint) {
        return;
    }
    const weightIcon = weightLabel.previousElementSibling?.querySelector('i');
    const quantityIcon = quantityLabel.previousElementSibling?.querySelector('i');
    if (unit === 'kg') {
        weightDiv.classList.remove('hidden');
        quantityDiv.classList.add('hidden');
        weightInput.setAttribute('required', 'required');
        quantityInput.removeAttribute('required');
        weightLabel.textContent = `Weight for ${materialName} (kg)`;
        weightHint.innerHTML = '<i data-lucide="info" class="w-4 h-4 mr-2"></i>Minimum requirement: 10 kilograms';
        if (weightIcon)
            weightIcon.setAttribute('data-lucide', 'scale');
    }
    else if (unit === 'pcs') {
        weightDiv.classList.add('hidden');
        quantityDiv.classList.remove('hidden');
        quantityInput.setAttribute('required', 'required');
        weightInput.removeAttribute('required');
        quantityLabel.textContent = `Quantity for ${materialName} (pieces)`;
        quantityHint.innerHTML = '<i data-lucide="info" class="w-4 h-4 mr-2"></i>Minimum requirement: 30 pieces';
        if (quantityIcon)
            quantityIcon.setAttribute('data-lucide', 'hash');
    }
    lucideCreateIcons();
    updateSubmitButton();
}
function updateSubmitButton() {
    const submitBtn = document.getElementById('submitBtn');
    if (!submitBtn)
        return;
    let isValid = true;
    if (!selectedMaterial || !selectedPoint) {
        isValid = false;
    }
    else {
        if (selectedMaterial.unit === 'kg') {
            const weightInput = document.getElementById('weight_kg');
            const weight = weightInput?.value ? parseFloat(weightInput.value) : NaN;
            if (!weight || weight < 10) {
                isValid = false;
            }
        }
        else if (selectedMaterial.unit === 'pcs') {
            const quantityInput = document.getElementById('quantity_pcs');
            const quantity = quantityInput?.value ? parseInt(quantityInput.value, 10) : NaN;
            if (!quantity || quantity < 30) {
                isValid = false;
            }
        }
    }
    submitBtn.disabled = !isValid;
    submitBtn.classList.toggle('opacity-50', !isValid);
    submitBtn.classList.toggle('cursor-not-allowed', !isValid);
}
function validateForm() {
    if (!selectedMaterial || !selectedPoint) {
        return false;
    }
    if (selectedMaterial.unit === 'kg') {
        const weightInput = document.getElementById('weight_kg');
        const weight = weightInput?.value ? parseFloat(weightInput.value) : NaN;
        if (!weight || weight < 10) {
            if (weightInput) {
                weightInput.classList.add('border-red-500', 'shake-animation');
                weightInput.focus();
                const parent = weightInput.closest('div');
                if (parent) {
                    const existingError = parent.querySelector('.bg-red-100');
                    if (existingError)
                        existingError.remove();
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-4 mt-2 fade-in';
                    errorMsg.innerHTML = '<div class="flex items-center"><i data-lucide="alert-circle" class="w-5 h-5 mr-2"></i><p>Minimum weight requirement is 10 kilograms</p></div>';
                    parent.appendChild(errorMsg);
                    lucideCreateIcons();
                    setTimeout(() => {
                        weightInput.classList.remove('shake-animation');
                    }, 500);
                }
            }
            return false;
        }
        else {
            if (weightInput) {
                const parent = weightInput.closest('div');
                const existingError = parent?.querySelector('.bg-red-100');
                if (existingError)
                    existingError.remove();
                weightInput.classList.remove('border-red-500');
            }
        }
    }
    else if (selectedMaterial.unit === 'pcs') {
        const quantityInput = document.getElementById('quantity_pcs');
        const quantity = quantityInput?.value ? parseInt(quantityInput.value, 10) : NaN;
        if (!quantity || quantity < 30) {
            if (quantityInput) {
                quantityInput.classList.add('border-red-500', 'shake-animation');
                quantityInput.focus();
                const parent = quantityInput.closest('div');
                if (parent) {
                    const existingError = parent.querySelector('.bg-red-100');
                    if (existingError)
                        existingError.remove();
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-4 mt-2 fade-in';
                    errorMsg.innerHTML = '<div class="flex items-center"><i data-lucide="alert-circle" class="w-5 h-5 mr-2"></i><p>Minimum quantity requirement is 30 pieces</p></div>';
                    parent.appendChild(errorMsg);
                    lucideCreateIcons();
                    setTimeout(() => {
                        quantityInput.classList.remove('shake-animation');
                    }, 500);
                }
            }
            return false;
        }
        else {
            if (quantityInput) {
                const parent = quantityInput.closest('div');
                const existingError = parent?.querySelector('.bg-red-100');
                if (existingError)
                    existingError.remove();
                quantityInput.classList.remove('border-red-500');
            }
        }
    }
    return true;
}
window.selectCollectionPoint = selectCollectionPoint;
window.selectMaterial = selectMaterial;
document.addEventListener('DOMContentLoaded', () => {
    lucideCreateIcons();
    updateSubmitButton();
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', (e) => {
            if (!validateForm()) {
                e.preventDefault();
                return;
            }
            const submitBtn = document.getElementById('submitBtn');
            const submitLoader = document.getElementById('submitLoader');
            if (submitBtn)
                submitBtn.disabled = true;
            if (submitLoader)
                submitLoader.classList.remove('hidden');
            if (submitBtn) {
                const text = submitBtn.querySelector('span');
                if (text)
                    text.classList.add('hidden');
            }
        });
    }
    const weightInput = document.getElementById('weight_kg');
    if (weightInput) {
        weightInput.addEventListener('input', function () {
            const input = this;
            const value = parseFloat(input.value);
            if (!Number.isNaN(value) && value < 10) {
                input.classList.add('border-red-500');
                const parent = input.closest('div');
                const hint = parent?.querySelector('#weightHint');
                if (hint) {
                    hint.innerHTML = '<i data-lucide="alert-circle" class="w-4 h-4 mr-2 text-red-500"></i>Minimum requirement: 10 kilograms';
                    lucideCreateIcons();
                }
            }
            else {
                input.classList.remove('border-red-500');
                const parent = input.closest('div');
                const hint = parent?.querySelector('#weightHint');
                if (hint) {
                    hint.innerHTML = '<i data-lucide="info" class="w-4 h-4 mr-2"></i>Minimum requirement: 10 kilograms';
                    lucideCreateIcons();
                }
            }
            updateSubmitButton();
        });
    }
    const quantityInput = document.getElementById('quantity_pcs');
    if (quantityInput) {
        quantityInput.addEventListener('input', function () {
            const input = this;
            const value = parseInt(input.value, 10);
            if (!Number.isNaN(value) && value < 30) {
                input.classList.add('border-red-500');
                const parent = input.closest('div');
                const hint = parent?.querySelector('#quantityHint');
                if (hint) {
                    hint.innerHTML = '<i data-lucide="alert-circle" class="w-4 h-4 mr-2 text-red-500"></i>Minimum requirement: 30 pieces';
                    lucideCreateIcons();
                }
            }
            else {
                input.classList.remove('border-red-500');
                const parent = input.closest('div');
                const hint = parent?.querySelector('#quantityHint');
                if (hint) {
                    hint.innerHTML = '<i data-lucide="info" class="w-4 h-4 mr-2"></i>Minimum requirement: 30 pieces';
                    lucideCreateIcons();
                }
            }
            updateSubmitButton();
        });
    }
});
export {};
//# sourceMappingURL=resident_direct_dropoff.js.map