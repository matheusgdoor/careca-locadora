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
echo "Careca Locadora - Portal do Cliente Base 18.0.1" . PHP_EOL;
echo PHP_EOL;

$webPath = p($root, 'routes/web.php');
$web = readRequired($webPath);

if (! str_contains($web, "require __DIR__.'/customer.php';")) {
    $web .= PHP_EOL . "require __DIR__.'/customer.php';" . PHP_EOL;
    writeSafe($webPath, $web);
    echo "[OK] Rotas do portal registradas." . PHP_EOL;
}

$bootstrapPath = p($root, 'bootstrap/app.php');
$bootstrap = readRequired($bootstrapPath);

if (! str_contains($bootstrap, "'customer.portal'")) {
    $needle = '->withMiddleware(function (Middleware $middleware): void {';

    if (! str_contains($bootstrap, $needle)) {
        fwrite(STDERR, "[ERRO] withMiddleware não localizado em bootstrap/app.php.\n");
        exit(5);
    }

    $bootstrap = str_replace(
        $needle,
        $needle . PHP_EOL
        . "        \$middleware->alias([" . PHP_EOL
        . "            'customer.portal' => \\App\\Http\\Middleware\\EnsureCustomerPortalUser::class," . PHP_EOL
        . "        ]);" . PHP_EOL,
        $bootstrap
    );

    writeSafe($bootstrapPath, $bootstrap);
    echo "[OK] Middleware customer.portal registrado." . PHP_EOL;
}

$appPath = p($root, 'resources/js/app.tsx');
$app = readRequired($appPath);

if (! str_contains($app, "case name.startsWith('customer/'):")) {
    $pattern = "~case name\\.startsWith\\('public/'\\):\\s*return null;~s";

    $app = preg_replace(
        $pattern,
        "case name.startsWith('public/'):\ncase name.startsWith('customer/'):\nreturn null;",
        $app,
        1,
        $count
    ) ?? $app;

    if (($count ?? 0) !== 1) {
        fwrite(STDERR, "[ERRO] Layout public não localizado em app.tsx.\n");
        exit(6);
    }

    writeSafe($appPath, $app);
    echo "[OK] Páginas customer usam layout próprio." . PHP_EOL;
}

$userPath = p($root, 'app/Models/User.php');
$user = readRequired($userPath);

if (! str_contains($user, "data_get(\$this->metadata, 'portal_only'")) {
    $pattern = '~public function canAccessPanel\(Panel \$panel\): bool\s*\{~';

    $user = preg_replace(
        $pattern,
        "public function canAccessPanel(Panel \$panel): bool\n    {\n        if ((bool) data_get(\$this->metadata, 'portal_only', false)) {\n            return false;\n        }\n",
        $user,
        1,
        $count
    ) ?? $user;

    if (($count ?? 0) !== 1) {
        fwrite(STDERR, "[ERRO] canAccessPanel não localizado em User.php.\n");
        exit(7);
    }

    writeSafe($userPath, $user);
    echo "[SEGURANÇA] Contas portal_only bloqueadas no Filament." . PHP_EOL;
}

echo PHP_EOL;
echo "[OK] Portal do Cliente Base 18.0.1 aplicado." . PHP_EOL;
