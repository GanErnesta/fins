<?php

namespace App\Support;

/**
 * Menyatukan pembuatan URL publik Supabase untuk profil dan navbar.
 * Database menyimpan object key (contoh: profile_photos/123/nama.jpg),
 * bukan URL lokal Laravel.
 */
final class ProfilePhotoUrl
{
    public static function objectKey(?string $storedPath): ?string
    {
        $path = trim((string) $storedPath);
        if ($path === '') {
            return null;
        }

        $bucket = (string) config('filesystems.disks.supabase.bucket', 'profile-photos');
        $base = rtrim((string) config('filesystems.disks.supabase.public_base_url'), '/');
        $publicPrefix = '/storage/v1/object/public/' . rawurlencode($bucket) . '/';

        // Dukungan untuk baris lama yang tanpa sengaja menyimpan URL publik penuh.
        if (preg_match('~^https?://~i', $path)) {
            $urlHost = parse_url($path, PHP_URL_HOST);
            $projectHost = parse_url($base, PHP_URL_HOST);
            $urlPath = parse_url($path, PHP_URL_PATH);
            if (!$projectHost || $urlHost !== $projectHost ||
                !is_string($urlPath) || !str_starts_with($urlPath, $publicPrefix)) {
                return null;
            }
            $path = substr($urlPath, strlen($publicPrefix));
        }

        // Dukungan jika path tersimpan dengan awalan bucket.
        if (str_starts_with(ltrim($path, '/'), $bucket . '/')) {
            $path = substr(ltrim($path, '/'), strlen($bucket) + 1);
        }

        $path = trim($path, '/');
        if ($path === '') {
            return null;
        }

        $segments = array_map('rawurldecode', explode('/', $path));
        // Object key harus berada di dalam bucket, tidak boleh punya traversal.
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..' ||
                str_contains($segment, '/') || str_contains($segment, "\\")) {
                return null;
            }
        }

        return implode('/', $segments);
    }

    public static function publicUrl(?string $storedPath): ?string
    {
        $key = self::objectKey($storedPath);
        if (!$key) {
            return null;
        }

        $base = rtrim((string) config('filesystems.disks.supabase.public_base_url'), '/');
        $bucket = (string) config('filesystems.disks.supabase.bucket', 'profile-photos');
        if (!filter_var($base, FILTER_VALIDATE_URL) || $bucket === '') {
            return null;
        }

        $encodedKey = implode('/', array_map('rawurlencode', explode('/', $key)));

        return $base . '/storage/v1/object/public/'
            . rawurlencode($bucket) . '/' . $encodedKey;
    }
}
