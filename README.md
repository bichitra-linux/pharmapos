<p align="center">
    <img src="resources/images/pharmapos-logo.png" width="320" alt="PharmaPOS">
</p>

<p align="center"><strong>PharmaPOS</strong> &mdash; Smart Pharmacy, Simplified</p>

<p align="center">A cloud-based pharmacy Point-of-Sale, inventory and compliance platform built for Nepali pharmacies.</p>

---

## Overview

PharmaPOS helps pharmacies run a clean counter: batch-tracked inventory, expiry alerts, prescription and schedule-H / H1 / X handling, narcotics register, VAT + PAN invoicing, eSewa / Khalti / Fonepay payments, and a multi-outlet ledger &mdash; from a single Laravel + React app.

## Features

- **Inventory & batch tracking** &mdash; every unit traced by batch, expiry, manufacturer and reorder level; FIFO dispensing; auto-deactivation on zero stock.
- **Pharmacy workflow** &mdash; walk-in &amp; online sales, prescription capture, schedule-aware dispense with pharmacist override, partial dispense and refunds.
- **Compliance** &mdash; VAT (13%), PAN invoicing, narcotics register, audit logs, Bikram Sambat calendar support.
- **Payments** &mdash; cash, card, credit, eSewa, Khalti, Fonepay, ConnectIPS &mdash; with split payments per sale.
- **Reports** &mdash; sales, purchases, inventory, expiry, profit &amp; loss, VAT, narcotics, dead-stock, supplier / customer due.
- **Multi-outlet** &mdash; per-outlet stock, centralised reporting, role-based access (owner / admin / pharmacist / cashier / inventory_staff).
- **Super-admin platform** &mdash; tenant management, plans, subscriptions, system health.

## Stack

- **Backend** &mdash; Laravel 13, PHP 8.3+, MySQL 8, Redis (predis)
- **Frontend** &mdash; React 19, Vite, Tailwind CSS v4, Zustand, react-query
- **Auth** &mdash; Laravel Sanctum (SPA tokens)

## Project layout

```
pharmapos/
├── app/                # Laravel app (controllers, models, services, observers, actions)
├── database/migrations/ # schema
├── resources/js/       # React SPA (pages, components, hooks, stores, services)
├── resources/css/      # Tailwind v4 + design tokens (OKLCH)
├── resources/views/    # Blade (landing, invoice, app shell)
├── public/build/       # Vite output (gitignored; ships via release artifact)
├── routes/             # api + web
└── tests/              # PHPUnit
```

## Getting started

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve   # or use the composer `dev` script
```

## Deployment

See **[DEPLOY_CPANEL.md](DEPLOY_CPANEL.md)** for the DevOps handoff (cPanel + Redis shared hosting).

## Documentation

- **[RELEASE_AUDIT_2026-08.md](RELEASE_AUDIT_2026-08.md)** &mdash; audit findings and fixes per release item.
- **[PLAN.md](PLAN.md)** &mdash; the original product conversion plan.
- **[DESIGN.md](DESIGN.md)** &mdash; design system (colors, typography, components).

## License

MIT (see upstream Laravel license references where applicable).