import { useState } from 'react';
import { Trash2, Minus, Plus, ShoppingCart, ChevronDown, ChevronUp } from 'lucide-react';
import { useCartStore } from '@/stores/cartStore';
import { formatCurrency } from '@/lib/utils';

export function Cart() {
    const items = useCartStore((s) => s.items);
    const removeItem = useCartStore((s) => s.removeItem);
    const updateQuantity = useCartStore((s) => s.updateQuantity);
    const updateDiscount = useCartStore((s) => s.updateDiscount);
    const setSellMode = useCartStore((s) => s.setSellMode);
    const setLineNote = useCartStore((s) => s.setLineNote);
    const getEffectivePrice = useCartStore((s) => s.getEffectivePrice);
    const [expandedId, setExpandedId] = useState<string | null>(null);

    if (items.length === 0) {
        return (
            <div className="flex h-full flex-col items-center justify-center gap-3 p-8 text-center">
                <ShoppingCart className="h-12 w-12 text-text-muted" aria-hidden="true" />
                <p className="text-sm text-text-muted">Cart is empty. Search for medicines to add.</p>
            </div>
        );
    }

    return (
        <div className="divide-y divide-border">
            {items.map((item) => {
                const key = `${item.medicine_id}-${item.batch_id}`;
                const isExpanded = expandedId === key;
                const effectivePrice = getEffectivePrice(item);
                const lineTotal = effectivePrice * item.quantity;
                const discountAmt = (lineTotal * (item.discount_percent ?? 0)) / 100;
                const packSize = item.units_per_pack ?? 1;
                const canPieceSell = item.allow_piece_selling && packSize > 1;
                const isPiece = item.sell_mode === 'piece';
                const isRx = ['h', 'h1', 'x'].includes(item.name.toLowerCase().slice(0, 2));

                return (
                    <div key={key} className="p-2">
                        {/* Compact row (always visible) */}
                        <div className="flex items-start gap-2 cursor-pointer" onClick={() => setExpandedId(isExpanded ? null : key)}>
                            {/* Batch expiry dot */}
                            <div className="mt-1.5 flex-shrink-0">
                                <div className="h-2 w-2 rounded-full bg-success-400" title="In stock" />
                            </div>
                            <div className="flex-1 min-w-0">
                                <div className="flex items-start gap-1">
                                    <p className="text-sm font-medium leading-tight">{item.name}</p>
                                    {isRx && <span className="text-[9px] px-1 rounded-full bg-danger-100 text-danger-700 font-semibold shrink-0">Rx</span>}
                                </div>
                                {item.secondary_name && <p className="text-[11px] text-text-muted leading-tight">{item.secondary_name}</p>}
                                <p className="text-[11px] text-text-muted">Batch: {item.batch_number} &bull; {isPiece ? formatCurrency(effectivePrice) + '/pc' : formatCurrency(item.unit_price) + '/' + (item.piece_unit_label || 'strip')}</p>
                            </div>
                            <div className="text-right flex-shrink-0">
                                <p className="text-sm font-medium">{formatCurrency(lineTotal - discountAmt)}</p>
                                <p className="text-[10px] text-text-muted">{Math.round(item.quantity)}{isPiece ? ' pcs' : ' ' + (item.piece_unit_label || '')}</p>
                            </div>
                            <button
                                onClick={(e) => { e.stopPropagation(); removeItem(item.medicine_id, item.batch_id); }}
                                className="rounded p-1 text-text-muted hover:text-danger-600 flex-shrink-0"
                                aria-label="Remove"
                            >
                                <Trash2 className="h-3.5 w-3.5" />
                            </button>
                            <button className="text-text-muted flex-shrink-0 p-1">
                                {isExpanded ? <ChevronUp className="h-3.5 w-3.5" /> : <ChevronDown className="h-3.5 w-3.5" />}
                            </button>
                        </div>

                        {/* Expanded details */}
                        {isExpanded && (
                            <div className="mt-2 ml-4 space-y-2 border-t border-dashed border-border pt-2">
                                {/* Pack/piece toggle */}
                                {canPieceSell && (
                                    <div className="flex rounded border border-border overflow-hidden text-xs w-fit">
                                        <button onClick={() => setSellMode(item.medicine_id, item.batch_id, 'pack')} className={`px-2 py-1 ${!isPiece ? 'bg-primary-600 text-white' : 'bg-surface text-text'}`}>{item.piece_unit_label || 'Strip'}</button>
                                        <button onClick={() => setSellMode(item.medicine_id, item.batch_id, 'piece')} className={`px-2 py-1 ${isPiece ? 'bg-primary-600 text-white' : 'bg-surface text-text'}`}>{item.piece_unit_label || 'Piece'}</button>
                                    </div>
                                )}

                                {/* Quantity */}
                                <div className="flex items-center gap-1">
                                    <div className="flex items-center rounded border border-border">
                                        <button onClick={() => updateQuantity(item.medicine_id, item.batch_id, item.quantity - 1)} className="min-h-[36px] min-w-[36px] px-1 hover:bg-surface-muted text-xs"><Minus className="h-3 w-3" /></button>
                                        <input type="number" value={item.quantity}
                                            onChange={(e) => updateQuantity(item.medicine_id, item.batch_id, parseInt(e.target.value) || 1)}
                                            aria-label="Quantity" inputMode="decimal"
                                            className="w-16 border-x border-border py-1.5 text-center text-xs" />
                                        <button onClick={() => updateQuantity(item.medicine_id, item.batch_id, item.quantity + 1)} className="min-h-[36px] min-w-[36px] px-1 hover:bg-surface-muted text-xs"><Plus className="h-3 w-3" /></button>
                                    </div>
                                    <div className="flex gap-1">
                                        <button onClick={() => updateQuantity(item.medicine_id, item.batch_id, item.quantity + 5)} className="min-h-[36px] min-w-[28px] rounded border border-border text-[10px] hover:bg-surface-muted">+5</button>
                                        <button onClick={() => updateQuantity(item.medicine_id, item.batch_id, item.quantity + 10)} className="min-h-[36px] min-w-[28px] rounded border border-border text-[10px] hover:bg-surface-muted">+10</button>
                                    </div>
                                    {canPieceSell && isPiece && (
                                        <div className="flex gap-1">
                                            <button onClick={() => updateQuantity(item.medicine_id, item.batch_id, item.quantity + Math.max(1, Math.floor(packSize / 2)))} className="min-h-[36px] min-w-[28px] rounded border border-border text-[10px] hover:bg-surface-muted">+{packSize}</button>
                                        </div>
                                    )}
                                </div>

                                {/* Discount */}
                                <div className="flex items-center gap-2">
                                    <input type="number" value={item.discount_percent}
                                        onChange={(e) => updateDiscount(item.medicine_id, item.batch_id, parseFloat(e.target.value) || 0)}
                                        placeholder="Disc %" aria-label="Discount"
                                        className="w-20 rounded border border-border px-2 py-1.5 text-xs" min="0" max="100" />
                                    <span className="text-xs text-text-muted">({formatCurrency(discountAmt)} off)</span>
                                </div>

                                {/* Line note */}
                                <input type="text" value={item.line_note}
                                    onChange={(e) => setLineNote(item.medicine_id, item.batch_id, e.target.value)}
                                    placeholder="Note (e.g., Take after food)"
                                    className="w-full rounded border border-border px-2 py-1.5 text-xs text-text-muted" />

                                {/* FIFO warning */}
                                {(item as any).fifo_warning && (
                                    <p className="text-xs text-warning-600">{(item as any).fifo_warning}</p>
                                )}
                            </div>
                        )}
                    </div>
                );
            })}
        </div>
    );
}
