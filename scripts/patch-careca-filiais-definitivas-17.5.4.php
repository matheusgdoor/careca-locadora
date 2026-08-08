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
echo "Careca Locadora - Filiais Definitivas 17.5.4" . PHP_EOL;
echo "Correção baseada nas linhas reais do projeto" . PHP_EOL;
echo PHP_EOL;

/*
|--------------------------------------------------------------------------
| 1. HOME - Presença regional
|--------------------------------------------------------------------------
| Linhas reais informadas:
| 650 Presença regional
| 653 Perto de você.
| 676 {branch.city}
| 677 {branch.state
| 678 ? `/${branch.state}`
|--------------------------------------------------------------------------
*/
$welcomePath = projectPath(
    $root,
    'resources/js/pages/welcome.tsx'
);

$welcome = readRequired($welcomePath);

/*
| Remove exatamente o JSX que imprime:
| {branch.city}
| {branch.state ? `/${branch.state}` : ''}
|
| Aceita quebra de linha e formatação do Prettier.
*/
$cityStatePattern =
    '~\s*\{branch\.city\}\s*'
    . '\{branch\.state\s*\?\s*`/\$\{branch\.state\}`\s*:\s*[\'"][\'"]\s*\}'
    . '~s';

$updated = preg_replace(
    $cityStatePattern,
    '',
    $welcome,
    -1,
    $cityStateCount
);

if ($updated === null) {
    fwrite(STDERR, "[ERRO] Falha no regex de city/state da home.\n");
    exit(5);
}

$welcome = $updated;

/*
| Fallback caso o ternário esteja com null/undefined ou string diferente.
*/
if (
    str_contains($welcome, '{branch.city}') &&
    str_contains($welcome, 'branch.state')
) {
    $fallbackPattern =
        '~\s*\{branch\.city\}\s*'
        . '\{branch\.state.*?\}'
        . '~s';

    $welcome = preg_replace(
        $fallbackPattern,
        '',
        $welcome,
        1,
        $fallbackCount
    ) ?? $welcome;

    $cityStateCount += $fallbackCount ?? 0;
}

writeSafe($welcomePath, $welcome);

echo "[CORRIGIDO] Home: cidade/UF removida da seção Presença regional." . PHP_EOL;

/*
|--------------------------------------------------------------------------
| 2. /FILIAIS
|--------------------------------------------------------------------------
| O arquivo será substituído pelo arquivo limpo do pacote antes deste script.
| Aqui só validamos que city/state/address não são mais renderizados.
|--------------------------------------------------------------------------
*/
$branchesPath = projectPath(
    $root,
    'resources/js/pages/public/branches.tsx'
);

$branches = readRequired($branchesPath);

$forbiddenBranches = [
    'const cityState',
    'branch.city',
    'branch.state',
    'branch.address',
    'branch.number',
    'branch.neighborhood',
    '<MapPin',
];

foreach ($forbiddenBranches as $needle) {
    if (str_contains($branches, $needle)) {
        fwrite(
            STDERR,
            "[ERRO] /filiais ainda renderiza dado fiscal/localização: {$needle}\n"
        );
        exit(10);
    }
}

foreach ([
    '{branch.name}',
    'branch.whatsapp ?? branch.phone',
    'Reservar nesta filial',
    'Cada unidade é identificada pelo Nome da filial',
] as $needle) {
    if (! str_contains($branches, $needle)) {
        fwrite(STDERR, "[ERRO] /filiais incompleta: {$needle}\n");
        exit(11);
    }
}

/*
|--------------------------------------------------------------------------
| 3. Validação da home
|--------------------------------------------------------------------------
*/
$welcome = readRequired($welcomePath);

if (! str_contains($welcome, 'Presença regional')) {
    fwrite(STDERR, "[ERRO] Seção Presença regional não localizada.\n");
    exit(12);
}

if (! str_contains($welcome, '{branch.name}')) {
    fwrite(STDERR, "[ERRO] Nome da filial não localizado na home.\n");
    exit(13);
}

if (str_contains($welcome, '{branch.city}')) {
    fwrite(STDERR, "[ERRO] branch.city ainda aparece na home.\n");
    exit(14);
}

echo "[OK] Home exibe somente branch.name na Presença regional." . PHP_EOL;
echo "[OK] Página /filiais exibe somente Nome da filial + contato." . PHP_EOL;
echo PHP_EOL;
echo "[OK] Filiais Definitivas 17.5.4 aplicada com sucesso." . PHP_EOL;
