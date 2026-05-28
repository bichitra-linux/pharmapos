import { createBrowserRouter, Navigate } from 'react-router-dom';
import { useAuthStore } from '@/stores/authStore';
import { useSuperAdminStore } from '@/stores/superAdminStore';
import { AppLayout } from '@/components/layout/app-layout';
import { SuperAdminLayout } from '@/components/layout/super-admin-layout';

import LoginPage from '@/pages/login';
import RegisterPage from '@/pages/register';
import DashboardPage from '@/pages/dashboard';
import POSPage from '@/pages/pos';

import MedicinesIndex from '@/pages/medicines/index';
import CreateMedicine from '@/pages/medicines/create';
import EditMedicine from '@/pages/medicines/edit';
import ShowMedicine from '@/pages/medicines/show';

import InventoryIndex from '@/pages/inventory/index';
import AdjustmentsPage from '@/pages/inventory/adjustments';

import PurchasesIndex from '@/pages/purchases/index';
import CreatePurchase from '@/pages/purchases/create';
import ShowPurchase from '@/pages/purchases/show';

import SalesIndex from '@/pages/sales/index';
import ShowSale from '@/pages/sales/show';

import CustomersIndex from '@/pages/customers/index';
import CreateEditCustomer from '@/pages/customers/create';

import SuppliersIndex from '@/pages/suppliers/index';
import CreateEditSupplier from '@/pages/suppliers/create';
import SupplierLedger from '@/pages/suppliers/ledger';

import PrescriptionsIndex from '@/pages/prescriptions/index';
import CreatePrescription from '@/pages/prescriptions/create';
import ShowPrescription from '@/pages/prescriptions/show';

import ReturnsIndex from '@/pages/returns/index';
import CreateReturn from '@/pages/returns/create';

import ReportsIndex from '@/pages/reports/index';
import SalesReport from '@/pages/reports/sales';
import InventoryReport from '@/pages/reports/inventory';
import ExpiryReport from '@/pages/reports/expiry';
import ProfitLossReport from '@/pages/reports/profit-loss';
import VatReport from '@/pages/reports/vat';
import NarcoticsReport from '@/pages/reports/narcotics';

import NarcoticsRegisterIndex from '@/pages/narcotics-register/index';
import SettingsPage from '@/pages/settings/index';
import UsersIndex from '@/pages/users/index';
import CreateEditUser from '@/pages/users/create';

import SuperAdminLogin from '@/pages/super-admin/login';
import SuperAdminDashboard from '@/pages/super-admin/dashboard';
import SuperAdminTenants from '@/pages/super-admin/tenants/index';
import SuperAdminTenantShow from '@/pages/super-admin/tenants/show';
import SuperAdminPlans from '@/pages/super-admin/plans/index';
import SuperAdminSubscriptions from '@/pages/super-admin/subscriptions/index';
import SuperAdminPayments from '@/pages/super-admin/payments/index';
import SuperAdminSettings from '@/pages/super-admin/settings/index';
import SuperAdminSystem from '@/pages/super-admin/system/index';

function ProtectedRoute({ children }: { children: React.ReactNode }) {
    const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
    if (!isAuthenticated) {
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
                <LoginPage />
            </PublicRoute>
        ),
    },
    {
        path: '/register',
        element: (
            <PublicRoute>
                <RegisterPage />
            </PublicRoute>
        ),
    },
    {
        path: '/pos',
        element: (
            <ProtectedRoute>
                <POSPage />
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
            { path: 'dashboard', element: <DashboardPage /> },
            { path: 'medicines', element: <MedicinesIndex /> },
            { path: 'medicines/create', element: <CreateMedicine /> },
            { path: 'medicines/:id', element: <ShowMedicine /> },
            { path: 'medicines/:id/edit', element: <EditMedicine /> },
            { path: 'inventory', element: <InventoryIndex /> },
            { path: 'inventory/adjustments', element: <AdjustmentsPage /> },
            { path: 'purchases', element: <PurchasesIndex /> },
            { path: 'purchases/create', element: <CreatePurchase /> },
            { path: 'purchases/:id', element: <ShowPurchase /> },
            { path: 'sales', element: <SalesIndex /> },
            { path: 'sales/:id', element: <ShowSale /> },
            { path: 'customers', element: <CustomersIndex /> },
            { path: 'customers/create', element: <CreateEditCustomer /> },
            { path: 'customers/:id/edit', element: <CreateEditCustomer /> },
            { path: 'suppliers', element: <SuppliersIndex /> },
            { path: 'suppliers/create', element: <CreateEditSupplier /> },
            { path: 'suppliers/:id/edit', element: <CreateEditSupplier /> },
            { path: 'suppliers/:id/ledger', element: <SupplierLedger /> },
            { path: 'prescriptions', element: <PrescriptionsIndex /> },
            { path: 'prescriptions/create', element: <CreatePrescription /> },
            { path: 'prescriptions/:id', element: <ShowPrescription /> },
            { path: 'returns', element: <ReturnsIndex /> },
            { path: 'returns/create', element: <CreateReturn /> },
            { path: 'reports', element: <ReportsIndex /> },
            { path: 'reports/sales', element: <SalesReport /> },
            { path: 'reports/inventory', element: <InventoryReport /> },
            { path: 'reports/expiry', element: <ExpiryReport /> },
            { path: 'reports/profit-loss', element: <ProfitLossReport /> },
            { path: 'reports/vat', element: <VatReport /> },
            { path: 'reports/narcotics', element: <NarcoticsReport /> },
            { path: 'narcotics-register', element: <NarcoticsRegisterIndex /> },
            { path: 'settings', element: <SettingsPage /> },
            { path: 'users', element: <UsersIndex /> },
            { path: 'users/create', element: <CreateEditUser /> },
            { path: 'users/:id/edit', element: <CreateEditUser /> },
        ],
    },
    {
        path: '/super-admin/login',
        element: (
            <SuperAdminPublicRoute>
                <SuperAdminLogin />
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
            { path: 'dashboard', element: <SuperAdminDashboard /> },
            { path: 'tenants', element: <SuperAdminTenants /> },
            { path: 'tenants/:id', element: <SuperAdminTenantShow /> },
            { path: 'plans', element: <SuperAdminPlans /> },
            { path: 'subscriptions', element: <SuperAdminSubscriptions /> },
            { path: 'payments', element: <SuperAdminPayments /> },
            { path: 'settings', element: <SuperAdminSettings /> },
            { path: 'system', element: <SuperAdminSystem /> },
            { path: '*', element: <Navigate to="/super-admin/dashboard" replace /> },
        ],
    },
    {
        path: '*',
        element: <Navigate to="/dashboard" replace />,
    },
]);
