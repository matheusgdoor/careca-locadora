<?php

namespace App\Services\Organization;

use Illuminate\Support\Facades\Storage;
use Throwable;

final class OrganizationBrandStorage
{
    public static function disk(): string
    {
        $default = (string) config('filesystems.default', 'local');

        return ($default === '' || $default === 'local')
            ? 'public'
            : $default;
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

        try {
            $disk = Storage::disk(self::disk());

            if (! $disk->exists($normalized)) {
                return null;
            }

            return $disk->url($normalized);
        } catch (Throwable) {
            return null;
        }
    }
}
