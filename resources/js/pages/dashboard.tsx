import { useQuery } from '@tanstack/react-query';
import { dashboardService } from '@/services/dashboard';
import { PageLoader } from '@/components/ui/spinner';
import { Badge } from '@/components/ui/badge';
import { formatCurrency, formatDate } from '@/lib/utils';
import {
    DollarSign,
    ShoppingCart,
    Users,
    Pill,
    AlertTriangle,
    TrendingUp,
    Package,
} from 'lucide-react';

export default function DashboardPage() {
    const { data: summary, isLoading } = useQuery({
        queryKey: ['dashboard', 'summary'],
        queryFn: () => dashboardService.getSummary(),
        select: (res) => res.data,
        staleTime: 60_000,
        placeholderData: (prev) => prev,
    });

    const { data: expiryAlerts } = useQuery({
        queryKey: ['dashboard', 'expiry-alerts'],
        queryFn: () => dashboardService.getExpiryAlerts(30),
        select: (res) => res.data,
        staleTime: 60_000,
        placeholderData: (prev) => prev,
    });

    const { data: lowStock } = useQuery({
        queryKey: ['dashboard', 'low-stock'],
        queryFn: () => dashboardService.getLowStock(),
        select: (res) => res.data,
        staleTime: 60_000,
        placeholderData: (prev) => prev,
    });

    const { data: topMedicines } = useQuery({
        queryKey: ['dashboard', 'top-medicines'],
        queryFn: () => dashboardService.getTopMedicines({ limit: 5 }),
        select: (res) => res.data,
        staleTime: 60_000,
        placeholderData: (prev) => prev,
    });

    const { data: salesChart } = useQuery({
        queryKey: ['dashboard', 'sales-chart'],
        queryFn: () => dashboardService.getSalesChart(30),
        select: (res) => res.data,
        staleTime: 60_000,
        placeholderData: (prev) => prev,
    });

    if (isLoading) return <PageLoader />;

    const todaySales = summary?.today_sales?.count ?? 0;
    const todayRevenue = summary?.today_sales?.revenue ?? 0;

    return (
        <div className="space-y-6">
            <div className="flex items-baseline justify-between">
                <h1 className="text-2xl font-bold text-text">Dashboard</h1>
                <p className="text-sm text-text-muted">Today's overview</p>
            </div>

            <div className="grid gap-4 lg:grid-cols-3">
                <div className="lg:col-span-2 rounded-xl border border-border bg-surface p-6">
                    <div className="flex items-center gap-3 mb-1">
                        <DollarSign className="h-5 w-5 text-success-600" aria-hidden="true" />
                        <span className="text-sm font-medium text-text-muted">Today's Revenue</span>
                    </div>
                    <p className="text-4xl font-bold text-text tracking-tight">{formatCurrency(todayRevenue)}</p>
                    <div className="mt-4 flex items-center gap-6 text-sm text-text-muted">
                        <span className="flex items-center gap-1.5">
                            <ShoppingCart className="h-4 w-4" aria-hidden="true" />
                            {todaySales} sales
                        </span>
                        <span className="flex items-center gap-1.5">
                            <Users className="h-4 w-4" aria-hidden="true" />
                            {summary?.customer_count ?? 0} customers
                        </span>
                        <span className="flex items-center gap-1.5">
                            <Pill className="h-4 w-4" aria-hidden="true" />
                            {summary?.medicine_count ?? 0} medicines
                        </span>
                    </div>
                </div>

                <div className="rounded-xl border border-border bg-surface p-6">
                    <div className="flex items-center gap-3 mb-1">
                        <TrendingUp className="h-5 w-5 text-primary-600" aria-hidden="true" />
                        <span className="text-sm font-medium text-text-muted">30-Day Trend</span>
                    </div>
                    <div className="mt-3 h-32">
                        {salesChart && salesChart.length > 0 ? (
                            <div className="flex h-full items-end gap-0.5">
                                {(() => {
                                    const maxRevenue = salesChart.length > 0 ? Math.max(...salesChart.map((d) => d.revenue)) : 0;
                                    return salesChart.map((day, i) => {
                                        const height = maxRevenue > 0 ? (day.revenue / maxRevenue) * 100 : 0;
                                        return (
                                            <div
                                                key={i}
                                                className="flex-1 rounded-t-sm bg-primary-400 hover:bg-primary-600 transition-colors"
                                                style={{ height: `${Math.max(3, height)}%` }}
                                                title={`${day.date}: ${formatCurrency(day.revenue)}`}
                                            />
                                        );
                                    });
                                })()}
                            </div>
                        ) : (
                            <div className="flex h-full items-center justify-center text-text-muted text-sm">
                                No data yet
                            </div>
                        )}
                    </div>
                </div>
            </div>

            <div className="grid gap-6 xl:grid-cols-2">
                <section>
                    <div className="mb-3 flex items-center gap-2">
                        <AlertTriangle className="h-4 w-4 text-warning-500" aria-hidden="true" />
                        <h2 className="text-sm font-semibold text-text">Expiring Soon</h2>
                    </div>
                    <div className="overflow-hidden rounded-xl border border-border bg-surface">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-border bg-surface-muted">
                                    <th className="px-4 py-2.5 text-left font-medium text-text-muted">Medicine</th>
                                    <th className="px-4 py-2.5 text-left font-medium text-text-muted">Batch</th>
                                    <th className="px-4 py-2.5 text-left font-medium text-text-muted">Expiry</th>
                                    <th className="px-4 py-2.5 text-right font-medium text-text-muted">Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                {expiryAlerts && expiryAlerts.length > 0 ? expiryAlerts.slice(0, 5).map((alert) => (
                                    <tr key={alert.batch_id} className="border-b border-border last:border-0">
                                        <td className="px-4 py-2.5 font-medium text-text">{alert.brand_name}</td>
                                        <td className="px-4 py-2.5 text-text-muted">{alert.batch_number}</td>
                                        <td className="px-4 py-2.5 text-text-muted">{formatDate(alert.expiry_date)}</td>
                                        <td className="px-4 py-2.5 text-right text-text-muted">{alert.quantity}</td>
                                    </tr>
                                )) : (
                                    <tr>
                                        <td colSpan={4} className="px-4 py-8 text-center text-text-muted">No expiring items</td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </section>

                <section>
                    <div className="mb-3 flex items-center gap-2">
                        <Package className="h-4 w-4 text-danger-500" aria-hidden="true" />
                        <h2 className="text-sm font-semibold text-text">Low Stock</h2>
                    </div>
                    <div className="overflow-hidden rounded-xl border border-border bg-surface">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-border bg-surface-muted">
                                    <th className="px-4 py-2.5 text-left font-medium text-text-muted">Medicine</th>
                                    <th className="px-4 py-2.5 text-right font-medium text-text-muted">Stock</th>
                                    <th className="px-4 py-2.5 text-right font-medium text-text-muted">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {lowStock && lowStock.length > 0 ? lowStock.slice(0, 5).map((item) => (
                                    <tr key={item.id} className="border-b border-border last:border-0">
                                        <td className="px-4 py-2.5 font-medium text-text">{item.brand_name}</td>
                                        <td className="px-4 py-2.5 text-right text-text-muted">{item.current_stock}</td>
                                        <td className="px-4 py-2.5 text-right">
                                            <Badge variant="destructive">Low</Badge>
                                        </td>
                                    </tr>
                                )) : (
                                    <tr>
                                        <td colSpan={3} className="px-4 py-8 text-center text-text-muted">All stock levels OK</td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </section>

                <section className="xl:col-span-2">
                    <div className="mb-3 flex items-center gap-2">
                        <TrendingUp className="h-4 w-4 text-success-500" aria-hidden="true" />
                        <h2 className="text-sm font-semibold text-text">Top Selling Medicines</h2>
                    </div>
                    <div className="overflow-hidden rounded-xl border border-border bg-surface">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-border bg-surface-muted">
                                    <th className="px-4 py-2.5 text-left font-medium text-text-muted">Medicine</th>
                                    <th className="px-4 py-2.5 text-left font-medium text-text-muted">Generic</th>
                                    <th className="px-4 py-2.5 text-right font-medium text-text-muted">Sold</th>
                                    <th className="px-4 py-2.5 text-right font-medium text-text-muted">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                {topMedicines && topMedicines.length > 0 ? topMedicines.map((med) => (
                                    <tr key={med.id} className="border-b border-border last:border-0">
                                        <td className="px-4 py-2.5 font-medium text-text">{med.brand_name}</td>
                                        <td className="px-4 py-2.5 text-text-muted">{med.generic_name}</td>
                                        <td className="px-4 py-2.5 text-right text-text-muted">{med.total_quantity}</td>
                                        <td className="px-4 py-2.5 text-right font-medium text-text">{formatCurrency(med.total_revenue)}</td>
                                    </tr>
                                )) : (
                                    <tr>
                                        <td colSpan={4} className="px-4 py-8 text-center text-text-muted">No sales data yet</td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    );
}
