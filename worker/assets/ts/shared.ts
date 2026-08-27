export function getPageData<T>(id: string): T | null {
    const element = document.getElementById(id);
    if (!element) return null;
    try {
        return JSON.parse(element.textContent ?? '') as T;
    } catch {
        console.error(`Failed to parse JSON data from #${id}`);
        return null;
    }
}

export function selectElement<T extends HTMLElement>(id: string): T | null {
    return document.getElementById(id) as T | null;
}

export function selectElements<T extends HTMLElement>(selector: string): T[] {
    return Array.from(document.querySelectorAll<T>(selector));
}
