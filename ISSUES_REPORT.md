# PharmaPOS Issues Report

**Date:** May 28, 2026  
**Scope:** Super Admin Panel, Admin Dashboard, Database/Schema  
**Total Issues:** 21 + 66 database issues = 87

---

## Category 1: Super Admin Issues (6)

| # | Issue | Severity | Root Cause |
|---|---|---|---|
| SA-1 | Dashboard doesn't display data properly | Medium | `Company::withoutGlobalScopes()` bypasses soft deletes |
| SA-2 | Tenants action dropdown overflows table | High | Dropdown uses `position: absolute` inside `overflow: hidden` table cell |
| SA-3 | Subscriptions `.split()` TypeError | Critical | `sub.expires_at` is undefined, should be `sub.subscription_expires_at` |
| SA-4 | Payments page data wrong | High | Field mismatch: `total` vs `total_revenue`, `this_month` vs `current_month` |
| SA-5 | Settings `forEach` TypeError | Critical | API returns object, frontend expects array |
| SA-6 | System page data incorrect | High | API returns nested `{ checks: { database: { status } } }`, frontend reads flat `health?.database` |

## Category 2: Admin Dashboard Issues (11)

| # | Issue | Severity | Root Cause |
|---|---|---|---|
| AD-1 | Notifications button no function | Medium | No `onClick` handler on Bell button |
| AD-2 | Search doesn't route to data | Medium | Navigation works but medicine show page may not load |
| AD-3 | POS prescription modal ugly | Low | Uses inline div instead of Dialog component |
| AD-4 | Need Nepali dates in invoice | Medium | No Bikram Sambat calendar support |
| AD-5 | Purchases table Invoice Date/Total wrong | High | `invoice_date` should be `purchase_date`, `total_amount` should be `total` |
| AD-6 | Customers table Purchases/Balance wrong | High | `total_purchases` and `outstanding_balance` don't exist in DB |
| AD-7 | Suppliers table Balance wrong | High | `outstanding_balance` doesn't exist in DB |
| AD-8 | Returns Customer Returns: Customer/Status wrong | High | Customer not eager-loaded, `status` column doesn't exist |
| AD-9 | Returns Supplier Returns: Purchase/Supplier/Status wrong | High | Raw query returns flat fields, frontend expects nested; `status` should be `refund_status` |
| AD-10 | Narcotics register Date/Medicine wrong | High | `item.date` should be `created_at`, `item.medicine?.brand_name` should be flat `item.brand_name` |
| AD-11 | Settings form needs new fields | Medium | Missing country, state, local_level, registration_number, google_maps_link |

## Category 3: Database/Schema Issues (66)

| # | Issue | Count | Severity |
|---|---|---|---|
| DB-1 | N+1 Queries | 16 | High |
| DB-2 | Race Conditions | 6 | Critical |
| DB-3 | Missing Indexes | 43 | High |
| DB-4 | Missing Tables/Columns | 7 | High |

---

*Report generated: May 28, 2026*
