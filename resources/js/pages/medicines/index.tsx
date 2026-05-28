import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { medicinesService } from '@/services/medicines';
import { DataTable, type Column } from '@/components/ui/data-table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { formatCurrency } from '@/lib/utils';
import { Plus, Upload } from 'lucide-react';
import type { Medicine } from '@/types';

export default function MedicinesIndex() {
    const navigate = useNavigate();
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);

    const { data, isLoading } = useQuery({
        queryKey: ['medicines', search, page],
        queryFn: () => medicinesService.list({ search, page, per_page: 15 }),
    });

    const columns: Column<Medicine>[] = [
        { key: 'brand_name', header: 'Brand Name', sortable: true },
        { key: 'generic_name', header: 'Generic Name', sortable: true },
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
            render: (item) => item.manufacturer?.name || '-',
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
