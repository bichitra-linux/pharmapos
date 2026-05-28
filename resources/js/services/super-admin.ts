import axios from 'axios';
import type { ApiResponse } from '@/types';
import { extractPaginatedData } from '@/lib/utils';
import type {
    SuperAdmin,
    PlatformDashboard,
    TenantSummary,
    TenantDetail,
    SubscriptionPlan,
    SubscriptionPayment,
    PlatformSetting,
    SystemHealth,
} from '@/types/super-admin';

const superAdminApi = axios.create({
    baseURL: '/api/super-admin/',
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
    },
});

superAdminApi.interceptors.request.use((config) => {
    const token = localStorage.getItem('super_admin_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

superAdminApi.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            localStorage.removeItem('super_admin_token');
            window.location.href = '/super-admin/login';
        }
        return Promise.reject(error);
    }
);

interface LoginData {
    email: string;
    password: string;
}

interface LoginResponse {
    user: SuperAdmin;
    token: string;
}

interface TenantCreateData {
    name: string;
    email: string;
    phone: string;
    address?: string;
    pan_number?: string;
    vat_number?: string;
    drug_license_number?: string;
    pharmacy_license_number?: string;
    pharmacist_name?: string;
    pharmacist_registration_number?: string;
    subscription_plan_id?: number;
    password: string;
    password_confirmation: string;
}

interface TenantUpdateData {
    name?: string;
    email?: string;
    phone?: string;
    address?: string;
    pan_number?: string;
    vat_number?: string;
    drug_license_number?: string;
    pharmacy_license_number?: string;
    pharmacist_name?: string;
    pharmacist_registration_number?: string;
    subscription_plan_id?: number;
}

interface PlanCreateData {
    name: string;
    price_monthly: number;
    price_yearly: number;
    max_outlets: number;
    max_users: number;
    max_medicines: number;
    features: string[];
}

interface TenantParams {
    search?: string;
    status?: string;
    page?: number;
    per_page?: number;
}

interface RevenueResponse {
    total: number;
    this_month: number;
    this_year: number;
}

export const superAdminService = {
    login: async (data: LoginData) => {
        const res = await superAdminApi.post<ApiResponse<LoginResponse>>('auth/login', data);
        return res.data;
    },

    logout: async () => {
        const res = await superAdminApi.post<ApiResponse<null>>('auth/logout');
        return res.data;
    },

    getMe: async () => {
        const res = await superAdminApi.get<ApiResponse<SuperAdmin>>('auth/me');
        return res.data;
    },

    getDashboard: async () => {
        const res = await superAdminApi.get<ApiResponse<PlatformDashboard>>('dashboard');
        return res.data;
    },

    getTenants: async (params?: TenantParams) => {
        const res = await superAdminApi.get('tenants', { params });
        return extractPaginatedData<TenantSummary>(res.data);
    },

    getTenant: async (id: number) => {
        const res = await superAdminApi.get<ApiResponse<TenantDetail>>(`tenants/${id}`);
        return res.data;
    },

    createTenant: async (data: TenantCreateData) => {
        const res = await superAdminApi.post<ApiResponse<TenantDetail>>('tenants', data);
        return res.data;
    },

    updateTenant: async (id: number, data: TenantUpdateData) => {
        const res = await superAdminApi.put<ApiResponse<TenantDetail>>(`tenants/${id}`, data);
        return res.data;
    },

    suspendTenant: async (id: number, reason: string) => {
        const res = await superAdminApi.post<ApiResponse<TenantDetail>>(`tenants/${id}/suspend`, { reason });
        return res.data;
    },

    activateTenant: async (id: number) => {
        const res = await superAdminApi.post<ApiResponse<TenantDetail>>(`tenants/${id}/activate`);
        return res.data;
    },

    deleteTenant: async (id: number) => {
        const res = await superAdminApi.delete<ApiResponse<null>>(`tenants/${id}`);
        return res.data;
    },

    getPlans: async () => {
        const res = await superAdminApi.get<ApiResponse<SubscriptionPlan[]>>('plans');
        return res.data;
    },

    getPlan: async (id: number) => {
        const res = await superAdminApi.get<ApiResponse<SubscriptionPlan>>(`plans/${id}`);
        return res.data;
    },

    createPlan: async (data: PlanCreateData) => {
        const res = await superAdminApi.post<ApiResponse<SubscriptionPlan>>('plans', data);
        return res.data;
    },

    updatePlan: async (id: number, data: Partial<PlanCreateData>) => {
        const res = await superAdminApi.put<ApiResponse<SubscriptionPlan>>(`plans/${id}`, data);
        return res.data;
    },

    deletePlan: async (id: number) => {
        const res = await superAdminApi.delete<ApiResponse<null>>(`plans/${id}`);
        return res.data;
    },

    togglePlan: async (id: number) => {
        const res = await superAdminApi.post<ApiResponse<SubscriptionPlan>>(`plans/${id}/toggle`);
        return res.data;
    },

    getSubscriptions: async () => {
        const res = await superAdminApi.get('subscriptions');
        return extractPaginatedData<SubscriptionPayment>(res.data);
    },

    extendSubscription: async (companyId: number, days: number) => {
        const res = await superAdminApi.post<ApiResponse<SubscriptionPayment>>(`subscriptions/${companyId}/extend`, { days });
        return res.data;
    },

    cancelSubscription: async (companyId: number) => {
        const res = await superAdminApi.post<ApiResponse<null>>(`subscriptions/${companyId}/cancel`);
        return res.data;
    },

    getPayments: async () => {
        const res = await superAdminApi.get('payments');
        return extractPaginatedData<SubscriptionPayment>(res.data);
    },

    getRevenue: async () => {
        const res = await superAdminApi.get<ApiResponse<RevenueResponse>>('payments/revenue');
        return res.data;
    },

    getSettings: async () => {
        const res = await superAdminApi.get<ApiResponse<PlatformSetting[]>>('settings');
        return res.data;
    },

    updateSettings: async (data: Record<string, string>) => {
        const res = await superAdminApi.put<ApiResponse<PlatformSetting[]>>('settings', { settings: data });
        return res.data;
    },

    getHealth: async () => {
        const res = await superAdminApi.get<ApiResponse<SystemHealth>>('system/health');
        return res.data;
    },

    clearCache: async () => {
        const res = await superAdminApi.post<ApiResponse<null>>('system/clear-cache');
        return res.data;
    },
};
