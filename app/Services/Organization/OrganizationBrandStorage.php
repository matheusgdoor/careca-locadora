<?php

namespace App\Services\Organization;

use Illuminate\Support\Facades\Storage;

final class OrganizationBrandStorage
{
    public static function disk(): string
    {
        $default = (string) config('filesystems.default', 'local');

        if ($default === '' || $default === 'local') {
            return 'public';
        }

        return $default;
    }

    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $path) === 1) {
            return $path;
        }

        $normalized = ltrim(str_replace('\\', '/', $path), '/');

        if (str_starts_with($normalized, 'storage/')) {
            $normalized = substr($normalized, strlen('storage/'));
        }

        return Storage::disk(self::disk())->url($normalized);
    }
}
