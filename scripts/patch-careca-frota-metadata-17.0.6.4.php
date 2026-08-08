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
echo "Careca Locadora - Frota Metadata 17.0.6.4" . PHP_EOL;
echo "Correção do campo portas sem migration" . PHP_EOL;
echo PHP_EOL;

/*
|--------------------------------------------------------------------------
| 1. AssetForm: doors passa a viver em metadata
|--------------------------------------------------------------------------
*/
$formPath = p($root, 'app/Filament/Resources/Assets/Schemas/AssetForm.php');
$form = readRequired($formPath);

if (str_contains($form, "TextInput::make('doors')")) {
    $form = str_replace(
        "TextInput::make('doors')",
        "TextInput::make('metadata.doors')",
        $form,
        $count
    );

    writeSafe($formPath, $form);

    echo "[CORRIGIDO] Cadastro grava quantidade de portas em metadata.doors." . PHP_EOL;
} elseif (str_contains($form, "TextInput::make('metadata.doors')")) {
    echo "[OK] Cadastro já usa metadata.doors." . PHP_EOL;
} else {
    fwrite(STDERR, "[ERRO] Campo de portas não localizado no AssetForm.\n");
    exit(5);
}

/*
|--------------------------------------------------------------------------
| 2. PublicVehicleController: leitura de portas do metadata
|--------------------------------------------------------------------------
*/
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
} elseif (
    str_contains(
        $vehicleController,
        "'doors' => data_get(\$vehicle->metadata, 'doors')"
    )
) {
    echo "[OK] API individual já lê portas do metadata." . PHP_EOL;
}

/*
|--------------------------------------------------------------------------
| 3. PublicCatalogController: endpoint categoryVehicles
|--------------------------------------------------------------------------
*/
$catalogPath = p(
    $root,
    'app/Http/Controllers/Api/PublicCatalogController.php'
);

$catalog = readRequired($catalogPath);

if (str_contains($catalog, "'doors' => \$asset->doors")) {
    $catalog = str_replace(
        "'doors' => \$asset->doors",
        "'doors' => data_get(\$asset->metadata, 'doors')",
        $catalog
    );

    writeSafe($catalogPath, $catalog);

    echo "[CORRIGIDO] Escolha inteligente lê portas do metadata." . PHP_EOL;
} elseif (
    str_contains(
        $catalog,
        "'doors' => data_get(\$asset->metadata, 'doors')"
    )
) {
    echo "[OK] Escolha inteligente já lê portas do metadata." . PHP_EOL;
}

/*
|--------------------------------------------------------------------------
| Validação
|--------------------------------------------------------------------------
*/
$form = readRequired($formPath);
$vehicleController = readRequired($vehicleControllerPath);
$catalog = readRequired($catalogPath);

if (str_contains($form, "TextInput::make('doors')")) {
    fwrite(STDERR, "[ERRO] AssetForm ainda tenta gravar coluna doors.\n");
    exit(10);
}

if (! str_contains($form, "TextInput::make('metadata.doors')")) {
    fwrite(STDERR, "[ERRO] metadata.doors ausente no cadastro.\n");
    exit(11);
}

if (str_contains($vehicleController, "'doors' => \$vehicle->doors")) {
    fwrite(STDERR, "[ERRO] PublicVehicleController ainda usa coluna doors.\n");
    exit(12);
}

if (str_contains($catalog, "'doors' => \$asset->doors")) {
    fwrite(STDERR, "[ERRO] PublicCatalogController ainda usa coluna doors.\n");
    exit(13);
}

echo PHP_EOL;
echo "[OK] Frota Metadata 17.0.6.4 aplicada com sucesso." . PHP_EOL;
