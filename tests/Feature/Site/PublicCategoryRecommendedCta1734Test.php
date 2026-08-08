<?php

it('mantém CTA recomendado em uma linha', function (): void {
    $page = file_get_contents(
        resource_path('js/pages/public/category-vehicles.tsx')
    );

    expect($page)
        ->toContain('Escolher recomendado')
        ->toContain('whitespace-nowrap')
        ->toContain('text-sm')
        ->toContain('px-3');
});
