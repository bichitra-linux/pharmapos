import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { suppliersService } from '@/services/suppliers';
import { DataTable, type Column } from '@/components/ui/data-table';
import { Button } from '@/components/ui/button';
import { formatCurrency } from '@/lib/utils';
import { Plus } from 'lucide-react';
import type { Supplier } from '@/types';

export default function SuppliersIndex() {
    const navigate = useNavigate();
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);

    const { data, isLoading } = useQuery({
        queryKey: ['suppliers', search, page],
        queryFn: () => suppliersService.list({ search, page, per_page: 15 }),
    });

    const columns: Column<Supplier>[] = [
        { key: 'name', header: 'Name', sortable: true },
        { key: 'contact_person', header: 'Contact Person' },
        { key: 'phone', header: 'Phone' },
        { key: 'email', header: 'Email' },
        { key: 'outstanding_balance', header: 'Balance', render: (item) => formatCurrency(item.outstanding_balance) },
    ];

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold">Suppliers</h1>
                <Button onClick={() => navigate('/suppliers/create')}>
                    <Plus className="mr-2 h-4 w-4" />
                    Add Supplier
                </Button>
            </div>
            <DataTable
                columns={columns}
                data={(data?.data ?? []) as (Supplier & Record<string, unknown>)[]}
                loading={isLoading}
                searchable
                searchPlaceholder="Search suppliers..."
                onSearch={setSearch}
                onRowClick={(item) => navigate(`/suppliers/${(item as unknown as Supplier).id}/edit`)}
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
