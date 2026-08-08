<?php

it('transforma a agenda em centro operacional', function (): void {
    $source = file_get_contents(
        resource_path('views/filament/pages/rental-availability-calendar.blade.php')
    );

    expect($source)
        ->toContain('rental-ops__kpis')
        ->toContain('Ativos exibidos')
        ->toContain('Pendentes')
        ->toContain('Reservas do site')
        ->toContain('Valor previsto')
        ->toContain('selectedReservation: null')
        ->toContain('openReservation(data)')
        ->toContain('rental-ops__drawer')
        ->toContain('Abrir reserva completa')
        ->toContain('WhatsApp')
        ->toContain('Copiar número');
});

it('preserva a agenda premium existente', function (): void {
    $source = file_get_contents(
        resource_path('views/filament/pages/rental-availability-calendar.blade.php')
    );

    expect($source)
        ->toContain('rental-calendar__slot--interactive')
        ->toContain('rental-calendar__popover')
        ->toContain('RentalReservationResource::getUrl')
        ->toContain('@forelse ($this->schedule as $row)')
        ->toContain('slot-free')
        ->toContain('slot-reserved')
        ->toContain('slot-rented');
});
