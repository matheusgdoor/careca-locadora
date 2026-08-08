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
echo "Careca Locadora - Filtros Inteligentes 17.4.0" . PHP_EOL;
echo "Transmissão + ar-condicionado + lugares + portas" . PHP_EOL;
echo PHP_EOL;

$pagePath = p($root, 'resources/js/pages/public/category-vehicles.tsx');
$page = readRequired($pagePath);

/*
|--------------------------------------------------------------------------
| 1. Estado dos filtros
|--------------------------------------------------------------------------
*/
if (! str_contains($page, 'const [filters, setFilters]')) {
    $needle = "    const [sortBy, setSortBy] = useState<'newest' | 'equipped' | 'name'>('newest');";

    $replacement = <<<'TSX'
    const [sortBy, setSortBy] = useState<'newest' | 'equipped' | 'name'>('newest');
    const [filters, setFilters] = useState({
        automatic: false,
        airConditioning: false,
        fiveSeats: false,
        fourDoors: false,
    });
TSX;

    if (! str_contains($page, $needle)) {
        fwrite(STDERR, "[ERRO] Estado sortBy não localizado.\n");
        exit(5);
    }

    $page = str_replace($needle, $replacement, $page);
}

/*
|--------------------------------------------------------------------------
| 2. Filtragem antes da ordenação
|--------------------------------------------------------------------------
*/
if (! str_contains($page, 'const filteredVehicles = useMemo')) {
    $needle = <<<'TSX'
    const sortedVehicles = useMemo(() => {
        return [...vehicles].sort((a, b) => {
TSX;

    $replacement = <<<'TSX'
    const filteredVehicles = useMemo(() => {
        return vehicles.filter((vehicle) => {
            const transmission = (vehicle.transmission ?? '').toLowerCase();

            if (
                filters.automatic &&
                !transmission.includes('autom')
            ) {
                return false;
            }

            if (
                filters.airConditioning &&
                !vehicle.air_conditioning
            ) {
                return false;
            }

            if (
                filters.fiveSeats &&
                (vehicle.seats ?? 0) < 5
            ) {
                return false;
            }

            if (
                filters.fourDoors &&
                (vehicle.doors ?? 0) < 4
            ) {
                return false;
            }

            return true;
        });
    }, [filters, vehicles]);

    const sortedVehicles = useMemo(() => {
        return [...filteredVehicles].sort((a, b) => {
TSX;

    if (! str_contains($page, $needle)) {
        fwrite(STDERR, "[ERRO] sortedVehicles não localizado.\n");
        exit(6);
    }

    $page = str_replace($needle, $replacement, $page);

    $page = str_replace(
        '    }, [vehicles, sortBy]);',
        '    }, [filteredVehicles, sortBy]);',
        $page,
        $count
    );

    if ($count < 1) {
        fwrite(STDERR, "[ERRO] Dependências de sortedVehicles não atualizadas.\n");
        exit(7);
    }
}

/*
|--------------------------------------------------------------------------
| 3. Helpers de toggle e limpar
|--------------------------------------------------------------------------
*/
if (! str_contains($page, 'const toggleFilter =')) {
    $needle = <<<'TSX'
    const vehicleUrl = (vehicle: Vehicle): string => {
TSX;

    $replacement = <<<'TSX'
    const toggleFilter = (
        key: keyof typeof filters,
    ): void => {
        setFilters((current) => ({
            ...current,
            [key]: !current[key],
        }));
    };

    const clearFilters = (): void => {
        setFilters({
            automatic: false,
            airConditioning: false,
            fiveSeats: false,
            fourDoors: false,
        });
    };

    const hasActiveFilters = Object.values(filters).some(Boolean);

    const vehicleUrl = (vehicle: Vehicle): string => {
TSX;

    if (! str_contains($page, $needle)) {
        fwrite(STDERR, "[ERRO] vehicleUrl não localizado.\n");
        exit(8);
    }

    $page = str_replace($needle, $replacement, $page);
}

/*
|--------------------------------------------------------------------------
| 4. Barra de filtros
|--------------------------------------------------------------------------
*/
if (! str_contains($page, 'Filtrar veículos')) {
    $needle = <<<'TSX'
                            <div className="mt-5 flex flex-wrap items-center justify-between gap-4">
TSX;

    $replacement = <<<'TSX'
                            <div className="mt-5 rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm">
                                <div className="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p className="text-xs font-black tracking-[0.14em] text-zinc-400 uppercase">
                                            Filtrar veículos
                                        </p>
                                        <p className="mt-1 text-sm font-semibold text-zinc-500">
                                            Refine a lista de acordo com sua preferência.
                                        </p>
                                    </div>

                                    {hasActiveFilters && (
                                        <button
                                            type="button"
                                            onClick={clearFilters}
                                            className="text-sm font-black text-red-600 transition hover:text-red-700"
                                        >
                                            Limpar filtros
                                        </button>
                                    )}
                                </div>

                                <div className="mt-4 flex flex-wrap gap-2">
                                    {[
                                        [
                                            'automatic',
                                            'Automático',
                                            filters.automatic,
                                        ],
                                        [
                                            'airConditioning',
                                            'Ar-condicionado',
                                            filters.airConditioning,
                                        ],
                                        [
                                            'fiveSeats',
                                            '5+ lugares',
                                            filters.fiveSeats,
                                        ],
                                        [
                                            'fourDoors',
                                            '4+ portas',
                                            filters.fourDoors,
                                        ],
                                    ].map(([key, label, active]) => (
                                        <button
                                            key={String(key)}
                                            type="button"
                                            onClick={() =>
                                                toggleFilter(
                                                    key as keyof typeof filters,
                                                )
                                            }
                                            className={`rounded-full border px-4 py-2 text-sm font-black transition ${
                                                active
                                                    ? 'border-red-600 bg-red-600 text-white'
                                                    : 'border-zinc-200 bg-white text-zinc-700 hover:border-red-300 hover:text-red-600'
                                            }`}
                                        >
                                            {String(label)}
                                        </button>
                                    ))}
                                </div>
                            </div>

                            <div className="mt-5 flex flex-wrap items-center justify-between gap-4">
TSX;

    if (! str_contains($page, $needle)) {
        fwrite(STDERR, "[ERRO] Cabeçalho da contagem não localizado.\n");
        exit(9);
    }

    $page = str_replace($needle, $replacement, $page, $count);

    if ($count !== 1) {
        fwrite(STDERR, "[ERRO] Barra de filtros não pôde ser inserida de forma única.\n");
        exit(10);
    }
}

/*
|--------------------------------------------------------------------------
| 5. Estado sem resultado
|--------------------------------------------------------------------------
*/
if (! str_contains($page, 'Nenhum veículo atende aos filtros')) {
    $needle = <<<'TSX'
                            <div className="mt-5 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
TSX;

    $replacement = <<<'TSX'
                            {sortedVehicles.length === 0 && (
                                <div className="mt-5 rounded-3xl border border-zinc-200 bg-white p-8 text-center">
                                    <CarFront className="mx-auto size-10 text-zinc-300" />
                                    <h3 className="mt-3 text-lg font-black">
                                        Nenhum veículo atende aos filtros
                                    </h3>
                                    <p className="mt-1 text-sm text-zinc-500">
                                        Remova um dos filtros para ampliar os resultados.
                                    </p>
                                    <button
                                        type="button"
                                        onClick={clearFilters}
                                        className="mt-4 rounded-xl bg-zinc-950 px-5 py-3 text-sm font-black text-white"
                                    >
                                        Limpar filtros
                                    </button>
                                </div>
                            )}

                            <div className="mt-5 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
TSX;

    if (! str_contains($page, $needle)) {
        fwrite(STDERR, "[ERRO] Grade de veículos não localizada.\n");
        exit(11);
    }

    $page = str_replace($needle, $replacement, $page, $count);

    if ($count !== 1) {
        fwrite(STDERR, "[ERRO] Estado sem resultado não inserido de forma única.\n");
        exit(12);
    }
}

writeSafe($pagePath, $page);

/*
|--------------------------------------------------------------------------
| Validação
|--------------------------------------------------------------------------
*/
$page = readRequired($pagePath);

foreach ([
    'const [filters, setFilters]',
    'const filteredVehicles = useMemo',
    'const toggleFilter =',
    'Filtrar veículos',
    'Automático',
    'Ar-condicionado',
    '5+ lugares',
    '4+ portas',
    'Limpar filtros',
    'Nenhum veículo atende aos filtros',
] as $needle) {
    if (! str_contains($page, $needle)) {
        fwrite(STDERR, "[ERRO] Validação final falhou: {$needle}\n");
        exit(13);
    }
}

echo PHP_EOL;
echo "[OK] Filtros Inteligentes 17.4.0 aplicados com sucesso." . PHP_EOL;
