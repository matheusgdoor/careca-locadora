<?php

it('mantém indicadores e painel lateral do centro operacional', function (): void {
    $source = file_get_contents(
        resource_path('views/filament/pages/rental-availability-calendar.blade.php')
    );

    expect($source)
        ->toContain('Ativos exibidos')
        ->toContain('Pendentes')
        ->toContain('Em locação')
        ->toContain('Reservas do site')
        ->toContain('Valor previsto')
        ->toContain('selectedReservation: null')
        ->toContain('@careca-open-reservation.window="openReservation($event.detail)"')
        ->toContain("@click.stop.prevent=\"\$dispatch('careca-open-reservation'")
        ->toContain('Abrir reserva completa')
        ->toContain('WhatsApp')
        ->toContain('Copiar número');
});
