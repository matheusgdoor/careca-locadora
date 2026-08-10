<?php

namespace App\Domain\Reservations;

use App\Data\Rentals\ReservationSearch;
use App\Models\Asset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class ReservationAvailabilityEngine
{
    public function __construct(
        private readonly ReservationConflictEngine $conflicts,
    ) {
    }

    public function availableAssets(
        ReservationSearch $search,
        ?string $text = null,
        int $limit = 100,
    ): Collection {
        $limit = max(1, min(500, $limit));

        return Asset::query()
            ->withoutGlobalScopes()
            ->with(['category', 'branch', 'photos'])
            ->where('organization_id', $search->organizationId)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->whereNotIn('operational_status', [
                'maintenance',
                'blocked',
                'inactive',
                'sold',
            ])
            ->when(
                filled($search->branchId),
                fn (Builder $query): Builder =>
                    $query->where('branch_id', $search->branchId)
            )
            ->when(
                filled($search->categoryId),
                fn (Builder $query): Builder =>
                    $query->where('category_id', $search->categoryId)
            )
            ->when(
                filled($search->assetId),
                fn (Builder $query): Builder =>
                    $query->whereKey($search->assetId)
            )
            ->when(
                filled($text),
                fn (Builder $query): Builder => $query->where(
                    function (Builder $nested) use ($text): void {
                        $nested
                            ->where('prefix', 'ilike', "%{$text}%")
                            ->orWhere('plate', 'ilike', "%{$text}%")
                            ->orWhere('name', 'ilike', "%{$text}%");
                    }
                )
            )
            ->orderBy('current_odometer')
            ->orderBy('current_hourmeter')
            ->orderBy('prefix')
            ->limit($limit)
            ->get()
            ->reject(
                fn (Asset $asset): bool =>
                    $this->conflicts->hasConflict($search, $asset->id)
            )
            ->values();
    }

    public function categorySummary(
        ReservationSearch $search,
    ): Collection {
        return $this->availableAssets($search, limit: 500)
            ->groupBy('category_id')
            ->map(function (Collection $assets): array {
                $first = $assets->first();

                return [
                    'category_id' => $first?->category_id,
                    'category_name' => $first?->category?->name,
                    'available_count' => $assets->count(),
                    'asset_ids' => $assets->pluck('id')->values()->all(),
                ];
            })
            ->values();
    }

    public function isAvailable(
        ReservationSearch $search,
        string $assetId,
    ): bool {
        return $this->availableAssets(
            new ReservationSearch(
                organizationId: $search->organizationId,
                startsAt: $search->startsAt,
                endsAt: $search->endsAt,
                branchId: $search->branchId,
                categoryId: $search->categoryId,
                assetId: $assetId,
                ignoreReservationId: $search->ignoreReservationId,
                preparationMinutes: $search->preparationMinutes,
            ),
            limit: 1,
        )->isNotEmpty();
    }
}
