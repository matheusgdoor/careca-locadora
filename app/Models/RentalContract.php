<?php

namespace App\Models;

use App\Services\Numbering\NumberSequenceService;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentalContract extends Model
{
    use BelongsToOrganization;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $guarded = [];

    protected $attributes = [
        'status' => 'draft',
        'subtotal' => 0,
        'discount_value' => 0,
        'additional_value' => 0,
        'deposit_value' => 0,
        'total_value' => 0,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $contract): void {
            if (blank($contract->number) && filled($contract->organization_id)) {
                $contract->number = app(NumberSequenceService::class)->next(
                    organizationId: $contract->organization_id,
                    key: 'rental_contract',
                    name: 'Contratos de locação',
                    prefix: 'CTR-',
                    padding: 7,
                );
            }
        });

        static::saving(function (self $contract): void {
            $contract->subtotal = (float) ($contract->subtotal ?? 0);
            $contract->discount_value = (float) ($contract->discount_value ?? 0);
            $contract->additional_value = (float) ($contract->additional_value ?? 0);
            $contract->deposit_value = (float) ($contract->deposit_value ?? 0);

            $contract->total_value = max(
                0,
                (float) $contract->subtotal
                - (float) $contract->discount_value
                + (float) $contract->additional_value
            );
        });
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'signed_at' => 'datetime',
            'activated_at' => 'datetime',
            'closed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'additional_value' => 'decimal:2',
            'deposit_value' => 'decimal:2',
            'total_value' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function reservation(): BelongsTo { return $this->belongsTo(RentalReservation::class); }
    public function customer(): BelongsTo { return $this->belongsTo(BusinessPartner::class, 'business_partner_id'); }
    public function authorizedContact(): BelongsTo { return $this->belongsTo(BusinessPartnerContact::class, 'authorized_contact_id'); }
    public function responsibleUser(): BelongsTo { return $this->belongsTo(User::class, 'responsible_user_id'); }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function costCenter(): BelongsTo { return $this->belongsTo(CostCenter::class); }
    public function items(): HasMany { return $this->hasMany(RentalContractItem::class, 'contract_id'); }
    public function delivery(): HasOne { return $this->hasOne(RentalDelivery::class, 'contract_id'); }
    public function rentalReturn(): HasOne { return $this->hasOne(RentalReturn::class, 'contract_id'); }
    public function rentalInvoice(): HasOne { return $this->hasOne(RentalInvoice::class, 'contract_id'); }

    public function signatureRequests(): HasMany
    {
        return $this->hasMany(RentalContractSignatureRequest::class, 'rental_contract_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(RentalContractEvent::class, 'rental_contract_id');
    }
}
