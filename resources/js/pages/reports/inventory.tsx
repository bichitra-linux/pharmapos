import { useQuery } from '@tanstack/react-query';
import { reportsService } from '@/services/reports';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { PageLoader } from '@/components/ui/spinner';
import { Button } from '@/components/ui/button';
import { useToast } from '@/components/ui/toast';
import { Download } from 'lucide-react';

export default function InventoryReport() {
    const { addToast } = useToast();
    const { data, isLoading } = useQuery({
        queryKey: ['reports', 'inventory'],
        queryFn: () => reportsService.inventory(),
        select: (res) => res.data,
    });

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold">Inventory Report</h1>
                <Button variant="outline" onClick={() => addToast({ type: 'info', title: 'Export coming soon' })}>
                    <Download className="mr-2 h-4 w-4" />
                    Export
                </Button>
            </div>

            {isLoading ? (
                <PageLoader />
            ) : data ? (
                <Card>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    {data.headers?.map((h) => (
                                        <TableHead key={h}>{h}</TableHead>
                                    ))}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {data.rows?.map((row, i) => (
                                    <TableRow key={i}>
                                        {row.map((cell, j) => (
                                            <TableCell key={j}>{String(cell)}</TableCell>
                                        ))}
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            ) : null}
        </div>
    );
}
