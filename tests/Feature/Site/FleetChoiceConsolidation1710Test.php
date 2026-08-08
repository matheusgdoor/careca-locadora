<?php

use Illuminate\Support\Facades\Route;

it('padroniza características extras da frota em metadata', function (): void {
    $form = file_get_contents(
        app_path('Filament/Resources/Assets/Schemas/AssetForm.php')
    );

    $vehicle = file_get_contents(
        app_path('Http/Controllers/Api/PublicVehicleController.php')
    );

    expect($form)
        ->toContain("TextInput::make('metadata.doors')")
        ->toContain("Toggle::make('metadata.air_conditioning')")
        ->toContain("TextInput::make('metadata.luggage_capacity')")
        ->not->toContain("TextInput::make('doors')")
        ->and($vehicle)
        ->toContain("'doors' => data_get(\$vehicle->metadata, 'doors')");
});

it('preserva disponibilidade agrupada da home e cria endpoint de ativos individuais', function (): void {
    $controller = file_get_contents(
        app_path('Http/Controllers/Api/PublicCatalogController.php')
    );

    expect($controller)
        ->toContain("->groupBy('category_id')")
        ->toContain("'mode' => 'category'")
        ->toContain('public function categoryVehicles(')
        ->toContain("'doors' => data_get(\$asset->metadata, 'doors')")
        ->toContain("'path' => \$photo->file_path")
        ->toContain("'mode' => 'assets'");
});

it('registra rotas da escolha inteligente', function (): void {
    expect(Route::has('api.public.category-vehicles'))->toBeTrue()
        ->and(Route::has('public.categories.vehicles'))->toBeTrue()
        ->and(route('api.public.category-vehicles', absolute: false))
        ->toBe('/api/public/category-vehicles');
});

it('home abre categoria e cliente escolhe ativo específico', function (): void {
    $welcome = file_get_contents(resource_path('js/pages/welcome.tsx'));
    $page = file_get_contents(
        resource_path('js/pages/public/category-vehicles.tsx')
    );

    expect($welcome)
        ->toContain('/categoria/${offer.id}?starts_at=')
        ->not->toContain('/veiculos/${offer.representative_asset_id}?starts_at=')
        ->and($page)
        ->toContain("fetch('/api/public/category-vehicles'")
        ->toContain('Escolher veículo')
        ->toContain('/veiculos/${vehicle.id}?');
});
