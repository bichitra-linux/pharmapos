import { Search, X } from 'lucide-react';
import { useDebounce } from '@/hooks/useDebounce';
import { cn } from '@/lib/utils';
import { useState, useEffect } from 'react';

interface SearchInputProps {
    value?: string;
    onChange: (value: string) => void;
    placeholder?: string;
    className?: string;
    debounceMs?: number;
    autoFocus?: boolean;
}

export function SearchInput({
    value = '',
    onChange,
    placeholder = 'Search...',
    className,
    debounceMs = 300,
    autoFocus,
}: SearchInputProps) {
    const [localValue, setLocalValue] = useState(value);
    const debouncedValue = useDebounce(localValue, debounceMs);

    useEffect(() => {
        onChange(debouncedValue);
    }, [debouncedValue, onChange]);

    useEffect(() => {
        setLocalValue(value);
    }, [value]);

    return (
        <div className={cn('relative', className)}>
            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-text-muted" />
            <input
                type="text"
                value={localValue}
                onChange={(e) => setLocalValue(e.target.value)}
                placeholder={placeholder}
                autoFocus={autoFocus}
                aria-label={placeholder}
                className="flex h-10 w-full rounded-md border border-border bg-surface pl-9 pr-9 text-sm placeholder:text-text-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
            />
            {localValue && (
                <button
                    onClick={() => {
                        setLocalValue('');
                        onChange('');
                    }}
                    aria-label="Clear search"
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-text-muted hover:text-text"
                >
                    <X className="h-4 w-4" />
                </button>
            )}
        </div>
    );
}
