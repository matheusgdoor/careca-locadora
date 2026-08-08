<?php

it('cria página pública de escolha por categoria', function (): void {
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

it('home deixa de obrigar veículo representativo', function (): void {
    $source = file_get_contents(resource_path('js/pages/welcome.tsx'));

    expect($source)
        ->toContain('/categoria/${offer.id}?starts_at=')
        ->not->toContain('/veiculos/${offer.representative_asset_id}?starts_at=');
});

it('availability pública expõe características para escolha', function (): void {
    $source = file_get_contents(
        app_path('Http/Controllers/Api/PublicCatalogController.php')
    );

    expect($source)
        ->toContain("'doors' => \$asset->doors")
        ->toContain("'air_conditioning' =>")
        ->toContain("'luggage_capacity' =>");
});

it('registra rota pública da categoria', function (): void {
    $source = file_get_contents(base_path('routes/web.php'));

    expect($source)
        ->toContain("Route::get('/categoria/{category}'")
        ->toContain("name('public.categories.vehicles')");
});
