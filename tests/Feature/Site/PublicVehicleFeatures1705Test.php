<?php

it('expõe ficha de conforto do veículo no site público', function (): void {
    $controller = file_get_contents(
        app_path('Http/Controllers/Api/PublicVehicleController.php')
    );

    $page = file_get_contents(
        resource_path('js/pages/public/vehicle-show.tsx')
    );

    expect($controller)
        ->toContain("'air_conditioning' =>")
        ->toContain("'power_steering' =>")
        ->toContain("'luggage_capacity' =>")
        ->and($page)
        ->toContain('Snowflake,')
        ->toContain('Briefcase,')
        ->toContain("vehicle.air_conditioning ? 'Ar-condicionado'")
        ->toContain('xl:grid-cols-6');
});

it('mantém características adicionais no cadastro de ativos', function (): void {
    $source = file_get_contents(
        app_path('Filament/Resources/Assets/Schemas/AssetForm.php')
    );

    expect($source)
        ->toContain("TextInput::make('doors')")
        ->toContain('Quantidade de portas')
        ->toContain("Toggle::make('metadata.air_conditioning')")
        ->toContain("TextInput::make('metadata.luggage_capacity')")
        ->toContain("Toggle::make('metadata.power_steering')");
});
