# PharmaPOS Issue Fix Report

**Date:** May 28, 2026  
**Issues Addressed:** 6 user-reported problems  
**Status:** All resolved

---

## Issue 1: Medicines Not Displaying on Medicine Page

**Root Cause:** The `DummyDataSeeder` hardcoded `companyId = 1`, but dynamic lookups were needed. If the database auto-increment didn't start at 1, seeded medicines wouldn't match the logged-in user's `company_id`.

**Fix Applied:**
- `database/seeders/DummyDataSeeder.php` — Changed from hardcoded `private int $companyId = 1` to dynamic lookups: `DB::table('companies')->first()->id`
- Same for `outletId` and `userId`

**Files Changed:** `DummyDataSeeder.php`

---

## Issue 2: Import Button Non-Functional

**Root Cause:** `medicines/index.tsx:56` called `navigate('/medicines/import')`, but:
- No page component existed
- No route was registered
- The route `medicines/:id` matched instead, causing ShowMedicine to fetch medicine with id "import"

**Fix Applied:**
- Created `resources/js/pages/medicines/import.tsx` — Full import page with CSV upload, format guide, and result display
- Added route `medicines/import` in `resources/js/router.tsx` (before `:id` route)
- Uses existing `medicinesService.import()` backend endpoint

**Files Created:** `resources/js/pages/medicines/import.tsx`  
**Files Modified:** `resources/js/router.tsx`

---

## Issue 3: Inventory Page Data Issues

**Root Cause:** Complete field name mismatch between API and frontend:
- API returned flat aggregated data (`brand_name`, `current_stock`, `stock_value`)
- Frontend expected per-batch `MedicineBatch` fields (`medicine?.brand_name`, `batch_number`, `expiry_date`, `quantity_in_stock`)

**Fix Applied:**
- Rewrote `InventoryController::stock()` to return per-batch data using Eloquent with relations
- Changed from `DB::table()` raw query to `MedicineBatch::where()->with(['medicine', 'medicine.manufacturer', 'medicine.medicineCategory'])`
- Added filters for category, manufacturer, schedule, stock status, expiring days
- Updated `inventory/index.tsx` columns to match new data structure (category, schedule badge, expiry highlighting)

**Files Modified:** `app/Http/Controllers/Api/InventoryController.php`, `resources/js/pages/inventory/index.tsx`

---

## Issue 4: Sales Page Status Column Problem

**Root Cause:** Two problems:
1. The `sales` migration has **no `status` column**, but the frontend rendered `item.status` (always undefined)
2. `Sale` TypeScript type declared `status: 'completed' | 'held' | 'cancelled' | 'returned'` which doesn't exist in DB
3. `payment_status` values mismatch: DB uses `'due'`, TypeScript type said `'unpaid'`

**Fix Applied:**
- Removed `status` column from `Sale` TypeScript type (doesn't exist in DB)
- Aligned `payment_status` to match DB: `'paid' | 'partial' | 'due' | 'refunded'`
- Updated `sales/index.tsx` to show only `payment_status` with proper badge variants
- Updated `sales/show.tsx` to use `payment_status` instead of `status`, `dispensedBy` instead of `user`
- Fixed `vat_amount` references (was `tax_amount`)

**Files Modified:** `resources/js/types/index.ts`, `resources/js/pages/sales/index.tsx`, `resources/js/pages/sales/show.tsx`, `resources/js/components/pos/invoice-preview.tsx`

---

## Issue 5: Schedule Drug Info on Add Medicine Page

**Root Cause:** The schedule dropdown showed only letter codes (`H`, `H1`, `X`, `NRX`, `G`) with no descriptions. Pharmacists unfamiliar with the codes had no guidance.

Additionally:
- DB enum uses lowercase (`h`, `h1`, `x`, `g`, `otc`) but frontend sent uppercase
- `dosage_form` and `unit_type` constants were plain strings, now changed to objects

**Fix Applied:**
- Redesigned `SCHEDULE_TYPES` in `constants.ts` as objects with `value`, `label`, and `description`
- Added detailed descriptions for each schedule type explaining legal requirements
- Added dynamic schedule info box on medicine create page that shows when a schedule is selected
- Shows prescription warning for H/H1/X drugs
- Shows narcotics register warning for X drugs
- Normalized all enum values to lowercase throughout
- Updated `DOSAGE_FORMS` and `UNIT_TYPES` to object format with labels

**Constants now:**
```typescript
SCHEDULE_TYPES = [
    { value: 'h', label: 'H — Prescription Required', description: 'Schedule H drugs can only be sold with a valid prescription...' },
    { value: 'h1', label: 'H1 — Strict Prescription', description: 'Schedule H1 drugs require prescription with patient details...' },
    { value: 'x', label: 'X — Narcotic / Psychotropic', description: 'Schedule X drugs are narcotics...' },
    { value: 'g', label: 'G — General (OTC)', description: 'General / Over-the-counter drugs...' },
    { value: 'otc', label: 'OTC — Over the Counter', description: 'Over-the-counter medicines...' },
]
```

**Files Modified:** `resources/js/lib/constants.ts`, `resources/js/pages/medicines/create.tsx`, `resources/js/pages/medicines/edit.tsx`

---

## Issue 6: Data Seeder Update and Fresh Migration

**Root Cause:** Multiple seeder issues:
- Hardcoded `companyId = 1` (fixed in Issue 1)
- `schedule_type` casing: seeder used `'OTC'`/`'H'` (uppercase), DB enum is lowercase
- `dosage_form`/`unit_type` mismatch with frontend constants
- `SaltCompositionSeeder` didn't set `company_id` (new column added in security fix)

**Fix Applied:**
- All enum values normalized to lowercase in seeder (`'otc'`, `'h'`, `'tablet'`, `'strip'`)
- Dynamic company/outlet/user lookups
- `SaltCompositionSeeder` updated to set `company_id`
- Fresh migration run: `php artisan migrate:fresh --seed`
- All 40 migrations ran successfully
- All 7 seeders completed

**Final seeded data:**
| Table | Count |
|---|---|
| Companies | 1 |
| Outlets | 1 |
| Users | 1 |
| Subscription Plans | 3 |
| Salt Compositions | 50 |
| Medicine Categories | 20 |
| Manufacturers | 20 |
| Super Admins | 1 |
| Customers | 12 |
| Suppliers | 12 |
| Medicines | 12 |
| Medicine Batches | 24 |
| Sales | 12 |
| Sale Items | ~35 |
| Purchases | 10 |
| Prescriptions | 10 |
| Registers | 10 |

**Files Modified:** `DummyDataSeeder.php`, `SaltCompositionSeeder.php`

---

## Verification

| Check | Result |
|---|---|
| `php artisan migrate:fresh --seed` | All 40 migrations + 7 seeders passed |
| `npx tsc --noEmit` | 0 TypeScript errors |
| `npx vite build` | Passes (3.5s) |
| `php artisan route:list` | 145 routes loaded |

---

## Summary

| Issue | Problem | Fix |
|---|---|---|
| 1. Medicines not displaying | Hardcoded company ID in seeder | Dynamic lookups |
| 2. Import button broken | No page/route existed | Created import page + route |
| 3. Inventory data wrong | Field name mismatch | Rewrote controller + page |
| 4. Sales status column | Non-existent column referenced | Removed from type, fixed display |
| 5. Schedule drug info | No descriptions, wrong casing | Added descriptions, normalized lowercase |
| 6. Data seeder issues | Multiple mismatches | Fixed all, fresh migration successful |

---

*Report generated: May 28, 2026*
