<?php

namespace App\Services\Rentals;

use App\Models\RentalContract;
use App\Models\RentalContractSignatureRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use RuntimeException;

final class RentalContractSignatureService
{
    public function __construct(
        private readonly RentalContractDocumentService $documents,
        private readonly RentalContractEventService $events,
    ) {
    }

    public function createRequest(RentalContract $contract, int $validDays = 7): RentalContractSignatureRequest
    {
        $contract->loadMissing('customer');

        $request = RentalContractSignatureRequest::query()->create([
            'organization_id' => $contract->organization_id,
            'rental_contract_id' => $contract->id,
            'requested_by_user_id' => auth()->id(),
            'status' => 'pending',
            'signer_name' => $contract->customer?->display_name
                ?? $contract->customer?->legal_name
                ?? 'Cliente',
            'signer_email' => $contract->customer?->email,
            'signer_document' => $contract->customer?->document,
            'signer_phone' => $contract->customer?->whatsapp ?: $contract->customer?->phone,
            'document_hash' => $this->documents->hash($contract),
            'expires_at' => now()->addDays($validDays),
        ]);

        $contract->update(['status' => 'awaiting_signature']);

        $this->events->record(
            contract: $contract,
            type: 'signature_requested',
            channel: 'link',
            recipient: $request->signer_email ?: $request->signer_phone,
            signatureRequest: $request,
        );

        return $request;
    }

    public function url(RentalContractSignatureRequest $request): string
    {
        return URL::temporarySignedRoute(
            'public.contract-signature.show',
            $request->expires_at,
            ['signatureRequest' => $request->id],
        );
    }

    public function markViewed(RentalContractSignatureRequest $signatureRequest): void
    {
        if ($signatureRequest->viewed_at) {
            return;
        }

        $signatureRequest->update(['viewed_at' => now()]);

        $this->events->record(
            contract: $signatureRequest->contract,
            type: 'signature_viewed',
            channel: 'web',
            signatureRequest: $signatureRequest,
        );
    }

    public function sign(
        RentalContractSignatureRequest $signatureRequest,
        Request $httpRequest,
        string $signatureDataUrl,
        bool $accepted,
    ): RentalContractSignatureRequest {
        if (! $accepted) {
            throw new RuntimeException('É necessário aceitar os termos para assinar.');
        }

        if ($signatureRequest->status !== 'pending') {
            throw new RuntimeException('Esta solicitação de assinatura não está mais disponível.');
        }

        if ($signatureRequest->expires_at->isPast()) {
            $signatureRequest->update(['status' => 'expired']);
            throw new RuntimeException('Esta solicitação de assinatura expirou.');
        }

        $currentHash = $this->documents->hash($signatureRequest->contract);

        if (! hash_equals($signatureRequest->document_hash, $currentHash)) {
            throw new RuntimeException('O contrato foi alterado após o envio. Gere uma nova solicitação de assinatura.');
        }

        if (! preg_match('/^data:image\/png;base64,(.+)$/', $signatureDataUrl, $matches)) {
            throw new RuntimeException('Assinatura inválida.');
        }

        $bytes = base64_decode($matches[1], true);

        if ($bytes === false || strlen($bytes) < 100 || strlen($bytes) > 2_000_000) {
            throw new RuntimeException('Assinatura inválida ou muito grande.');
        }

        return DB::transaction(function () use ($signatureRequest, $httpRequest, $bytes): RentalContractSignatureRequest {
            $path = RentalContractStorage::putSignature(
                $signatureRequest->organization_id,
                $signatureRequest->id,
                $bytes,
            );

            $signatureRequest->update([
                'status' => 'signed',
                'signature_path' => $path,
                'signed_at' => now(),
                'signed_ip' => $httpRequest->ip(),
                'signed_user_agent' => mb_substr((string) $httpRequest->userAgent(), 0, 2000),
                'acceptance_text' => 'Declaro que li, compreendi e aceito integralmente o contrato apresentado.',
            ]);

            $signatureRequest->contract->update([
                'signed_at' => $signatureRequest->signed_at,
            ]);

            $signatureRequest->refresh();

            $signatureRequest->update([
                'signed_document_hash' => $this->documents->hash(
                    $signatureRequest->contract,
                    $signatureRequest,
                ),
            ]);

            $this->events->record(
                contract: $signatureRequest->contract,
                type: 'signed',
                channel: 'web',
                recipient: $signatureRequest->signer_email ?: $signatureRequest->signer_phone,
                signatureRequest: $signatureRequest,
                metadata: [
                    'ip' => $signatureRequest->signed_ip,
                    'document_hash' => $signatureRequest->document_hash,
                    'signed_document_hash' => $signatureRequest->signed_document_hash,
                ],
            );

            return $signatureRequest->fresh();
        });
    }
}
