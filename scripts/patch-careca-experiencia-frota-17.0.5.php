<?php

declare(strict_types=1);

$root = rtrim($argv[1] ?? 'C:\dev\careca-locadora', "\\/");

function pathOf(string $root, string $relative): string
{
    return $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
}

function readFileOrFail(string $path): string
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

function writeFileOrFail(string $path, string $content): void
{
    if (file_put_contents($path, $content) === false) {
        fwrite(STDERR, "[ERRO] Falha ao gravar: {$path}\n");
        exit(4);
    }
}

echo PHP_EOL;
echo "Careca Locadora - Experiência de Frota 17.0.5" . PHP_EOL;
echo "Filtros estáveis + ficha do veículo estilo locadora" . PHP_EOL;
echo PHP_EOL;

/*
|--------------------------------------------------------------------------
| 1. Agenda: quebra real dos filtros em linhas
|--------------------------------------------------------------------------
*/
$agendaPath = pathOf(
    $root,
    'resources/views/filament/pages/rental-availability-calendar.blade.php'
);

$agenda = readFileOrFail($agendaPath);

$css = <<<'CSS'

        /* Filtros Estáveis 17.0.5 */
        .rental-calendar__controls{
            display:grid !important;
            grid-template-columns:minmax(200px,.85fr) minmax(280px,1.15fr) minmax(220px,.9fr) !important;
            gap:.75rem !important;
            width:100% !important;
            align-items:end !important;
        }
        .rental-calendar__actions{
            grid-column:1 / -1 !important;
            display:flex !important;
            align-items:center !important;
            justify-content:flex-start !important;
            gap:.5rem !important;
            flex-wrap:wrap !important;
            min-width:0 !important;
            margin-top:.1rem !important;
        }
        .rental-calendar__actions > *{
            flex:0 0 auto !important;
        }
        .rental-calendar__legend{
            width:100% !important;
            margin-top:.1rem !important;
            padding-top:.8rem !important;
            border-top:1px solid var(--border) !important;
        }
        @media (max-width:900px){
            .rental-calendar__controls{
                grid-template-columns:minmax(0,1fr) minmax(0,1fr) !important;
            }
            .rental-calendar__field:last-of-type{
                grid-column:1 / -1 !important;
            }
        }
        @media (max-width:640px){
            .rental-calendar__controls{
                grid-template-columns:minmax(0,1fr) !important;
            }
            .rental-calendar__field:last-of-type{
                grid-column:auto !important;
            }
            .rental-calendar__actions{
                grid-column:auto !important;
            }
            .rental-calendar__actions > *{
                flex:1 1 calc(50% - .5rem) !important;
            }
        }
CSS;

if (! str_contains($agenda, '/* Filtros Estáveis 17.0.5 */')) {
    $agenda = preg_replace(
        '/\s*<\/style>/',
        "\n{$css}\n    </style>",
        $agenda,
        1,
        $count
    ) ?? $agenda;

    if (($count ?? 0) !== 1) {
        fwrite(STDERR, "[ERRO] Não foi possível inserir CSS da agenda.\n");
        exit(5);
    }

    writeFileOrFail($agendaPath, $agenda);
    echo "[CORRIGIDO] Filtros agora usam 3 campos na primeira linha e ações na segunda." . PHP_EOL;
} else {
    echo "[OK] Filtros estáveis já presentes." . PHP_EOL;
}

/*
|--------------------------------------------------------------------------
| 2. Cadastro de ativos: portas e conforto
|--------------------------------------------------------------------------
*/
$formPath = pathOf(
    $root,
    'app/Filament/Resources/Assets/Schemas/AssetForm.php'
);

$form = readFileOrFail($formPath);

if (! str_contains($form, "TextInput::make('doors')")) {
    $anchor = <<<'PHP'
                        TextInput::make('seats')
                            ->label('Passageiros')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100),
PHP;

    $replacement = <<<'PHP'
                        TextInput::make('seats')
                            ->label('Lugares')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100),

                        TextInput::make('doors')
                            ->label('Quantidade de portas')
                            ->numeric()
                            ->minValue(2)
                            ->maxValue(6)
                            ->placeholder('Ex.: 2'),

                        TextInput::make('metadata.luggage_capacity')
                            ->label('Capacidade de malas')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(20)
                            ->placeholder('Ex.: 2')
                            ->helperText('Quantidade aproximada de malas padrão.'),

                        Toggle::make('metadata.air_conditioning')
                            ->label('Ar-condicionado')
                            ->default(false)
                            ->inline(false),

                        Toggle::make('metadata.power_steering')
                            ->label('Direção assistida')
                            ->default(true)
                            ->inline(false),
PHP;

    if (! str_contains($form, $anchor)) {
        fwrite(STDERR, "[ERRO] Campo Passageiros não localizado no AssetForm.\n");
        exit(6);
    }

    $form = str_replace($anchor, $replacement, $form, $count);

    if ($count !== 1) {
        fwrite(STDERR, "[ERRO] Falha ao inserir características do veículo.\n");
        exit(7);
    }

    writeFileOrFail($formPath, $form);
    echo "[CORRIGIDO] Cadastro ganhou portas, malas, ar-condicionado e direção assistida." . PHP_EOL;
} else {
    echo "[OK] Características adicionais já estão no cadastro." . PHP_EOL;
}

/*
|--------------------------------------------------------------------------
| 3. API pública: expor conforto do veículo
|--------------------------------------------------------------------------
*/
$controllerPath = pathOf(
    $root,
    'app/Http/Controllers/Api/PublicVehicleController.php'
);

$controller = readFileOrFail($controllerPath);

if (! str_contains($controller, "'air_conditioning' =>")) {
    $anchor = "                'color' => \$vehicle->color,";

    $replacement = <<<'PHP'
                'color' => $vehicle->color,
                'air_conditioning' => (bool) data_get(
                    $vehicle->metadata,
                    'air_conditioning',
                    false
                ),
                'power_steering' => (bool) data_get(
                    $vehicle->metadata,
                    'power_steering',
                    false
                ),
                'luggage_capacity' => data_get(
                    $vehicle->metadata,
                    'luggage_capacity'
                ),
PHP;

    if (! str_contains($controller, $anchor)) {
        fwrite(STDERR, "[ERRO] Campo color não localizado no PublicVehicleController.\n");
        exit(8);
    }

    $controller = str_replace($anchor, $replacement, $controller, $count);

    if ($count !== 1) {
        fwrite(STDERR, "[ERRO] Falha ao expor conforto do veículo.\n");
        exit(9);
    }

    writeFileOrFail($controllerPath, $controller);
    echo "[CORRIGIDO] API pública expõe características de conforto." . PHP_EOL;
} else {
    echo "[OK] API de conforto já presente." . PHP_EOL;
}

/*
|--------------------------------------------------------------------------
| 4. Site: ficha visual semelhante às grandes locadoras
|--------------------------------------------------------------------------
*/
$vehiclePath = pathOf(
    $root,
    'resources/js/pages/public/vehicle-show.tsx'
);

$vehicle = readFileOrFail($vehiclePath);

if (! str_contains($vehicle, 'Snowflake,')) {
    $vehicle = str_replace(
        '    ShieldCheck,',
        "    ShieldCheck,\n    Snowflake,\n    Briefcase,",
        $vehicle,
        $count
    );

    if ($count !== 1) {
        fwrite(STDERR, "[ERRO] Import ShieldCheck não localizado.\n");
        exit(10);
    }
}

if (! str_contains($vehicle, 'air_conditioning?: boolean')) {
    $anchor = "    color?: string | null;";

    $replacement = <<<'TSX'
    color?: string | null;
    air_conditioning?: boolean;
    power_steering?: boolean;
    luggage_capacity?: number | null;
TSX;

    if (! str_contains($vehicle, $anchor)) {
        fwrite(STDERR, "[ERRO] Tipo Vehicle.color não localizado.\n");
        exit(11);
    }

    $vehicle = str_replace($anchor, $replacement, $vehicle, $count);
}

if (! str_contains($vehicle, "vehicle.air_conditioning ? 'Ar-condicionado'")) {
    $old = <<<'TSX'
                                <div className="mt-7 grid grid-cols-2 gap-3 md:grid-cols-4">
                                    {[
                                        [Users, `${vehicle.seats ?? '—'} lugares`],
                                        [Gauge, vehicle.transmission ?? 'Câmbio'],
                                        [Fuel, vehicle.fuel_type ?? 'Combustível'],
                                        [CarFront, `${vehicle.doors ?? '—'} portas`],
                                    ].map(([Icon, label], index) => {
TSX;

    $new = <<<'TSX'
                                <div className="mt-7 grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
                                    {[
                                        [Users, `${vehicle.seats ?? '—'} lugares`],
                                        [CarFront, `${vehicle.doors ?? '—'} portas`],
                                        [
                                            Snowflake,
                                            vehicle.air_conditioning
                                                ? 'Ar-condicionado'
                                                : 'Sem ar-condicionado',
                                        ],
                                        [Gauge, vehicle.transmission ?? 'Câmbio'],
                                        [Fuel, vehicle.fuel_type ?? 'Combustível'],
                                        [
                                            Briefcase,
                                            vehicle.luggage_capacity
                                                ? `${vehicle.luggage_capacity} malas`
                                                : 'Porta-malas',
                                        ],
                                    ].map(([Icon, label], index) => {
TSX;

    if (! str_contains($vehicle, $old)) {
        fwrite(STDERR, "[ERRO] Grade atual das características não localizada.\n");
        exit(12);
    }

    $vehicle = str_replace($old, $new, $vehicle, $count);

    if ($count !== 1) {
        fwrite(STDERR, "[ERRO] Falha ao atualizar ficha visual do veículo.\n");
        exit(13);
    }

    echo "[CORRIGIDO] Ficha pública agora mostra 6 características no padrão de locadora." . PHP_EOL;
}

writeFileOrFail($vehiclePath, $vehicle);

/*
|--------------------------------------------------------------------------
| Validação
|--------------------------------------------------------------------------
*/
$agendaCheck = readFileOrFail($agendaPath);
$formCheck = readFileOrFail($formPath);
$controllerCheck = readFileOrFail($controllerPath);
$vehicleCheck = readFileOrFail($vehiclePath);

foreach ([
    '/* Filtros Estáveis 17.0.5 */',
    'grid-column:1 / -1 !important',
] as $needle) {
    if (! str_contains($agendaCheck, $needle)) {
        fwrite(STDERR, "[ERRO] Validação agenda falhou: {$needle}\n");
        exit(20);
    }
}

foreach ([
    "TextInput::make('doors')",
    "Toggle::make('metadata.air_conditioning')",
    "TextInput::make('metadata.luggage_capacity')",
] as $needle) {
    if (! str_contains($formCheck, $needle)) {
        fwrite(STDERR, "[ERRO] Validação cadastro falhou: {$needle}\n");
        exit(21);
    }
}

foreach ([
    "'air_conditioning' =>",
    "'luggage_capacity' =>",
] as $needle) {
    if (! str_contains($controllerCheck, $needle)) {
        fwrite(STDERR, "[ERRO] Validação API falhou: {$needle}\n");
        exit(22);
    }
}

foreach ([
    'Snowflake,',
    'Briefcase,',
    "vehicle.air_conditioning ? 'Ar-condicionado'",
    'xl:grid-cols-6',
] as $needle) {
    if (! str_contains($vehicleCheck, $needle)) {
        fwrite(STDERR, "[ERRO] Validação frontend falhou: {$needle}\n");
        exit(23);
    }
}

echo PHP_EOL;
echo "[OK] Experiência de Frota 17.0.5 aplicada com sucesso." . PHP_EOL;
