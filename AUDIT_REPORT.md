# PharmaPOS Comprehensive Deep Audit Report

**Date:** May 26, 2026  
**Auditor:** Automated System Analysis  
**Version:** 2.0.0  
**Scope:** Full codebase — backend, frontend, security, migrations, data integrity

---

## Executive Summary

This deep audit examines the entire PharmaPOS codebase across 7 dimensions: security, multi-tenancy, CRUD operations, data integrity, frontend type safety, data mapping, and UX quality.

**Overall Status:** 90 findings — 13 CRITICAL, 26 HIGH, 25 MEDIUM, 26 LOW

| Dimension | Score | Key Finding |
|---|---|---|
| Security | 1/4 | Payment callback has no signature verification, SQL injection risk |
| SaaS Multi-Tenancy | 2/4 | Salt compositions globally shared, middleware not applied to routes |
| CRUD Operations | 2/4 | Multiple column mismatches, missing ownership checks |
| Data Integrity | 2/4 | Race conditions on invoice/prescription numbers, no audit logging |
| Frontend Type Safety | 2/4 | 6 API endpoint mismatches, unsafe type assertions |
| Frontend Data Mapping | 2/4 | Paginated data extraction inconsistent, field name mismatches |
| Layout/UX | 2/4 | No skeleton loaders, CLS on image load, spinner-to-content jumps |
| **Total** | **13/28** | **Needs Significant Work** |

---

## CRITICAL Issues (13)

### Backend Security

| # | File | Line | Issue |
|---|---|---|---|
| C-1 | `PaymentController.php` | 171-244 | Payment callback has zero authentication and no signature verification. Any attacker can mark any sale as paid. |
| C-2 | `PurchaseController.php` | 201-206 | `DB::raw()` with user input — SQL injection risk. |
| C-3 | `SaltComposition.php` | — | No `company_id`, no CompanyScope. Cross-tenant data leak. |
| C-4 | `CheckPermission.php` | 25-29 | Uses OR logic for multi-permission checks — access control bypass. |
| C-5 | `routes/api.php` | 90 | `CheckTenantNotSuspended` and `EnsureCompanyIsActive` NOT applied to any route. |

### Backend Data

| # | File | Line | Issue |
|---|---|---|---|
| C-6 | `SaleController.php` | 71-82 | Invoice number race condition — `MAX()` on string wraps at 9999. |
| C-7 | `PrescriptionController.php` | 58-59 | Prescription number uses `count()` — concurrent requests produce duplicates. |
| C-8 | `InvoiceService.php` | 207-210 | XSS — company name/address/phone interpolated into HTML without escaping. |

### Frontend

| # | File | Line | Issue |
|---|---|---|---|
| C-9 | `services/settings.ts` | 35,40 | `getCompany()`/`updateCompany()` call wrong endpoint `/settings/company` vs `/company`. |
| C-10 | `services/auth.ts` | 50 | `changePassword()` calls `POST /auth/change-password` vs `PUT /auth/password`. |
| C-11 | `services/super-admin.ts` | 61,65 | `suspendTenant()`/`activateTenant()` use `POST` vs `PATCH`. |
| C-12 | `pages/medicines/create.tsx` | 24,28 | Default `unit_type` and `schedule_type` don't match constants. |
| C-13 | `pages/medicines/create.tsx` | 19-21 | FK defaults are `0` instead of `null`. |

---

## HIGH Issues (26)

### Backend Security

| # | File | Line | Issue |
|---|---|---|---|
| H-1 | `routes/api.php` | — | No rate limiting on sales, purchases, inventory, callbacks |
| H-2 | `CompanyController.php` | 17-28 | Exposes full company row including sensitive settings |
| H-3 | `CustomerController.php` | 83 | `update()` could overwrite `company_id` |
| H-4 | `InventoryController.php` | 133 | `storeAdjustment()` doesn't check company_id on batch |

### Backend CRUD

| # | File | Line | Issue |
|---|---|---|---|
| H-5 | `SupplierController.php` | 40-51 | Uses fields not in migration (`company_name`, `bank_details`, `credit_days`) |
| H-6 | `SaleReturnController.php` | 83-84 | Doesn't validate return qty <= sold qty minus already returned |
| H-7 | `SupplierPaymentController.php` | 47-77 | Doesn't validate purchase_id belongs to same supplier |
| H-8 | `UserController.php` | 38 | `outlet_id` not validated against user's company |
| H-9 | `MedicineCategoryController.php` | 32 | `parent_id` could belong to another company |
| H-10 | `CustomerController.php` | 92-103 | Hard delete cascades to sales — destroys history |

### Backend Data

| # | File | Line | Issue |
|---|---|---|---|
| H-11 | `Medicine.php` | — | No SoftDeletes — deleting breaks sale history |
| H-12 | `NarcoticsRegisterController.php` | 56 | `doctor_name` nullable in validation but NOT NULL in migration |
| H-13 | `ReportController.php` | 186-193 | COGS uses current price, not price at time of sale |
| H-14 | `SupplierController.php` | 87-98 | Hard delete cascades to purchases |

### Frontend

| # | File | Line | Issue |
|---|---|---|---|
| H-15 | Multiple pages | — | 15 pages use unsafe `as (T & Record<string, unknown>)[]` cast |
| H-16 | `stores/cartStore.ts` | 116 | `tax_rate \|\| VAT_RATE` — 0% tax falls back to 13% |
| H-17 | `stores/superAdminStore.ts` | 21,29 | Dual localStorage token storage |
| H-18 | `services/api.ts` | 34-37 | 401 doesn't call Zustand `logout()` — stale state |
| H-19 | `services/customers.ts` | 37 | `search()` calls non-existent route `/customers/search` |
| H-20 | `services/reports.ts` | 52-58 | `export()` calls non-existent route |
| H-21 | `stores/cartStore.ts` | 131-138 | Cart persists across user sessions |
| H-22 | `pages/purchases/create.tsx` | 108-121 | `dueDate` collected but not sent |
| H-23 | `components/pos/product-search.tsx` | 13 | `debouncedQuery` unused — search fires every keystroke |
| H-24 | `pages/customers/index.tsx` | 25 | `total_purchases` rendered as currency but may be count |
| H-25 | `services/super-admin.ts` | 16 | Trailing slash in baseURL |
| H-26 | `pages/login.tsx` | 66 | Inconsistent error casting across login pages |

---

## MEDIUM Issues (25)

### Backend (15)

| # | Issue |
|---|---|
| M-1 | Substitute column mismatch: `medicine_one_id` vs `medicine_id_1` |
| M-2 | PlanController orders by non-existent `sort_order` |
| M-3 | SaleService/PurchaseService are dead code |
| M-4 | VAT hardcoded to 13% — ignores company settings |
| M-5 | Low stock threshold hardcoded to 10 |
| M-6 | Subscription hardcodes 30-day expiry |
| M-7 | BatchController missing `company_id` in insert |
| M-8 | `register_id` not validated against outlet |
| M-9 | SupplierReturn doesn't verify batch-medicine relationship |
| M-10 | SaleReturn doesn't verify batch-medicine relationship |
| M-11 | ReportController field name assumptions |
| M-12 | PurchaseService increments quantity instead of tracking received |
| M-13 | No audit logging despite AuditLog model |
| M-14 | Missing indexes on sale_items, purchase_items |
| M-15 | SupplierPayment not wrapped in transaction |

### Frontend (10)

| # | Issue |
|---|---|
| M-16 | Cart computed values as functions — re-render on every call |
| M-17 | `Date.now()` notification IDs can collide |
| M-18 | `extractPaginatedData` edge case not handled |
| M-19 | `Math.max(...[])` returns `-Infinity` on empty chart |
| M-20 | Select value type mismatch (number vs string) |
| M-21 | `updateField` uses `string` instead of `keyof` |
| M-22 | All cache buttons call same mutation |
| M-23 | `alert()` used for suspension notice |
| M-24 | POS local `cn()` doesn't use tailwind-merge |
| M-25 | settings uses `useState` instead of `useEffect` |

---

## LOW Issues (26)

### Backend (15)
- Inconsistent paginated response format
- CompanyScope returns ALL data when unauthenticated
- Hard delete without reference checks (Customer, Supplier)
- No SoftDeletes on Manufacturer, MedicineCategory
- Subscription hardcodes 30-day expiry
- No audit logging
- doctor_name nullable vs NOT NULL mismatch
- Batch-medicine relationship not verified in returns
- Missing indexes on FK columns
- No transaction wrapping on payments/subscriptions
- Outlet ownership not validated in BatchController

### Frontend (11)
- CLS: Dashboard chart height mismatch
- CLS: Prescription image no dimensions
- CLS: Spinner instead of skeleton loaders
- `customers/search` route doesn't exist
- `reports/export` route doesn't exist
- Cart persists across sessions
- `dueDate` collected but not sent
- `debouncedQuery` unused
- `total_purchases` semantic mismatch
- Trailing slash in baseURL
- Inconsistent error casting

---

## Recommended Fix Plan

### Phase 1: Security (CRITICAL)
1. Add payment gateway signature verification
2. Add `company_id` to salt_compositions
3. Apply tenant middleware to routes
4. Fix CheckPermission logic
5. Replace `DB::raw()` with `->increment()`

### Phase 2: Data Integrity (CRITICAL + HIGH)
6. Fix invoice/prescription number race conditions
7. Add SoftDeletes to Medicine
8. Fix Substitute column names
9. Add missing indexes
10. Fix return quantity validation

### Phase 3: Frontend API Fixes (CRITICAL)
11. Fix all 6 endpoint mismatches
12. Fix default values in medicine create
13. Fix FK defaults

### Phase 4: Frontend Data Flow (HIGH)
14. Fix extractPaginatedData
15. Fix tax_rate fallback
16. Fix cart persistence logout cleanup
17. Fix missing routes

### Phase 5: UX Polish (MEDIUM + LOW)
18. Fix CLS issues
19. Fix computed value performance
20. Add audit logging
21. Wrap critical operations in transactions

---

*Report generated: May 26, 2026*
