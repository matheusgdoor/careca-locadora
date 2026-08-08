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
echo "Careca Locadora - Portal Cliente Guard Separado 18.0.1.2" . PHP_EOL;
echo PHP_EOL;

$authPath = p($root, 'config/auth.php');
$auth = readRequired($authPath);

if (! str_contains($auth, "'customer' => [")) {
    $needle = <<<'PHP'
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
PHP;

    if (! str_contains($auth, $needle)) {
        fwrite(STDERR, "[ERRO] Guard web não localizado em config/auth.php.\n");
        exit(5);
    }

    $replacement = $needle . PHP_EOL . <<<'PHP'
        'customer' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
PHP;

    $auth = str_replace($needle, $replacement, $auth);
    writeSafe($authPath, $auth);

    echo "[OK] Guard customer criado sem nova tabela." . PHP_EOL;
}

echo "[OK] Sessão do cliente separada da sessão administrativa." . PHP_EOL;
echo "[OK] /dashboard permanece administrativo." . PHP_EOL;
echo "[OK] /cliente passa a usar auth:customer." . PHP_EOL;
echo PHP_EOL;
echo "[OK] Portal Cliente Guard Separado 18.0.1.2 aplicado." . PHP_EOL;
