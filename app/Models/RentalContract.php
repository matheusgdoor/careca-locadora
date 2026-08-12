<?php

namespace App\Models;

use App\Services\Numbering\NumberSequenceService;
use App\Traits\BelongsToOrganization;
use DomainException;
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
        'rental_mode' => 'daily',
        'contract_version' => 1,
        'extra_distance_value' => 0,
        'protection_included' => false,
        'protection_deductible' => 0,
        'fuel_policy' => 'same_level',
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
            $contract->guardSignedCommercialTerms();

            $contract->subtotal = (float) ($contract->subtotal ?? 0);
            $contract->discount_value = (float) ($contract->discount_value ?? 0);
            $contract->additional_value = (float) ($contract->additional_value ?? 0);
            $contract->deposit_value = (float) ($contract->deposit_value ?? 0);
            $contract->extra_distance_value = (float) ($contract->extra_distance_value ?? 0);
            $contract->protection_deductible = (float) ($contract->protection_deductible ?? 0);

            $contract->total_value = max(
                0,
                (float) $contract->subtotal
                - (float) $contract->discount_value
                + (float) $contract->additional_value
            );

            if ($contract->rental_mode !== 'monthly') {
                $contract->billing_day = null;
            }
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
            'contract_version' => 'integer',
            'billing_day' => 'integer',
            'included_distance' => 'decimal:2',
            'extra_distance_value' => 'decimal:4',
            'protection_included' => 'boolean',
            'protection_deductible' => 'decimal:2',
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

    public function isMonthly(): bool
    {
        return $this->rental_mode === 'monthly';
    }

    public function isDaily(): bool
    {
        return $this->rental_mode === 'daily';
    }

    private function guardSignedCommercialTerms(): void
    {
        if (! $this->exists || blank($this->getOriginal('signed_at'))) {
            return;
        }

        foreach ([
            'rental_mode',
            'starts_at',
            'ends_at',
            'business_partner_id',
            'pickup_location',
            'return_location',
            'billing_day',
            'included_distance',
            'extra_distance_value',
            'protection_included',
            'protection_deductible',
            'fuel_policy',
            'subtotal',
            'discount_value',
            'additional_value',
            'deposit_value',
            'total_value',
            'terms',
        ] as $field) {
            if ($this->isDirty($field)) {
                throw new DomainException(
                    'Contrato assinado não permite alteração de condição comercial crítica. Gere um aditivo ou nova versão.'
                );
            }
        }
    }
}
