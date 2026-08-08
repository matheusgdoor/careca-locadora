<?php

declare(strict_types=1);

$root = rtrim($argv[1] ?? 'C:\dev\careca-locadora', "\\/");

function pathIn(string $root, string $relative): string
{
    return $root . DIRECTORY_SEPARATOR .
        str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
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
echo "Careca Locadora - Home Filiais Definitiva 17.5.2" . PHP_EOL;
echo "Menu sem duplicidade + filtro somente Nome da filial + presença regional" . PHP_EOL;
echo PHP_EOL;

$welcomePath = pathIn($root, 'resources/js/pages/welcome.tsx');
$source = readRequired($welcomePath);

/*
|--------------------------------------------------------------------------
| 1. MENU: reescrever o primeiro NAV principal com os 4 itens canônicos
|--------------------------------------------------------------------------
*/
$navPattern = '~<nav(?P<attrs>[^>]*)>(?P<body>.*?)</nav>~s';
preg_match_all($navPattern, $source, $navMatches, PREG_SET_ORDER);

$mainNavIndex = null;

foreach ($navMatches as $index => $match) {
    $body = $match['body'] ?? '';

    if (
        str_contains($body, 'Reservar') &&
        str_contains($body, 'Categorias')
    ) {
        $mainNavIndex = $index;
        break;
    }
}

if ($mainNavIndex !== null) {
    $match = $navMatches[$mainNavIndex];
    $originalNav = $match[0];
    $attrs = $match['attrs'] ?? '';

    $newNav = '<nav' . $attrs . '>' . PHP_EOL . <<<'TSX'
                        <a href="#reservar" className="hover:text-red-500">
                            Reservar
                        </a>
                        <a href="#categorias" className="hover:text-red-500">
                            Categorias
                        </a>
                        <a href="/vantagens" className="hover:text-red-500">
                            Vantagens
                        </a>
                        <a href="/filiais" className="hover:text-red-500">
                            Filiais
                        </a>
TSX
        . PHP_EOL . '                    </nav>';

    $source = str_replace($originalNav, $newNav, $source, $count);

    if ($count !== 1) {
        fwrite(STDERR, "[ERRO] Menu principal não pôde ser substituído de forma única.\n");
        exit(5);
    }

    echo "[CORRIGIDO] Menu principal: Reservar / Categorias / Vantagens / Filiais." . PHP_EOL;
} else {
    fwrite(STDERR, "[ERRO] Menu principal com Reservar/Categorias não localizado.\n");
    exit(6);
}

/*
|--------------------------------------------------------------------------
| 2. FILTRO: option de filial exibe SOMENTE branch.name
|--------------------------------------------------------------------------
*/
$optionPatterns = [
    '~<option(?P<attrs>[^>]*value=\{branch\.id\}[^>]*)>.*?</option>~s',
    '~<option(?P<attrs>[^>]*value=\{String\(branch\.id\)\}[^>]*)>.*?</option>~s',
];

$optionChanged = false;

foreach ($optionPatterns as $pattern) {
    $updated = preg_replace_callback(
        $pattern,
        static fn (array $m): string =>
            '<option' . $m['attrs'] . '>{branch.name}</option>',
        $source,
        -1,
        $count
    );

    if ($updated !== null && $count > 0) {
        $source = $updated;
        $optionChanged = true;
        break;
    }
}

if (! $optionChanged) {
    foreach ([
        '{branch.name} — {branch.city}/{branch.state}' => '{branch.name}',
        '{branch.name} · {branch.city}/{branch.state}' => '{branch.name}',
        '{branch.label}' => '{branch.name}',
        '{branch.display_name}' => '{branch.name}',
    ] as $from => $to) {
        if (str_contains($source, $from)) {
            $source = str_replace($from, $to, $source);
            $optionChanged = true;
        }
    }
}

$source = str_replace('Loja de retirada', 'Filial', $source);
$source = str_replace('Todas as lojas', 'Todas as filiais', $source);

echo "[CORRIGIDO] Filtro de reserva usa somente o Nome da filial." . PHP_EOL;

/*
|--------------------------------------------------------------------------
| 3. PRESENÇA REGIONAL: título é Nome da filial; cidade/UF é complementar
|--------------------------------------------------------------------------
| Corrige os formatos antigos mais comuns sem afetar o select já tratado.
*/
$regionalOldPatterns = [
    '{branch.name} — {branch.city}/{branch.state}',
    '{branch.name} · {branch.city}/{branch.state}',
];

foreach ($regionalOldPatterns as $old) {
    $source = str_replace($old, '{branch.name}', $source);
}

/*
| Caso a home tenha heading + linha cidade/UF, mantemos a cidade apenas como
| informação complementar. O importante é nunca usá-la como identidade.
| Se já houver endereço/CEP no objeto branch, adicionamos um bloco premium
| sem exigir campos novos no banco.
*/
if (! str_contains($source, 'branch.address ?? branch.street')) {
    $cityLinePattern = '~<p(?P<attrs>[^>]*)>\s*\{branch\.city\}/\{branch\.state\}\s*</p>~s';

    $replacement = <<<'TSX'
<p${attrs}>
    {[
        branch.address ?? branch.street,
        branch.number,
        branch.neighborhood,
    ].filter(Boolean).join(', ')}
    {[
        branch.address ?? branch.street,
        branch.number,
        branch.neighborhood,
    ].filter(Boolean).length > 0 && <br />}
    {[branch.city, branch.state].filter(Boolean).join('/')}
    {(branch.zip_code ?? branch.postal_code ?? branch.cep) && (
        <>
            <br />
            CEP {branch.zip_code ?? branch.postal_code ?? branch.cep}
        </>
    )}
</p>
TSX;

    $source = preg_replace_callback(
        $cityLinePattern,
        static function (array $m) use ($replacement): string {
            return str_replace('${attrs}', $m['attrs'] ?? '', $replacement);
        },
        $source,
        -1,
        $regionalCount
    ) ?? $source;

    if (($regionalCount ?? 0) > 0) {
        echo "[CORRIGIDO] Presença regional passou a tratar cidade/UF como informação complementar." . PHP_EOL;
    } else {
        echo "[INFO] Linha de cidade/UF regional não estava em <p>; identidade da filial já foi normalizada." . PHP_EOL;
    }
}

/*
|--------------------------------------------------------------------------
| 4. Tipagem opcional dos campos complementares, se houver type Branch
|--------------------------------------------------------------------------
*/
if (
    str_contains($source, 'type Branch = {') &&
    ! str_contains($source, 'address?: string | null;')
) {
    $typePattern = '~type Branch = \{(?P<body>.*?)\};~s';

    $source = preg_replace_callback(
        $typePattern,
        static function (array $m): string {
            $body = $m['body'] ?? '';

            if (str_contains($body, 'address?: string | null;')) {
                return $m[0];
            }

            $extra = <<<'TSX'

    address?: string | null;
    street?: string | null;
    number?: string | null;
    neighborhood?: string | null;
    zip_code?: string | null;
    postal_code?: string | null;
    cep?: string | null;
TSX;

            return 'type Branch = {' . $body . $extra . PHP_EOL . '};';
        },
        $source,
        1
    ) ?? $source;
}

/*
|--------------------------------------------------------------------------
| 5. Validação forte
|--------------------------------------------------------------------------
*/
writeSafe($welcomePath, $source);
$source = readRequired($welcomePath);

foreach ([
    'Reservar',
    'Categorias',
    'href="/vantagens"',
    'href="/filiais"',
    'Filial',
    'Todas as filiais',
    '{branch.name}',
] as $needle) {
    if (! str_contains($source, $needle)) {
        fwrite(STDERR, "[ERRO] Validação final falhou: {$needle}\n");
        exit(10);
    }
}

if (substr_count($source, 'href="/vantagens"') !== 1) {
    fwrite(STDERR, "[ERRO] Vantagens ainda está duplicado no menu principal.\n");
    exit(11);
}

if (preg_match('~>\s*Lojas\s*<~s', $source) === 1) {
    fwrite(STDERR, "[ERRO] Item Lojas legado ainda presente.\n");
    exit(12);
}

if (
    str_contains($source, '{branch.label}') ||
    str_contains($source, '{branch.display_name}') ||
    str_contains($source, '{branch.name} — {branch.city}/{branch.state}') ||
    str_contains($source, '{branch.name} · {branch.city}/{branch.state}')
) {
    fwrite(STDERR, "[ERRO] Ainda existe identificação composta de filial na home.\n");
    exit(13);
}

echo "[OK] Menu sem duplicidade." . PHP_EOL;
echo "[OK] Filtro somente pelo Nome da filial." . PHP_EOL;
echo "[OK] Presença regional com Nome da filial como identidade." . PHP_EOL;
echo PHP_EOL;
echo "[OK] Home Filiais Definitiva 17.5.2 aplicada com sucesso." . PHP_EOL;
