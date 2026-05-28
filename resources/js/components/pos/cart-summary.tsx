import { useState } from 'react';
import { useCartStore } from '@/stores/cartStore';
import { formatCurrency } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { PaymentModal } from './payment-modal';
import { InvoicePreview } from './invoice-preview';
import { salesService } from '@/services/sales';
import { useToast } from '@/components/ui/toast';
import { CreditCard, Save, Trash2 } from 'lucide-react';
import type { Sale } from '@/types';

export function CartSummary() {
    const cart = useCartStore();
    const { addToast } = useToast();
    const [showPayment, setShowPayment] = useState(false);
    const [completedSale, setCompletedSale] = useState<Sale | null>(null);
    const [saving, setSaving] = useState(false);

    const subtotal = cart.getSubtotal();
    const discountTotal = cart.getDiscountTotal();
    const taxTotal = cart.getTaxTotal();
    const total = cart.getTotal();

    const handleHold = async () => {
        if (cart.items.length === 0) return;
        setSaving(true);
        try {
            await salesService.hold({
                customer_id: cart.customer_id ?? undefined,
                prescription_id: cart.prescription_id ?? undefined,
                items: cart.items.map((i) => ({
                    medicine_id: i.medicine_id,
                    batch_id: i.batch_id,
                    quantity: i.quantity,
                    unit_price: i.unit_price,
                    discount: i.discount_amount,
                })),
                payments: [],
                discount: cart.discount_amount,
            });
            addToast({ type: 'success', title: 'Sale held successfully' });
            cart.clear();
        } catch {
            addToast({ type: 'error', title: 'Failed to hold sale' });
        } finally {
            setSaving(false);
        }
    };

    const handlePaymentComplete = (sale: Sale) => {
        setCompletedSale(sale);
        setShowPayment(false);
        cart.clear();
    };

    return (
        <>
            <div className="border-t border-gray-200 bg-gray-50 p-4">
                <div className="mb-3 space-y-1 text-sm">
                    <div className="flex justify-between">
                        <span className="text-gray-600">Subtotal</span>
                        <span>{formatCurrency(subtotal)}</span>
                    </div>
                    {discountTotal > 0 && (
                        <div className="flex justify-between text-success-600">
                            <span>Discount</span>
                            <span>-{formatCurrency(discountTotal)}</span>
                        </div>
                    )}
                    <div className="flex justify-between">
                        <span className="text-gray-600">VAT</span>
                        <span>{formatCurrency(taxTotal)}</span>
                    </div>
                    <div className="flex justify-between border-t border-gray-300 pt-2 text-lg font-bold">
                        <span>Total</span>
                        <span className="text-primary-600">{formatCurrency(total)}</span>
                    </div>
                </div>

                <div className="flex gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={handleHold}
                        disabled={cart.items.length === 0 || saving}
                        loading={saving}
                        className="flex-1"
                    >
                        <Save className="mr-1 h-4 w-4" />
                        Hold
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={cart.clear}
                        disabled={cart.items.length === 0}
                    >
                        <Trash2 className="h-4 w-4" />
                    </Button>
                    <Button
                        onClick={() => setShowPayment(true)}
                        disabled={cart.items.length === 0}
                        className="flex-1"
                    >
                        <CreditCard className="mr-1 h-4 w-4" />
                        Pay {formatCurrency(total)}
                    </Button>
                </div>
            </div>

            <PaymentModal
                open={showPayment}
                onClose={() => setShowPayment(false)}
                onComplete={handlePaymentComplete}
            />

            {completedSale && (
                <InvoicePreview
                    sale={completedSale}
                    onClose={() => setCompletedSale(null)}
                />
            )}
        </>
    );
}
