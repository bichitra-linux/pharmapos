import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useCartStore } from '@/stores/cartStore';
import { formatCurrency } from '@/lib/utils';
import { Dialog, DialogHeader, DialogTitle, DialogContent, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { salesService } from '@/services/sales';
import { paymentMethodsService } from '@/services/payment-methods';
import { useToast } from '@/components/ui/toast';
import { PAYMENT_METHODS } from '@/lib/constants';
import type { Sale } from '@/types';

interface PaymentModalProps {
    open: boolean;
    onClose: () => void;
    onComplete: (sale: Sale) => void;
}

export function PaymentModal({ open, onClose, onComplete }: PaymentModalProps) {
    const items = useCartStore((s) => s.items);
    const discount_amount = useCartStore((s) => s.discount_amount);
    const customer_id = useCartStore((s) => s.customer_id);
    const prescription_id = useCartStore((s) => s.prescription_id);
    const getTotal = useCartStore((s) => s.getTotal);
    const { addToast } = useToast();
    const total = getTotal();
    const [payments, setPayments] = useState<{ method_id: number; amount: number }[]>([]);
    const [processing, setProcessing] = useState(false);

    // ponytail: DB methods are canonical; constants are the fallback if the fetch fails
    const { data: methodsResponse } = useQuery({
        queryKey: ['payment-methods'],
        queryFn: () => paymentMethodsService.list(),
        enabled: open,
    });
    const methods = methodsResponse?.data?.length
        ? methodsResponse.data
        : PAYMENT_METHODS.map((m) => ({ id: m.id, name: m.name, type: m.type }));

    const paidAmount = payments.reduce((sum, p) => sum + p.amount, 0);
    const remaining = total - paidAmount;

    const addPayment = (methodId: number) => {
        setPayments([...payments, { method_id: methodId, amount: remaining > 0 ? remaining : 0 }]);
    };

    const updateAmount = (index: number, amount: number) => {
        const updated = [...payments];
        updated[index].amount = Math.max(0, amount);
        setPayments(updated);
    };

    const removePayment = (index: number) => {
        setPayments(payments.filter((_, i) => i !== index));
    };

    const handleSubmit = async () => {
        if (remaining > 0.01) {
            addToast({ type: 'error', title: 'Insufficient payment amount' });
            return;
        }

        // Check if any restricted items need prescription
        const hasRestricted = items.some((i) => ['h', 'h1', 'x'].includes(i.name.toLowerCase().slice(0, 2)));
        if (hasRestricted && !prescription_id) {
            if (window.confirm('This sale contains schedule H/H1/X drugs without a linked prescription. Dispense anyway?')) {
                // Pharmacist override — allowed to proceed
            } else {
                return;
            }
        }

        setProcessing(true);
        try {
            const res = await salesService.create({
                customer_id: customer_id ?? undefined,
                prescription_id: prescription_id ?? undefined,
                sale_type: useCartStore.getState().sale_type,
                items: items.map((i) => {
                    const isPiece = i.sell_mode === 'piece' && i.units_per_pack > 1;
                    const outerQty = isPiece
                        ? Math.round((i.quantity / i.units_per_pack) * 100) / 100
                        : i.quantity;
                    const pieceQty = isPiece ? i.quantity : i.quantity * (i.units_per_pack || 1);
                    const effPrice = isPiece ? i.unit_price / i.units_per_pack : i.unit_price;
                    const lineTotal = effPrice * i.quantity;
                    const discount = (lineTotal * (i.discount_percent ?? 0)) / 100;
                    return {
                        medicine_id: i.medicine_id,
                        batch_id: i.batch_id,
                        quantity: outerQty,
                        unit_price: i.unit_price,
                        discount,
                        sell_mode: i.sell_mode,
                        units_per_pack: i.units_per_pack,
                        pieces_quantity: pieceQty,
                    };
                }),
                payments: payments
                    .filter((p) => p.amount > 0)
                    .map((p) => ({
                        payment_method_id: p.method_id,
                        amount: p.amount,
                    })),
                discount: discount_amount,
            });
            addToast({ type: 'success', title: 'Sale completed successfully!' });
            onComplete(res.data.sale);
        } catch (err: any) {
            const msg = err?.response?.data?.message
                ?? err?.validationErrors
                ?? err?.message
                ?? 'Failed to complete sale';
            addToast({ type: 'error', title: msg });
        } finally {
            setProcessing(false);
        }
    };

    return (
        <Dialog open={open} onClose={onClose} size="lg">
            <DialogHeader>
                <DialogTitle>Complete Payment</DialogTitle>
            </DialogHeader>
            <DialogContent>
                <div className="mb-4 rounded-lg bg-surface-muted p-4 text-center">
                    <p className="text-sm text-text-muted">Total Amount</p>
                    <p className="text-3xl font-bold text-primary-600">{formatCurrency(total)}</p>
                </div>

                <div className="mb-4">
                    <p className="mb-2 text-sm font-medium">Payment Methods</p>
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        {methods.map((method) => (
                            <button
                                key={method.id}
                                onClick={() => addPayment(method.id)}
                                className="rounded-lg border border-border bg-surface p-2 text-sm font-medium hover:border-primary-500 hover:bg-primary-50"
                            >
                                {method.name}
                            </button>
                        ))}
                    </div>
                </div>

                {payments.length > 0 && (
                    <div className="space-y-2">
                        <p className="text-sm font-medium">Split Payments</p>
                        {payments.map((p, idx) => {
                            const method = methods.find((m) => m.id === p.method_id);
                            return (
                                <div key={idx} className="flex items-center gap-2">
                                    <span className="w-24 text-sm">{method?.name}</span>
                                    <Input
                                        type="number"
                                        value={p.amount}
                                        onChange={(e) => updateAmount(idx, parseFloat(e.target.value) || 0)}
                                        className="flex-1"
                                    />
                                    <button
                                        onClick={() => removePayment(idx)}
                                        aria-label="Remove payment method"
                                        className="min-h-[44px] min-w-[44px] text-danger-500 hover:text-danger-700"
                                    >
                                        ✕
                                    </button>
                                </div>
                            );
                        })}
                    </div>
                )}

                <div className="mt-4 flex justify-between text-sm">
                    <span>Paid: {formatCurrency(paidAmount)}</span>
                    <span className={remaining > 0 ? 'text-danger-600' : 'text-success-600'}>
                        {remaining > 0 ? `Remaining: ${formatCurrency(remaining)}` : 'Fully Paid'}
                    </span>
                </div>
            </DialogContent>
            <DialogFooter>
                <Button variant="outline" onClick={onClose}>
                    Cancel
                </Button>
                <Button onClick={handleSubmit} loading={processing} disabled={remaining > 0.01}>
                    Complete Sale
                </Button>
            </DialogFooter>
        </Dialog>
    );
}
