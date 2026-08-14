import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import { VAT_RATE } from '@/lib/constants';

export interface CartItem {
    medicine_id: number;
    batch_id: number;
    name: string;
    secondary_name: string;
    batch_number: string;
    unit_price: number;
    quantity: number;
    max_quantity: number;
    discount_percent?: number;
    discount_amount?: number;
    tax_rate: number;
    sell_mode?: 'pack' | 'piece';
    units_per_pack: number;
    piece_unit_label: string;
    allow_piece_selling: boolean;
    line_note?: string;
}

interface CartState {
    items: CartItem[];
    customer_id: number | null;
    prescription_id: number | null;
    discount_amount: number;
    sale_type: 'walk_in' | 'online' | 'delivery';
    addItem: (item: CartItem) => void;
    removeItem: (medicineId: number, batchId: number) => void;
    updateQuantity: (medicineId: number, batchId: number, quantity: number) => void;
    updateDiscount: (medicineId: number, batchId: number, discountPercent: number) => void;
    setSellMode: (medicineId: number, batchId: number, mode: 'pack' | 'piece') => void;
    setLineNote: (medicineId: number, batchId: number, note: string) => void;
    setCustomer: (id: number | null) => void;
    setPrescription: (id: number | null) => void;
    setDiscount: (amount: number) => void;
    setSaleType: (type: 'walk_in' | 'online' | 'delivery') => void;
    clear: () => void;
    getSubtotal: () => number;
    getDiscountTotal: () => number;
    getTaxTotal: () => number;
    getTotal: () => number;
    getItemCount: () => number;
    getEffectivePrice: (item: CartItem) => number;
    getStockDisplay: (item: CartItem) => string;
}

export const useCartStore = create<CartState>()(
    persist(
        (set, get) => ({
            items: [],
            customer_id: null,
            prescription_id: null,
            discount_amount: 0,
            sale_type: 'walk_in',

            addItem: (item) =>
                set((state) => {
                    const existing = state.items.find(
                        (i) => i.medicine_id === item.medicine_id && i.batch_id === item.batch_id
                    );
                    const packSize = item.units_per_pack ?? 1;
                    if (existing) {
                        const effectiveMax = (existing.sell_mode ?? 'pack') === 'piece' && existing.units_per_pack > 1
                            ? existing.max_quantity * existing.units_per_pack
                            : existing.max_quantity;
                        const newQty = Math.min(existing.quantity + item.quantity, effectiveMax);
                        return {
                            items: state.items.map((i) =>
                                i.medicine_id === item.medicine_id && i.batch_id === item.batch_id
                                    ? { ...i, quantity: newQty }
                                    : i
                            ),
                        };
                    }
                    return { items: [...state.items, {
                        ...item,
                        discount_percent: item.discount_percent ?? 0,
                        discount_amount: item.discount_amount ?? 0,
                        sell_mode: item.sell_mode ?? 'pack',
                        line_note: item.line_note ?? '',
                    }] };
                }),

            removeItem: (medicineId, batchId) =>
                set((state) => ({
                    items: state.items.filter(
                        (i) => !(i.medicine_id === medicineId && i.batch_id === batchId)
                    ),
                })),

            updateQuantity: (medicineId, batchId, quantity) =>
                set((state) => ({
                    items: state.items.map((i) => {
                        if (i.medicine_id !== medicineId || i.batch_id !== batchId) return i;
                        const effectiveMax = (i.sell_mode ?? 'pack') === 'piece' && i.units_per_pack > 1
                            ? i.max_quantity * i.units_per_pack
                            : i.max_quantity;
                        return { ...i, quantity: Math.min(Math.max(1, quantity), effectiveMax) };
                    }),
                })),

            updateDiscount: (medicineId, batchId, discountPercent) =>
                set((state) => ({
                    items: state.items.map((i) =>
                        i.medicine_id === medicineId && i.batch_id === batchId
                            ? { ...i, discount_percent: Math.min(100, Math.max(0, discountPercent)) }
                            : i
                    ),
                })),

            setSellMode: (medicineId, batchId, mode) =>
                set((state) => ({
                    items: state.items.map((i) => {
                        if (i.medicine_id !== medicineId || i.batch_id !== batchId) return i;
                        if (mode === 'piece' && i.units_per_pack > 1) {
                            return {
                                ...i,
                                sell_mode: 'piece',
                                quantity: i.quantity * i.units_per_pack,
                            };
                        }
                        return {
                            ...i,
                            sell_mode: 'pack',
                            quantity: Math.max(1, Math.floor(i.quantity / Math.max(1, i.units_per_pack))),
                        };
                    }),
                })),

            setLineNote: (medicineId, batchId, note) =>
                set((state) => ({
                    items: state.items.map((i) =>
                        i.medicine_id === medicineId && i.batch_id === batchId
                            ? { ...i, line_note: note }
                            : i
                    ),
                })),

            setCustomer: (id) => set({ customer_id: id }),
            setPrescription: (id) => set({ prescription_id: id }),
            setDiscount: (amount) => set({ discount_amount: Math.max(0, amount) }),
            setSaleType: (type) => set({ sale_type: type }),

            clear: () =>
                set({ items: [], customer_id: null, prescription_id: null, discount_amount: 0, sale_type: 'walk_in' }),

            getSubtotal: () => {
                const { items } = get();
                return items.reduce((sum, item) => {
                    const effectivePrice = item.sell_mode === 'piece' && item.units_per_pack > 1
                        ? item.unit_price / item.units_per_pack
                        : item.unit_price;
                    return sum + effectivePrice * item.quantity;
                }, 0);
            },

            getDiscountTotal: () => {
                const { items, discount_amount } = get();
                const itemDiscounts = items.reduce((sum, item) => {
                    const effectivePrice = item.sell_mode === 'piece' && item.units_per_pack > 1
                        ? item.unit_price / item.units_per_pack
                        : item.unit_price;
                    const itemTotal = effectivePrice * item.quantity;
                    return sum + (itemTotal * (item.discount_percent ?? 0)) / 100;
                }, 0);
                return itemDiscounts + discount_amount;
            },

            getTaxTotal: () => {
                const { items } = get();
                return items.reduce((sum, item) => {
                    const effectivePrice = item.sell_mode === 'piece' && item.units_per_pack > 1
                        ? item.unit_price / item.units_per_pack
                        : item.unit_price;
                    const itemTotal = effectivePrice * item.quantity;
                    const itemDiscount = (itemTotal * (item.discount_percent ?? 0)) / 100;
                    const taxable = itemTotal - itemDiscount;
                    return sum + (taxable * (item.tax_rate ?? VAT_RATE)) / 100;
                }, 0);
            },

            getTotal: () => {
                const store = get();
                return store.getSubtotal() - store.getDiscountTotal() + store.getTaxTotal();
            },

            getItemCount: () => {
                const { items } = get();
                return items.reduce((sum, item) => sum + item.quantity, 0);
            },

            getEffectivePrice: (item) => {
                return item.sell_mode === 'piece' && item.units_per_pack > 1
                    ? item.unit_price / item.units_per_pack
                    : item.unit_price;
            },

            getStockDisplay: (item) => {
                const packSize = item.units_per_pack ?? 1;
                if (packSize > 1 && item.allow_piece_selling) {
                    return `${item.max_quantity} ${item.piece_unit_label || 'strip'}s (${item.max_quantity * packSize} pcs)`;
                }
                return `${item.max_quantity} in stock`;
            },
        }),
        {
            name: 'pharmapos-cart',
            partialize: (state) => ({
                items: state.items,
                customer_id: state.customer_id,
                prescription_id: state.prescription_id,
                discount_amount: state.discount_amount,
            }),
        }
    )
);
