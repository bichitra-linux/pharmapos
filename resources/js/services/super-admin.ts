import axios from 'axios';
import type { ApiResponse } from '@/types';
import { extractPaginatedData } from '@/lib/utils';
import { useSuperAdminStore } from '@/stores/superAdminStore';
import type {
    SuperAdmin,
    PlatformDashboard,
    TenantSummary,
    TenantDetail,
    SubscriptionPlan,
    SubscriptionPayment,
    PlatformSetting,
    PaymentGateway,
    SystemHealth,
    RevenueResponse,
} from '@/types/super-admin';

const superAdminApi = axios.create({
    baseURL: '/api/super-admin/',
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
    },
});

superAdminApi.interceptors.request.use((config) => {
    const token = useSuperAdminStore.getState().token;
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

superAdminApi.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;
        const data = error.response?.data;

        if (status === 401) {
            useSuperAdminStore.getState().logout();
            window.location.href = '/super-admin/login';
        }

        if (status === 422 && data?.errors) {
            const fieldErrors = data.errors as Record<string, string[]>;
            const messages = Object.entries(fieldErrors)
                .map(([field, errs]) => `${field}: ${errs.join(', ')}`)
                .join('\n');
            error.validationErrors = fieldErrors;
            error.message = messages || data.message || 'Validation failed';
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
        const res = await superAdminApi.patch<ApiResponse<TenantDetail>>(`tenants/${id}/suspend`, { reason });
        return res.data;
    },

    activateTenant: async (id: number) => {
        const res = await superAdminApi.patch<ApiResponse<TenantDetail>>(`tenants/${id}/activate`);
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
        const res = await superAdminApi.patch<ApiResponse<SubscriptionPlan>>(`plans/${id}/toggle`);
        return res.data;
    },

    getSubscriptions: async () => {
        const res = await superAdminApi.get('subscriptions');
        return extractPaginatedData<TenantSummary>(res.data);
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
        const res = await superAdminApi.get<ApiResponse<Record<string, PlatformSetting[]>>>('settings');
        return res.data;
    },

    updateSettings: async (data: Record<string, string>) => {
        const settings = Object.entries(data).map(([key, value]) => ({ key, value }));
        const res = await superAdminApi.put<ApiResponse<PlatformSetting[]>>('settings', { settings });
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

    // Payment Gateways
    getPaymentGateways: async () => {
        const res = await superAdminApi.get<ApiResponse<PaymentGateway[]>>('payment-gateways');
        return res.data;
    },

    updatePaymentGateway: async (id: number, data: Partial<PaymentGateway>) => {
        const res = await superAdminApi.put<ApiResponse<PaymentGateway>>(`payment-gateways/${id}`, data);
        return res.data;
    },

    togglePaymentGateway: async (id: number) => {
        const res = await superAdminApi.patch<ApiResponse<PaymentGateway>>(`payment-gateways/${id}/toggle`);
        return res.data;
    },

    testPaymentGateway: async (id: number) => {
        const res = await superAdminApi.post<ApiResponse<{ message: string }>>(`payment-gateways/${id}/test`);
        return res.data;
    },

    // Tenant impersonation & usage
    impersonateTenant: async (id: number) => {
        const res = await superAdminApi.post<ApiResponse<{ token: string; user: any; tenant: any; expires_at: string }>>(`tenants/${id}/impersonate`);
        return res.data;
    },

    getTenantUsage: async (id: number) => {
        const res = await superAdminApi.get<ApiResponse<{
            users_count: number; outlets_count: number; medicines_count: number;
            customers_count: number; sales_this_month: number; sales_total: number;
            plan_limit_medicines?: number; plan_limit_users?: number; plan_limit_outlets?: number;
            medicines_percent?: number; users_percent?: number; outlets_percent?: number;
        }>>(`tenants/${id}/usage`);
        return res.data;
    },

    // Audit logs
    getAuditLogs: async (params?: Record<string, string | number | boolean>) => {
        const res = await superAdminApi.get('audit-logs', { params });
        return extractPaginatedData<Record<string, unknown>>(res.data);
    },

    // Landing page
    getLanding: async () => {
        const res = await superAdminApi.get<ApiResponse<Record<string, unknown>>>('landing');
        return res.data;
    },

    updateLanding: async (data: Record<string, unknown>) => {
        const res = await superAdminApi.put<ApiResponse<Record<string, unknown>>>('landing', data);
        return res.data;
    },

    publishLanding: async () => {
        const res = await superAdminApi.post<ApiResponse<Record<string, unknown>>>('landing/publish');
        return res.data;
    },

    getLandingRevisions: async () => {
        const res = await superAdminApi.get('landing/revisions');
        return extractPaginatedData<Record<string, unknown>>(res.data);
    },

    restoreLandingRevision: async (revisionId: number) => {
        const res = await superAdminApi.post<ApiResponse<Record<string, unknown>>>(`landing/revisions/${revisionId}/restore`);
        return res.data;
    },
};
