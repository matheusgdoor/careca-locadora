<?php

declare(strict_types=1);

$root = rtrim($argv[1] ?? 'C:\dev\careca-locadora', "\\/");

function p(string $root, string $relative): string
{
    return $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
}

function readRequired(string $path): string
{
    if (! is_file($path)) {
        fwrite(STDERR, "[ERRO] Arquivo não encontrado: {$path}\n");
        exit(2);
    }

    $content = file_get_contents($path);

    if ($content === false) {
        fwrite(STDERR, "[ERRO] Falha ao ler: {$path}\n");
        exit(3);
    }

    return $content;
}

function writeSafe(string $path, string $content): void
{
    if (file_put_contents($path, $content) === false) {
        fwrite(STDERR, "[ERRO] Falha ao gravar: {$path}\n");
        exit(4);
    }
}

echo PHP_EOL;
echo "Careca Locadora - UX Consolidada 17.0.4" . PHP_EOL;
echo "Filtros organizados + foto no catálogo + máscaras CPF/CNPJ e telefone" . PHP_EOL;
echo PHP_EOL;

/*
|--------------------------------------------------------------------------
| 1. Agenda - organizar filtros
|--------------------------------------------------------------------------
*/
$agendaPath = p(
    $root,
    'resources/views/filament/pages/rental-availability-calendar.blade.php'
);

$agenda = readRequired($agendaPath);

if (! str_contains($agenda, '/* Organização Filtros 17.0.4 */')) {
    $css = <<<'CSS'

        /* Organização Filtros 17.0.4 */
        .rental-calendar__toolbar{
            display:grid;
            grid-template-columns:minmax(0,1fr);
            gap:.85rem;
            margin-bottom:1rem;
            border:1px solid var(--border);
            border-radius:1rem;
            background:linear-gradient(180deg,rgba(255,255,255,.025),rgba(255,255,255,.012));
            padding:1rem;
        }
        .rental-calendar__controls{
            display:grid;
            grid-template-columns:minmax(200px,.85fr) minmax(260px,1.15fr) minmax(220px,.9fr) auto;
            gap:.75rem;
            width:100%;
            align-items:end;
        }
        .rental-calendar__field{
            min-width:0;
            width:100%;
        }
        .rental-calendar__actions{
            display:flex;
            align-items:center;
            justify-content:flex-end;
            gap:.5rem;
            flex-wrap:nowrap;
            min-width:max-content;
        }
        .rental-calendar__button{
            white-space:nowrap;
        }
        .rental-calendar__legend{
            width:100%;
            border-top:1px solid var(--border);
            padding-top:.8rem;
        }
        @media (max-width:1250px){
            .rental-calendar__controls{
                grid-template-columns:minmax(190px,.8fr) minmax(240px,1.2fr) minmax(210px,.9fr);
            }
            .rental-calendar__actions{
                grid-column:1/-1;
                justify-content:flex-start;
                min-width:0;
            }
        }
        @media (max-width:820px){
            .rental-calendar__toolbar{padding:.85rem}
            .rental-calendar__controls{
                grid-template-columns:minmax(0,1fr);
            }
            .rental-calendar__actions{
                grid-column:auto;
                flex-wrap:wrap;
            }
            .rental-calendar__button{
                flex:1 1 auto;
            }
        }
CSS;

    $agenda = preg_replace(
        '/\s*<\/style>/',
        "\n{$css}\n    </style>",
        $agenda,
        1,
        $count
    ) ?? $agenda;

    if (($count ?? 0) !== 1) {
        fwrite(STDERR, "[ERRO] Não foi possível inserir CSS dos filtros.\n");
        exit(5);
    }

    writeSafe($agendaPath, $agenda);
    echo "[CORRIGIDO] Filtros da agenda organizados e responsivos." . PHP_EOL;
} else {
    echo "[OK] Organização dos filtros já aplicada." . PHP_EOL;
}

/*
|--------------------------------------------------------------------------
| 2. Catálogo público - foto do ativo
|--------------------------------------------------------------------------
*/
$catalogPath = p(
    $root,
    'app/Http/Controllers/Api/PublicCatalogController.php'
);

$catalog = readRequired($catalogPath);

if (str_contains($catalog, "'path' => \$photo->path")) {
    $catalog = str_replace(
        "'path' => \$photo->path",
        "'path' => \$photo->file_path",
        $catalog
    );

    echo "[CORRIGIDO] Catálogo público passa a usar AssetPhoto.file_path." . PHP_EOL;
}

if (
    str_contains($catalog, "'photos' => \$asset->photos")
    && ! str_contains($catalog, "->filter(fn (\$photo): bool => filled(\$photo->file_path))")
) {
    $catalog = str_replace(
        "'photos' => \$asset->photos\n                ->sortByDesc('is_featured')",
        "'photos' => \$asset->photos\n                ->filter(fn (\$photo): bool => filled(\$photo->file_path))\n                ->sortByDesc('is_featured')",
        $catalog,
        $filterCount
    );

    if (($filterCount ?? 0) > 0) {
        echo "[CORRIGIDO] Fotos vazias são ignoradas no resultado da consulta." . PHP_EOL;
    }
}

writeSafe($catalogPath, $catalog);

/*
|--------------------------------------------------------------------------
| 3. Reserva pública - máscaras brasileiras
|--------------------------------------------------------------------------
*/
$vehiclePath = p(
    $root,
    'resources/js/pages/public/vehicle-show.tsx'
);

$vehicle = readRequired($vehiclePath);

if (! str_contains($vehicle, 'const formatCpfCnpj =')) {
    $anchor = <<<'TSX'
const money = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});
TSX;

    $helpers = <<<'TSX'
const money = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

const onlyDigits = (value: string): string => value.replace(/\D/g, '');

const formatCpfCnpj = (value: string): string => {
    const digits = onlyDigits(value).slice(0, 14);

    if (digits.length <= 11) {
        return digits
            .replace(/^(\d{3})(\d)/, '$1.$2')
            .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
            .replace(/\.(\d{3})(\d)/, '.$1-$2');
    }

    return digits
        .replace(/^(\d{2})(\d)/, '$1.$2')
        .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/\.(\d{3})(\d)/, '.$1/$2')
        .replace(/(\d{4})(\d)/, '$1-$2');
};

const formatPhone = (value: string): string => {
    const digits = onlyDigits(value).slice(0, 11);

    if (digits.length <= 10) {
        return digits
            .replace(/^(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{4})(\d)/, '$1-$2');
    }

    return digits
        .replace(/^(\d{2})(\d)/, '($1) $2')
        .replace(/(\d{5})(\d)/, '$1-$2');
};

const formatCustomerField = (field: string, value: string): string => {
    if (field === 'document') return formatCpfCnpj(value);
    if (field === 'phone') return formatPhone(value);

    return value;
};
TSX;

    if (! str_contains($vehicle, $anchor)) {
        fwrite(STDERR, "[ERRO] Bloco money do vehicle-show.tsx não localizado.\n");
        exit(6);
    }

    $vehicle = str_replace($anchor, $helpers, $vehicle);
    echo "[CORRIGIDO] Funções de máscara CPF/CNPJ e telefone adicionadas." . PHP_EOL;
}

if (str_contains($vehicle, '[field]: event.target.value,')) {
    $vehicle = str_replace(
        '[field]: event.target.value,',
        '[field]: formatCustomerField(field, event.target.value),',
        $vehicle
    );

    echo "[CORRIGIDO] Campos do cliente aplicam máscara durante a digitação." . PHP_EOL;
}

if (
    str_contains($vehicle, 'placeholder={label}')
    && ! str_contains($vehicle, "inputMode={field === 'document'")
) {
    $vehicle = str_replace(
        '                                                placeholder={label}',
        <<<'TSX'
                                                placeholder={label}
                                                inputMode={
                                                    field === 'document' || field === 'phone'
                                                        ? 'numeric'
                                                        : field === 'email'
                                                          ? 'email'
                                                          : 'text'
                                                }
                                                maxLength={
                                                    field === 'document'
                                                        ? 18
                                                        : field === 'phone'
                                                          ? 15
                                                          : undefined
                                                }
                                                autoComplete={
                                                    field === 'name'
                                                        ? 'name'
                                                        : field === 'email'
                                                          ? 'email'
                                                          : field === 'phone'
                                                            ? 'tel'
                                                            : 'off'
                                                }
TSX,
        $vehicle,
        $attributesCount
    );

    if (($attributesCount ?? 0) > 0) {
        echo "[CORRIGIDO] Teclado, limite e autocomplete dos campos ajustados." . PHP_EOL;
    }
}

writeSafe($vehiclePath, $vehicle);

/*
|--------------------------------------------------------------------------
| Validação final do patch
|--------------------------------------------------------------------------
*/
$agendaCheck = readRequired($agendaPath);
$catalogCheck = readRequired($catalogPath);
$vehicleCheck = readRequired($vehiclePath);

if (! str_contains($agendaCheck, '/* Organização Filtros 17.0.4 */')) {
    fwrite(STDERR, "[ERRO] CSS dos filtros não foi aplicado.\n");
    exit(10);
}

if (str_contains($catalogCheck, "'path' => \$photo->path")) {
    fwrite(STDERR, "[ERRO] Catálogo ainda usa photo->path.\n");
    exit(11);
}

if (! str_contains($catalogCheck, "'path' => \$photo->file_path")) {
    fwrite(STDERR, "[ERRO] Catálogo não está usando file_path.\n");
    exit(12);
}

if (
    ! str_contains($vehicleCheck, 'const formatCpfCnpj =')
    || ! str_contains($vehicleCheck, 'const formatPhone =')
    || ! str_contains($vehicleCheck, 'formatCustomerField(field, event.target.value)')
) {
    fwrite(STDERR, "[ERRO] Máscaras do formulário público não foram aplicadas.\n");
    exit(13);
}

echo PHP_EOL;
echo "[OK] UX Consolidada 17.0.4 aplicada com sucesso." . PHP_EOL;
