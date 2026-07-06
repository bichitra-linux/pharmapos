import { NavLink } from 'react-router-dom';
import {
    LayoutDashboard,
    ShoppingCart,
    Pill,
    Package,
    Truck,
    Receipt,
    Users,
    Building2,
    FileText,
    RotateCcw,
    BarChart3,
    Shield,
    Settings,
    UserCog,
    ChevronLeft,
    Store,
    AlertTriangle,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { useUIStore } from '@/stores/uiStore';
import { useAuthStore } from '@/stores/authStore';

const navItems = [
    { to: '/dashboard', icon: LayoutDashboard, label: 'Dashboard' },
    { to: '/pos', icon: ShoppingCart, label: 'POS' },
    { to: '/medicines', icon: Pill, label: 'Medicines' },
    { to: '/inventory', icon: Package, label: 'Inventory' },
    { to: '/inventory/reorder', icon: AlertTriangle, label: 'Reorder' },
    { to: '/purchases', icon: Truck, label: 'Purchases' },
    { to: '/sales', icon: Receipt, label: 'Sales' },
    { to: '/customers', icon: Users, label: 'Customers' },
    { to: '/suppliers', icon: Building2, label: 'Suppliers' },
    { to: '/prescriptions', icon: FileText, label: 'Prescriptions' },
    { to: '/returns', icon: RotateCcw, label: 'Returns' },
    { to: '/reports', icon: BarChart3, label: 'Reports' },
    { to: '/narcotics-register', icon: Shield, label: 'Narcotics Register' },
    { to: '/settings', icon: Settings, label: 'Settings' },
    { to: '/users', icon: UserCog, label: 'Users' },
];

export function Sidebar() {
    const sidebarOpen = useUIStore((s) => s.sidebarOpen);
    const toggleSidebar = useUIStore((s) => s.toggleSidebar);
    const user = useAuthStore((s) => s.user);

    return (
        <aside
            className={cn(
                'fixed left-0 top-0 z-40 flex h-screen flex-col border-r border-border bg-sidebar transition-all duration-300',
                sidebarOpen ? 'w-64' : 'w-16'
            )}
        >
            <div className="flex h-16 items-center justify-between border-b border-white/10 px-4">
                {sidebarOpen && (
                    <div className="flex items-center gap-2">
                        <Store className="h-6 w-6 text-primary-400" />
                        <span className="text-lg font-bold text-white">PharmaPOS</span>
                    </div>
                )}
                <button
                    onClick={toggleSidebar}
                    aria-label={sidebarOpen ? 'Collapse sidebar' : 'Expand sidebar'}
                    className="rounded p-1 text-white/70 hover:text-white focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2"
                >
                    <ChevronLeft
                        className={cn('h-5 w-5 transition-transform', !sidebarOpen && 'rotate-180')}
                    />
                </button>
            </div>

            <nav className="flex-1 overflow-y-auto px-2 py-4" aria-label="Main navigation">
                <ul className="space-y-1">
                    {navItems.map((item) => (
                        <li key={item.to}>
                            <NavLink
                                to={item.to}
                                className={({ isActive }) =>
                                    cn(
                                        'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                                        isActive
                                            ? 'bg-sidebar-active text-white'
                                            : 'text-white/70 hover:bg-sidebar-hover hover:text-white',
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
                <div className="border-t border-white/10 p-4">
                    <div className="flex items-center gap-3">
                        <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 text-sm font-medium text-white">
                            {user.name.charAt(0).toUpperCase()}
                        </div>
                        <div className="flex-1 truncate">
                            <p className="text-sm font-medium text-white">{user.name}</p>
                            <p className="text-xs text-white/70 capitalize">{user.role}</p>
                        </div>
                    </div>
                </div>
            )}
        </aside>
    );
}
