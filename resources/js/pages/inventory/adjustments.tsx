import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { inventoryService } from '@/services/inventory';
import { medicinesService } from '@/services/medicines';
import { DataTable, type Column } from '@/components/ui/data-table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Dialog, DialogHeader, DialogTitle, DialogContent, DialogFooter } from '@/components/ui/dialog';
import { formatDate } from '@/lib/utils';
import { useToast } from '@/components/ui/toast';
import { Plus } from 'lucide-react';
import type { InventoryAdjustment } from '@/types';

const ADJUSTMENT_TYPES = [
    { label: 'Damage', value: 'damage' },
    { label: 'Expiry', value: 'expiry' },
    { label: 'Count Adjustment', value: 'count_adjustment' },
    { label: 'Return', value: 'return' },
    { label: 'Other', value: 'other' },
];

export default function AdjustmentsPage() {
    const { addToast } = useToast();
    const queryClient = useQueryClient();
    const [page, setPage] = useState(1);
    const [open, setOpen] = useState(false);
    const [type, setType] = useState('damage');
    const [reason, setReason] = useState('');
    const [search, setSearch] = useState('');
    const [batchId, setBatchId] = useState('');
    const [medicineId, setMedicineId] = useState('');
    const [quantity, setQuantity] = useState('1');

    const { data, isLoading } = useQuery({
        queryKey: ['inventory', 'adjustments', page],
        queryFn: () => inventoryService.getAdjustments({ page, per_page: 15 }),
    });

    const { data: searchResults } = useQuery({
        queryKey: ['medicines', 'search', search],
        queryFn: () => medicinesService.search(search),
        enabled: open && search.trim().length > 0,
    });

    const mutation = useMutation({
        mutationFn: () => inventoryService.createAdjustment({
            type,
            reason,
            items: [{ medicine_id: Number(medicineId), batch_id: Number(batchId), quantity: Number(quantity) }],
        }),
        onSuccess: () => {
            addToast({ type: 'success', title: 'Adjustment recorded' });
            queryClient.invalidateQueries({ queryKey: ['inventory', 'adjustments'] });
            setOpen(false);
            setSearch('');
            setBatchId('');
            setMedicineId('');
            setQuantity('1');
            setReason('');
        },
        onError: (err: any) => {
            addToast({ type: 'error', title: err?.response?.data?.message || 'Failed to record adjustment' });
        },
    });

    const columns: Column<InventoryAdjustment>[] = [
        { key: 'type', header: 'Type', sortable: true, render: (item) => <Badge>{item.type}</Badge> },
        { key: 'reason', header: 'Reason' },
        { key: 'adjusted_by_name', header: 'Adjusted By' },
        { key: 'created_at', header: 'Date', sortable: true, render: (item) => formatDate(item.created_at) },
    ];

    const selectedBatch = searchResults?.data
        ?.flatMap((med: any) => med.batches ?? [])
        ?.find((b: any) => b.id === Number(batchId));

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold">Stock Adjustments</h1>
                <Button onClick={() => setOpen(true)}>
                    <Plus className="mr-2 h-4 w-4" />
                    New Adjustment
                </Button>
            </div>
            <DataTable
                columns={columns}
                data={(data?.data ?? []) as (InventoryAdjustment & Record<string, unknown>)[]}
                loading={isLoading}
                pagination={{
                    currentPage: data?.meta?.current_page ?? 1,
                    lastPage: data?.meta?.last_page ?? 1,
                    total: data?.meta?.total ?? 0,
                    onPageChange: setPage,
                }}
            />

            <Dialog open={open} onClose={() => setOpen(false)}>
                <DialogHeader>
                    <DialogTitle>New Stock Adjustment</DialogTitle>
                </DialogHeader>
                <DialogContent className="space-y-4">
                    <Select
                        label="Type"
                        options={ADJUSTMENT_TYPES}
                        value={type}
                        onChange={(v) => setType(String(v))}
                    />
                    <Input
                        label="Search Medicine"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Type to search by brand or generic name"
                    />
                    {searchResults?.data && (
                        <div className="max-h-48 space-y-1 overflow-y-auto rounded-md border border-border">
                            {searchResults.data.map((med: any) => (
                                <div key={med.id} className="border-b border-border p-2 last:border-0">
                                    <p className="text-sm font-medium">{med.brand_name} <span className="text-text-muted">({med.generic_name})</span></p>
                                    {(med.batches ?? []).map((b: any) => (
                                        <button
                                            key={b.id}
                                            type="button"
                                            onClick={() => {
                                                setBatchId(String(b.id));
                                                setMedicineId(String(med.id));
                                            }}
                                            className={`mt-1 flex w-full items-center justify-between rounded px-2 py-1 text-xs hover:bg-primary-50 ${batchId === String(b.id) ? 'bg-primary-100' : ''}`}
                                        >
                                            <span>{b.batch_number} — exp {formatDate(b.expiry_date)}</span>
                                            <span>{b.quantity_in_stock} in stock</span>
                                        </button>
                                    ))}
                                </div>
                            ))}
                        </div>
                    )}
                    {selectedBatch && (
                        <p className="text-xs text-text-muted">Selected: {selectedBatch.batch_number} ({selectedBatch.quantity_in_stock} in stock)</p>
                    )}
                    <Input
                        label={type === 'count_adjustment' ? 'New Count (exact quantity)' : 'Quantity'}
                        type="number"
                        min={0.01}
                        step={0.01}
                        value={quantity}
                        onChange={(e) => setQuantity(e.target.value)}
                    />
                    <Input
                        label="Reason"
                        value={reason}
                        onChange={(e) => setReason(e.target.value)}
                        placeholder="Why is this adjustment needed?"
                    />
                </DialogContent>
                <DialogFooter>
                    <Button variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
                    <Button onClick={() => mutation.mutate()} loading={mutation.isPending} disabled={!batchId || !quantity || Number(quantity) <= 0}>
                        Record Adjustment
                    </Button>
                </DialogFooter>
            </Dialog>
        </div>
    );
}
