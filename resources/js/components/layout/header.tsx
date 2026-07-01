import { useState, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import {
    Menu,
    Search,
    X,
    Bell,
    User,
    LogOut,
    Settings,
    ChevronDown,
} from 'lucide-react';
import { useQuery } from '@tanstack/react-query';
import { useUIStore } from '@/stores/uiStore';
import { useAuthStore } from '@/stores/authStore';
import { useAuth } from '@/hooks/useAuth';
import { DropdownMenu, DropdownMenuItem, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import { medicinesService } from '@/services/medicines';
import { useDebounce } from '@/hooks/useDebounce';

export function Header() {
    const toggleSidebar = useUIStore((s) => s.toggleSidebar);
    const user = useAuthStore((s) => s.user);
    const { logout } = useAuth();
    const navigate = useNavigate();
    const [searchQuery, setSearchQuery] = useState('');
    const [showSearch, setShowSearch] = useState(false);
    const [showMobileSearch, setShowMobileSearch] = useState(false);
    const [showNotifications, setShowNotifications] = useState(false);
    const debouncedQuery = useDebounce(searchQuery, 300);
    const mobileSearchInputRef = useRef<HTMLInputElement>(null);

    const { data: searchResults } = useQuery({
        queryKey: ['medicines', 'search', debouncedQuery],
        queryFn: () => medicinesService.search(debouncedQuery),
        enabled: debouncedQuery.length >= 2,
        staleTime: 30_000,
        select: (res) => res.data,
    });

    return (
        <header className="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-border bg-surface px-4 lg:px-6">
            <div className="flex items-center gap-4">
                <button onClick={toggleSidebar} className="lg:hidden" aria-label="Toggle menu">
                    <Menu className="h-6 w-6 text-text-muted" aria-hidden="true" />
                </button>

                <div className="relative hidden md:block">
                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-text-muted" aria-hidden="true" />
                    <input
                        type="text"
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        onFocus={() => (searchResults?.length ?? 0) > 0 && setShowSearch(true)}
                        onBlur={() => setTimeout(() => setShowSearch(false), 200)}
                        placeholder="Search medicines..."
                        aria-label="Search medicines"
                        className="w-80 rounded-lg border border-border bg-surface-muted py-2 pl-10 pr-4 text-sm focus:border-primary-500 focus:bg-surface focus:outline-none focus:ring-1 focus:ring-primary-500"
                    />
                    {showSearch && (searchResults?.length ?? 0) > 0 && (
                        <div className="absolute top-full left-0 mt-1 w-full rounded-lg border border-border bg-surface py-1 shadow-lg">
                            {searchResults!.map((med) => (
                                <button
                                    key={med.id}
                                    onMouseDown={(e) => {
                                        e.preventDefault();
                                        navigate(`/medicines/${med.id}`);
                                        setShowSearch(false);
                                        setSearchQuery('');
                                    }}
                                    className="flex w-full items-center justify-between px-4 py-2 text-sm hover:bg-surface-muted"
                                >
                                    <div>
                                        <p className="font-medium">{med.brand_name}</p>
                                        <p className="text-xs text-text-muted">{med.generic_name}</p>
                                    </div>
                                    <span className="text-xs text-text-muted">{med.dosage_form}</span>
                                </button>
                            ))}
                        </div>
                    )}
                </div>
            </div>

            <div className="flex items-center gap-3">
                <button
                    onClick={() => {
                        setShowMobileSearch(true);
                        setTimeout(() => mobileSearchInputRef.current?.focus(), 100);
                    }}
                    className="rounded-lg p-2 text-text-muted hover:bg-surface-muted md:hidden"
                    aria-label="Search"
                >
                    <Search className="h-5 w-5" aria-hidden="true" />
                </button>

                {showMobileSearch && (
                    <div
                        role="dialog"
                        aria-modal="true"
                        aria-label="Search medicines"
                        className="fixed inset-0 z-50 flex items-start bg-surface md:hidden"
                        onKeyDown={(e) => {
                            if (e.key === 'Escape') {
                                setShowMobileSearch(false);
                                setSearchQuery('');
                            }
                            if (e.key === 'Tab') {
                                const container = e.currentTarget;
                                const focusable = container.querySelectorAll<HTMLElement>(
                                    'input, button, [tabindex]:not([tabindex="-1"])'
                                );
                                if (focusable.length === 0) return;
                                const first = focusable[0];
                                const last = focusable[focusable.length - 1];
                                if (e.shiftKey && document.activeElement === first) {
                                    e.preventDefault();
                                    last.focus();
                                } else if (!e.shiftKey && document.activeElement === last) {
                                    e.preventDefault();
                                    first.focus();
                                }
                            }
                        }}
                    >
                        <div className="flex w-full items-center gap-2 border-b border-border p-3">
                            <Search className="h-5 w-5 shrink-0 text-text-muted" aria-hidden="true" />
                            <input
                                ref={mobileSearchInputRef}
                                type="text"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                placeholder="Search medicines..."
                                aria-label="Search medicines"
                                className="flex-1 bg-transparent text-sm outline-none"
                            />
                            <button
                                onClick={() => {
                                    setShowMobileSearch(false);
                            setSearchQuery('');
                        }}
                        aria-label="Close search"
                        className="rounded p-1 text-text-muted hover:text-text"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>
                {(searchResults?.length ?? 0) > 0 && (
                    <div className="w-full overflow-y-auto p-2">
                        {searchResults!.map((med) => (
                                    <button
                                        key={med.id}
                                        onClick={() => {
                                            navigate(`/medicines/${med.id}`);
                                            setShowMobileSearch(false);
                                            setSearchQuery('');
                                        }}
                                        className="flex w-full items-center justify-between rounded-lg px-4 py-3 text-left hover:bg-surface-muted"
                                    >
                                        <div>
                                            <p className="text-sm font-medium">{med.brand_name}</p>
                                            <p className="text-xs text-text-muted">{med.generic_name}</p>
                                        </div>
                                        <span className="text-xs text-text-muted">{med.dosage_form}</span>
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>
                )}

                <div className="relative">
                    <button
                        onClick={() => setShowNotifications(!showNotifications)}
                        className="relative rounded-full p-2 text-text-muted hover:bg-surface-muted"
                        aria-label="Notifications"
                    >
                        <Bell className="h-5 w-5" aria-hidden="true" />
                        <span className="absolute right-1 top-1 h-2 w-2 rounded-full bg-danger-500" aria-hidden="true" />
                    </button>
                    {showNotifications && (
                        <div role="menu" className="absolute right-0 top-full z-10 mt-1 w-64 rounded-lg border border-border bg-surface py-2 shadow-lg">
                            <div role="menuitem" className="px-4 py-2 text-sm text-text-muted">No new notifications</div>
                        </div>
                    )}
                </div>

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
                    <DropdownMenuItem onClick={() => navigate('/settings')}>
                        <User className="mr-2 h-4 w-4" /> Profile
                    </DropdownMenuItem>
                    <DropdownMenuItem onClick={() => navigate('/settings')}>
                        <Settings className="mr-2 h-4 w-4" /> Settings
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem onClick={logout} destructive>
                        <LogOut className="mr-2 h-4 w-4" /> Logout
                    </DropdownMenuItem>
                </DropdownMenu>
            </div>
        </header>
    );
}
