<?php

it('mantém escolha individual dos veículos disponíveis por categoria', function (): void {
    $page = file_get_contents(
        resource_path('js/pages/public/category-vehicles.tsx')
    );

    expect($page)
        ->toContain('/api/public/availability')
        ->toContain('Veículos realmente disponíveis')
        ->toContain('Escolher veículo')
        ->toContain('Disponibilidade em tempo real')
        ->toContain('/veiculos/${vehicle.id}?');
});

it('availability entrega ficha necessária para a escolha', function (): void {
    $source = file_get_contents(
        app_path('Http/Controllers/Api/PublicCatalogController.php')
    );

    expect($source)
        ->toContain("'seats' => \$asset->seats")
        ->toContain("'doors' => \$asset->doors")
        ->toContain("'air_conditioning' =>")
        ->toContain("'luggage_capacity' =>");
});

it('catálogo abre a categoria e não força ativo representativo', function (): void {
    $welcome = file_get_contents(resource_path('js/pages/welcome.tsx'));
    $routes = file_get_contents(base_path('routes/web.php'));

    expect($welcome)
        ->toContain('/categoria/${offer.id}?starts_at=')
        ->not->toContain('/veiculos/${offer.representative_asset_id}?starts_at=')
        ->and($routes)
        ->toContain("Route::get('/categoria/{category}'")
        ->toContain("name('public.categories.vehicles')");
});
