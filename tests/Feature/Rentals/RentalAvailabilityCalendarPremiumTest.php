<?php

it('mantém a agenda premium com estrutura Blade válida', function (): void {
    $path = resource_path('views/filament/pages/rental-availability-calendar.blade.php');
    $source = file_get_contents($path);

    expect($source)
        ->toContain('rental-calendar__table')
        ->toContain('@forelse ($this->schedule as $row)')
        ->toContain('$asset = $row[\'asset\']')
        ->toContain('$items = $row[\'items\']')
        ->toContain('@empty')
        ->toContain('@endforelse')
        ->toContain('slot-free')
        ->toContain('slot-reserved')
        ->toContain('slot-rented')
        ->toContain('Início da agenda')
        ->toContain('Visão dos próximos 14 dias.');
});
