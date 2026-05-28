import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { superAdminService } from '@/services/super-admin';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { PageLoader } from '@/components/ui/spinner';
import { EmptyState } from '@/components/ui/empty-state';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogHeader, DialogTitle, DialogContent, DialogFooter } from '@/components/ui/dialog';
import { formatCurrency } from '@/lib/utils';
import { CreditCard, Plus, Edit, Trash2, Power } from 'lucide-react';
import type { SubscriptionPlan } from '@/types/super-admin';

interface PlanFormData {
    name: string;
    price_monthly: string;
    price_yearly: string;
    max_outlets: string;
    max_users: string;
    max_medicines: string;
    features: string;
}

const emptyForm: PlanFormData = {
    name: '',
    price_monthly: '',
    price_yearly: '',
    max_outlets: '',
    max_users: '',
    max_medicines: '',
    features: '',
};

export default function SuperAdminPlansPage() {
    const queryClient = useQueryClient();
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [deleteId, setDeleteId] = useState<number | null>(null);
    const [form, setForm] = useState<PlanFormData>(emptyForm);

    const { data: plans, isLoading } = useQuery({
        queryKey: ['super-admin', 'plans'],
        queryFn: () => superAdminService.getPlans(),
        select: (res) => res.data,
    });

    const createMutation = useMutation({
        mutationFn: (data: PlanFormData) =>
            superAdminService.createPlan({
                name: data.name,
                price_monthly: Number(data.price_monthly),
                price_yearly: Number(data.price_yearly),
                max_outlets: Number(data.max_outlets),
                max_users: Number(data.max_users),
                max_medicines: Number(data.max_medicines),
                features: data.features
                    ? data.features.split(',').map((f) => f.trim()).filter(Boolean)
                    : [],
            }),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['super-admin', 'plans'] });
            closeDialog();
        },
    });

    const updateMutation = useMutation({
        mutationFn: ({ id, data }: { id: number; data: Partial<PlanFormData> }) =>
            superAdminService.updatePlan(id, {
                ...data,
                price_monthly: data.price_monthly ? Number(data.price_monthly) : undefined,
                price_yearly: data.price_yearly ? Number(data.price_yearly) : undefined,
                max_outlets: data.max_outlets ? Number(data.max_outlets) : undefined,
                max_users: data.max_users ? Number(data.max_users) : undefined,
                max_medicines: data.max_medicines ? Number(data.max_medicines) : undefined,
                features: data.features
                    ? data.features.split(',').map((f) => f.trim()).filter(Boolean)
                    : undefined,
            }),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['super-admin', 'plans'] });
            closeDialog();
        },
    });

    const deleteMutation = useMutation({
        mutationFn: (id: number) => superAdminService.deletePlan(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['super-admin', 'plans'] });
            setDeleteId(null);
        },
    });

    const toggleMutation = useMutation({
        mutationFn: (id: number) => superAdminService.togglePlan(id),
        onSuccess: () => queryClient.invalidateQueries({ queryKey: ['super-admin', 'plans'] }),
    });

    const openCreate = () => {
        setEditingId(null);
        setForm(emptyForm);
        setDialogOpen(true);
    };

    const openEdit = (plan: SubscriptionPlan) => {
        setEditingId(plan.id);
        setForm({
            name: plan.name,
            price_monthly: String(plan.price_monthly),
            price_yearly: String(plan.price_yearly),
            max_outlets: String(plan.max_outlets),
            max_users: String(plan.max_users),
            max_medicines: String(plan.max_medicines),
            features: plan.features?.join(', ') ?? '',
        });
        setDialogOpen(true);
    };

    const closeDialog = () => {
        setDialogOpen(false);
        setEditingId(null);
        setForm(emptyForm);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (editingId) {
            updateMutation.mutate({ id: editingId, data: form });
        } else {
            createMutation.mutate(form);
        }
    };

    if (isLoading) return <PageLoader />;

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold">Subscription Plans</h1>
                <Button onClick={openCreate}>
                    <Plus className="mr-2 h-4 w-4" /> Create Plan
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>All Plans</CardTitle>
                </CardHeader>
                <CardContent>
                    {plans && plans.length > 0 ? (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Monthly</TableHead>
                                    <TableHead>Yearly</TableHead>
                                    <TableHead>Outlets</TableHead>
                                    <TableHead>Users</TableHead>
                                    <TableHead>Medicines</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="w-24">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {plans.map((plan) => (
                                    <TableRow key={plan.id}>
                                        <TableCell className="font-medium">{plan.name}</TableCell>
                                        <TableCell>{formatCurrency(plan.price_monthly)}</TableCell>
                                        <TableCell>{formatCurrency(plan.price_yearly)}</TableCell>
                                        <TableCell>{plan.max_outlets}</TableCell>
                                        <TableCell>{plan.max_users}</TableCell>
                                        <TableCell>{plan.max_medicines}</TableCell>
                                        <TableCell>
                                            {plan.is_active ? (
                                                <Badge variant="success">Active</Badge>
                                            ) : (
                                                <Badge variant="secondary">Inactive</Badge>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex gap-1">
                                                <Button variant="ghost" size="icon" onClick={() => openEdit(plan)}>
                                                    <Edit className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() => toggleMutation.mutate(plan.id)}
                                                >
                                                    <Power className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() => setDeleteId(plan.id)}
                                                >
                                                    <Trash2 className="h-4 w-4 text-danger-500" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    ) : (
                        <EmptyState
                            icon={CreditCard}
                            title="No plans yet"
                            description="Create your first subscription plan."
                        />
                    )}
                </CardContent>
            </Card>

            <Dialog open={dialogOpen} onClose={closeDialog} size="lg">
                <DialogHeader>
                    <DialogTitle>{editingId ? 'Edit Plan' : 'Create Plan'}</DialogTitle>
                </DialogHeader>
                <form onSubmit={handleSubmit}>
                    <DialogContent>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="sm:col-span-2">
                                <Input
                                    label="Plan Name"
                                    value={form.name}
                                    onChange={(e) => setForm({ ...form, name: e.target.value })}
                                    placeholder="e.g. Basic, Pro, Enterprise"
                                    required
                                />
                            </div>
                            <Input
                                label="Monthly Price (NPR)"
                                type="number"
                                value={form.price_monthly}
                                onChange={(e) => setForm({ ...form, price_monthly: e.target.value })}
                                placeholder="0"
                                required
                            />
                            <Input
                                label="Yearly Price (NPR)"
                                type="number"
                                value={form.price_yearly}
                                onChange={(e) => setForm({ ...form, price_yearly: e.target.value })}
                                placeholder="0"
                                required
                            />
                            <Input
                                label="Max Outlets"
                                type="number"
                                value={form.max_outlets}
                                onChange={(e) => setForm({ ...form, max_outlets: e.target.value })}
                                placeholder="0"
                                required
                            />
                            <Input
                                label="Max Users"
                                type="number"
                                value={form.max_users}
                                onChange={(e) => setForm({ ...form, max_users: e.target.value })}
                                placeholder="0"
                                required
                            />
                            <Input
                                label="Max Medicines"
                                type="number"
                                value={form.max_medicines}
                                onChange={(e) => setForm({ ...form, max_medicines: e.target.value })}
                                placeholder="0"
                                required
                            />
                            <div className="sm:col-span-2">
                                <Input
                                    label="Features (comma separated)"
                                    value={form.features}
                                    onChange={(e) => setForm({ ...form, features: e.target.value })}
                                    placeholder="e.g. POS, Reports, Multi-outlet"
                                />
                            </div>
                        </div>
                    </DialogContent>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={closeDialog}>
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            loading={createMutation.isPending || updateMutation.isPending}
                        >
                            {editingId ? 'Update' : 'Create'}
                        </Button>
                    </DialogFooter>
                </form>
            </Dialog>

            <Dialog open={deleteId !== null} onClose={() => setDeleteId(null)}>
                <DialogHeader>
                    <DialogTitle>Delete Plan</DialogTitle>
                </DialogHeader>
                <DialogContent>
                    <p className="text-sm text-gray-600">
                        Are you sure you want to delete this plan? Tenants using this plan will not be affected.
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
