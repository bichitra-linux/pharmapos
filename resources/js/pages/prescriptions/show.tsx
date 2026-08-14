import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { prescriptionsService } from '@/services/prescriptions';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { PageLoader } from '@/components/ui/spinner';
import { formatDate } from '@/lib/utils';
import { useToast } from '@/components/ui/toast';
import { ArrowLeft, CheckCircle, FileText } from 'lucide-react';

export default function ShowPrescription() {
    const { id } = useParams();
    const navigate = useNavigate();
    const { addToast } = useToast();
    const queryClient = useQueryClient();

    const { data: prescription, isLoading } = useQuery({
        queryKey: ['prescription', id],
        queryFn: () => prescriptionsService.get(Number(id)),
        select: (res) => res.data,
    });

    const dispenseMutation = useMutation({
        mutationFn: () => prescriptionsService.dispense(Number(id), {
            items: (prescription?.items ?? [])
                .filter((item) => (item.quantity_prescribed ?? 0) > item.quantity_dispensed)
                .map((item) => ({
                    prescription_item_id: item.id,
                    quantity_dispensed: (item.quantity_prescribed ?? 0) - item.quantity_dispensed,
                })),
        }),
        onSuccess: () => {
            addToast({ type: 'success', title: 'Prescription dispensed' });
            queryClient.invalidateQueries({ queryKey: ['prescription', id] });
        },
        onError: (error) => {
            addToast({ type: 'error', title: (error as Error)?.message || 'Failed to dispense' });
        },
    });

    if (isLoading) return <PageLoader />;
    if (!prescription) return (
        <div className="flex flex-col items-center justify-center gap-3 py-16 text-center">
            <FileText className="h-12 w-12 text-text-muted" />
            <h2 className="text-lg font-semibold text-text">Prescription not found</h2>
            <p className="text-sm text-text-muted">The prescription you're looking for doesn't exist.</p>
            <Button variant="outline" onClick={() => navigate('/prescriptions')}>
                <ArrowLeft className="mr-2 h-4 w-4" />
                Back to Prescriptions
            </Button>
        </div>
    );

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" onClick={() => navigate('/prescriptions')}>
                        <ArrowLeft className="mr-2 h-4 w-4" />
                        Back
                    </Button>
                    <h1 className="text-2xl font-bold">Prescription #{prescription.id}</h1>
                    <Badge variant={prescription.status === 'dispensed' ? 'success' : prescription.status === 'cancelled' ? 'destructive' : 'default'}>
                        {prescription.status}
                    </Badge>
                </div>
                {prescription.status === 'pending' && (
                    <Button onClick={() => dispenseMutation.mutate()} loading={dispenseMutation.isPending}>
                        <CheckCircle className="mr-2 h-4 w-4" />
                        Dispense
                    </Button>
                )}
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader><CardTitle>Details</CardTitle></CardHeader>
                    <CardContent className="text-sm space-y-2">
                        <div><span className="text-text-muted">Doctor:</span> {prescription.doctor_name}</div>
                        {prescription.hospital_name && <div><span className="text-text-muted">Hospital:</span> {prescription.hospital_name}</div>}
                        <div><span className="text-text-muted">Date:</span> {formatDate(prescription.prescription_date)}</div>
                        <div><span className="text-text-muted">Customer:</span> {prescription.customer?.name || '-'}</div>
                        {prescription.diagnosis && <div><span className="text-text-muted">Diagnosis:</span> {prescription.diagnosis}</div>}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader><CardTitle>Image</CardTitle></CardHeader>
                    <CardContent>
                        {prescription.image_path ? (
                            <img src={`/storage/${prescription.image_path}`} alt="Prescription" width={600} height={400} className="max-h-64 rounded border" />
                        ) : (
                            <p className="text-sm text-text-muted">No image uploaded</p>
                        )}
                    </CardContent>
                </Card>
            </div>

            {prescription.items && prescription.items.length > 0 && (
                <Card>
                    <CardHeader><CardTitle>Medicines</CardTitle></CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Medicine</TableHead>
                                    <TableHead>Dosage</TableHead>
                                    <TableHead>Frequency</TableHead>
                                    <TableHead>Duration</TableHead>
                                    <TableHead>Qty</TableHead>
                                    <TableHead>Dispensed</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {prescription.items.map((item) => (
                                    <TableRow key={item.id}>
                                        <TableCell className="font-medium">{item.medicine_name}</TableCell>
                                        <TableCell>{item.dosage}</TableCell>
                                        <TableCell>{item.frequency}</TableCell>
                                        <TableCell>{item.duration}</TableCell>
                                        <TableCell>{item.quantity_prescribed}</TableCell>
                                        <TableCell>{item.quantity_dispensed}</TableCell>
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
