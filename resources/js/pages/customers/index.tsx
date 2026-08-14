import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { customersService } from '@/services/customers';
import { DataTable, type Column } from '@/components/ui/data-table';
import { Button } from '@/components/ui/button';
import { formatCurrency } from '@/lib/utils';
import { Plus } from 'lucide-react';
import type { Customer } from '@/types';

export default function CustomersIndex() {
    const navigate = useNavigate();
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);

    const { data, isLoading } = useQuery({
        queryKey: ['customers', search, page],
        queryFn: () => customersService.list({ search, page, per_page: 15 }),
        staleTime: 30_000,
    });

    const columns: Column<Customer>[] = [
        { key: 'name', header: 'Name', sortable: true },
        { key: 'phone', header: 'Phone' },
        { key: 'email', header: 'Email' },
        { key: 'total_dues', header: 'Due', render: (item) => (
            <span className={item.total_dues > 0 ? 'text-warning-600 font-medium' : ''}>{formatCurrency(item.total_dues)}</span>
        )},
        { key: 'loyalty_points', header: 'Points', render: (item) => item.loyalty_points.toLocaleString() },
    ];

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold">Customers</h1>
                <Button onClick={() => navigate('/customers/create')}>
                    <Plus className="mr-2 h-4 w-4" />
                    Add Customer
                </Button>
            </div>
            <DataTable
                columns={columns}
                data={(data?.data ?? []) as (Customer & Record<string, unknown>)[]}
                loading={isLoading}
                searchable
                searchPlaceholder="Search customers..."
                onSearch={setSearch}
                onRowClick={(item) => navigate(`/customers/${(item as unknown as Customer).id}`)}
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
