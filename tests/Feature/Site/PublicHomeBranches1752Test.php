<?php

it('mantém menu público canônico sem duplicidades', function (): void {
    $source = file_get_contents(resource_path('js/pages/welcome.tsx'));

    expect(substr_count($source, 'href="/vantagens"'))->toBe(1)
        ->and(substr_count($source, 'href="/filiais"'))->toBe(1)
        ->and(preg_match('~>\s*Lojas\s*<~s', $source))->toBe(0);
});

it('usa somente nome da filial como identidade', function (): void {
    $source = file_get_contents(resource_path('js/pages/welcome.tsx'));

    expect($source)
        ->toContain('Filial')
        ->toContain('Todas as filiais')
        ->toContain('{branch.name}')
        ->not->toContain('{branch.label}')
        ->not->toContain('{branch.display_name}')
        ->not->toContain('{branch.name} — {branch.city}/{branch.state}')
        ->not->toContain('{branch.name} · {branch.city}/{branch.state}');
});
