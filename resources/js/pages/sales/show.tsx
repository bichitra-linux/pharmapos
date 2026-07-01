import { useParams, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { salesService } from '@/services/sales';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { PageLoader } from '@/components/ui/spinner';
import { formatCurrency, formatDate } from '@/lib/utils';
import { ArrowLeft, Printer, Receipt } from 'lucide-react';

export default function ShowSale() {
    const { id } = useParams();
    const navigate = useNavigate();

    const { data: sale, isLoading } = useQuery({
        queryKey: ['sale', id],
        queryFn: () => salesService.get(Number(id)),
        select: (res) => res.data,
    });

    if (isLoading) return <PageLoader />;
    if (!sale) return (
        <div className="flex flex-col items-center justify-center gap-3 py-16 text-center">
            <Receipt className="h-12 w-12 text-text-muted" />
            <h2 className="text-lg font-semibold text-text">Sale not found</h2>
            <p className="text-sm text-text-muted">The sale you're looking for doesn't exist.</p>
            <Button variant="outline" onClick={() => navigate('/sales')}>
                <ArrowLeft className="mr-2 h-4 w-4" />
                Back to Sales
            </Button>
        </div>
    );

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" onClick={() => navigate('/sales')}>
                        <ArrowLeft className="mr-2 h-4 w-4" />
                        Back
                    </Button>
                    <h1 className="text-2xl font-bold">Sale #{sale.invoice_number}</h1>
                    <Badge variant={sale.payment_status === 'paid' ? 'success' : sale.payment_status === 'due' ? 'destructive' : 'warning'}>
                        {sale.payment_status}
                    </Badge>
                </div>
                <Button variant="outline" onClick={() => window.print()}>
                    <Printer className="mr-2 h-4 w-4" />
                    Print
                </Button>
            </div>

            <div className="grid gap-6 lg:grid-cols-3">
                <Card>
                    <CardHeader><CardTitle>Customer</CardTitle></CardHeader>
                    <CardContent className="text-sm space-y-1">
                        <p className="font-medium">{sale.customer?.name || 'Walk-in Customer'}</p>
                        {sale.customer?.phone && <p className="text-text-muted">{sale.customer.phone}</p>}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader><CardTitle>Sale Details</CardTitle></CardHeader>
                    <CardContent className="text-sm space-y-1">
                        <div><span className="text-text-muted">Date:</span> {formatDate(sale.created_at, 'dd/MM/yyyy HH:mm')}</div>
                        <div><span className="text-text-muted">Cashier:</span> {sale.dispensedBy?.name || '-'}</div>
                        {sale.prescription_id && <div><span className="text-text-muted">Prescription:</span> #{sale.prescription_id}</div>}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader><CardTitle>Payment</CardTitle></CardHeader>
                    <CardContent className="text-sm space-y-1">
                        <div><span className="text-text-muted">Subtotal:</span> {formatCurrency(sale.subtotal)}</div>
                        <div><span className="text-text-muted">Discount:</span> {formatCurrency(sale.discount_amount)}</div>
                        <div><span className="text-text-muted">VAT ({sale.vat_percentage}%):</span> {formatCurrency(sale.vat_amount)}</div>
                        <div className="font-bold text-lg">{formatCurrency(sale.total_amount)}</div>
                        <div><span className="text-text-muted">Paid:</span> {formatCurrency(sale.paid_amount)}</div>
                        {sale.due_amount > 0 && <div className="text-danger-600">Due: {formatCurrency(sale.due_amount)}</div>}
                    </CardContent>
                </Card>
            </div>

            {sale.items && sale.items.length > 0 && (
                <Card>
                    <CardHeader><CardTitle>Items</CardTitle></CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Medicine</TableHead>
                                    <TableHead>Batch</TableHead>
                                    <TableHead>Qty</TableHead>
                                    <TableHead>Price</TableHead>
                                    <TableHead>Disc</TableHead>
                                    <TableHead>Tax</TableHead>
                                    <TableHead>Total</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {sale.items.map((item) => (
                                    <TableRow key={item.id}>
                                        <TableCell className="font-medium">{item.medicine?.brand_name || `Medicine #${item.medicine_id}`}</TableCell>
                                        <TableCell>{item.batch?.batch_number || '-'}</TableCell>
                                        <TableCell>{item.quantity}</TableCell>
                                        <TableCell>{formatCurrency(item.selling_price)}</TableCell>
                                        <TableCell>{formatCurrency(item.discount)}</TableCell>
                                        <TableCell>{formatCurrency(item.vat)}</TableCell>
                                        <TableCell>{formatCurrency(item.total)}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            )}

            {sale.payments && sale.payments.length > 0 && (
                <Card>
                    <CardHeader><CardTitle>Payments</CardTitle></CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Method</TableHead>
                                    <TableHead>Amount</TableHead>
                                    <TableHead>Reference</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {sale.payments.map((p) => (
                                    <TableRow key={p.id}>
                                        <TableCell>{p.payment_method?.name || '-'}</TableCell>
                                        <TableCell>{formatCurrency(p.amount)}</TableCell>
                                        <TableCell>{p.reference_number || '-'}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            )}
        </div>
    );
}
