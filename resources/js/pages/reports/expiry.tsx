import { useQuery } from '@tanstack/react-query';
import { reportsService } from '@/services/reports';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { PageLoader } from '@/components/ui/spinner';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { useState } from 'react';

export default function ExpiryReport() {
    const [days, setDays] = useState(90);

    const { data, isLoading, refetch } = useQuery({
        queryKey: ['reports', 'expiry', days],
        queryFn: () => reportsService.expiry({ days }),
        select: (res) => res.data,
    });

    return (
        <div className="space-y-6">
            <h1 className="text-2xl font-bold">Expiry Report</h1>

            <Card>
                <CardContent className="flex items-end gap-4 p-6">
                    <Input label="Days until expiry" type="number" value={days} onChange={(e) => setDays(Number(e.target.value))} />
                    <Button onClick={() => refetch()}>Generate</Button>
                </CardContent>
            </Card>

            {isLoading ? (
                <PageLoader />
            ) : data ? (
                <Card>
                    <CardHeader><CardTitle>Expiring Items</CardTitle></CardHeader>
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
