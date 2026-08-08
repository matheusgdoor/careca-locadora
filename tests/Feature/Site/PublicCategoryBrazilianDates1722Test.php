<?php

it('mantém período da locação em formato brasileiro', function (): void {
    $page = file_get_contents(
        resource_path('js/pages/public/category-vehicles.tsx')
    );

    expect($page)
        ->toContain('const formatDateTime =')
        ->toContain("new Intl.DateTimeFormat('pt-BR'")
        ->toContain('formatDateTime(startsAt)')
        ->toContain('formatDateTime(endsAt)')
        ->toContain('Duração estimada')
        ->toContain('Retirada')
        ->toContain('Devolução');
});

it('preserva escolha premium existente', function (): void {
    $page = file_get_contents(
        resource_path('js/pages/public/category-vehicles.tsx')
    );

    expect($page)
        ->toContain("fetch('/api/public/category-vehicles'")
        ->toContain("fetch('/api/public/quote'")
        ->toContain('Mais novos')
        ->toContain('Mais novo')
        ->toContain('Valor estimado para o período')
        ->toContain('Escolher veículo');
});
