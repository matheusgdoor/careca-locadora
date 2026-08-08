<?php

use Illuminate\Support\Facades\Route;

it('registra detalhe da reserva no portal', function (): void {
    expect(Route::has('customer.reservations.show'))->toBeTrue();
});

it('primeiro acesso possui mascara dinamica de cpf cnpj', function (): void {
    $source = file_get_contents(
        resource_path('js/pages/customer/first-access.tsx')
    );

    expect($source)
        ->toContain('maskDocument')
        ->toContain('onlyDigits')
        ->toContain("slice(0, 14)")
        ->toContain('A máscara muda automaticamente entre CPF e CNPJ.');
});

it('detalhe da reserva possui timeline e resumo financeiro', function (): void {
    $source = file_get_contents(
        resource_path('js/pages/customer/reservation-show.tsx')
    );

    expect($source)
        ->toContain('Acompanhamento')
        ->toContain('Sua locação')
        ->toContain('Resumo financeiro')
        ->toContain('Falar pelo WhatsApp');
});
