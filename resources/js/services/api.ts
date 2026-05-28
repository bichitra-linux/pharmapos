import axios from 'axios';
import { API_BASE_URL } from '@/lib/constants';

const api = axios.create({
    baseURL: API_BASE_URL,
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
    },
});

api.interceptors.request.use((config) => {
    try {
        const stored = localStorage.getItem('auth-storage');
        if (stored) {
            const parsed = JSON.parse(stored);
            const token = parsed?.state?.token;
            if (token) {
                config.headers.Authorization = `Bearer ${token}`;
            }
        }
    } catch {
        // ignore parse errors
    }
    return config;
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;
        const data = error.response?.data;

        if (status === 401) {
            localStorage.removeItem('auth-storage');
            localStorage.removeItem('super_admin_token');
            window.location.href = '/login';
        }

        if (status === 403 && data?.suspension_reason) {
            localStorage.removeItem('auth-storage');
            localStorage.removeItem('super_admin_token');
            alert(`Account suspended: ${data.suspension_reason}`);
            window.location.href = '/login';
        }

        if (status === 422 && data?.errors) {
            const fieldErrors = data.errors as Record<string, string[]>;
            const messages = Object.entries(fieldErrors)
                .map(([field, errs]) => `${field}: ${errs.join(', ')}`)
                .join('\n');
            error.validationErrors = fieldErrors;
            error.message = messages || data.message || 'Validation failed';
        }

        return Promise.reject(error);
    }
);

export default api;
