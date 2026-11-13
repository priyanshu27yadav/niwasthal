#!/usr/bin/env bash
set -e

PORT="${PORT:-8080}"
sed -ri "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -ri "s/:80>/:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache public/uploads/qr
chown -R www-data:www-data storage bootstrap/cache public/uploads

# Ensure valid 32-byte Laravel APP_KEY (uses persistent RENDER_SECRET_KEY if provided)
if [ -z "${APP_KEY:-}" ]; then
    if [ -n "${RENDER_SECRET_KEY:-}" ]; then
        export APP_KEY="base64:$(printf '%s' "${RENDER_SECRET_KEY}" | openssl dgst -sha256 -binary | base64)"
    else
        export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
    fi
fi

if [ -z "${APP_URL:-}" ] && [ -n "${RENDER_EXTERNAL_URL:-}" ]; then
    export APP_URL="${RENDER_EXTERNAL_URL}"
fi

php artisan package:discover --ansi || true

# Wait briefly for managed DB readiness on initial Blueprint boot
for i in 1 2 3 4 5; do
    if php artisan migrate --force; then
        break
    fi
    echo "Database not ready yet, retrying migration (${i}/5)..."
    sleep 3
done

php artisan config:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec apache2-foreground
