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
export function getEl(id) {
    const element = document.getElementById(id);
    if (!element) {
        throw new Error(`Required DOM element #${id} not found`);
    }
    return element;
}
export function getInput(id) {
    return getEl(id);
}
export function getSelect(id) {
    return getEl(id);
}
export function getTextArea(id) {
    return getEl(id);
}
export function queryEl(selector) {
    return document.querySelector(selector);
}
export function triggerFileInput(inputId) {
    const input = document.getElementById(inputId);
    if (input) {
        input.click();
    }
}
export function gotoUrl(url) {
    window.location.href = url;
}
export function hideParent(el, levels = 1) {
    let target = el;
    for (let i = 0; i < levels; i++) {
        target = target?.parentElement ?? null;
    }
    if (target) {
        target.style.display = 'none';
    }
}
export function dismissNotification(el) {
    const target = el.closest('.notification') ?? el.parentElement;
    target?.remove();
}
//# sourceMappingURL=dom.js.map