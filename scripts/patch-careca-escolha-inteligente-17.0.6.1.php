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
echo "Careca Locadora - Escolha Inteligente 17.0.6.1" . PHP_EOL;
echo "Correção do localizador do payload availability" . PHP_EOL;
echo PHP_EOL;

$controllerPath = p($root, 'app/Http/Controllers/Api/PublicCatalogController.php');
$controller = readRequired($controllerPath);

if (! str_contains($controller, "'doors' => \$asset->doors")) {
    $pattern = "/('seats'\\s*=>\\s*\\\$asset->seats\\s*,)/";

    $addition = <<<'PHP'
$1
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

    $updated = preg_replace($pattern, $addition, $controller, 1, $count);

    if ($updated === null || ($count ?? 0) !== 1) {
        fwrite(STDERR, "[ERRO] Campo seats da availability não foi localizado nem por expressão flexível.\n");
        exit(5);
    }

    $controller = $updated;
    writeSafe($controllerPath, $controller);

    echo "[CORRIGIDO] Payload availability ampliado com localizador flexível." . PHP_EOL;
} else {
    echo "[OK] Payload availability já ampliado." . PHP_EOL;
}

$webPath = p($root, 'routes/web.php');
$web = readRequired($webPath);

if (! str_contains($web, "name('public.categories.vehicles')")) {
    $route = <<<'PHP'
Route::get('/categoria/{category}', function (string $category) {
    return \Inertia\Inertia::render('public/category-vehicles', [
        'categoryId' => $category,
    ]);
})->name('public.categories.vehicles');


PHP;

    $pattern = "/(?=Route::get\\('\\/veiculos\\/\\{asset\\}')/";

    $updated = preg_replace($pattern, $route, $web, 1, $count);

    if ($updated === null || ($count ?? 0) !== 1) {
        fwrite(STDERR, "[ERRO] Rota de veículo não localizada para inserir a rota da categoria.\n");
        exit(6);
    }

    $web = $updated;
    writeSafe($webPath, $web);

    echo "[CORRIGIDO] Rota pública da categoria adicionada." . PHP_EOL;
} else {
    echo "[OK] Rota pública da categoria já presente." . PHP_EOL;
}

$welcomePath = p($root, 'resources/js/pages/welcome.tsx');
$welcome = readRequired($welcomePath);

if (str_contains($welcome, '/veiculos/${offer.representative_asset_id}')) {
    $pattern = '~`/veiculos/\$\{offer\.representative_asset_id\}\?starts_at=\$\{encodeURIComponent\(form\.starts_at\)\}&ends_at=\$\{encodeURIComponent\(form\.ends_at\)\}&branch_id=\$\{encodeURIComponent\(form\.branch_id\)\}&category_id=\$\{encodeURIComponent\(offer\.id\)\}`~';

    $replacement = '`/categoria/${offer.id}?starts_at=${encodeURIComponent(form.starts_at)}&ends_at=${encodeURIComponent(form.ends_at)}&branch_id=${encodeURIComponent(form.branch_id)}`';

    $updated = preg_replace($pattern, $replacement, $welcome, -1, $count);

    if ($updated === null || ($count ?? 0) < 1) {
        fwrite(STDERR, "[ERRO] Links do veículo representativo foram encontrados, mas não puderam ser convertidos.\n");
        exit(7);
    }

    $welcome = $updated;
    writeSafe($welcomePath, $welcome);

    echo "[CORRIGIDO] {$count} link(s) do catálogo direcionam para escolha da categoria." . PHP_EOL;
} elseif (str_contains($welcome, '/categoria/${offer.id}?starts_at=')) {
    echo "[OK] Home já direciona para escolha por categoria." . PHP_EOL;
} else {
    fwrite(STDERR, "[ERRO] Estrutura do link do catálogo não reconhecida.\n");
    exit(8);
}

$pagePath = p($root, 'resources/js/pages/public/category-vehicles.tsx');
$page = readRequired($pagePath);

foreach ([
    "'doors' => \$asset->doors",
    "'air_conditioning' =>",
    "'luggage_capacity' =>",
] as $needle) {
    if (! str_contains($controller, $needle)) {
        fwrite(STDERR, "[ERRO] Validação API falhou: {$needle}\n");
        exit(10);
    }
}

if (! str_contains($web, "name('public.categories.vehicles')")) {
    fwrite(STDERR, "[ERRO] Validação da rota falhou.\n");
    exit(11);
}

if (str_contains($welcome, '/veiculos/${offer.representative_asset_id}')) {
    fwrite(STDERR, "[ERRO] Home ainda possui link para representative_asset_id.\n");
    exit(12);
}

foreach ([
    '/api/public/availability',
    'Veículos realmente disponíveis',
    'Escolher veículo',
    'Disponibilidade em tempo real',
] as $needle) {
    if (! str_contains($page, $needle)) {
        fwrite(STDERR, "[ERRO] Página pública incompleta: {$needle}\n");
        exit(13);
    }
}

echo PHP_EOL;
echo "[OK] Escolha Inteligente 17.0.6.1 aplicada com sucesso." . PHP_EOL;
