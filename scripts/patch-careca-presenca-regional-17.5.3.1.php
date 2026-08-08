<?php

declare(strict_types=1);

$root = rtrim($argv[1] ?? 'C:\dev\careca-locadora', "\\/");

function projectPath(string $root, string $relative): string
{
    return $root . DIRECTORY_SEPARATOR
        . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
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
echo "Careca Locadora - Presença Regional 17.5.3.1" . PHP_EOL;
echo "Correção de sintaxe + remoção definitiva de cidade/UF abaixo da filial" . PHP_EOL;
echo PHP_EOL;

$welcomePath = projectPath(
    $root,
    'resources/js/pages/welcome.tsx'
);

$source = readRequired($welcomePath);

/*
|--------------------------------------------------------------------------
| 1. Identidade composta
|--------------------------------------------------------------------------
*/
$source = str_replace(
    '{branch.name} — {branch.city}/{branch.state}',
    '{branch.name}',
    $source
);

$source = str_replace(
    '{branch.name} · {branch.city}/{branch.state}',
    '{branch.name}',
    $source
);

$source = str_replace(
    '`${branch.name} — ${branch.city}/${branch.state}`',
    'branch.name',
    $source
);

$source = str_replace(
    '`${branch.name} · ${branch.city}/${branch.state}`',
    'branch.name',
    $source
);

/*
|--------------------------------------------------------------------------
| 2. Linha isolada de cidade/UF na seção Presença regional
|--------------------------------------------------------------------------
| Remove somente elementos cujo conteúdo seja exatamente:
| {branch.city}/{branch.state}
|--------------------------------------------------------------------------
*/
$elementPatterns = [
    '~\s*<p[^>]*>\s*\{branch\.city\}/\{branch\.state\}\s*</p>~s',
    '~\s*<span[^>]*>\s*\{branch\.city\}/\{branch\.state\}\s*</span>~s',
    '~\s*<div[^>]*>\s*\{branch\.city\}/\{branch\.state\}\s*</div>~s',
];

$totalRemoved = 0;

foreach ($elementPatterns as $pattern) {
    $updated = preg_replace(
        $pattern,
        '',
        $source,
        -1,
        $count
    );

    if ($updated !== null) {
        $source = $updated;
        $totalRemoved += $count;
    }
}

/*
|--------------------------------------------------------------------------
| 3. Condicional JSX comum
|--------------------------------------------------------------------------
*/
$conditionalPattern =
    '~\s*\{branch\.city\s*&&\s*branch\.state\s*&&\s*\(\s*'
    . '<(?P<tag>p|span|div)[^>]*>\s*'
    . '\{branch\.city\}/\{branch\.state\}\s*'
    . '</(?P=tag)>\s*'
    . '\)\}~s';

$updated = preg_replace(
    $conditionalPattern,
    '',
    $source,
    -1,
    $conditionalCount
);

if ($updated !== null) {
    $source = $updated;
    $totalRemoved += $conditionalCount;
}

/*
|--------------------------------------------------------------------------
| 4. Expressão array/join comum
|--------------------------------------------------------------------------
*/
$arrayJoinPattern =
    '~\s*\{\[branch\.city,\s*branch\.state\]'
    . '\.filter\(Boolean\)'
    . '\.join\([\'"]\/[\'"]\)\}~s';

$updated = preg_replace(
    $arrayJoinPattern,
    '',
    $source,
    -1,
    $joinCount
);

if ($updated !== null) {
    $source = $updated;
    $totalRemoved += $joinCount;
}

writeSafe($welcomePath, $source);

$source = readRequired($welcomePath);

/*
|--------------------------------------------------------------------------
| 5. Validação
|--------------------------------------------------------------------------
*/
if (! str_contains($source, '{branch.name}')) {
    fwrite(STDERR, "[ERRO] branch.name não localizado na home.\n");
    exit(10);
}

$forbidden = [
    '{branch.name} — {branch.city}/{branch.state}',
    '{branch.name} · {branch.city}/{branch.state}',
    '{branch.city}/{branch.state}',
];

foreach ($forbidden as $needle) {
    if (str_contains($source, $needle)) {
        fwrite(
            STDERR,
            "[ERRO] Ainda existe cidade/UF vinculada à filial: {$needle}\n"
        );
        exit(11);
    }
}

echo "[CORRIGIDO] Cidade/UF removida da identificação da filial." . PHP_EOL;
echo "[INFO] Ocorrências removidas por bloco JSX: {$totalRemoved}" . PHP_EOL;
echo "[OK] Presença regional agora usa somente o Nome da filial." . PHP_EOL;
echo PHP_EOL;
echo "[OK] Presença Regional 17.5.3.1 aplicada com sucesso." . PHP_EOL;
