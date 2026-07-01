import api from './api';
import type { ApiResponse, DashboardSummary, SalesChartData, ExpiryAlert, LowStockAlert, TopMedicine } from '@/types';

export const dashboardService = {
    getSummary: async () => {
        const res = await api.get<ApiResponse<DashboardSummary>>('/dashboard');
        return res.data;
    },

    getExpiryAlerts: async (days?: number) => {
        const res = await api.get<ApiResponse<ExpiryAlert[]>>('/dashboard/expiry-alerts', {
            params: days ? { days } : {},
        });
        return res.data;
    },

    getLowStock: async () => {
        const res = await api.get<ApiResponse<LowStockAlert[]>>('/dashboard/low-stock');
        return res.data;
    },

    getSalesChart: async (days?: number) => {
        const res = await api.get<ApiResponse<SalesChartData[]>>('/dashboard/sales-chart', {
            params: days ? { days } : {},
        });
        return res.data;
    },

    getTopMedicines: async (params?: { limit?: number; period?: string }) => {
        const res = await api.get<ApiResponse<TopMedicine[]>>('/dashboard/top-medicines', { params });
        return res.data;
    },
};
