export function getPageData(id) {
    const element = document.getElementById(id);
    if (!element)
        return null;
    try {
        return JSON.parse(element.textContent ?? '');
    }
    catch {
        console.error(`Failed to parse JSON data from #${id}`);
        return null;
    }
}
export function selectElement(id) {
    return document.getElementById(id);
}
export function selectElements(selector) {
    return Array.from(document.querySelectorAll(selector));
}
//# sourceMappingURL=shared.js.map