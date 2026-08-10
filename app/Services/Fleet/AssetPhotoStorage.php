<?php

namespace App\Services\Fleet;

use Illuminate\Support\Facades\Storage;

final class AssetPhotoStorage
{
    public static function disk(): string
    {
        $default = (string) config('filesystems.default', 'local');

        // Desenvolvimento local: o "local" padrão não é público.
        // Mantemos o comportamento já funcional usando storage/app/public.
        if ($default === '' || $default === 'local') {
            return 'public';
        }

        // Laravel Cloud: ao anexar um bucket e defini-lo como padrão,
        // FILESYSTEM_DISK é injetado e passa a apontar para o Object Storage.
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
