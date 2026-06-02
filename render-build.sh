#!/usr/bin/env bash
# Hentikan eksekusi jika terjadi error
set -o errexit

echo "Mulai proses build untuk Render..."

# Install dependencies (hanya untuk production)
composer install --optimize-autoloader --no-dev

# Bersihkan semua cache lama
php artisan optimize:clear

# Buat cache baru khusus untuk production agar lebih cepat
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Jalankan migrasi database secara paksa (tanpa prompt yes/no)
php artisan migrate --force
