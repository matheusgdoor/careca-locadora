<?php

declare(strict_types=1);

$root = $argv[1] ?? dirname(__DIR__);
$appPath = $root . '/app';

if (! is_dir($appPath)) {
    throw new RuntimeException("Diretorio app nao encontrado: {$appPath}");
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(
        $appPath,
        FilesystemIterator::SKIP_DOTS
    )
);

$changed = [];
$matches = [];

foreach ($iterator as $file) {
    if (! $file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $content = file_get_contents($path);

    if (! str_contains($content, 'selectedItemIds:')) {
        continue;
    }

    $matches[] = $path;

    $patched = str_replace(
        'selectedItemIds:',
        'itemIds:',
        $content,
        $count
    );

    if ($count > 0) {
        file_put_contents($path, $patched);
        $changed[] = [$path, $count];
    }
}

if ($matches === []) {
    echo "[SEM ALTERACAO] Nenhum named argument selectedItemIds: encontrado." . PHP_EOL;
    exit(0);
}

foreach ($changed as [$path, $count]) {
    echo "[CORRIGIDO] {$path} ({$count} ocorrencia(s))" . PHP_EOL;
}
