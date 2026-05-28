import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '@/services/api';
import { DataTable, type Column } from '@/components/ui/data-table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Plus } from 'lucide-react';
import type { User } from '@/types';

export default function UsersIndex() {
    const navigate = useNavigate();
    const [page, setPage] = useState(1);

    const { data, isLoading } = useQuery({
        queryKey: ['users', page],
        queryFn: async () => {
            const res = await api.get('/users', { params: { page, per_page: 15 } });
            return res.data;
        },
    });

    const columns: Column<User>[] = [
        { key: 'name', header: 'Name', sortable: true },
        { key: 'email', header: 'Email' },
        { key: 'phone', header: 'Phone' },
        {
            key: 'role',
            header: 'Role',
            render: (item) => <Badge>{item.role}</Badge>,
        },
        {
            key: 'is_active',
            header: 'Status',
            render: (item) => (
                <Badge variant={item.is_active ? 'success' : 'destructive'}>
                    {item.is_active ? 'Active' : 'Inactive'}
                </Badge>
            ),
        },
    ];

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold">Users</h1>
                <Button onClick={() => navigate('/users/create')}>
                    <Plus className="mr-2 h-4 w-4" />
                    Add User
                </Button>
            </div>
            <DataTable
                columns={columns}
                data={(data?.data ?? []) as (User & Record<string, unknown>)[]}
                loading={isLoading}
                onRowClick={(item) => navigate(`/users/${(item as unknown as User).id}/edit`)}
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
