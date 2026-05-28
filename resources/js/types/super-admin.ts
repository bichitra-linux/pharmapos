export interface SuperAdmin {
    id: number;
    name: string;
    email: string;
    role: 'super_admin' | 'support';
    is_active: boolean;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface PlatformDashboard {
    total_tenants: number;
    active_tenants: number;
    suspended_tenants: number;
    new_this_month: number;
    mrr: number;
    expiring_soon: number;
    monthly_revenue: MonthlyRevenue[];
    recent_tenants: TenantSummary[];
}

export interface MonthlyRevenue {
    month: string;
    total: number;
}

export interface TenantSummary {
    id: number;
    name: string;
    slug: string;
    email: string;
    phone: string;
    is_active: boolean;
    suspended_at: string | null;
    suspension_reason: string | null;
    subscription_plan_id: number | null;
    subscription_expires_at: string | null;
    created_at: string;
}

export interface TenantDetail extends TenantSummary {
    address: string;
    pan_number: string;
    vat_number: string;
    drug_license_number: string;
    pharmacy_license_number: string;
    pharmacist_name: string;
    pharmacist_registration_number: string;
    settings: Record<string, unknown>;
    subscription_plan?: SubscriptionPlan;
    users?: TenantUser[];
    payments?: SubscriptionPayment[];
}

export interface TenantUser {
    id: number;
    name: string;
    email: string;
    role: string;
    is_active: boolean;
    created_at: string;
}

export interface SubscriptionPlan {
    id: number;
    name: string;
    price_monthly: number;
    price_yearly: number;
    max_outlets: number;
    max_users: number;
    max_medicines: number;
    features: string[] | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface SubscriptionPayment {
    id: number;
    company_id: number;
    plan_id: number;
    amount: number;
    payment_method: string;
    gateway: string | null;
    starts_at: string;
    expires_at: string;
    status: 'active' | 'expired' | 'cancelled';
    created_at: string;
    company?: TenantSummary;
    plan?: SubscriptionPlan;
}

export interface PlatformSetting {
    id: number;
    key: string;
    value: string | null;
    group: string;
}

export interface SystemHealth {
    database: 'ok' | 'error';
    redis: 'ok' | 'error';
    cache: 'ok' | 'error';
    queue: 'ok' | 'error';
}
