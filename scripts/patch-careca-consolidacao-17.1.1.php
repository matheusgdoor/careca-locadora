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
echo "Careca Locadora - Consolidação 17.1.1" . PHP_EOL;
echo "Correção TypeScript das imagens públicas" . PHP_EOL;
echo PHP_EOL;

$vehiclePath = p(
    $root,
    'resources/js/pages/public/vehicle-show.tsx'
);

$vehicle = readRequired($vehiclePath);

$replacements = [
    'src={storageUrl(photos[activePhoto].path)}'
        => 'src={storageUrl(photos[activePhoto].path) ?? undefined}',
    'src={storageUrl(photo.path)}'
        => 'src={storageUrl(photo.path) ?? undefined}',
];

$changed = 0;

foreach ($replacements as $old => $new) {
    if (str_contains($vehicle, $old)) {
        $vehicle = str_replace($old, $new, $vehicle, $count);
        $changed += $count;
    }
}

if ($changed > 0) {
    writeSafe($vehiclePath, $vehicle);
    echo "[CORRIGIDO] {$changed} uso(s) de src agora convertem null para undefined." . PHP_EOL;
} elseif (
    str_contains(
        $vehicle,
        'src={storageUrl(photos[activePhoto].path) ?? undefined}'
    )
    && str_contains(
        $vehicle,
        'src={storageUrl(photo.path) ?? undefined}'
    )
) {
    echo "[OK] Correção TypeScript já presente." . PHP_EOL;
} else {
    fwrite(STDERR, "[ERRO] Estrutura atual das imagens não reconhecida.\n");
    exit(5);
}

$vehicle = readRequired($vehiclePath);

foreach ([
    'src={storageUrl(photos[activePhoto].path) ?? undefined}',
    'src={storageUrl(photo.path) ?? undefined}',
] as $needle) {
    if (! str_contains($vehicle, $needle)) {
        fwrite(STDERR, "[ERRO] Validação falhou: {$needle}\n");
        exit(6);
    }
}

echo PHP_EOL;
echo "[OK] Consolidação 17.1.1 aplicada com sucesso." . PHP_EOL;
