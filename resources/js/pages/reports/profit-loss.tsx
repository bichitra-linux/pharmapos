import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { reportsService } from '@/services/reports';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { PageLoader } from '@/components/ui/spinner';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';

export default function ProfitLossReport() {
    const [from, setFrom] = useState(new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]);
    const [to, setTo] = useState(new Date().toISOString().split('T')[0]);

    const { data, isLoading, refetch } = useQuery({
        queryKey: ['reports', 'profit-loss', from, to],
        queryFn: () => reportsService.profitLoss({ from, to }),
        select: (res) => res.data,
    });

    return (
        <div className="space-y-6">
            <h1 className="text-2xl font-bold">Profit & Loss Report</h1>

            <Card>
                <CardContent className="flex items-end gap-4 p-6">
                    <Input label="From" type="date" value={from} onChange={(e) => setFrom(e.target.value)} />
                    <Input label="To" type="date" value={to} onChange={(e) => setTo(e.target.value)} />
                    <Button onClick={() => refetch()}>Generate</Button>
                </CardContent>
            </Card>

            {isLoading ? (
                <PageLoader />
            ) : data ? (
                <Card>
                    <CardHeader><CardTitle>Profit & Loss Statement</CardTitle></CardHeader>
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
