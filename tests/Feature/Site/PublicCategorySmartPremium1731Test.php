<?php

it('mantém seleção premium com recomendação e equipamento', function (): void {
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
        ->toContain('Recomendado')
        ->toContain('Mais novo')
        ->toContain('Mais equipado')
        ->toContain('Escolher recomendado');
});

it('preserva período e escolha inteligente anteriores', function (): void {
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
