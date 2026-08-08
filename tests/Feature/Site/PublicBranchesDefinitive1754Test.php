<?php

it('remove cidade e uf da presença regional da home', function (): void {
    $source = file_get_contents(
        resource_path('js/pages/welcome.tsx')
    );

    expect($source)
        ->toContain('Presença regional')
        ->toContain('{branch.name}')
        ->not->toContain('{branch.city}');
});

it('pagina de filiais usa somente nome e contato', function (): void {
    $source = file_get_contents(
        resource_path('js/pages/public/branches.tsx')
    );

    expect($source)
        ->toContain('{branch.name}')
        ->toContain('branch.whatsapp ?? branch.phone')
        ->toContain('Reservar nesta filial')
        ->not->toContain('branch.city')
        ->not->toContain('branch.state')
        ->not->toContain('branch.address')
        ->not->toContain('branch.number')
        ->not->toContain('branch.neighborhood')
        ->not->toContain('<MapPin');
});

it('corrige textos pt br da pagina de filiais', function (): void {
    $source = file_get_contents(
        resource_path('js/pages/public/branches.tsx')
    );

    expect($source)
        ->toContain('Início')
        ->toContain('locação')
        ->toContain('Nenhuma filial pública disponível')
        ->not->toContain('InÃ')
        ->not->toContain('locaÃ');
});
