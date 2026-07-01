import { cn } from '@/lib/utils';

interface SkeletonProps {
    className?: string;
}

export function Skeleton({ className }: SkeletonProps) {
    return (
        <div
            role="status"
            aria-label="Loading"
            className={cn('animate-pulse rounded bg-border', className)}
        />
    );
}

export function TableSkeleton({ rows = 5, cols = 4 }: { rows?: number; cols?: number }) {
    return (
        <div className="space-y-3">
            <div className="flex gap-4">
                {Array.from({ length: cols }).map((_, i) => (
                    <Skeleton key={`h-${i}`} className="h-4 flex-1" />
                ))}
            </div>
            {Array.from({ length: rows }).map((_, r) => (
                <div key={`r-${r}`} className="flex gap-4">
                    {Array.from({ length: cols }).map((_, c) => (
                        <Skeleton key={`c-${r}-${c}`} className="h-6 flex-1" />
                    ))}
                </div>
            ))}
        </div>
    );
}

export function CardSkeleton() {
    return (
        <div className="rounded-lg border border-border p-6 space-y-4">
            <Skeleton className="h-5 w-1/3" />
            <Skeleton className="h-8 w-1/4" />
            <Skeleton className="h-4 w-1/2" />
        </div>
    );
}
