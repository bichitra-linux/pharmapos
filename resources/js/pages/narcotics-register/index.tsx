import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '@/services/api';
import { extractPaginatedData, formatDate } from '@/lib/utils';
import { DataTable, type Column } from '@/components/ui/data-table';
import type { NarcoticsRegister } from '@/types';

export default function NarcoticsRegisterIndex() {
    const [page, setPage] = useState(1);

    const { data, isLoading } = useQuery({
        queryKey: ['narcotics', page],
        queryFn: async () => {
            const res = await api.get('/narcotics-register', { params: { page, per_page: 15 } });
            return extractPaginatedData<NarcoticsRegister>(res.data);
        },
    });

    const columns: Column<NarcoticsRegister>[] = [
        { key: 'created_at', header: 'Date', sortable: true, render: (item) => formatDate(item.created_at) },
        { key: 'brand_name', header: 'Medicine', render: (item) => item.brand_name || '-' },
        { key: 'patient_name', header: 'Patient' },
        { key: 'doctor_name', header: 'Doctor' },
        { key: 'quantity', header: 'Qty' },
        { key: 'balance', header: 'Balance' },
        { key: 'prescription_number', header: 'Prescription #' },
    ];

    return (
        <div className="space-y-4">
            <h1 className="text-2xl font-bold">Narcotics Register</h1>
            <DataTable
                columns={columns}
                data={(data?.data ?? []) as (NarcoticsRegister & Record<string, unknown>)[]}
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
