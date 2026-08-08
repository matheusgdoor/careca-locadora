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

    $value = file_get_contents($path);

    if ($value === false) {
        fwrite(STDERR, "[ERRO] Falha ao ler: {$path}\n");
        exit(3);
    }

    return $value;
}

function writeSafe(string $path, string $value): void
{
    if (file_put_contents($path, $value) === false) {
        fwrite(STDERR, "[ERRO] Falha ao gravar: {$path}\n");
        exit(4);
    }
}

echo PHP_EOL;
echo "Careca Locadora - Escolha Inteligente de Veículos 17.0.6" . PHP_EOL;
echo PHP_EOL;

/*
|--------------------------------------------------------------------------
| 1. API availability com ficha completa
|--------------------------------------------------------------------------
*/
$controllerPath = p($root, 'app/Http/Controllers/Api/PublicCatalogController.php');
$controller = readRequired($controllerPath);

if (! str_contains($controller, "'doors' => \$asset->doors")) {
    $anchor = "            'seats' => \$asset->seats,";

    $replacement = <<<'PHP'
            'seats' => $asset->seats,
            'doors' => $asset->doors,
            'air_conditioning' => (bool) data_get(
                $asset->metadata,
                'air_conditioning',
                false
            ),
            'luggage_capacity' => data_get(
                $asset->metadata,
                'luggage_capacity'
            ),
PHP;

    if (! str_contains($controller, $anchor)) {
        fwrite(STDERR, "[ERRO] Campo seats da availability não localizado.\n");
        exit(5);
    }

    $controller = str_replace($anchor, $replacement, $controller, $count);

    if ($count !== 1) {
        fwrite(STDERR, "[ERRO] Falha ao ampliar payload de availability.\n");
        exit(6);
    }

    writeSafe($controllerPath, $controller);
    echo "[CORRIGIDO] API availability expõe portas e conforto." . PHP_EOL;
} else {
    echo "[OK] API availability já possui ficha completa." . PHP_EOL;
}

/*
|--------------------------------------------------------------------------
| 2. Rota pública de escolha
|--------------------------------------------------------------------------
*/
$webPath = p($root, 'routes/web.php');
$web = readRequired($webPath);

if (! str_contains($web, "name('public.categories.vehicles')")) {
    $anchor = "Route::get('/veiculos/{asset}', function (string \$asset) {";

    $route = <<<'PHP'
Route::get('/categoria/{category}', function (string $category) {
    return \Inertia\Inertia::render('public/category-vehicles', [
        'categoryId' => $category,
    ]);
})->name('public.categories.vehicles');

PHP;

    if (! str_contains($web, $anchor)) {
        fwrite(STDERR, "[ERRO] Rota /veiculos/{asset} não localizada.\n");
        exit(7);
    }

    $web = str_replace($anchor, $route . $anchor, $web, $count);

    if ($count !== 1) {
        fwrite(STDERR, "[ERRO] Falha ao inserir rota de categoria.\n");
        exit(8);
    }

    writeSafe($webPath, $web);
    echo "[CORRIGIDO] Rota /categoria/{category} adicionada." . PHP_EOL;
} else {
    echo "[OK] Rota de categoria já existe." . PHP_EOL;
}

/*
|--------------------------------------------------------------------------
| 3. Home: categoria leva para lista real, não veículo representativo
|--------------------------------------------------------------------------
*/
$welcomePath = p($root, 'resources/js/pages/welcome.tsx');
$welcome = readRequired($welcomePath);

$old = '`/veiculos/${offer.representative_asset_id}?starts_at=${encodeURIComponent(form.starts_at)}&ends_at=${encodeURIComponent(form.ends_at)}&branch_id=${encodeURIComponent(form.branch_id)}&category_id=${encodeURIComponent(offer.id)}`';

$new = '`/categoria/${offer.id}?starts_at=${encodeURIComponent(form.starts_at)}&ends_at=${encodeURIComponent(form.ends_at)}&branch_id=${encodeURIComponent(form.branch_id)}`';

if (str_contains($welcome, $old)) {
    $welcome = str_replace($old, $new, $welcome, $count);

    writeSafe($welcomePath, $welcome);

    echo "[CORRIGIDO] {$count} link(s) do catálogo agora abrem escolha por categoria." . PHP_EOL;
} elseif (str_contains($welcome, '`/categoria/${offer.id}?starts_at=')) {
    echo "[OK] Home já direciona para escolha por categoria." . PHP_EOL;
} else {
    fwrite(STDERR, "[ERRO] Links atuais do catálogo não foram reconhecidos. Envie welcome.tsx atual.\n");
    exit(9);
}

/*
|--------------------------------------------------------------------------
| 4. Validação
|--------------------------------------------------------------------------
*/
$controller = readRequired($controllerPath);
$web = readRequired($webPath);
$welcome = readRequired($welcomePath);
$page = readRequired(p($root, 'resources/js/pages/public/category-vehicles.tsx'));

foreach ([
    "'doors' => \$asset->doors",
    "'air_conditioning' =>",
    "'luggage_capacity' =>",
] as $needle) {
    if (! str_contains($controller, $needle)) {
        fwrite(STDERR, "[ERRO] API incompleta: {$needle}\n");
        exit(10);
    }
}

if (! str_contains($web, "name('public.categories.vehicles')")) {
    fwrite(STDERR, "[ERRO] Rota de categorias ausente.\n");
    exit(11);
}

if (str_contains($welcome, '/veiculos/${offer.representative_asset_id}')) {
    fwrite(STDERR, "[ERRO] Home ainda aponta para veículo representativo.\n");
    exit(12);
}

foreach ([
    '/api/public/availability',
    'Escolher veículo',
    'Veículos realmente disponíveis',
    'Disponibilidade em tempo real',
] as $needle) {
    if (! str_contains($page, $needle)) {
        fwrite(STDERR, "[ERRO] Página de escolha incompleta: {$needle}\n");
        exit(13);
    }
}

echo PHP_EOL;
echo "[OK] Escolha Inteligente 17.0.6 aplicada com sucesso." . PHP_EOL;
