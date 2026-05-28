import api from './api';
import type { ApiResponse, Sale } from '@/types';
import { extractPaginatedData } from '@/lib/utils';

interface CreateSaleData {
    customer_id?: number;
    prescription_id?: number;
    items: {
        medicine_id: number;
        batch_id: number;
        quantity: number;
        unit_price: number;
        discount?: number;
    }[];
    payments: {
        payment_method_id: number;
        amount: number;
        reference_number?: string;
    }[];
    discount?: number;
    notes?: string;
}

export const salesService = {
    list: async (params?: Record<string, string | number | boolean>) => {
        const res = await api.get('/sales', { params });
        return extractPaginatedData<Sale>(res.data);
    },

    get: async (id: number) => {
        const res = await api.get<ApiResponse<Sale>>(`/sales/${id}`);
        return res.data;
    },

    create: async (data: CreateSaleData) => {
        const res = await api.post<{ success: boolean; data: { sale: Sale; invoice_number: string }; message: string }>('/sales', data);
        return res.data;
    },

    hold: async (data: CreateSaleData) => {
        const res = await api.post<ApiResponse<Sale>>('/sales/hold', data);
        return res.data;
    },

    cancel: async (id: number) => {
        const res = await api.post<ApiResponse<Sale>>(`/sales/${id}/cancel`);
        return res.data;
    },

    dailySummary: async (date?: string) => {
        const res = await api.get<ApiResponse<Record<string, number>>>('/sales/daily-summary', {
            params: date ? { date } : {},
        });
        return res.data;
    },

    getInvoice: async (id: number) => {
        const res = await api.get<ApiResponse<Sale>>(`/sales/${id}/invoice`);
        return res.data;
    },

    heldSales: async () => {
        const res = await api.get<ApiResponse<Sale[]>>('/sales/held');
        return res.data;
    },
};
