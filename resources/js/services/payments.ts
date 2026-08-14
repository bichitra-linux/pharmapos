import api from './api';
import type { ApiResponse } from '@/types';

interface PaymentInitResponse {
    url?: string;
    form_data?: Record<string, string>;
    token?: string;
}

export const paymentsService = {
    initiate: async (gateway: string, saleId: number, amount: number) => {
        const res = await api.post<ApiResponse<PaymentInitResponse>>(`/payments/${gateway}/initiate`, {
            sale_id: saleId,
            amount,
        });
        return res.data;
    },

    initiateEsewa: async (saleId: number, amount: number) => paymentsService.initiate('esewa', saleId, amount),

    initiateKhalti: async (saleId: number, amount: number) => paymentsService.initiate('khalti', saleId, amount),

    initiateFonepay: async (saleId: number, amount: number) => paymentsService.initiate('fonepay', saleId, amount),

    initiateConnectIPS: async (saleId: number, amount: number) => paymentsService.initiate('connectips', saleId, amount),
};
