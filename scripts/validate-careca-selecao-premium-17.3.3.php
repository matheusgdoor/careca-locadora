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

echo PHP_EOL;
echo "Careca Locadora - Seleção Inteligente Premium 17.3.3" . PHP_EOL;
echo "Validação JSX robusta dos selos premium" . PHP_EOL;
echo PHP_EOL;

$pagePath = p($root, 'resources/js/pages/public/category-vehicles.tsx');
$page = readRequired($pagePath);

/*
|--------------------------------------------------------------------------
| Diagnóstico
|--------------------------------------------------------------------------
| O 17.3.2 inseriu os selos corretamente, mas validou procurando:
|   >Recomendado<
| Em JSX formatado, o texto fica com quebras de linha e espaços:
|   >
|       Recomendado
|   </span>
| Portanto o erro foi somente de validação.
|--------------------------------------------------------------------------
*/

$requiredText = [
    "useState<'newest' | 'equipped' | 'name'>('newest')",
    '<option value="equipped">Mais equipados</option>',
    "if (sortBy === 'equipped')",
    'const equipmentScore =',
    'recommendedVehicleId',
    'Filial de retirada',
    'equipmentScore(vehicle) ===',
    'Escolher recomendado',
];

foreach ($requiredText as $needle) {
    if (! str_contains($page, $needle)) {
        fwrite(STDERR, "[ERRO] Base 17.3.2 incompleta: {$needle}\n");
        exit(4);
    }
}

$regexChecks = [
    'Recomendado' => '/>\s*Recomendado\s*</s',
    'Mais novo' => '/>\s*Mais novo\s*</s',
    'Mais equipado' => '/>\s*Mais equipado\s*</s',
];

foreach ($regexChecks as $label => $pattern) {
    if (preg_match($pattern, $page) !== 1) {
        fwrite(STDERR, "[ERRO] Selo não localizado: {$label}\n");
        exit(5);
    }

    echo "[OK] Selo {$label} localizado." . PHP_EOL;
}

if (
    preg_match(
        '/vehicle\.id\s*===\s*recommendedVehicleId\s*&&\s*\(/s',
        $page
    ) !== 1
) {
    fwrite(STDERR, "[ERRO] Condição JSX do selo Recomendado não localizada.\n");
    exit(6);
}

if (
    preg_match(
        '/equipmentScore\(vehicle\)\s*===\s*maxEquipmentScore\s*&&/s',
        $page
    ) !== 1
) {
    fwrite(STDERR, "[ERRO] Condição JSX do selo Mais equipado não localizada.\n");
    exit(7);
}

echo PHP_EOL;
echo "[OK] Seleção Inteligente Premium 17.3.3 validada com sucesso." . PHP_EOL;
