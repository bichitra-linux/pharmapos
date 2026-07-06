import { useState, useEffect, useRef } from 'react';
import { ProductSearch } from './product-search';
import { ProductGrid } from './product-grid';
import { Cart } from './cart';
import { CartSummary } from './cart-summary';
import { CustomerSelect } from './customer-select';
import { CustomerHistoryPanel } from './customer-history-panel';
import { PrescriptionUpload } from './prescription-upload';
import { HeldSalesDrawer } from './held-sales-drawer';
import { PosFooter } from './pos-footer';
import { useCartStore } from '@/stores/cartStore';
import { useAuthStore } from '@/stores/authStore';
import { useKeyboardShortcuts } from '@/hooks/useKeyboardShortcuts';
import { useOnlineStatus } from '@/hooks/use-online-status';
import { cn } from '@/lib/utils';
import { useNavigate } from 'react-router-dom';
import { ArrowLeft, ShoppingCart, X, Clock, Wifi, WifiOff, Search, User, CreditCard } from 'lucide-react';

export function POSLayout() {
    const navigate = useNavigate();
    const itemCount = useCartStore((s) => s.items.length);
    const items = useCartStore((s) => s.items);
    const user = useAuthStore((s) => s.user);
    const [showCart, setShowCart] = useState(false);
    const [showHeldSales, setShowHeldSales] = useState(false);
    const [activeMobileTab, setActiveMobileTab] = useState<'search' | 'cart' | 'held' | 'profile'>('search');
    const searchRef = useRef<HTMLInputElement>(null);
    const isOnline = useOnlineStatus();

    useKeyboardShortcuts({
        'f2': () => searchRef.current?.focus(),
        'f4': () => {
            if (items.length > 0) {
                const btn = document.querySelector('[data-pay-btn]') as HTMLButtonElement;
                btn?.click();
            }
        },
        'escape': () => setShowCart(false),
    });

    // Barcode scan handler
    useEffect(() => {
        let buf = '';
        let timer: ReturnType<typeof setTimeout> | null = null;
        const handler = (e: KeyboardEvent) => {
            if (e.target !== searchRef.current) return;
            if (e.key === 'Enter' && buf.length > 0) {
                const ev = new Event('input', { bubbles: true });
                (e.target as HTMLInputElement).dispatchEvent(ev);
                buf = '';
                return;
            }
            if (e.key.length === 1) { buf += e.key; if (timer) clearTimeout(timer); timer = setTimeout(() => { buf = ''; }, 100); }
        };
        window.addEventListener('keydown', handler);
        return () => { window.removeEventListener('keydown', handler); if (timer) clearTimeout(timer); };
    }, []);

    const hasRestricted = items.some((i) => ['h', 'h1', 'x'].includes(i.name.toLowerCase().slice(0, 2)));

    return (
        <div className="fixed inset-0 z-50 flex flex-col bg-surface-muted">
            {/* Header */}
            <header className="flex h-14 items-center justify-between border-b border-border bg-surface px-3 lg:px-4">
                <div className="flex items-center gap-3">
                    <button onClick={() => navigate('/dashboard')} className="rounded p-1.5 text-text-muted hover:bg-surface-muted" aria-label="Back">
                        <ArrowLeft className="h-5 w-5" />
                    </button>
                    <h1 className="text-lg font-bold hidden sm:inline">POS</h1>
                    {/* Cashier chip */}
                    {user && (
                        <div className="hidden md:flex items-center gap-2 rounded-full bg-surface-muted px-3 py-1 text-xs">
                            <div className="flex h-6 w-6 items-center justify-center rounded-full bg-primary-100 text-primary-700 font-medium text-xs">
                                {user.name.charAt(0).toUpperCase()}
                            </div>
                            <span className="font-medium">{user.name.split(' ')[0]}</span>
                        </div>
                    )}
                </div>

                {/* Center */}
                <div className="hidden md:flex items-center gap-4">
                    <div className="flex items-center gap-1.5 text-xs text-text-muted">
                        <span className={cn('h-2 w-2 rounded-full', isOnline ? 'bg-success-500 animate-pulse' : 'bg-danger-500')} />
                        {isOnline ? 'Live' : 'Offline'}
                    </div>
                    <span className="text-xs text-text-muted">F2: Search &bull; F4: Pay</span>
                </div>

                {/* Right */}
                <div className="flex items-center gap-2">
                    {/* Online/offline indicator mobile */}
                    <div className="md:hidden">
                        {isOnline ? <Wifi className="h-4 w-4 text-success-500" /> : <WifiOff className="h-4 w-4 text-danger-500" />}
                    </div>
                    <div className="hidden md:flex items-center gap-2">
                        <CustomerSelect />
                        <PrescriptionUpload />
                        {hasRestricted && !useCartStore.getState().prescription_id && (
                            <span className="text-[10px] px-1.5 py-0.5 rounded-full bg-danger-100 text-danger-700 font-semibold">Rx</span>
                        )}
                        <button
                            onClick={() => setShowHeldSales(!showHeldSales)}
                            className="relative rounded-lg border border-border p-2 text-text-muted hover:bg-surface-muted"
                            aria-label="Held sales"
                        >
                            <Clock className="h-4 w-4" />
                        </button>
                    </div>
                    <button
                        onClick={() => setShowCart(!showCart)}
                        className="relative rounded-lg bg-primary-600 p-2 text-white md:hidden"
                        aria-label={`Cart, ${itemCount} items`}
                    >
                        <ShoppingCart className="h-5 w-5" />
                        {itemCount > 0 && (
                            <span className="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-danger-500 text-[10px] text-white">{itemCount}</span>
                        )}
                    </button>
                </div>
            </header>

            {/* Offline banner */}
            {!isOnline && (
                <div className="flex items-center justify-center gap-2 bg-warning-100 px-4 py-1 text-xs text-warning-800">
                    <WifiOff className="h-3 w-3" /> Working offline — sales will still process
                </div>
            )}

            {/* Customer history panel (when customer selected) */}
            <div className="hidden md:block">
                <CustomerHistoryPanel />
            </div>

            {/* Main layout */}
            <div className="flex flex-1 overflow-hidden">
                {/* Left: Search + Grid */}
                <div className="flex flex-1 flex-col overflow-hidden border-r border-border">
                    <div className="border-b border-border bg-surface p-3">
                        <div className="md:hidden mb-3">
                            <CustomerSelect />
                        </div>
                        <ProductSearch ref={searchRef} />
                    </div>
                    <div className="flex-1 overflow-y-auto p-3">
                        <ProductGrid />
                    </div>
                </div>

                {/* Held sales drawer */}
                <HeldSalesDrawer open={showHeldSales} onClose={() => setShowHeldSales(false)} />

                {/* Right: Cart */}
                <div className={cn('flex flex-col bg-surface', 'hidden md:flex md:w-96')}>
                    <div className="flex-1 overflow-y-auto">
                        <Cart />
                    </div>
                    <CartSummary />
                </div>

                {/* Mobile cart drawer */}
                {showCart && (
                    <div
                        role="dialog" aria-modal="true" aria-label="Cart"
                        className="fixed inset-0 z-50 flex md:hidden"
                        onKeyDown={(e) => { if (e.key === 'Escape') setShowCart(false); }}
                    >
                        <div className="fixed inset-0 bg-black/40" onClick={() => setShowCart(false)} aria-hidden="true" />
                        <div className="ml-auto flex w-full max-w-sm flex-col bg-surface shadow-xl">
                            <div className="flex items-center justify-between border-b border-border p-4">
                                <h2 className="font-semibold">Cart ({itemCount})</h2>
                                <button onClick={() => setShowCart(false)} className="rounded p-1 hover:bg-surface-muted" aria-label="Close cart">
                                    <X className="h-5 w-5" />
                                </button>
                            </div>
                            <div className="flex-1 overflow-y-auto"><Cart /></div>
                            <CartSummary />
                        </div>
                    </div>
                )}
            </div>

            {/* Mobile bottom tab bar */}
            <div className="md:hidden flex border-t border-border bg-surface">
                {[
                    { key: 'search', icon: Search, label: 'Search' },
                    { key: 'cart', icon: ShoppingCart, label: `Cart${itemCount ? ` (${itemCount})` : ''}` },
                    { key: 'held', icon: Clock, label: 'Held' },
                    { key: 'profile', icon: User, label: user?.name?.split(' ')[0] || 'User' },
                ].map((tab) => (
                    <button
                        key={tab.key}
                        onClick={() => {
                            if (tab.key === 'cart') { setShowCart(!showCart); return; }
                            if (tab.key === 'held') { setShowHeldSales(!showHeldSales); return; }
                            setActiveMobileTab(tab.key as any);
                            if (tab.key === 'search') searchRef.current?.focus();
                        }}
                        className={`flex flex-1 flex-col items-center py-2 text-[10px] ${activeMobileTab === tab.key ? 'text-primary-600' : 'text-text-muted'}`}
                    >
                        <tab.icon className="h-5 w-5" />
                        <span>{tab.label}</span>
                    </button>
                ))}
            </div>

            {/* Footer */}
            <PosFooter />
        </div>
    );
}
