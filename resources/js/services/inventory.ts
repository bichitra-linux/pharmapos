import api from './api';
import type { ApiResponse, MedicineBatch, InventoryAdjustment } from '@/types';
import { extractPaginatedData } from '@/lib/utils';

export const inventoryService = {
    getStock: async (params?: Record<string, string | number | boolean>) => {
        const res = await api.get('/inventory/stock', { params });
        return extractPaginatedData<MedicineBatch>(res.data);
    },

    getAdjustments: async (params?: Record<string, string | number | boolean>) => {
        const res = await api.get('/inventory/adjustments', { params });
        return extractPaginatedData<InventoryAdjustment>(res.data);
    },

    createAdjustment: async (data: { type: string; reason: string; items: { medicine_id: number; batch_id: number; quantity: number }[]; notes?: string }) => {
        const res = await api.post<ApiResponse<InventoryAdjustment>>('/inventory/adjustments', data);
        return res.data;
    },


};
