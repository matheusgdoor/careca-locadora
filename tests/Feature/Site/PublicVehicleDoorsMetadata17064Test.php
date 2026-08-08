<?php

it('mantém portas no metadata sem exigir coluna no banco', function (): void {
    $form = file_get_contents(
        app_path('Filament/Resources/Assets/Schemas/AssetForm.php')
    );

    expect($form)
        ->toContain("TextInput::make('metadata.doors')")
        ->not->toContain("TextInput::make('doors')");
});

it('apis públicas leem portas do metadata', function (): void {
    $vehicle = file_get_contents(
        app_path('Http/Controllers/Api/PublicVehicleController.php')
    );

    $catalog = file_get_contents(
        app_path('Http/Controllers/Api/PublicCatalogController.php')
    );

    expect($vehicle)
        ->toContain("'doors' => data_get(\$vehicle->metadata, 'doors')")
        ->and($catalog)
        ->toContain("'doors' => data_get(\$asset->metadata, 'doors')")
        ->not->toContain("'doors' => \$asset->doors");
});
