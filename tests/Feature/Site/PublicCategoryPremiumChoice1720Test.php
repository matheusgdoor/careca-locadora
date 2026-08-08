<?php

it('mantém escolha premium com preço período e ordenação', function (): void {
    $page = file_get_contents(
        resource_path('js/pages/public/category-vehicles.tsx')
    );

    expect($page)
        ->toContain("fetch('/api/public/category-vehicles'")
        ->toContain("fetch('/api/public/quote'")
        ->toContain('Período selecionado')
        ->toContain('Estimativa da categoria')
        ->toContain('Mais novos')
        ->toContain('Mais novo')
        ->toContain('Valor estimado para o período')
        ->toContain('sortedVehicles.map');
});
