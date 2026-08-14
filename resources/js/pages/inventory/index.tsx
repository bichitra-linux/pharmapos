import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { inventoryService } from '@/services/inventory';
import { DataTable, type Column } from '@/components/ui/data-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatCurrency, formatDate } from '@/lib/utils';
import type { MedicineBatch } from '@/types';

export default function InventoryIndex() {
    const navigate = useNavigate();
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);

    const { data, isLoading } = useQuery({
        queryKey: ['inventory', 'stock', search, page],
        queryFn: () => inventoryService.getStock({ search, page, per_page: 15 }),
        staleTime: 30_000,
    });

    const columns: Column<MedicineBatch>[] = [
        {
            key: 'medicine',
            header: 'Medicine',
            sortable: false,
            render: (item) => (
                <div>
                    <p className="font-medium">{item.medicine?.brand_name || '-'}</p>
                    <p className="text-xs text-text-muted">{item.medicine?.generic_name}</p>
                </div>
            ),
        },
        { key: 'batch_number', header: 'Batch #', sortable: true },
        {
            key: 'expiry_date',
            header: 'Expiry',
            sortable: true,
            render: (item) => {
                const isExpiring = new Date(item.expiry_date) < new Date(Date.now() + 90 * 24 * 60 * 60 * 1000);
                const isExpired = new Date(item.expiry_date) < new Date();
                return (
                    <span className={isExpired ? 'text-danger-600 font-medium' : isExpiring ? 'text-warning-600' : ''}>
                        {formatDate(item.expiry_date)}
                        {isExpired && ' (Expired)'}
                    </span>
                );
            },
        },
        { key: 'quantity_in_stock', header: 'Stock', sortable: true },
        {
            key: 'purchase_price_per_unit',
            header: 'Purchase Price',
            render: (item) => formatCurrency(item.purchase_price_per_unit),
        },
        {
            key: 'selling_price_per_unit',
            header: 'Selling Price',
            render: (item) => formatCurrency(item.selling_price_per_unit),
        },
        {
            key: 'medicine',
            header: 'Category',
            render: (item) => item.medicine?.medicine_category?.name || '-',
        },
        {
            key: 'medicine',
            header: 'Schedule',
            render: (item) => {
                const schedule = item.medicine?.schedule_type;
                const variant = schedule === 'x' ? 'destructive' : schedule === 'h' || schedule === 'h1' ? 'warning' : 'secondary';
                return <Badge variant={variant}>{schedule?.toUpperCase() || '-'}</Badge>;
            },
        },
    ];

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold">Inventory / Stock</h1>
                <div className="flex gap-2">
                    <Button variant="outline" onClick={() => navigate('/inventory/reorder')}>
                        Reorder Suggestions
                    </Button>
                    <Button variant="outline" onClick={() => navigate('/inventory/adjustments')}>
                        Adjustments
                    </Button>
                </div>
            </div>
            <DataTable
                columns={columns}
                data={(data?.data ?? []) as (MedicineBatch & Record<string, unknown>)[]}
                loading={isLoading}
                searchable
                searchPlaceholder="Search by medicine name, generic name, or barcode..."
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
