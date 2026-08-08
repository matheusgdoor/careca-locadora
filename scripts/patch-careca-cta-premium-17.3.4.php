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
echo "Careca Locadora - Refinamento CTA Premium 17.3.4" . PHP_EOL;
echo "Botão recomendado em uma linha" . PHP_EOL;
echo PHP_EOL;

$pagePath = p($root, 'resources/js/pages/public/category-vehicles.tsx');
$page = readRequired($pagePath);

$oldClass = 'className="grid h-12 place-items-center rounded-xl bg-red-600 font-black text-white transition hover:bg-red-700"';
$newClass = 'className="grid h-12 place-items-center rounded-xl bg-red-600 px-3 text-center text-sm font-black whitespace-nowrap text-white transition hover:bg-red-700"';

if (str_contains($page, $oldClass)) {
    $page = str_replace($oldClass, $newClass, $page, $count);

    if ($count < 1) {
        fwrite(STDERR, "[ERRO] Botão vermelho não foi atualizado.\n");
        exit(5);
    }

    writeSafe($pagePath, $page);
    echo "[CORRIGIDO] CTA vermelho ganhou largura útil e nowrap." . PHP_EOL;
} elseif (str_contains($page, 'whitespace-nowrap')) {
    echo "[OK] Refinamento do CTA já aplicado." . PHP_EOL;
} else {
    fwrite(STDERR, "[ERRO] Classe atual do CTA não reconhecida.\n");
    exit(6);
}

$page = readRequired($pagePath);

foreach ([
    'Escolher recomendado',
    'whitespace-nowrap',
    'text-sm',
    'px-3',
] as $needle) {
    if (! str_contains($page, $needle)) {
        fwrite(STDERR, "[ERRO] Validação final falhou: {$needle}\n");
        exit(7);
    }
}

echo PHP_EOL;
echo "[OK] Refinamento CTA Premium 17.3.4 aplicado com sucesso." . PHP_EOL;
