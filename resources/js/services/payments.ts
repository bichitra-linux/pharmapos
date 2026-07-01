import api from './api';
import type { ApiResponse } from '@/types';

interface PaymentInitResponse {
    url?: string;
    form_data?: Record<string, string>;
    token?: string;
}

export const paymentsService = {
    initiateEsewa: async (saleId: number, amount: number) => {
        const res = await api.post<ApiResponse<PaymentInitResponse>>('/payments/esewa', {
            sale_id: saleId,
            amount,
        });
        return res.data;
    },

    initiateKhalti: async (saleId: number, amount: number) => {
        const res = await api.post<ApiResponse<PaymentInitResponse>>('/payments/khalti', {
            sale_id: saleId,
            amount,
        });
        return res.data;
    },

    initiateFonepay: async (saleId: number, amount: number) => {
        const res = await api.post<ApiResponse<PaymentInitResponse>>('/payments/fonepay', {
            sale_id: saleId,
            amount,
        });
        return res.data;
    },

    initiateConnectIPS: async (saleId: number, amount: number) => {
        const res = await api.post<ApiResponse<PaymentInitResponse>>('/payments/connectips', {
            sale_id: saleId,
            amount,
        });
        return res.data;
    },


};
