<?php

namespace App\Services\Rentals;

use App\Models\RentalContract;
use App\Models\RentalContractSignatureRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Throwable;

final class RentalContractSignatureService
{
    public function __construct(
        private readonly RentalContractDocumentService $documents,
        private readonly RentalContractEventService $events,
    ) {}

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
            'document_hash' => str_repeat('0', 64),
            'expires_at' => now()->addDays($validDays),
        ]);

        $request = $this->documents->freeze($request);

        $contract->update(['status' => 'awaiting_signature']);

        $this->events->record(
            contract: $contract,
            type: 'signature_requested',
            channel: 'link',
            recipient: $request->signer_email ?: $request->signer_phone,
            signatureRequest: $request,
            metadata: [
                'document_hash' => $request->document_hash,
                'snapshot_version' => data_get($request->metadata, 'document_snapshot_version'),
            ],
        );

        return $request;
    }

    public function ensureFrozenDocument(RentalContractSignatureRequest $signatureRequest): RentalContractSignatureRequest
    {
        return $this->documents->freeze($signatureRequest);
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
        $signatureRequest = $this->ensureFrozenDocument($signatureRequest);

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
        $signatureRequest = $this->ensureFrozenDocument($signatureRequest);

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

        $frozenHash = $this->documents->frozenHash($signatureRequest);

        if (! hash_equals($signatureRequest->document_hash, $frozenHash)) {
            throw new RuntimeException('A versão armazenada do contrato não passou na validação de integridade.');
        }

        $currentLiveHash = $this->documents->liveHash($signatureRequest->contract);

        if (! hash_equals($signatureRequest->document_hash, $currentLiveHash)) {
            throw new RuntimeException('O contrato foi alterado após o envio. Gere uma nova solicitação de assinatura.');
        }

        if (! preg_match('/^data:image\/png;base64,(.+)$/', $signatureDataUrl, $matches)) {
            throw new RuntimeException('Assinatura inválida.');
        }

        $bytes = base64_decode($matches[1], true);

        if ($bytes === false || strlen($bytes) < 100 || strlen($bytes) > 2_000_000) {
            throw new RuntimeException('Assinatura inválida ou muito grande.');
        }

        return DB::transaction(function () use ($signatureRequest, $httpRequest, $signatureDataUrl, $bytes): RentalContractSignatureRequest {
            $metadata = $signatureRequest->metadata ?? [];
            $metadata['signature_data_url'] = $signatureDataUrl;
            $metadata['signature_sha256'] = hash('sha256', $bytes);

            $path = null;

            try {
                $path = RentalContractStorage::putSignature(
                    $signatureRequest->organization_id,
                    $signatureRequest->id,
                    $bytes,
                );
            } catch (Throwable) {
                // O banco permanece como cópia durável primária.
            }

            $signatureRequest->update([
                'status' => 'signed',
                'signature_path' => $path,
                'signed_at' => now(),
                'signed_ip' => $httpRequest->ip(),
                'signed_user_agent' => mb_substr((string) $httpRequest->userAgent(), 0, 2000),
                'acceptance_text' => 'Declaro que li, compreendi e aceito integralmente o contrato apresentado.',
                'metadata' => $metadata,
            ]);

            $signatureRequest->contract->update([
                'signed_at' => $signatureRequest->signed_at,
            ]);

            $signatureRequest->refresh();

            $signatureRequest->update([
                'signed_document_hash' => $this->documents->signedContentHash($signatureRequest),
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
                    'signature_sha256' => data_get($signatureRequest->metadata, 'signature_sha256'),
                ],
            );

            return $signatureRequest->fresh();
        });
    }
}
