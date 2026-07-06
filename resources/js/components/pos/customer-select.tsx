import { useState } from 'react';
import { UserPlus, X } from 'lucide-react';
import { useQuery } from '@tanstack/react-query';
import { useCartStore } from '@/stores/cartStore';
import { customersService } from '@/services/customers';
import { useDebounce } from '@/hooks/useDebounce';
import { formatCurrency } from '@/lib/utils';
import type { Customer } from '@/types';

export function CustomerSelect() {
    const { customer_id, setCustomer } = useCartStore();
    const [query, setQuery] = useState('');
    const [showResults, setShowResults] = useState(false);
    const [selectedName, setSelectedName] = useState('');
    const debouncedQuery = useDebounce(query, 200);

    const { data: results } = useQuery({
        queryKey: ['customers', 'search', debouncedQuery],
        queryFn: () => customersService.search(debouncedQuery),
        enabled: debouncedQuery.length >= 2,
        staleTime: 30_000,
        select: (res) => res.data,
    });

    const { data: creditSummary } = useQuery({
        queryKey: ['customers', 'credit-summary', customer_id],
        queryFn: () => customersService.getCreditSummary(customer_id!),
        select: (res) => res.data,
        enabled: !!customer_id,
        staleTime: 60_000,
    });

    const handleSelect = (customer: Customer) => {
        setCustomer(customer.id);
        setSelectedName(customer.name);
        setQuery('');
        setShowResults(false);
    };

    const balance = creditSummary?.current_balance ?? 0;
    const isOverLimit = creditSummary?.is_over_limit ?? false;

    if (customer_id) {
        return (
            <div className={`flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm ${
                isOverLimit ? 'bg-danger-50 text-danger-700' : balance > 0 ? 'bg-warning-50 text-warning-700' : 'bg-success-50 text-success-700'
            }`}>
                <span>{selectedName || `Customer #${customer_id}`}</span>
                {balance > 0 && (
                    <span className="text-xs font-medium">
                        (owes {formatCurrency(balance)})
                    </span>
                )}
                <button onClick={() => { setCustomer(null); setSelectedName(''); }} aria-label="Clear customer">
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
                    onChange={(e) => setQuery(e.target.value)}
                    onFocus={() => (results?.length ?? 0) > 0 && setShowResults(true)}
                    onBlur={() => setTimeout(() => setShowResults(false), 200)}
                    placeholder="Search customer..."
                    aria-label="Search customers"
                    className="w-full sm:w-48 rounded-lg border border-border bg-surface px-3 py-2 text-sm focus:border-primary-500 focus:outline-none"
                />
            </div>
            {showResults && (results?.length ?? 0) > 0 && (
                <div className="absolute top-full left-0 z-10 mt-1 w-64 rounded-lg border border-border bg-surface py-1 shadow-lg">
                    {results!.map((c) => (
                        <button
                            key={c.id}
                            onClick={() => handleSelect(c)}
                            className="flex w-full items-center justify-between px-3 py-2 text-sm hover:bg-surface-muted"
                        >
                            <div>
                                <p className="font-medium">{c.name}</p>
                                <p className="text-xs text-text-muted">{c.phone}</p>
                            </div>
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
