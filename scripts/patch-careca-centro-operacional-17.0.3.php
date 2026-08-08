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
        fwrite(STDERR, "[ERRO] Não foi possível ler: {$path}\n");
        exit(3);
    }

    return $content;
}

function writeSafe(string $path, string $content): void
{
    if (file_put_contents($path, $content) === false) {
        fwrite(STDERR, "[ERRO] Não foi possível gravar: {$path}\n");
        exit(4);
    }
}

echo PHP_EOL;
echo "Careca Locadora - Centro Operacional 17.0.3" . PHP_EOL;
echo "Alinhamento dos testes com clique global Alpine" . PHP_EOL;
echo PHP_EOL;

$viewPath = pathOf(
    $root,
    'resources/views/filament/pages/rental-availability-calendar.blade.php'
);

$view = readRequired($viewPath);

foreach ([
    '@careca-open-reservation.window="openReservation($event.detail)"',
    '@click.stop.prevent="$dispatch(\'careca-open-reservation\'',
    'Abrir reserva completa',
    'Ativos exibidos',
    'rental-ops__drawer',
] as $needle) {
    if (! str_contains($view, $needle)) {
        fwrite(STDERR, "[ERRO] Base 17.0.2 incompleta: {$needle}\n");
        exit(5);
    }
}

echo "[OK] Implementação 17.0.2 está presente." . PHP_EOL;

$legacyTestPath = pathOf(
    $root,
    'tests/Feature/Rentals/RentalOperationalCenter1701Test.php'
);

if (is_file($legacyTestPath)) {
    $test = readRequired($legacyTestPath);

    $old = '        ->toContain(\'@click.prevent="openReservation([\')';
    $new =
        '        ->toContain(\'@careca-open-reservation.window="openReservation($event.detail)"\')' . PHP_EOL
        . '        ->toContain("@click.stop.prevent=\"\\$dispatch(\'careca-open-reservation\'")';

    if (str_contains($test, $old)) {
        $test = str_replace($old, $new, $test);
        echo "[CORRIGIDO] Teste 17.0.1 atualizado para evento Alpine global." . PHP_EOL;
    } elseif (
        str_contains($test, '@careca-open-reservation.window')
        && str_contains($test, 'careca-open-reservation')
    ) {
        echo "[OK] Teste 17.0.1 já está atualizado." . PHP_EOL;
    } else {
        fwrite(STDERR, "[ERRO] Expectativa antiga do teste 17.0.1 não localizada com segurança.\n");
        exit(6);
    }

    writeSafe($legacyTestPath, $test);
} else {
    fwrite(STDERR, "[ERRO] Teste legado 17.0.1 não encontrado.\n");
    exit(7);
}

echo PHP_EOL;
echo "[OK] Centro Operacional 17.0.3 aplicado." . PHP_EOL;
