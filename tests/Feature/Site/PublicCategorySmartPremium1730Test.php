<?php

it('mantém seleção inteligente premium dos veículos', function (): void {
    $page = file_get_contents(
        resource_path('js/pages/public/category-vehicles.tsx')
    );

    expect($page)
        ->toContain('Mais equipados')
        ->toContain('const equipmentScore =')
        ->toContain('recommendedVehicleId')
        ->toContain('Filial de retirada')
        ->toContain('Recomendado')
        ->toContain('Mais equipado')
        ->toContain('Escolher recomendado');
});

it('preserva período brasileiro e cotação premium', function (): void {
    $page = file_get_contents(
        resource_path('js/pages/public/category-vehicles.tsx')
    );

    expect($page)
        ->toContain('formatDateTime(startsAt)')
        ->toContain('formatDateTime(endsAt)')
        ->toContain('Duração estimada')
        ->toContain("fetch('/api/public/quote'")
        ->toContain('Valor estimado para o período');
});
