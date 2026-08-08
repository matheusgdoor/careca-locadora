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
echo "Careca Locadora - Escolha Inteligente 17.0.6.2" . PHP_EOL;
echo "Patch estrutural da availability" . PHP_EOL;
echo PHP_EOL;

/*
|--------------------------------------------------------------------------
| 1. PublicCatalogController - alterar somente o método availability()
|--------------------------------------------------------------------------
*/
$controllerPath = p($root, 'app/Http/Controllers/Api/PublicCatalogController.php');
$controller = readRequired($controllerPath);

$availabilityStart = strpos($controller, 'public function availability(');
$quoteStart = strpos($controller, 'public function quote(');

if ($availabilityStart === false || $quoteStart === false || $quoteStart <= $availabilityStart) {
    fwrite(STDERR, "[ERRO] Não foi possível delimitar availability() e quote().\n");
    exit(5);
}

$before = substr($controller, 0, $availabilityStart);
$availability = substr($controller, $availabilityStart, $quoteStart - $availabilityStart);
$after = substr($controller, $quoteStart);

if (! str_contains($availability, "'doors' => \$asset->doors")) {
    $categoryPos = strpos($availability, "'category' => [");

    if ($categoryPos === false) {
        fwrite(STDERR, "[ERRO] Bloco category do payload availability não localizado.\n");
        exit(6);
    }

    $lineStart = strrpos(substr($availability, 0, $categoryPos), "\n");
    $lineStart = $lineStart === false ? $categoryPos : $lineStart + 1;

    $indent = '';
    if (preg_match('/^(\s*)/', substr($availability, $lineStart), $matches)) {
        $indent = $matches[1];
    }

    $addition =
        $indent . "'doors' => \$asset->doors,\n" .
        $indent . "'air_conditioning' => (bool) data_get(\n" .
        $indent . "    \$asset->metadata,\n" .
        $indent . "    'air_conditioning',\n" .
        $indent . "    false\n" .
        $indent . "),\n" .
        $indent . "'luggage_capacity' => data_get(\n" .
        $indent . "    \$asset->metadata,\n" .
        $indent . "    'luggage_capacity'\n" .
        $indent . "),\n";

    $availability =
        substr($availability, 0, $lineStart) .
        $addition .
        substr($availability, $lineStart);

    $controller = $before . $availability . $after;
    writeSafe($controllerPath, $controller);

    echo "[CORRIGIDO] Portas e conforto inseridos no payload availability." . PHP_EOL;
} else {
    echo "[OK] Payload availability já possui portas e conforto." . PHP_EOL;
}

/*
|--------------------------------------------------------------------------
| 2. Rota pública da categoria
|--------------------------------------------------------------------------
*/
$webPath = p($root, 'routes/web.php');
$web = readRequired($webPath);

if (! str_contains($web, "name('public.categories.vehicles')")) {
    $vehicleRoutePos = strpos($web, "Route::get('/veiculos/{asset}'");

    if ($vehicleRoutePos === false) {
        fwrite(STDERR, "[ERRO] Rota pública de veículo não localizada.\n");
        exit(7);
    }

    $route = <<<'PHP'
Route::get('/categoria/{category}', function (string $category) {
    return \Inertia\Inertia::render('public/category-vehicles', [
        'categoryId' => $category,
    ]);
})->name('public.categories.vehicles');


PHP;

    $web =
        substr($web, 0, $vehicleRoutePos) .
        $route .
        substr($web, $vehicleRoutePos);

    writeSafe($webPath, $web);
    echo "[CORRIGIDO] Rota /categoria/{category} adicionada." . PHP_EOL;
} else {
    echo "[OK] Rota da categoria já existe." . PHP_EOL;
}

/*
|--------------------------------------------------------------------------
| 3. Home - trocar representative_asset_id pela categoria
|--------------------------------------------------------------------------
*/
$welcomePath = p($root, 'resources/js/pages/welcome.tsx');
$welcome = readRequired($welcomePath);

if (str_contains($welcome, '/veiculos/${offer.representative_asset_id}')) {
    $oldFragment = '/veiculos/${offer.representative_asset_id}?starts_at=${encodeURIComponent(form.starts_at)}&ends_at=${encodeURIComponent(form.ends_at)}&branch_id=${encodeURIComponent(form.branch_id)}&category_id=${encodeURIComponent(offer.id)}';
    $newFragment = '/categoria/${offer.id}?starts_at=${encodeURIComponent(form.starts_at)}&ends_at=${encodeURIComponent(form.ends_at)}&branch_id=${encodeURIComponent(form.branch_id)}';

    if (! str_contains($welcome, $oldFragment)) {
        fwrite(STDERR, "[ERRO] Link representativo existe, mas em formato não reconhecido.\n");
        exit(8);
    }

    $welcome = str_replace($oldFragment, $newFragment, $welcome, $count);

    if ($count < 1) {
        fwrite(STDERR, "[ERRO] Nenhum link do catálogo foi convertido.\n");
        exit(9);
    }

    writeSafe($welcomePath, $welcome);
    echo "[CORRIGIDO] {$count} link(s) do catálogo agora abrem a categoria." . PHP_EOL;
} elseif (str_contains($welcome, '/categoria/${offer.id}?starts_at=')) {
    echo "[OK] Home já abre a categoria." . PHP_EOL;
} else {
    fwrite(STDERR, "[ERRO] Não foi possível reconhecer os links do catálogo.\n");
    exit(10);
}

/*
|--------------------------------------------------------------------------
| 4. Validação final
|--------------------------------------------------------------------------
*/
$controller = readRequired($controllerPath);
$web = readRequired($webPath);
$welcome = readRequired($welcomePath);
$page = readRequired(p($root, 'resources/js/pages/public/category-vehicles.tsx'));

$availabilityStart = strpos($controller, 'public function availability(');
$quoteStart = strpos($controller, 'public function quote(');
$availability = substr($controller, $availabilityStart, $quoteStart - $availabilityStart);

foreach ([
    "'doors' => \$asset->doors",
    "'air_conditioning' =>",
    "'luggage_capacity' =>",
] as $needle) {
    if (! str_contains($availability, $needle)) {
        fwrite(STDERR, "[ERRO] Validação availability falhou: {$needle}\n");
        exit(11);
    }
}

if (! str_contains($web, "name('public.categories.vehicles')")) {
    fwrite(STDERR, "[ERRO] Rota da categoria ausente.\n");
    exit(12);
}

if (str_contains($welcome, '/veiculos/${offer.representative_asset_id}')) {
    fwrite(STDERR, "[ERRO] Home ainda usa representative_asset_id.\n");
    exit(13);
}

foreach ([
    '/api/public/availability',
    'Veículos realmente disponíveis',
    'Escolher veículo',
    'Disponibilidade em tempo real',
] as $needle) {
    if (! str_contains($page, $needle)) {
        fwrite(STDERR, "[ERRO] Página category-vehicles incompleta: {$needle}\n");
        exit(14);
    }
}

echo PHP_EOL;
echo "[OK] Escolha Inteligente 17.0.6.2 aplicada com sucesso." . PHP_EOL;
