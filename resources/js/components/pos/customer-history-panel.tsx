import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { customersService } from '@/services/customers';
import { useCartStore } from '@/stores/cartStore';
import { formatDate, formatCurrency } from '@/lib/utils';
import { ChevronDown, ChevronUp, ClipboardList } from 'lucide-react';

export function CustomerHistoryPanel() {
    const customerId = useCartStore((s) => s.customer_id);
    const [open, setOpen] = useState(false);

    const { data: history } = useQuery({
        queryKey: ['customers', 'history', customerId],
        queryFn: () => customersService.getHistory(customerId!),
        enabled: !!customerId,
        select: (res) => res.data,
        staleTime: 60_000,
    });

    if (!customerId) return null;

    const prescriptions = history?.prescriptions ?? [];

    return (
        <div className="border-b border-border">
            <button
                onClick={() => setOpen(!open)}
                className="flex w-full items-center justify-between px-3 py-1.5 text-xs text-text-muted hover:bg-surface-muted"
            >
                <span className="flex items-center gap-1.5">
                    <ClipboardList className="h-3 w-3" />
                    {prescriptions.length > 0
                        ? `${prescriptions.length} past prescription${prescriptions.length > 1 ? 's' : ''}`
                        : 'No past prescriptions'}
                </span>
                {open ? <ChevronUp className="h-3 w-3" /> : <ChevronDown className="h-3 w-3" />}
            </button>
            {open && prescriptions.length > 0 && (
                <div className="space-y-1 px-3 pb-2">
                    {prescriptions.slice(0, 5).map((rx: any) => (
                        <div key={rx.id} className="rounded border border-border bg-surface-muted p-2 text-[11px]">
                            <div className="flex items-center justify-between">
                                <span className="font-medium">{rx.prescription_number}</span>
                                <span className="text-text-muted">{formatDate(rx.created_at, 'dd/MM/yyyy')}</span>
                            </div>
                            {rx.doctor_name && (
                                <p className="text-text-muted mt-0.5">Dr. {rx.doctor_name}{rx.hospital_name ? ` — ${rx.hospital_name}` : ''}</p>
                            )}
                            <span className={`mt-0.5 inline-block rounded-full px-1.5 py-0.5 text-[10px] font-medium ${
                                rx.status === 'dispensed' ? 'bg-success-100 text-success-700' :
                                rx.status === 'partial' ? 'bg-warning-100 text-warning-700' :
                                'bg-secondary-100 text-text-muted'
                            }`}>{rx.status}</span>
                        </div>
                    ))}
                </div>
            )}
            {open && prescriptions.length === 0 && (
                <p className="px-3 pb-2 text-[11px] text-text-muted">No prescription history for this customer.</p>
            )}
        </div>
    );
}
