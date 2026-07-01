import * as React from 'react';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';

const badgeVariants = cva(
    'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors',
    {
        variants: {
            variant: {
                default: 'border-transparent bg-primary-100 text-primary-800',
                secondary: 'border-transparent bg-surface-muted text-text',
                destructive: 'border-transparent bg-danger-100 text-danger-800',
                outline: 'border-border text-text',
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
        VariantProps<typeof badgeVariants> {
    status?: boolean;
}

function Badge({ className, variant, status, ...props }: BadgeProps) {
    return <span className={cn(badgeVariants({ variant }), className)} {...(status ? { role: 'status' } : {})} {...props} />;
}

export { Badge, badgeVariants };
