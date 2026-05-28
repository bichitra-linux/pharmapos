import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { superAdminService } from '@/services/super-admin';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { PageLoader } from '@/components/ui/spinner';
import { EmptyState } from '@/components/ui/empty-state';
import { Pagination } from '@/components/ui/pagination';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogHeader, DialogTitle, DialogContent, DialogFooter } from '@/components/ui/dialog';
import { formatCurrency, formatDate } from '@/lib/utils';
import { Repeat, Plus, XCircle } from 'lucide-react';
import { differenceInDays, parseISO } from 'date-fns';

export default function SuperAdminSubscriptionsPage() {
    const queryClient = useQueryClient();
    const [page, setPage] = useState(1);
    const [extendId, setExtendId] = useState<number | null>(null);
    const [extendDays, setExtendDays] = useState('30');
    const [cancelId, setCancelId] = useState<number | null>(null);

    const { data, isLoading } = useQuery({
        queryKey: ['super-admin', 'subscriptions', page],
        queryFn: () => superAdminService.getSubscriptions(),
    });

    const extendMutation = useMutation({
        mutationFn: () => superAdminService.extendSubscription(extendId!, Number(extendDays)),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['super-admin', 'subscriptions'] });
            setExtendId(null);
            setExtendDays('30');
        },
    });

    const cancelMutation = useMutation({
        mutationFn: () => superAdminService.cancelSubscription(cancelId!),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['super-admin', 'subscriptions'] });
            setCancelId(null);
        },
    });

    const getStatusBadge = (expiresAt: string, status: string) => {
        if (status === 'cancelled') return <Badge variant="destructive">Cancelled</Badge>;
        if (status === 'expired') return <Badge variant="secondary">Expired</Badge>;
        const daysLeft = differenceInDays(parseISO(expiresAt), new Date());
        if (daysLeft < 0) return <Badge variant="destructive">Expired</Badge>;
        if (daysLeft <= 7) return <Badge variant="warning">Expiring Soon</Badge>;
        return <Badge variant="success">Active</Badge>;
    };

    if (isLoading) return <PageLoader />;

    const subscriptions = data?.data ?? [];

    return (
        <div className="space-y-6">
            <h1 className="text-2xl font-bold">Subscriptions</h1>

            <Card>
                <CardHeader>
                    <CardTitle>All Subscriptions</CardTitle>
                </CardHeader>
                <CardContent>
                    {subscriptions.length > 0 ? (
                        <>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Company</TableHead>
                                        <TableHead>Plan</TableHead>
                                        <TableHead>Amount</TableHead>
                                        <TableHead>Starts</TableHead>
                                        <TableHead>Expires</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="w-24">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {subscriptions.map((sub) => (
                                        <TableRow key={sub.id}>
                                            <TableCell className="font-medium">
                                                {sub.company?.name ?? `Company #${sub.company_id}`}
                                            </TableCell>
                                            <TableCell>
                                                {sub.plan?.name ?? `Plan #${sub.plan_id}`}
                                            </TableCell>
                                            <TableCell>{formatCurrency(sub.amount)}</TableCell>
                                            <TableCell className="text-sm text-gray-500">
                                                {formatDate(sub.starts_at)}
                                            </TableCell>
                                            <TableCell className="text-sm text-gray-500">
                                                {formatDate(sub.expires_at)}
                                            </TableCell>
                                            <TableCell>
                                                {getStatusBadge(sub.expires_at, sub.status)}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => setExtendId(sub.company_id)}
                                                        title="Extend"
                                                    >
                                                        <Plus className="mr-1 h-3 w-3" /> Extend
                                                    </Button>
                                                    {sub.status === 'active' && (
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => setCancelId(sub.company_id)}
                                                            title="Cancel"
                                                        >
                                                            <XCircle className="h-3 w-3 text-danger-500" />
                                                        </Button>
                                                    )}
                                                </div>
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
                            icon={Repeat}
                            title="No subscriptions"
                            description="Subscriptions will appear here when tenants subscribe to plans."
                        />
                    )}
                </CardContent>
            </Card>

            <Dialog open={extendId !== null} onClose={() => setExtendId(null)}>
                <DialogHeader>
                    <DialogTitle>Extend Subscription</DialogTitle>
                </DialogHeader>
                <DialogContent>
                    <Input
                        label="Number of Days"
                        type="number"
                        value={extendDays}
                        onChange={(e) => setExtendDays(e.target.value)}
                        placeholder="30"
                        min="1"
                    />
                </DialogContent>
                <DialogFooter>
                    <Button variant="outline" onClick={() => setExtendId(null)}>
                        Cancel
                    </Button>
                    <Button
                        loading={extendMutation.isPending}
                        onClick={() => extendMutation.mutate()}
                    >
                        Extend
                    </Button>
                </DialogFooter>
            </Dialog>

            <Dialog open={cancelId !== null} onClose={() => setCancelId(null)}>
                <DialogHeader>
                    <DialogTitle>Cancel Subscription</DialogTitle>
                </DialogHeader>
                <DialogContent>
                    <p className="text-sm text-gray-600">
                        Are you sure you want to cancel this subscription? The tenant will lose access to premium features.
                    </p>
                </DialogContent>
                <DialogFooter>
                    <Button variant="outline" onClick={() => setCancelId(null)}>
                        Keep Active
                    </Button>
                    <Button
                        variant="destructive"
                        loading={cancelMutation.isPending}
                        onClick={() => cancelMutation.mutate()}
                    >
                        Cancel Subscription
                    </Button>
                </DialogFooter>
            </Dialog>
        </div>
    );
}
