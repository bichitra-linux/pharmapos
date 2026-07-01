import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { medicinesService } from '@/services/medicines';
import { posStatsService } from '@/services/pos-stats';
import { useCartStore } from '@/stores/cartStore';
import { formatCurrency } from '@/lib/utils';
import { VAT_RATE } from '@/lib/constants';
import { Spinner } from '@/components/ui/spinner';
import { Skeleton } from '@/components/ui/skeleton';

const TABS = ['All', 'Frequent', 'Low Stock', 'Expiring', 'Tablets', 'Syrups', 'Injections'] as const;

export function ProductGrid() {
    const addItem = useCartStore((s) => s.addItem);
    const [activeTab, setActiveTab] = useState<string>('All');

    const { data: medicines, isLoading } = useQuery({
        queryKey: ['medicines', 'grid'],
        queryFn: () => medicinesService.list({ per_page: 24 }),
        select: (res) => res.data,
        placeholderData: (prev) => prev,
        staleTime: 30_000,
    });

    const { data: topMeds } = useQuery({
        queryKey: ['pos', 'stats', 'top'],
        queryFn: () => posStatsService.getStats(),
        select: (res) => res.data?.top_medicines ?? [],
        staleTime: 60_000,
    });

    let filtered = medicines ?? [];
    if (activeTab === 'Frequent' && topMeds && topMeds.length > 0) {
        const topIds = new Set(topMeds.map((m: any) => m.id));
        filtered = filtered.filter((m: any) => topIds.has(m.id));
    } else if (activeTab === 'Low Stock') {
        filtered = filtered.filter((m: any) => {
            const batch = m.batches?.[0];
            return batch && batch.quantity_in_stock <= (m.reorder_level ?? 10);
        });
    } else if (activeTab === 'Expiring') {
        filtered = filtered.filter((m: any) => {
            const batch = m.batches?.[0];
            if (!batch?.expiry_date) return false;
            const days = (new Date(batch.expiry_date).getTime() - Date.now()) / 86400000;
            return days > 0 && days < 90;
        });
    } else if (['Tablets', 'Syrups', 'Injections'].includes(activeTab)) {
        const formMap: Record<string, string> = { Tablets: 'tablet', Syrups: 'syrup', Injections: 'injection' };
        filtered = filtered.filter((m: any) => m.dosage_form === formMap[activeTab]);
    }

    return (
        <div>
            {/* Tabs */}
            <div className="flex gap-1 mb-3 overflow-x-auto pb-1 text-xs scrollbar-none">
                {TABS.map((tab) => (
                    <button
                        key={tab}
                        onClick={() => setActiveTab(tab)}
                        className={`px-3 py-1.5 rounded-full whitespace-nowrap font-medium transition-colors ${
                            activeTab === tab ? 'bg-primary-600 text-white' : 'bg-surface text-text-muted border border-border hover:bg-surface-muted'
                        }`}
                    >
                        {tab}
                    </button>
                ))}
            </div>

            {/* Grid */}
            {isLoading ? (
                <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                    {Array.from({ length: 8 }).map((_, i) => (
                        <div key={i} className="rounded-lg border border-border p-3 space-y-2">
                            <Skeleton className="h-4 w-3/4" />
                            <Skeleton className="h-3 w-1/2" />
                            <Skeleton className="h-3 w-1/3" />
                        </div>
                    ))}
                </div>
            ) : (
                <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                    {filtered.map((med: any) => {
                        const batch = med.batches?.[0];
                        const inStock = batch && batch.quantity_in_stock > 0;
                        const stockPct = batch ? (batch.quantity_in_stock / (med.reorder_level || 10)) * 100 : 100;
                        const expDays = batch?.expiry_date ? (new Date(batch.expiry_date).getTime() - Date.now()) / 86400000 : 999;
                        const isRx = ['h', 'h1', 'x'].includes(med.schedule_type);

                        return (
                            <div key={med.id} className={`flex flex-col rounded-lg border bg-surface p-3 text-left transition-shadow hover:shadow-md ${!inStock ? 'opacity-50' : ''}`}>
                                <div className="flex items-start justify-between gap-1">
                                    <p className="text-sm font-medium truncate flex-1">{med.brand_name}{med.strength ? ` ${med.strength}` : ''}</p>
                                    {isRx && <span className="text-[9px] px-1 rounded-full bg-danger-100 text-danger-700 font-semibold shrink-0">Rx</span>}
                                </div>
                                <p className="text-xs text-text-muted truncate">{med.generic_name}{med.manufacturer?.name ? ` • ${med.manufacturer.name}` : ''}</p>
                                {batch && (
                                    <p className="text-[10px] text-text-muted mt-0.5">
                                        Batch: {batch.batch_number}
                                        <span className={`ml-1 ${stockPct < 10 ? 'text-danger-600' : stockPct < 50 ? 'text-warning-600' : 'text-success-600'}`}>
                                            ●
                                        </span>
                                    </p>
                                )}
                                <div className="mt-auto flex items-center justify-between pt-2">
                                    <span className="text-sm font-bold text-primary-600">{formatCurrency(batch?.selling_price_per_unit || 0)}</span>
                                    <span className={`text-[10px] ${inStock ? 'text-success-600' : 'text-danger-600'}`}>
                                        {inStock ? `${batch.quantity_in_stock} ${med.unit_type || ''}` : 'Out'}
                                    </span>
                                </div>
                                {inStock && (
                                    <div className="mt-1.5 flex gap-1">
                                        <button onClick={() => {
                                            addItem({
                                                medicine_id: med.id, batch_id: batch.id, name: med.brand_name + (med.strength ? ' ' + med.strength : ''),
                                                secondary_name: (med.generic_name ?? '') + (med.manufacturer?.name ? ' • ' + med.manufacturer.name : ''),
                                                batch_number: batch.batch_number, unit_price: batch.selling_price_per_unit, quantity: 1,
                                                max_quantity: batch.quantity_in_stock, tax_rate: VAT_RATE,
                                                units_per_pack: med.units_per_pack ?? 1, piece_unit_label: med.piece_unit_label ?? 'piece',
                                                allow_piece_selling: med.allow_piece_selling ?? false,
                                            });
                                        }} className="flex-1 py-1 rounded bg-primary-50 text-primary-700 text-[10px] font-medium hover:bg-primary-100">+1</button>
                                        <button onClick={() => {
                                            addItem({
                                                medicine_id: med.id, batch_id: batch.id, name: med.brand_name + (med.strength ? ' ' + med.strength : ''),
                                                secondary_name: (med.generic_name ?? '') + (med.manufacturer?.name ? ' • ' + med.manufacturer.name : ''),
                                                batch_number: batch.batch_number, unit_price: batch.selling_price_per_unit, quantity: 5,
                                                max_quantity: batch.quantity_in_stock, tax_rate: VAT_RATE,
                                                units_per_pack: med.units_per_pack ?? 1, piece_unit_label: med.piece_unit_label ?? 'piece',
                                                allow_piece_selling: med.allow_piece_selling ?? false,
                                            });
                                        }} className="flex-1 py-1 rounded bg-surface-muted text-text-muted text-[10px] font-medium hover:bg-surface-muted">+5</button>
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
