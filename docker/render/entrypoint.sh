#!/usr/bin/env bash
set -e

echo "==> Memulai konfigurasi runtime Laravel..."

# Jalankan package discovery jika belum
php artisan package:discover --ansi || true

# Generate cache & storage symlink
php artisan storage:link || true
php artisan optimize:clear || true

# Jalankan migrasi jika database terhubung
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "==> Menjalankan migrasi database..."
    php artisan migrate --force || true
fi

# Cache config & routes di production
if [ "$APP_ENV" = "production" ]; then
    echo "==> Mengoptimalkan cache Laravel..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

echo "==> Menjalankan PHP-FPM di background..."
php-fpm -D

echo "==> Menjalankan Nginx..."
exec nginx -g "daemon off;"
