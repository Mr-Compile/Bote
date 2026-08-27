export {};

declare global {
    // Legacy DOM migration helper: treat Element as HTML-like for this project
    interface Element {
        style: CSSStyleDeclaration;
        dataset: DOMStringMap;
    }

    interface Window {
        nextSlide?: () => void;
        prevSlide?: () => void;
        goToSlide?: (index: number) => void;
        togglePassword?: (inputId: string, iconId: string) => void;
        tailwind?: {
            config?: {
                theme?: {
                    extend?: {
                        colors?: Record<string, string>;
                    };
                };
            };
            refresh?: () => void;
        };
        tailwindCSS?: {
            refresh?: () => void;
        };
        setupBoteTheme?: () => void;
    }
}
