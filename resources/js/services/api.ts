import axios from 'axios';
import { API_BASE_URL } from '@/lib/constants';
import { useAuthStore } from '@/stores/authStore';

const api = axios.create({
    baseURL: API_BASE_URL,
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
    },
});

api.interceptors.request.use((config) => {
    try {
        const impersonationToken = localStorage.getItem('impersonation_token');
        const token = impersonationToken || getAuthToken();
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
    } catch {
        // ignore parse errors
    }
    return config;
});

function getAuthToken(): string | null {
    const stored = localStorage.getItem('auth-storage');
    if (!stored) return null;
    const parsed = JSON.parse(stored);
    return parsed?.state?.token ?? null;
}

api.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;
        const data = error.response?.data;

        if (status === 401) {
            // Let the login page render its own error instead of bouncing
            if (error.config?.url?.includes('/auth/login')) {
                error.message = data?.message || 'Invalid credentials';
                return Promise.reject(error);
            }
            const wasImpersonating = !!localStorage.getItem('impersonation_token');
            useAuthStore.getState().logout();
            window.location.href = wasImpersonating ? '/super-admin/dashboard' : '/login';
        }

        if (status === 403 && data?.suspension_reason) {
            localStorage.removeItem('auth-storage');
            window.location.href = `/login?suspended=${encodeURIComponent(data.suspension_reason)}`;
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
