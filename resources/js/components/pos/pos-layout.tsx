import { useState } from 'react';
import { ProductSearch } from './product-search';
import { ProductGrid } from './product-grid';
import { Cart } from './cart';
import { CartSummary } from './cart-summary';
import { CustomerSelect } from './customer-select';
import { PrescriptionUpload } from './prescription-upload';
import { useCartStore } from '@/stores/cartStore';
import { useNavigate } from 'react-router-dom';
import { ArrowLeft, ShoppingCart, X } from 'lucide-react';

export function POSLayout() {
    const navigate = useNavigate();
    const itemCount = useCartStore((s) => s.items.length);
    const [showCart, setShowCart] = useState(false);

    return (
        <div className="fixed inset-0 z-50 flex flex-col bg-gray-100">
            <div className="flex h-14 items-center justify-between border-b border-gray-200 bg-white px-4">
                <div className="flex items-center gap-4">
                    <button
                        onClick={() => navigate('/dashboard')}
                        className="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900"
                        aria-label="Back to dashboard"
                    >
                        <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                        <span className="hidden sm:inline">Back</span>
                    </button>
                    <h1 className="text-lg font-bold">Point of Sale</h1>
                </div>
                <div className="flex items-center gap-3">
                    <div className="hidden md:flex items-center gap-3">
                        <CustomerSelect />
                        <PrescriptionUpload />
                    </div>
                    <button
                        onClick={() => setShowCart(!showCart)}
                        className="relative rounded-lg bg-primary-600 p-2 text-white md:hidden"
                        aria-label={`Cart, ${itemCount} items`}
                    >
                        <ShoppingCart className="h-5 w-5" aria-hidden="true" />
                        {itemCount > 0 && (
                            <span className="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-danger-500 text-xs text-white">
                                {itemCount}
                            </span>
                        )}
                    </button>
                </div>
            </div>

            <div className="flex flex-1 overflow-hidden">
                <div className="flex flex-1 flex-col overflow-hidden border-r border-gray-200">
                    <div className="border-b border-gray-200 bg-white p-3">
                        <div className="md:hidden mb-3">
                            <CustomerSelect />
                        </div>
                        <ProductSearch />
                    </div>
                    <div className="flex-1 overflow-y-auto p-3">
                        <ProductGrid />
                    </div>
                </div>

                <div className={cn(
                    'flex flex-col bg-white',
                    'hidden md:flex md:w-96',
                )}>
                    <div className="flex-1 overflow-y-auto">
                        <Cart />
                    </div>
                    <CartSummary />
                </div>

                {showCart && (
                    <div className="fixed inset-0 z-50 flex md:hidden">
                        <div
                            className="fixed inset-0 bg-black/40"
                            onClick={() => setShowCart(false)}
                            aria-hidden="true"
                        />
                        <div className="ml-auto flex w-full max-w-sm flex-col bg-white shadow-xl">
                            <div className="flex items-center justify-between border-b border-gray-200 p-4">
                                <h2 className="font-semibold">Cart ({itemCount})</h2>
                                <button
                                    onClick={() => setShowCart(false)}
                                    aria-label="Close cart"
                                    className="rounded p-1 hover:bg-gray-100"
                                >
                                    <X className="h-5 w-5" aria-hidden="true" />
                                </button>
                            </div>
                            <div className="flex-1 overflow-y-auto">
                                <Cart />
                            </div>
                            <CartSummary />
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}

function cn(...classes: (string | boolean | undefined)[]) {
    return classes.filter(Boolean).join(' ');
}
