<?php

it('abre o painel lateral por evento Alpine global', function (): void {
    $source = file_get_contents(
        resource_path('views/filament/pages/rental-availability-calendar.blade.php')
    );

    expect($source)
        ->toContain('@careca-open-reservation.window="openReservation($event.detail)"')
        ->toContain("@click.stop.prevent=\"\$dispatch('careca-open-reservation'")
        ->toContain('selectedReservation: null')
        ->toContain('Abrir reserva completa')
        ->not->toContain('@click.prevent="openReservation([');
});

it('preserva hover e centro operacional', function (): void {
    $source = file_get_contents(
        resource_path('views/filament/pages/rental-availability-calendar.blade.php')
    );

    expect($source)
        ->toContain('@mouseenter="open = true')
        ->toContain('@mouseleave="open = false"')
        ->toContain('rental-calendar__popover')
        ->toContain('Ativos exibidos')
        ->toContain('Valor previsto')
        ->toContain('rental-ops__drawer');
});
