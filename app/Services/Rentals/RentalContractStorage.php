<?php

namespace App\Services\Rentals;

use Illuminate\Support\Facades\Storage;

final class RentalContractStorage
{
    public static function disk(): string
    {
        $default = (string) config('filesystems.default', 'local');

        return $default === '' ? 'local' : $default;
    }

    public static function putSignature(string $organizationId, string $requestId, string $pngBytes): string
    {
        $path = "rental-contracts/{$organizationId}/signatures/{$requestId}.png";
        Storage::disk(self::disk())->put($path, $pngBytes);

        return $path;
    }

    public static function dataUri(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $disk = Storage::disk(self::disk());

        if (! $disk->exists($path)) {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode($disk->get($path));
    }
}
