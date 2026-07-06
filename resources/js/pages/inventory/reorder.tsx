import { useQuery } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { inventoryService } from '@/services/inventory';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { PageLoader } from '@/components/ui/spinner';
import { Package, AlertTriangle, ShoppingCart, Building2 } from 'lucide-react';

export default function ReorderPage() {
    const navigate = useNavigate();

    const { data, isLoading, error } = useQuery({
        queryKey: ['inventory', 'reorder-suggestions'],
        queryFn: () => inventoryService.getReorderSuggestions(),
        select: (res) => res.data,
        staleTime: 30_000,
    });

    if (isLoading) return <PageLoader />;

    if (error) {
        return (
            <div className="py-12 text-center text-text-muted">
                <AlertTriangle className="mx-auto h-8 w-8 text-warning-500" />
                <p className="mt-2">Could not load reorder suggestions.</p>
            </div>
        );
    }

    const groups = data as Record<string, { id: number; brand_name: string; generic_name: string; current_stock: number; reorder_level: number; supplier_name: string | null }[]> | undefined;

    if (!groups || Object.keys(groups).length === 0) {
        return (
            <div className="space-y-6">
                <h1 className="text-2xl font-bold">Reorder Suggestions</h1>
                <Card>
                    <CardContent className="flex flex-col items-center py-12">
                        <Package className="h-12 w-12 text-success-500" />
                        <p className="mt-4 text-lg font-medium text-text">All stock levels are above reorder points</p>
                        <p className="text-sm text-text-muted">No items need reordering right now.</p>
                    </CardContent>
                </Card>
            </div>
        );
    }

    const totalItems = Object.values(groups).reduce((sum, items) => sum + items.length, 0);

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold">Reorder Suggestions</h1>
                    <p className="text-sm text-text-muted mt-1">{totalItems} items below reorder level</p>
                </div>
            </div>

            {Object.entries(groups).map(([supplier, items]) => (
                <Card key={supplier}>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Building2 className="h-4 w-4 text-text-muted" />
                            {supplier}
                            <Badge variant="secondary" className="ml-2">{items.length} items</Badge>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-hidden rounded-lg border border-border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="bg-surface-muted border-b border-border">
                                        <th className="px-4 py-2.5 text-left font-medium text-text-muted">Medicine</th>
                                        <th className="px-4 py-2.5 text-right font-medium text-text-muted">Stock</th>
                                        <th className="px-4 py-2.5 text-right font-medium text-text-muted">Reorder At</th>
                                        <th className="px-4 py-2.5 text-right font-medium text-text-muted">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.map((item) => {
                                        const ratio = item.current_stock / (item.reorder_level || 1);
                                        const isCritical = ratio <= 0.25;
                                        const isLow = ratio <= 0.5;
                                        return (
                                            <tr key={item.id} className="border-b border-border last:border-0 hover:bg-surface-muted">
                                                <td className="px-4 py-2.5">
                                                    <p className="font-medium">{item.brand_name}</p>
                                                    {item.generic_name && (
                                                        <p className="text-xs text-text-muted">{item.generic_name}</p>
                                                    )}
                                                </td>
                                                <td className="px-4 py-2.5 text-right font-medium">{item.current_stock}</td>
                                                <td className="px-4 py-2.5 text-right text-text-muted">{item.reorder_level}</td>
                                                <td className="px-4 py-2.5 text-right">
                                                    {isCritical ? (
                                                        <Badge variant="destructive">Critical</Badge>
                                                    ) : isLow ? (
                                                        <Badge variant="warning">Low</Badge>
                                                    ) : (
                                                        <Badge variant="secondary">Reorder</Badge>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}
