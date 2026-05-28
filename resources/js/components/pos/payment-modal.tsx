import { useState } from 'react';
import { useCartStore } from '@/stores/cartStore';
import { formatCurrency } from '@/lib/utils';
import { Dialog, DialogHeader, DialogTitle, DialogContent, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { salesService } from '@/services/sales';
import { useToast } from '@/components/ui/toast';
import { PAYMENT_METHODS } from '@/lib/constants';
import type { Sale } from '@/types';

interface PaymentModalProps {
    open: boolean;
    onClose: () => void;
    onComplete: (sale: Sale) => void;
}

export function PaymentModal({ open, onClose, onComplete }: PaymentModalProps) {
    const cart = useCartStore();
    const { addToast } = useToast();
    const total = cart.getTotal();
    const [payments, setPayments] = useState<{ method_id: number; amount: number }[]>([]);
    const [processing, setProcessing] = useState(false);

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

        setProcessing(true);
        try {
            const res = await salesService.create({
                customer_id: cart.customer_id ?? undefined,
                prescription_id: cart.prescription_id ?? undefined,
                items: cart.items.map((i) => ({
                    medicine_id: i.medicine_id,
                    batch_id: i.batch_id,
                    quantity: i.quantity,
                    unit_price: i.unit_price,
                    discount: (i.unit_price * i.quantity * i.discount_percent) / 100,
                })),
                payments: payments
                    .filter((p) => p.amount > 0)
                    .map((p) => ({
                        payment_method_id: p.method_id,
                        amount: p.amount,
                    })),
                discount: cart.discount_amount,
            });
            addToast({ type: 'success', title: 'Sale completed successfully!' });
            onComplete(res.data.sale);
        } catch {
            addToast({ type: 'error', title: 'Failed to complete sale' });
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
                <div className="mb-4 rounded-lg bg-gray-50 p-4 text-center">
                    <p className="text-sm text-gray-500">Total Amount</p>
                    <p className="text-3xl font-bold text-primary-600">{formatCurrency(total)}</p>
                </div>

                <div className="mb-4">
                    <p className="mb-2 text-sm font-medium">Payment Methods</p>
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        {PAYMENT_METHODS.map((method) => (
                            <button
                                key={method.id}
                                onClick={() => addPayment(method.id)}
                                className="rounded-lg border border-gray-200 bg-white p-2 text-sm font-medium hover:border-primary-500 hover:bg-primary-50"
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
                            const method = PAYMENT_METHODS.find((m) => m.id === p.method_id);
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
                                        className="min-h-[40px] min-w-[40px] text-danger-500 hover:text-danger-700"
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
