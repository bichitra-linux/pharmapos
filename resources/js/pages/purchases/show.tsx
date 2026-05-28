import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation } from '@tanstack/react-query';
import { purchasesService } from '@/services/purchases';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { PageLoader } from '@/components/ui/spinner';
import { formatCurrency, formatDate } from '@/lib/utils';
import { useToast } from '@/components/ui/toast';
import { ArrowLeft, CheckCircle } from 'lucide-react';

export default function ShowPurchase() {
    const { id } = useParams();
    const navigate = useNavigate();
    const { addToast } = useToast();

    const { data: purchase, isLoading } = useQuery({
        queryKey: ['purchase', id],
        queryFn: () => purchasesService.get(Number(id)),
        select: (res) => res.data,
    });

    const receiveMutation = useMutation({
        mutationFn: () => purchasesService.receive(Number(id)),
        onSuccess: () => {
            addToast({ type: 'success', title: 'Purchase received successfully' });
        },
        onError: () => {
            addToast({ type: 'error', title: 'Failed to receive purchase' });
        },
    });

    if (isLoading) return <PageLoader />;
    if (!purchase) return <div>Purchase not found</div>;

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" onClick={() => navigate('/purchases')}>
                        <ArrowLeft className="mr-2 h-4 w-4" />
                        Back
                    </Button>
                    <h1 className="text-2xl font-bold">Purchase #{purchase.purchase_number}</h1>
                    <Badge variant={purchase.status === 'received' ? 'success' : purchase.status === 'cancelled' ? 'destructive' : 'default'}>
                        {purchase.status}
                    </Badge>
                </div>
                {(purchase.status === 'ordered' || purchase.status === 'draft') && (
                    <Button onClick={() => receiveMutation.mutate()} loading={receiveMutation.isPending}>
                        <CheckCircle className="mr-2 h-4 w-4" />
                        Receive GRN
                    </Button>
                )}
            </div>

            <div className="grid gap-6 lg:grid-cols-3">
                <Card>
                    <CardHeader><CardTitle>Supplier</CardTitle></CardHeader>
                    <CardContent className="text-sm space-y-1">
                        <p className="font-medium">{purchase.supplier?.name}</p>
                        <p className="text-gray-500">{purchase.supplier?.phone}</p>
                        <p className="text-gray-500">{purchase.supplier?.address}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader><CardTitle>Invoice Details</CardTitle></CardHeader>
                    <CardContent className="text-sm space-y-1">
                        <div><span className="text-gray-500">Invoice Date:</span> {formatDate(purchase.invoice_date)}</div>
                        <div><span className="text-gray-500">Due Date:</span> {purchase.due_date ? formatDate(purchase.due_date) : '-'}</div>
                        <div><span className="text-gray-500">Supplier Invoice:</span> {purchase.supplier_invoice_number || '-'}</div>
                        {purchase.grn_number && <div><span className="text-gray-500">GRN #:</span> {purchase.grn_number}</div>}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader><CardTitle>Amounts</CardTitle></CardHeader>
                    <CardContent className="text-sm space-y-1">
                        <div><span className="text-gray-500">Subtotal:</span> {formatCurrency(purchase.subtotal)}</div>
                        <div><span className="text-gray-500">Discount:</span> {formatCurrency(purchase.discount_amount)}</div>
                        <div><span className="text-gray-500">Tax:</span> {formatCurrency(purchase.tax_amount)}</div>
                        <div className="font-bold text-lg"><span className="text-gray-500 text-sm">Total:</span> {formatCurrency(purchase.total_amount)}</div>
                        <div><span className="text-gray-500">Paid:</span> {formatCurrency(purchase.paid_amount)}</div>
                        {purchase.due_amount > 0 && <div className="text-danger-600"><span className="text-gray-500">Due:</span> {formatCurrency(purchase.due_amount)}</div>}
                    </CardContent>
                </Card>
            </div>

            {purchase.items && purchase.items.length > 0 && (
                <Card>
                    <CardHeader><CardTitle>Items</CardTitle></CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Medicine</TableHead>
                                    <TableHead>Batch #</TableHead>
                                    <TableHead>Expiry</TableHead>
                                    <TableHead>Qty</TableHead>
                                    <TableHead>Received</TableHead>
                                    <TableHead>Price</TableHead>
                                    <TableHead>Total</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {purchase.items.map((item) => (
                                    <TableRow key={item.id}>
                                        <TableCell className="font-medium">{item.medicine?.brand_name || `Medicine #${item.medicine_id}`}</TableCell>
                                        <TableCell>{item.batch_number}</TableCell>
                                        <TableCell>{formatDate(item.expiry_date)}</TableCell>
                                        <TableCell>{item.quantity}</TableCell>
                                        <TableCell>{item.received_quantity}</TableCell>
                                        <TableCell>{formatCurrency(item.unit_price)}</TableCell>
                                        <TableCell>{formatCurrency(item.total_amount)}</TableCell>
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
