<?php

use App\Http\Controllers\CustomerPortal\CustomerPortalAuthController;
use App\Http\Controllers\CustomerPortal\CustomerPortalDashboardController;
use App\Http\Controllers\CustomerPortal\CustomerPortalReservationController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest:customer')->group(function (): void {
    Route::get('/cliente/acesso', [
        CustomerPortalAuthController::class,
        'login',
    ])->name('customer.login');

    Route::post('/cliente/acesso', [
        CustomerPortalAuthController::class,
        'authenticate',
    ])
        ->middleware('throttle:6,1')
        ->name('customer.authenticate');

    Route::get('/cliente/primeiro-acesso', [
        CustomerPortalAuthController::class,
        'firstAccess',
    ])->name('customer.first-access');

    Route::post('/cliente/primeiro-acesso', [
        CustomerPortalAuthController::class,
        'registerFirstAccess',
    ])
        ->middleware('throttle:4,1')
        ->name('customer.first-access.store');
});

Route::middleware(['auth:customer', 'customer.portal'])
    ->group(function (): void {
        Route::get(
            '/cliente',
            CustomerPortalDashboardController::class
        )->name('customer.dashboard');

        Route::get('/cliente/reservas', [
            CustomerPortalReservationController::class,
            'index',
        ])->name('customer.reservations');

        Route::post('/cliente/sair', [
            CustomerPortalAuthController::class,
            'logout',
        ])->name('customer.logout');
    });
