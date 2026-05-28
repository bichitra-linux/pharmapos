import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { superAdminService } from '@/services/super-admin';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { PageLoader } from '@/components/ui/spinner';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatCurrency, formatDate } from '@/lib/utils';
import {
    ArrowLeft,
    Edit,
    PauseCircle,
    PlayCircle,
    Users,
    CreditCard,
    Building2,
    Mail,
    Phone,
    MapPin,
    FileText,
} from 'lucide-react';

export default function SuperAdminTenantShowPage() {
    const { id } = useParams<{ id: string }>();
    const navigate = useNavigate();
    const queryClient = useQueryClient();

    const { data: tenant, isLoading } = useQuery({
        queryKey: ['super-admin', 'tenants', id],
        queryFn: () => superAdminService.getTenant(Number(id)),
        select: (res) => res.data,
    });

    const suspendMutation = useMutation({
        mutationFn: (reason: string) => superAdminService.suspendTenant(Number(id), reason),
        onSuccess: () => queryClient.invalidateQueries({ queryKey: ['super-admin', 'tenants', id] }),
    });

    const activateMutation = useMutation({
        mutationFn: () => superAdminService.activateTenant(Number(id)),
        onSuccess: () => queryClient.invalidateQueries({ queryKey: ['super-admin', 'tenants', id] }),
    });

    if (isLoading) return <PageLoader />;
    if (!tenant) return <div className="py-12 text-center text-gray-500">Tenant not found</div>;

    const handleSuspend = () => {
        const reason = prompt('Enter suspension reason:');
        if (reason) suspendMutation.mutate(reason);
    };

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <Button variant="ghost" size="icon" onClick={() => navigate('/super-admin/tenants')}>
                        <ArrowLeft className="h-5 w-5" />
                    </Button>
                    <h1 className="text-2xl font-bold">{tenant.name}</h1>
                    {tenant.suspended_at ? (
                        <Badge variant="destructive">Suspended</Badge>
                    ) : tenant.is_active ? (
                        <Badge variant="success">Active</Badge>
                    ) : (
                        <Badge variant="secondary">Inactive</Badge>
                    )}
                </div>
                <div className="flex gap-2">
                    <Button variant="outline" onClick={() => navigate(`/super-admin/tenants/${id}/edit`)}>
                        <Edit className="mr-2 h-4 w-4" /> Edit
                    </Button>
                    {tenant.suspended_at ? (
                        <Button variant="success" onClick={() => activateMutation.mutate()} loading={activateMutation.isPending}>
                            <PlayCircle className="mr-2 h-4 w-4" /> Activate
                        </Button>
                    ) : (
                        <Button variant="destructive" onClick={handleSuspend} loading={suspendMutation.isPending}>
                            <PauseCircle className="mr-2 h-4 w-4" /> Suspend
                        </Button>
                    )}
                </div>
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Building2 className="h-5 w-5" />
                            Tenant Details
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <p className="text-xs font-medium uppercase text-gray-500">Name</p>
                                <p className="mt-1 text-sm">{tenant.name}</p>
                            </div>
                            <div>
                                <p className="text-xs font-medium uppercase text-gray-500">Slug</p>
                                <p className="mt-1 text-sm font-mono">{tenant.slug}</p>
                            </div>
                        </div>
                        <div className="flex items-center gap-3">
                            <Mail className="h-4 w-4 text-gray-400" />
                            <span className="text-sm">{tenant.email}</span>
                        </div>
                        <div className="flex items-center gap-3">
                            <Phone className="h-4 w-4 text-gray-400" />
                            <span className="text-sm">{tenant.phone}</span>
                        </div>
                        {tenant.address && (
                            <div className="flex items-center gap-3">
                                <MapPin className="h-4 w-4 text-gray-400" />
                                <span className="text-sm">{tenant.address}</span>
                            </div>
                        )}
                        <div className="grid grid-cols-2 gap-4 pt-2">
                            {tenant.pan_number && (
                                <div>
                                    <p className="text-xs font-medium uppercase text-gray-500">PAN Number</p>
                                    <p className="mt-1 text-sm font-mono">{tenant.pan_number}</p>
                                </div>
                            )}
                            {tenant.vat_number && (
                                <div>
                                    <p className="text-xs font-medium uppercase text-gray-500">VAT Number</p>
                                    <p className="mt-1 text-sm font-mono">{tenant.vat_number}</p>
                                </div>
                            )}
                            {tenant.drug_license_number && (
                                <div>
                                    <p className="text-xs font-medium uppercase text-gray-500">Drug License</p>
                                    <p className="mt-1 text-sm font-mono">{tenant.drug_license_number}</p>
                                </div>
                            )}
                            {tenant.pharmacy_license_number && (
                                <div>
                                    <p className="text-xs font-medium uppercase text-gray-500">Pharmacy License</p>
                                    <p className="mt-1 text-sm font-mono">{tenant.pharmacy_license_number}</p>
                                </div>
                            )}
                            {tenant.pharmacist_name && (
                                <div>
                                    <p className="text-xs font-medium uppercase text-gray-500">Pharmacist</p>
                                    <p className="mt-1 text-sm">{tenant.pharmacist_name}</p>
                                </div>
                            )}
                            {tenant.pharmacist_registration_number && (
                                <div>
                                    <p className="text-xs font-medium uppercase text-gray-500">Reg. Number</p>
                                    <p className="mt-1 text-sm font-mono">{tenant.pharmacist_registration_number}</p>
                                </div>
                            )}
                        </div>
                        <div className="pt-2">
                            <p className="text-xs font-medium uppercase text-gray-500">Joined</p>
                            <p className="mt-1 text-sm">{formatDate(tenant.created_at)}</p>
                        </div>
                        {tenant.suspended_at && (
                            <div className="rounded-md bg-danger-50 p-3">
                                <p className="text-xs font-medium uppercase text-danger-600">Suspended</p>
                                <p className="mt-1 text-sm text-danger-700">{formatDate(tenant.suspended_at)}</p>
                                {tenant.suspension_reason && (
                                    <p className="mt-1 text-sm text-danger-600">{tenant.suspension_reason}</p>
                                )}
                            </div>
                        )}
                    </CardContent>
                </Card>

                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <CreditCard className="h-5 w-5" />
                                Subscription
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {tenant.subscription_plan ? (
                                <div className="space-y-3">
                                    <div>
                                        <p className="text-xs font-medium uppercase text-gray-500">Plan</p>
                                        <p className="mt-1 text-lg font-semibold">{tenant.subscription_plan.name}</p>
                                    </div>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <p className="text-xs font-medium uppercase text-gray-500">Monthly</p>
                                            <p className="mt-1 text-sm">{formatCurrency(tenant.subscription_plan.price_monthly)}</p>
                                        </div>
                                        <div>
                                            <p className="text-xs font-medium uppercase text-gray-500">Yearly</p>
                                            <p className="mt-1 text-sm">{formatCurrency(tenant.subscription_plan.price_yearly)}</p>
                                        </div>
                                    </div>
                                    <div className="grid grid-cols-3 gap-4">
                                        <div>
                                            <p className="text-xs font-medium uppercase text-gray-500">Outlets</p>
                                            <p className="mt-1 text-sm">{tenant.subscription_plan.max_outlets}</p>
                                        </div>
                                        <div>
                                            <p className="text-xs font-medium uppercase text-gray-500">Users</p>
                                            <p className="mt-1 text-sm">{tenant.subscription_plan.max_users}</p>
                                        </div>
                                        <div>
                                            <p className="text-xs font-medium uppercase text-gray-500">Medicines</p>
                                            <p className="mt-1 text-sm">{tenant.subscription_plan.max_medicines}</p>
                                        </div>
                                    </div>
                                    {tenant.subscription_expires_at && (
                                        <div>
                                            <p className="text-xs font-medium uppercase text-gray-500">Expires</p>
                                            <p className="mt-1 text-sm">{formatDate(tenant.subscription_expires_at)}</p>
                                        </div>
                                    )}
                                </div>
                            ) : (
                                <p className="text-sm text-gray-500">No active subscription</p>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Users className="h-5 w-5" />
                                Users ({tenant.users?.length ?? 0})
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {tenant.users && tenant.users.length > 0 ? (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Name</TableHead>
                                            <TableHead>Email</TableHead>
                                            <TableHead>Role</TableHead>
                                            <TableHead>Status</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {tenant.users.map((user) => (
                                            <TableRow key={user.id}>
                                                <TableCell className="font-medium">{user.name}</TableCell>
                                                <TableCell className="text-sm text-gray-500">{user.email}</TableCell>
                                                <TableCell>
                                                    <Badge variant="secondary" className="capitalize">{user.role}</Badge>
                                                </TableCell>
                                                <TableCell>
                                                    {user.is_active ? (
                                                        <Badge variant="success">Active</Badge>
                                                    ) : (
                                                        <Badge variant="secondary">Inactive</Badge>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            ) : (
                                <p className="text-sm text-gray-500">No users</p>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <FileText className="h-5 w-5" />
                                Recent Payments
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {tenant.payments && tenant.payments.length > 0 ? (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Plan</TableHead>
                                            <TableHead>Amount</TableHead>
                                            <TableHead>Method</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead>Date</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {tenant.payments.map((payment) => (
                                            <TableRow key={payment.id}>
                                                <TableCell className="font-medium">
                                                    {payment.plan?.name ?? '—'}
                                                </TableCell>
                                                <TableCell>{formatCurrency(payment.amount)}</TableCell>
                                                <TableCell className="capitalize">{payment.payment_method}</TableCell>
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
                            ) : (
                                <p className="text-sm text-gray-500">No payments</p>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    );
}
