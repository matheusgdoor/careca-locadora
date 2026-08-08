<?php

declare(strict_types=1);

$root = rtrim($argv[1] ?? 'C:\dev\careca-locadora', "\\/");

function p(string $root, string $relative): string
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
echo "Careca Locadora - Presença Regional 17.5.3" . PHP_EOL;
echo "Filial identificada somente pelo Nome da filial" . PHP_EOL;
echo PHP_EOL;

$welcomePath = p($root, 'resources/js/pages/welcome.tsx');
$source = readRequired($welcomePath);

/*
|--------------------------------------------------------------------------
| OBJETIVO
|--------------------------------------------------------------------------
| Na home, a seção "Presença regional" NÃO deve usar city/state como
| identificação operacional da filial.
|
| Exemplo atual incorreto:
|   ARIPUANA MT
|   ALTA FLORESTA/MT
|
| Exemplo correto:
|   ARIPUANA MT
|
| O endereço fiscal/cidade do CNPJ não define o nome/local operacional
| da filial. A identidade visual da unidade vem exclusivamente branch.name.
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| 1. Remover linha JSX city/state isolada
|--------------------------------------------------------------------------
*/
$patterns = [
    '~\s*<p(?P<attrs>[^>]*)>\s*\{branch\.city\}/\{branch\.state\}\s*</p>~s',
    '~\s*<span(?P<attrs>[^>]*)>\s*\{branch\.city\}/\{branch\.state\}\s*</span>~s',
    '~\s*<div(?P<attrs>[^>]*)>\s*\{branch\.city\}/\{branch\.state\}\s*</div>~s',
];

$totalRemoved = 0;

foreach ($patterns as $pattern) {
    $source = preg_replace(
        $pattern,
        '',
        $source,
        -1,
        $count
    ) ?? $source;

    $totalRemoved += $count;
}

/*
|--------------------------------------------------------------------------
| 2. Remover expressões compostas remanescentes
|--------------------------------------------------------------------------
*/
$replacements = [
    '{branch.name} — {branch.city}/{branch.state}' => '{branch.name}',
    '{branch.name} · {branch.city}/{branch.state}' => '{branch.name}',
    '`${branch.name} — ${branch.city}/${branch.state}`' => 'branch.name',
    '`${branch.name} · ${branch.city}/${branch.state}`' => 'branch.name',
];

foreach ($replacements as $from => $to) {
    if (str_contains($source, $from)) {
        $source = str_replace($from, $to, $source, $count);
        $totalRemoved += $count;
    }
}

/*
|--------------------------------------------------------------------------
| 3. Remover bloco condicional específico de cidade/UF
|--------------------------------------------------------------------------
*/
$conditionalPatterns = [
    '~\s*\{branch\.city\s*&&\s*branch\.state\s*&&\s*\(\s*<[^>]+>\s*\{branch\.city\}/\{branch\.state\}\s*</[^>]+>\s*\)\}~s',
    '~\s*\{\[branch\.city,\s*branch\.state\]\.filter\(Boolean\)\.join\([\'"]/[\''"]\)\}~s',
];

foreach ($conditionalPatterns as $pattern) {
    $source = preg_replace(
        $pattern,
        '',
        $source,
        -1,
        $count
    ) ?? $source;

    $totalRemoved += $count;
}

writeSafe($welcomePath, $source);

$source = readRequired($welcomePath);

/*
|--------------------------------------------------------------------------
| 4. Validação
|--------------------------------------------------------------------------
*/
if (! str_contains($source, '{branch.name}')) {
    fwrite(STDERR, "[ERRO] Nome da filial não localizado na home.\n");
    exit(10);
}

$forbidden = [
    '{branch.name} — {branch.city}/{branch.state}',
    '{branch.name} · {branch.city}/{branch.state}',
    '{branch.city}/{branch.state}',
];

foreach ($forbidden as $needle) {
    if (str_contains($source, $needle)) {
        fwrite(STDERR, "[ERRO] Ainda existe cidade/UF vinculada à identidade da filial: {$needle}\n");
        exit(11);
    }
}

echo "[CORRIGIDO] {$totalRemoved} ocorrência(s) de cidade/UF removida(s) da identificação da filial." . PHP_EOL;
echo "[OK] Presença regional agora usa somente branch.name." . PHP_EOL;
echo PHP_EOL;
echo "[OK] Presença Regional 17.5.3 aplicada com sucesso." . PHP_EOL;
