<?php

it('mantém filtros da agenda organizados e responsivos', function (): void {
    $source = file_get_contents(
        resource_path('views/filament/pages/rental-availability-calendar.blade.php')
    );

    expect($source)
        ->toContain('/* Organização Filtros 17.0.4 */')
        ->toContain('grid-template-columns:minmax(200px,.85fr)')
        ->toContain('@media (max-width:1250px)')
        ->toContain('@media (max-width:820px)')
        ->toContain('rental-calendar__legend');
});
