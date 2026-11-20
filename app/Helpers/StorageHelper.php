<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class StorageHelper
{
    /**
     * Menyimpan file yang diupload ke disk yang aktif dengan fallback aman.
     */
    public static function storeFile($file, string $folder = 'uploads'): string
    {
        $disk = self::getDefaultDisk();
        $path = $file->store($folder, $disk);
        return self::getUrl($path) ?? $path;
    }

    /**
     * Mendapatkan disk aktif (S3 jika bucket terkonfigurasi, selain itu public disk).
     */
    public static function getDefaultDisk(): string
    {
        $s3Bucket = config('filesystems.disks.s3.bucket');
        if (! empty($s3Bucket)) {
            return 's3';
        }
        return config('filesystems.default', 'public');
    }

    /**
     * Resolusi URL file dengan fallback aman jika S3 tidak dikonfigurasi.
     */
    public static function getUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        // Jika path sudah berupa URL absolut (http/https), kembalikan langsung
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // Cek apakah AWS S3 memiliki bucket yang terkonfigurasi
        $s3Bucket = config('filesystems.disks.s3.bucket');
        if (! empty($s3Bucket)) {
            try {
                return Storage::disk('s3')->url($path);
            } catch (\Throwable $e) {
                // Abaikan error dan gunakan fallback public disk
            }
        }

        // Fallback default: disk public lokal
        return asset(Storage::disk('public')->url($path));
    }
}
