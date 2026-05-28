import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { superAdminService } from '@/services/super-admin';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { PageLoader } from '@/components/ui/spinner';
import { EmptyState } from '@/components/ui/empty-state';
import { Pagination } from '@/components/ui/pagination';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { DropdownMenu, DropdownMenuItem, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import { Dialog, DialogHeader, DialogTitle, DialogContent, DialogFooter } from '@/components/ui/dialog';
import { formatDate } from '@/lib/utils';
import {
    Building2,
    Plus,
    Search,
    MoreVertical,
    Eye,
    PauseCircle,
    PlayCircle,
    Trash2,
    ChevronDown,
} from 'lucide-react';

export default function SuperAdminTenantsPage() {
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('all');
    const [page, setPage] = useState(1);
    const [deleteId, setDeleteId] = useState<number | null>(null);

    const { data, isLoading } = useQuery({
        queryKey: ['super-admin', 'tenants', search, status, page],
        queryFn: () =>
            superAdminService.getTenants({
                search: search || undefined,
                status: status !== 'all' ? status : undefined,
                page,
            }),
    });

    const suspendMutation = useMutation({
        mutationFn: ({ id, reason }: { id: number; reason: string }) =>
            superAdminService.suspendTenant(id, reason),
        onSuccess: () => queryClient.invalidateQueries({ queryKey: ['super-admin', 'tenants'] }),
    });

    const activateMutation = useMutation({
        mutationFn: (id: number) => superAdminService.activateTenant(id),
        onSuccess: () => queryClient.invalidateQueries({ queryKey: ['super-admin', 'tenants'] }),
    });

    const deleteMutation = useMutation({
        mutationFn: (id: number) => superAdminService.deleteTenant(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['super-admin', 'tenants'] });
            setDeleteId(null);
        },
    });

    const handleSuspend = (id: number) => {
        const reason = prompt('Enter suspension reason:');
        if (reason) suspendMutation.mutate({ id, reason });
    };

    const statusOptions = [
        { label: 'All Status', value: 'all' },
        { label: 'Active', value: 'active' },
        { label: 'Suspended', value: 'suspended' },
    ];

    if (isLoading) return <PageLoader />;

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold">Tenants</h1>
                <Button onClick={() => navigate('/super-admin/tenants/create')}>
                    <Plus className="mr-2 h-4 w-4" /> Create Tenant
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <CardTitle>All Tenants</CardTitle>
                        <div className="flex gap-3">
                            <div className="relative w-64">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                <input
                                    type="text"
                                    value={search}
                                    onChange={(e) => {
                                        setSearch(e.target.value);
                                        setPage(1);
                                    }}
                                    placeholder="Search by name or email..."
                                    className="flex h-10 w-full rounded-md border border-gray-300 bg-white pl-9 pr-3 text-sm placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                                />
                            </div>
                            <Select
                                options={statusOptions}
                                value={status}
                                onChange={(val) => {
                                    setStatus(String(val));
                                    setPage(1);
                                }}
                            />
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    {data?.data && data.data.length > 0 ? (
                        <>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Email</TableHead>
                                        <TableHead>Plan</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Expires</TableHead>
                                        <TableHead>Created</TableHead>
                                        <TableHead className="w-12">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {data.data.map((tenant) => (
                                        <TableRow key={tenant.id}>
                                            <TableCell className="font-medium">{tenant.name}</TableCell>
                                            <TableCell className="text-sm text-gray-500">{tenant.email}</TableCell>
                                            <TableCell>
                                                {tenant.subscription_plan_id ? (
                                                    <Badge variant="default">Plan #{tenant.subscription_plan_id}</Badge>
                                                ) : (
                                                    <span className="text-sm text-gray-400">None</span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {tenant.suspended_at ? (
                                                    <Badge variant="destructive">Suspended</Badge>
                                                ) : tenant.is_active ? (
                                                    <Badge variant="success">Active</Badge>
                                                ) : (
                                                    <Badge variant="secondary">Inactive</Badge>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-sm text-gray-500">
                                                {tenant.subscription_expires_at
                                                    ? formatDate(tenant.subscription_expires_at)
                                                    : '—'}
                                            </TableCell>
                                            <TableCell className="text-sm text-gray-500">
                                                {formatDate(tenant.created_at)}
                                            </TableCell>
                                            <TableCell>
                                                <DropdownMenu
                                                    trigger={
                                                        <button className="rounded p-1 hover:bg-gray-100">
                                                            <MoreVertical className="h-4 w-4 text-gray-500" />
                                                        </button>
                                                    }
                                                >
                                                    <DropdownMenuItem onClick={() => navigate(`/super-admin/tenants/${tenant.id}`)}>
                                                        <Eye className="mr-2 h-4 w-4" /> View
                                                    </DropdownMenuItem>
                                                    <DropdownMenuSeparator />
                                                    {tenant.suspended_at ? (
                                                        <DropdownMenuItem onClick={() => activateMutation.mutate(tenant.id)}>
                                                            <PlayCircle className="mr-2 h-4 w-4" /> Activate
                                                        </DropdownMenuItem>
                                                    ) : (
                                                        <DropdownMenuItem onClick={() => handleSuspend(tenant.id)}>
                                                            <PauseCircle className="mr-2 h-4 w-4" /> Suspend
                                                        </DropdownMenuItem>
                                                    )}
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem onClick={() => setDeleteId(tenant.id)} destructive>
                                                        <Trash2 className="mr-2 h-4 w-4" /> Delete
                                                    </DropdownMenuItem>
                                                </DropdownMenu>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                            {data.meta && (
                                <div className="mt-4">
                                    <Pagination
                                        currentPage={data.meta.current_page}
                                        lastPage={data.meta.last_page}
                                        onPageChange={setPage}
                                    />
                                </div>
                            )}
                        </>
                    ) : (
                        <EmptyState
                            icon={Building2}
                            title="No tenants found"
                            description="Create your first tenant to get started."
                        />
                    )}
                </CardContent>
            </Card>

            <Dialog open={deleteId !== null} onClose={() => setDeleteId(null)}>
                <DialogHeader>
                    <DialogTitle>Delete Tenant</DialogTitle>
                </DialogHeader>
                <DialogContent>
                    <p className="text-sm text-gray-600">
                        Are you sure you want to delete this tenant? This action cannot be undone.
                        All data associated with this tenant will be permanently removed.
                    </p>
                </DialogContent>
                <DialogFooter>
                    <Button variant="outline" onClick={() => setDeleteId(null)}>
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        loading={deleteMutation.isPending}
                        onClick={() => deleteId && deleteMutation.mutate(deleteId)}
                    >
                        Delete
                    </Button>
                </DialogFooter>
            </Dialog>
        </div>
    );
}
