export interface ApiResponse<T = unknown> {
    success: boolean;
    message?: string;
    data?: T;
}

export function getCsrfToken(): string | null {
    const input = document.querySelector<HTMLInputElement>('input[name="csrf_token"]');
    return input?.value ?? null;
}

export async function postJson<T = unknown>(url: string, body: Record<string, unknown>): Promise<ApiResponse<T>> {
    const token = getCsrfToken();
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            ...(token ? { 'X-Csrf-Token': token } : {}),
        },
        body: JSON.stringify({ ...body, ...(token ? { csrf_token: token } : {}) }),
    });

    return (await response.json()) as ApiResponse<T>;
}

export async function postForm<T = unknown>(url: string, form: HTMLFormElement): Promise<ApiResponse<T>> {
    const data = new FormData(form);
    const token = getCsrfToken();
    if (token && !data.has('csrf_token')) {
        data.append('csrf_token', token);
    }

    const response = await fetch(url, {
        method: 'POST',
        body: data,
    });

    return (await response.json()) as ApiResponse<T>;
}
