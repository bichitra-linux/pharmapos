import * as React from 'react';
import { cn } from '@/lib/utils';

export interface InputProps extends React.InputHTMLAttributes<HTMLInputElement> {
    label?: string;
    error?: string;
    hint?: string;
}

const Input = React.forwardRef<HTMLInputElement, InputProps>(
    ({ className, type, label, error, hint, id, ...props }, ref) => {
        const inputId = id || React.useId();
        const errorId = `${inputId}-error`;
        const hintId = `${inputId}-hint`;
        const hasDescription = error || hint;

        return (
            <div className="w-full">
                {label && (
                    <label
                        htmlFor={inputId}
                        className="mb-1.5 block text-sm font-medium text-gray-700"
                    >
                        {label}
                    </label>
                )}
                <input
                    type={type}
                    id={inputId}
                    aria-invalid={error ? true : undefined}
                    aria-describedby={error ? errorId : hint ? hintId : undefined}
                    className={cn(
                        'flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm ring-offset-white file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                        error && 'border-danger-500 focus-visible:ring-danger-500',
                        className
                    )}
                    ref={ref}
                    {...props}
                />
                {hint && !error && (
                    <p id={hintId} className="mt-1 text-xs text-gray-500">{hint}</p>
                )}
                {error && <p id={errorId} className="mt-1 text-xs text-danger-600" role="alert">{error}</p>}
            </div>
        );
    }
);
Input.displayName = 'Input';

export { Input };
