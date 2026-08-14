import api from './api';
import type { ApiResponse, PaymentMethod } from '@/types';

export const paymentMethodsService = {
    list: async () => {
        const res = await api.get<ApiResponse<PaymentMethod[]>>('/payment-methods');
        return res.data;
    },
};
