import { useState, useMemo } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { medicinesService } from '@/services/medicines';
import { DataTable, type Column } from '@/components/ui/data-table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

import { formatCurrency } from '@/lib/utils';
import { Plus, Upload, AlertTriangle } from 'lucide-react';
import type { Medicine } from '@/types';

export default function MedicinesIndex() {
    const navigate = useNavigate();
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);

    const { data, isLoading } = useQuery({
        queryKey: ['medicines', search, page],
        queryFn: () => medicinesService.list({ search, page, per_page: 15 }),
        staleTime: 30_000,
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
                    <Button variant="outline" onClick={() => navigate('/medicines/manufacturers')}>
                        Manufacturers
                    </Button>
                    <Button variant="outline" onClick={() => navigate('/medicines/import')}>
                        <Upload className="mr-2 h-4 w-4" />
                        Import
                    </Button>
                    <Button onClick={() => navigate('/medicines/create')}>
                        <Plus className="mr-2 h-4 w-4" />
                        Add Medicine
                    </Button>
                </div>
            </div>

            {duplicates.length > 0 && (
                <div className="flex items-center gap-2 rounded-lg border border-warning-200 bg-warning-50 px-4 py-3 text-sm text-warning-800">
                    <AlertTriangle className="h-5 w-5 shrink-0" />
                    <span>
                        <strong>{duplicates.length}</strong> duplicate brand entries found. Some brands appear with the same name and strength from different manufacturers. Review and verify correct labeling.
                    </span>
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
