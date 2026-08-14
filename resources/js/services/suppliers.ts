import api from './api';
import type { ApiResponse, Supplier, SupplierPayment } from '@/types';
import { extractPaginatedData } from '@/lib/utils';

export const suppliersService = {
    list: async (params?: Record<string, string | number | boolean>) => {
        const res = await api.get('/suppliers', { params });
        return extractPaginatedData<Supplier>(res.data);
    },

    get: async (id: number) => {
        const res = await api.get<ApiResponse<Supplier>>(`/suppliers/${id}`);
        return res.data;
    },

    create: async (data: Partial<Supplier>) => {
        const res = await api.post<ApiResponse<Supplier>>('/suppliers', data);
        return res.data;
    },

    update: async (id: number, data: Partial<Supplier>) => {
        const res = await api.put<ApiResponse<Supplier>>(`/suppliers/${id}`, data);
        return res.data;
    },

    delete: async (id: number) => {
        const res = await api.delete<ApiResponse<null>>(`/suppliers/${id}`);
        return res.data;
    },

    getLedger: async (id: number) => {
        const res = await api.get<ApiResponse<{
            supplier: Supplier;
            purchases: { id: number; purchase_number: string; total: number; paid_amount: number; status: string; created_at: string }[];
            payments: (SupplierPayment & { purchase_id?: number; purchase_number?: string | null })[];
            summary: { total_purchases: number; total_paid: number; balance: number };
        }>>(`/suppliers/${id}/ledger`);
        return res.data;
    },

    recordPayment: async (supplierId: number, data: { amount: number; payment_method: string; reference_number?: string; purchase_id?: number; notes?: string }) => {
        const res = await api.post<ApiResponse<SupplierPayment>>('/supplier-payments', { ...data, supplier_id: supplierId });
        return res.data;
    },
};
