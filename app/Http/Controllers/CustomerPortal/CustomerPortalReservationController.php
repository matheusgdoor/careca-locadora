<?php

namespace App\Http\Controllers\CustomerPortal;

use App\Http\Controllers\Controller;
use App\Services\Fleet\AssetPhotoStorage;
use App\Models\RentalReservation;
use App\Services\CustomerPortal\CustomerPortalAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

final class CustomerPortalReservationController extends Controller
{
    public function index(Request $request, CustomerPortalAccountService $accounts): Response
    {
        $partner = $accounts->partnerForUser($request->user());
        abort_unless($partner, 403);

        $reservations = RentalReservation::query()
            ->withoutGlobalScopes()
            ->with(['branch', 'items.asset.photos', 'items.asset.category'])
            ->where('organization_id', $partner->organization_id)
            ->where('business_partner_id', $partner->id)
            ->latest('pickup_expected_at')
            ->get()
            ->map(fn ($reservation): array => $this->summaryData($reservation))
            ->values()
            ->all();

        return Inertia::render('customer/reservations', [
            'reservations' => $reservations,
        ]);
    }

    public function show(
        Request $request,
        string $reservation,
        CustomerPortalAccountService $accounts,
    ): Response {
        $partner = $accounts->partnerForUser($request->user());
        abort_unless($partner, 403);

        $model = RentalReservation::query()
            ->withoutGlobalScopes()
            ->with(['branch', 'items.asset.photos', 'items.asset.category'])
            ->where('organization_id', $partner->organization_id)
            ->where('business_partner_id', $partner->id)
            ->whereKey($reservation)
            ->firstOrFail();

        $asset = $model->items->first()?->asset;
        $photo = $asset?->photos
            ?->sortByDesc('is_featured')
            ->sortBy('display_order')
            ->first();

        return Inertia::render('customer/reservation-show', [
            'reservation' => [
                'id' => $model->id,
                'number' => $model->number,
                'status' => $model->status,
                'origin' => $model->origin,
                'pickup_expected_at' => $model->pickup_expected_at?->toIso8601String(),
                'return_expected_at' => $model->return_expected_at?->toIso8601String(),
                'total_value' => (float) $model->total_value,
                'deposit_value' => (float) ($model->deposit_value ?? 0),
                'commercial_notes' => $model->commercial_notes,
                'branch' => [
                    'name' => $model->branch?->name,
                    'phone' => $model->branch?->phone,
                    'whatsapp' => $model->branch?->whatsapp,
                ],
                'vehicle' => [
                    'name' => $asset?->name,
                    'category' => $asset?->category?->name,
                    'brand' => $asset?->brand,
                    'model' => $asset?->model,
                    'year' => $asset?->model_year,
                    'transmission' => $asset?->transmission,
                    'fuel_type' => $asset?->fuel_type,
                    'seats' => $asset?->seats,
                    'doors' => data_get($asset?->metadata, 'doors'),
                    'air_conditioning' => (bool) data_get($asset?->metadata, 'air_conditioning', false),
                    'photo' => $photo?->file_path,
                    'photo_url' => filled($photo?->file_path)
                        ? Storage::disk(AssetPhotoStorage::disk())->url($photo->file_path)
                        : null,
                ],
                'timeline' => $this->timeline((string) $model->status),
            ],
        ]);
    }

    private function summaryData($reservation): array
    {
        $asset = $reservation->items->first()?->asset;
        $photo = $asset?->photos
            ?->sortByDesc('is_featured')
            ->sortBy('display_order')
            ->first();

        return [
            'id' => $reservation->id,
            'number' => $reservation->number,
            'status' => $reservation->status,
            'pickup_expected_at' => $reservation->pickup_expected_at?->toIso8601String(),
            'return_expected_at' => $reservation->return_expected_at?->toIso8601String(),
            'total_value' => (float) $reservation->total_value,
            'branch' => $reservation->branch?->name,
            'vehicle' => $asset?->name,
            'category' => $asset?->category?->name,
            'photo' => $photo?->file_path,
            'photo_url' => filled($photo?->file_path)
                ? Storage::disk(AssetPhotoStorage::disk())->url($photo->file_path)
                : null,
        ];
    }

    private function timeline(string $status): array
    {
        $order = ['pending' => 1, 'confirmed' => 2, 'converted' => 3, 'active' => 4, 'completed' => 5];
        $current = $order[$status] ?? 1;
        $cancelled = $status === 'cancelled';

        return [
            ['key' => 'requested', 'label' => 'Reserva solicitada', 'done' => true, 'current' => $current === 1 && ! $cancelled],
            ['key' => 'confirmed', 'label' => 'Reserva confirmada', 'done' => ! $cancelled && $current >= 2, 'current' => ! $cancelled && $current === 2],
            ['key' => 'contract', 'label' => 'Contrato preparado', 'done' => ! $cancelled && $current >= 3, 'current' => ! $cancelled && $current === 3],
            ['key' => 'rental', 'label' => 'Veículo em locação', 'done' => ! $cancelled && $current >= 4, 'current' => ! $cancelled && $current === 4],
            ['key' => 'completed', 'label' => $cancelled ? 'Reserva cancelada' : 'Locação concluída', 'done' => $cancelled || $current >= 5, 'current' => $cancelled || $current === 5],
        ];
    }
}
