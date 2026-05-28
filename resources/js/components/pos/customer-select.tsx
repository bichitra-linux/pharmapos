import { useState } from 'react';
import { UserPlus, X } from 'lucide-react';
import { useCartStore } from '@/stores/cartStore';
import { customersService } from '@/services/customers';
import { useDebounce } from '@/hooks/useDebounce';
import type { Customer } from '@/types';

export function CustomerSelect() {
    const { customer_id, setCustomer } = useCartStore();
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<Customer[]>([]);
    const [showResults, setShowResults] = useState(false);
    const [selectedName, setSelectedName] = useState('');
    const debouncedQuery = useDebounce(query, 200);

    const handleSearch = async (value: string) => {
        setQuery(value);
        if (value.length >= 2) {
            try {
                const res = await customersService.search(value);
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

    const handleSelect = (customer: Customer) => {
        setCustomer(customer.id);
        setSelectedName(customer.name);
        setQuery('');
        setResults([]);
        setShowResults(false);
    };

    if (customer_id) {
        return (
            <div className="flex items-center gap-2 rounded-lg bg-success-50 px-3 py-1.5 text-sm text-success-700">
                <span>{selectedName || `Customer #${customer_id}`}</span>
                <button onClick={() => { setCustomer(null); setSelectedName(''); }}>
                    <X className="h-4 w-4" />
                </button>
            </div>
        );
    }

    return (
        <div className="relative">
            <div className="flex items-center gap-2">
                <input
                    type="text"
                    value={query}
                    onChange={(e) => handleSearch(e.target.value)}
                    onFocus={() => results.length > 0 && setShowResults(true)}
                    onBlur={() => setTimeout(() => setShowResults(false), 200)}
                    placeholder="Search customer..."
                    className="w-48 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
                />
            </div>
            {showResults && results.length > 0 && (
                <div className="absolute top-full left-0 z-10 mt-1 w-64 rounded-lg border border-gray-200 bg-white py-1 shadow-lg">
                    {results.map((c) => (
                        <button
                            key={c.id}
                            onClick={() => handleSelect(c)}
                            className="flex w-full items-center justify-between px-3 py-2 text-sm hover:bg-gray-50"
                        >
                            <div>
                                <p className="font-medium">{c.name}</p>
                                <p className="text-xs text-gray-500">{c.phone}</p>
                            </div>
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
