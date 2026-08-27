export function setupBoteTheme(): void {
    const colors: Record<string, string> = {
        'bote-stone-50': '#fafaf9',
        'bote-stone-100': '#f5f5f4',
        'bote-stone-200': '#e7e5e4',
        'bote-stone-300': '#d7d3d0',
        'bote-stone-400': '#a89f99',
        'bote-stone-500': '#797169',
        'bote-stone-600': '#5b5450',
        'bote-stone-700': '#44403c',
        'bote-stone-800': '#2a2724',
        'bote-stone-900': '#1c1917',
        'bote-stone-950': '#0c0a09',
        'bote-green-50': '#f4fbf2',
        'bote-green-100': '#e3f5df',
        'bote-green-200': '#c5e8bf',
        'bote-green-300': '#9ed696',
        'bote-green-400': '#72b968',
        'bote-green-500': '#4f9f45',
        'bote-green-600': '#3d7f35',
        'bote-green-700': '#31652b',
        'bote-green-800': '#275124',
        'bote-green-900': '#214220',
        'bote-green-950': '#0f2210',
        'bote-success': '#3d7f35',
        'bote-warning': '#ca8a04',
        'bote-danger': '#dc2626',
        'bote-info-50': '#f0fdfa',
        'bote-info-100': '#ccfbf1',
        'bote-info-200': '#99f6e4',
        'bote-info-300': '#5eead4',
        'bote-info-400': '#2dd4bf',
        'bote-info-500': '#14b8a6',
        'bote-info-600': '#0d9488',
        'bote-info-700': '#0f766e',
        'bote-info-800': '#115e59',
        'bote-info-900': '#134e4a',
        'bote-info-950': '#042f2e',
        'bote-info': '#0d9488',
    };

    const existing = window.tailwind;
    const config = {
        theme: {
            extend: {
                colors,
            },
        },
    };

    if (existing) {
        existing.config = config;
    } else {
        window.tailwind = { config };
    }

    window.tailwindCSS?.refresh?.();
    window.tailwind?.refresh?.();
}

setupBoteTheme();
