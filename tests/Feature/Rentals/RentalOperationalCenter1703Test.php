<?php

it('mantém hover e clique independentes na agenda operacional', function (): void {
    $source = file_get_contents(
        resource_path('views/filament/pages/rental-availability-calendar.blade.php')
    );

    expect($source)
        ->toContain('@mouseenter="open = true')
        ->toContain('@mouseleave="open = false"')
        ->toContain('@click="open = false"')
        ->toContain('@careca-open-reservation.window="openReservation($event.detail)"')
        ->toContain("@click.stop.prevent=\"\$dispatch('careca-open-reservation'")
        ->toContain('selectedReservation: null')
        ->toContain('Abrir reserva completa')
        ->toContain('rental-ops__drawer');
});

it('mantém indicadores operacionais após a correção do clique', function (): void {
    $source = file_get_contents(
        resource_path('views/filament/pages/rental-availability-calendar.blade.php')
    );

    expect($source)
        ->toContain('Ativos exibidos')
        ->toContain('Pendentes')
        ->toContain('Em locação')
        ->toContain('Reservas do site')
        ->toContain('Valor previsto');
});
