import * as React from 'react';
import { cn } from '@/lib/utils';

export interface SelectOption {
    label: string;
    value: string | number;
}

interface SelectProps {
    options: SelectOption[];
    value?: string | number;
    onChange: (value: string | number) => void;
    placeholder?: string;
    label?: string;
    error?: string;
    disabled?: boolean;
    className?: string;
}

export function Select({
    options,
    value,
    onChange,
    placeholder = 'Select...',
    label,
    error,
    disabled,
    className,
}: SelectProps) {
    const selectId = React.useId();
    const errorId = `${selectId}-error`;

    return (
        <div className="w-full">
            {label && (
                <label htmlFor={selectId} className="mb-1.5 block text-sm font-medium text-gray-700">
                    {label}
                </label>
            )}
            <select
                id={selectId}
                value={value ?? ''}
                onChange={(e) => {
                    const val = e.target.value;
                    onChange(isNaN(Number(val)) ? val : Number(val));
                }}
                disabled={disabled}
                aria-invalid={error ? true : undefined}
                aria-describedby={error ? errorId : undefined}
                className={cn(
                    'flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm ring-offset-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                    error && 'border-danger-500 focus-visible:ring-danger-500',
                    className
                )}
            >
                <option value="">{placeholder}</option>
                {options.map((opt) => (
                    <option key={opt.value} value={opt.value}>
                        {opt.label}
                    </option>
                ))}
            </select>
            {error && <p id={errorId} className="mt-1 text-xs text-danger-600" role="alert">{error}</p>}
        </div>
    );
}
