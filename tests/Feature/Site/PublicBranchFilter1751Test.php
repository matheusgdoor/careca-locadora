<?php
it('usa somente nome da filial no filtro público', function (): void {
    $s=file_get_contents(resource_path('js/pages/welcome.tsx'));
    expect($s)->toContain('Filial')->toContain('Todas as filiais')->toContain('{branch.name}')
        ->not->toContain('{branch.label}')
        ->not->toContain('{branch.display_name}')
        ->not->toContain('{branch.name} — {branch.city}/{branch.state}')
        ->not->toContain('{branch.name} · {branch.city}/{branch.state}');
});
it('remove lojas legado do menu', function (): void {
    $s=file_get_contents(resource_path('js/pages/welcome.tsx'));
    expect(preg_match('~>\s*Lojas\s*<~s',$s))->toBe(0);
});
