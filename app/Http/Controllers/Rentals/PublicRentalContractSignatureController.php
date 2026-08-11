<?php

namespace App\Http\Controllers\Rentals;

use App\Http\Controllers\Controller;
use App\Models\RentalContractSignatureRequest;
use App\Services\Rentals\RentalContractDocumentService;
use App\Services\Rentals\RentalContractSignatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use RuntimeException;

final class PublicRentalContractSignatureController extends Controller
{
    public function show(
        RentalContractSignatureRequest $signatureRequest,
        RentalContractSignatureService $signatures,
    ): InertiaResponse {
        abort_if($signatureRequest->expires_at->isPast(), 410);
        abort_if($signatureRequest->status === 'cancelled', 410);

        $signatureRequest->loadMissing('contract.customer');
        $signatures->markViewed($signatureRequest);

        return Inertia::render('public/contract-signature', [
            'request' => [
                'status' => $signatureRequest->status,
                'signer_name' => $signatureRequest->signer_name,
                'signer_document' => $signatureRequest->signer_document,
                'expires_at' => $signatureRequest->expires_at->toIso8601String(),
                'signed_at' => $signatureRequest->signed_at?->toIso8601String(),
            ],
            'contract' => [
                'number' => $signatureRequest->contract->number,
                'customer' => $signatureRequest->contract->customer?->display_name,
                'starts_at' => $signatureRequest->contract->starts_at?->toIso8601String(),
                'ends_at' => $signatureRequest->contract->ends_at?->toIso8601String(),
                'total_value' => (float) $signatureRequest->contract->total_value,
            ],
            'submit_url' => URL::temporarySignedRoute(
                'public.contract-signature.store',
                $signatureRequest->expires_at,
                ['signatureRequest' => $signatureRequest->id],
            ),
            'pdf_url' => URL::temporarySignedRoute(
                'public.contract-signature.pdf',
                $signatureRequest->expires_at,
                ['signatureRequest' => $signatureRequest->id],
            ),
        ]);
    }

    public function store(
        Request $request,
        RentalContractSignatureRequest $signatureRequest,
        RentalContractSignatureService $signatures,
    ): RedirectResponse {
        $data = $request->validate([
            'accepted' => ['required', 'accepted'],
            'signature' => ['required', 'string', 'max:3000000'],
        ]);

        try {
            $signatures->sign(
                $signatureRequest,
                $request,
                $data['signature'],
                (bool) $data['accepted'],
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'signature' => $exception->getMessage(),
            ]);
        }

        return back()->with('success', 'Contrato assinado com sucesso.');
    }

    public function pdf(
        RentalContractSignatureRequest $signatureRequest,
        RentalContractDocumentService $documents,
    ): Response {
        abort_if($signatureRequest->expires_at->isPast(), 410);

        $bytes = $documents->bytes(
            $signatureRequest->contract,
            $signatureRequest->status === 'signed' ? $signatureRequest : null,
        );

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $documents->filename($signatureRequest->contract) . '"',
        ]);
    }
}
