import { useQuery } from '@tanstack/react-query';
import { superAdminService } from '@/services/super-admin';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { PageLoader } from '@/components/ui/spinner';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatCurrency, formatDate } from '@/lib/utils';
import {
    Building2,
    TrendingUp,
    AlertTriangle,
    UserPlus,
    DollarSign,
    PauseCircle,
} from 'lucide-react';

export default function SuperAdminDashboardPage() {
    const { data: dashboard, isLoading } = useQuery({
        queryKey: ['super-admin', 'dashboard'],
        queryFn: () => superAdminService.getDashboard(),
        select: (res) => res.data,
    });

    if (isLoading) return <PageLoader />;

    const statCards = [
        { title: 'Total Tenants', value: dashboard?.total_tenants ?? 0, icon: Building2, color: 'text-indigo-600', bg: 'bg-indigo-50' },
        { title: 'Active', value: dashboard?.active_tenants ?? 0, icon: TrendingUp, color: 'text-success-600', bg: 'bg-success-50' },
        { title: 'Suspended', value: dashboard?.suspended_tenants ?? 0, icon: PauseCircle, color: 'text-danger-600', bg: 'bg-danger-50' },
        { title: 'MRR', value: formatCurrency(dashboard?.mrr ?? 0), icon: DollarSign, color: 'text-indigo-600', bg: 'bg-indigo-50' },
        { title: 'Expiring Soon', value: dashboard?.expiring_soon ?? 0, icon: AlertTriangle, color: 'text-warning-600', bg: 'bg-warning-50' },
        { title: 'New This Month', value: dashboard?.new_this_month ?? 0, icon: UserPlus, color: 'text-success-600', bg: 'bg-success-50' },
    ];

    return (
        <div className="space-y-6">
            <h1 className="text-2xl font-bold">Platform Dashboard</h1>

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {statCards.map((card) => (
                    <Card key={card.title}>
                        <CardContent className="flex items-center gap-4 p-6">
                            <div className={`rounded-lg p-3 ${card.bg}`}>
                                <card.icon className={`h-6 w-6 ${card.color}`} />
                            </div>
                            <div>
                                <p className="text-sm text-gray-500">{card.title}</p>
                                <p className="text-2xl font-bold">{card.value}</p>
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <TrendingUp className="h-5 w-5" />
                            Monthly Revenue
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="h-64">
                            {dashboard?.monthly_revenue && dashboard.monthly_revenue.length > 0 ? (
                                <div className="flex h-full items-end gap-1">
                                    {dashboard.monthly_revenue.map((item, i) => {
                                        const maxTotal = Math.max(...dashboard.monthly_revenue.map((d) => d.total));
                                        const height = maxTotal > 0 ? (item.total / maxTotal) * 100 : 0;
                                        return (
                                            <div
                                                key={i}
                                                className="group relative flex-1"
                                            >
                                                <div
                                                    className="bg-indigo-500 hover:bg-indigo-600 transition-colors"
                                                    style={{ height: `${Math.max(2, height)}%` }}
                                                />
                                                <div className="absolute -top-6 left-1/2 -translate-x-1/2 hidden group-hover:block whitespace-nowrap rounded bg-gray-900 px-2 py-1 text-xs text-white">
                                                    {item.month}: {formatCurrency(item.total)}
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            ) : (
                                <div className="flex h-full items-center justify-center text-gray-400">
                                    No revenue data
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Building2 className="h-5 w-5 text-indigo-500" />
                            Recent Tenants
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Joined</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {dashboard?.recent_tenants?.slice(0, 5).map((tenant) => (
                                    <TableRow key={tenant.id}>
                                        <TableCell className="font-medium">{tenant.name}</TableCell>
                                        <TableCell className="text-sm text-gray-500">{tenant.email}</TableCell>
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
                                            {formatDate(tenant.created_at)}
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {(!dashboard?.recent_tenants || dashboard.recent_tenants.length === 0) && (
                                    <TableRow>
                                        <TableCell colSpan={4} className="text-center text-gray-500">
                                            No tenants yet
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}
