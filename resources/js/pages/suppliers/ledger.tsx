import { useParams, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { suppliersService } from '@/services/suppliers';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { PageLoader } from '@/components/ui/spinner';
import { formatCurrency, formatDate } from '@/lib/utils';
import { ArrowLeft } from 'lucide-react';

export default function SupplierLedger() {
    const { id } = useParams();
    const navigate = useNavigate();

    const { data: supplier, isLoading: loadingSupplier } = useQuery({
        queryKey: ['supplier', id],
        queryFn: () => suppliersService.get(Number(id)),
        select: (res) => res.data,
    });

    const { data: ledger, isLoading: loadingLedger } = useQuery({
        queryKey: ['supplier', id, 'ledger'],
        queryFn: () => suppliersService.getLedger(Number(id)),
        select: (res) => res.data,
    });

    if (loadingSupplier || loadingLedger) return <PageLoader />;
    if (!supplier || !ledger) return <div>Supplier not found</div>;

    const payments = ledger.payments ?? [];

    return (
        <div className="space-y-6">
            <div className="flex items-center gap-4">
                <Button variant="ghost" onClick={() => navigate('/suppliers')}>
                    <ArrowLeft className="mr-2 h-4 w-4" />
                    Back
                </Button>
                <h1 className="text-2xl font-bold">Ledger: {supplier.name}</h1>
                <div className="ml-auto">
                    <Button variant="outline" onClick={() => navigate(`/suppliers/${id}/edit`)}>
                        Edit Supplier
                    </Button>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Supplier Info</CardTitle>
                </CardHeader>
                <CardContent className="grid grid-cols-3 gap-4 text-sm">
                    <div><span className="text-text-muted">Phone:</span> {supplier.phone}</div>
                    <div><span className="text-text-muted">Email:</span> {supplier.email || '-'}</div>
                    <div>
                        <span className="text-text-muted">Outstanding:</span>{' '}
                        <span className={ledger.summary.balance > 0 ? 'font-bold text-danger-600' : 'font-bold text-success-600'}>
                            {formatCurrency(ledger.summary.balance)}
                        </span>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Payment History</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Date</TableHead>
                                <TableHead>Purchase #</TableHead>
                                <TableHead>Method</TableHead>
                                <TableHead>Amount</TableHead>
                                <TableHead>Reference</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {payments.length > 0 ? payments.map((p) => (
                                <TableRow key={p.id}>
                                    <TableCell>{formatDate(p.created_at)}</TableCell>
                                    <TableCell>{p.purchase_number || '-'}</TableCell>
                                    <TableCell>{p.payment_method}</TableCell>
                                    <TableCell>{formatCurrency(p.amount)}</TableCell>
                                    <TableCell>{p.reference_number || '-'}</TableCell>
                                </TableRow>
                            )) : (
                                <TableRow>
                                    <TableCell colSpan={5} className="text-center text-text-muted">No payments recorded</TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    );
}
