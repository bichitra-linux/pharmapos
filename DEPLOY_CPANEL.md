# PharmaPOS — cPanel Shared-Hosting Deployment Guide (DevOps Handoff)

**App:** PharmaPOS (Laravel 13.12 + React 19 SPA, Vite/Tailwind v4)
**Date:** 2026-08-14
**Environment target:** cPanel shared hosting **with Redis support**
**Owner:** DevOps team. The Dev team does **not** perform deployment actions or edit env files.

---

## 1. Compatibility verdict

The app is **deployable to cPanel shared hosting with Redis** with the checklist below.
No code changes are required. The production env template already uses:

- `REDIS_CLIENT=predis` — pure-PHP Redis client, **no phpredis extension needed**
- `SESSION_DRIVER=redis`, `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`
- `APP_DEBUG=false`

---

## 2. Env values to set in production (Dev team will NOT edit env files — apply these)

| Key | Value to set | Notes |
|---|---|---|
| `APP_URL` | `https://<real-domain>` | Must match the deployed domain exactly |
| `APP_KEY` | generated via `php artisan key:generate` | Empty in template by design |
| `DB_HOST` / `DB_PORT` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | cPanel MySQL credentials | `cpaneluser_pharmapos` etc. |
| `REDIS_HOST` / `REDIS_PORT` / `REDIS_PASSWORD` | host Redis values | Some hosts use a socket path instead of TCP — check with provider |
| `MAIL_MAILER` | `smtp` | Currently `log` → zero emails sent |
| `MAIL_HOST` / `MAIL_PORT` / `MAIL_USERNAME` / `MAIL_PASSWORD` | cPanel mail or external SMTP | |
| `SANCTUM_STATEFUL_DOMAINS` | the real domain | Bearer-only auth, but keep in sync |
| `SESSION_SECURE_COOKIE` | `true` | HTTPS only |
| `LOG_STACK` | `daily` (optional) | `single` grows unbounded |

---

## 3. Pre-deploy build steps (do these locally/CI, then upload)

1. **PHP 8.3+ required.** Verify in MultiPHP Manager (`php ^8.3` in composer.json; Laravel 13 hard floor). If the host caps at 8.2, the deploy cannot proceed.
2. **Required PHP extensions** (enable in MultiPHP / PHP INI editor):
   - `pdo_mysql`, `openssl`, `tokenizer`, `ctype`, `xml`, `fileinfo`, `curl`, `mbstring`, `dom` (dompdf), `gd` (recommended for dompdf rendering)
   - Redis client is `predis` (pure PHP) — **no `redis` extension required**
3. **Build assets locally** (Node is not available on shared hosting):
   ```
   npm ci
   npm run build
   ```
   The output `public/build/` **must be uploaded** (it is gitignored; on a fresh clone `@vite` throws and the SPA won't load). Commit it via `git add -f public/build` or ship it in the artifact. The brand assets (`public/pharmapos-logo.png` and `public/favicon.ico`) are included with the build output.
4. **Install vendor locally** on a PHP 8.3 machine and upload prebuilt (no composer on cPanel):
   ```
   composer install --no-dev --optimize-autoloader --no-scripts
   ```
5. Upload everything to the **subdomain/domain docroot** (e.g. `public_html/pharmapos` for `pos.pharmacy.com`). The app hardcodes `/api`, `/login`, `/build/...` paths — **subdirectory installs under an existing domain are not supported**. Use a dedicated subdomain or document root.
6. `storage/` and `bootstrap/cache/` must be writable by the PHP user: `chmod 775` (deploy.sh already does this).

---

## 4. Deploy steps (SSH as account user)

```
php artisan key:generate
php artisan migrate --force
php artisan storage:link          # verify symlink allowed; if forbidden, uploads under /storage 404
php artisan config:cache
php artisan route:cache           # note: may fail due to Closure route — non-fatal, skip if it errors
php artisan view:cache
php artisan optimize
```

- `storage:link`: if the host forbids symlinks, company logos and prescription images will 404 under `/storage/...`. Verify after deploy; alternative is a real directory + rewrite rule.
- First-run seeding: `php artisan db:seed --class=SuperAdminSeeder` then **change the default super-admin password immediately** (default credentials are `superadmin@pharmpos.com / password`).

---

## 5. Cron jobs

Add via cPanel → Cron Jobs (PHP-CLI):

```
* * * * * php-cli /home/<user>/<docroot>/artisan schedule:run >> /dev/null 2>&1
* * * * * php-cli /home/<user>/<docroot>/artisan queue:work --stop-when-empty --max-time=55 >> /dev/null 2>&1
```

- The app currently dispatches **no queued jobs** — the `queue:work` entry is future-proofing and harmless. The `--stop-when-empty`/`--max-time` guard prevents a stuck worker loop on shared hosting.
- There is no scheduled-task code yet; `schedule:run` is future-proofing.

---

## 6. Post-deploy verification

| Check | Command / action |
|---|---|
| PHP version | `php -v` → 8.3+ |
| Extensions | `php -m` → `pdo_mysql, mbstring, dom, fileinfo, curl` |
| Redis | `php artisan tinker --execute="echo Cache::store()->put('t','1',5) ? 'ok' : 'fail';"` |
| Routes | `php artisan route:list` |
| Migrations | `php artisan migrate:status` → all ran |
| Storage link | `ls -la public/storage` → symlink to `storage/app/public` |
| SPA assets | `curl -I https://<domain>/build/manifest.json` → 200 |
| Health | Login to super-admin → System page shows all checks ok |

---

## 7. Known constraints / notes

- **Subdomain-root only** (hardcoded `/api`, `/login`, `/build`). Subdirectory hosting requires Vite `base`, relative API URLs, and `SESSION_PATH` changes — not supported today.
- `barryvdh/laravel-dompdf` is unpinned (`"*"`) — recommend pinning to a fixed version for reproducible deploys (dev team decision).
- Invoice PDFs accumulate in `storage/app/private` — add housekeeping later.
- `laravel/tinker` is in `require` (prod) — harmless, could move to `require-dev`.
- Rate limits: login 5/min, register 3/min, callbacks 20/min, authed API 120/min — fine for shared hosting.

---

*Prepared by Dev team audit (2026-08-14). Execution is DevOps-owned.*
