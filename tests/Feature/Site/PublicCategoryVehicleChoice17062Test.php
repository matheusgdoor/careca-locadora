<?php

it('mantém escolha por veículo específico dentro da categoria', function (): void {
    $page = file_get_contents(
        resource_path('js/pages/public/category-vehicles.tsx')
    );

    expect($page)
        ->toContain('/api/public/availability')
        ->toContain('Veículos realmente disponíveis')
        ->toContain('Escolher veículo')
        ->toContain('/veiculos/${vehicle.id}?');
});

it('availability possui ficha completa do ativo', function (): void {
    $source = file_get_contents(
        app_path('Http/Controllers/Api/PublicCatalogController.php')
    );

    $start = strpos($source, 'public function availability(');
    $end = strpos($source, 'public function quote(');
    $availability = substr($source, $start, $end - $start);

    expect($availability)
        ->toContain("'seats' => \$asset->seats")
        ->toContain("'doors' => \$asset->doors")
        ->toContain("'air_conditioning' =>")
        ->toContain("'luggage_capacity' =>");
});

it('home abre categoria antes da escolha do ativo', function (): void {
    $welcome = file_get_contents(resource_path('js/pages/welcome.tsx'));
    $routes = file_get_contents(base_path('routes/web.php'));

    expect($welcome)
        ->toContain('/categoria/${offer.id}?starts_at=')
        ->not->toContain('/veiculos/${offer.representative_asset_id}?starts_at=')
        ->and($routes)
        ->toContain("name('public.categories.vehicles')");
});
