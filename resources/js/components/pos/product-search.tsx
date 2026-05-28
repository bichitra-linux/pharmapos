import { useState, useRef } from 'react';
import { Search, Barcode } from 'lucide-react';
import { medicinesService } from '@/services/medicines';
import { useCartStore } from '@/stores/cartStore';
import { useDebounce } from '@/hooks/useDebounce';
import { formatCurrency } from '@/lib/utils';
import type { Medicine } from '@/types';

export function ProductSearch() {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<Medicine[]>([]);
    const [showResults, setShowResults] = useState(false);
    const debouncedQuery = useDebounce(query, 200);
    const addItem = useCartStore((s) => s.addItem);
    const inputRef = useRef<HTMLInputElement>(null);

    const handleSearch = async (value: string) => {
        setQuery(value);
        if (value.length >= 2) {
            try {
                const res = await medicinesService.search(value);
                setResults(res.data);
                setShowResults(true);
            } catch {
                setResults([]);
            }
        } else {
            setResults([]);
            setShowResults(false);
        }
    };

    const handleSelect = (medicine: Medicine) => {
        const batch = medicine.batches?.[0];
        if (!batch || batch.quantity_in_stock <= 0) return;

        addItem({
            medicine_id: medicine.id,
            batch_id: batch.id,
            name: medicine.brand_name,
            batch_number: batch.batch_number,
            unit_price: batch.selling_price_per_unit,
            quantity: 1,
            max_quantity: batch.quantity_in_stock,
            tax_rate: 13,
        });

        setQuery('');
        setResults([]);
        setShowResults(false);
    };

    return (
        <div className="relative">
            <div className="flex gap-2">
                <div className="relative flex-1">
                    <Search className="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" aria-hidden="true" />
                    <input
                        ref={inputRef}
                        type="text"
                        value={query}
                        onChange={(e) => handleSearch(e.target.value)}
                        onFocus={() => results.length > 0 && setShowResults(true)}
                        onBlur={() => setTimeout(() => setShowResults(false), 200)}
                        placeholder="Search by brand name, generic name, or barcode..."
                        aria-label="Search medicines by name or barcode"
                        className="h-12 w-full rounded-lg border-2 border-gray-300 bg-gray-50 pl-10 pr-4 text-lg focus:border-primary-500 focus:bg-white focus:outline-none"
                        autoFocus
                    />
                </div>
                <button
                    onClick={() => inputRef.current?.focus()}
                    aria-label="Scan barcode"
                    className="flex h-12 items-center gap-2 rounded-lg border-2 border-gray-300 bg-gray-50 px-4 text-gray-600 hover:bg-gray-100"
                >
                    <Barcode className="h-5 w-5" aria-hidden="true" />
                    <span className="hidden sm:inline">Scan</span>
                </button>
            </div>

            {showResults && results.length > 0 && (
                <div className="absolute top-full left-0 right-0 z-10 mt-1 max-h-80 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg">
                    {results.map((med) => {
                        const batch = med.batches?.[0];
                        const inStock = batch && batch.quantity_in_stock > 0;
                        return (
                            <button
                                key={med.id}
                                onClick={() => handleSelect(med)}
                                disabled={!inStock}
                                className="flex w-full items-center justify-between px-4 py-3 text-left hover:bg-gray-50 disabled:opacity-50"
                            >
                                <div>
                                    <p className="font-medium">{med.brand_name}</p>
                                    <p className="text-sm text-gray-500">
                                        {med.generic_name} • {med.dosage_form} {med.strength}
                                    </p>
                                </div>
                                <div className="text-right">
                                    <p className="font-medium">{formatCurrency(batch?.selling_price_per_unit || 0)}</p>
                                    <p className={`text-xs ${inStock ? 'text-success-600' : 'text-danger-600'}`}>
                                        {inStock ? `Stock: ${batch?.quantity_in_stock}` : 'Out of stock'}
                                    </p>
                                </div>
                            </button>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
