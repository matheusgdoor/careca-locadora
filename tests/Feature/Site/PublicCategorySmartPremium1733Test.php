<?php

it('valida selos premium mesmo com JSX formatado em múltiplas linhas', function (): void {
    $page = file_get_contents(
        resource_path('js/pages/public/category-vehicles.tsx')
    );

    expect($page)
        ->toContain("useState<'newest' | 'equipped' | 'name'>('newest')")
        ->toContain('<option value="equipped">Mais equipados</option>')
        ->toContain("if (sortBy === 'equipped')")
        ->toContain('const equipmentScore =')
        ->toContain('recommendedVehicleId')
        ->toContain('Filial de retirada')
        ->toContain('Escolher recomendado');

    expect(preg_match('/>\s*Recomendado\s*</s', $page))->toBe(1)
        ->and(preg_match('/>\s*Mais novo\s*</s', $page))->toBe(1)
        ->and(preg_match('/>\s*Mais equipado\s*</s', $page))->toBe(1)
        ->and(
            preg_match(
                '/vehicle\.id\s*===\s*recommendedVehicleId\s*&&\s*\(/s',
                $page
            )
        )->toBe(1)
        ->and(
            preg_match(
                '/equipmentScore\(vehicle\)\s*===\s*maxEquipmentScore\s*&&/s',
                $page
            )
        )->toBe(1);
});

it('preserva período brasileiro e escolha individual', function (): void {
    $page = file_get_contents(
        resource_path('js/pages/public/category-vehicles.tsx')
    );

    expect($page)
        ->toContain('formatDateTime(startsAt)')
        ->toContain('formatDateTime(endsAt)')
        ->toContain('Duração estimada')
        ->toContain("fetch('/api/public/category-vehicles'")
        ->toContain("fetch('/api/public/quote'")
        ->toContain('/veiculos/${vehicle.id}?');
});
