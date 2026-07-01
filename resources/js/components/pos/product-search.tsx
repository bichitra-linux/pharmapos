import { useState, forwardRef, useRef, useEffect } from 'react';
import { Search, Barcode } from 'lucide-react';
import { useQuery } from '@tanstack/react-query';
import { medicinesService } from '@/services/medicines';
import { useCartStore } from '@/stores/cartStore';
import { useDebounce } from '@/hooks/useDebounce';
import { formatCurrency } from '@/lib/utils';
import { VAT_RATE } from '@/lib/constants';
import type { Medicine } from '@/types';

export const ProductSearch = forwardRef<HTMLInputElement>(function ProductSearch(_props, ref) {
    const [query, setQuery] = useState('');
    const [showResults, setShowResults] = useState(false);
    const [selectedIdx, setSelectedIdx] = useState(-1);
    const debouncedQuery = useDebounce(query, 200);
    const addItem = useCartStore((s) => s.addItem);
    const inputRef = ref as React.RefObject<HTMLInputElement | null>;
    const listRef = useRef<HTMLDivElement>(null);

    const { data: results } = useQuery({
        queryKey: ['medicines', 'search', debouncedQuery],
        queryFn: () => medicinesService.search(debouncedQuery),
        enabled: debouncedQuery.length >= 2,
        staleTime: 30_000,
        select: (res) => res.data,
    });

    useEffect(() => {
        setSelectedIdx(-1);
    }, [results]);

    const handleAdd = (medicine: Medicine) => {
        const batch = medicine.batches?.[0];
        if (!batch || batch.quantity_in_stock <= 0) return;
        addItem({
            medicine_id: medicine.id, batch_id: batch.id,
            name: medicine.brand_name + (medicine.strength ? ' ' + medicine.strength : ''),
            secondary_name: (medicine.generic_name ?? '') + (medicine.manufacturer?.name ? ' • ' + medicine.manufacturer.name : ''),
            batch_number: batch.batch_number, unit_price: batch.selling_price_per_unit, quantity: 1,
            max_quantity: batch.quantity_in_stock, tax_rate: VAT_RATE,
            units_per_pack: medicine.units_per_pack ?? 1,
            piece_unit_label: medicine.piece_unit_label ?? 'piece',
            allow_piece_selling: medicine.allow_piece_selling ?? false,
        });
        setQuery('');
        setShowResults(false);
    };

    const handleKeyDown = (e: React.KeyboardEvent) => {
        if (!results?.length) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); setSelectedIdx((prev) => Math.min(prev + 1, results.length - 1)); }
        if (e.key === 'ArrowUp') { e.preventDefault(); setSelectedIdx((prev) => Math.max(prev - 1, 0)); }
        if (e.key === 'Enter' && selectedIdx >= 0) { e.preventDefault(); handleAdd(results[selectedIdx]); }
    };

    return (
        <div className="relative">
            <div className="flex gap-2">
                <div className="relative flex-1">
                    <Search className="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-text-muted" aria-hidden="true" />
                    <input
                        ref={inputRef}
                        type="text"
                        value={query}
                        onChange={(e) => { setQuery(e.target.value); setShowResults(true); }}
                        onFocus={() => (results?.length ?? 0) > 0 && setShowResults(true)}
                        onBlur={() => setTimeout(() => { setShowResults(false); setSelectedIdx(-1); }, 200)}
                        onKeyDown={handleKeyDown}
                        placeholder="Search by brand name, generic name, or barcode..."
                        aria-label="Search medicines"
                        role="combobox"
                        aria-expanded={showResults}
                        aria-controls="search-results"
                        className="h-12 w-full rounded-lg border-2 border-border bg-surface-muted pl-10 pr-4 text-lg focus:border-primary-500 focus:bg-surface focus:outline-none"
                        autoFocus
                    />
                </div>
                <button
                    onClick={() => inputRef.current?.focus()}
                    aria-label="Scan barcode"
                    className="flex h-12 items-center gap-2 rounded-lg border-2 border-border bg-surface-muted px-4 text-text-muted hover:bg-surface-muted"
                >
                    <Barcode className="h-5 w-5" aria-hidden="true" />
                    <span className="hidden sm:inline">Scan</span>
                </button>
            </div>

            {showResults && (results?.length ?? 0) > 0 && (
                <div
                    id="search-results"
                    ref={listRef}
                    role="listbox"
                    className="absolute top-full left-0 right-0 z-10 mt-1 max-h-80 overflow-y-auto rounded-lg border border-border bg-surface shadow-lg"
                >
                    {results!.map((med, idx) => {
                        const batch = med.batches?.[0];
                        const inStock = batch && batch.quantity_in_stock > 0;
                        const isRx = ['h', 'h1', 'x'].includes(med.schedule_type);
                        const isSelected = idx === selectedIdx;
                        return (
                            <button
                                key={med.id}
                                role="option"
                                aria-selected={isSelected}
                                onClick={() => handleAdd(med)}
                                disabled={!inStock}
                                className={`flex w-full items-center justify-between px-4 py-2.5 text-left ${
                                    isSelected ? 'bg-primary-50' : 'hover:bg-surface-muted'
                                } disabled:opacity-50`}
                            >
                                <div className="min-w-0 flex-1">
                                    <div className="flex items-center gap-1.5">
                                        <p className="font-medium text-sm truncate">{med.brand_name}{med.strength ? ` ${med.strength}` : ''}</p>
                                        {isRx && <span className="text-[9px] px-1 rounded-full bg-danger-100 text-danger-700 font-semibold shrink-0">Rx</span>}
                                    </div>
                                    <p className="text-xs text-text-muted truncate">{med.generic_name}{med.manufacturer?.name ? ` • ${med.manufacturer.name}` : ''}</p>
                                    {batch && <p className="text-[10px] text-text-muted">Batch: {batch.batch_number} | Exp: {batch.expiry_date ? new Date(batch.expiry_date).toLocaleDateString() : '-'}</p>}
                                </div>
                                <div className="text-right ml-3 shrink-0">
                                    <p className="font-medium text-sm">{formatCurrency(batch?.selling_price_per_unit || 0)}</p>
                                    <p className={`text-xs ${inStock ? 'text-success-600' : 'text-danger-600'}`}>{inStock ? `Stock: ${batch.quantity_in_stock}` : 'Out of stock'}</p>
                                </div>
                            </button>
                        );
                    })}
                </div>
            )}
        </div>
    );
});
