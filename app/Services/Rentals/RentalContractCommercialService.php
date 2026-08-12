<?php

namespace App\Services\Rentals;

use App\Models\RentalContract;

final class RentalContractCommercialService
{
    public function modeLabel(RentalContract $contract): string
    {
        return $contract->rental_mode === 'monthly'
            ? 'Locação mensal'
            : 'Locação diária';
    }

    public function fuelPolicyLabel(RentalContract $contract): string
    {
        return match ($contract->fuel_policy) {
            'full_to_full' => 'Cheio para cheio',
            'same_level' => 'Mesmo nível da retirada',
            'charged_difference' => 'Diferença de combustível cobrada',
            default => 'Conforme checklist',
        };
    }

    public function distanceLabel(RentalContract $contract): string
    {
        if ($contract->included_distance === null) {
            return 'Quilometragem livre';
        }

        $suffix = $contract->rental_mode === 'monthly' ? ' km/mês' : ' km';

        return number_format((float) $contract->included_distance, 0, ',', '.') . $suffix;
    }
}
