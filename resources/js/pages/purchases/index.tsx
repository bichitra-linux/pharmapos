import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { purchasesService } from '@/services/purchases';
import { DataTable, type Column } from '@/components/ui/data-table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { formatCurrency, formatDate } from '@/lib/utils';
import { Plus } from 'lucide-react';
import type { Purchase } from '@/types';

export default function PurchasesIndex() {
    const navigate = useNavigate();
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);

    const { data, isLoading } = useQuery({
        queryKey: ['purchases', search, page],
        queryFn: () => purchasesService.list({ search, page, per_page: 15 }),
    });

    const columns: Column<Purchase>[] = [
        { key: 'purchase_number', header: 'Purchase #', sortable: true },
        { key: 'supplier', header: 'Supplier', render: (item) => item.supplier?.name || '-' },
        { key: 'invoice_date', header: 'Invoice Date', sortable: true, render: (item) => formatDate(item.invoice_date) },
        { key: 'total_amount', header: 'Total', sortable: true, render: (item) => formatCurrency(item.total_amount) },
        { key: 'paid_amount', header: 'Paid', render: (item) => formatCurrency(item.paid_amount) },
        { key: 'due_amount', header: 'Due', render: (item) => formatCurrency(item.due_amount) },
        {
            key: 'status',
            header: 'Status',
            render: (item) => (
                <Badge variant={item.status === 'received' ? 'success' : item.status === 'cancelled' ? 'destructive' : 'default'}>
                    {item.status}
                </Badge>
            ),
        },
    ];

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold">Purchases</h1>
                <Button onClick={() => navigate('/purchases/create')}>
                    <Plus className="mr-2 h-4 w-4" />
                    New Purchase
                </Button>
            </div>
            <DataTable
                columns={columns}
                data={(data?.data ?? []) as (Purchase & Record<string, unknown>)[]}
                loading={isLoading}
                searchable
                searchPlaceholder="Search purchases..."
                onSearch={setSearch}
                onRowClick={(item) => navigate(`/purchases/${(item as unknown as Purchase).id}`)}
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
