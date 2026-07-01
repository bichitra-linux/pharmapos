import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';
import { format, parseISO } from 'date-fns';
import type { PaginatedMeta, PaginatedLinks } from '@/types';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function formatCurrency(amount: number | null | undefined): string {
    if (amount == null) return '—';
    const absAmount = Math.abs(amount);
    const formatted = absAmount.toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
    return amount < 0 ? `-रू ${formatted}` : `रू ${formatted}`;
}

export function formatDate(date: string | Date | null | undefined, pattern: string = 'dd MMM yyyy'): string {
    if (!date) return '—';
    try {
        const d = typeof date === 'string' ? parseISO(date) : date;
        if (isNaN(d.getTime())) return '—';
        return format(d, pattern);
    } catch {
        return '—';
    }
}

export function formatDateTime(date: string | Date | null | undefined): string {
    return formatDate(date, 'dd MMM yyyy HH:mm');
}

export function formatNepaliDate(date: string | Date | null | undefined): string {
    if (!date) return '';
    try {
        const d = typeof date === 'string' ? parseISO(date) : date;
        if (isNaN(d.getTime())) return '';
        const year = d.getFullYear();
        const month = d.getMonth();
        const day = d.getDate();
        const bsYear = year + 56;
        const bsMonth = (month + 9) % 12;
        const bsMonthNames = ['Baisakh', 'Jestha', 'Ashadh', 'Shrawan', 'Bhadra', 'Ashwin', 'Kartik', 'Mangsir', 'Poush', 'Magh', 'Falgun', 'Chaitra'];
        return `${day} ${bsMonthNames[bsMonth]} ${bsYear}`;
    } catch {
        return '';
    }
}

export function formatNumber(num: number): string {
    if (num >= 10000000) {
        return `${(num / 10000000).toFixed(2)} Cr`;
    }
    if (num >= 100000) {
        return `${(num / 100000).toFixed(2)} L`;
    }
    return num.toLocaleString('en-IN');
}

export function truncate(str: string, length: number): string {
    if (str.length <= length) return str;
    return str.slice(0, length) + '...';
}

export function getInitials(name: string): string {
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
}

interface LaravelPaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    first_page_url?: string | null;
    last_page_url?: string | null;
    prev_page_url?: string | null;
    next_page_url?: string | null;
}

interface NormalizedPaginatedResponse<T> {
    data: T[];
    meta: PaginatedMeta;
    links: PaginatedLinks;
}

export function extractPaginatedData<T>(response: unknown): NormalizedPaginatedResponse<T> {
    const res = response as Record<string, unknown>;

    if (res && typeof res === 'object' && 'data' in res) {
        const inner = res.data;

        if (inner && typeof inner === 'object' && 'data' in inner && Array.isArray((inner as Record<string, unknown>).data)) {
            const paginated = inner as LaravelPaginatedData<T>;
            return {
                data: paginated.data,
                meta: {
                    current_page: paginated.current_page,
                    last_page: paginated.last_page,
                    per_page: paginated.per_page,
                    total: paginated.total,
                    from: paginated.from ?? null,
                    to: paginated.to ?? null,
                },
                links: {
                    first: paginated.first_page_url ?? null,
                    last: paginated.last_page_url ?? null,
                    prev: paginated.prev_page_url ?? null,
                    next: paginated.next_page_url ?? null,
                },
            };
        }

        if (Array.isArray(inner)) {
            const normalized = res as unknown as { data: T[]; meta?: PaginatedMeta; links?: PaginatedLinks };
            return {
                data: inner as T[],
                meta: normalized.meta ?? { current_page: 1, last_page: 1, per_page: 25, total: inner.length, from: 1, to: inner.length },
                links: normalized.links ?? { first: null, last: null, prev: null, next: null },
            };
        }
    }

    return {
        data: [],
        meta: { current_page: 1, last_page: 1, per_page: 25, total: 0, from: null, to: null },
        links: { first: null, last: null, prev: null, next: null },
    };
}
