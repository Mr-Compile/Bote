function showTab(tabName) {
    document.querySelectorAll('.tab-content').forEach((content) => {
        content.classList.remove('active');
    });
    document.querySelectorAll('[id^="tab-"]').forEach((btn) => {
        btn.classList.remove('tab-active');
        btn.classList.add('text-bote-stone-600', 'hover:text-bote-stone-800', 'hover:bg-bote-stone-200');
    });
    const content = document.getElementById(`content-${tabName}`);
    if (content) {
        content.classList.add('active');
    }
    const activeBtn = document.getElementById(`tab-${tabName}`);
    if (activeBtn) {
        activeBtn.classList.add('tab-active');
        activeBtn.classList.remove('text-bote-stone-600', 'hover:text-bote-stone-800', 'hover:bg-bote-stone-200');
    }
}
function openAddSlideModal() {
    window.location.href = 'carousel_editor.php';
}
function editSlide(slideId) {
    window.location.href = 'carousel_editor.php?id=' + slideId;
}
async function toggleSlide(slideId) {
    if (!confirm('Toggle this slide\'s visibility?'))
        return;
    const formData = new FormData();
    formData.append('action', 'toggle');
    formData.append('slide_id', String(slideId));
    try {
        const response = await fetch('carousel_handler.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            location.reload();
        }
        else {
            alert('Error: ' + String(result.message));
        }
    }
    catch (error) {
        alert('An error occurred: ' + String(error.message));
    }
}
async function deleteSlide(slideId) {
    if (!confirm('Are you sure you want to delete this slide? This action cannot be undone.'))
        return;
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('slide_id', String(slideId));
    try {
        const response = await fetch('carousel_handler.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            location.reload();
        }
        else {
            alert('Error: ' + String(result.message));
        }
    }
    catch (error) {
        alert('An error occurred: ' + String(error.message));
    }
}
document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
});
Object.assign(window, {
    showTab,
    openAddSlideModal,
    editSlide,
    toggleSlide,
    deleteSlide
});
export {};
//# sourceMappingURL=settings.js.map