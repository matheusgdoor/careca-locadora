<?php

namespace App\Http\Controllers\CustomerPortal;

use App\Http\Controllers\Controller;
use App\Services\Fleet\AssetPhotoStorage;
use App\Models\RentalContract;
use App\Models\RentalInvoice;
use App\Models\RentalReservation;
use App\Services\CustomerPortal\CustomerPortalAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

final class CustomerPortalDashboardController extends Controller
{
    public function __invoke(Request $request, CustomerPortalAccountService $accounts): Response
    {
        $partner = $accounts->partnerForUser($request->user());
        abort_unless($partner, 403);

        $reservations = RentalReservation::query()
            ->withoutGlobalScopes()
            ->with(['branch', 'items.asset.photos', 'items.asset.category'])
            ->where('organization_id', $partner->organization_id)
            ->where('business_partner_id', $partner->id)
            ->latest('pickup_expected_at')
            ->get();

        $contracts = RentalContract::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $partner->organization_id)
            ->where('business_partner_id', $partner->id)
            ->get();

        $invoices = RentalInvoice::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $partner->organization_id)
            ->where('business_partner_id', $partner->id)
            ->get();

        $nextReservation = $reservations
            ->filter(fn ($reservation) =>
                ! in_array($reservation->status, ['completed', 'cancelled'], true)
                && $reservation->pickup_expected_at?->isFuture()
            )
            ->sortBy('pickup_expected_at')
            ->first();

        return Inertia::render('customer/dashboard', [
            'customer' => [
                'name' => $partner->display_name,
                'document' => $partner->document,
                'email' => $partner->email,
                'phone' => $partner->phone,
            ],
            'stats' => [
                'reservations' => $reservations->count(),
                'active_contracts' => $contracts->where('status', 'active')->count(),
                'open_invoices' => $invoices->whereNotIn('status', ['paid', 'cancelled'])->count(),
                'documents' => $partner->documents()->count(),
            ],
            'nextReservation' => $nextReservation ? $this->reservationData($nextReservation) : null,
            'recentReservations' => $reservations
                ->take(5)
                ->map(fn ($reservation) => $this->reservationData($reservation))
                ->values()
                ->all(),
        ]);
    }

    private function reservationData($reservation): array
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
            'photo' => $photo?->file_path,
            'photo_url' => filled($photo?->file_path)
                ? Storage::disk(AssetPhotoStorage::disk())->url($photo->file_path)
                : null,
        ];
    }
}
