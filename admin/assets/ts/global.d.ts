declare global {
    interface Window {
        pickupOverview?: {
            pending: number;
            scheduled: number;
            completed_today: number;
        };
        analyticsData?: {
            labels: string[];
            points_earned: number[];
            points_redeemed: number[];
        };

        // Allow TypeScript modules to attach functions to window for legacy onclick handlers.
        // Functions are still strongly typed where they are defined.
        [key: string]: unknown;
    }

    // CDN globals
    interface ChartInstance {
        destroy(): void;
    }
    interface ChartConstructor {
        new (ctx: CanvasRenderingContext2D, config: unknown): ChartInstance;
    }
    const Chart: ChartConstructor;

    const L: typeof import('leaflet');
    namespace L {
        type Map = import('leaflet').Map;
        type Marker = import('leaflet').Marker;
        type LeafletMouseEvent = import('leaflet').LeafletMouseEvent;
        type DivIcon = import('leaflet').DivIcon;
        type LatLng = import('leaflet').LatLng;
    }

    const lucide: { createIcons(): void };
}

export {};
