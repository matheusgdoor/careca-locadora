<?php

it('usa file_path das fotos no catálogo público', function (): void {
    $source = file_get_contents(
        app_path('Http/Controllers/Api/PublicCatalogController.php')
    );

    expect($source)
        ->toContain("'path' => \$photo->file_path")
        ->not->toContain("'path' => \$photo->path");
});

it('aplica máscaras brasileiras no formulário da reserva pública', function (): void {
    $source = file_get_contents(
        resource_path('js/pages/public/vehicle-show.tsx')
    );

    expect($source)
        ->toContain('const formatCpfCnpj =')
        ->toContain('const formatPhone =')
        ->toContain('formatCustomerField(field, event.target.value)')
        ->toContain("field === 'document'")
        ->toContain("field === 'phone'");
});
