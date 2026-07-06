import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { superAdminService } from '@/services/super-admin';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { PageLoader } from '@/components/ui/spinner';
import { EmptyState } from '@/components/ui/empty-state';
import { Pagination } from '@/components/ui/pagination';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDate } from '@/lib/utils';
import { ClipboardList, Search } from 'lucide-react';

const actionLabels: Record<string, string> = {
    super_admin_impersonated: 'Impersonated',
    created: 'Created',
    updated: 'Updated',
    deleted: 'Deleted',
    suspended: 'Suspended',
    activated: 'Activated',
};

const actionVariants: Record<string, 'default' | 'warning' | 'destructive' | 'success'> = {
    created: 'success',
    updated: 'default',
    deleted: 'destructive',
    suspended: 'destructive',
    activated: 'success',
};

export default function SuperAdminAuditLogsPage() {
    const [search, setSearch] = useState('');
    const [action, setAction] = useState('');
    const [page, setPage] = useState(1);

    const { data, isLoading } = useQuery({
        queryKey: ['super-admin', 'audit-logs', search, action, page],
        queryFn: () => superAdminService.getAuditLogs({
            ...(search ? { company_id: search } : {}),
            ...(action ? { action } : {}),
            page: String(page),
        } as Record<string, string | number | boolean>),
    });

    if (isLoading) return <PageLoader />;

    const logs = data?.data ?? [];
    const actions = ['created', 'updated', 'deleted', 'suspended', 'activated', 'super_admin_impersonated'];

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold">Audit Logs</h1>
            </div>

            <Card>
                <CardHeader>
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <CardTitle>All Activity</CardTitle>
                        <div className="flex gap-3">
                            <div className="relative w-48">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-text-muted" />
                                <input
                                    type="text"
                                    value={search}
                                    onChange={(e) => { setSearch(e.target.value); setPage(1); }}
                                    placeholder="Company ID..."
                                    className="flex h-10 w-full rounded-md border border-border bg-surface pl-9 pr-3 text-sm placeholder:text-text-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                                />
                            </div>
                            <Select
                                options={[{ label: 'All Actions', value: '' }, ...actions.map(a => ({ label: actionLabels[a] || a, value: a }))]}
                                value={action}
                                onChange={(v) => { setAction(String(v)); setPage(1); }}
                            />
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    {logs.length > 0 ? (
                        <>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Date</TableHead>
                                        <TableHead>Company</TableHead>
                                        <TableHead>User</TableHead>
                                        <TableHead>Action</TableHead>
                                        <TableHead>Details</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {logs.map((log: Record<string, unknown>) => (
                                        <TableRow key={log.id as number}>
                                            <TableCell className="text-sm text-text-muted whitespace-nowrap">
                                                {formatDate(log.created_at as string)}
                                            </TableCell>
                                            <TableCell className="font-medium">
                                                {(log.company as Record<string, unknown>)?.name as string ?? '-'}
                                            </TableCell>
                                            <TableCell className="text-sm text-text-muted">
                                                {(log.user as Record<string, unknown>)?.name as string ?? '-'}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={actionVariants[log.action as string] ?? 'secondary'}>
                                                    {actionLabels[log.action as string] || (log.action as string)}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-xs text-text-muted max-w-48 truncate">
                                                {log.new_values ? JSON.stringify((log.new_values as Record<string, string>)['super_admin_name'] || (log.new_values as Record<string, string>)['reason'] || '').replace(/"/g, '') : '-'}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                            {data?.meta && (
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
                            icon={ClipboardList}
                            title="No audit logs"
                            description="Activity will appear here as users interact with the system."
                        />
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
