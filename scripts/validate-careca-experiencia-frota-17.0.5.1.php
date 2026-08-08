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

echo PHP_EOL;
echo "Careca Locadora - Experiência de Frota 17.0.5.1" . PHP_EOL;
echo "Correção da validação do 17.0.5" . PHP_EOL;
echo PHP_EOL;

$agendaPath = pathOf(
    $root,
    'resources/views/filament/pages/rental-availability-calendar.blade.php'
);

$formPath = pathOf(
    $root,
    'app/Filament/Resources/Assets/Schemas/AssetForm.php'
);

$controllerPath = pathOf(
    $root,
    'app/Http/Controllers/Api/PublicVehicleController.php'
);

$vehiclePath = pathOf(
    $root,
    'resources/js/pages/public/vehicle-show.tsx'
);

$agenda = readFileOrFail($agendaPath);
$form = readFileOrFail($formPath);
$controller = readFileOrFail($controllerPath);
$vehicle = readFileOrFail($vehiclePath);

/*
|--------------------------------------------------------------------------
| Validação tolerante a formatação
|--------------------------------------------------------------------------
| O 17.0.5 já aplicou as alterações, mas a checagem final procurava
| uma expressão TypeScript em uma única linha. O formatter quebrou
| a expressão em múltiplas linhas, causando falso negativo.
*/

foreach ([
    '/* Filtros Estáveis 17.0.5 */',
    'grid-column:1 / -1 !important',
] as $needle) {
    if (! str_contains($agenda, $needle)) {
        fwrite(STDERR, "[ERRO] Agenda 17.0.5 incompleta: {$needle}\n");
        exit(10);
    }
}

foreach ([
    "TextInput::make('doors')",
    "Toggle::make('metadata.air_conditioning')",
    "TextInput::make('metadata.luggage_capacity')",
    "Toggle::make('metadata.power_steering')",
] as $needle) {
    if (! str_contains($form, $needle)) {
        fwrite(STDERR, "[ERRO] Cadastro 17.0.5 incompleto: {$needle}\n");
        exit(11);
    }
}

foreach ([
    "'air_conditioning' =>",
    "'power_steering' =>",
    "'luggage_capacity' =>",
] as $needle) {
    if (! str_contains($controller, $needle)) {
        fwrite(STDERR, "[ERRO] API pública 17.0.5 incompleta: {$needle}\n");
        exit(12);
    }
}

foreach ([
    'Snowflake,',
    'Briefcase,',
    'vehicle.air_conditioning',
    'Ar-condicionado',
    'vehicle.luggage_capacity',
    'xl:grid-cols-6',
] as $needle) {
    if (! str_contains($vehicle, $needle)) {
        fwrite(STDERR, "[ERRO] Frontend 17.0.5 incompleto: {$needle}\n");
        exit(13);
    }
}

echo "[OK] Todas as alterações do 17.0.5 já estão presentes." . PHP_EOL;
echo "[OK] Falso negativo da validação corrigido." . PHP_EOL;
echo PHP_EOL;
echo "[OK] Experiência de Frota 17.0.5.1 validada." . PHP_EOL;
