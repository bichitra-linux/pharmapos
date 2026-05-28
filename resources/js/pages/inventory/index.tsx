import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { inventoryService } from '@/services/inventory';
import { DataTable, type Column } from '@/components/ui/data-table';
import { Badge } from '@/components/ui/badge';
import { formatCurrency, formatDate } from '@/lib/utils';
import type { MedicineBatch } from '@/types';

export default function InventoryIndex() {
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);

    const { data, isLoading } = useQuery({
        queryKey: ['inventory', 'stock', search, page],
        queryFn: () => inventoryService.getStock({ search, page, per_page: 15 }),
    });

    const columns: Column<MedicineBatch>[] = [
        { key: 'medicine', header: 'Medicine', render: (item) => item.medicine?.brand_name || '-' },
        { key: 'batch_number', header: 'Batch #', sortable: true },
        { key: 'expiry_date', header: 'Expiry', sortable: true, render: (item) => formatDate(item.expiry_date) },
        { key: 'quantity_in_stock', header: 'Stock', sortable: true },
        { key: 'purchase_price_per_unit', header: 'Purchase', render: (item) => formatCurrency(item.purchase_price_per_unit) },
        { key: 'selling_price_per_unit', header: 'Selling', render: (item) => formatCurrency(item.selling_price_per_unit) },
        {
            key: 'is_active',
            header: 'Status',
            render: (item) => (
                <Badge variant={item.is_active ? 'success' : 'secondary'}>
                    {item.is_active ? 'Active' : 'Inactive'}
                </Badge>
            ),
        },
    ];

    return (
        <div className="space-y-4">
            <h1 className="text-2xl font-bold">Inventory / Stock</h1>
            <DataTable
                columns={columns}
                data={(data?.data ?? []) as (MedicineBatch & Record<string, unknown>)[]}
                loading={isLoading}
                searchable
                searchPlaceholder="Search stock..."
                onSearch={setSearch}
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
