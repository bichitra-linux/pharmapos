import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/components/ui/toast';
import { formatCurrency } from '@/lib/utils';
import { ArrowLeft } from 'lucide-react';
import api from '@/services/api';
import { salesService } from '@/services/sales';
import { purchasesService } from '@/services/purchases';
import { PageLoader } from '@/components/ui/spinner';

interface ReturnableItem {
    id: number;
    medicine_id: number;
    batch_id: number;
    label: string;
    maxQuantity: number;
    quantity: number;
}

export default function CreateReturn() {
    const navigate = useNavigate();
    const { addToast } = useToast();
    const [type, setType] = useState<'customer' | 'supplier'>('customer');
    const [referenceId, setReferenceId] = useState('');
    const [loadedRef, setLoadedRef] = useState('');
    const [refundMethod, setRefundMethod] = useState('cash');
    const [reason, setReason] = useState('');
    const [notes, setNotes] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const saleQuery = useQuery({
        queryKey: ['sale', 'return', loadedRef],
        queryFn: () => salesService.get(Number(loadedRef)),
        select: (res) => res.data,
        enabled: type === 'customer' && !!loadedRef,
    });

    const purchaseQuery = useQuery({
        queryKey: ['purchase', 'return', loadedRef],
        queryFn: () => purchasesService.get(Number(loadedRef)),
        select: (res) => res.data,
        enabled: type === 'supplier' && !!loadedRef,
    });

    const [quantities, setQuantities] = useState<Record<number, number>>({});

    const source = type === 'customer' ? saleQuery.data : purchaseQuery.data;
    const loading = type === 'customer' ? saleQuery.isLoading : purchaseQuery.isLoading;

    const sourceItems: ReturnableItem[] = source
        ? (type === 'customer' ? (source as any).items : (source as any).items)
            ?.map((item: any) => ({
                id: item.id,
                medicine_id: item.medicine_id,
                batch_id: item.batch_id,
                label: item.medicine?.brand_name || `Medicine #${item.medicine_id}`,
                maxQuantity: item.quantity,
                quantity: item.quantity,
            })) ?? []
        : [];

    const items = sourceItems.map((item) => ({ ...item, quantity: quantities[item.id] ?? item.maxQuantity }));

    const totalRefund = items.reduce((sum, item) => {
        const price = type === 'customer'
            ? Number((source as any)?.items?.find((i: any) => i.id === item.id)?.selling_price ?? 0)
            : Number((source as any)?.items?.find((i: any) => i.id === item.id)?.purchase_price ?? 0);
        return sum + price * item.quantity;
    }, 0);

    const loadReference = () => {
        if (!referenceId) return;
        setLoadedRef(referenceId);
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        const payloadItems = items
            .filter((item) => item.quantity > 0)
            .map((item) => ({ medicine_id: item.medicine_id, batch_id: item.batch_id, quantity: item.quantity }));

        if (payloadItems.length === 0) {
            addToast({ type: 'error', title: 'Select at least one item to return' });
            return;
        }

        setSubmitting(true);
        try {
            if (type === 'customer') {
                await api.post('/sale-returns', {
                    sale_id: Number(loadedRef),
                    items: payloadItems,
                    refund_amount: Math.round(totalRefund * 100) / 100,
                    refund_method: refundMethod,
                    reason,
                });
            } else {
                await api.post('/supplier-returns', {
                    supplier_id: (source as any)?.supplier?.id,
                    purchase_id: Number(loadedRef),
                    items: payloadItems,
                    notes,
                });
            }
            addToast({ type: 'success', title: 'Return created successfully' });
            navigate('/returns');
        } catch (err: any) {
            addToast({ type: 'error', title: err?.response?.data?.message || 'Failed to create return' });
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center gap-4">
                <Button variant="ghost" onClick={() => navigate('/returns')}>
                    <ArrowLeft className="mr-2 h-4 w-4" />
                    Back
                </Button>
                <h1 className="text-2xl font-bold">Process Return</h1>
            </div>

            <form onSubmit={handleSubmit}>
                <Card>
                    <CardHeader>
                        <CardTitle>Return Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <Select
                            label="Return Type"
                            options={[
                                { label: 'Customer Return', value: 'customer' },
                                { label: 'Supplier Return', value: 'supplier' },
                            ]}
                            value={type}
                            onChange={(v) => {
                                setType(v as 'customer' | 'supplier');
                                setLoadedRef('');
                                setReferenceId('');
                            }}
                        />
                        <div className="flex items-end gap-3">
                            <div className="flex-1">
                                <Input
                                    label={type === 'customer' ? 'Sale ID' : 'Purchase ID'}
                                    type="number"
                                    value={referenceId}
                                    onChange={(e) => setReferenceId(e.target.value)}
                                    placeholder="Enter ID to load items"
                                />
                            </div>
                            <Button type="button" variant="outline" onClick={loadReference}>Load Items</Button>
                        </div>
                        {type === 'customer' && (
                            <Select
                                label="Refund Method"
                                options={[
                                    { label: 'Cash', value: 'cash' },
                                    { label: 'Digital Wallet', value: 'digital_wallet' },
                                    { label: 'Card', value: 'card' },
                                    { label: 'Bank Transfer', value: 'bank_transfer' },
                                ]}
                                value={refundMethod}
                                onChange={(v) => setRefundMethod(String(v))}
                            />
                        )}
                        <Input label="Reason" value={reason} onChange={(e) => setReason(e.target.value)} />
                        {type === 'supplier' && (
                            <Input label="Notes" value={notes} onChange={(e) => setNotes(e.target.value)} />
                        )}
                    </CardContent>
                </Card>

                {loading && <PageLoader />}

                {!loading && source && items.length > 0 && (
                    <Card className="mt-4">
                        <CardHeader>
                            <CardTitle>{type === 'customer' ? 'Sale Items' : 'Purchase Items'}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Medicine</TableHead>
                                        <TableHead>Available</TableHead>
                                        <TableHead>Return Qty</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {items.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell className="font-medium">{item.label}</TableCell>
                                            <TableCell>{item.maxQuantity}</TableCell>
                                            <TableCell className="w-32">
                                                <Input
                                                    type="number"
                                                    min={0}
                                                    max={item.maxQuantity}
                                                    value={item.quantity}
                                                    onChange={(e) => {
                                                        const value = Math.min(Math.max(Number(e.target.value) || 0, 0), item.maxQuantity);
                                                        setQuantities((prev) => ({ ...prev, [item.id]: value }));
                                                    }}
                                                />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                            {type === 'customer' && (
                                <div className="mt-4 text-right text-sm font-bold">
                                    Refund Amount: {formatCurrency(Math.round(totalRefund * 100) / 100)}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}

                {!loading && loadedRef && source == null && (
                    <p className="mt-4 text-sm text-danger-600">{type === 'customer' ? 'Sale' : 'Purchase'} not found</p>
                )}

                <div className="mt-6 flex justify-end gap-3">
                    <Button variant="outline" type="button" onClick={() => navigate('/returns')}>
                        Cancel
                    </Button>
                    <Button type="submit" loading={submitting} disabled={!source}>Create Return</Button>
                </div>
            </form>
        </div>
    );
}
