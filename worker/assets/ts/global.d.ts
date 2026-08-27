declare global {
    // Legacy DOM migration helper: treat Element as HTML-like for this project
    interface Element {
        style: CSSStyleDeclaration;
        dataset: DOMStringMap;
    }

    interface Window {
        [key: string]: unknown;
    }

    // CDN globals
    const tailwind: { [key: string]: unknown } | undefined;
    const feather: { replace(): void } | undefined;
    const lucide: { createIcons(): void } | undefined;
    const Chart: new (ctx: CanvasRenderingContext2D, config: unknown) => unknown;

    interface LeafletBounds {
        pad(amount: number): LeafletBounds;
    }

    interface LeafletLayer {
        addTo(map: LeafletMap): this;
        bindPopup(content: string, options?: Record<string, unknown>): this;
        removeLayer(layer: unknown): void;
    }

    interface LeafletMarker extends LeafletLayer {
        getLatLng(): { lat: number; lng: number };
        getElement(): HTMLElement | null;
        openPopup(): this;
        _icon?: HTMLElement | null;
    }

    interface LeafletMap {
        setView(center: [number, number], zoom: number, options?: unknown): this;
        eachLayer(callback: (layer: unknown) => void): void;
        removeLayer(layer: unknown): void;
        hasLayer(layer: unknown): boolean;
        fitBounds(bounds: LeafletBounds, options?: unknown): this;
        getCenter(): { lat: number; lng: number };
    }

    interface LeafletFeatureGroup {
        getBounds(): LeafletBounds;
    }

    interface LeafletControl {
        onAdd: (map: LeafletMap) => HTMLElement;
        addTo(map: LeafletMap): this;
    }

    interface LeafletPopupBuilder {
        setLatLng(latlng: [number, number]): this;
        setContent(content: string): this;
        openOn(map: LeafletMap): this;
    }

    const L: {
        map(id: string): LeafletMap;
        marker(latlng: [number, number], options?: Record<string, unknown>): LeafletMarker;
        divIcon(options?: Record<string, unknown>): unknown;
        tileLayer(url: string, options?: Record<string, unknown>): unknown;
        polyline(latlngs: unknown, options?: Record<string, unknown>): LeafletLayer;
        featureGroup(layers: unknown): LeafletFeatureGroup;
        circleMarker(latlng: [number, number], options?: Record<string, unknown>): LeafletLayer;
        control(options?: Record<string, unknown>): LeafletControl;
        DomUtil: { create(tagName: string, className?: string, container?: HTMLElement): HTMLElement };
        popup(options?: Record<string, unknown>): LeafletPopupBuilder;
    };
}

export {};
