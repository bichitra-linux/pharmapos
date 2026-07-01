import { useQuery } from '@tanstack/react-query';
import { posStatsService } from '@/services/pos-stats';
import { formatCurrency } from '@/lib/utils';

export function PosFooter() {
    const { data } = useQuery({
        queryKey: ['pos', 'stats'],
        queryFn: () => posStatsService.getStats(),
        select: (res) => res.data,
        refetchInterval: 60_000,
    });

    return (
        <footer className="hidden md:flex h-8 items-center justify-between border-t border-border bg-surface px-4 text-[10px] text-text-muted">
            <div className="flex items-center gap-4">
                <span>Sales Today: {data?.sales_today ?? 0}</span>
                <span>Revenue: {formatCurrency(data?.revenue_today ?? 0)}</span>
                <span>Items Sold: {data?.items_sold_today ?? 0}</span>
            </div>
            {data?.top_medicines && data.top_medicines.length > 0 && (
                <div className="flex items-center gap-2">
                    <span className="font-medium">Top:</span>
                    {data.top_medicines.slice(0, 3).map((m) => (
                        <span key={m.id}>{m.brand_name}</span>
                    ))}
                </div>
            )}
        </footer>
    );
}
