import { NavLink, Outlet, useNavigate } from 'react-router-dom';
import {
    LayoutDashboard,
    Building2,
    CreditCard,
    Repeat,
    DollarSign,
    Settings,
    Server,
    ChevronLeft,
    Shield,
    LogOut,
    User,
    ChevronDown,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { useSuperAdminStore } from '@/stores/superAdminStore';
import { useUIStore } from '@/stores/uiStore';
import { DropdownMenu, DropdownMenuItem, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import { superAdminService } from '@/services/super-admin';

const navItems = [
    { to: '/super-admin/dashboard', icon: LayoutDashboard, label: 'Dashboard' },
    { to: '/super-admin/tenants', icon: Building2, label: 'Tenants' },
    { to: '/super-admin/plans', icon: CreditCard, label: 'Plans' },
    { to: '/super-admin/subscriptions', icon: Repeat, label: 'Subscriptions' },
    { to: '/super-admin/payments', icon: DollarSign, label: 'Payments' },
    { to: '/super-admin/settings', icon: Settings, label: 'Settings' },
    { to: '/super-admin/system', icon: Server, label: 'System' },
];

export function SuperAdminLayout() {
    const sidebarOpen = useUIStore((s) => s.sidebarOpen);
    const toggleSidebar = useUIStore((s) => s.toggleSidebar);
    const user = useSuperAdminStore((s) => s.user);
    const logoutStore = useSuperAdminStore((s) => s.logout);
    const navigate = useNavigate();

    const handleLogout = async () => {
        try {
            await superAdminService.logout();
        } catch {
            // ignore error, still logout locally
        }
        logoutStore();
        navigate('/super-admin/login');
    };

    return (
        <div className="min-h-screen bg-gray-50">
            <aside
                className={cn(
                    'fixed left-0 top-0 z-40 flex h-screen flex-col border-r border-indigo-900/30 bg-gradient-to-b from-indigo-950 to-gray-950 transition-all duration-300',
                    sidebarOpen ? 'w-64' : 'w-16'
                )}
            >
                <div className="flex h-16 items-center justify-between border-b border-indigo-800/30 px-4">
                    {sidebarOpen && (
                        <div className="flex items-center gap-2">
                            <Shield className="h-6 w-6 text-indigo-400" />
                            <span className="text-lg font-bold text-white">Super Admin</span>
                        </div>
                    )}
                    <button
                        onClick={toggleSidebar}
                        aria-label={sidebarOpen ? 'Collapse sidebar' : 'Expand sidebar'}
                        className="rounded p-1 text-gray-400 hover:text-white"
                    >
                        <ChevronLeft
                            className={cn('h-5 w-5 transition-transform', !sidebarOpen && 'rotate-180')}
                            aria-hidden="true"
                        />
                    </button>
                </div>

                <nav className="flex-1 overflow-y-auto px-2 py-4">
                    <ul className="space-y-1">
                        {navItems.map((item) => (
                            <li key={item.to}>
                                <NavLink
                                    to={item.to}
                                    className={({ isActive }) =>
                                        cn(
                                            'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                                            isActive
                                                ? 'bg-indigo-600 text-white'
                                                : 'text-gray-300 hover:bg-indigo-800/40 hover:text-white',
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
                    <div className="border-t border-indigo-800/30 p-4">
                        <div className="flex items-center gap-3">
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-sm font-medium text-white">
                                {user.name.charAt(0).toUpperCase()}
                            </div>
                            <div className="flex-1 truncate">
                                <p className="text-sm font-medium text-white">{user.name}</p>
                                <p className="text-xs text-gray-400 capitalize">{user.role.replace('_', ' ')}</p>
                            </div>
                        </div>
                    </div>
                )}
            </aside>

            {sidebarOpen && (
                <div
                    className="fixed inset-0 z-30 bg-black/40 lg:hidden"
                    onClick={toggleSidebar}
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
                <header className="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4 lg:px-6">
                    <div className="flex items-center gap-3">
                        <div className="rounded-md bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                            Platform Management
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <DropdownMenu
                            trigger={
                                <button className="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-gray-100">
                                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 font-medium">
                                        {user?.name?.charAt(0).toUpperCase()}
                                    </div>
                                    <span className="hidden font-medium md:inline">{user?.name}</span>
                                    <ChevronDown className="h-4 w-4 text-gray-400" />
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
