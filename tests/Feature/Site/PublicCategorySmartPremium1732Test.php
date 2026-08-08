<?php

it('mantém badges premium sem falso positivo da opção Mais equipados', function (): void {
    $page = file_get_contents(
        resource_path('js/pages/public/category-vehicles.tsx')
    );

    expect($page)
        ->toContain('<option value="equipped">Mais equipados</option>')
        ->toContain('recommendedVehicleId && (')
        ->toContain('>Recomendado<')
        ->toContain('equipmentScore(vehicle) ===')
        ->toContain('>Mais equipado<')
        ->toContain('Escolher recomendado');
});

it('preserva seleção, período e cotação anteriores', function (): void {
    $page = file_get_contents(
        resource_path('js/pages/public/category-vehicles.tsx')
    );

    expect($page)
        ->toContain("if (sortBy === 'equipped')")
        ->toContain('Filial de retirada')
        ->toContain('formatDateTime(startsAt)')
        ->toContain('formatDateTime(endsAt)')
        ->toContain("fetch('/api/public/quote'")
        ->toContain("fetch('/api/public/category-vehicles'");
});
