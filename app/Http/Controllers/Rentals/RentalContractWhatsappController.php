<?php

namespace App\Http\Controllers\Rentals;

use App\Http\Controllers\Controller;
use App\Models\RentalContract;
use App\Services\Rentals\RentalContractCommunicationService;
use App\Services\Rentals\RentalContractSignatureService;
use Illuminate\Http\RedirectResponse;

final class RentalContractWhatsappController extends Controller
{
    public function __invoke(
        RentalContract $contract,
        RentalContractCommunicationService $communications,
        RentalContractSignatureService $signatures,
    ): RedirectResponse {
        $hasPending = $contract->signatureRequests()
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->exists();

        if (! $hasPending && in_array($contract->status, ['draft', 'awaiting_signature'], true)) {
            $signatures->createRequest($contract);
            $contract->refresh();
        }

        return redirect()->away($communications->whatsappUrl($contract));
    }
}
