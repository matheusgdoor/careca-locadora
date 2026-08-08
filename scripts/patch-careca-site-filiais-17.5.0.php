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
echo "Careca Locadora - Site Institucional e Filiais 17.5.0" . PHP_EOL;
echo "Vantagens + Filiais + filtro somente por nome da filial" . PHP_EOL;
echo PHP_EOL;

/*
|--------------------------------------------------------------------------
| 1. Rotas institucionais
|--------------------------------------------------------------------------
*/
$webPath = p($root, 'routes/web.php');
$web = readRequired($webPath);

if (! str_contains($web, "name('public.advantages')")) {
    $anchor = "Route::get('/categoria/{category}'";

    $pos = strpos($web, $anchor);

    if ($pos === false) {
        $anchor = "Route::get('/veiculos/{asset}'";
        $pos = strpos($web, $anchor);
    }

    if ($pos === false) {
        fwrite(STDERR, "[ERRO] Ponto de inserção das rotas públicas não localizado.\n");
        exit(5);
    }

    $routes = <<<'PHP'
Route::get('/vantagens', function () {
    return \Inertia\Inertia::render('public/advantages');
})->name('public.advantages');

Route::get('/filiais', function () {
    $organizationId = config('careca-public.organization_id');

    $branches = \App\Models\Branch::query()
        ->withoutGlobalScopes()
        ->where('organization_id', $organizationId)
        ->orderBy('name')
        ->get()
        ->map(fn (\App\Models\Branch $branch): array => [
            'id' => $branch->id,
            'name' => $branch->name,
            'address' => $branch->address,
            'number' => $branch->number,
            'neighborhood' => $branch->neighborhood,
            'city' => $branch->city,
            'state' => $branch->state,
            'phone' => $branch->phone,
            'whatsapp' => $branch->whatsapp,
        ])
        ->values()
        ->all();

    return \Inertia\Inertia::render('public/branches', [
        'branches' => $branches,
    ]);
})->name('public.branches');


PHP;

    $web = substr($web, 0, $pos) . $routes . substr($web, $pos);
    writeSafe($webPath, $web);

    echo "[CORRIGIDO] Rotas /vantagens e /filiais adicionadas." . PHP_EOL;
}

/*
|--------------------------------------------------------------------------
| 2. Filtro da home: usar somente o campo Nome da filial
|--------------------------------------------------------------------------
*/
$welcomePath = p($root, 'resources/js/pages/welcome.tsx');
$welcome = readRequired($welcomePath);

$welcome = str_replace('Loja de retirada', 'Filial', $welcome);
$welcome = str_replace('Todas as lojas', 'Todas as filiais', $welcome);

/*
| Formatos conhecidos de label composto.
*/
$patterns = [
    '/\{branch\.name\}\s*—\s*\{branch\.city\}\/\{branch\.state\}/',
    '/\{branch\.name\}\s*·\s*\{branch\.city\}\/\{branch\.state\}/',
    '/\$\{branch\.name\}\s*—\s*\$\{branch\.city\}\/\$\{branch\.state\}/',
    '/\$\{branch\.name\}\s*·\s*\$\{branch\.city\}\/\$\{branch\.state\}/',
];

foreach ($patterns as $pattern) {
    $welcome = preg_replace($pattern, '{branch.name}', $welcome) ?? $welcome;
}

/*
| Template string comum.
*/
$welcome = str_replace(
    '`${branch.name} — ${branch.city}/${branch.state}`',
    'branch.name',
    $welcome
);

$welcome = str_replace(
    '`${branch.name} · ${branch.city}/${branch.state}`',
    'branch.name',
    $welcome
);

/*
| JSX comum.
*/
$welcome = str_replace(
    "{branch.name} — {branch.city}/{branch.state}",
    "{branch.name}",
    $welcome
);

$welcome = str_replace(
    "{branch.name} · {branch.city}/{branch.state}",
    "{branch.name}",
    $welcome
);

writeSafe($welcomePath, $welcome);

echo "[CORRIGIDO] Filtro renomeado para Filial e opção geral para Todas as filiais." . PHP_EOL;

/*
|--------------------------------------------------------------------------
| 3. Navegação institucional na home
|--------------------------------------------------------------------------
*/
$welcome = readRequired($welcomePath);

if (! str_contains($welcome, 'href="/vantagens"')) {
    /*
    | Insere links perto do primeiro fechamento de nav caso exista.
    | Se a home não tiver nav convencional, as rotas continuam funcionais.
    */
    $navPos = strpos($welcome, '</nav>');

    if ($navPos !== false) {
        $links = <<<'TSX'
                        <a href="/vantagens" className="hover:text-red-500">
                            Vantagens
                        </a>
                        <a href="/filiais" className="hover:text-red-500">
                            Filiais
                        </a>
TSX;

        $welcome = substr($welcome, 0, $navPos) . $links . substr($welcome, $navPos);
        writeSafe($welcomePath, $welcome);
        echo "[CORRIGIDO] Links Vantagens e Filiais adicionados à navegação." . PHP_EOL;
    } else {
        echo "[INFO] Navegação convencional não localizada; rotas públicas estão disponíveis." . PHP_EOL;
    }
}

/*
|--------------------------------------------------------------------------
| Validação
|--------------------------------------------------------------------------
*/
$web = readRequired($webPath);
$welcome = readRequired($welcomePath);

foreach ([
    "name('public.advantages')",
    "name('public.branches')",
    "Inertia::render('public/advantages')",
    "Inertia::render('public/branches'",
] as $needle) {
    if (! str_contains($web, $needle)) {
        fwrite(STDERR, "[ERRO] Rota institucional ausente: {$needle}\n");
        exit(10);
    }
}

foreach ([
    'Filial',
    'Todas as filiais',
] as $needle) {
    if (! str_contains($welcome, $needle)) {
        fwrite(STDERR, "[ERRO] Filtro de filial não atualizado: {$needle}\n");
        exit(11);
    }
}

echo PHP_EOL;
echo "[OK] Site Institucional e Filiais 17.5.0 aplicado com sucesso." . PHP_EOL;
