import { useState, useMemo } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { medicinesService } from '@/services/medicines';
import { DataTable, type Column } from '@/components/ui/data-table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { formatCurrency } from '@/lib/utils';
import { Plus, Upload, AlertTriangle, DollarSign, Printer } from 'lucide-react';
import type { Medicine } from '@/types';

export default function MedicinesIndex() {
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const [pricePercent, setPricePercent] = useState('');
    const [showPriceUpdate, setShowPriceUpdate] = useState(false);
    const [selectedIds, setSelectedIds] = useState<number[]>([]);

    const { data, isLoading } = useQuery({
        queryKey: ['medicines', search, page],
        queryFn: () => medicinesService.list({ search, page, per_page: 15 }),
        staleTime: 30_000,
    });

    const bulkPriceMutation = useMutation({
        mutationFn: () => medicinesService.bulkPriceUpdate(selectedIds, parseFloat(pricePercent)),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['medicines'] });
            setShowPriceUpdate(false);
            setPricePercent('');
            setSelectedIds([]);
        },
    });

    const duplicates = useMemo(() => {
        if (!data?.data) return [];
        const seen = new Map<string, number[]>();
        for (const med of data.data as Medicine[]) {
            const key = `${med.brand_name}|${med.strength ?? ''}`;
            if (!seen.has(key)) seen.set(key, []);
            seen.get(key)!.push(med.id);
        }
        return Array.from(seen.entries()).filter(([, ids]) => ids.length > 1);
    }, [data?.data]);

    const columns: Column<Medicine>[] = [
        {
            key: 'brand_name',
            header: 'Name',
            sortable: true,
            render: (item) => (
                <div>
                    <p className="font-medium">{item.brand_name}{item.strength ? ` ${item.strength}` : ''}</p>
                    <p className="text-xs text-text-muted">{item.generic_name}</p>
                </div>
            ),
        },
        { key: 'dosage_form', header: 'Form', sortable: true },
        { key: 'strength', header: 'Strength' },
        {
            key: 'batches',
            header: 'Price',
            render: (item) => {
                const price = item.batches?.[0]?.selling_price_per_unit;
                return price != null ? formatCurrency(price) : '-';
            },
        },
        {
            key: 'manufacturer',
            header: 'Manufacturer',
            render: (item) =>
                item.manufacturer?.name ? (
                    <Badge variant="secondary">{item.manufacturer.name}</Badge>
                ) : (
                    <span className="text-text-muted">-</span>
                ),
        },
        {
            key: 'is_active',
            header: 'Status',
            render: (item) => (
                <Badge variant={item.is_active ? 'success' : 'destructive'}>
                    {item.is_active ? 'Active' : 'Inactive'}
                </Badge>
            ),
        },
    ];

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold">Medicines</h1>
                <div className="flex gap-2">
                    <Button variant="outline" size="sm" onClick={() => { setSelectedIds((data?.data ?? []).map((m: any) => m.id)); setShowPriceUpdate(true); }}>
                        <DollarSign className="mr-1.5 h-4 w-4" /> Bulk Price
                    </Button>
                    <Button variant="outline" size="sm" onClick={() => window.open('/medicines/labels', '_blank')}>
                        <Printer className="mr-1.5 h-4 w-4" /> Print Labels
                    </Button>
                    <Button variant="outline" size="sm" onClick={() => navigate('/medicines/manufacturers')}>
                        Manufacturers
                    </Button>
                    <Button variant="outline" size="sm" onClick={() => navigate('/medicines/import')}>
                        <Upload className="mr-1.5 h-4 w-4" /> Import
                    </Button>
                    <Button size="sm" onClick={() => navigate('/medicines/create')}>
                        <Plus className="mr-1.5 h-4 w-4" /> Add Medicine
                    </Button>
                </div>
            </div>

            {duplicates.length > 0 && (
                <div className="flex items-center gap-2 rounded-lg border border-warning-200 bg-warning-50 px-4 py-3 text-sm text-warning-800">
                    <AlertTriangle className="h-5 w-5 shrink-0" />
                    <span>
                        <strong>{duplicates.length}</strong> duplicate brand entries found.
                    </span>
                </div>
            )}

            {showPriceUpdate && (
                <div className="rounded-lg border border-border bg-surface p-4">
                    <div className="flex items-end gap-3">
                        <div>
                            <label className="text-xs font-medium text-text-muted">Price change (%)</label>
                            <input
                                type="number"
                                value={pricePercent}
                                onChange={(e) => setPricePercent(e.target.value)}
                                placeholder="e.g. 10 for +10%, -5 for -5%"
                                className="mt-1 block w-48 rounded-md border border-border bg-surface-muted px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
                            />
                        </div>
                        <Button
                            onClick={() => bulkPriceMutation.mutate()}
                            disabled={!pricePercent || isNaN(parseFloat(pricePercent))}
                            loading={bulkPriceMutation.isPending}
                        >
                            <DollarSign className="mr-1.5 h-4 w-4" /> Apply to {selectedIds.length} medicines
                        </Button>
                        <Button variant="ghost" onClick={() => { setShowPriceUpdate(false); setPricePercent(''); }}>
                            Cancel
                        </Button>
                    </div>
                </div>
            )}

            <DataTable
                columns={columns}
                data={(data?.data ?? []) as (Medicine & Record<string, unknown>)[]}
                loading={isLoading}
                searchable
                searchPlaceholder="Search medicines..."
                onSearch={setSearch}
                onRowClick={(item) => navigate(`/medicines/${(item as unknown as Medicine).id}`)}
                pagination={{
                    currentPage: data?.meta?.current_page ?? 1,
                    lastPage: data?.meta?.last_page ?? 1,
                    total: data?.meta?.total ?? 0,
                    onPageChange: setPage,
                }}
            />
        </div>
    );
}
