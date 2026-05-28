import { useQuery } from '@tanstack/react-query';
import { superAdminService } from '@/services/super-admin';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { PageLoader } from '@/components/ui/spinner';
import { EmptyState } from '@/components/ui/empty-state';
import { Pagination } from '@/components/ui/pagination';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatCurrency, formatDate } from '@/lib/utils';
import { DollarSign, TrendingUp, CreditCard, Calendar } from 'lucide-react';
import { useState } from 'react';

export default function SuperAdminPaymentsPage() {
    const [page, setPage] = useState(1);

    const { data: payments, isLoading } = useQuery({
        queryKey: ['super-admin', 'payments', page],
        queryFn: () => superAdminService.getPayments(),
    });

    const { data: revenue } = useQuery({
        queryKey: ['super-admin', 'revenue'],
        queryFn: () => superAdminService.getRevenue(),
        select: (res) => res.data,
    });

    if (isLoading) return <PageLoader />;

    const paymentList = payments?.data ?? [];

    const statCards = [
        { title: 'Total Revenue', value: formatCurrency(revenue?.total ?? 0), icon: DollarSign, color: 'text-indigo-600', bg: 'bg-indigo-50' },
        { title: 'This Month', value: formatCurrency(revenue?.this_month ?? 0), icon: Calendar, color: 'text-success-600', bg: 'bg-success-50' },
        { title: 'This Year', value: formatCurrency(revenue?.this_year ?? 0), icon: TrendingUp, color: 'text-warning-600', bg: 'bg-warning-50' },
    ];

    return (
        <div className="space-y-6">
            <h1 className="text-2xl font-bold">Payments</h1>

            <div className="grid gap-4 sm:grid-cols-3">
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

            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <CreditCard className="h-5 w-5" />
                        All Payments
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    {paymentList.length > 0 ? (
                        <>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Company</TableHead>
                                        <TableHead>Plan</TableHead>
                                        <TableHead>Amount</TableHead>
                                        <TableHead>Method</TableHead>
                                        <TableHead>Gateway</TableHead>
                                        <TableHead>Period</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Date</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {paymentList.map((payment) => (
                                        <TableRow key={payment.id}>
                                            <TableCell className="font-medium">
                                                {payment.company?.name ?? `Company #${payment.company_id}`}
                                            </TableCell>
                                            <TableCell>
                                                {payment.plan?.name ?? `Plan #${payment.plan_id}`}
                                            </TableCell>
                                            <TableCell>{formatCurrency(payment.amount)}</TableCell>
                                            <TableCell className="capitalize">{payment.payment_method}</TableCell>
                                            <TableCell className="text-sm text-gray-500">
                                                {payment.gateway ?? '—'}
                                            </TableCell>
                                            <TableCell className="text-xs text-gray-500">
                                                {formatDate(payment.starts_at)} — {formatDate(payment.expires_at)}
                                            </TableCell>
                                            <TableCell>
                                                {payment.status === 'active' && <Badge variant="success">Active</Badge>}
                                                {payment.status === 'expired' && <Badge variant="secondary">Expired</Badge>}
                                                {payment.status === 'cancelled' && <Badge variant="destructive">Cancelled</Badge>}
                                            </TableCell>
                                            <TableCell className="text-sm text-gray-500">
                                                {formatDate(payment.created_at)}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                            {payments?.meta && (
                                <div className="mt-4">
                                    <Pagination
                                        currentPage={payments.meta.current_page}
                                        lastPage={payments.meta.last_page}
                                        onPageChange={setPage}
                                    />
                                </div>
                            )}
                        </>
                    ) : (
                        <EmptyState
                            icon={CreditCard}
                            title="No payments yet"
                            description="Payment records will appear here."
                        />
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
