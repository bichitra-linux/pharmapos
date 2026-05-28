import * as React from 'react';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';

const badgeVariants = cva(
    'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors',
    {
        variants: {
            variant: {
                default: 'border-transparent bg-primary-100 text-primary-800',
                secondary: 'border-transparent bg-gray-100 text-gray-800',
                destructive: 'border-transparent bg-danger-100 text-danger-800',
                outline: 'text-gray-700',
                success: 'border-transparent bg-success-50 text-success-600',
                warning: 'border-transparent bg-warning-50 text-warning-600',
            },
        },
        defaultVariants: {
            variant: 'default',
        },
    }
);

export interface BadgeProps
    extends React.HTMLAttributes<HTMLSpanElement>,
        VariantProps<typeof badgeVariants> {}

function Badge({ className, variant, ...props }: BadgeProps) {
    return <span className={cn(badgeVariants({ variant }), className)} {...props} />;
}

export { Badge, badgeVariants };
