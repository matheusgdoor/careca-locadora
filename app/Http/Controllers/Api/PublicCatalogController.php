<?php

namespace App\Http\Controllers\Api;

use App\Data\Rentals\ReservationSearch;
use App\Domain\Reservations\ReservationAvailabilityEngine;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicCatalogAvailabilityRequest;
use App\Http\Requests\PublicCatalogQuoteRequest;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Branch;
use App\Models\RentalRatePlan;
use App\Services\Rentals\RentalCommercialPricingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class PublicCatalogController extends Controller
{
    public function branches(): JsonResponse
    {
        $data = Branch::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $this->organizationId())
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(fn (Branch $branch): array => [
                'id' => $branch->id,
                'name' => $branch->name,
                'city' => $branch->city,
                'state' => $branch->state,
            ]);

        return response()->json(['data' => $data]);
    }

    public function categories(): JsonResponse
    {
        $data = AssetCategory::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $this->organizationId())
            ->where('status', 'active')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get()
            ->map(fn (AssetCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'public_title' => data_get(
                    $category->metadata,
                    'public_title',
                    $category->name
                ),
                'prefix' => $category->prefix,
                'description' => data_get(
                    $category->metadata,
                    'commercial_description'
                ),
                'similar_models' => data_get(
                    $category->metadata,
                    'similar_models'
                ),
                'cover_image' => data_get(
                    $category->metadata,
                    'cover_image'
                ),
                'featured' => (bool) data_get(
                    $category->metadata,
                    'featured',
                    false
                ),
            ]);

        return response()->json(['data' => $data]);
    }

    public function availability(
        PublicCatalogAvailabilityRequest $request,
        ReservationAvailabilityEngine $engine,
    ): JsonResponse {
        $search = $this->search($request->validated());

        $assets = $engine->availableAssets(
            $search,
            $request->string('search')->toString() ?: null,
            100
        );

        $categories = $assets
            ->groupBy('category_id')
            ->map(function (Collection $group) use ($search): array {
                /** @var Asset $representative */
                $representative = $group
                    ->sortByDesc(
                        fn (Asset $asset): int =>
                            $asset->photos->contains('is_featured', true)
                                ? 1
                                : 0
                    )
                    ->first();

                $category = $representative->category;
                $metadata = $category?->metadata ?? [];

                return [
                    'id' => $category?->id,
                    'name' => $category?->name,
                    'public_title' => data_get(
                        $metadata,
                        'public_title',
                        $category?->name
                    ),
                    'description' => data_get(
                        $metadata,
                        'commercial_description'
                    ),
                    'similar_models' => data_get(
                        $metadata,
                        'similar_models',
                        $group->pluck('model')
                            ->filter()
                            ->unique()
                            ->take(3)
                            ->implode(', ')
                    ),
                    'cover_image' => data_get(
                        $metadata,
                        'cover_image'
                    ),
                    'available_count' => $group->count(),
                    'representative_asset_id' => $representative->id,
                    'branch' => [
                        'id' => $representative->branch?->id,
                        'name' => $representative->branch?->name,
                        'city' => $representative->branch?->city,
                        'state' => $representative->branch?->state,
                    ],
                    'specs' => [
                        'seats' => $group->pluck('seats')
                            ->filter()
                            ->max(),
                        'transmissions' => $group->pluck('transmission')
                            ->filter()
                            ->unique()
                            ->values()
                            ->all(),
                        'fuel_types' => $group->pluck('fuel_type')
                            ->filter()
                            ->unique()
                            ->values()
                            ->all(),
                    ],
                    'photos' => $representative->photos
                        ->sortByDesc('is_featured')
                        ->sortBy('display_order')
                        ->map(fn ($photo): array => [
                            'path' => $photo->file_path,
                            'url' => $photo->url,
                            'disk' => \App\Services\Fleet\AssetPhotoStorage::disk(),
                            'featured' => (bool) $photo->is_featured,
                        ])
                        ->values()
                        ->all(),
                    'tariffs' => $this->tariffs(
                        categoryId: (string) $category?->id,
                        branchId: $search->branchId,
                    ),
                ];
            })
            ->filter(fn (array $category): bool => filled($category['id']))
            ->sortBy('name')
            ->values();

        return response()->json([
            'data' => $categories,
            'meta' => [
                'count' => $categories->count(),
                'available_assets' => $assets->count(),
                'mode' => 'category',
                'starts_at' => $search->startsAt->toIso8601String(),
                'ends_at' => $search->endsAt->toIso8601String(),
            ],
        ]);
    }

    public function categoryVehicles(
        PublicCatalogAvailabilityRequest $request,
        ReservationAvailabilityEngine $engine,
    ): JsonResponse {
        $search = $this->search($request->validated());

        $assets = $engine->availableAssets(
            $search,
            $request->string('search')->toString() ?: null,
            100
        )->map(fn (Asset $asset): array => [
            'id' => $asset->id,
            'prefix' => $asset->prefix,
            'name' => $asset->name,
            'plate' => $asset->plate,
            'seats' => $asset->seats,
            'doors' => data_get($asset->metadata, 'doors'),
            'transmission' => $asset->transmission,
            'fuel_type' => $asset->fuel_type,
            'model_year' => $asset->model_year,
            'air_conditioning' => (bool) data_get(
                $asset->metadata,
                'air_conditioning',
                false
            ),
            'power_steering' => (bool) data_get(
                $asset->metadata,
                'power_steering',
                false
            ),
            'luggage_capacity' => data_get(
                $asset->metadata,
                'luggage_capacity'
            ),
            'category' => [
                'id' => $asset->category?->id,
                'name' => $asset->category?->name,
            ],
            'branch' => [
                'id' => $asset->branch?->id,
                'name' => $asset->branch?->trade_name
                    ?: $asset->branch?->name,
                'city' => $asset->branch?->city,
                'state' => $asset->branch?->state,
            ],
            'photos' => $asset->photos
                ->filter(fn ($photo): bool => filled($photo->file_path))
                ->sortByDesc('is_featured')
                ->sortBy('display_order')
                ->map(fn ($photo): array => [
                    'path' => $photo->file_path,
                    'url' => $photo->url,
                    'disk' => \App\Services\Fleet\AssetPhotoStorage::disk(),
                    'featured' => (bool) $photo->is_featured,
                ])
                ->values()
                ->all(),
        ])->values();

        return response()->json([
            'data' => $assets,
            'meta' => [
                'count' => $assets->count(),
                'mode' => 'assets',
                'starts_at' => $search->startsAt->toIso8601String(),
                'ends_at' => $search->endsAt->toIso8601String(),
            ],
        ]);
    }

    public function quote(
        PublicCatalogQuoteRequest $request,
        RentalCommercialPricingService $pricing,
    ): JsonResponse {
        $data = $request->validated();

        $quote = $pricing->quote(
            $this->search($data),
            $data['commercial_item_ids'] ?? [],
            $data['coupon_code'] ?? null,
        );

        return response()->json(['data' => $quote]);
    }

    private function tariffs(
        string $categoryId,
        ?string $branchId,
    ): array {
        $rates = RentalRatePlan::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $this->organizationId())
            ->where('status', 'active')
            ->where('asset_category_id', $categoryId)
            ->when(
            filled($branchId),
            fn (Builder $query): Builder => $query->where(
                function (Builder $nested) use ($branchId): void {
                    $nested
                        ->whereNull('branch_id')
                        ->orWhere('branch_id', $branchId);
                }
            )
        )
        ->orderByRaw(
                'case when branch_id is null then 1 else 0 end'
            )
            ->orderBy('priority')
            ->get();

        $find = fn (string $unit, ?int $minimum = null) =>
            $rates->first(function (RentalRatePlan $rate) use (
                $unit,
                $minimum
            ): bool {
                if ($rate->billing_unit !== $unit) {
                    return false;
                }

                return $minimum === null
                    || (int) $rate->minimum_quantity === $minimum
                    || (int) data_get(
                        $rate->metadata,
                        'period_days',
                        0
                    ) === $minimum;
            });

        $daily = $find('daily');
        $fifteen = $find('fixed', 15);
        $monthly = $find('monthly');

        return [
            'daily' => $this->ratePayload($daily),
            'fifteen_days' => $this->ratePayload($fifteen),
            'monthly' => $this->ratePayload($monthly),
        ];
    }

    private function ratePayload(
        ?RentalRatePlan $rate,
    ): ?array {
        if (! $rate) {
            return null;
        }

        return [
            'id' => $rate->id,
            'name' => $rate->name,
            'billing_unit' => $rate->billing_unit,
            'value' => (float) $rate->unit_value,
            'deposit_value' => (float) $rate->deposit_value,
            'minimum_quantity' => (int) $rate->minimum_quantity,
        ];
    }

    private function search(array $data): ReservationSearch
    {
        return ReservationSearch::fromArray([
            'organization_id' => $this->organizationId(),
            'branch_id' => $data['branch_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'preparation_minutes' => config(
                'careca-public.preparation_minutes',
                60
            ),
        ]);
    }

    private function organizationId(): string
    {
        $id = config('careca-public.organization_id');

        if (blank($id)) {
            throw new ServiceUnavailableHttpException(
                null,
                'CARECA_PUBLIC_ORGANIZATION_ID nao configurado.'
            );
        }

        return (string) $id;
    }
}