import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { prescriptionsService } from '@/services/prescriptions';
import { DataTable, type Column } from '@/components/ui/data-table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { formatDate } from '@/lib/utils';
import { Plus } from 'lucide-react';
import type { Prescription } from '@/types';

export default function PrescriptionsIndex() {
    const navigate = useNavigate();
    const [page, setPage] = useState(1);

    const { data, isLoading } = useQuery({
        queryKey: ['prescriptions', page],
        queryFn: () => prescriptionsService.list({ page, per_page: 15 }),
        staleTime: 30_000,
    });

    const columns: Column<Prescription>[] = [
        { key: 'id', header: '#', sortable: true },
        { key: 'customer', header: 'Customer', render: (item) => item.customer?.name || '-' },
        { key: 'doctor_name', header: 'Doctor', sortable: true },
        { key: 'prescription_date', header: 'Date', sortable: true, render: (item) => formatDate(item.prescription_date) },
        {
            key: 'status',
            header: 'Status',
            render: (item) => (
                <Badge variant={item.status === 'dispensed' ? 'success' : item.status === 'cancelled' ? 'destructive' : 'default'}>
                    {item.status}
                </Badge>
            ),
        },
    ];

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold">Prescriptions</h1>
                <Button onClick={() => navigate('/prescriptions/create')}>
                    <Plus className="mr-2 h-4 w-4" />
                    Add Prescription
                </Button>
            </div>
            <DataTable
                columns={columns}
                data={(data?.data ?? []) as (Prescription & Record<string, unknown>)[]}
                loading={isLoading}
                onRowClick={(item) => navigate(`/prescriptions/${(item as unknown as Prescription).id}`)}
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
