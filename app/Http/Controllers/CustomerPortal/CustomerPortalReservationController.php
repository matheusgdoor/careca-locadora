<?php

namespace App\Http\Controllers\CustomerPortal;

use App\Http\Controllers\Controller;
use App\Models\RentalReservation;
use App\Services\CustomerPortal\CustomerPortalAccountService;
use Illuminate\Http\Request;
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
            ->map(function ($reservation): array {
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
                ];
            })
            ->values()
            ->all();

        return Inertia::render('customer/reservations', [
            'reservations' => $reservations,
        ]);
    }
}
