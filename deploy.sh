#!/bin/bash
# PharmaPOS cPanel Server Deploy Script
# Run this on the cPanel server AFTER uploading the codebase
# Usage: bash deploy.sh

set -euo pipefail

echo "=== PharmaPOS cPanel Deploy ==="
echo ""

# 1. Verify PHP version
echo "[1/8] Checking PHP version..."
php -v | head -1
if [ "$(php -r 'echo PHP_MAJOR_VERSION;')" -lt 8 ] || [ "$(php -r 'echo PHP_MINOR_VERSION;')" -lt 3 ]; then
    echo "ERROR: PHP 8.3+ required. Set it in cPanel MultiPHP Manager first."
    exit 1
fi
echo "OK"
echo ""

# 2. Create .env from template
echo "[2/8] Creating .env from template..."
if [ -f ".env.production" ]; then
    if [ -f ".env" ]; then
        echo "WARNING: .env already exists. Skipping copy."
    else
        cp .env.production .env
        echo ".env created from .env.production"
        echo ">>> IMPORTANT: Edit .env with your real DB/Redis credentials <<<"
        echo ">>> Then run: nano .env <<<"
    fi
else
    echo "WARNING: .env.production not found. Copy .env.example manually."
fi
echo ""

# 3. Generate APP_KEY
echo "[3/8] Generating APP_KEY..."
if [ -f ".env" ] && grep -q "^APP_KEY=$" .env 2>/dev/null; then
    php artisan key:generate --force
    echo "APP_KEY generated"
else
    echo "APP_KEY already set or .env missing. Skipping."
fi
echo ""

# 4. Storage link
echo "[4/8] Creating storage symlink..."
php artisan storage:link --force 2>/dev/null && echo "OK" || echo "Symlink skipped (may not be supported)"
echo ""

# 5. Run migrations
echo "[5/8] Running migrations..."
php artisan migrate --force
echo "OK"
echo ""

# 6. Seed required data
echo "[6/8] Seeding required data..."
php artisan db:seed --class=SubscriptionPlanSeeder --force 2>/dev/null || true
php artisan db:seed --class=LandingPageSeeder --force 2>/dev/null || true
php artisan db:seed --class=PaymentGatewaySeeder --force 2>/dev/null || true
php artisan db:seed --class=SuperAdminSeeder --force 2>/dev/null || true
echo "Seeders run. Default super admin: superadmin@pharmpos.com / password"
echo ">>> CHANGE THIS PASSWORD IMMEDIATELY AFTER LOGIN <<<"
echo ""

# 7. Cache optimization
echo "[7/8] Caching config, routes, views..."
php artisan config:cache || echo "config:cache failed — check .env values"
php artisan route:cache || echo "route:cache failed — check routes"
php artisan view:cache
echo "OK"
echo ""

# 8. Set permissions
echo "[8/8] Setting permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
touch storage/logs/laravel.log
chmod 664 storage/logs/laravel.log 2>/dev/null || true
if [ -f ".env" ]; then
    chmod 640 .env
fi
echo "OK"
echo ""

echo "=== Deploy complete ==="
echo ""
echo "Next steps:"
echo "  1. Set up cron jobs in cPanel:"
echo "     * * * * * cd $(pwd) && php artisan schedule:run >> /dev/null 2>&1"
echo "     * * * * * cd $(pwd) && php artisan queue:work --stop-when-empty --max-time=50 --tries=1 >> /dev/null 2>&1"
echo "  2. Visit https://subdomain.abc.com/up to verify health"
echo "  3. Login at /super-admin/login and change the default password"
echo "  4. If the SPA loads blank, re-run: npm ci && npm run build"
