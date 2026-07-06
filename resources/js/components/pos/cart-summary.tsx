import { useState, useMemo, useCallback } from 'react';
import { useCartStore } from '@/stores/cartStore';
import { formatCurrency } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { PaymentModal } from './payment-modal';
import { InvoicePreview } from './invoice-preview';
import { CreditCard, Trash2, Pause, Eye } from 'lucide-react';
import type { Sale } from '@/types';

export function CartSummary() {
    const items = useCartStore((s) => s.items);
    const discount_amount = useCartStore((s) => s.discount_amount);
    const sale_type = useCartStore((s) => s.sale_type);
    const setSaleType = useCartStore((s) => s.setSaleType);
    const customer_id = useCartStore((s) => s.customer_id);
    const prescription_id = useCartStore((s) => s.prescription_id);
    const clear = useCartStore((s) => s.clear);
    const storeState = useCartStore.getState();
    const [showPayment, setShowPayment] = useState(false);
    const [completedSale, setCompletedSale] = useState<Sale | null>(null);

    const subtotal = useMemo(() => {
        return items.reduce((sum, item) => {
            const p = item.sell_mode === 'piece' && item.units_per_pack > 1 ? item.unit_price / item.units_per_pack : item.unit_price;
            return sum + p * item.quantity;
        }, 0);
    }, [items]);

    const discountTotal = useMemo(() => {
        const d = items.reduce((sum, item) => {
            const p = item.sell_mode === 'piece' && item.units_per_pack > 1 ? item.unit_price / item.units_per_pack : item.unit_price;
            return sum + (p * item.quantity * item.discount_percent) / 100;
        }, 0);
        return d + discount_amount;
    }, [items, discount_amount]);

    const taxTotal = useMemo(() => {
        return items.reduce((sum, item) => {
            const p = item.sell_mode === 'piece' && item.units_per_pack > 1 ? item.unit_price / item.units_per_pack : item.unit_price;
            const t = p * item.quantity;
            return sum + ((t - (t * item.discount_percent) / 100) * (item.tax_rate ?? 13)) / 100;
        }, 0);
    }, [items]);

    const total = useMemo(() => subtotal - discountTotal + taxTotal, [subtotal, discountTotal, taxTotal]);
    const hasRxWithoutRx = items.some((i) => ['h', 'h1', 'x'].includes(i.name.toLowerCase().slice(0, 2))) && !prescription_id;

    const handlePaymentComplete = useCallback((sale: Sale) => {
        setCompletedSale(sale);
        setShowPayment(false);
        clear();
    }, [clear]);

    const handleHold = useCallback(() => {
        if (items.length === 0) return;
        const name = window.prompt('Name for this held sale?') || '';
        const held = { id: Date.now(), name, items: JSON.parse(JSON.stringify(items)), customer_id: storeState.customer_id, discount_amount: storeState.discount_amount, sale_type: storeState.sale_type, held_at: new Date().toISOString() };
        const existing = JSON.parse(localStorage.getItem('pharmapos-held-sales') || '[]');
        existing.push(held);
        localStorage.setItem('pharmapos-held-sales', JSON.stringify(existing));
        clear();
    }, [items, storeState, clear]);

    return (
        <>
            <div className="border-t border-border bg-surface-muted p-3 space-y-2">
                {/* Row 1: Sale type + customer + prescription */}
                <div className="flex items-center gap-1">
                    <div className="flex rounded border border-border overflow-hidden text-[10px]">
                        {(['walk_in', 'online', 'delivery'] as const).map((t) => (
                            <button key={t} onClick={() => setSaleType(t)}
                                className={`px-2 py-1 font-medium ${sale_type === t ? 'bg-primary-600 text-white' : 'bg-surface text-text hover:bg-surface-muted'}`}>
                                {t === 'walk_in' ? 'W' : t === 'online' ? 'On' : 'Del'}
                            </button>
                        ))}
                    </div>
                    <span className="text-[10px] text-text-muted">{customer_id ? 'Cust #' + customer_id : ''}</span>
                    {hasRxWithoutRx && <span className="text-[10px] px-1.5 rounded bg-danger-100 text-danger-700 font-semibold">Rx!</span>}
                </div>

                {/* Row 2: Totals */}
                <div className="space-y-0.5 text-xs">
                    <div className="flex justify-between"><span className="text-text-muted">Subtotal</span><span>{formatCurrency(subtotal)}</span></div>
                    {discountTotal > 0 && <div className="flex justify-between text-success-600"><span>Discount</span><span>-{formatCurrency(discountTotal)}</span></div>}
                    <div className="flex justify-between"><span className="text-text-muted">VAT</span><span>{formatCurrency(taxTotal)}</span></div>
                    <div className="flex justify-between border-t border-border pt-1 text-base font-bold">
                        <span>Total</span><span className="text-primary-600">{formatCurrency(total)}</span>
                    </div>
                </div>

                {/* Row 3: Actions */}
                <div className="flex gap-1">
                    <Button variant="outline" size="sm" onClick={clear} disabled={items.length === 0} aria-label="Clear" className="min-h-[36px] min-w-[36px] px-2">
                        <Trash2 className="h-3.5 w-3.5" />
                    </Button>
                    <Button variant="outline" size="sm" onClick={handleHold} disabled={items.length === 0} aria-label="Hold" className="min-h-[36px] min-w-[36px] px-2">
                        <Pause className="h-3.5 w-3.5" />
                    </Button>
                    <Button variant="outline" size="sm" onClick={() => {}} disabled={items.length === 0} aria-label="Preview" className="min-h-[36px] min-w-[36px] px-2">
                        <Eye className="h-3.5 w-3.5" />
                    </Button>
                    <Button
                        data-pay-btn
                        onClick={() => setShowPayment(true)}
                        disabled={items.length === 0}
                        className="flex-1 min-h-[40px] text-sm"
                    >
                        <CreditCard className="mr-1 h-4 w-4" /> Pay {formatCurrency(total)}
                    </Button>
                </div>

                {/* Rx warning */}
                {hasRxWithoutRx && (
                    <p className="text-[10px] text-danger-600 text-center">Prescription required for some items</p>
                )}
            </div>

            <PaymentModal open={showPayment} onClose={() => setShowPayment(false)} onComplete={handlePaymentComplete} />
            {completedSale && <InvoicePreview sale={completedSale} onClose={() => setCompletedSale(null)} />}
        </>
    );
}
