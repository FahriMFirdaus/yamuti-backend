#!/usr/bin/env bash
set -e

echo "==> Memperbaiki permission storage & bootstrap/cache..."
mkdir -p /var/www/storage/logs /var/www/storage/framework/sessions /var/www/storage/framework/views /var/www/storage/framework/cache
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 777 /var/www/storage /var/www/bootstrap/cache

echo "==> Memulai konfigurasi runtime Laravel..."

# Jalankan package discovery jika belum
php artisan package:discover --ansi || true

# Generate cache & storage symlink
php artisan storage:link || true
php artisan optimize:clear || true

# Jalankan migrasi & seeder jika database terhubung
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "==> Menjalankan migrasi database..."
    php artisan migrate --force || true

    if [ "$RUN_SEEDERS" = "true" ]; then
        echo "==> Menjalankan database seeder..."
        php artisan db:seed --force || true
    fi
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
