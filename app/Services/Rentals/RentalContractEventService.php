<?php

namespace App\Services\Rentals;

use App\Models\RentalContract;
use App\Models\RentalContractEvent;
use App\Models\RentalContractSignatureRequest;

final class RentalContractEventService
{
    public function record(
        RentalContract $contract,
        string $type,
        ?string $channel = null,
        ?string $recipient = null,
        ?RentalContractSignatureRequest $signatureRequest = null,
        array $metadata = [],
    ): RentalContractEvent {
        return RentalContractEvent::query()->create([
            'organization_id' => $contract->organization_id,
            'rental_contract_id' => $contract->id,
            'signature_request_id' => $signatureRequest?->id,
            'user_id' => auth()->id(),
            'type' => $type,
            'channel' => $channel,
            'recipient' => $recipient,
            'occurred_at' => now(),
            'metadata' => $metadata ?: null,
        ]);
    }
}
