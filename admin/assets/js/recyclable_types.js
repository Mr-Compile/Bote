import { getEl, getInput, getSelect } from './shared.js';
function showAddTypeModal() {
    const modal = getEl('addTypeModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    resetImagePreview('add');
}
function hideAddTypeModal() {
    const modal = getEl('addTypeModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    const form = modal.querySelector('form');
    if (form) {
        form.reset();
    }
    resetImagePreview('add');
}
function showEditTypeModal(type) {
    getInput('edit_type_id').value = String(type.type_id ?? '');
    getInput('edit_name').value = String(type.name ?? '');
    getSelect('edit_unit').value = String(type.unit ?? '');
    const kgField = getEl('edit_points_kg_field');
    const unitField = getEl('edit_points_unit_field');
    const kgInput = getInput('edit_points_per_kg');
    const unitInput = getInput('edit_points_per_unit');
    if (String(type.unit ?? '') === 'kg') {
        kgField.classList.remove('hidden');
        unitField.classList.add('hidden');
        kgInput.value = String(type.points_per_kg ?? '');
        unitInput.value = '';
    }
    else {
        kgField.classList.add('hidden');
        unitField.classList.remove('hidden');
        kgInput.value = '';
        unitInput.value = String(type.points_per_unit ?? '');
    }
    const modal = getEl('editTypeModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function hideEditTypeModal() {
    const modal = getEl('editTypeModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
function confirmDeleteType(typeId) {
    getInput('delete_type_id').value = String(typeId);
    const modal = getEl('deleteTypeModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function hideDeleteTypeModal() {
    const modal = getEl('deleteTypeModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
function previewAddImage(input) {
    const file = input.files?.item(0);
    if (!file)
        return;
    const reader = new FileReader();
    reader.onload = (ev) => {
        if (!ev.target?.result)
            return;
        getEl('add_preview_img').src = String(ev.target.result);
        getEl('add_image_preview').classList.remove('hidden');
        getEl('add_image_placeholder').classList.add('hidden');
    };
    reader.readAsDataURL(file);
}
function previewEditImage(input) {
    const file = input.files?.item(0);
    if (!file)
        return;
    const reader = new FileReader();
    reader.onload = (ev) => {
        if (!ev.target?.result)
            return;
        getEl('edit_preview_img').src = String(ev.target.result);
        getEl('edit_image_preview').classList.remove('hidden');
        getEl('edit_image_placeholder').classList.add('hidden');
    };
    reader.readAsDataURL(file);
}
function resetImagePreview(prefix) {
    getEl(prefix + '_image_preview').classList.add('hidden');
    getEl(prefix + '_image_placeholder').classList.remove('hidden');
    const input = document.getElementById(prefix + '_image');
    if (input) {
        input.value = '';
    }
}
function updateUnitFields(select, kgFieldId, unitFieldId, kgInputId, unitInputId) {
    const kgField = getEl(kgFieldId);
    const unitField = getEl(unitFieldId);
    const kgInput = getInput(kgInputId);
    const unitInput = getInput(unitInputId);
    if (select.value === 'kg') {
        kgField.classList.remove('hidden');
        unitField.classList.add('hidden');
        kgInput.required = true;
        unitInput.required = false;
        unitInput.value = '';
    }
    else {
        kgField.classList.add('hidden');
        unitField.classList.remove('hidden');
        kgInput.required = false;
        unitInput.required = true;
        kgInput.value = '';
    }
}
function setupDragDrop() {
    const dropAreas = document.querySelectorAll('.file-drop-area');
    dropAreas.forEach(dropArea => {
        const preventDefaults = (e) => {
            e.preventDefault();
            e.stopPropagation();
        };
        const highlight = () => {
            dropArea.classList.add('border-bote-green-500', 'bg-bote-green-50');
        };
        const unhighlight = () => {
            dropArea.classList.remove('border-bote-green-500', 'bg-bote-green-50');
        };
        const handleDrop = (e) => {
            const files = e.dataTransfer?.files;
            if (!files || files.length === 0)
                return;
            const input = dropArea.parentElement?.querySelector('input[type="file"]');
            if (input) {
                input.files = files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        };
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, preventDefaults, false);
        });
        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, highlight, false);
        });
        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, unhighlight, false);
        });
        dropArea.addEventListener('drop', handleDrop, false);
    });
}
document.addEventListener('DOMContentLoaded', () => {
    const addUnit = document.getElementById('unit');
    if (addUnit) {
        addUnit.addEventListener('change', () => updateUnitFields(addUnit, 'points_kg_field', 'points_unit_field', 'points_per_kg', 'points_per_unit'));
    }
    const editUnit = document.getElementById('edit_unit');
    if (editUnit) {
        editUnit.addEventListener('change', () => updateUnitFields(editUnit, 'edit_points_kg_field', 'edit_points_unit_field', 'edit_points_per_kg', 'edit_points_per_unit'));
    }
    ['addTypeModal', 'editTypeModal', 'deleteTypeModal'].forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === this) {
                    this.classList.add('hidden');
                    this.classList.remove('flex');
                }
            });
        }
    });
    setupDragDrop();
    lucide.createIcons();
    const alerts = document.querySelectorAll('.alert-auto-hide');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease-in-out';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});
Object.assign(window, {
    showAddTypeModal,
    hideAddTypeModal,
    showEditTypeModal,
    hideEditTypeModal,
    confirmDeleteType,
    hideDeleteTypeModal,
    previewAddImage,
    previewEditImage,
    resetImagePreview
});
//# sourceMappingURL=recyclable_types.js.map