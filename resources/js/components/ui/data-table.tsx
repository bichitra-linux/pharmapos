import { useState } from 'react';
import { ArrowUpDown } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from './table';
import { Pagination } from './pagination';
import { SearchInput } from './search-input';
import { Spinner } from './spinner';
import { EmptyState } from './empty-state';
import { Inbox } from 'lucide-react';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export interface Column<T = any> {
    key: string;
    header: string;
    sortable?: boolean;
    render?: (item: T) => React.ReactNode;
    className?: string;
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
interface DataTableProps<T = any> {
    columns: Column<T>[];
    data: T[];
    loading?: boolean;
    searchable?: boolean;
    searchPlaceholder?: string;
    onSearch?: (query: string) => void;
    pagination?: {
        currentPage: number;
        lastPage: number;
        total: number;
        perPage?: number;
        onPageChange: (page: number) => void;
    };
    onRowClick?: (item: T) => void;
    emptyMessage?: string;
    className?: string;
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function DataTable<T extends Record<string, any>>({
    columns,
    data,
    loading,
    searchable,
    searchPlaceholder,
    onSearch,
    pagination,
    onRowClick,
    emptyMessage = 'No data found',
    className,
}: DataTableProps<T>) {
    const [sortKey, setSortKey] = useState<string | null>(null);
    const [sortDir, setSortDir] = useState<'asc' | 'desc'>('asc');

    const handleSort = (key: string) => {
        if (sortKey === key) {
            setSortDir(sortDir === 'asc' ? 'desc' : 'asc');
        } else {
            setSortKey(key);
            setSortDir('asc');
        }
    };

    const sortedData = sortKey
        ? [...data].sort((a, b) => {
              const aVal = a[sortKey];
              const bVal = b[sortKey];
              if (aVal == null) return 1;
              if (bVal == null) return -1;
              if (typeof aVal === 'string' && typeof bVal === 'string') {
                  return sortDir === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
              }
              return sortDir === 'asc' ? (aVal as number) - (bVal as number) : (bVal as number) - (aVal as number);
          })
        : data;

    return (
        <div className={cn('space-y-4', className)}>
            {searchable && onSearch && (
                <SearchInput
                    value=""
                    onChange={onSearch}
                    placeholder={searchPlaceholder}
                    className="max-w-sm"
                />
            )}
            <div className="rounded-lg border border-gray-200 bg-white">
                <Table>
                    <TableHeader>
                        <TableRow>
                            {columns.map((col) => (
                                <TableHead
                                    key={col.key}
                                    className={cn(col.className, col.sortable && 'cursor-pointer select-none')}
                                    onClick={() => col.sortable && handleSort(col.key)}
                                >
                                    <div className="flex items-center gap-1">
                                        {col.header}
                                        {col.sortable && (
                                            <ArrowUpDown className="h-4 w-4 text-gray-400" />
                                        )}
                                    </div>
                                </TableHead>
                            ))}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {loading ? (
                            <TableRow>
                                <TableCell colSpan={columns.length} className="h-32 text-center">
                                    <Spinner className="mx-auto" />
                                </TableCell>
                            </TableRow>
                        ) : sortedData.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={columns.length}>
                                    <EmptyState icon={Inbox} title={emptyMessage} />
                                </TableCell>
                            </TableRow>
                        ) : (
                            sortedData.map((item, idx) => (
                                <TableRow
                                    key={idx}
                                    onClick={() => onRowClick?.(item)}
                                    className={onRowClick ? 'cursor-pointer' : ''}
                                >
                                    {columns.map((col) => (
                                        <TableCell key={col.key} className={col.className}>
                                            {col.render
                                                ? col.render(item)
                                                : String(item[col.key] ?? '')}
                                        </TableCell>
                                    ))}
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
            </div>
            {pagination && (
                <div className="flex items-center justify-between">
                    <p className="text-sm text-gray-500">
                        Showing {pagination.total > 0 ? (pagination.currentPage - 1) * (pagination.perPage ?? 15) + 1 : 0} to{' '}
                        {Math.min(pagination.currentPage * (pagination.perPage ?? 15), pagination.total)} of {pagination.total}
                    </p>
                    <Pagination
                        currentPage={pagination.currentPage}
                        lastPage={pagination.lastPage}
                        onPageChange={pagination.onPageChange}
                    />
                </div>
            )}
        </div>
    );
}
