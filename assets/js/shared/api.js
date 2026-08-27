export function getCsrfToken() {
    const input = document.querySelector('input[name="csrf_token"]');
    return input?.value ?? null;
}
export async function postJson(url, body) {
    const token = getCsrfToken();
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            ...(token ? { 'X-Csrf-Token': token } : {}),
        },
        body: JSON.stringify({ ...body, ...(token ? { csrf_token: token } : {}) }),
    });
    return (await response.json());
}
export async function postForm(url, form) {
    const data = new FormData(form);
    const token = getCsrfToken();
    if (token && !data.has('csrf_token')) {
        data.append('csrf_token', token);
    }
    const response = await fetch(url, {
        method: 'POST',
        body: data,
    });
    return (await response.json());
}
//# sourceMappingURL=api.js.map