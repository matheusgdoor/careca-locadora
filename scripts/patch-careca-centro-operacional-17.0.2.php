<?php
declare(strict_types=1);

$root = rtrim($argv[1] ?? 'C:\dev\careca-locadora', "\\/");
$viewPath = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, 'resources/views/filament/pages/rental-availability-calendar.blade.php');

if (! is_file($viewPath)) {
    fwrite(STDERR, "[ERRO] Agenda não encontrada.\n");
    exit(2);
}

$view = file_get_contents($viewPath);
if ($view === false) {
    fwrite(STDERR, "[ERRO] Falha ao ler agenda.\n");
    exit(3);
}

echo "\nCareca Locadora - Centro Operacional 17.0.2\n";
echo "Correção definitiva do clique no card\n\n";

foreach ([
    'selectedReservation: null',
    'openReservation(data)',
    'rental-ops__drawer',
    'Abrir reserva completa',
] as $needle) {
    if (! str_contains($view, $needle)) {
        fwrite(STDERR, "[ERRO] Base 17.0.1 incompleta: {$needle}\n");
        exit(4);
    }
}

echo "[OK] Base 17.0.1 localizada.\n";

/*
|--------------------------------------------------------------------------
| 1. Listener global no root
|--------------------------------------------------------------------------
| O card possui x-data próprio para hover. Para não depender da resolução
| de escopo Alpine entre componentes aninhados, usamos CustomEvent global.
*/
if (! str_contains($view, '@careca-open-reservation.window=')) {
    $needle = '@keydown.escape.window="closeReservation()"';
    $replacement = <<<'BLADE'
@careca-open-reservation.window="openReservation($event.detail)"
        @keydown.escape.window="closeReservation()"
BLADE;

    if (! str_contains($view, $needle)) {
        fwrite(STDERR, "[ERRO] Listener Esc do root não localizado.\n");
        exit(5);
    }

    $view = str_replace($needle, $replacement, $view, $count);
    if ($count !== 1) {
        fwrite(STDERR, "[ERRO] Falha ao inserir listener global do clique.\n");
        exit(6);
    }

    echo "[CORRIGIDO] Listener global de abertura inserido.\n";
} else {
    echo "[OK] Listener global já presente.\n";
}

/*
|--------------------------------------------------------------------------
| 2. Troca chamada direta por dispatch global
|--------------------------------------------------------------------------
*/
$oldPrefix = '@click.prevent="openReservation([';
$newPrefix = '@click.stop.prevent="$dispatch(\'careca-open-reservation\', {';

if (str_contains($view, $oldPrefix)) {
    $view = str_replace($oldPrefix, $newPrefix, $view, $count);

    if ($count < 1) {
        fwrite(STDERR, "[ERRO] Não foi possível trocar o evento de clique.\n");
        exit(7);
    }

    // O bloco antigo fechava com ])". Agora precisa fechar com })".
    $view = preg_replace(
        '/(\'phone\'\s*=>\s*@js\([^\n]+\)\s*)\]\)"/m',
        '$1})"',
        $view,
        1,
        $closeCount
    ) ?? $view;

    if (($closeCount ?? 0) !== 1) {
        fwrite(STDERR, "[ERRO] Não foi possível ajustar fechamento do payload do clique.\n");
        exit(8);
    }

    echo "[CORRIGIDO] Card agora dispara evento global de abertura.\n";
} elseif (str_contains($view, "@click.stop.prevent=\"\$dispatch('careca-open-reservation'")) {
    echo "[OK] Clique via dispatch já presente.\n";
} else {
    fwrite(STDERR, "[ERRO] Evento de clique do card não localizado.\n");
    exit(9);
}

/*
|--------------------------------------------------------------------------
| 3. Evita que o hover permaneça aberto ao clicar
|--------------------------------------------------------------------------
*/
if (! str_contains($view, '@click="open = false"')) {
    $needle = '@mouseleave="open = false"';
    $replacement = <<<'BLADE'
@mouseleave="open = false"
                                                @click="open = false"
BLADE;

    if (str_contains($view, $needle)) {
        $view = str_replace($needle, $replacement, $view, $count);
        echo "[CORRIGIDO] Popover de hover fecha ao clicar.\n";
    }
}

if (file_put_contents($viewPath, $view) === false) {
    fwrite(STDERR, "[ERRO] Falha ao salvar agenda.\n");
    exit(10);
}

/*
|--------------------------------------------------------------------------
| Validação
|--------------------------------------------------------------------------
*/
$check = file_get_contents($viewPath);

foreach ([
    '@careca-open-reservation.window="openReservation($event.detail)"',
    "@click.stop.prevent=\"\$dispatch('careca-open-reservation'",
    'Abrir reserva completa',
    'selectedReservation: null',
] as $needle) {
    if (! str_contains($check, $needle)) {
        fwrite(STDERR, "[ERRO] Validação final falhou: {$needle}\n");
        exit(11);
    }
}

echo "\n[OK] Centro Operacional 17.0.2 aplicado com sucesso.\n";
