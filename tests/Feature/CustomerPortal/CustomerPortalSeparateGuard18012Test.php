<?php

use Illuminate\Support\Facades\Route;

it('possui guard customer separado do web', function (): void {
    expect(config('auth.guards.web.driver'))->toBe('session')
        ->and(config('auth.guards.customer.driver'))->toBe('session')
        ->and(config('auth.guards.customer.provider'))->toBe('users');
});

it('rotas privadas do portal usam auth customer', function (): void {
    $route = Route::getRoutes()->getByName('customer.dashboard');

    expect($route)->not->toBeNull()
        ->and($route?->gatherMiddleware())
        ->toContain('auth:customer')
        ->toContain('customer.portal');
});

it('dashboard administrativo continua separado', function (): void {
    $dashboard = Route::getRoutes()->getByName('dashboard');
    $customer = Route::getRoutes()->getByName('customer.dashboard');

    expect($dashboard?->uri())->toBe('dashboard')
        ->and($customer?->uri())->toBe('cliente');
});
