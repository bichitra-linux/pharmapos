import { useState, useEffect } from 'react';
import { Outlet } from 'react-router-dom';
import { Sidebar } from './sidebar';
import { Header } from './header';
import { useUIStore } from '@/stores/uiStore';
import { cn } from '@/lib/utils';
import { EyeOff, LogOut } from 'lucide-react';

export function AppLayout() {
    const sidebarOpen = useUIStore((s) => s.sidebarOpen);
    const toggleSidebar = useUIStore((s) => s.toggleSidebar);
    const [impersonating, setImpersonating] = useState(false);
    const [tenantName, setTenantName] = useState('');

    useEffect(() => {
        const token = localStorage.getItem('impersonation_token');
        const tenant = localStorage.getItem('impersonation_tenant');
        if (token) {
            setImpersonating(true);
            try {
                const parsed = JSON.parse(tenant || '{}');
                setTenantName(parsed.name || 'Tenant');
            } catch { setTenantName('Tenant'); }
        }
    }, []);

    const exitImpersonation = () => {
        localStorage.removeItem('impersonation_token');
        localStorage.removeItem('impersonation_tenant');
        localStorage.removeItem('impersonation_user');
        localStorage.removeItem('impersonation_original_path');
        setImpersonating(false);
        window.location.href = '/super-admin/dashboard';
    };

    return (
        <div className="min-h-screen bg-surface-muted">
            {impersonating && (
                <div className="fixed top-0 left-0 right-0 z-[60] flex items-center justify-center gap-3 bg-danger-600 px-4 py-2 text-sm text-white">
                    <EyeOff className="h-4 w-4" />
                    <span>You are viewing <strong>{tenantName}</strong> as an impersonator</span>
                    <button onClick={exitImpersonation} className="flex items-center gap-1.5 rounded bg-white/20 px-3 py-1 text-xs font-medium hover:bg-white/30">
                        <LogOut className="h-3 w-3" /> Exit
                    </button>
                </div>
            )}

            <Sidebar />

            {sidebarOpen && (
                <div
                    className={`fixed inset-0 z-30 bg-black/40 lg:hidden ${impersonating ? 'mt-10' : ''}`}
                    onClick={toggleSidebar}
                    onKeyDown={(e) => e.key === 'Escape' && toggleSidebar()}
                    tabIndex={-1}
                    aria-hidden="true"
                />
            )}

            <div
                className={cn(
                    'transition-all duration-300',
                    'ml-0 lg:ml-16',
                    sidebarOpen && 'lg:ml-64',
                    impersonating && 'mt-10'
                )}
            >
                <Header />
                <main className="p-4 lg:p-6">
                    <Outlet />
                </main>
            </div>
        </div>
    );
}
