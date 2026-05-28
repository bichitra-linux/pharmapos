import { useParams, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { medicinesService } from '@/services/medicines';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { PageLoader } from '@/components/ui/spinner';
import { formatCurrency, formatDate } from '@/lib/utils';
import { ArrowLeft, Edit } from 'lucide-react';

export default function ShowMedicine() {
    const { id } = useParams();
    const navigate = useNavigate();

    const { data: medicine, isLoading } = useQuery({
        queryKey: ['medicine', id],
        queryFn: () => medicinesService.get(Number(id)),
        select: (res) => res.data,
    });

    if (isLoading) return <PageLoader />;
    if (!medicine) return <div>Medicine not found</div>;

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" onClick={() => navigate('/medicines')}>
                        <ArrowLeft className="mr-2 h-4 w-4" />
                        Back
                    </Button>
                    <h1 className="text-2xl font-bold">{medicine.brand_name}</h1>
                    <Badge variant={medicine.is_active ? 'success' : 'destructive'}>
                        {medicine.is_active ? 'Active' : 'Inactive'}
                    </Badge>
                </div>
                <Button onClick={() => navigate(`/medicines/${id}/edit`)}>
                    <Edit className="mr-2 h-4 w-4" />
                    Edit
                </Button>
            </div>

            <div className="grid gap-6 lg:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle>Basic Info</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <div><span className="text-gray-500">Brand:</span> {medicine.brand_name}</div>
                        <div><span className="text-gray-500">Generic:</span> {medicine.generic_name}</div>
                        <div><span className="text-gray-500">Form:</span> {medicine.dosage_form}</div>
                        <div><span className="text-gray-500">Strength:</span> {medicine.strength}</div>
                        <div><span className="text-gray-500">Schedule:</span> {medicine.schedule_type}</div>
                        {medicine.barcode && <div><span className="text-gray-500">Barcode:</span> {medicine.barcode}</div>}
                        {medicine.manufacturer && <div><span className="text-gray-500">Manufacturer:</span> {medicine.manufacturer.name}</div>}
                        {medicine.medicine_category && <div><span className="text-gray-500">Category:</span> {medicine.medicine_category.name}</div>}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Pricing (from batches)</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        {medicine.batches && medicine.batches.length > 0 ? (
                            <>
                                <div><span className="text-gray-500">Purchase Price:</span> {formatCurrency(medicine.batches[0].purchase_price_per_unit)}</div>
                                <div><span className="text-gray-500">Selling Price:</span> {formatCurrency(medicine.batches[0].selling_price_per_unit)}</div>
                                <div><span className="text-gray-500">MRP:</span> {formatCurrency(medicine.batches[0].mrp_per_unit)}</div>
                            </>
                        ) : (
                            <div className="text-gray-400">No batch pricing available</div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <div><span className="text-gray-500">Unit Type:</span> {medicine.unit_type}</div>
                        <div><span className="text-gray-500">Units Per Pack:</span> {medicine.units_per_pack}</div>
                        <div><span className="text-gray-500">Prescription Required:</span> {medicine.is_prescription_required ? 'Yes' : 'No'}</div>
                        {medicine.storage_conditions && <div><span className="text-gray-500">Storage:</span> {medicine.storage_conditions}</div>}
                        {medicine.description && <div><span className="text-gray-500">Description:</span> {medicine.description}</div>}
                    </CardContent>
                </Card>
            </div>

            {medicine.batches && medicine.batches.length > 0 && (
                <Card>
                    <CardHeader>
                        <CardTitle>Batches</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Batch #</TableHead>
                                    <TableHead>Expiry</TableHead>
                                    <TableHead>Qty</TableHead>
                                    <TableHead>Purchase</TableHead>
                                    <TableHead>Selling</TableHead>
                                    <TableHead>Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {medicine.batches.map((batch) => (
                                    <TableRow key={batch.id}>
                                        <TableCell className="font-medium">{batch.batch_number}</TableCell>
                                        <TableCell>{formatDate(batch.expiry_date)}</TableCell>
                                        <TableCell>{batch.quantity_in_stock}</TableCell>
                                        <TableCell>{formatCurrency(batch.purchase_price_per_unit)}</TableCell>
                                        <TableCell>{formatCurrency(batch.selling_price_per_unit)}</TableCell>
                                        <TableCell>
                                            <Badge variant={!batch.is_active ? 'secondary' : new Date(batch.expiry_date) < new Date() ? 'destructive' : 'success'}>
                                                {!batch.is_active ? 'Inactive' : new Date(batch.expiry_date) < new Date() ? 'Expired' : 'Active'}
                                            </Badge>
                                        </TableCell>
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
