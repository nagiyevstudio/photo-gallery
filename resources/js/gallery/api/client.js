// Simple fetch wrapper with credentials (cookies) enabled.

const API_BASE = '/api';

export const client = {
    async get(endpoint) {
        const response = await fetch(`${API_BASE}${endpoint}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
            },
        });
        
        return handleResponse(response);
    },

    async post(endpoint, data = {}) {
        const response = await fetch(`${API_BASE}${endpoint}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(data),
        });
        
        return handleResponse(response);
    }
};

async function handleResponse(response) {
    const isJson = response.headers.get('content-type')?.includes('application/json');
    const data = isJson ? await response.json() : null;

    if (!response.ok) {
        // Return structured error
        const error = new Error(data?.message || response.statusText);
        error.status = response.status;
        error.data = data;
        throw error;
    }

    return data;
}
