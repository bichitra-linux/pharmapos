# PharmaPOS — Pharmacy SaaS POS for Nepal

> **Conversion Plan**: Restaurant POS (iRestora PLUS v7.8) → Pharmacy SaaS POS
> **Target Market**: Nepal
> **Stack**: Laravel 11 + React 18 (Vite + TypeScript) | Monorepo | Cloud SaaS
> **Date**: May 2026

---

## Table of Contents

1. [Overview](#1-overview)
2. [Architecture](#2-architecture)
3. [Technology Stack](#3-technology-stack)
4. [Domain Conversion Map](#4-domain-conversion-map)
5. [Database Schema](#5-database-schema)
6. [API Endpoints](#6-api-endpoints)
7. [Pharmacy-Specific Features](#7-pharmacy-specific-features)
8. [Nepal Market Specifics](#8-nepal-market-specifics)
9. [Payment Gateway Integration](#9-payment-gateway-integration)
10. [Hardware Setup Guide](#10-hardware-setup-guide)
11. [Implementation Phases](#11-implementation-phases)
12. [Cost Estimates](#12-cost-estimates)
13. [Network Architecture](#13-network-architecture)

---

## 1. Overview

### What is PharmaPOS?

A cloud-based, multi-tenant Pharmacy Point-of-Sale system built specifically for the Nepali pharmaceutical retail market. Converted from the existing iRestora PLUS restaurant POS codebase.

### Key Goals

- Full pharmacy workflow: medicine catalog, batch tracking, expiry management, prescription handling
- Nepal regulatory compliance: PAN/VAT invoicing, narcotics register, drug license management
- Nepali localization: NPR currency, Bikram Sambat calendar, English + Nepali UI
- All major Nepal digital payment gateways: eSewa, Khalti, IME Pay, Fonepay, ConnectIPS
- SaaS model: subscription-based, multi-tenant, white-label capable
- Modern stack: Laravel 11 API backend + React 18 SPA frontend

---

## 2. Architecture

### Project Structure

```
pharmapos/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/    # REST API controllers
│   │   ├── Middleware/          # Tenant, Auth, Permission, CORS
│   │   └── Requests/           # Form request validation
│   ├── Models/                 # Eloquent models with scopes
│   ├── Services/               # Business logic layer
│   ├── Enums/                  # PHP 8.1+ enums
│   ├── Observers/              # Model observers (audit, stock)
│   ├── Events/                 # Event classes
│   └── Listeners/              # Event listeners
├── database/
│   ├── migrations/             # All table migrations
│   ├── seeders/                # Master data, plans, roles
│   └── factories/              # Test data factories
├── resources/
│   └── js/                     # React SPA (Vite)
│       ├── components/         # Reusable UI components
│       │   ├── ui/             # shadcn base components
│       │   ├── pos/            # POS-specific components
│       │   ├── inventory/      # Inventory components
│       │   └── reports/        # Report components
│       ├── pages/              # Page-level components
│       ├── hooks/              # Custom React hooks
│       ├── stores/             # Zustand state stores
│       ├── services/           # Axios API service layer
│       ├── lib/                # Utilities, helpers
│       └── types/              # TypeScript type definitions
├── routes/
│   ├── api.php                 # All API routes
│   └── web.php                 # SPA catch-all route
├── config/
├── docker-compose.yml          # Local dev environment
└── README.md
```

### Multi-Tenancy Model

- **Strategy**: Shared database with `company_id` column on all tenant tables
- **Scoping**: Laravel Global Scopes on all models
- **Isolation**: Automatic query scoping via middleware
- **Cost-effective**: Single database for all tenants (Nepal market is price-sensitive)

---

## 3. Technology Stack

| Layer | Technology | Version | Purpose |
|---|---|---|---|
| Backend | Laravel | 11.x | API, business logic, auth |
| PHP | PHP | 8.2+ | Runtime |
| Frontend | React | 18.x | SPA client |
| Language | TypeScript | 5.x | Type-safe frontend |
| Build Tool | Vite | 5.x | Fast HMR, builds |
| UI Library | shadcn/ui | Latest | Component library |
| CSS | Tailwind CSS | 3.4+ | Utility-first styling |
| State | Zustand | Latest | Lightweight state management |
| HTTP Client | Axios | Latest | API communication |
| Router | React Router | 6.x | Client-side routing |
| Auth | Laravel Sanctum | Latest | Token-based SPA auth |
| Database | MySQL | 8.x | Primary datastore |
| Cache/Queue | Redis | 7.x | Sessions, cache, queues |
| Search | Laravel Scout + Meilisearch | Latest | Fast medicine search |
| PDF | DomPDF / Snappy | Latest | Invoice PDF generation |
| Excel | Laravel Excel | Latest | Import/export |
| Calendar | nepalicalendar (npm) | Latest | Bikram Sambat support |
| Printer | ESC/POS (PHP) | Latest | Thermal receipt printing |
| Real-time | Laravel Reverb | Latest | WebSocket notifications |

---

## 4. Domain Conversion Map

### Concept Mapping (Restaurant → Pharmacy)

| Restaurant Concept | Pharmacy Concept | Database Change |
|---|---|---|
| Food Menu / Food Items | Medicines / Products | `tbl_food_menus` → `medicines` |
| Food Menu Categories | Drug Categories / Therapeutic Groups | `tbl_food_menu_categories` → `medicine_categories` |
| Modifiers (add-ons) | Unit variants (strip/bottle/pack) | Remove modifier system, use unit-based variants |
| Ingredients | Salt Composition / Generic Name | `tbl_ingredients` → `salt_compositions` |
| Kitchen | Dispensary Counter | `tbl_kitchens` → `dispensary_counters` |
| Kitchen Order Ticket (KOT) | Dispensing Slip / Prescription Label | New workflow |
| Table / Area | Counter / Consultation Room | Remove `tbl_tables`/`tbl_areas` |
| Dine-in / Takeaway / Delivery | Walk-in / Online / Delivery | Simplify order types |
| Waiter | Pharmacist / Technician | Role rename |
| Recipe (food→ingredients) | Composition (salt+strength) | New model `medicine_compositions` |
| Token Number | Prescription Number | Minor rename |
| Delivery Partner | Delivery Service (Pathao etc.) | Keep as-is |
| Combo Food Items | Remove | Drop `tbl_combo_food_menus` |
| Food Menu Ratings | Remove | Drop `tbl_food_menu_ratings` |
| Reservations | Remove | Drop `tbl_reservations` |
| Pre-made Food Items | Remove | Drop related tables |

### Tables to Remove

- `tbl_tables`, `tbl_areas` — No floor plan in pharmacy
- `tbl_combo_food_menus` — No combo meals
- `tbl_food_menu_ratings` — No food ratings
- `tbl_reservations` — No table booking
- `tbl_modifiers`, `tbl_modifier_ingredients` — No add-ons
- `tbl_food_menus_ingredients` (recipe) — Replace with salt composition
- `tbl_kitchen_*` tables — Repurpose or remove
- `tbl_carts` — Simplify or remove

---

## 5. Database Schema

### 5.1 Multi-Tenant Foundation

```sql
-- Companies (tenants)
CREATE TABLE companies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    logo VARCHAR(500) NULL,
    address TEXT NULL,
    phone VARCHAR(20) NULL,
    email VARCHAR(255) NULL,
    pan_number VARCHAR(20) NULL,
    vat_number VARCHAR(20) NULL,
    drug_license_number VARCHAR(50) NULL,
    pharmacy_license_number VARCHAR(50) NULL,
    pharmacist_name VARCHAR(255) NULL,
    pharmacist_registration_number VARCHAR(50) NULL,
    subscription_plan_id BIGINT UNSIGNED NULL,
    subscription_expires_at TIMESTAMP NULL,
    settings JSON NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Outlets (branches)
CREATE TABLE outlets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    address TEXT NULL,
    phone VARCHAR(20) NULL,
    drug_license_number VARCHAR(50) NULL,
    is_main_outlet TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    settings JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id)
);

-- Users
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    outlet_id BIGINT UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'staff',
    pharmacist_registration VARCHAR(50) NULL,
    permissions JSON NULL,
    is_active TINYINT(1) DEFAULT 1,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_email_company (email, company_id),
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (outlet_id) REFERENCES outlets(id)
);
```

### 5.2 Medicine Catalog

```sql
-- Manufacturers (pharma companies)
CREATE TABLE manufacturers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    country VARCHAR(100) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id)
);

-- Salt compositions (generic names)
CREATE TABLE salt_compositions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Medicine categories
CREATE TABLE medicine_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    parent_id BIGINT UNSIGNED NULL,
    description TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (parent_id) REFERENCES medicine_categories(id)
);

-- Medicines (core catalog)
CREATE TABLE medicines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    generic_name VARCHAR(255) NOT NULL,
    brand_name VARCHAR(255) NOT NULL,
    manufacturer_id BIGINT UNSIGNED NULL,
    salt_composition_id BIGINT UNSIGNED NULL,
    medicine_category_id BIGINT UNSIGNED NULL,
    dosage_form ENUM('tablet','capsule','syrup','injection','ointment','cream','drops','inhaler','powder','gel','lotion','suspension','solution','suppository','patch','spray','other') NOT NULL DEFAULT 'tablet',
    strength VARCHAR(100) NULL,
    unit_type ENUM('strip','bottle','tube','piece','box','vial','sachet','roll') NOT NULL DEFAULT 'strip',
    units_per_pack INT UNSIGNED DEFAULT 1,
    schedule_type ENUM('H','H1','X','G','OTC') NOT NULL DEFAULT 'OTC',
    hsn_code VARCHAR(20) NULL,
    is_prescription_required TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    barcode VARCHAR(50) NULL,
    image VARCHAR(500) NULL,
    description TEXT NULL,
    storage_conditions VARCHAR(255) NULL,
    is_temperature_sensitive TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (manufacturer_id) REFERENCES manufacturers(id),
    FOREIGN KEY (salt_composition_id) REFERENCES salt_compositions(id),
    FOREIGN KEY (medicine_category_id) REFERENCES medicine_categories(id),
    INDEX idx_brand_name (brand_name),
    INDEX idx_generic_name (generic_name),
    INDEX idx_barcode (barcode),
    INDEX idx_company_active (company_id, is_active)
);

-- Medicine batches (batch-wise inventory)
CREATE TABLE medicine_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    medicine_id BIGINT UNSIGNED NOT NULL,
    outlet_id BIGINT UNSIGNED NOT NULL,
    batch_number VARCHAR(50) NOT NULL,
    manufacturing_date DATE NULL,
    expiry_date DATE NOT NULL,
    quantity_in_stock DECIMAL(10,2) NOT NULL DEFAULT 0,
    purchase_price_per_unit DECIMAL(10,2) NOT NULL DEFAULT 0,
    mrp_per_unit DECIMAL(10,2) NOT NULL DEFAULT 0,
    selling_price_per_unit DECIMAL(10,2) NOT NULL DEFAULT 0,
    barcode VARCHAR(50) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (medicine_id) REFERENCES medicines(id),
    FOREIGN KEY (outlet_id) REFERENCES outlets(id),
    INDEX idx_batch_expiry (outlet_id, expiry_date),
    INDEX idx_batch_stock (outlet_id, medicine_id, quantity_in_stock)
);

-- Generic substitutes mapping
CREATE TABLE substitutes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    medicine_id_1 BIGINT UNSIGNED NOT NULL,
    medicine_id_2 BIGINT UNSIGNED NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medicine_id_1) REFERENCES medicines(id),
    FOREIGN KEY (medicine_id_2) REFERENCES medicines(id),
    UNIQUE KEY unique_substitute (medicine_id_1, medicine_id_2)
);
```

### 5.3 Sales & Billing

```sql
-- Payment methods
CREATE TABLE payment_methods (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('cash','digital_wallet','card','bank_transfer','credit') NOT NULL,
    gateway VARCHAR(50) NULL,
    is_active TINYINT(1) DEFAULT 1,
    config JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id)
);

-- Cash register
CREATE TABLE registers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    outlet_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    opening_balance DECIMAL(12,2) NOT NULL DEFAULT 0,
    closing_balance DECIMAL(12,2) NULL,
    total_sales DECIMAL(12,2) NULL,
    total_returns DECIMAL(12,2) NULL,
    total_cash DECIMAL(12,2) NULL,
    total_digital DECIMAL(12,2) NULL,
    status ENUM('open','closed') NOT NULL DEFAULT 'open',
    opened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (outlet_id) REFERENCES outlets(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Sales
CREATE TABLE sales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    outlet_id BIGINT UNSIGNED NOT NULL,
    register_id BIGINT UNSIGNED NULL,
    invoice_number VARCHAR(50) NOT NULL,
    customer_id BIGINT UNSIGNED NULL,
    prescription_id BIGINT UNSIGNED NULL,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount_type ENUM('percentage','fixed') NULL,
    vat_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    vat_percentage DECIMAL(5,2) NOT NULL DEFAULT 13.00,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    due_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    payment_status ENUM('paid','partial','due','refunded') NOT NULL DEFAULT 'paid',
    sale_type ENUM('walk_in','online','delivery') NOT NULL DEFAULT 'walk_in',
    dispensed_by BIGINT UNSIGNED NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (outlet_id) REFERENCES outlets(id),
    FOREIGN KEY (register_id) REFERENCES registers(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (dispensed_by) REFERENCES users(id),
    UNIQUE KEY unique_invoice (company_id, invoice_number),
    INDEX idx_sale_date (company_id, created_at)
);

-- Sale items
CREATE TABLE sale_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id BIGINT UNSIGNED NOT NULL,
    medicine_id BIGINT UNSIGNED NOT NULL,
    batch_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit_type VARCHAR(20) NOT NULL,
    mrp DECIMAL(10,2) NOT NULL,
    selling_price DECIMAL(10,2) NOT NULL,
    discount DECIMAL(10,2) NOT NULL DEFAULT 0,
    vat DECIMAL(10,2) NOT NULL DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    prescription_required TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id),
    FOREIGN KEY (batch_id) REFERENCES medicine_batches(id)
);

-- Sale payments
CREATE TABLE sale_payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id BIGINT UNSIGNED NOT NULL,
    payment_method_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    reference_number VARCHAR(100) NULL,
    gateway_response JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id)
);
```

### 5.4 Prescriptions

```sql
CREATE TABLE prescriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NULL,
    prescription_number VARCHAR(50) NOT NULL,
    doctor_name VARCHAR(255) NULL,
    hospital_name VARCHAR(255) NULL,
    prescription_date DATE NULL,
    image_path VARCHAR(500) NULL,
    status ENUM('pending','dispensed','partial','cancelled') NOT NULL DEFAULT 'pending',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    UNIQUE KEY unique_rx (company_id, prescription_number)
);

CREATE TABLE prescription_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prescription_id BIGINT UNSIGNED NOT NULL,
    medicine_name VARCHAR(255) NOT NULL,
    salt_composition_id BIGINT UNSIGNED NULL,
    medicine_id BIGINT UNSIGNED NULL,
    dosage VARCHAR(100) NULL,
    frequency VARCHAR(100) NULL,
    duration VARCHAR(100) NULL,
    quantity_prescribed DECIMAL(10,2) NULL,
    quantity_dispensed DECIMAL(10,2) NOT NULL DEFAULT 0,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (salt_composition_id) REFERENCES salt_compositions(id),
    FOREIGN KEY (medicine_id) REFERENCES medicines(id)
);
```

### 5.5 Purchase & Supplier

```sql
CREATE TABLE suppliers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255) NULL,
    phone VARCHAR(20) NULL,
    email VARCHAR(255) NULL,
    address TEXT NULL,
    pan_number VARCHAR(20) NULL,
    drug_license_number VARCHAR(50) NULL,
    payment_terms VARCHAR(100) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id)
);

CREATE TABLE purchases (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    outlet_id BIGINT UNSIGNED NOT NULL,
    supplier_id BIGINT UNSIGNED NOT NULL,
    purchase_number VARCHAR(50) NOT NULL,
    purchase_date DATE NOT NULL,
    grn_number VARCHAR(50) NULL,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount DECIMAL(12,2) NOT NULL DEFAULT 0,
    vat DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    due_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    payment_status ENUM('paid','partial','due') NOT NULL DEFAULT 'due',
    status ENUM('draft','received','cancelled') NOT NULL DEFAULT 'draft',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (outlet_id) REFERENCES outlets(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    UNIQUE KEY unique_purchase (company_id, purchase_number)
);

CREATE TABLE purchase_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_id BIGINT UNSIGNED NOT NULL,
    medicine_id BIGINT UNSIGNED NOT NULL,
    batch_number VARCHAR(50) NOT NULL,
    manufacturing_date DATE NULL,
    expiry_date DATE NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    purchase_price DECIMAL(10,2) NOT NULL,
    mrp DECIMAL(10,2) NOT NULL,
    selling_price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id)
);

-- Supplier payments
CREATE TABLE supplier_payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    supplier_id BIGINT UNSIGNED NOT NULL,
    purchase_id BIGINT UNSIGNED NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    reference_number VARCHAR(100) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (purchase_id) REFERENCES purchases(id)
);
```

### 5.6 Returns

```sql
CREATE TABLE customer_returns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    outlet_id BIGINT UNSIGNED NOT NULL,
    sale_id BIGINT UNSIGNED NOT NULL,
    return_number VARCHAR(50) NOT NULL,
    return_date DATE NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    refund_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    refund_method VARCHAR(50) NULL,
    reason TEXT NULL,
    processed_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (outlet_id) REFERENCES outlets(id),
    FOREIGN KEY (sale_id) REFERENCES sales(id),
    FOREIGN KEY (processed_by) REFERENCES users(id),
    UNIQUE KEY unique_return (company_id, return_number)
);

CREATE TABLE customer_return_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    return_id BIGINT UNSIGNED NOT NULL,
    medicine_id BIGINT UNSIGNED NOT NULL,
    batch_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    reason VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (return_id) REFERENCES customer_returns(id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id),
    FOREIGN KEY (batch_id) REFERENCES medicine_batches(id)
);

CREATE TABLE supplier_returns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    outlet_id BIGINT UNSIGNED NOT NULL,
    supplier_id BIGINT UNSIGNED NOT NULL,
    purchase_id BIGINT UNSIGNED NULL,
    return_number VARCHAR(50) NOT NULL,
    return_date DATE NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    refund_status ENUM('pending','received','cancelled') NOT NULL DEFAULT 'pending',
    reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (outlet_id) REFERENCES outlets(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (purchase_id) REFERENCES purchases(id)
);

CREATE TABLE supplier_return_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_return_id BIGINT UNSIGNED NOT NULL,
    medicine_id BIGINT UNSIGNED NOT NULL,
    batch_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    reason VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_return_id) REFERENCES supplier_returns(id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id),
    FOREIGN KEY (batch_id) REFERENCES medicine_batches(id)
);
```

### 5.7 Inventory Adjustments

```sql
CREATE TABLE inventory_adjustments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    outlet_id BIGINT UNSIGNED NOT NULL,
    type ENUM('damage','expiry','count_adjustment','return','other') NOT NULL,
    reason TEXT NULL,
    adjusted_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (outlet_id) REFERENCES outlets(id),
    FOREIGN KEY (adjusted_by) REFERENCES users(id)
);

CREATE TABLE adjustment_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    adjustment_id BIGINT UNSIGNED NOT NULL,
    medicine_id BIGINT UNSIGNED NOT NULL,
    batch_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    reason VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (adjustment_id) REFERENCES inventory_adjustments(id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id),
    FOREIGN KEY (batch_id) REFERENCES medicine_batches(id)
);
```

### 5.8 Regulatory

```sql
CREATE TABLE narcotics_register (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    outlet_id BIGINT UNSIGNED NOT NULL,
    sale_id BIGINT UNSIGNED NULL,
    medicine_id BIGINT UNSIGNED NOT NULL,
    batch_id BIGINT UNSIGNED NOT NULL,
    patient_name VARCHAR(255) NOT NULL,
    patient_address TEXT NULL,
    doctor_name VARCHAR(255) NOT NULL,
    prescription_number VARCHAR(50) NULL,
    quantity DECIMAL(10,2) NOT NULL,
    balance DECIMAL(10,2) NOT NULL,
    dispensed_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (outlet_id) REFERENCES outlets(id),
    FOREIGN KEY (sale_id) REFERENCES sales(id),
    FOREIGN KEY (medicine_id) REFERENCES medicines(id),
    FOREIGN KEY (batch_id) REFERENCES medicine_batches(id),
    FOREIGN KEY (dispensed_by) REFERENCES users(id)
);

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(50) NOT NULL,
    model_type VARCHAR(100) NOT NULL,
    model_id BIGINT UNSIGNED NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_audit_model (model_type, model_id),
    INDEX idx_audit_date (company_id, created_at)
);
```

### 5.9 Customers

```sql
CREATE TABLE customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    email VARCHAR(255) NULL,
    address TEXT NULL,
    date_of_birth DATE NULL,
    gender ENUM('male','female','other') NULL,
    allergies TEXT NULL,
    chronic_conditions TEXT NULL,
    loyalty_points INT UNSIGNED DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    INDEX idx_customer_phone (company_id, phone)
);
```

### 5.10 SaaS Subscription

```sql
CREATE TABLE subscription_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price_monthly DECIMAL(10,2) NOT NULL,
    price_yearly DECIMAL(10,2) NOT NULL,
    max_outlets INT UNSIGNED NOT NULL DEFAULT 1,
    max_users INT UNSIGNED NOT NULL DEFAULT 3,
    max_medicines INT UNSIGNED NOT NULL DEFAULT 5000,
    features JSON NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE subscription_payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    plan_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    gateway VARCHAR(50) NULL,
    gateway_response JSON NULL,
    starts_at TIMESTAMP NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    status ENUM('active','expired','cancelled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (plan_id) REFERENCES subscription_plans(id)
);
```

---

## 6. API Endpoints

### Authentication

```
POST   /api/auth/login                    # Email + password login
POST   /api/auth/register                 # Company registration
POST   /api/auth/logout                   # Invalidate token
GET    /api/auth/me                       # Current user profile
POST   /api/auth/password/reset           # Password reset request
POST   /api/auth/password/reset/confirm   # Confirm password reset
```

### Dashboard

```
GET    /api/dashboard                     # Summary stats
GET    /api/dashboard/expiry-alerts       # Medicines expiring soon
GET    /api/dashboard/low-stock           # Low stock alerts
GET    /api/dashboard/sales-chart         # Sales trend chart data
GET    /api/dashboard/top-medicines       # Best selling medicines
```

### Medicines

```
GET    /api/medicines                     # List (search, filter, paginate)
POST   /api/medicines                     # Create
GET    /api/medicines/{id}                # Show
PUT    /api/medicines/{id}                # Update
DELETE /api/medicines/{id}                # Soft delete
GET    /api/medicines/search?q=           # Fast search (brand, generic, salt, barcode)
GET    /api/medicines/{id}/batches        # All batches for medicine
GET    /api/medicines/{id}/substitutes    # Generic substitutes
POST   /api/medicines/import              # Bulk import from Excel/CSV
```

### Batches

```
GET    /api/batches                       # List all batches
POST   /api/batches                       # Create batch
PUT    /api/batches/{id}                  # Update batch
GET    /api/batches/expiring?days=30      # Expiring soon
```

### Categories & Manufacturers

```
GET    /api/categories                    # List categories
POST   /api/categories                    # Create category
PUT    /api/categories/{id}               # Update category
DELETE /api/categories/{id}               # Delete category

GET    /api/manufacturers                 # List manufacturers
POST   /api/manufacturers                 # Create manufacturer
PUT    /api/manufacturers/{id}            # Update manufacturer
```

### Sales / POS

```
GET    /api/sales                         # List sales (date range, filter)
POST   /api/sales                         # Create sale (main POS action)
GET    /api/sales/{id}                    # Sale detail
GET    /api/sales/{id}/invoice            # Generate invoice PDF
POST   /api/sales/{id}/return             # Process return
GET    /api/sales/daily-summary           # Daily sales summary
```

### Prescriptions

```
GET    /api/prescriptions                 # List prescriptions
POST   /api/prescriptions                 # Create/upload prescription
GET    /api/prescriptions/{id}            # Show prescription
PUT    /api/prescriptions/{id}            # Update prescription
PUT    /api/prescriptions/{id}/dispense   # Mark as dispensed
```

### Customers

```
GET    /api/customers                     # List customers
POST   /api/customers                     # Create customer
GET    /api/customers/{id}                # Show customer
PUT    /api/customers/{id}                # Update customer
GET    /api/customers/{id}/history        # Purchase + prescription history
```

### Suppliers

```
GET    /api/suppliers                     # List suppliers
POST   /api/suppliers                     # Create supplier
GET    /api/suppliers/{id}                # Show supplier
PUT    /api/suppliers/{id}                # Update supplier
GET    /api/suppliers/{id}/ledger         # Supplier ledger
```

### Purchases

```
GET    /api/purchases                     # List purchases
POST   /api/purchases                     # Create purchase
GET    /api/purchases/{id}                # Show purchase
PUT    /api/purchases/{id}                # Update purchase
POST   /api/purchases/{id}/receive        # Mark as received (GRN)
```

### Supplier Payments

```
GET    /api/supplier-payments             # List payments
POST   /api/supplier-payments             # Record payment
```

### Inventory

```
GET    /api/inventory/stock               # Current stock report
GET    /api/inventory/adjustments         # List adjustments
POST   /api/inventory/adjustments         # Create adjustment
```

### Returns

```
GET    /api/customer-returns              # List customer returns
POST   /api/customer-returns              # Process customer return
GET    /api/supplier-returns              # List supplier returns
POST   /api/supplier-returns              # Process supplier return
```

### Payments

```
GET    /api/payment-methods               # List configured methods
POST   /api/payments/esewa/initiate       # Start eSewa payment
POST   /api/payments/khalti/initiate      # Start Khalti payment
POST   /api/payments/fonepay/initiate     # Start Fonepay payment
POST   /api/payments/connectips/initiate  # Start ConnectIPS payment
POST   /api/payments/callback/{gateway}   # Payment gateway callback
```

### Reports

```
GET    /api/reports/sales                 # Sales report (date range, filters)
GET    /api/reports/purchase              # Purchase report
GET    /api/reports/inventory             # Inventory report
GET    /api/reports/expiry                # Expiry report
GET    /api/reports/profit-loss           # Profit & loss
GET    /api/reports/vat                   # VAT report (IRD filing)
GET    /api/reports/narcotics             # Narcotics register report
GET    /api/reports/dead-stock            # Dead stock report
GET    /api/reports/supplier-due          # Supplier due report
GET    /api/reports/customer-due          # Customer due report
GET    /api/reports/schedule-wise         # Schedule H/H1/X sales
GET    /api/reports/category-wise         # Category-wise sales
```

### Narcotics Register

```
GET    /api/narcotics-register            # List entries
POST   /api/narcotics-register            # Create entry
```

### Users & Roles

```
GET    /api/users                         # List users
POST   /api/users                         # Create user
GET    /api/users/{id}                    # Show user
PUT    /api/users/{id}                    # Update user
DELETE /api/users/{id}                    # Deactivate user

GET    /api/roles                         # List roles
POST   /api/roles                         # Create role
PUT    /api/roles/{id}                    # Update role
```

### Settings

```
GET    /api/settings                      # Get all settings
PUT    /api/settings                      # Update settings
GET    /api/company                       # Get company profile
PUT    /api/company                       # Update company profile
```

### Subscription

```
GET    /api/subscription/plans            # List available plans
POST   /api/subscription/subscribe        # Subscribe to plan
GET    /api/subscription/status           # Current subscription status
POST   /api/subscription/cancel           # Cancel subscription
```

---

## 7. Pharmacy-Specific Features

### 7.1 Medicine Management

- **Batch-wise inventory**: Every unit tracked by batch number, manufacturing date, expiry date
- **Expiry management**: Auto-alerts at 30/60/90 days, near-expiry discounting suggestions
- **Schedule classification**: Schedule H (prescription required), H1 (strict tracking), X (narcotic register), G, OTC
- **Generic substitution**: Suggest cheaper generic alternatives when brand medicine selected
- **Salt composition search**: Search by generic name/salt, not just brand name
- **Drug interaction warnings**: Alert when dispensing conflicting medicines together
- **Minimum stock / Reorder level**: Auto-purchase suggestions when stock low
- **FIFO dispensing**: First Expiry First Out — auto-select nearest expiry batch
- **Cold chain tracking**: Flag temperature-sensitive medicines
- **Narcotics register**: Separate digital register for Schedule X drugs (NDDBA compliance)
- **Barcode support**: EAN-13 barcode scanning and label printing

### 7.2 Prescription Management

- **Prescription upload**: Camera capture or file scan, linked to customer
- **Prescription validation**: Pharmacist must verify before dispensing Schedule H/H1/X drugs
- **Prescription history**: Per-customer archive of all prescriptions
- **Repeat prescription**: Refill reminders for chronic patients (diabetes, hypertension, etc.)
- **Partial dispensing**: Dispense part of prescription, remaining tracked for later

### 7.3 Sales & Billing

- **Walk-in billing**: Simplified POS screen (no table/dine-in concept)
- **Prescription-linked billing**: Mandatory prescription verification for scheduled drugs
- **Return/Exchange**: Batch-verified medicine returns within allowed period
- **MRP-based pricing**: Nepal law requires MRP as maximum selling price
- **VAT calculation**: 13% VAT on applicable medicines
- **Multiple payments**: Split payment across cash, digital wallet, card
- **Loyalty points**: Points on purchase, redeemable for discounts
- **Invoice printing**: Nepal-compliant invoice with PAN, drug license, pharmacist name

### 7.4 Purchase & Supplier

- **Pharma distributor management**: Nepal-specific distributor master data
- **Purchase with batch/expiry**: Mandatory batch number and expiry date entry on purchase
- **GRN workflow**: Purchase order → Goods Received Note → Stock update
- **Return to supplier**: Expired or damaged stock return with tracking
- **Rate comparison**: Compare purchase prices across distributors for same medicine
- **Supplier ledger**: Full payment history per distributor

### 7.5 Regulatory Compliance

- **PAN/VAT invoicing**: Nepal IRD-compliant invoice format
- **Drug license display**: License number printed on all invoices and receipts
- **Narcotics register**: Digital register matching NDDBA requirements for Schedule X
- **Audit trail**: Complete log of all actions — who dispensed what, when, why
- **Government inspection reports**: Ready-to-print reports for DDA inspections
- **Bikram Sambat calendar**: Nepali date display alongside AD dates throughout

### 7.6 Reports

| Report | Description |
|---|---|
| Sales Summary | Daily/weekly/monthly sales totals |
| Sales by Medicine | Best/worst selling medicines |
| Sales by Category | Category-wise breakdown |
| Sales by Schedule | Schedule H, H1, X, OTC breakdown |
| Expiry Report | Medicines expiring in 30/60/90 days |
| Dead Stock | Items not sold in X days |
| Stock Report | Current inventory with batch details |
| Stock Valuation | Inventory value at cost and MRP |
| Profit & Loss | Revenue, cost, profit per batch |
| VAT Report | Input/output VAT for IRD filing |
| Narcotics Register | Schedule X dispensing log |
| Supplier Due | Outstanding payments to distributors |
| Customer Due | Outstanding receivables from customers |
| Purchase Report | Purchase history by supplier/date |
| Return Report | Customer and supplier returns |
| Audit Log | All system actions with user/time |

---

## 8. Nepal Market Specifics

### 8.1 Localization

| Aspect | Implementation |
|---|---|
| **Currency** | Nepali Rupee (NPR / रू) — symbol: रू, code: NPR |
| **Languages** | English (primary) + Nepali (secondary) |
| **Calendar** | Bikram Sambat (BS) displayed alongside AD dates |
| **Date format** | BS: YYYY/MM/DD, AD: DD/MM/YYYY |
| **Number format** | Nepali lakh/crore style: 1,00,000 (not 100,000) |
| **Tax** | 13% VAT on medicines (Nepal standard rate) |
| **Invoice** | PAN number mandatory, VAT breakdown required |

### 8.2 Regulatory Requirements

| Regulation | Requirement |
|---|---|
| **Drug Act 2035 (1978)** | Nepal's primary pharmaceutical regulation |
| **DDA (Dept. of Drug Administration)** | Licensing and oversight authority |
| **Drug License** | Must display on invoice and premises |
| **Pharmacist on Duty** | Registered pharmacist must be present during operations |
| **Schedule H Drugs** | Require valid prescription to dispense |
| **Schedule X Drugs** | Narcotics register required, strict quantity tracking |
| **VAT Compliance** | IRD (Inland Revenue Department) compliant invoicing |
| **Barcode Standards** | EAN-13 / GS1 barcodes on medicines |

---

## 9. Payment Gateway Integration

### Supported Gateways

| Gateway | Type | Integration Method |
|---|---|---|
| **eSewa** | Digital Wallet | Merchant API (redirect flow) |
| **Khalti** | Digital Wallet | Khalti API (redirect or widget) |
| **IME Pay** | Digital Wallet | IME Merchant API |
| **Fonepay** | QR Payment | Fonepay API (QR generate/verify) |
| **ConnectIPS** | Bank Transfer | NCHL ConnectIPS API |
| **Cash** | Physical | Direct recording (no gateway) |
| **Card** | POS Terminal | Manual recording (terminal separate) |

---

## 10. Hardware Setup Guide

### Minimum Setup (Single Counter)

| Item | Recommended Model | Est. Price (NPR) |
|---|---|---|
| POS Terminal | Any PC/Laptop: Intel i5+, 8GB RAM, 256GB SSD | 50,000 - 80,000 |
| Thermal Receipt Printer | Epson TM-T82III or Bixolon SRP-350II (80mm, ESC/POS) | 15,000 - 25,000 |
| Barcode Scanner | Zebra LS2208 or Honeywell Voyager 1900 (1D/2D) | 5,000 - 15,000 |
| Cash Drawer | 8-bill, 5-coin compartment, with lock | 5,000 - 8,000 |
| UPS / Inverter | APC 650VA or CyberPower 1000VA | 8,000 - 15,000 |
| WiFi Router | TP-Link Archer C6 or similar | 3,000 - 5,000 |
| **TOTAL** | | **86,000 - 1,48,000** |

---

## 11. Implementation Phases

### Phase 1: Foundation (Weeks 1-2)
- [x] Initialize Laravel 11 project with Vite + React + TypeScript
- [ ] Configure monorepo structure
- [ ] Set up Docker environment (MySQL 8, Redis 7, PHP 8.2)
- [ ] Implement multi-tenancy (company scoping with global scopes)
- [ ] Build auth system (Sanctum tokens, roles, permissions)
- [ ] Create all database migrations
- [ ] Seed master data
- [ ] Set up Tailwind CSS + shadcn/ui component library
- [ ] Build app layout shell
- [ ] Nepali localization setup

### Phase 2: Medicine Catalog & Inventory (Weeks 3-5)
### Phase 3: POS & Billing (Weeks 6-8)
### Phase 4: Purchase & Supplier (Weeks 9-10)
### Phase 5: Customer & Prescription (Week 11)
### Phase 6: Regulatory & Compliance (Week 12)
### Phase 7: Reports & Analytics (Weeks 13-14)
### Phase 8: Payment Gateway Integration (Week 15)
### Phase 9: SaaS Features (Week 16)
### Phase 10: Testing & Deployment (Weeks 17-18)

---

## 12. Cost Estimates

### Hardware (Per Pharmacy)

| Setup Level | NPR | USD |
|---|---|---|
| Basic (single counter) | 86,000 - 1,48,000 | ~$650 - $1,100 |
| Standard (+ recommended) | 1,50,000 - 2,50,000 | ~$1,100 - $1,900 |
| Multi-counter (3 counters) | 3,50,000 - 5,50,000 | ~$2,600 - $4,100 |

### SaaS Pricing (Nepal Market)

| Plan | NPR/month | Outlets | Users | Medicines |
|---|---|---|---|---|
| Starter | 1,500 | 1 | 2 | 2,000 |
| Professional | 3,500 | 2 | 5 | 10,000 |
| Enterprise | 7,000 | 5 | 15 | Unlimited |

---

*Document: May 2026 | PharmaPOS — Pharmacy SaaS POS for Nepal*
