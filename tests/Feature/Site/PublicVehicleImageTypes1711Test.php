<?php

it('mantém src das imagens compatível com React TypeScript', function (): void {
    $source = file_get_contents(
        resource_path('js/pages/public/vehicle-show.tsx')
    );

    expect($source)
        ->toContain(
            'src={storageUrl(photos[activePhoto].path) ?? undefined}'
        )
        ->toContain(
            'src={storageUrl(photo.path) ?? undefined}'
        );
});
