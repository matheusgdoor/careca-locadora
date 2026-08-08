<?php

declare(strict_types=1);

$root = rtrim($argv[1] ?? 'C:\dev\careca-locadora', "\\/");

function pathOf(string $root, string $relative): string
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
echo "Careca Locadora - Agenda Premium 16.1.3" . PHP_EOL;
echo "Correção final dos testes da Agenda Premium" . PHP_EOL;
echo PHP_EOL;

$viewPath = pathOf(
    $root,
    'resources/views/filament/pages/rental-availability-calendar.blade.php'
);

$view = readRequired($viewPath);

foreach ([
    'rental-calendar__slot--interactive',
    'rental-calendar__popover',
    'RentalReservationResource::getUrl',
] as $needle) {
    if (! str_contains($view, $needle)) {
        fwrite(STDERR, "[ERRO] A melhoria visual 16.1.2 não está completa: {$needle}\n");
        exit(5);
    }
}

echo "[OK] Agenda visual 16.1.2 já está aplicada." . PHP_EOL;

$testPath = pathOf(
    $root,
    'tests/Feature/Rentals/RentalAvailabilityCalendarPremiumTest.php'
);

$test = readRequired($testPath);

$test = str_replace(
    '        ->toContain("$asset = $row[\'asset\']")',
    '        ->toContain(\'$asset = $row[\\\'asset\\\']\')',
    $test
);

$test = str_replace(
    '        ->toContain("$items = $row[\'items\']")',
    '        ->toContain(\'$items = $row[\\\'items\\\']\')',
    $test
);

// Fallback: reescreve linhas quebradas se vierem com espaçamento diferente.
$test = preg_replace(
    '/^\s*->toContain\("\$asset\s*=\s*\$row\[\'asset\'\]"\)\s*$/m',
    '        ->toContain(\'$asset = $row[\\\'asset\\\']\')',
    $test
) ?? $test;

$test = preg_replace(
    '/^\s*->toContain\("\$items\s*=\s*\$row\[\'items\'\]"\)\s*$/m',
    '        ->toContain(\'$items = $row[\\\'items\\\']\')',
    $test
) ?? $test;

writeSafe($testPath, $test);

echo "[CORRIGIDO] Teste legado usa strings literais sem interpolação PHP." . PHP_EOL;

$check = readRequired($testPath);

if (str_contains($check, '->toContain("$asset = $row[')) {
    fwrite(STDERR, "[ERRO] Linha problemática de asset ainda existe.\n");
    exit(10);
}

if (str_contains($check, '->toContain("$items = $row[')) {
    fwrite(STDERR, "[ERRO] Linha problemática de items ainda existe.\n");
    exit(11);
}

echo PHP_EOL;
echo "[OK] Agenda Premium 16.1.3 aplicada." . PHP_EOL;
