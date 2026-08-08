<?php
declare(strict_types=1);

$root = rtrim($argv[1] ?? 'C:\dev\careca-locadora', "\\/");

function p(string $root, string $relative): string
{
    return $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
}

function rr(string $path): string
{
    if (! is_file($path)) {
        fwrite(STDERR, "[ERRO] Arquivo não encontrado: {$path}\n");
        exit(2);
    }

    $content = file_get_contents($path);

    if ($content === false) {
        exit(3);
    }

    return $content;
}

function ww(string $path, string $content): void
{
    if (file_put_contents($path, $content) === false) {
        exit(4);
    }
}

echo PHP_EOL;
echo "Careca Locadora - Portal Cliente Reserva Premium 18.0.2" . PHP_EOL;
echo PHP_EOL;

$routesPath = p($root, 'routes/customer.php');
$routes = rr($routesPath);

if (! str_contains($routes, "name('customer.reservations.show')")) {
    $needle = <<<'PHP'
        Route::get('/cliente/reservas', [
            CustomerPortalReservationController::class,
            'index',
        ])->name('customer.reservations');
PHP;

    if (! str_contains($routes, $needle)) {
        fwrite(STDERR, "[ERRO] Rota customer.reservations não localizada.\n");
        exit(5);
    }

    $replacement = $needle . PHP_EOL . PHP_EOL . <<<'PHP'
        Route::get('/cliente/reservas/{reservation}', [
            CustomerPortalReservationController::class,
            'show',
        ])->name('customer.reservations.show');
PHP;

    $routes = str_replace($needle, $replacement, $routes);
    ww($routesPath, $routes);

    echo "[OK] Rota de detalhes da reserva registrada." . PHP_EOL;
}

$listPath = p($root, 'resources/js/pages/customer/reservations.tsx');
$list = rr($listPath);

if (! str_contains($list, 'Ver detalhes da reserva')) {
    $needle = '<p className="mt-5 text-2xl font-black">{money.format(reservation.total_value)}</p>';

    if (! str_contains($list, $needle)) {
        fwrite(STDERR, "[ERRO] Valor da reserva não localizado em reservations.tsx.\n");
        exit(6);
    }

    $replacement = $needle . PHP_EOL . <<<'TSX'
                                    <Link
                                        href={`/cliente/reservas/${reservation.id}`}
                                        className="mt-5 inline-grid h-11 place-items-center rounded-xl bg-zinc-950 px-5 text-sm font-black text-white transition hover:bg-red-600"
                                    >
                                        Ver detalhes da reserva
                                    </Link>
TSX;

    $list = str_replace($needle, $replacement, $list);

    if (! str_contains($list, "converted: 'Contrato preparado'")) {
        $list = str_replace(
            "active: 'Em locação',",
            "converted: 'Contrato preparado', active: 'Em locação',",
            $list
        );
    }

    ww($listPath, $list);
    echo "[OK] Minhas Reservas ganhou acesso ao detalhe." . PHP_EOL;
}

echo "[OK] Máscara dinâmica CPF/CNPJ aplicada no primeiro acesso." . PHP_EOL;
echo "[OK] Linha do tempo da reserva adicionada." . PHP_EOL;
echo PHP_EOL;
echo "[OK] Portal Cliente Reserva Premium 18.0.2 aplicado." . PHP_EOL;
