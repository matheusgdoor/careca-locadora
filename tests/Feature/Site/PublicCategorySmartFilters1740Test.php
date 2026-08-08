<?php

it('mantém filtros inteligentes na escolha de veículos', function (): void {
    $page = file_get_contents(
        resource_path('js/pages/public/category-vehicles.tsx')
    );

    expect($page)
        ->toContain('const [filters, setFilters]')
        ->toContain('const filteredVehicles = useMemo')
        ->toContain('const toggleFilter =')
        ->toContain('Filtrar veículos')
        ->toContain('Automático')
        ->toContain('Ar-condicionado')
        ->toContain('5+ lugares')
        ->toContain('4+ portas')
        ->toContain('Limpar filtros')
        ->toContain('Nenhum veículo atende aos filtros');
});

it('preserva recomendação e período premium', function (): void {
    $page = file_get_contents(
        resource_path('js/pages/public/category-vehicles.tsx')
    );

    expect($page)
        ->toContain('recommendedVehicleId')
        ->toContain('Mais equipado')
        ->toContain('Escolher recomendado')
        ->toContain('formatDateTime(startsAt)')
        ->toContain('formatDateTime(endsAt)')
        ->toContain("fetch('/api/public/quote'");
});
