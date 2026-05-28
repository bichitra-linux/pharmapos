import { useState, useEffect, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import {
    Menu,
    Search,
    Bell,
    User,
    LogOut,
    Settings,
    ChevronDown,
} from 'lucide-react';
import { useUIStore } from '@/stores/uiStore';
import { useAuthStore } from '@/stores/authStore';
import { useAuth } from '@/hooks/useAuth';
import { DropdownMenu, DropdownMenuItem, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import { medicinesService } from '@/services/medicines';
import { useDebounce } from '@/hooks/useDebounce';
import type { Medicine } from '@/types';

export function Header() {
    const { toggleSidebar } = useUIStore();
    const user = useAuthStore((s) => s.user);
    const { logout } = useAuth();
    const navigate = useNavigate();
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState<Medicine[]>([]);
    const [showSearch, setShowSearch] = useState(false);
    const debouncedQuery = useDebounce(searchQuery, 300);
    const abortRef = useRef<AbortController | null>(null);

    useEffect(() => {
        if (debouncedQuery.length < 2) {
            setSearchResults([]);
            setShowSearch(false);
            return;
        }

        abortRef.current?.abort();
        abortRef.current = new AbortController();

        medicinesService.search(debouncedQuery)
            .then((res) => {
                setSearchResults(res.data);
                setShowSearch(true);
            })
            .catch(() => {
                setSearchResults([]);
            });

        return () => abortRef.current?.abort();
    }, [debouncedQuery]);

    return (
        <header className="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4 lg:px-6">
            <div className="flex items-center gap-4">
                <button onClick={toggleSidebar} className="lg:hidden" aria-label="Toggle menu">
                    <Menu className="h-6 w-6 text-gray-500" aria-hidden="true" />
                </button>

                <div className="relative hidden md:block">
                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" aria-hidden="true" />
                    <input
                        type="text"
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        onFocus={() => searchResults.length > 0 && setShowSearch(true)}
                        onBlur={() => setTimeout(() => setShowSearch(false), 200)}
                        placeholder="Search medicines..."
                        aria-label="Search medicines"
                        className="w-80 rounded-lg border border-gray-300 bg-gray-50 py-2 pl-10 pr-4 text-sm focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-primary-500"
                    />
                    {showSearch && searchResults.length > 0 && (
                        <div className="absolute top-full left-0 mt-1 w-full rounded-lg border border-gray-200 bg-white py-1 shadow-lg">
                            {searchResults.map((med) => (
                                <button
                                    key={med.id}
                                    onClick={() => {
                                        navigate(`/medicines/${med.id}`);
                                        setShowSearch(false);
                                        setSearchQuery('');
                                    }}
                                    className="flex w-full items-center justify-between px-4 py-2 text-sm hover:bg-gray-50"
                                >
                                    <div>
                                        <p className="font-medium">{med.brand_name}</p>
                                        <p className="text-xs text-gray-500">{med.generic_name}</p>
                                    </div>
                                    <span className="text-xs text-gray-400">{med.dosage_form}</span>
                                </button>
                            ))}
                        </div>
                    )}
                </div>
            </div>

            <div className="flex items-center gap-3">
                <button className="relative rounded-full p-2 text-gray-500 hover:bg-gray-100" aria-label="Notifications">
                    <Bell className="h-5 w-5" aria-hidden="true" />
                    <span className="absolute right-1 top-1 h-2 w-2 rounded-full bg-danger-500" aria-label="New notifications" />
                </button>

                <DropdownMenu
                    trigger={
                        <button className="flex items-center gap-2 rounded-lg px-3 py-2 text-sm hover:bg-gray-100">
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 text-primary-700 font-medium">
                                {user?.name?.charAt(0).toUpperCase()}
                            </div>
                            <span className="hidden font-medium md:inline">{user?.name}</span>
                            <ChevronDown className="h-4 w-4 text-gray-400" />
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
