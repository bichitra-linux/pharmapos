import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { inventoryService } from '@/services/inventory';
import { DataTable, type Column } from '@/components/ui/data-table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { formatDate } from '@/lib/utils';
import { useToast } from '@/components/ui/toast';
import { Plus } from 'lucide-react';
import type { InventoryAdjustment } from '@/types';

export default function AdjustmentsPage() {
    const { addToast } = useToast();
    const [page, setPage] = useState(1);

    const { data, isLoading } = useQuery({
        queryKey: ['inventory', 'adjustments', page],
        queryFn: () => inventoryService.getAdjustments({ page, per_page: 15 }),
    });

    const columns: Column<InventoryAdjustment>[] = [
        { key: 'adjustment_number', header: 'Adjustment #', sortable: true },
        { key: 'type', header: 'Type', sortable: true, render: (item) => <Badge>{item.type}</Badge> },
        { key: 'reason', header: 'Reason' },
        {
            key: 'status',
            header: 'Status',
            render: (item) => (
                <Badge variant={item.status === 'completed' ? 'success' : item.status === 'approved' ? 'default' : 'secondary'}>
                    {item.status}
                </Badge>
            ),
        },
        { key: 'created_at', header: 'Date', sortable: true, render: (item) => formatDate(item.created_at) },
    ];

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold">Stock Adjustments</h1>
                <Button onClick={() => addToast({ type: 'info', title: 'New adjustment coming soon' })}>
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
        </div>
    );
}
