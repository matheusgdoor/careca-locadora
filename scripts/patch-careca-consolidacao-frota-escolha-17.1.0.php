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
echo "Careca Locadora - Consolidação Frota e Escolha Inteligente 17.1.0" . PHP_EOL;
echo PHP_EOL;

/*
|--------------------------------------------------------------------------
| 1. Metadata de portas
|--------------------------------------------------------------------------
*/
$formPath = p($root, 'app/Filament/Resources/Assets/Schemas/AssetForm.php');
$form = readRequired($formPath);

if (str_contains($form, "TextInput::make('doors')")) {
    $form = str_replace(
        "TextInput::make('doors')",
        "TextInput::make('metadata.doors')",
        $form
    );
    writeSafe($formPath, $form);
    echo "[CORRIGIDO] Portas migradas logicamente para metadata.doors." . PHP_EOL;
}

$vehicleControllerPath = p(
    $root,
    'app/Http/Controllers/Api/PublicVehicleController.php'
);
$vehicleController = readRequired($vehicleControllerPath);

if (str_contains($vehicleController, "'doors' => \$vehicle->doors")) {
    $vehicleController = str_replace(
        "'doors' => \$vehicle->doors",
        "'doors' => data_get(\$vehicle->metadata, 'doors')",
        $vehicleController
    );
    writeSafe($vehicleControllerPath, $vehicleController);
    echo "[CORRIGIDO] API individual lê portas do metadata." . PHP_EOL;
}

/*
|--------------------------------------------------------------------------
| 2. Endpoint dedicado de veículos da categoria
|--------------------------------------------------------------------------
*/
$catalogPath = p($root, 'app/Http/Controllers/Api/PublicCatalogController.php');
$catalog = readRequired($catalogPath);

if (! str_contains($catalog, 'public function categoryVehicles(')) {
    $quotePos = strpos($catalog, '    public function quote(');

    if ($quotePos === false) {
        fwrite(STDERR, "[ERRO] Método quote() não localizado no PublicCatalogController.\n");
        exit(5);
    }

    $method = <<<'PHP'
    public function categoryVehicles(
        PublicCatalogAvailabilityRequest $request,
        ReservationAvailabilityEngine $engine,
    ): JsonResponse {
        $search = $this->search($request->validated());

        $assets = $engine->availableAssets(
            $search,
            $request->string('search')->toString() ?: null,
            100
        )->map(fn (Asset $asset): array => [
            'id' => $asset->id,
            'prefix' => $asset->prefix,
            'name' => $asset->name,
            'plate' => $asset->plate,
            'seats' => $asset->seats,
            'doors' => data_get($asset->metadata, 'doors'),
            'transmission' => $asset->transmission,
            'fuel_type' => $asset->fuel_type,
            'model_year' => $asset->model_year,
            'air_conditioning' => (bool) data_get(
                $asset->metadata,
                'air_conditioning',
                false
            ),
            'power_steering' => (bool) data_get(
                $asset->metadata,
                'power_steering',
                false
            ),
            'luggage_capacity' => data_get(
                $asset->metadata,
                'luggage_capacity'
            ),
            'category' => [
                'id' => $asset->category?->id,
                'name' => $asset->category?->name,
            ],
            'branch' => [
                'id' => $asset->branch?->id,
                'name' => $asset->branch?->trade_name
                    ?: $asset->branch?->name,
                'city' => $asset->branch?->city,
                'state' => $asset->branch?->state,
            ],
            'photos' => $asset->photos
                ->filter(fn ($photo): bool => filled($photo->file_path))
                ->sortByDesc('is_featured')
                ->sortBy('display_order')
                ->map(fn ($photo): array => [
                    'path' => $photo->file_path,
                    'disk' => $photo->disk ?? 'public',
                    'featured' => (bool) $photo->is_featured,
                ])
                ->values()
                ->all(),
        ])->values();

        return response()->json([
            'data' => $assets,
            'meta' => [
                'count' => $assets->count(),
                'mode' => 'assets',
                'starts_at' => $search->startsAt->toIso8601String(),
                'ends_at' => $search->endsAt->toIso8601String(),
            ],
        ]);
    }


PHP;

    $catalog =
        substr($catalog, 0, $quotePos) .
        $method .
        substr($catalog, $quotePos);

    writeSafe($catalogPath, $catalog);
    echo "[CORRIGIDO] Endpoint lógico categoryVehicles() adicionado." . PHP_EOL;
} else {
    if (str_contains($catalog, "'doors' => \$asset->doors")) {
        $catalog = str_replace(
            "'doors' => \$asset->doors",
            "'doors' => data_get(\$asset->metadata, 'doors')",
            $catalog
        );
        writeSafe($catalogPath, $catalog);
    }
    echo "[OK] categoryVehicles() já presente." . PHP_EOL;
}

/*
|--------------------------------------------------------------------------
| 3. API route
|--------------------------------------------------------------------------
*/
$apiRoutesPath = p($root, 'routes/api-public-catalog.php');
$apiRoutes = readRequired($apiRoutesPath);

if (! str_contains($apiRoutes, "name('category-vehicles')")) {
    $quoteMarker = "        Route::post('/quote', [";
    $quotePos = strpos($apiRoutes, $quoteMarker);

    if ($quotePos === false) {
        fwrite(STDERR, "[ERRO] Rota /quote não localizada.\n");
        exit(6);
    }

    $route = <<<'PHP'
        Route::post('/category-vehicles', [
            PublicCatalogController::class,
            'categoryVehicles',
        ])->name('category-vehicles');

PHP;

    $apiRoutes =
        substr($apiRoutes, 0, $quotePos) .
        $route .
        substr($apiRoutes, $quotePos);

    writeSafe($apiRoutesPath, $apiRoutes);
    echo "[CORRIGIDO] POST /api/public/category-vehicles registrado." . PHP_EOL;
}

/*
|--------------------------------------------------------------------------
| 4. Web route
|--------------------------------------------------------------------------
*/
$webPath = p($root, 'routes/web.php');
$web = readRequired($webPath);

if (! str_contains($web, "name('public.categories.vehicles')")) {
    $vehicleMarker = "Route::get('/veiculos/{asset}'";
    $vehiclePos = strpos($web, $vehicleMarker);

    if ($vehiclePos === false) {
        fwrite(STDERR, "[ERRO] Rota /veiculos/{asset} não localizada.\n");
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
        substr($web, 0, $vehiclePos) .
        $route .
        substr($web, $vehiclePos);

    writeSafe($webPath, $web);
    echo "[CORRIGIDO] GET /categoria/{category} registrado." . PHP_EOL;
}

/*
|--------------------------------------------------------------------------
| 5. Home para escolha por categoria
|--------------------------------------------------------------------------
*/
$welcomePath = p($root, 'resources/js/pages/welcome.tsx');
$welcome = readRequired($welcomePath);

if (str_contains($welcome, '/veiculos/${offer.representative_asset_id}')) {
    $old = '/veiculos/${offer.representative_asset_id}?starts_at=${encodeURIComponent(form.starts_at)}&ends_at=${encodeURIComponent(form.ends_at)}&branch_id=${encodeURIComponent(form.branch_id)}&category_id=${encodeURIComponent(offer.id)}';
    $new = '/categoria/${offer.id}?starts_at=${encodeURIComponent(form.starts_at)}&ends_at=${encodeURIComponent(form.ends_at)}&branch_id=${encodeURIComponent(form.branch_id)}';

    if (! str_contains($welcome, $old)) {
        fwrite(STDERR, "[ERRO] Link representative_asset_id encontrado em formato diferente do esperado.\n");
        exit(8);
    }

    $welcome = str_replace($old, $new, $welcome, $count);

    if ($count < 1) {
        fwrite(STDERR, "[ERRO] Nenhum link da home foi convertido.\n");
        exit(9);
    }

    writeSafe($welcomePath, $welcome);
    echo "[CORRIGIDO] {$count} link(s) da home abrem a categoria." . PHP_EOL;
}

/*
|--------------------------------------------------------------------------
| 6. Atualizar teste legado 17.0.5.1
|--------------------------------------------------------------------------
*/
$legacyTestPath = p(
    $root,
    'tests/Feature/Site/PublicVehicleFeatures17051Test.php'
);

if (is_file($legacyTestPath)) {
    $legacy = readRequired($legacyTestPath);

    if (str_contains($legacy, "TextInput::make('doors')")) {
        $legacy = str_replace(
            "TextInput::make('doors')",
            "TextInput::make('metadata.doors')",
            $legacy
        );
        writeSafe($legacyTestPath, $legacy);
        echo "[CORRIGIDO] Teste legado 17.0.5.1 alinhado ao metadata." . PHP_EOL;
    }
}

/*
|--------------------------------------------------------------------------
| Validação final
|--------------------------------------------------------------------------
*/
$form = readRequired($formPath);
$vehicleController = readRequired($vehicleControllerPath);
$catalog = readRequired($catalogPath);
$apiRoutes = readRequired($apiRoutesPath);
$web = readRequired($webPath);
$welcome = readRequired($welcomePath);
$page = readRequired(p($root, 'resources/js/pages/public/category-vehicles.tsx'));

foreach ([
    "TextInput::make('metadata.doors')",
    "Toggle::make('metadata.air_conditioning')",
    "TextInput::make('metadata.luggage_capacity')",
] as $needle) {
    if (! str_contains($form, $needle)) {
        fwrite(STDERR, "[ERRO] Formulário incompleto: {$needle}\n");
        exit(10);
    }
}

if (! str_contains(
    $vehicleController,
    "'doors' => data_get(\$vehicle->metadata, 'doors')"
)) {
    fwrite(STDERR, "[ERRO] API individual ainda não lê metadata.doors.\n");
    exit(11);
}

foreach ([
    'public function categoryVehicles(',
    "'doors' => data_get(\$asset->metadata, 'doors')",
    "'path' => \$photo->file_path",
    "'mode' => 'assets'",
] as $needle) {
    if (! str_contains($catalog, $needle)) {
        fwrite(STDERR, "[ERRO] Escolha inteligente incompleta: {$needle}\n");
        exit(12);
    }
}

if (! str_contains($apiRoutes, "name('category-vehicles')")) {
    fwrite(STDERR, "[ERRO] Rota API category-vehicles ausente.\n");
    exit(13);
}

if (! str_contains($web, "name('public.categories.vehicles')")) {
    fwrite(STDERR, "[ERRO] Rota web da categoria ausente.\n");
    exit(14);
}

if (str_contains($welcome, '/veiculos/${offer.representative_asset_id}')) {
    fwrite(STDERR, "[ERRO] Home ainda força representative_asset_id.\n");
    exit(15);
}

foreach ([
    "fetch('/api/public/category-vehicles'",
    'Escolher veículo',
    '/veiculos/${vehicle.id}?',
] as $needle) {
    if (! str_contains($page, $needle)) {
        fwrite(STDERR, "[ERRO] Página da categoria incompleta: {$needle}\n");
        exit(16);
    }
}

echo PHP_EOL;
echo "[OK] Consolidação 17.1.0 aplicada com sucesso." . PHP_EOL;
