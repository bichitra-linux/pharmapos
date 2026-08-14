import { useState, useEffect, useRef } from 'react';
import { NavLink, Outlet, useNavigate } from 'react-router-dom';
import {
    LayoutDashboard,
    Building2,
    CreditCard,
    Repeat,
    DollarSign,
    Settings,
    Server,
    Layout,
    ClipboardList,
    ChevronLeft,
    Shield,
    LogOut,
    User,
    ChevronDown,
    AlertTriangle,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { useSuperAdminStore } from '@/stores/superAdminStore';
import { DropdownMenu, DropdownMenuItem, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import { superAdminService } from '@/services/super-admin';

const navItems = [
    { to: '/super-admin/dashboard', icon: LayoutDashboard, label: 'Dashboard' },
    { to: '/super-admin/tenants', icon: Building2, label: 'Tenants' },
    { to: '/super-admin/plans', icon: CreditCard, label: 'Plans' },
    { to: '/super-admin/subscriptions', icon: Repeat, label: 'Subscriptions' },
    { to: '/super-admin/payments', icon: DollarSign, label: 'Payments' },
    { to: '/super-admin/landing', icon: Layout, label: 'Landing Page' },
    { to: '/super-admin/audit-logs', icon: ClipboardList, label: 'Audit Logs' },
    { to: '/super-admin/settings', icon: Settings, label: 'Settings' },
    { to: '/super-admin/system', icon: Server, label: 'System' },
];

export function SuperAdminLayout() {
    const [sidebarOpen, setSidebarOpen] = useState(true);
    const toggleSidebar = () => setSidebarOpen((prev) => !prev);
    const user = useSuperAdminStore((s) => s.user);
    const logoutStore = useSuperAdminStore((s) => s.logout);
    const navigate = useNavigate();
    const [systemDegraded, setSystemDegraded] = useState(false);
    const healthInterval = useRef<any>(null);

    useEffect(() => {
        const check = async () => {
            try {
                const res = await superAdminService.getHealth();
                const status = res?.data?.status;
                setSystemDegraded(Boolean(status) && status !== 'healthy');
            } catch (err: any) {
                // health endpoint returns 503 with the payload when degraded
                const status = err?.response?.data?.data?.status;
                setSystemDegraded(Boolean(status) && status !== 'healthy');
            }
        };
        check();
        healthInterval.current = setInterval(check, 30000);
        return () => { if (healthInterval.current) clearInterval(healthInterval.current); };
    }, []);

    const handleLogout = async () => {
        try {
            await superAdminService.logout();
        } catch {
        }
        logoutStore();
        navigate('/super-admin/login', { replace: true });
    };

    return (
        <div className="min-h-screen bg-surface-muted">
            {systemDegraded && (
                <div className="flex items-center justify-center gap-2 bg-danger-600 px-4 py-2 text-sm text-white">
                    <AlertTriangle className="h-4 w-4" />
                    System degraded — some services may be unavailable
                </div>
            )}
            <aside
                className={cn(
                    'fixed left-0 top-0 z-40 flex h-screen flex-col border-r border-primary-900/30 bg-gradient-to-b from-primary-950 to-primary-950/90 transition-all duration-300',
                    sidebarOpen ? 'w-64' : 'w-16',
                    systemDegraded && 'top-10'
                )}
            >
                <div className="flex h-16 items-center justify-between border-b border-primary-800/30 px-4">
                    {sidebarOpen && (
                        <div className="flex items-center gap-2 text-white">
                            <Shield className="h-5 w-5 text-primary-300" aria-hidden="true" />
                            <span className="text-sm font-semibold uppercase tracking-wide">Super Admin</span>
                        </div>
                    )}
                    <button
                        onClick={toggleSidebar}
                        aria-label={sidebarOpen ? 'Collapse sidebar' : 'Expand sidebar'}
                        className="rounded p-1 text-text-muted hover:text-white focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2"
                    >
                        <ChevronLeft
                            className={cn('h-5 w-5 transition-transform', !sidebarOpen && 'rotate-180')}
                            aria-hidden="true"
                        />
                    </button>
                </div>

                <nav aria-label="Super admin navigation" className="flex-1 overflow-y-auto px-2 py-4">
                    <ul className="space-y-1">
                        {navItems.map((item) => (
                            <li key={item.to}>
                                <NavLink
                                    to={item.to}
                                    className={({ isActive }) =>
                                        cn(
                                            'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                                            isActive
                                                ? 'bg-primary-600 text-white'
                                                : 'text-text-muted hover:bg-primary-800/40 hover:text-white',
                                            !sidebarOpen && 'justify-center px-2'
                                        )
                                    }
                                    title={item.label}
                                >
                                    <item.icon className="h-5 w-5 shrink-0" />
                                    {sidebarOpen && <span>{item.label}</span>}
                                </NavLink>
                            </li>
                        ))}
                    </ul>
                </nav>

                {sidebarOpen && user && (
                    <div className="border-t border-primary-800/30 p-4">
                        <div className="flex items-center gap-3">
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 text-sm font-medium text-white">
                                {user.name.charAt(0).toUpperCase()}
                            </div>
                            <div className="flex-1 truncate">
                                <p className="text-sm font-medium text-white">{user.name}</p>
                                <p className="text-xs text-text-muted capitalize">{user.role.replace('_', ' ')}</p>
                            </div>
                        </div>
                    </div>
                )}
            </aside>

            {sidebarOpen && (
                <div
                    className={`fixed inset-0 z-30 bg-black/40 lg:hidden ${systemDegraded ? 'top-10' : ''}`}
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
                    sidebarOpen && 'lg:ml-64'
                )}
            >
                <header className={`sticky top-0 z-30 flex h-16 items-center justify-between border-b border-border bg-surface px-4 lg:px-6 ${systemDegraded ? 'top-10' : ''}`}>
                    <div className="flex items-center gap-3">
                        <div className="rounded-md bg-primary-100 px-2.5 py-1 text-xs font-semibold text-primary-700">
                            Platform Management
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <DropdownMenu
                            trigger={
                                <button aria-label="User menu" className="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-surface-muted">
                                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 text-primary-700 font-medium">
                                        {user?.name?.charAt(0).toUpperCase()}
                                    </div>
                                    <span className="hidden font-medium md:inline">{user?.name}</span>
                                    <ChevronDown className="h-4 w-4 text-text-muted" />
                                </button>
                            }
                        >
                            <DropdownMenuItem onClick={() => navigate('/super-admin/settings')}>
                                <User className="mr-2 h-4 w-4" /> Profile
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => navigate('/super-admin/settings')}>
                                <Settings className="mr-2 h-4 w-4" /> Settings
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem onClick={handleLogout} destructive>
                                <LogOut className="mr-2 h-4 w-4" /> Logout
                            </DropdownMenuItem>
                        </DropdownMenu>
                    </div>
                </header>

                <main className="p-4 lg:p-6">
                    <Outlet />
                </main>
            </div>
        </div>
    );
}
