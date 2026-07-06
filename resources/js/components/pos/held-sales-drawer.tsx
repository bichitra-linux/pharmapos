import { useState, useEffect, useCallback } from 'react';
import { useCartStore } from '@/stores/cartStore';
import { formatCurrency, formatDate } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { ShoppingCart, Trash2, Play, X, Pencil } from 'lucide-react';

interface HeldSale {
    id: number;
    name: string;
    items: any[];
    customer_id: number | null;
    discount_amount: number;
    sale_type: string;
    held_at: string;
}

interface Props {
    open: boolean;
    onClose: () => void;
}

export function HeldSalesDrawer({ open, onClose }: Props) {
    const [heldSales, setHeldSales] = useState<HeldSale[]>([]);
    const addItems = useCartStore((s) => s.addItem);
    const setCustomer = useCartStore((s) => s.setCustomer);
    const setDiscount = useCartStore((s) => s.setDiscount);
    const setSaleType = useCartStore((s) => s.setSaleType);

    useEffect(() => {
        if (open) {
            const stored = JSON.parse(localStorage.getItem('pharmapos-held-sales') || '[]');
            setHeldSales(stored);
        }
    }, [open]);

    const handleResume = useCallback((sale: HeldSale) => {
        for (const item of sale.items) {
            addItems(item);
        }
        if (sale.customer_id) setCustomer(sale.customer_id);
        if (sale.discount_amount) setDiscount(sale.discount_amount);
        if (sale.sale_type) setSaleType(sale.sale_type as any);

        const stored = JSON.parse(localStorage.getItem('pharmapos-held-sales') || '[]');
        const filtered = stored.filter((s: HeldSale) => s.id !== sale.id);
        localStorage.setItem('pharmapos-held-sales', JSON.stringify(filtered));
        setHeldSales(filtered);
        onClose();
    }, [addItems, setCustomer, setDiscount, setSaleType, onClose]);

    const handleDelete = useCallback((id: number) => {
        const stored = JSON.parse(localStorage.getItem('pharmapos-held-sales') || '[]');
        const filtered = stored.filter((s: HeldSale) => s.id !== id);
        localStorage.setItem('pharmapos-held-sales', JSON.stringify(filtered));
        setHeldSales(filtered);
    }, []);

    const handleRename = useCallback((id: number) => {
        const newName = window.prompt('Rename this held sale:');
        if (newName === null) return;
        const stored = JSON.parse(localStorage.getItem('pharmapos-held-sales') || '[]');
        const updated = stored.map((s: HeldSale) => s.id === id ? { ...s, name: newName } : s);
        localStorage.setItem('pharmapos-held-sales', JSON.stringify(updated));
        setHeldSales(updated);
    }, []);

    if (!open) return null;

    return (
        <div className="fixed inset-y-0 right-0 z-40 flex md:relative">
            <div className="w-80 border-l border-border bg-surface shadow-lg flex flex-col">
                <div className="flex items-center justify-between border-b border-border p-3">
                    <h2 className="font-semibold flex items-center gap-2">
                        <ShoppingCart className="h-4 w-4" />
                        Held Sales ({heldSales.length})
                    </h2>
                    <button onClick={onClose} aria-label="Close held sales" className="rounded p-1 hover:bg-surface-muted">
                        <X className="h-4 w-4" />
                    </button>
                </div>
                <div className="flex-1 overflow-y-auto p-3 space-y-2">
                    {heldSales.length === 0 && (
                        <p className="text-sm text-text-muted text-center py-8">No held sales.</p>
                    )}
                    {heldSales.map((sale) => {
                        const totalItems = sale.items.reduce((sum: number, i: any) => sum + i.quantity, 0);
                        return (
                            <div key={sale.id} className="rounded border border-border p-3">
                                <div className="flex items-start justify-between">
                                    <div>
                                        <p className="text-sm font-medium">{sale.name || `${totalItems} item${totalItems > 1 ? 's' : ''}`}</p>
                                        <p className="text-xs text-text-muted">{formatDate(sale.held_at, 'dd/MM/yyyy HH:mm')}</p>
                                    </div>
                                    <div className="flex gap-1">
                                        <button
                                            onClick={() => handleRename(sale.id)}
                                            aria-label="Rename"
                                            className="rounded p-1 text-text-muted hover:bg-surface-muted"
                                        >
                                            <Pencil className="h-4 w-4" />
                                        </button>
                                        <button
                                            onClick={() => handleResume(sale)}
                                            aria-label="Resume sale"
                                            className="rounded p-1 text-success-600 hover:bg-success-50"
                                        >
                                            <Play className="h-4 w-4" />
                                        </button>
                                        <button
                                            onClick={() => handleDelete(sale.id)}
                                            aria-label="Delete held sale"
                                            className="rounded p-1 text-danger-600 hover:bg-danger-50"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                                <div className="mt-1 text-xs text-text-muted">
                                    {sale.items.map((i: any, idx: number) => (
                                        <span key={idx}>{i.name} x{i.quantity}{idx < sale.items.length - 1 ? ', ' : ''}</span>
                                    ))}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}
