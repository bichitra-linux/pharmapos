import api from './api';
import type { ApiResponse } from '@/types';

interface SalesReportParams {
    from: string;
    to: string;
    group_by?: 'day' | 'week' | 'month';
}

interface ReportData {
    headers: string[];
    rows: (string | number)[][];
    totals: Record<string, number>;
}

export const reportsService = {
    sales: async (params: SalesReportParams) => {
        const res = await api.get<ApiResponse<ReportData>>('/reports/sales', { params });
        return res.data;
    },

    purchases: async (params: { from: string; to: string }) => {
        const res = await api.get<ApiResponse<ReportData>>('/reports/purchases', { params });
        return res.data;
    },

    inventory: async () => {
        const res = await api.get<ApiResponse<ReportData>>('/reports/inventory');
        return res.data;
    },

    expiry: async (params?: { days?: number }) => {
        const res = await api.get<ApiResponse<ReportData>>('/reports/expiry', { params });
        return res.data;
    },

    profitLoss: async (params: { from: string; to: string }) => {
        const res = await api.get<ApiResponse<ReportData>>('/reports/profit-loss', { params });
        return res.data;
    },

    vat: async (params: { from: string; to: string }) => {
        const res = await api.get<ApiResponse<ReportData>>('/reports/vat', { params });
        return res.data;
    },

    narcotics: async (params: { from: string; to: string }) => {
        const res = await api.get<ApiResponse<ReportData>>('/reports/narcotics', { params });
        return res.data;
    },

    export: async (type: string, params: Record<string, string | number>) => {
        const res = await api.get(`/reports/${type}/export`, {
            params,
            responseType: 'blob',
        });
        return res.data;
    },
};
