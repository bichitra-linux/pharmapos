import { lazy, Suspense } from 'react';
import { createBrowserRouter, Navigate } from 'react-router-dom';
import { useAuthStore } from '@/stores/authStore';
import { useSuperAdminStore } from '@/stores/superAdminStore';
import { AppLayout } from '@/components/layout/app-layout';
import { SuperAdminLayout } from '@/components/layout/super-admin-layout';
import { PageLoader } from '@/components/ui/spinner';

const LoginPage = lazy(() => import('@/pages/login'));
const RegisterPage = lazy(() => import('@/pages/register'));
const DashboardPage = lazy(() => import('@/pages/dashboard'));
const POSPage = lazy(() => import('@/pages/pos'));

const MedicinesIndex = lazy(() => import('@/pages/medicines/index'));
const CreateMedicine = lazy(() => import('@/pages/medicines/create'));
const EditMedicine = lazy(() => import('@/pages/medicines/edit'));
const ShowMedicine = lazy(() => import('@/pages/medicines/show'));
const ImportMedicine = lazy(() => import('@/pages/medicines/import'));
const ManufacturersIndex = lazy(() => import('@/pages/medicines/manufacturers/index'));

const InventoryIndex = lazy(() => import('@/pages/inventory/index'));
const AdjustmentsPage = lazy(() => import('@/pages/inventory/adjustments'));
const ReorderPage = lazy(() => import('@/pages/inventory/reorder'));

const PurchasesIndex = lazy(() => import('@/pages/purchases/index'));
const CreatePurchase = lazy(() => import('@/pages/purchases/create'));
const ShowPurchase = lazy(() => import('@/pages/purchases/show'));

const SalesIndex = lazy(() => import('@/pages/sales/index'));
const ShowSale = lazy(() => import('@/pages/sales/show'));

const CustomersIndex = lazy(() => import('@/pages/customers/index'));
const CreateEditCustomer = lazy(() => import('@/pages/customers/create'));
const CustomerShow = lazy(() => import('@/pages/customers/show'));

const SuppliersIndex = lazy(() => import('@/pages/suppliers/index'));
const CreateEditSupplier = lazy(() => import('@/pages/suppliers/create'));
const SupplierLedger = lazy(() => import('@/pages/suppliers/ledger'));

const PrescriptionsIndex = lazy(() => import('@/pages/prescriptions/index'));
const CreatePrescription = lazy(() => import('@/pages/prescriptions/create'));
const ShowPrescription = lazy(() => import('@/pages/prescriptions/show'));

const ReturnsIndex = lazy(() => import('@/pages/returns/index'));
const CreateReturn = lazy(() => import('@/pages/returns/create'));

const ReportsIndex = lazy(() => import('@/pages/reports/index'));
const SalesReport = lazy(() => import('@/pages/reports/sales'));
const InventoryReport = lazy(() => import('@/pages/reports/inventory'));
const ExpiryReport = lazy(() => import('@/pages/reports/expiry'));
const ProfitLossReport = lazy(() => import('@/pages/reports/profit-loss'));
const VatReport = lazy(() => import('@/pages/reports/vat'));
const NarcoticsReport = lazy(() => import('@/pages/reports/narcotics'));

const NarcoticsRegisterIndex = lazy(() => import('@/pages/narcotics-register/index'));
const SettingsPage = lazy(() => import('@/pages/settings/index'));
const UsersIndex = lazy(() => import('@/pages/users/index'));
const CreateEditUser = lazy(() => import('@/pages/users/create'));

const SuperAdminLogin = lazy(() => import('@/pages/super-admin/login'));
const SuperAdminDashboard = lazy(() => import('@/pages/super-admin/dashboard'));
const SuperAdminTenants = lazy(() => import('@/pages/super-admin/tenants/index'));
const SuperAdminTenantCreate = lazy(() => import('@/pages/super-admin/tenants/create'));
const SuperAdminTenantShow = lazy(() => import('@/pages/super-admin/tenants/show'));
const SuperAdminPlans = lazy(() => import('@/pages/super-admin/plans/index'));
const SuperAdminSubscriptions = lazy(() => import('@/pages/super-admin/subscriptions/index'));
const SuperAdminPayments = lazy(() => import('@/pages/super-admin/payments/index'));
const SuperAdminLanding = lazy(() => import('@/pages/super-admin/landing/index'));
const SuperAdminSettings = lazy(() => import('@/pages/super-admin/settings/index'));
const SuperAdminSystem = lazy(() => import('@/pages/super-admin/system/index'));
const SuperAdminAuditLogs = lazy(() => import('@/pages/super-admin/audit-logs/index'));

function SuspenseWrapper({ children }: { children: React.ReactNode }) {
    return <Suspense fallback={<PageLoader />}>{children}</Suspense>;
}

function ProtectedRoute({ children }: { children: React.ReactNode }) {
    const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
    const token = useAuthStore((s) => s.token);
    if (!isAuthenticated || !token) {
        return <Navigate to="/login" replace />;
    }
    return <>{children}</>;
}

function PublicRoute({ children }: { children: React.ReactNode }) {
    const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
    if (isAuthenticated) {
        return <Navigate to="/dashboard" replace />;
    }
    return <>{children}</>;
}

function SuperAdminProtectedRoute({ children }: { children: React.ReactNode }) {
    const isAuthenticated = useSuperAdminStore((s) => s.isAuthenticated);
    if (!isAuthenticated) {
        return <Navigate to="/super-admin/login" replace />;
    }
    return <>{children}</>;
}

function SuperAdminPublicRoute({ children }: { children: React.ReactNode }) {
    const isAuthenticated = useSuperAdminStore((s) => s.isAuthenticated);
    if (isAuthenticated) {
        return <Navigate to="/super-admin/dashboard" replace />;
    }
    return <>{children}</>;
}

export const router = createBrowserRouter([
    {
        path: '/login',
        element: (
            <PublicRoute>
                <SuspenseWrapper><LoginPage /></SuspenseWrapper>
            </PublicRoute>
        ),
    },
    {
        path: '/register',
        element: (
            <PublicRoute>
                <SuspenseWrapper><RegisterPage /></SuspenseWrapper>
            </PublicRoute>
        ),
    },
    {
        path: '/pos',
        element: (
            <ProtectedRoute>
                <SuspenseWrapper><POSPage /></SuspenseWrapper>
            </ProtectedRoute>
        ),
    },
    {
        path: '/',
        element: (
            <ProtectedRoute>
                <AppLayout />
            </ProtectedRoute>
        ),
        children: [
            { index: true, element: <Navigate to="/dashboard" replace /> },
            { path: 'dashboard', element: <SuspenseWrapper><DashboardPage /></SuspenseWrapper> },
            { path: 'medicines', element: <SuspenseWrapper><MedicinesIndex /></SuspenseWrapper> },
            { path: 'medicines/create', element: <SuspenseWrapper><CreateMedicine /></SuspenseWrapper> },
            { path: 'medicines/import', element: <SuspenseWrapper><ImportMedicine /></SuspenseWrapper> },
            { path: 'medicines/:id', element: <SuspenseWrapper><ShowMedicine /></SuspenseWrapper> },
            { path: 'medicines/:id/edit', element: <SuspenseWrapper><EditMedicine /></SuspenseWrapper> },
            { path: 'medicines/manufacturers', element: <SuspenseWrapper><ManufacturersIndex /></SuspenseWrapper> },
            { path: 'inventory', element: <SuspenseWrapper><InventoryIndex /></SuspenseWrapper> },
            { path: 'inventory/adjustments', element: <SuspenseWrapper><AdjustmentsPage /></SuspenseWrapper> },
            { path: 'inventory/reorder', element: <SuspenseWrapper><ReorderPage /></SuspenseWrapper> },
            { path: 'purchases', element: <SuspenseWrapper><PurchasesIndex /></SuspenseWrapper> },
            { path: 'purchases/create', element: <SuspenseWrapper><CreatePurchase /></SuspenseWrapper> },
            { path: 'purchases/:id', element: <SuspenseWrapper><ShowPurchase /></SuspenseWrapper> },
            { path: 'sales', element: <SuspenseWrapper><SalesIndex /></SuspenseWrapper> },
            { path: 'sales/:id', element: <SuspenseWrapper><ShowSale /></SuspenseWrapper> },
            { path: 'customers', element: <SuspenseWrapper><CustomersIndex /></SuspenseWrapper> },
            { path: 'customers/create', element: <SuspenseWrapper><CreateEditCustomer /></SuspenseWrapper> },
            { path: 'customers/:id', element: <SuspenseWrapper><CustomerShow /></SuspenseWrapper> },
            { path: 'customers/:id/edit', element: <SuspenseWrapper><CreateEditCustomer /></SuspenseWrapper> },
            { path: 'suppliers', element: <SuspenseWrapper><SuppliersIndex /></SuspenseWrapper> },
            { path: 'suppliers/create', element: <SuspenseWrapper><CreateEditSupplier /></SuspenseWrapper> },
            { path: 'suppliers/:id/edit', element: <SuspenseWrapper><CreateEditSupplier /></SuspenseWrapper> },
            { path: 'suppliers/:id/ledger', element: <SuspenseWrapper><SupplierLedger /></SuspenseWrapper> },
            { path: 'prescriptions', element: <SuspenseWrapper><PrescriptionsIndex /></SuspenseWrapper> },
            { path: 'prescriptions/create', element: <SuspenseWrapper><CreatePrescription /></SuspenseWrapper> },
            { path: 'prescriptions/:id', element: <SuspenseWrapper><ShowPrescription /></SuspenseWrapper> },
            { path: 'returns', element: <SuspenseWrapper><ReturnsIndex /></SuspenseWrapper> },
            { path: 'returns/create', element: <SuspenseWrapper><CreateReturn /></SuspenseWrapper> },
            { path: 'reports', element: <SuspenseWrapper><ReportsIndex /></SuspenseWrapper> },
            { path: 'reports/sales', element: <SuspenseWrapper><SalesReport /></SuspenseWrapper> },
            { path: 'reports/inventory', element: <SuspenseWrapper><InventoryReport /></SuspenseWrapper> },
            { path: 'reports/expiry', element: <SuspenseWrapper><ExpiryReport /></SuspenseWrapper> },
            { path: 'reports/profit-loss', element: <SuspenseWrapper><ProfitLossReport /></SuspenseWrapper> },
            { path: 'reports/vat', element: <SuspenseWrapper><VatReport /></SuspenseWrapper> },
            { path: 'reports/narcotics', element: <SuspenseWrapper><NarcoticsReport /></SuspenseWrapper> },
            { path: 'narcotics-register', element: <SuspenseWrapper><NarcoticsRegisterIndex /></SuspenseWrapper> },
            { path: 'settings', element: <SuspenseWrapper><SettingsPage /></SuspenseWrapper> },
            { path: 'users', element: <SuspenseWrapper><UsersIndex /></SuspenseWrapper> },
            { path: 'users/create', element: <SuspenseWrapper><CreateEditUser /></SuspenseWrapper> },
            { path: 'users/:id/edit', element: <SuspenseWrapper><CreateEditUser /></SuspenseWrapper> },
        ],
    },
    {
        path: '/super-admin/login',
        element: (
            <SuperAdminPublicRoute>
                <SuspenseWrapper><SuperAdminLogin /></SuspenseWrapper>
            </SuperAdminPublicRoute>
        ),
    },
    {
        path: '/super-admin',
        element: (
            <SuperAdminProtectedRoute>
                <SuperAdminLayout />
            </SuperAdminProtectedRoute>
        ),
        children: [
            { index: true, element: <Navigate to="/super-admin/dashboard" replace /> },
            { path: 'dashboard', element: <SuspenseWrapper><SuperAdminDashboard /></SuspenseWrapper> },
            { path: 'tenants', element: <SuspenseWrapper><SuperAdminTenants /></SuspenseWrapper> },
            { path: 'tenants/create', element: <SuspenseWrapper><SuperAdminTenantCreate /></SuspenseWrapper> },
            { path: 'tenants/:id', element: <SuspenseWrapper><SuperAdminTenantShow /></SuspenseWrapper> },
            { path: 'plans', element: <SuspenseWrapper><SuperAdminPlans /></SuspenseWrapper> },
            { path: 'subscriptions', element: <SuspenseWrapper><SuperAdminSubscriptions /></SuspenseWrapper> },
            { path: 'payments', element: <SuspenseWrapper><SuperAdminPayments /></SuspenseWrapper> },
            { path: 'landing', element: <SuspenseWrapper><SuperAdminLanding /></SuspenseWrapper> },
            { path: 'audit-logs', element: <SuspenseWrapper><SuperAdminAuditLogs /></SuspenseWrapper> },
            { path: 'settings', element: <SuspenseWrapper><SuperAdminSettings /></SuspenseWrapper> },
            { path: 'system', element: <SuspenseWrapper><SuperAdminSystem /></SuspenseWrapper> },
            { path: '*', element: <Navigate to="/super-admin/dashboard" replace /> },
        ],
    },
    {
        path: '*',
        element: <Navigate to="/dashboard" replace />,
    },
]);
