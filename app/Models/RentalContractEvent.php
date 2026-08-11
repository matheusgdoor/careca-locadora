<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalContractEvent extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(RentalContract::class, 'rental_contract_id');
    }

    public function signatureRequest(): BelongsTo
    {
        return $this->belongsTo(RentalContractSignatureRequest::class, 'signature_request_id');
    }
}
