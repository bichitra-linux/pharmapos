import api from './api';
import type { ApiResponse, PaginatedResponse, Medicine, MedicineBatch } from '@/types';
import { extractPaginatedData } from '@/lib/utils';

export const medicinesService = {
    list: async (params?: Record<string, string | number | boolean>) => {
        const res = await api.get('/medicines', { params });
        return extractPaginatedData<Medicine>(res.data);
    },

    get: async (id: number) => {
        const res = await api.get<ApiResponse<Medicine>>(`/medicines/${id}`);
        return res.data;
    },

    create: async (data: Partial<Medicine>) => {
        const res = await api.post<ApiResponse<Medicine>>('/medicines', data);
        return res.data;
    },

    update: async (id: number, data: Partial<Medicine>) => {
        const res = await api.put<ApiResponse<Medicine>>(`/medicines/${id}`, data);
        return res.data;
    },

    delete: async (id: number) => {
        const res = await api.delete<ApiResponse<null>>(`/medicines/${id}`);
        return res.data;
    },

    search: async (query: string) => {
        const res = await api.get<ApiResponse<Medicine[]>>('/medicines/search', {
            params: { q: query },
        });
        return res.data;
    },

    getBatches: async (medicineId: number) => {
        const res = await api.get<ApiResponse<MedicineBatch[]>>(`/medicines/${medicineId}/batches`);
        return res.data;
    },

    getSubstitutes: async (medicineId: number) => {
        const res = await api.get<ApiResponse<Medicine[]>>(`/medicines/${medicineId}/substitutes`);
        return res.data;
    },

    import: async (file: File) => {
        const formData = new FormData();
        formData.append('file', file);
        const res = await api.post<ApiResponse<{ imported: number; errors: string[] }>>(
            '/medicines/import',
            formData,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        );
        return res.data;
    },
};
