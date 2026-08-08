<?php

use App\Http\Middleware\EnsureCustomerPortalUser;
use App\Services\CustomerPortal\CustomerPortalAccountService;
use Illuminate\Http\Request;

it('middleware do portal recebe somente request e next no handle', function (): void {
    $reflection = new ReflectionMethod(
        EnsureCustomerPortalUser::class,
        'handle'
    );

    expect($reflection->getNumberOfParameters())->toBe(2);
});

it('middleware injeta serviço pelo construtor', function (): void {
    $reflection = new ReflectionClass(EnsureCustomerPortalUser::class);
    $constructor = $reflection->getConstructor();

    expect($constructor)->not->toBeNull()
        ->and($constructor?->getNumberOfParameters())->toBe(1)
        ->and((string) $constructor?->getParameters()[0]->getType())
        ->toBe(CustomerPortalAccountService::class);
});
