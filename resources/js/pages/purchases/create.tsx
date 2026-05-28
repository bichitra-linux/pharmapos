import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation, useQuery } from '@tanstack/react-query';
import { purchasesService } from '@/services/purchases';
import { medicinesService } from '@/services/medicines';
import { suppliersService } from '@/services/suppliers';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useToast } from '@/components/ui/toast';
import { formatCurrency } from '@/lib/utils';
import { ArrowLeft, Plus, Trash2 } from 'lucide-react';
import { useDebounce } from '@/hooks/useDebounce';
import type { Medicine } from '@/types';

interface PurchaseItemForm {
    medicine_id: number;
    medicine_name: string;
    batch_number: string;
    manufacturing_date: string;
    expiry_date: string;
    quantity: number;
    unit_price: number;
    discount_percent: number;
    tax_rate: number;
}

export default function CreatePurchase() {
    const navigate = useNavigate();
    const { addToast } = useToast();
    const [supplierId, setSupplierId] = useState(0);
    const [invoiceDate, setInvoiceDate] = useState(new Date().toISOString().split('T')[0]);
    const [dueDate, setDueDate] = useState('');
    const [supplierInvoice, setSupplierInvoice] = useState('');
    const [notes, setNotes] = useState('');
    const [items, setItems] = useState<PurchaseItemForm[]>([]);
    const [medSearch, setMedSearch] = useState('');
    const debouncedSearch = useDebounce(medSearch, 300);

    const { data: suppliers } = useQuery({
        queryKey: ['suppliers', 'list'],
        queryFn: () => suppliersService.list({ per_page: 100 }),
        select: (res) => res.data,
    });

    const { data: searchResults } = useQuery({
        queryKey: ['medicines', 'search', debouncedSearch],
        queryFn: () => medicinesService.search(debouncedSearch),
        enabled: debouncedSearch.length >= 2,
        select: (res) => res.data,
    });

    const addItem = (medicine: Medicine) => {
        const defaultPrice = medicine.batches?.[0]?.purchase_price_per_unit ?? 0;
        setItems([
            ...items,
            {
                medicine_id: medicine.id,
                medicine_name: medicine.brand_name,
                batch_number: '',
                manufacturing_date: '',
                expiry_date: '',
                quantity: 1,
                unit_price: defaultPrice,
                discount_percent: 0,
                tax_rate: 0,
            },
        ]);
        setMedSearch('');
    };

    const updateItem = (index: number, field: keyof PurchaseItemForm, value: string | number) => {
        const updated = [...items];
        updated[index] = { ...updated[index], [field]: value };
        setItems(updated);
    };

    const removeItem = (index: number) => {
        setItems(items.filter((_, i) => i !== index));
    };

    const subtotal = items.reduce((sum, item) => sum + item.quantity * item.unit_price, 0);
    const total = items.reduce((sum, item) => {
        const lineTotal = item.quantity * item.unit_price;
        const disc = (lineTotal * item.discount_percent) / 100;
        const tax = ((lineTotal - disc) * item.tax_rate) / 100;
        return sum + lineTotal - disc + tax;
    }, 0);

    const mutation = useMutation({
        mutationFn: purchasesService.create,
        onSuccess: (res) => {
            addToast({ type: 'success', title: 'Purchase created successfully' });
            navigate(`/purchases/${res.data.id}`);
        },
        onError: () => {
            addToast({ type: 'error', title: 'Failed to create purchase' });
        },
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!supplierId || items.length === 0) {
            addToast({ type: 'error', title: 'Please select supplier and add items' });
            return;
        }
        mutation.mutate({
            supplier_id: supplierId,
            purchase_date: invoiceDate,
            items: items.map((i) => ({
                medicine_id: i.medicine_id,
                batch_number: i.batch_number,
                manufacturing_date: i.manufacturing_date || undefined,
                expiry_date: i.expiry_date,
                quantity: i.quantity,
                purchase_price: i.unit_price,
                vat_rate: i.tax_rate ?? 13,
            })),
            notes,
        });
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center gap-4">
                <Button variant="ghost" onClick={() => navigate('/purchases')}>
                    <ArrowLeft className="mr-2 h-4 w-4" />
                    Back
                </Button>
                <h1 className="text-2xl font-bold">New Purchase</h1>
            </div>

            <form onSubmit={handleSubmit}>
                <Card>
                    <CardHeader>
                        <CardTitle>Purchase Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                            <Select
                                label="Supplier"
                                options={(suppliers || []).map((s) => ({ label: s.name, value: s.id }))}
                                value={supplierId}
                                onChange={(v) => setSupplierId(Number(v))}
                            />
                            <Input label="Invoice Date" type="date" value={invoiceDate} onChange={(e) => setInvoiceDate(e.target.value)} />
                            <Input label="Due Date" type="date" value={dueDate} onChange={(e) => setDueDate(e.target.value)} />
                            <Input label="Supplier Invoice #" value={supplierInvoice} onChange={(e) => setSupplierInvoice(e.target.value)} />
                        </div>
                    </CardContent>
                </Card>

                <Card className="mt-4">
                    <CardHeader>
                        <CardTitle>Items</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="mb-4">
                            <div className="relative">
                                <Input
                                    placeholder="Search medicine to add..."
                                    value={medSearch}
                                    onChange={(e) => setMedSearch(e.target.value)}
                                />
                                {searchResults && searchResults.length > 0 && (
                                    <div className="absolute top-full left-0 z-10 mt-1 w-full rounded-lg border border-gray-200 bg-white py-1 shadow-lg">
                                        {searchResults.map((med) => (
                                            <button
                                                key={med.id}
                                                type="button"
                                                onClick={() => addItem(med)}
                                                className="flex w-full items-center justify-between px-4 py-2 text-sm hover:bg-gray-50"
                                            >
                                                <span>{med.brand_name}</span>
                                                <span className="text-gray-500">
                                                    {med.batches?.[0]?.purchase_price_per_unit != null
                                                        ? formatCurrency(med.batches[0].purchase_price_per_unit)
                                                        : 'No price'}
                                                </span>
                                            </button>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>

                        {items.length > 0 && (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-gray-500">
                                            <th className="p-2">Medicine</th>
                                            <th className="p-2">Batch #</th>
                                            <th className="p-2">Mfg Date</th>
                                            <th className="p-2">Expiry</th>
                                            <th className="p-2">Qty</th>
                                            <th className="p-2">Price</th>
                                            <th className="p-2">Disc %</th>
                                            <th className="p-2">Total</th>
                                            <th className="p-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {items.map((item, idx) => {
                                            const lineTotal = item.quantity * item.unit_price;
                                            const disc = (lineTotal * item.discount_percent) / 100;
                                            const tax = ((lineTotal - disc) * item.tax_rate) / 100;
                                            return (
                                                <tr key={idx} className="border-b">
                                                    <td className="p-2 font-medium">{item.medicine_name}</td>
                                                    <td className="p-2">
                                                        <input
                                                            type="text"
                                                            value={item.batch_number}
                                                            onChange={(e) => updateItem(idx, 'batch_number', e.target.value)}
                                                            className="w-24 rounded border px-2 py-1 text-sm"
                                                        />
                                                    </td>
                                                    <td className="p-2">
                                                        <input
                                                            type="date"
                                                            value={item.manufacturing_date}
                                                            onChange={(e) => updateItem(idx, 'manufacturing_date', e.target.value)}
                                                            className="w-32 rounded border px-2 py-1 text-sm"
                                                        />
                                                    </td>
                                                    <td className="p-2">
                                                        <input
                                                            type="date"
                                                            value={item.expiry_date}
                                                            onChange={(e) => updateItem(idx, 'expiry_date', e.target.value)}
                                                            className="w-32 rounded border px-2 py-1 text-sm"
                                                        />
                                                    </td>
                                                    <td className="p-2">
                                                        <input
                                                            type="number"
                                                            value={item.quantity}
                                                            onChange={(e) => updateItem(idx, 'quantity', parseInt(e.target.value) || 0)}
                                                            className="w-16 rounded border px-2 py-1 text-sm"
                                                            min="1"
                                                        />
                                                    </td>
                                                    <td className="p-2">
                                                        <input
                                                            type="number"
                                                            value={item.unit_price}
                                                            onChange={(e) => updateItem(idx, 'unit_price', parseFloat(e.target.value) || 0)}
                                                            className="w-24 rounded border px-2 py-1 text-sm"
                                                        />
                                                    </td>
                                                    <td className="p-2">
                                                        <input
                                                            type="number"
                                                            value={item.discount_percent}
                                                            onChange={(e) => updateItem(idx, 'discount_percent', parseFloat(e.target.value) || 0)}
                                                            className="w-16 rounded border px-2 py-1 text-sm"
                                                            min="0"
                                                            max="100"
                                                        />
                                                    </td>
                                                    <td className="p-2 font-medium">{formatCurrency(lineTotal - disc + tax)}</td>
                                                    <td className="p-2">
                                                        <button type="button" onClick={() => removeItem(idx)} className="text-danger-500 hover:text-danger-700">
                                                            <Trash2 className="h-4 w-4" />
                                                        </button>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <div className="mt-4 flex items-center justify-between">
                    <div className="text-lg font-bold">
                        Total: {formatCurrency(total)}
                    </div>
                    <div className="flex gap-3">
                        <Button variant="outline" type="button" onClick={() => navigate('/purchases')}>
                            Cancel
                        </Button>
                        <Button type="submit" loading={mutation.isPending}>
                            Create Purchase
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    );
}
