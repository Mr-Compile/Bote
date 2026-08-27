import { getEl } from '../../shared.js';

document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();

    const form = getEl<HTMLFormElement>('carouselForm');
    form.addEventListener('submit', async (e: SubmitEvent) => {
        e.preventDefault();

        const formData = new FormData(form);

        try {
            const response = await fetch('carousel_handler.php', {
                method: 'POST',
                body: formData
            });

            const responseText = await response.text();

            let result: Record<string, unknown>;
            try {
                result = JSON.parse(responseText) as Record<string, unknown>;
            } catch (jsonError) {
                console.error('JSON parse error:', jsonError);
                console.error('Response text:', responseText);
                alert('Error parsing server response. Check browser console for details.');
                return;
            }

            if (result.success) {
                alert(String(result.message));
                window.location.href = 'admin_settings.php';
            } else {
                alert('Error: ' + String(result.message));
            }
        } catch (error) {
            console.error('Fetch error:', error);
            alert('An error occurred: ' + String((error as Error).message));
        }
    });
});

export {};
