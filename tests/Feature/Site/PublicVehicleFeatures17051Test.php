<?php

it('mantém ficha pública expandida do veículo', function (): void {
    $page = file_get_contents(
        resource_path('js/pages/public/vehicle-show.tsx')
    );

    expect($page)
        ->toContain('Snowflake,')
        ->toContain('Briefcase,')
        ->toContain('vehicle.air_conditioning')
        ->toContain('Ar-condicionado')
        ->toContain('vehicle.luggage_capacity')
        ->toContain('xl:grid-cols-6');
});

it('mantém campos adicionais no cadastro do ativo', function (): void {
    $form = file_get_contents(
        app_path('Filament/Resources/Assets/Schemas/AssetForm.php')
    );

    expect($form)
        ->toContain("TextInput::make('metadata.doors')")
        ->toContain("Toggle::make('metadata.air_conditioning')")
        ->toContain("TextInput::make('metadata.luggage_capacity')")
        ->toContain("Toggle::make('metadata.power_steering')");
});
