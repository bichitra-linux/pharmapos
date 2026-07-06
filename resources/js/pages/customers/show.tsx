import { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { customersService } from '@/services/customers';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { PageLoader } from '@/components/ui/spinner';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatCurrency, formatDate } from '@/lib/utils';
import {
    ArrowLeft, User, Phone, Mail, MapPin, DollarSign, CreditCard,
    Calendar, Heart, Shield, Plus, Minus, Wallet,
} from 'lucide-react';

const paymentMethods = [
    { label: 'Cash', value: 'cash' },
    { label: 'eSewa', value: 'esewa' },
    { label: 'Khalti', value: 'khalti' },
    { label: 'Bank Transfer', value: 'bank_transfer' },
];

export default function CustomerShowPage() {
    const { id } = useParams<{ id: string }>();
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const [showLend, setShowLend] = useState(false);
    const [showReceive, setShowReceive] = useState(false);
    const [amount, setAmount] = useState('');
    const [paymentMethod, setPaymentMethod] = useState('cash');
    const [note, setNote] = useState('');
    const [creditLimit, setCreditLimit] = useState('');

    const { data: customer, isLoading } = useQuery({
        queryKey: ['customers', 'detail', id],
        queryFn: () => customersService.get(Number(id)),
        select: (res) => res.data,
    });

    const { data: ledgerData } = useQuery({
        queryKey: ['customers', 'ledger', id],
        queryFn: () => customersService.getCreditLedger(Number(id)),
        enabled: !!id,
    });

    const { data: summary } = useQuery({
        queryKey: ['customers', 'credit-summary', id],
        queryFn: () => customersService.getCreditSummary(Number(id)),
        select: (res) => res.data,
        enabled: !!id,
    });

    const lendMutation = useMutation({
        mutationFn: () => customersService.creditLend(Number(id), parseFloat(amount), note),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['customers', 'detail', id] });
            queryClient.invalidateQueries({ queryKey: ['customers', 'ledger', id] });
            queryClient.invalidateQueries({ queryKey: ['customers', 'credit-summary', id] });
            setShowLend(false); setAmount(''); setNote('');
        },
    });

    const receiveMutation = useMutation({
        mutationFn: () => customersService.creditReceive(Number(id), parseFloat(amount), paymentMethod, note),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['customers', 'detail', id] });
            queryClient.invalidateQueries({ queryKey: ['customers', 'ledger', id] });
            queryClient.invalidateQueries({ queryKey: ['customers', 'credit-summary', id] });
            setShowReceive(false); setAmount(''); setNote(''); setPaymentMethod('cash');
        },
    });

    const creditLimitMutation = useMutation({
        mutationFn: () => customersService.setCreditLimit(Number(id), parseFloat(creditLimit)),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['customers', 'detail', id] });
            queryClient.invalidateQueries({ queryKey: ['customers', 'credit-summary', id] });
            setCreditLimit('');
        },
    });

    if (isLoading) return <PageLoader />;
    if (!customer) return <div className="py-12 text-center text-text-muted">Customer not found</div>;

    const balance = summary?.current_balance ?? 0;
    const limit = summary?.credit_limit ?? 0;
    const isOverLimit = summary?.is_over_limit ?? false;
    const ledger = (ledgerData as { data: Array<Record<string, unknown>> } | undefined)?.data ?? [];

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <Button variant="ghost" size="icon" onClick={() => navigate('/customers')}>
                        <ArrowLeft className="h-5 w-5" />
                    </Button>
                    <h1 className="text-2xl font-bold">{customer.name}</h1>
                    <Badge variant={customer.is_active ? 'success' : 'secondary'}>
                        {customer.is_active ? 'Active' : 'Inactive'}
                    </Badge>
                </div>
            </div>

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="lg:col-span-2 space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2"><User className="h-5 w-5" /> Customer Details</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <div className="flex items-center gap-2"><Phone className="h-4 w-4 text-text-muted shrink-0" /><span>{customer.phone || '—'}</span></div>
                            <div className="flex items-center gap-2"><Mail className="h-4 w-4 text-text-muted shrink-0" /><span>{customer.email || '—'}</span></div>
                            <div className="flex items-center gap-2"><MapPin className="h-4 w-4 text-text-muted shrink-0" /><span>{customer.address || '—'}</span></div>
                            <div className="flex items-center gap-2"><Calendar className="h-4 w-4 text-text-muted shrink-0" /><span>{customer.date_of_birth ? formatDate(customer.date_of_birth) : '—'}</span></div>
                            {customer.allergies && <div className="flex items-center gap-2"><Heart className="h-4 w-4 text-danger-500 shrink-0" /><span className="text-danger-600">Allergies: {customer.allergies}</span></div>}
                            {(customer as any).chronic_conditions && <div className="flex items-center gap-2"><Shield className="h-4 w-4 text-warning-500 shrink-0" /><span className="text-warning-700">{(customer as any).chronic_conditions}</span></div>}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2"><Wallet className="h-5 w-5" /> Credit Ledger</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center justify-between mb-4">
                                <div>
                                    <p className="text-sm text-text-muted">Current Balance</p>
                                    <p className={`text-2xl font-bold ${balance > 0 ? (isOverLimit ? 'text-danger-600' : 'text-warning-600') : 'text-success-600'}`}>
                                        {formatCurrency(balance)}
                                    </p>
                                    {limit > 0 && (
                                        <p className="text-xs text-text-muted">Credit limit: {formatCurrency(limit)}</p>
                                    )}
                                </div>
                                <div className="flex gap-2">
                                    <Button size="sm" variant="outline" onClick={() => setShowLend(!showLend)}>
                                        <Plus className="mr-1 h-4 w-4" /> Lend
                                    </Button>
                                    <Button size="sm" variant="outline" onClick={() => setShowReceive(!showReceive)} disabled={balance <= 0}>
                                        <Minus className="mr-1 h-4 w-4" /> Receive
                                    </Button>
                                </div>
                            </div>

                            {showLend && (
                                <div className="mb-4 rounded-lg border border-border bg-surface-muted p-4 space-y-3">
                                    <Input label="Amount" type="number" value={amount} onChange={(e) => setAmount(e.target.value)} placeholder="0.00" min="0" step="0.01" />
                                    <Input label="Note" value={note} onChange={(e) => setNote(e.target.value)} placeholder="Reason for credit" />
                                    <Button onClick={() => lendMutation.mutate()} disabled={!amount || parseFloat(amount) <= 0} loading={lendMutation.isPending}>
                                        Record Credit
                                    </Button>
                                </div>
                            )}

                            {showReceive && (
                                <div className="mb-4 rounded-lg border border-border bg-surface-muted p-4 space-y-3">
                                    <Input label="Amount" type="number" value={amount} onChange={(e) => setAmount(e.target.value)} placeholder="0.00" min="0" step="0.01" />
                                    <Select label="Payment Method" options={paymentMethods} value={paymentMethod} onChange={(v) => setPaymentMethod(String(v))} />
                                    <Input label="Note" value={note} onChange={(e) => setNote(e.target.value)} placeholder="Optional note" />
                                    <Button onClick={() => receiveMutation.mutate()} disabled={!amount || parseFloat(amount) <= 0 || parseFloat(amount) > balance} loading={receiveMutation.isPending}>
                                        Record Payment
                                    </Button>
                                </div>
                            )}

                            <div className="flex items-center gap-3 mb-4">
                                <Input label="Set Credit Limit" type="number" value={creditLimit} onChange={(e) => setCreditLimit(e.target.value)} placeholder="0 = unlimited" className="w-48" />
                                <Button variant="outline" size="sm" onClick={() => creditLimitMutation.mutate()} disabled={!creditLimit} className="mt-6">
                                    Set
                                </Button>
                            </div>

                            {ledger.length > 0 ? (
                                <div className="overflow-hidden rounded-lg border border-border">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="bg-surface-muted border-b border-border">
                                                <th className="px-4 py-2.5 text-left font-medium text-text-muted">Date</th>
                                                <th className="px-4 py-2.5 text-left font-medium text-text-muted">Type</th>
                                                <th className="px-4 py-2.5 text-right font-medium text-text-muted">Amount</th>
                                                <th className="px-4 py-2.5 text-right font-medium text-text-muted">Balance</th>
                                                <th className="px-4 py-2.5 text-left font-medium text-text-muted">Note</th>
                                                <th className="px-4 py-2.5 text-left font-medium text-text-muted">By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {ledger.map((entry: Record<string, unknown>) => (
                                                <tr key={entry.id as number} className="border-b border-border last:border-0">
                                                    <td className="px-4 py-2.5 text-text-muted">{formatDate(entry.created_at as string)}</td>
                                                    <td className="px-4 py-2.5">
                                                        <Badge variant={entry.type === 'debit' ? 'warning' : 'success'}>
                                                            {entry.type === 'debit' ? 'Lent' : 'Paid'}
                                                        </Badge>
                                                    </td>
                                                    <td className="px-4 py-2.5 text-right font-medium">
                                                        <span className={entry.type === 'debit' ? 'text-danger-600' : 'text-success-600'}>
                                                            {entry.type === 'debit' ? '-' : '+'}{formatCurrency(entry.amount as number)}
                                                        </span>
                                                    </td>
                                                    <td className="px-4 py-2.5 text-right">{formatCurrency(entry.balance_after as number)}</td>
                                                    <td className="px-4 py-2.5 text-text-muted text-xs max-w-32 truncate">{(entry.note as string) || '—'}</td>
                                                    <td className="px-4 py-2.5 text-text-muted">{(entry.recorded_by as Record<string, string>)?.name || '—'}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <p className="text-sm text-text-muted text-center py-4">No credit transactions yet.</p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2"><DollarSign className="h-5 w-5" /> Summary</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="flex justify-between py-2 border-b border-border">
                                <span className="text-sm text-text-muted">Loyalty Points</span>
                                <span className="font-medium">{customer.loyalty_points}</span>
                            </div>
                            <div className="flex justify-between py-2 border-b border-border">
                                <span className="text-sm text-text-muted">Total Due</span>
                                <span className={`font-medium ${balance > 0 ? 'text-warning-600' : 'text-success-600'}`}>
                                    {formatCurrency(balance)}
                                </span>
                            </div>
                            {limit > 0 && (
                                <div className="flex justify-between py-2 border-b border-border">
                                    <span className="text-sm text-text-muted">Credit Limit</span>
                                    <span className="font-medium">{formatCurrency(limit)}</span>
                                </div>
                            )}
                            {summary?.last_payment_at && (
                                <div className="flex justify-between py-2 border-b border-border">
                                    <span className="text-sm text-text-muted">Last Payment</span>
                                    <span className="text-sm">{formatDate(summary.last_payment_at)}</span>
                                </div>
                            )}
                            <div className="flex justify-between py-2">
                                <span className="text-sm text-text-muted">Status</span>
                                <Badge variant={isOverLimit ? 'destructive' : balance > 0 ? 'warning' : 'success'}>
                                    {isOverLimit ? 'Over Limit' : balance > 0 ? 'Has Due' : 'Clear'}
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    );
}
