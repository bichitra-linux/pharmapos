import { Trash2, Minus, Plus, ShoppingCart } from 'lucide-react';
import { useCartStore } from '@/stores/cartStore';
import { formatCurrency } from '@/lib/utils';

export function Cart() {
    const { items, removeItem, updateQuantity, updateDiscount } = useCartStore();

    if (items.length === 0) {
        return (
            <div className="flex h-full flex-col items-center justify-center gap-3 p-8 text-center">
                <ShoppingCart className="h-12 w-12 text-gray-300" aria-hidden="true" />
                <p className="text-sm text-gray-500">Cart is empty. Search for medicines to add.</p>
            </div>
        );
    }

    return (
        <div className="divide-y divide-gray-100">
            {items.map((item) => {
                const lineTotal = item.unit_price * item.quantity;
                const discountAmt = (lineTotal * item.discount_percent) / 100;
                return (
                    <div key={`${item.medicine_id}-${item.batch_id}`} className="p-3">
                        <div className="flex items-start justify-between">
                            <div className="flex-1">
                                <p className="text-sm font-medium">{item.name}</p>
                                <p className="text-xs text-gray-500">
                                    Batch: {item.batch_number} • {formatCurrency(item.unit_price)}/unit
                                </p>
                            </div>
                            <button
                                onClick={() => removeItem(item.medicine_id, item.batch_id)}
                                aria-label={`Remove ${item.name} from cart`}
                                className="rounded p-2 text-gray-400 hover:text-danger-600"
                            >
                                <Trash2 className="h-4 w-4" aria-hidden="true" />
                            </button>
                        </div>

                        <div className="mt-2 flex items-center gap-2">
                            <div className="flex items-center rounded border border-gray-200">
                                <button
                                    onClick={() =>
                                        updateQuantity(item.medicine_id, item.batch_id, item.quantity - 1)
                                    }
                                    aria-label="Decrease quantity"
                                    className="min-h-[40px] min-w-[40px] px-2 hover:bg-gray-50"
                                >
                                    <Minus className="h-3 w-3" aria-hidden="true" />
                                </button>
                                <input
                                    type="number"
                                    value={item.quantity}
                                    onChange={(e) =>
                                        updateQuantity(
                                            item.medicine_id,
                                            item.batch_id,
                                            parseInt(e.target.value) || 1
                                        )
                                    }
                                    aria-label="Quantity"
                                    className="w-12 border-x border-gray-200 py-2 text-center text-sm"
                                />
                                <button
                                    onClick={() =>
                                        updateQuantity(item.medicine_id, item.batch_id, item.quantity + 1)
                                    }
                                    aria-label="Increase quantity"
                                    className="min-h-[40px] min-w-[40px] px-2 hover:bg-gray-50"
                                >
                                    <Plus className="h-3 w-3" aria-hidden="true" />
                                </button>
                            </div>

                            <input
                                type="number"
                                value={item.discount_percent}
                                onChange={(e) =>
                                    updateDiscount(
                                        item.medicine_id,
                                        item.batch_id,
                                        parseFloat(e.target.value) || 0
                                    )
                                }
                                placeholder="Disc %"
                                aria-label="Discount percentage"
                                className="w-16 rounded border border-gray-200 px-2 py-2 text-sm"
                                min="0"
                                max="100"
                            />

                            <div className="ml-auto text-right">
                                <p className="text-sm font-medium">
                                    {formatCurrency(lineTotal - discountAmt)}
                                </p>
                            </div>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
