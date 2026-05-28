import { useQuery } from '@tanstack/react-query';
import { medicinesService } from '@/services/medicines';
import { useCartStore } from '@/stores/cartStore';
import { formatCurrency } from '@/lib/utils';
import { Spinner } from '@/components/ui/spinner';

export function ProductGrid() {
    const addItem = useCartStore((s) => s.addItem);

    const { data: medicines, isLoading } = useQuery({
        queryKey: ['medicines', 'grid'],
        queryFn: () => medicinesService.list({ per_page: 24 }),
        select: (res) => res.data,
    });

    if (isLoading) {
        return (
            <div className="flex h-64 items-center justify-center">
                <Spinner />
            </div>
        );
    }

    return (
        <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
            {medicines?.map((med) => {
                const batch = med.batches?.[0];
                const inStock = batch && batch.quantity_in_stock > 0;
                return (
                    <button
                        key={med.id}
                        onClick={() => {
                            if (!inStock) return;
                            addItem({
                                medicine_id: med.id,
                                batch_id: batch.id,
                                name: med.brand_name,
                                batch_number: batch.batch_number,
                                unit_price: batch.selling_price_per_unit,
                                quantity: 1,
                                max_quantity: batch.quantity_in_stock,
                                tax_rate: 13,
                            });
                        }}
                        disabled={!inStock}
                        className="flex flex-col rounded-lg border border-gray-200 bg-white p-3 text-left transition-shadow hover:shadow-md disabled:opacity-50"
                    >
                        <p className="truncate text-sm font-medium">{med.brand_name}</p>
                        <p className="truncate text-xs text-gray-500">{med.generic_name}</p>
                        <p className="mt-1 text-xs text-gray-400">
                            {med.dosage_form} • {med.strength}
                        </p>
                        <div className="mt-auto flex items-center justify-between pt-2">
                            <span className="text-sm font-bold text-primary-600">
                                {formatCurrency(batch?.selling_price_per_unit || 0)}
                            </span>
                            <span
                                className={`text-xs ${
                                    inStock ? 'text-success-600' : 'text-danger-600'
                                }`}
                            >
                                {inStock ? `${batch?.quantity_in_stock}` : 'Out'}
                            </span>
                        </div>
                    </button>
                );
            })}
        </div>
    );
}
