declare global {
    // Legacy DOM migration helper: treat Element as HTML-like for this project
    interface Element {
        style: CSSStyleDeclaration;
        dataset: DOMStringMap;
    }

    interface Window {
        [key: string]: unknown;
    }

    // Chart.js global (CDN)
    interface ChartDataset {
        data: number[];
        backgroundColor: string[];
    }

    interface ChartType {
        data: {
            labels: string[];
            datasets: ChartDataset[];
        };
    }

    const Chart: new (ctx: CanvasRenderingContext2D, config: unknown) => ChartType;

    // Lucide icons global (CDN)
    const lucide: { createIcons(): void };

    // Leaflet global (CDN)
    interface LeafletMap {
        setView(latLng: [number, number], zoom: number): LeafletMap;
        on(event: string, handler: (e: unknown) => void): LeafletMap;
        removeLayer(layer: unknown): void;
        whenReady(fn: () => void): void;
        invalidateSize(): void;
    }

    interface LeafletTileLayer {
        addTo(map: LeafletMap): LeafletTileLayer;
    }

    interface LeafletMarker {
        addTo(map: LeafletMap): LeafletMarker;
        bindPopup(content: string): LeafletMarker;
        openPopup(): LeafletMarker;
    }

    interface LeafletIcon {}

    interface LeafletStatic {
        map(id: string, options?: unknown): LeafletMap;
        tileLayer(url: string, options?: unknown): LeafletTileLayer;
        marker(latLng: [number, number], options?: { icon?: LeafletIcon } & Record<string, unknown>): LeafletMarker;
        divIcon(options: { className?: string; html?: string; iconSize?: [number, number]; iconAnchor?: [number, number] }): LeafletIcon;
    }

    const L: LeafletStatic;
}

export {};
