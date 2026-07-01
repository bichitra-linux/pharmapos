import api from './api';
import type { ApiResponse } from '@/types';

interface PosStats {
    sales_today: number;
    revenue_today: number;
    items_sold_today: number;
    top_medicines: { id: number; brand_name: string; total_sold: number }[];
}

interface RecentSale {
    id: number;
    invoice_number: string;
    total_amount: number;
    paid_amount: number;
    due_amount: number;
    payment_status: string;
    created_at: string;
    customer_name: string;
}

export const posStatsService = {
    getStats: async () => {
        const res = await api.get<ApiResponse<PosStats>>('/pos/stats');
        return res.data;
    },

    getRecentSales: async () => {
        const res = await api.get<ApiResponse<RecentSale[]>>('/pos/recent-sales');
        return res.data;
    },
};
