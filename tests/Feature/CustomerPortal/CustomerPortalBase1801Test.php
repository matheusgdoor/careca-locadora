<?php

use Illuminate\Support\Facades\Route;

it('registra rotas base do portal do cliente', function (): void {
    expect(Route::has('customer.login'))->toBeTrue()
        ->and(Route::has('customer.first-access'))->toBeTrue()
        ->and(Route::has('customer.dashboard'))->toBeTrue()
        ->and(Route::has('customer.reservations'))->toBeTrue()
        ->and(Route::has('customer.logout'))->toBeTrue();
});

it('mantém portal separado do painel administrativo', function (): void {
    $app = file_get_contents(resource_path('js/app.tsx'));
    $user = file_get_contents(app_path('Models/User.php'));

    expect($app)
        ->toContain("case name.startsWith('customer/'):")
        ->and($user)
        ->toContain("data_get(\$this->metadata, 'portal_only'");
});

it('possui telas essenciais do mvp', function (): void {
    foreach ([
        'login.tsx',
        'first-access.tsx',
        'dashboard.tsx',
        'reservations.tsx',
    ] as $file) {
        expect(file_exists(resource_path("js/pages/customer/{$file}")))->toBeTrue();
    }
});
