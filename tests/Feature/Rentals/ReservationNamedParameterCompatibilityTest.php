<?php

it('não usa o named argument antigo selectedItemIds', function (): void {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            app_path(),
            FilesystemIterator::SKIP_DOTS
        )
    );

    $violations = [];

    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $source = file_get_contents($file->getPathname());

        if (str_contains($source, 'selectedItemIds:')) {
            $violations[] = $file->getPathname();
        }
    }

    expect($violations)->toBe([]);
});

it('mantém a assinatura atual do motor comercial', function (): void {
    $service = file_get_contents(
        app_path('Services/Rentals/RentalCommercialPricingService.php')
    );

    expect($service)
        ->toContain('function quote(ReservationSearch $search,array $itemIds=[]')
        ->not->toContain('$selectedItemIds');
});
