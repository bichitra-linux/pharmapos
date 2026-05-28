import { create } from 'zustand';
import { VAT_RATE } from '@/lib/constants';

export interface CartItem {
    medicine_id: number;
    batch_id: number;
    name: string;
    batch_number: string;
    unit_price: number;
    quantity: number;
    max_quantity: number;
    discount_percent: number;
    discount_amount: number;
    tax_rate: number;
}

interface CartState {
    items: CartItem[];
    customer_id: number | null;
    prescription_id: number | null;
    discount_amount: number;
    addItem: (item: Omit<CartItem, 'discount_percent' | 'discount_amount'>) => void;
    removeItem: (medicineId: number, batchId: number) => void;
    updateQuantity: (medicineId: number, batchId: number, quantity: number) => void;
    updateDiscount: (medicineId: number, batchId: number, discountPercent: number) => void;
    setCustomer: (id: number | null) => void;
    setPrescription: (id: number | null) => void;
    setDiscount: (amount: number) => void;
    clear: () => void;
    getSubtotal: () => number;
    getDiscountTotal: () => number;
    getTaxTotal: () => number;
    getTotal: () => number;
    getItemCount: () => number;
}

export const useCartStore = create<CartState>()((set, get) => ({
    items: [],
    customer_id: null,
    prescription_id: null,
    discount_amount: 0,

    addItem: (item) =>
        set((state) => {
            const existing = state.items.find(
                (i) => i.medicine_id === item.medicine_id && i.batch_id === item.batch_id
            );
            if (existing) {
                const newQty = Math.min(existing.quantity + item.quantity, existing.max_quantity);
                return {
                    items: state.items.map((i) =>
                        i.medicine_id === item.medicine_id && i.batch_id === item.batch_id
                            ? { ...i, quantity: newQty }
                            : i
                    ),
                };
            }
            return { items: [...state.items, { ...item, discount_percent: 0, discount_amount: 0 }] };
        }),

    removeItem: (medicineId, batchId) =>
        set((state) => ({
            items: state.items.filter(
                (i) => !(i.medicine_id === medicineId && i.batch_id === batchId)
            ),
        })),

    updateQuantity: (medicineId, batchId, quantity) =>
        set((state) => ({
            items: state.items.map((i) =>
                i.medicine_id === medicineId && i.batch_id === batchId
                    ? { ...i, quantity: Math.min(Math.max(1, quantity), i.max_quantity) }
                    : i
            ),
        })),

    updateDiscount: (medicineId, batchId, discountPercent) =>
        set((state) => ({
            items: state.items.map((i) =>
                i.medicine_id === medicineId && i.batch_id === batchId
                    ? { ...i, discount_percent: Math.min(100, Math.max(0, discountPercent)) }
                    : i
            ),
        })),

    setCustomer: (id) => set({ customer_id: id }),
    setPrescription: (id) => set({ prescription_id: id }),
    setDiscount: (amount) => set({ discount_amount: Math.max(0, amount) }),

    clear: () =>
        set({ items: [], customer_id: null, prescription_id: null, discount_amount: 0 }),

    getSubtotal: () => {
        const { items } = get();
        return items.reduce((sum, item) => sum + item.unit_price * item.quantity, 0);
    },

    getDiscountTotal: () => {
        const { items, discount_amount } = get();
        const itemDiscounts = items.reduce((sum, item) => {
            const itemTotal = item.unit_price * item.quantity;
            return sum + (itemTotal * item.discount_percent) / 100;
        }, 0);
        return itemDiscounts + discount_amount;
    },

    getTaxTotal: () => {
        const { items } = get();
        return items.reduce((sum, item) => {
            const itemTotal = item.unit_price * item.quantity;
            const itemDiscount = (itemTotal * item.discount_percent) / 100;
            const taxable = itemTotal - itemDiscount;
            return sum + (taxable * (item.tax_rate || VAT_RATE)) / 100;
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
}));
