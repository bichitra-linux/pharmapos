import api from './api';
import type { ApiResponse, User } from '@/types';

interface LoginData {
    email: string;
    password: string;
    remember?: boolean;
}

interface RegisterData {
    company_name: string;
    name: string;
    email: string;
    phone: string;
    password: string;
    password_confirmation: string;
}

interface AuthResponse {
    user: User;
    token: string;
}

export const authService = {
    login: async (data: LoginData) => {
        const res = await api.post<ApiResponse<AuthResponse>>('/auth/login', data);
        return res.data;
    },

    register: async (data: RegisterData) => {
        const res = await api.post<ApiResponse<AuthResponse>>('/auth/register', data);
        return res.data;
    },

    logout: async () => {
        const res = await api.post<ApiResponse<null>>('/auth/logout');
        return res.data;
    },

    getMe: async () => {
        const res = await api.get<ApiResponse<User>>('/auth/me');
        return res.data;
    },

    updateProfile: async (data: Partial<User>) => {
        const res = await api.put<ApiResponse<User>>('/auth/profile', data);
        return res.data;
    },

    changePassword: async (data: { current_password: string; password: string; password_confirmation: string }) => {
        const res = await api.put<ApiResponse<null>>('/auth/password', data);
        return res.data;
    },
};
