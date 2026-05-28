export interface User {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    role: 'admin' | 'manager' | 'cashier' | 'pharmacist';
    company_id: number;
    outlet_id: number | null;
    is_active: boolean;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    company?: Company;
    outlet?: Outlet;
}

export interface Company {
    id: number;
    name: string;
    email: string;
    phone: string;
    address: string;
    city: string;
    state: string;
    pan_number: string;
    vat_number: string;
    logo: string | null;
    subscription_plan_id: number | null;
    subscription_expires_at: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface Outlet {
    id: number;
    company_id: number;
    name: string;
    address: string;
    phone: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface Medicine {
    id: number;
    company_id: number;
    generic_name: string;
    brand_name: string;
    manufacturer_id: number | null;
    medicine_category_id: number | null;
    salt_composition_id: number | null;
    dosage_form: string;
    strength: string | null;
    unit_type: string;
    units_per_pack: number;
    schedule_type: string;
    hsn_code: string | null;
    is_prescription_required: boolean;
    is_active: boolean;
    barcode: string | null;
    image: string | null;
    description: string | null;
    storage_conditions: string | null;
    is_temperature_sensitive: boolean;
    created_at: string;
    updated_at: string;
    manufacturer?: Manufacturer;
    medicine_category?: MedicineCategory;
    salt_composition?: SaltComposition;
    batches?: MedicineBatch[];
    substitutes?: Substitute[];
}

export interface MedicineBatch {
    id: number;
    medicine_id: number;
    company_id: number;
    outlet_id: number;
    batch_number: string;
    manufacturing_date: string | null;
    expiry_date: string;
    quantity_in_stock: number;
    purchase_price_per_unit: number;
    mrp_per_unit: number;
    selling_price_per_unit: number;
    barcode: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    medicine?: Medicine;
}

export interface MedicineCategory {
    id: number;
    company_id: number;
    name: string;
    description: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface Manufacturer {
    id: number;
    company_id: number;
    name: string;
    country: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface SaltComposition {
    id: number;
    name: string;
    description: string | null;
    created_at: string;
    updated_at: string;
}

export interface Substitute {
    id: number;
    medicine_id: number;
    substitute_medicine_id: number;
    medicine?: Medicine;
    substitute_medicine?: Medicine;
    created_at: string;
    updated_at: string;
}

export interface Customer {
    id: number;
    company_id: number;
    name: string;
    phone: string;
    email: string | null;
    address: string | null;
    date_of_birth: string | null;
    gender: 'male' | 'female' | 'other' | null;
    blood_group: string | null;
    allergies: string | null;
    credit_limit: number;
    outstanding_balance: number;
    total_purchases: number;
    loyalty_points: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface Supplier {
    id: number;
    company_id: number;
    name: string;
    contact_person: string | null;
    phone: string;
    email: string | null;
    address: string;
    city: string;
    state: string;
    pan_number: string | null;
    bank_details: string | null;
    credit_limit: number;
    outstanding_balance: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface Sale {
    id: number;
    company_id: number;
    outlet_id: number;
    user_id: number;
    customer_id: number | null;
    invoice_number: string;
    prescription_id: number | null;
    subtotal: number;
    discount_amount: number;
    discount_percent: number;
    tax_amount: number;
    total_amount: number;
    paid_amount: number;
    due_amount: number;
    change_amount: number;
    payment_status: 'paid' | 'partial' | 'unpaid';
    status: 'completed' | 'held' | 'cancelled' | 'returned';
    notes: string | null;
    created_at: string;
    updated_at: string;
    customer?: Customer;
    user?: User;
    items?: SaleItem[];
    payments?: SalePayment[];
}

export interface SaleItem {
    id: number;
    sale_id: number;
    medicine_id: number;
    batch_id: number;
    quantity: number;
    unit_price: number;
    discount_percent: number;
    discount_amount: number;
    tax_rate: number;
    tax_amount: number;
    total_amount: number;
    created_at: string;
    updated_at: string;
    medicine?: Medicine;
    batch?: MedicineBatch;
}

export interface SalePayment {
    id: number;
    sale_id: number;
    payment_method_id: number;
    amount: number;
    reference_number: string | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
    payment_method?: PaymentMethod;
}

export interface PaymentMethod {
    id: number;
    company_id: number;
    name: string;
    type: 'cash' | 'card' | 'digital_wallet' | 'bank_transfer' | 'credit';
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface Purchase {
    id: number;
    company_id: number;
    outlet_id: number;
    user_id: number;
    supplier_id: number;
    purchase_number: string;
    supplier_invoice_number: string | null;
    invoice_date: string;
    due_date: string;
    subtotal: number;
    discount_amount: number;
    tax_amount: number;
    total_amount: number;
    paid_amount: number;
    due_amount: number;
    status: 'draft' | 'ordered' | 'received' | 'cancelled';
    grn_number: string | null;
    received_at: string | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
    supplier?: Supplier;
    user?: User;
    items?: PurchaseItem[];
}

export interface PurchaseItem {
    id: number;
    purchase_id: number;
    medicine_id: number;
    batch_number: string;
    manufacturing_date: string;
    expiry_date: string;
    quantity: number;
    received_quantity: number;
    unit_price: number;
    discount_percent: number;
    discount_amount: number;
    tax_rate: number;
    tax_amount: number;
    total_amount: number;
    created_at: string;
    updated_at: string;
    medicine?: Medicine;
}

export interface SupplierPayment {
    id: number;
    supplier_id: number;
    purchase_id: number | null;
    company_id: number;
    amount: number;
    payment_method: string;
    reference_number: string | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
    supplier?: Supplier;
    purchase?: Purchase;
}

export interface Prescription {
    id: number;
    company_id: number;
    customer_id: number | null;
    doctor_name: string;
    hospital_name: string | null;
    prescription_date: string;
    diagnosis: string | null;
    notes: string | null;
    image_path: string | null;
    status: 'pending' | 'dispensed' | 'partial' | 'cancelled';
    dispensed_at: string | null;
    created_at: string;
    updated_at: string;
    customer?: Customer;
    items?: PrescriptionItem[];
}

export interface PrescriptionItem {
    id: number;
    prescription_id: number;
    medicine_name: string;
    salt_composition_id: number | null;
    medicine_id: number | null;
    dosage: string | null;
    frequency: string | null;
    duration: string | null;
    quantity_prescribed: number | null;
    quantity_dispensed: number;
    notes: string | null;
    created_at: string;
    updated_at: string;
    medicine?: Medicine;
}

export interface CustomerReturn {
    id: number;
    company_id: number;
    sale_id: number;
    customer_id: number | null;
    return_number: string;
    reason: string;
    total_amount: number;
    refund_amount: number;
    refund_method: string;
    status: 'pending' | 'approved' | 'completed' | 'rejected';
    notes: string | null;
    created_at: string;
    updated_at: string;
    sale?: Sale;
    customer?: Customer;
    items?: CustomerReturnItem[];
}

export interface CustomerReturnItem {
    id: number;
    return_id: number;
    sale_item_id: number;
    medicine_id: number;
    batch_id: number;
    quantity: number;
    unit_price: number;
    total_amount: number;
    reason: string;
    created_at: string;
    updated_at: string;
    medicine?: Medicine;
}

export interface SupplierReturn {
    id: number;
    company_id: number;
    purchase_id: number;
    supplier_id: number;
    return_number: string;
    reason: string;
    total_amount: number;
    refund_amount: number;
    status: 'pending' | 'approved' | 'completed' | 'rejected';
    notes: string | null;
    created_at: string;
    updated_at: string;
    purchase?: Purchase;
    supplier?: Supplier;
    items?: SupplierReturnItem[];
}

export interface SupplierReturnItem {
    id: number;
    return_id: number;
    purchase_item_id: number;
    medicine_id: number;
    batch_id: number;
    quantity: number;
    unit_price: number;
    total_amount: number;
    reason: string;
    created_at: string;
    updated_at: string;
    medicine?: Medicine;
}

export interface InventoryAdjustment {
    id: number;
    company_id: number;
    outlet_id: number;
    user_id: number;
    adjustment_number: string;
    type: 'addition' | 'subtraction' | 'damage' | 'expired' | 'correction';
    reason: string;
    status: 'draft' | 'approved' | 'completed';
    notes: string | null;
    created_at: string;
    updated_at: string;
    user?: User;
    items?: AdjustmentItem[];
}

export interface AdjustmentItem {
    id: number;
    adjustment_id: number;
    medicine_id: number;
    batch_id: number;
    quantity: number;
    created_at: string;
    updated_at: string;
    medicine?: Medicine;
    batch?: MedicineBatch;
}

export interface NarcoticsRegister {
    id: number;
    company_id: number;
    outlet_id: number;
    medicine_id: number;
    batch_id: number;
    customer_id: number | null;
    prescription_id: number | null;
    sale_id: number | null;
    date: string;
    quantity: number;
    balance: number;
    doctor_name: string;
    patient_name: string;
    patient_address: string;
    prescription_number: string | null;
    license_number: string | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
    medicine?: Medicine;
    customer?: Customer;
}

export interface AuditLog {
    id: number;
    company_id: number;
    user_id: number;
    action: string;
    model_type: string;
    model_id: number;
    old_values: Record<string, unknown> | null;
    new_values: Record<string, unknown> | null;
    ip_address: string;
    user_agent: string;
    created_at: string;
    user?: User;
}

export interface Register {
    id: number;
    company_id: number;
    outlet_id: number;
    user_id: number;
    opening_amount: number;
    closing_amount: number | null;
    total_sales: number;
    total_cash: number;
    total_card: number;
    total_digital: number;
    status: 'open' | 'closed';
    opened_at: string;
    closed_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface DashboardSummary {
    today_sales: {
        count: number;
        revenue: number;
        discount: number;
    };
    customer_count: number;
    medicine_count: number;
    total_stock: number;
}

export interface SalesChartData {
    date: string;
    count: number;
    revenue: number;
}

export interface ExpiryAlert {
    batch_id: number;
    medicine_name: string;
    batch_number: string;
    expiry_date: string;
    brand_name: string;
    generic_name: string;
    quantity: number;
    days_remaining: number;
}

export interface LowStockAlert {
    id: number;
    brand_name: string;
    generic_name: string;
    hsn_code: string | null;
    current_stock: number;
}

export interface TopMedicine {
    id: number;
    brand_name: string;
    generic_name: string;
    total_quantity: number;
    total_revenue: number;
}

export interface ApiResponse<T> {
    success: boolean;
    data: T;
    message: string;
}

export interface PaginatedMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

export interface PaginatedLinks {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
}

export interface PaginatedResponse<T> {
    success: boolean;
    message?: string;
    data: T[];
    meta: PaginatedMeta;
    links: PaginatedLinks;
}
