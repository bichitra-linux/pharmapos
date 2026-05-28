import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { salesService } from '@/services/sales';
import { DataTable, type Column } from '@/components/ui/data-table';
import { Badge } from '@/components/ui/badge';
import { formatCurrency, formatDate } from '@/lib/utils';
import type { Sale } from '@/types';

export default function SalesIndex() {
    const navigate = useNavigate();
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);

    const { data, isLoading } = useQuery({
        queryKey: ['sales', search, page],
        queryFn: () => salesService.list({ search, page, per_page: 15 }),
    });

    const columns: Column<Sale>[] = [
        { key: 'invoice_number', header: 'Invoice #', sortable: true },
        { key: 'customer', header: 'Customer', render: (item) => item.customer?.name || 'Walk-in' },
        { key: 'created_at', header: 'Date', sortable: true, render: (item) => formatDate(item.created_at, 'dd/MM/yyyy HH:mm') },
        { key: 'total_amount', header: 'Total', sortable: true, render: (item) => formatCurrency(item.total_amount) },
        { key: 'paid_amount', header: 'Paid', render: (item) => formatCurrency(item.paid_amount) },
        {
            key: 'payment_status',
            header: 'Payment',
            render: (item) => (
                <Badge variant={item.payment_status === 'paid' ? 'success' : item.payment_status === 'partial' ? 'warning' : 'destructive'}>
                    {item.payment_status}
                </Badge>
            ),
        },
        {
            key: 'status',
            header: 'Status',
            render: (item) => (
                <Badge variant={item.status === 'completed' ? 'success' : item.status === 'cancelled' ? 'destructive' : 'default'}>
                    {item.status}
                </Badge>
            ),
        },
    ];

    return (
        <div className="space-y-4">
            <h1 className="text-2xl font-bold">Sales</h1>
            <DataTable
                columns={columns}
                data={(data?.data ?? []) as (Sale & Record<string, unknown>)[]}
                loading={isLoading}
                searchable
                searchPlaceholder="Search sales..."
                onSearch={setSearch}
                onRowClick={(item) => navigate(`/sales/${(item as unknown as Sale).id}`)}
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
