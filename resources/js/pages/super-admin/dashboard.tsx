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
        { title: 'Total Tenants', value: dashboard?.total_tenants ?? 0, icon: Building2, color: 'text-primary-600', bg: 'bg-primary-50', description: 'All registered pharmacies', span: true },
        { title: 'Active', value: dashboard?.active_tenants ?? 0, icon: TrendingUp, color: 'text-success-600', bg: 'bg-success-50' },
        { title: 'Suspended', value: dashboard?.suspended_tenants ?? 0, icon: PauseCircle, color: 'text-danger-600', bg: 'bg-danger-50' },
        { title: 'MRR', value: formatCurrency(dashboard?.mrr ?? 0), icon: DollarSign, color: 'text-primary-600', bg: 'bg-primary-50' },
        { title: 'Expiring Soon', value: dashboard?.expiring_soon ?? 0, icon: AlertTriangle, color: 'text-warning-600', bg: 'bg-warning-50' },
        { title: 'New This Month', value: dashboard?.new_this_month ?? 0, icon: UserPlus, color: 'text-success-600', bg: 'bg-success-50' },
    ];

    return (
        <div className="space-y-6">
            <h1 className="text-2xl font-bold">Platform Dashboard</h1>

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {statCards.map((card) => (
                    <Card key={card.title} className={card.span ? 'lg:col-span-2' : undefined}>
                        <CardContent className="flex items-center gap-4 p-6">
                            <div className={`rounded-lg p-3 ${card.bg}`}>
                                <card.icon className={`h-6 w-6 ${card.color}`} aria-hidden="true" />
                            </div>
                            <div>
                                <p className="text-sm text-text-muted">{card.title}</p>
                                <p className="text-2xl font-bold">{card.value}</p>
                                {card.description && (
                                    <p className="text-xs text-text-muted">{card.description}</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardContent className="p-4">
                        <p className="text-xs font-medium uppercase text-text-muted">Expiring in 7 days</p>
                        <p className="text-xl font-bold text-warning-600">{dashboard?.expiring_soon_7 ?? 0}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-4">
                        <p className="text-xs font-medium uppercase text-text-muted">Expiring in 14 days</p>
                        <p className="text-xl font-bold text-warning-600">{dashboard?.expiring_soon_14 ?? 0}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-4">
                        <p className="text-xs font-medium uppercase text-text-muted">Expiring in 30 days</p>
                        <p className="text-xl font-bold text-warning-600">{dashboard?.expiring_soon_30 ?? 0}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-4">
                        <p className="text-xs font-medium uppercase text-text-muted">Churn Rate</p>
                        <p className="text-xl font-bold text-danger-600">{dashboard?.churn_rate ?? 0}%</p>
                        <p className="text-xs text-text-muted">{dashboard?.cancelled_this_month ?? 0} cancelled this month</p>
                    </CardContent>
                </Card>
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
                                                    className="bg-primary-500 hover:bg-primary-600 transition-colors"
                                                    style={{ height: `${Math.max(2, height)}%` }}
                                                />
                                                <div className="absolute -top-6 left-1/2 -translate-x-1/2 hidden group-hover:block whitespace-nowrap rounded bg-surface px-2 py-1 text-xs text-text shadow-lg border border-border">
                                                    {item.month}: {formatCurrency(item.total)}
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            ) : (
                                <div className="flex h-full items-center justify-center text-text-muted">
                                    No revenue data
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Building2 className="h-5 w-5 text-primary-500" />
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
                                        <TableCell className="text-sm text-text-muted">{tenant.email}</TableCell>
                                        <TableCell>
                                            {tenant.suspended_at ? (
                                                <Badge variant="destructive">Suspended</Badge>
                                            ) : tenant.is_active ? (
                                                <Badge variant="success">Active</Badge>
                                            ) : (
                                                <Badge variant="secondary">Inactive</Badge>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-sm text-text-muted">
                                            {formatDate(tenant.created_at)}
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {(!dashboard?.recent_tenants || dashboard.recent_tenants.length === 0) && (
                                    <TableRow>
                                        <TableCell colSpan={4} className="text-center text-text-muted">
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
