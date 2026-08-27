function openAddSlideModal(): void {
    window.location.href = 'carousel_editor.php';
}

function editSlide(slideId: number | string): void {
    window.location.href = 'carousel_editor.php?id=' + slideId;
}

async function toggleSlide(slideId: number | string): Promise<void> {
    if (!confirm('Toggle this slide\'s visibility?')) return;

    const formData = new FormData();
    formData.append('action', 'toggle');
    formData.append('slide_id', String(slideId));

    try {
        const response = await fetch('carousel_handler.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json() as Record<string, unknown>;

        if (result.success) {
            location.reload();
        } else {
            alert('Error: ' + String(result.message));
        }
    } catch (error) {
        alert('An error occurred: ' + String((error as Error).message));
    }
}

async function deleteSlide(slideId: number | string): Promise<void> {
    if (!confirm('Are you sure you want to delete this slide? This action cannot be undone.')) return;

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('slide_id', String(slideId));

    try {
        const response = await fetch('carousel_handler.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json() as Record<string, unknown>;

        if (result.success) {
            location.reload();
        } else {
            alert('Error: ' + String(result.message));
        }
    } catch (error) {
        alert('An error occurred: ' + String((error as Error).message));
    }
}

document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
});

Object.assign(window, {
    openAddSlideModal,
    editSlide,
    toggleSlide,
    deleteSlide
});

export {};
