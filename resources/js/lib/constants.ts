export const API_BASE_URL = '/api';

export const DOSAGE_FORMS = [
    'Tablet',
    'Capsule',
    'Syrup',
    'Suspension',
    'Injection',
    'Drops',
    'Cream',
    'Ointment',
    'Gel',
    'Lotion',
    'Powder',
    'Inhaler',
    'Spray',
    'Suppository',
    'Patch',
    'Solution',
    'Eye Drops',
    'Ear Drops',
    'Nasal Drops',
    'IV Fluid',
] as const;

export const UNIT_TYPES = [
    'Tablets',
    'Capsules',
    'Bottles',
    'Tubes',
    'Vials',
    'Sachets',
    'Strips',
    'Boxes',
    'Pieces',
    'ml',
    'gm',
    'kg',
] as const;

export const SCHEDULE_TYPES = [
    'H',
    'H1',
    'X',
    'NRX',
    'G',
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
    'addition',
    'subtraction',
    'damage',
    'expired',
    'correction',
] as const;
