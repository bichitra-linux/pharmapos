# PharmaPOS Release Audit — 2026-08-14

**Scope:** Items 1–10 of the release checklist. Env files untouched; deployment actions documented for DevOps (see `DEPLOY_CPANEL.md`).
**Baseline:** May 2026 audits (90 findings) + fresh deep audit (Aug 2026, 6 parallel explore agents).
**Final verification:** `php artisan test` 97 passed / 1 pre-existing skip · `tsc --noEmit` 0 errors · `vite build` ✓ · `route:list` 169 routes.

---

## 1. Missing pages / routing

| Finding | Fix |
|---|---|
| `/super-admin/tenants/:id/edit` button → no route (silent redirect to dashboard) | Removed the button (`tenants/show.tsx`) |
| `/medicines/labels` `window.open` → matched `medicines/:id` → API 404 | Removed the button (no labels endpoint exists) |
| 4 dead routes unreachable from UI: `/inventory/adjustments`, `/suppliers/:id/ledger`, `/returns/create`, `/customers/:id` | Wired: Adjustments button on Inventory, row-click → ledger on Suppliers (with Edit on ledger page), New Return button, row-click → customer show (Edit button on show) |
| Verified clean | 61 router paths ↔ 61 page files; sidebar/header/super-admin nav all resolve |

## 2. Link & button redirects

Full sweep of `navigate(`, `href`, `window.location`, `Link`, post-action navigations — all destinations verified against registered routes after the fixes above. No hash/placeholder links.

## 3. Authentication flow

| Severity | Finding | Fix |
|---|---|---|
| HIGH | Tenant suspension was a silent no-op (`suspended_at`/`suspension_reason` missing from `Company::$fillable`) | Added to `$fillable`; `activate()` also re-enables `is_active` |
| HIGH | Self-registration dead-end: new company had no `subscription_expires_at` → every request 403 | 14-day trial expiry seeded at register |
| HIGH | Impersonation broken end-to-end; cross-tenant user could be picked; no revoke | Interceptor falls back to `impersonation_token`; impersonation target scoped to the tenant (`withoutGlobalScopes + where company_id`); new `POST /api/super-admin/impersonation/stop` revokes the token; exit/logout clears keys |
| MED | Login 401 → interceptor bounced before error could render | 401 on `/auth/login` no longer redirects; message normalized |
| MED | `changePassword` left other sessions valid | Other tokens revoked |
| MED | `switchOutlet` accepted inactive outlets | `is_active` check added |
| MED | Suspended 403 lacked reason; login page ignored `?suspended=` | `EnsureCompanyIsActive` + login return `suspension_reason`; login page renders a banner |
| LOW | `CheckPermission` owner check compared enum to string (dead code) | `=== UserRole::Owner` |
| Notes | Deactivated user / expired subscription / suspended now return 403 with actionable messages; wrong password stays 401 (anti-enumeration) |

## 4. Maps / data mapping (API ↔ UI)

All endpoints aligned to one documented shape. Backend-first fixes:

| Feature | Before | After |
|---|---|---|
| **Reports (12 endpoints)** | Raw aggregates + `date_from/date_to`; all 7 pages rendered empty | `{headers, rows, totals}` + `from/to` + `group_by day/week/month`; all pages render |
| **Supplier ledger** | Object piped through `extractPaginatedData` → always empty | Correct shape; payments join `purchase_number`; balance displayed |
| **Purchase receive** | Empty body / wrong key → always 422 | `purchase_item_id`+`received_quantity` sent (remaining qty); invalidates cache; 422 with message |
| **Prescription dispense** | Wrong keys → always 422 | `prescription_item_id`/`quantity_dispensed`; filters already-dispensed items |
| **Returns create** | `items: []`, no refund → always 422 | Rebuilt page: loads sale/purchase items, quantity inputs, auto refund amount, real payload |
| **Payment initiate** | `/payments/esewa` (404) | `/payments/{gateway}/initiate` |
| **Payment methods** | Hardcoded IDs (wrong mapping); new tenants had none → POS sale 422 | POS modal fetches `GET /payment-methods` (constants as fallback); 9 methods seeded on register |
| **Roles** | UI `manager` → 422; `inventory_manager` → 500; enum drift | Single source: `owner,admin,pharmacist,cashier,inventory_staff` (UI list, validation, enum, TS type) |
| **Super-admin** | Plans consumed as array → always "No plans yet"; revenue filtered `completed` (impossible) → always 0; tenant payments never loaded | Plans via `extractPaginatedData`; revenue uses `active/expired`; tenant show includes recent payments |
| **Settings round-trip** | `city/state/country/local_level/registration_number/google_maps_link` silently dropped on save; `city`/`phone_country_code` columns missing | Columns added (migration); `CompanyController` persists all fields; `SettingController` accepts all frontend keys |
| **Sale items bug (found via tests)** | Multi-item sales created **corrupt rows** (item 2 linked to item 1's batch) — `$batchesUsed` accumulated across items | Per-item batch scoping; sale_items now correct per batch |
| Misc | Purchase detail ₹0.00 prices; adjustments phantom columns; manufacturer dropdowns empty; invoice cashier `-`; held-sale lost discounts; `vat_percentage` hardcoded 13; purchase discounts dropped; medicine search missing fields; prescription `hospital_name`/`diagnosis` dropped; `topMedicines` limit ignored | All fixed (accessors, response fields, cart resume preserves discounts, configured VAT rate, discount accumulated into bill discount, search map + manufacturer/piece fields, persisted, limit honored) |

## 5. Vulnerabilities

| Severity | Finding | Fix |
|---|---|---|
| CRITICAL | Payment callback never verified amount (₹1 attack with victim invoice as `pid`) | Gateway amount compared to `sale.total_amount` (ε 0.01); mismatch → 400; `transaction_id` persisted (new column) |
| CRITICAL | `subscribe()` without gateway = free plan activation | Gateway now required; direct-activation branch removed |
| HIGH | Gateway secrets (`config`) returned to tenants | Removed from `subscribe()` response; tenant endpoints select explicit columns |
| HIGH | `manage_users` ⇒ owner escalation | Only owner may create/modify owner accounts |
| HIGH | `.env.example` commits real password pattern | **Reported only** — dev team blanks + rotates (no env edits per instruction) |
| MED | Cross-outlet stock adjustment; `pending` status not in enum (500); purchases `partial` status not in enum (500) | Outlet check via delta math; enum extended in migration |

Verified clean: mass assignment (`$fillable` everywhere), tenant isolation (CompanyScope + explicit guards), SQLi (all raw usages static), XSS (escaped), rate limiting, upload validation.

## 6. Security

- Suspension control restored (3), impersonation hardened (3), owner-role guard (5).
- Test suite now exercises auth security paths (deactivated/expired/suspended → 403, wrong password → 401).
- `DEPLOY_CPANEL.md` covers `SESSION_SECURE_COOKIE=true`, smtp mailer, Redis credentials — DevOps-applied.

## 7. Known issues

- **PurchasePolicy missing** → 403 on every purchase create: added policy + registered.
- **AuditableObserver FK bug** → super-admin tenant create/suspend 500 (SuperAdmin id into `users` FK): now nulls non-User actors.
- **Test suite**: 21 failing → **0** (stale contract-drift tests updated to current contracts; real bugs fixed in code).
- Dirty working tree reviewed (observer bug folded in). **No commits made.**
- Remaining backlog: COGS uses current (not historical) price; audit logging breadth; CSP; skeleton-loader coverage; duplicate `settings`/`company` split; med labels feature (button removed).

## 8. Error codes

- Business failures in sale store, purchase receive, stock adjustments → **422 with real messages** (was bare 500 "Server Error").
- `subscription_payments.status='pending'` + purchases `'partial'` enum violations fixed via migration.
- Super-admin health 503 now surfaces the degraded banner (was swallowed); poll no longer spams silently.
- Duplicate-medicine "Create anyway?" now actually creates (`force_create` flag).
- Verified: no 200-for-error, no `res.status !== 200` checks, 201s consumed correctly.

## 9. cPanel deployment (Redis available) — DevOps handoff

See `DEPLOY_CPANEL.md`. Verdict: **deployable** — `REDIS_CLIENT=predis` (pure PHP, no extension), Redis drivers keep working. DevOps must: set real env values (URL, DB, Redis, SMTP, secure cookie), upload prebuilt `vendor/` + `public/build` (gitignored), run migrations/storage:link, add the two cron entries, verify PHP 8.3+ and extensions. Subdomain-root install only.

## 10. Feature / UI-UX modernization

- **Auth pages**: brand-blue consistency, password visibility toggles, register is two-column with password guidance, errors `role="alert"`, back-to-landing links, actionable copy (14-day trial), restricted-area notice on super-admin login, `?suspended=` banner.
- **Landing**: verified already rebuilt (no fake logos, static workflow, server-rendered pricing) — no changes needed.
- **App shell**: sidebar role-gated (cashiers/pharmacists see only their modules); notification bell now shows real expiry + low-stock alerts.
- **POS**: prescription modal already on Dialog; BS dates already on invoice; held-sale discounts preserved (cart fix).
- **Dead features**: `welcome.blade.php` (unreachable) deleted; Print Labels button removed (no endpoint); unused code left as-is where harmless.

---

## Deliverables

- `RELEASE_AUDIT_2026-08.md` (this file)
- `DEPLOY_CPANEL.md` — DevOps deployment pack (env key=value table, extensions, cron, verification)
- One new migration: `2026_08_14_000001_add_transaction_id_and_pending_subscription_status.php` (sales `transaction_id`; subscription status +`pending`; purchases status +`partial`; companies +`city`/`phone_country_code`)

### 2026-08-14 — Brand swap (addendum)

- New logo PNG (green cross + navy "Pharma" + green "POS" wordmark) placed at `resources/images/pharmapos-logo.png` and `public/pharmapos-logo.png` (latter survives `vite build`); legacy empty `public/favicon.ico` overwritten with the same PNG (PNG-as-ICO, accepted via `<link rel="icon" type="image/png">`).
- New `resources/js/components/ui/brand-logo.tsx` (`mark` / `wordmark` / `full` variants) imported into the app shell sidebar, super-admin sidebar, login, register, and super-admin login.
- New CSS tokens `--color-brand-green-*` and `--color-brand-navy-*` added to `resources/css/app.css`; landing blade palette tinted toward navy + green to match the new mark while keeping the warm character.
- Invoice PDF (`resources/views/invoice.blade.php`) renders the logo via in-template base64 embed (no dompdf `chroot` config change required).
- README rewritten with the new logo and project description.
- No env files touched; no deploy actions taken.

## Backlog (deferred, documented)

1. COGS at historical batch price (currently current price)
2. Audit logging beyond Company/User/Outlet
3. CSP header
4. Skeleton-loaders on remaining pages (component exists)
5. Medicine barcode label printing (button removed; endpoint never existed)
6. Password reset / email verification flows
7. CORS origin pinning in production config
