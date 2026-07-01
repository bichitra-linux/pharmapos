import { type LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

interface EmptyStateProps {
    icon: LucideIcon;
    title: string;
    description?: string;
    action?: React.ReactNode;
    className?: string;
}

export function EmptyState({ icon: Icon, title, description, action, className }: EmptyStateProps) {
    return (
        <div className={cn('flex flex-col items-center justify-center py-12 text-center', className)}>
            <Icon className="mb-4 h-12 w-12 text-text-muted" aria-hidden="true" />
            <h3 className="mb-1 text-lg font-medium text-text">{title}</h3>
            {description && <p className="mb-4 text-sm text-text-muted">{description}</p>}
            {action}
        </div>
    );
}
