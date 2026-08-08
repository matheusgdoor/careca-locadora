<?php

it('mantém filtros estáveis da agenda', function (): void {
    $source = file_get_contents(
        resource_path('views/filament/pages/rental-availability-calendar.blade.php')
    );

    expect($source)
        ->toContain('/* Filtros Estáveis 17.0.5 */')
        ->toContain('grid-column:1 / -1 !important')
        ->toContain('justify-content:flex-start !important');
});
