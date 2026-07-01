import api from './api';
import type { ApiResponse, Manufacturer } from '@/types';

export const manufacturersService = {
    list: async (params?: Record<string, string | number | boolean>) => {
        const res = await api.get('/manufacturers', { params });
        return res.data;
    },

    create: async (data: Partial<Manufacturer>) => {
        const res = await api.post<ApiResponse<Manufacturer>>('/manufacturers', data);
        return res.data;
    },

    update: async (id: number, data: Partial<Manufacturer>) => {
        const res = await api.put<ApiResponse<Manufacturer>>(`/manufacturers/${id}`, data);
        return res.data;
    },

    delete: async (id: number) => {
        const res = await api.delete<ApiResponse<null>>(`/manufacturers/${id}`);
        return res.data;
    },
};
