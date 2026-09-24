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

export function getEl<T extends HTMLElement>(id: string): T {
    const element = document.getElementById(id);
    if (!element) {
        throw new Error(`Required DOM element #${id} not found`);
    }
    return element as T;
}

export function getInput(id: string): HTMLInputElement {
    return getEl<HTMLInputElement>(id);
}

export function getSelect(id: string): HTMLSelectElement {
    return getEl<HTMLSelectElement>(id);
}

export function getTextArea(id: string): HTMLTextAreaElement {
    return getEl<HTMLTextAreaElement>(id);
}

export function queryEl<T extends HTMLElement>(selector: string): T | null {
    return document.querySelector<T>(selector);
}

export function triggerFileInput(inputId: string): void {
    const input = document.getElementById(inputId) as HTMLInputElement | null;
    if (input) {
        input.click();
    }
}

export function gotoUrl(url: string): void {
    window.location.href = url;
}

export function hideParent(el: HTMLElement, levels = 1): void {
    let target: HTMLElement | null = el;
    for (let i = 0; i < levels; i++) {
        target = target?.parentElement ?? null;
    }
    if (target) {
        target.style.display = 'none';
    }
}

export function dismissNotification(el: HTMLElement): void {
    const target = el.closest<HTMLElement>('.notification') ?? el.parentElement;
    target?.remove();
}

Object.assign(window, { hideParent, dismissNotification, triggerFileInput });
