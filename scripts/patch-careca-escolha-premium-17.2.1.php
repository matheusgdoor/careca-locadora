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
echo "Careca Locadora - Escolha Premium 17.2.1" . PHP_EOL;
echo "Correção do patch 17.2.0 + aplicação idempotente" . PHP_EOL;
echo PHP_EOL;

$pagePath = p($root, 'resources/js/pages/public/category-vehicles.tsx');
$page = readRequired($pagePath);

/*
|--------------------------------------------------------------------------
| 1. Tipos e estados
|--------------------------------------------------------------------------
*/
if (! str_contains($page, 'type QuoteSummary =')) {
    $needle = "type Feature = {\n    icon: LucideIcon;\n    label: string;\n};";

    $replacement = <<<'TSX'
type Feature = {
    icon: LucideIcon;
    label: string;
};

type QuoteSummary = {
    total_value?: number;
    total?: number;
    deposit_value?: number;
};
TSX;

    if (! str_contains($page, $needle)) {
        fwrite(STDERR, "[ERRO] Tipo Feature não localizado.\n");
        exit(5);
    }

    $page = str_replace($needle, $replacement, $page);
    echo "[CORRIGIDO] Tipo QuoteSummary adicionado." . PHP_EOL;
}

if (! str_contains($page, "const [quote, setQuote]")) {
    $needle = "    const [error, setError] = useState<string | null>(null);";

    $replacement = <<<'TSX'
    const [error, setError] = useState<string | null>(null);
    const [quote, setQuote] = useState<QuoteSummary | null>(null);
    const [sortBy, setSortBy] = useState<'newest' | 'name'>('newest');
TSX;

    if (! str_contains($page, $needle)) {
        fwrite(STDERR, "[ERRO] Estado error não localizado.\n");
        exit(6);
    }

    $page = str_replace($needle, $replacement, $page);
    echo "[CORRIGIDO] Estados de cotação e ordenação adicionados." . PHP_EOL;
}

/*
|--------------------------------------------------------------------------
| 2. Cotação, ordenação e moeda
|--------------------------------------------------------------------------
*/
if (! str_contains($page, "fetch('/api/public/quote'")) {
    $needle = "    const categoryName =\n        vehicles.at(0)?.category?.name ?? 'Veículos disponíveis';";

    $replacement = <<<'TSX'
    useEffect(() => {
        if (!startsAt || !endsAt) {
            return;
        }

        fetch('/api/public/quote', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                branch_id: branchId || null,
                category_id: categoryId,
                starts_at: startsAt,
                ends_at: endsAt,
                commercial_item_ids: [],
                coupon_code: null,
            }),
        })
            .then(async (response) => {
                const payload = await response.json();

                if (response.ok) {
                    setQuote(payload.data ?? null);
                }
            })
            .catch(() => {
                setQuote(null);
            });
    }, [branchId, categoryId, startsAt, endsAt]);

    const categoryName =
        vehicles.at(0)?.category?.name ?? 'Veículos disponíveis';

    const sortedVehicles = useMemo(() => {
        return [...vehicles].sort((a, b) => {
            if (sortBy === 'name') {
                return a.name.localeCompare(b.name, 'pt-BR');
            }

            return (b.model_year ?? 0) - (a.model_year ?? 0);
        });
    }, [vehicles, sortBy]);

    const newestYear = Math.max(
        ...vehicles.map((vehicle) => vehicle.model_year ?? 0),
        0,
    );

    const estimatedTotal =
        quote?.total_value ?? quote?.total ?? null;

    const money = new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    });
TSX;

    if (! str_contains($page, $needle)) {
        fwrite(STDERR, "[ERRO] categoryName não localizado para inserir cotação.\n");
        exit(7);
    }

    $page = str_replace($needle, $replacement, $page);
    echo "[CORRIGIDO] Cotação e ordenação adicionadas." . PHP_EOL;
}

/*
|--------------------------------------------------------------------------
| 3. Corrigir exatamente o erro do 17.2.0
|--------------------------------------------------------------------------
| As chamadas anteriores esqueceram o terceiro argumento ($page).
*/
if (str_contains($page, "{vehicles.length}{' '}")) {
    $page = str_replace(
        "{vehicles.length}{' '}",
        "{sortedVehicles.length}{' '}",
        $page
    );
}

if (str_contains($page, "vehicles.length === 1")) {
    $page = str_replace(
        "vehicles.length === 1",
        "sortedVehicles.length === 1",
        $page
    );
}

if (str_contains($page, "{vehicles.map((vehicle) => {")) {
    $page = str_replace(
        "{vehicles.map((vehicle) => {",
        "{sortedVehicles.map((vehicle) => {",
        $page
    );
}

echo "[CORRIGIDO] Contagem e listagem agora usam sortedVehicles." . PHP_EOL;

/*
|--------------------------------------------------------------------------
| 4. Painel de período e ordenação
|--------------------------------------------------------------------------
*/
if (! str_contains($page, 'Período selecionado')) {
    $needle = <<<'TSX'
                    {!loading && !error && vehicles.length > 0 && (
                        <>
                            <div className="mt-8 flex flex-wrap items-center justify-between gap-4">
TSX;

    $replacement = <<<'TSX'
                    {!loading && !error && vehicles.length > 0 && (
                        <>
                            <div className="mt-8 grid gap-4 rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm md:grid-cols-[1fr_auto] md:items-center">
                                <div>
                                    <p className="text-xs font-black tracking-[0.15em] text-zinc-400 uppercase">
                                        Período selecionado
                                    </p>
                                    <p className="mt-1 font-black text-zinc-800">
                                        {startsAt || 'Retirada não informada'} até{' '}
                                        {endsAt || 'devolução não informada'}
                                    </p>

                                    {estimatedTotal !== null && (
                                        <p className="mt-2 text-sm font-bold text-zinc-500">
                                            Estimativa da categoria:{' '}
                                            <span className="text-lg font-black text-zinc-950">
                                                {money.format(estimatedTotal)}
                                            </span>
                                        </p>
                                    )}
                                </div>

                                <label className="flex items-center gap-3 text-sm font-bold text-zinc-600">
                                    Ordenar por
                                    <select
                                        value={sortBy}
                                        onChange={(event) =>
                                            setSortBy(
                                                event.target.value as
                                                    | 'newest'
                                                    | 'name',
                                            )
                                        }
                                        className="h-11 rounded-xl border border-zinc-200 bg-white px-4 font-bold outline-none focus:border-red-500"
                                    >
                                        <option value="newest">Mais novos</option>
                                        <option value="name">Nome</option>
                                    </select>
                                </label>
                            </div>

                            <div className="mt-5 flex flex-wrap items-center justify-between gap-4">
TSX;

    if (! str_contains($page, $needle)) {
        fwrite(STDERR, "[ERRO] Cabeçalho da listagem não localizado.\n");
        exit(8);
    }

    $page = str_replace($needle, $replacement, $page);
    echo "[CORRIGIDO] Período, cotação e ordenação exibidos." . PHP_EOL;
}

/*
|--------------------------------------------------------------------------
| 5. Badge Mais novo
|--------------------------------------------------------------------------
*/
if (! str_contains($page, '>Mais novo<') && ! str_contains($page, 'Mais novo')) {
    $needle = <<<'TSX'
                                                <span className="absolute top-4 left-4 rounded-full bg-black/85 px-3 py-1.5 text-xs font-black text-white">
                                                    {vehicle.prefix ?? 'Veículo'}
                                                </span>
TSX;

    $replacement = <<<'TSX'
                                                <span className="absolute top-4 left-4 rounded-full bg-black/85 px-3 py-1.5 text-xs font-black text-white">
                                                    {vehicle.prefix ?? 'Veículo'}
                                                </span>

                                                {vehicle.model_year === newestYear &&
                                                    newestYear > 0 && (
                                                        <span className="absolute top-4 right-4 rounded-full bg-red-600 px-3 py-1.5 text-xs font-black text-white shadow">
                                                            Mais novo
                                                        </span>
                                                    )}
TSX;

    if (! str_contains($page, $needle)) {
        fwrite(STDERR, "[ERRO] Badge do prefixo não localizado.\n");
        exit(9);
    }

    $page = str_replace($needle, $replacement, $page);
    echo "[CORRIGIDO] Selo Mais novo adicionado." . PHP_EOL;
}

/*
|--------------------------------------------------------------------------
| 6. Valor estimado no card
|--------------------------------------------------------------------------
*/
if (! str_contains($page, 'Valor estimado para o período')) {
    $needle = <<<'TSX'
                                                <div className="mt-6 grid grid-cols-2 gap-3">
TSX;

    $replacement = <<<'TSX'
                                                {estimatedTotal !== null && (
                                                    <div className="mt-5 rounded-2xl border border-zinc-100 bg-zinc-50 px-4 py-3">
                                                        <p className="text-xs font-bold text-zinc-400">
                                                            Valor estimado para o período
                                                        </p>
                                                        <p className="mt-1 text-xl font-black text-zinc-950">
                                                            {money.format(estimatedTotal)}
                                                        </p>
                                                    </div>
                                                )}

                                                <div className="mt-6 grid grid-cols-2 gap-3">
TSX;

    $page = str_replace($needle, $replacement, $page, $count);

    if ($count < 1) {
        fwrite(STDERR, "[ERRO] Área de ações dos cards não localizada.\n");
        exit(10);
    }

    echo "[CORRIGIDO] Valor estimado adicionado aos cards." . PHP_EOL;
}

writeSafe($pagePath, $page);

/*
|--------------------------------------------------------------------------
| Validação
|--------------------------------------------------------------------------
*/
$page = readRequired($pagePath);

foreach ([
    "fetch('/api/public/quote'",
    'Período selecionado',
    'Estimativa da categoria',
    'Mais novos',
    'Mais novo',
    'Valor estimado para o período',
    'sortedVehicles.map',
] as $needle) {
    if (! str_contains($page, $needle)) {
        fwrite(STDERR, "[ERRO] Validação final falhou: {$needle}\n");
        exit(11);
    }
}

echo PHP_EOL;
echo "[OK] Escolha Premium 17.2.1 aplicada com sucesso." . PHP_EOL;
