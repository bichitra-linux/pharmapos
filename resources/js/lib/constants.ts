export const API_BASE_URL = '/api';

export const DOSAGE_FORMS = [
    { value: 'tablet', label: 'Tablet' },
    { value: 'capsule', label: 'Capsule' },
    { value: 'syrup', label: 'Syrup' },
    { value: 'suspension', label: 'Suspension' },
    { value: 'injection', label: 'Injection' },
    { value: 'drops', label: 'Drops' },
    { value: 'cream', label: 'Cream' },
    { value: 'ointment', label: 'Ointment' },
    { value: 'gel', label: 'Gel' },
    { value: 'lotion', label: 'Lotion' },
    { value: 'powder', label: 'Powder' },
    { value: 'inhaler', label: 'Inhaler' },
    { value: 'spray', label: 'Spray' },
    { value: 'suppository', label: 'Suppository' },
    { value: 'patch', label: 'Patch' },
    { value: 'solution', label: 'Solution' },
    { value: 'other', label: 'Other' },
] as const;

export const UNIT_TYPES = [
    { value: 'strip', label: 'Strip (blister pack)' },
    { value: 'bottle', label: 'Bottle' },
    { value: 'tube', label: 'Tube' },
    { value: 'piece', label: 'Piece (individual)' },
    { value: 'box', label: 'Box' },
    { value: 'vial', label: 'Vial (injectable)' },
    { value: 'sachet', label: 'Sachet' },
    { value: 'roll', label: 'Roll' },
] as const;

export const SCHEDULE_TYPES = [
    { value: 'h', label: 'H — Prescription Required', description: 'Schedule H drugs can only be sold with a valid prescription from a registered medical practitioner.' },
    { value: 'h1', label: 'H1 — Strict Prescription', description: 'Schedule H1 drugs require prescription with patient details recorded. Mandatory for antibiotics and certain controlled substances.' },
    { value: 'x', label: 'X — Narcotic / Psychotropic', description: 'Schedule X drugs are narcotics and psychotropic substances. Requires prescription, maintains narcotics register, and is subject to strict government audit.' },
    { value: 'g', label: 'G — General (OTC)', description: 'General / Over-the-counter drugs that can be sold without prescription.' },
    { value: 'otc', label: 'OTC — Over the Counter', description: 'Over-the-counter medicines freely available without prescription.' },
] as const;

export const PAYMENT_METHODS = [
    { id: 1, name: 'Cash', type: 'cash' as const },
    { id: 2, name: 'Card', type: 'card' as const },
    { id: 3, name: 'eSewa', type: 'digital_wallet' as const },
    { id: 4, name: 'Khalti', type: 'digital_wallet' as const },
    { id: 5, name: 'Fonepay', type: 'digital_wallet' as const },
    { id: 6, name: 'ConnectIPS', type: 'bank_transfer' as const },
    { id: 7, name: 'Credit', type: 'credit' as const },
] as const;

export const VAT_RATE = 13;

export const ROLES = ['admin', 'manager', 'cashier', 'pharmacist'] as const;

export const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as const;

export const ADJUSTMENT_TYPES = [
    { value: 'damage', label: 'Damaged Stock' },
    { value: 'expiry', label: 'Expired Stock' },
    { value: 'count_adjustment', label: 'Physical Count Adjustment' },
    { value: 'return', label: 'Customer Return' },
    { value: 'other', label: 'Other' },
] as const;
