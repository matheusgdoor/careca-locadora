<?php
it('mantém agenda premium clicável e contextual', function (): void {
    $source = file_get_contents(resource_path('views/filament/pages/rental-availability-calendar.blade.php'));

    expect($source)
        ->toContain('rental-calendar__slot--interactive')
        ->toContain('rental-calendar__popover')
        ->toContain('RentalReservationResource::getUrl')
        ->toContain('Clique no card para abrir a reserva completa.')
        ->toContain('Site público')
        ->toContain('Em preparação')
        ->toContain('rental-calendar__today');
});
