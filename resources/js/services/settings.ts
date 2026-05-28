import api from './api';
import type { ApiResponse, Company } from '@/types';

interface Settings {
    company_name: string;
    receipt_header: string;
    receipt_footer: string;
    invoice_prefix: string;
    prescription_prefix: string;
    purchase_prefix: string;
    return_prefix: string;
    default_tax_rate: number;
    currency_symbol: string;
    date_format: string;
    time_zone: string;
    low_stock_threshold: number;
    expiry_alert_days: number;
    enable_loyalty: boolean;
    loyalty_points_per_rupee: number;
    enable_narcotics_register: boolean;
}

export const settingsService = {
    getSettings: async () => {
        const res = await api.get<ApiResponse<Settings>>('/settings');
        return res.data;
    },

    updateSettings: async (data: Partial<Settings>) => {
        const res = await api.put<ApiResponse<Settings>>('/settings', data);
        return res.data;
    },

    getCompany: async () => {
        const res = await api.get<ApiResponse<Company>>('/settings/company');
        return res.data;
    },

    updateCompany: async (data: Partial<Company>) => {
        const res = await api.put<ApiResponse<Company>>('/settings/company', data);
        return res.data;
    },
};
