import { ChevronLeft, ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';

interface PaginationProps {
    currentPage: number;
    lastPage: number;
    onPageChange: (page: number) => void;
    className?: string;
}

export function Pagination({ currentPage, lastPage, onPageChange, className }: PaginationProps) {
    if (lastPage <= 1) return null;

    const pages: (number | string)[] = [];
    for (let i = 1; i <= lastPage; i++) {
        if (i === 1 || i === lastPage || (i >= currentPage - 1 && i <= currentPage + 1)) {
            pages.push(i);
        } else if (pages[pages.length - 1] !== '...') {
            pages.push('...');
        }
    }

    return (
        <nav className={cn('flex items-center justify-center gap-1', className)} aria-label="Pagination">
            <button
                onClick={() => onPageChange(currentPage - 1)}
                disabled={currentPage === 1}
                aria-label="Previous page"
                className="inline-flex h-9 w-9 items-center justify-center rounded-md border border-gray-300 bg-white text-sm hover:bg-gray-50 disabled:opacity-50"
            >
                <ChevronLeft className="h-4 w-4" aria-hidden="true" />
            </button>
            {pages.map((page, idx) =>
                typeof page === 'string' ? (
                    <span key={`dots-${idx}`} className="px-2 text-gray-400" aria-hidden="true">
                        ...
                    </span>
                ) : (
                    <button
                        key={page}
                        onClick={() => onPageChange(page)}
                        aria-current={page === currentPage ? 'page' : undefined}
                        aria-label={`Page ${page}`}
                        className={cn(
                            'inline-flex h-9 w-9 items-center justify-center rounded-md border text-sm',
                            page === currentPage
                                ? 'border-primary-600 bg-primary-600 text-white'
                                : 'border-gray-300 bg-white hover:bg-gray-50'
                        )}
                    >
                        {page}
                    </button>
                )
            )}
            <button
                onClick={() => onPageChange(currentPage + 1)}
                disabled={currentPage === lastPage}
                aria-label="Next page"
                className="inline-flex h-9 w-9 items-center justify-center rounded-md border border-gray-300 bg-white text-sm hover:bg-gray-50 disabled:opacity-50"
            >
                <ChevronRight className="h-4 w-4" aria-hidden="true" />
            </button>
        </nav>
    );
}
