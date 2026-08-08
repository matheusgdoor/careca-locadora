<?php

it('mostra somente o nome da filial na presença regional', function (): void {
    $source = file_get_contents(
        resource_path('js/pages/welcome.tsx')
    );

    expect($source)
        ->toContain('{branch.name}')
        ->not->toContain(
            '{branch.name} — {branch.city}/{branch.state}'
        )
        ->not->toContain(
            '{branch.name} · {branch.city}/{branch.state}'
        )
        ->not->toContain('{branch.city}/{branch.state}');
});

it('preserva a navegação pública corrigida', function (): void {
    $source = file_get_contents(
        resource_path('js/pages/welcome.tsx')
    );

    expect($source)
        ->toContain('Reservar')
        ->toContain('Categorias')
        ->toContain('href="/vantagens"')
        ->toContain('href="/filiais"');
});
