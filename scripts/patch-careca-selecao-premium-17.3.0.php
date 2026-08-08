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
echo "Careca Locadora - Seleção Inteligente Premium 17.3.0" . PHP_EOL;
echo "Mais equipado + recomendado + filial" . PHP_EOL;
echo PHP_EOL;

$pagePath = p($root, 'resources/js/pages/public/category-vehicles.tsx');
$page = readRequired($pagePath);

$page = str_replace(
    "useState<'newest' | 'name'>('newest')",
    "useState<'newest' | 'equipped' | 'name'>('newest')",
    $page
);

$page = str_replace(
    "| 'newest'\n                                                    | 'name'",
    "| 'newest'\n                                                    | 'equipped'\n                                                    | 'name'",
    $page
);

if (
    str_contains($page, "if (sortBy === 'name') {")
    && ! str_contains($page, "if (sortBy === 'equipped')")
) {
    $needle = <<<'TSX'
            if (sortBy === 'name') {
                return a.name.localeCompare(b.name, 'pt-BR');
            }

            return (b.model_year ?? 0) - (a.model_year ?? 0);
TSX;

    $replacement = <<<'TSX'
            if (sortBy === 'name') {
                return a.name.localeCompare(b.name, 'pt-BR');
            }

            if (sortBy === 'equipped') {
                const score = (vehicle: Vehicle) =>
                    Number(Boolean(vehicle.air_conditioning)) +
                    Number(Boolean(vehicle.power_steering)) +
                    Number((vehicle.luggage_capacity ?? 0) > 0) +
                    Number((vehicle.doors ?? 0) >= 4);

                return score(b) - score(a);
            }

            return (b.model_year ?? 0) - (a.model_year ?? 0);
TSX;

    if (! str_contains($page, $needle)) {
        fwrite(STDERR, "[ERRO] Bloco de ordenação não localizado.\n");
        exit(5);
    }

    $page = str_replace($needle, $replacement, $page);
}

if (! str_contains($page, '<option value="equipped">Mais equipados</option>')) {
    $needle = '<option value="newest">Mais novos</option>';
    $replacement = $needle . PHP_EOL . '                                        <option value="equipped">Mais equipados</option>';

    if (! str_contains($page, $needle)) {
        fwrite(STDERR, "[ERRO] Opção Mais novos não localizada.\n");
        exit(6);
    }

    $page = str_replace($needle, $replacement, $page);
}

if (! str_contains($page, 'const equipmentScore =')) {
    $needle = <<<'TSX'
    const newestYear = Math.max(
        ...vehicles.map((vehicle) => vehicle.model_year ?? 0),
        0,
    );
TSX;

    $replacement = <<<'TSX'
    const newestYear = Math.max(
        ...vehicles.map((vehicle) => vehicle.model_year ?? 0),
        0,
    );

    const equipmentScore = (vehicle: Vehicle): number =>
        Number(Boolean(vehicle.air_conditioning)) +
        Number(Boolean(vehicle.power_steering)) +
        Number((vehicle.luggage_capacity ?? 0) > 0) +
        Number((vehicle.doors ?? 0) >= 4);

    const maxEquipmentScore = Math.max(
        ...vehicles.map((vehicle) => equipmentScore(vehicle)),
        0,
    );

    const recommendedVehicleId =
        [...vehicles]
            .sort((a, b) => {
                const equipmentDifference =
                    equipmentScore(b) - equipmentScore(a);

                if (equipmentDifference !== 0) {
                    return equipmentDifference;
                }

                return (b.model_year ?? 0) - (a.model_year ?? 0);
            })
            .at(0)?.id ?? null;
TSX;

    if (! str_contains($page, $needle)) {
        fwrite(STDERR, "[ERRO] newestYear não localizado.\n");
        exit(7);
    }

    $page = str_replace($needle, $replacement, $page);
}

if (! str_contains($page, 'Filial de retirada')) {
    $needle = <<<'TSX'
                                    {rentalDuration && (
                                        <p className="mt-3 text-sm font-bold text-zinc-500">
                                            Duração estimada:{' '}
                                            <span className="font-black text-zinc-800">
                                                {rentalDuration}
                                            </span>
                                        </p>
                                    )}
TSX;

    $replacement = <<<'TSX'
                                    {rentalDuration && (
                                        <p className="mt-3 text-sm font-bold text-zinc-500">
                                            Duração estimada:{' '}
                                            <span className="font-black text-zinc-800">
                                                {rentalDuration}
                                            </span>
                                        </p>
                                    )}

                                    {vehicles.at(0)?.branch?.name && (
                                        <p className="mt-2 text-sm font-bold text-zinc-500">
                                            Filial de retirada:{' '}
                                            <span className="font-black text-zinc-800">
                                                {vehicles.at(0)?.branch?.name}
                                            </span>
                                        </p>
                                    )}
TSX;

    if (! str_contains($page, $needle)) {
        fwrite(STDERR, "[ERRO] Bloco de duração não localizado.\n");
        exit(8);
    }

    $page = str_replace($needle, $replacement, $page);
}

if (! str_contains($page, 'Recomendado')) {
    $needle = <<<'TSX'
                                                {vehicle.model_year === newestYear &&
                                                    newestYear > 0 && (
                                                        <span className="absolute top-4 right-4 rounded-full bg-red-600 px-3 py-1.5 text-xs font-black text-white shadow">
                                                            Mais novo
                                                        </span>
                                                    )}
TSX;

    $replacement = <<<'TSX'
                                                <div className="absolute top-4 right-4 flex flex-col items-end gap-2">
                                                    {vehicle.id ===
                                                        recommendedVehicleId && (
                                                        <span className="rounded-full bg-emerald-600 px-3 py-1.5 text-xs font-black text-white shadow">
                                                            Recomendado
                                                        </span>
                                                    )}

                                                    {vehicle.model_year ===
                                                        newestYear &&
                                                        newestYear > 0 && (
                                                            <span className="rounded-full bg-red-600 px-3 py-1.5 text-xs font-black text-white shadow">
                                                                Mais novo
                                                            </span>
                                                        )}

                                                    {equipmentScore(vehicle) ===
                                                        maxEquipmentScore &&
                                                        maxEquipmentScore > 0 && (
                                                            <span className="rounded-full bg-zinc-900 px-3 py-1.5 text-xs font-black text-white shadow">
                                                                Mais equipado
                                                            </span>
                                                        )}
                                                </div>
TSX;

    if (! str_contains($page, $needle)) {
        fwrite(STDERR, "[ERRO] Selo Mais novo atual não localizado.\n");
        exit(9);
    }

    $page = str_replace($needle, $replacement, $page);
}

if (! str_contains($page, 'Escolher recomendado')) {
    $needle = "                                                        Escolher veículo";
    $replacement = <<<'TSX'
                                                        {vehicle.id ===
                                                        recommendedVehicleId
                                                            ? 'Escolher recomendado'
                                                            : 'Escolher veículo'}
TSX;

    if (! str_contains($page, $needle)) {
        fwrite(STDERR, "[ERRO] CTA Escolher veículo não localizado.\n");
        exit(10);
    }

    $page = str_replace($needle, $replacement, $page, $count);
}

writeSafe($pagePath, $page);

$page = readRequired($pagePath);

foreach ([
    'Mais equipados',
    'const equipmentScore =',
    'recommendedVehicleId',
    'Filial de retirada',
    'Recomendado',
    'Mais equipado',
    'Escolher recomendado',
] as $needle) {
    if (! str_contains($page, $needle)) {
        fwrite(STDERR, "[ERRO] Validação final falhou: {$needle}\n");
        exit(11);
    }
}

echo PHP_EOL;
echo "[OK] Seleção Inteligente Premium 17.3.0 aplicada com sucesso." . PHP_EOL;
