import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '@/services/api';
import { extractPaginatedData } from '@/lib/utils';
import { DataTable, type Column } from '@/components/ui/data-table';
import { Badge } from '@/components/ui/badge';
import { formatCurrency, formatDate } from '@/lib/utils';
import type { CustomerReturn, SupplierReturn } from '@/types';

export default function ReturnsIndex() {
    const [tab, setTab] = useState<'customer' | 'supplier'>('customer');
    const [page, setPage] = useState(1);

    const { data: customerReturns, isLoading: loadingCustomer } = useQuery({
        queryKey: ['returns', 'customer', page],
        queryFn: async () => {
            const res = await api.get('/sale-returns', { params: { page, per_page: 15 } });
            return extractPaginatedData<CustomerReturn>(res.data);
        },
        enabled: tab === 'customer',
        staleTime: 30_000,
    });

    const { data: supplierReturns, isLoading: loadingSupplier } = useQuery({
        queryKey: ['returns', 'supplier', page],
        queryFn: async () => {
            const res = await api.get('/supplier-returns', { params: { page, per_page: 15 } });
            return extractPaginatedData<SupplierReturn>(res.data);
        },
        enabled: tab === 'supplier',
        staleTime: 30_000,
    });

    const customerColumns: Column<CustomerReturn>[] = [
        { key: 'return_number', header: 'Return #', sortable: true },
        { key: 'sale', header: 'Sale #', render: (item) => item.sale?.invoice_number || '-' },
        { key: 'customer', header: 'Customer', render: (item) => item.sale?.customer?.name || '-' },
        { key: 'total_amount', header: 'Amount', render: (item) => formatCurrency(item.total_amount) },
        { key: 'reason', header: 'Reason' },
        { key: 'created_at', header: 'Date', sortable: true, render: (item) => formatDate(item.created_at) },
    ];

    const supplierColumns: Column<SupplierReturn>[] = [
        { key: 'return_number', header: 'Return #', sortable: true },
        { key: 'purchase_number', header: 'Purchase #', render: (item) => item.purchase_number || '-' },
        { key: 'supplier_name', header: 'Supplier', render: (item) => item.supplier_name || '-' },
        { key: 'total_amount', header: 'Amount', render: (item) => formatCurrency(item.total_amount) },
        { key: 'reason', header: 'Reason' },
        {
            key: 'refund_status',
            header: 'Status',
            render: (item) => (
                <Badge variant={item.refund_status === 'received' ? 'success' : item.refund_status === 'cancelled' ? 'destructive' : 'default'}>
                    {item.refund_status}
                </Badge>
            ),
        },
    ];

    return (
        <div className="space-y-4">
            <h1 className="text-2xl font-bold">Returns</h1>

            <div className="flex gap-1 rounded-lg bg-surface-muted p-1 w-fit" role="tablist">
                <button
                    role="tab"
                    aria-selected={tab === 'customer'}
                    onClick={() => { setTab('customer'); setPage(1); }}
                    className={`rounded-md px-4 py-2 text-sm font-medium ${tab === 'customer' ? 'bg-surface shadow' : 'text-text-muted'}`}
                >
                    Customer Returns
                </button>
                <button
                    role="tab"
                    aria-selected={tab === 'supplier'}
                    onClick={() => { setTab('supplier'); setPage(1); }}
                    className={`rounded-md px-4 py-2 text-sm font-medium ${tab === 'supplier' ? 'bg-surface shadow' : 'text-text-muted'}`}
                >
                    Supplier Returns
                </button>
            </div>

            {tab === 'customer' ? (
                <DataTable
                    columns={customerColumns}
                    data={(customerReturns?.data ?? []) as (CustomerReturn & Record<string, unknown>)[]}
                    loading={loadingCustomer}
                    pagination={{
                        currentPage: customerReturns?.meta?.current_page ?? 1,
                        lastPage: customerReturns?.meta?.last_page ?? 1,
                        total: customerReturns?.meta?.total ?? 0,
                        onPageChange: setPage,
                    }}
                />
            ) : (
                <DataTable
                    columns={supplierColumns}
                    data={(supplierReturns?.data ?? []) as (SupplierReturn & Record<string, unknown>)[]}
                    loading={loadingSupplier}
                    pagination={{
                        currentPage: supplierReturns?.meta?.current_page ?? 1,
                        lastPage: supplierReturns?.meta?.last_page ?? 1,
                        total: supplierReturns?.meta?.total ?? 0,
                        onPageChange: setPage,
                    }}
                />
            )}
        </div>
    );
}
