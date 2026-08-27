declare global {
    interface Window {
        tailwind?: {
            config?: { theme?: { extend?: { colors?: Record<string, string> } } };
            refresh?: () => void;
        };
        tailwindCSS?: { refresh?: () => void };

        // Legacy/legacy-by-module global hooks for onclick handlers.
        // Each page module still attaches only its own functions.
        [key: string]: unknown;
    }

    // CDN globals
    interface ChartDataset {
        label?: string;
        data?: number[];
        borderColor?: string | string[];
        backgroundColor?: string | string[];
        borderWidth?: number | number[];
        hoverOffset?: number;
        tension?: number;
        fill?: boolean;
    }

    interface ChartConfig {
        type: 'line' | 'bar' | 'doughnut' | 'pie' | 'radar' | 'polarArea';
        data: {
            labels?: string[];
            datasets: ChartDataset[];
        };
        options?: Record<string, unknown>;
    }

    interface ChartInstance {
        destroy(): void;
    }

    interface ChartType {
        data: {
            labels: string[];
            datasets: Array<{
                data: number[];
                backgroundColor: string[];
            }>;
        };
    }

    interface ChartConstructor {
        new (ctx: CanvasRenderingContext2D, config: ChartConfig): ChartInstance;
    }

    const Chart: ChartConstructor;

    type LeafletMap = L.Map;
    type LeafletMarker = L.Marker;

    const L: typeof import('leaflet');
    namespace L {
        type Map = import('leaflet').Map;
        type Marker = import('leaflet').Marker;
        type LeafletMouseEvent = import('leaflet').LeafletMouseEvent;
        type DivIcon = import('leaflet').DivIcon;
        type LatLng = import('leaflet').LatLng;
    }

    type LeafletMap = L.Map;
    type LeafletMarker = L.Marker;

    const feather: {
        replace(options?: { [key: string]: string }): void;
    };

    const lucide: { createIcons(): void };
}

export {};
