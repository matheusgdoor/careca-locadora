<?php

use Illuminate\Support\Facades\Route;

it('registra páginas públicas de vantagens e filiais', function (): void {
    expect(Route::has('public.advantages'))->toBeTrue()
        ->and(Route::has('public.branches'))->toBeTrue()
        ->and(route('public.advantages', absolute: false))->toBe('/vantagens')
        ->and(route('public.branches', absolute: false))->toBe('/filiais');
});

it('mantém páginas institucionais premium', function (): void {
    $advantages = file_get_contents(
        resource_path('js/pages/public/advantages.tsx')
    );

    $branches = file_get_contents(
        resource_path('js/pages/public/branches.tsx')
    );

    expect($advantages)
        ->toContain('Por que escolher a Careca')
        ->toContain('Reserva online')
        ->toContain('Cotação segura')
        ->and($branches)
        ->toContain('Nossas filiais')
        ->toContain('branch.name')
        ->toContain('Reservar nesta filial');
});

it('home filtra somente pela identificação da filial', function (): void {
    $welcome = file_get_contents(resource_path('js/pages/welcome.tsx'));

    expect($welcome)
        ->toContain('Filial')
        ->toContain('Todas as filiais')
        ->not->toContain('Loja de retirada')
        ->not->toContain('Todas as lojas');
});
