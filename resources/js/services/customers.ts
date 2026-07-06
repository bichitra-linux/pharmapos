import api from './api';
import type { ApiResponse, Customer, Sale } from '@/types';
import { extractPaginatedData } from '@/lib/utils';

export const customersService = {
    list: async (params?: Record<string, string | number | boolean>) => {
        const res = await api.get('/customers', { params });
        return extractPaginatedData<Customer>(res.data);
    },

    get: async (id: number) => {
        const res = await api.get<ApiResponse<Customer>>(`/customers/${id}`);
        return res.data;
    },

    create: async (data: Partial<Customer>) => {
        const res = await api.post<ApiResponse<Customer>>('/customers', data);
        return res.data;
    },

    update: async (id: number, data: Partial<Customer>) => {
        const res = await api.put<ApiResponse<Customer>>(`/customers/${id}`, data);
        return res.data;
    },

    delete: async (id: number) => {
        const res = await api.delete<ApiResponse<null>>(`/customers/${id}`);
        return res.data;
    },

    getHistory: async (id: number, params?: Record<string, string | number>) => {
        const res = await api.get<ApiResponse<{ customer: Customer; sales: any; prescriptions: any[] }>>(`/customers/${id}/history`, { params });
        return res.data;
    },

    search: async (query: string) => {
        const res = await api.get('/customers', { params: { search: query, per_page: 10 } });
        return extractPaginatedData<Customer>(res.data);
    },

    creditLend: async (id: number, amount: number, note?: string) => {
        const res = await api.post(`/customers/${id}/credit-lend`, { amount, note });
        return res.data;
    },

    creditReceive: async (id: number, amount: number, paymentMethod?: string, note?: string) => {
        const res = await api.post(`/customers/${id}/credit-receive`, { amount, payment_method: paymentMethod, note });
        return res.data;
    },

    getCreditLedger: async (id: number) => {
        const res = await api.get(`/customers/${id}/credit-ledger`);
        return extractPaginatedData<Record<string, unknown>>(res.data);
    },

    getCreditSummary: async (id: number) => {
        const res = await api.get(`/customers/${id}/credit-summary`);
        return res.data;
    },

    setCreditLimit: async (id: number, creditLimit: number) => {
        const res = await api.post(`/customers/${id}/credit-limit`, { credit_limit: creditLimit });
        return res.data;
    },
};
