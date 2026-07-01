import api from './api';
import type { ApiResponse, Prescription } from '@/types';
import { extractPaginatedData } from '@/lib/utils';

export const prescriptionsService = {
    list: async (params?: Record<string, string | number | boolean>) => {
        const res = await api.get('/prescriptions', { params });
        return extractPaginatedData<Prescription>(res.data);
    },

    get: async (id: number) => {
        const res = await api.get<ApiResponse<Prescription>>(`/prescriptions/${id}`);
        return res.data;
    },

    create: async (data: FormData) => {
        const res = await api.post<ApiResponse<Prescription>>('/prescriptions', data, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        return res.data;
    },

    dispense: async (id: number, data?: { items?: { id: number; dispensed_quantity: number }[] }) => {
        const res = await api.post<ApiResponse<Prescription>>(`/prescriptions/${id}/dispense`, data);
        return res.data;
    },


};
