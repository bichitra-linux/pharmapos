import api from './api';
import type { ApiResponse, Purchase } from '@/types';
import { extractPaginatedData } from '@/lib/utils';

interface CreatePurchaseData {
    supplier_id: number;
    purchase_date: string;
    grn_number?: string;
    due_date?: string;
    supplier_invoice_number?: string;
    items: {
        medicine_id: number;
        batch_number: string;
        manufacturing_date?: string;
        expiry_date: string;
        quantity: number;
        purchase_price: number;
        vat_rate?: number;
    }[];
    discount?: number;
    paid_amount?: number;
    notes?: string;
}

export const purchasesService = {
    list: async (params?: Record<string, string | number | boolean>) => {
        const res = await api.get('/purchases', { params });
        return extractPaginatedData<Purchase>(res.data);
    },

    get: async (id: number) => {
        const res = await api.get<ApiResponse<Purchase>>(`/purchases/${id}`);
        return res.data;
    },

    create: async (data: CreatePurchaseData) => {
        const res = await api.post<ApiResponse<Purchase>>('/purchases', data);
        return res.data;
    },

    receive: async (id: number, data?: { items?: { purchase_item_id: number; received_quantity: number }[] }) => {
        const res = await api.post<ApiResponse<Purchase>>(`/purchases/${id}/receive`, data);
        return res.data;
    },


};
