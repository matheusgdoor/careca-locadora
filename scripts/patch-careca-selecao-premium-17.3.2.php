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
echo "Careca Locadora - Seleção Inteligente Premium 17.3.2" . PHP_EOL;
echo "Correção definitiva dos selos Recomendado / Mais equipado" . PHP_EOL;
echo PHP_EOL;

$pagePath = p($root, 'resources/js/pages/public/category-vehicles.tsx');
$page = readRequired($pagePath);

/*
|--------------------------------------------------------------------------
| 1. Garantir base lógica do 17.3.x
|--------------------------------------------------------------------------
*/
if (! str_contains($page, "useState<'newest' | 'equipped' | 'name'>('newest')")) {
    $page = str_replace(
        "useState<'newest' | 'name'>('newest')",
        "useState<'newest' | 'equipped' | 'name'>('newest')",
        $page
    );
}

if (! str_contains($page, '<option value="equipped">Mais equipados</option>')) {
    $needle = '<option value="newest">Mais novos</option>';

    if (! str_contains($page, $needle)) {
        fwrite(STDERR, "[ERRO] Opção Mais novos não localizada.\n");
        exit(5);
    }

    $page = str_replace(
        $needle,
        $needle . PHP_EOL .
        '                                        <option value="equipped">Mais equipados</option>',
        $page
    );
}

if (! str_contains($page, "if (sortBy === 'equipped')")) {
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

/*
|--------------------------------------------------------------------------
| 2. Detectar selos corretamente
|--------------------------------------------------------------------------
| BUG 17.3.1:
| str_contains($page, 'Mais equipado') também casa com "Mais equipados"
| da opção do select. Assim o patch concluiu incorretamente que os badges
| já existiam. Agora validamos por marcadores JSX específicos.
*/
$hasRecommendedBadge =
    str_contains($page, 'vehicle.id ===') &&
    str_contains($page, 'recommendedVehicleId && (') &&
    str_contains($page, '>Recomendado<');

$hasEquippedBadge =
    str_contains($page, 'equipmentScore(vehicle) ===') &&
    str_contains($page, 'maxEquipmentScore') &&
    str_contains($page, '>Mais equipado<');

if (! $hasRecommendedBadge || ! $hasEquippedBadge) {
    /*
    | Remove somente um badge antigo isolado "Mais novo", caso exista.
    */
    $oldBadgePattern = '~\s*\{vehicle\.model_year\s*===\s*newestYear\s*&&\s*newestYear\s*>\s*0\s*&&\s*\(\s*<span[^>]*>\s*Mais novo\s*</span>\s*\)\}~s';
    $page = preg_replace($oldBadgePattern, '', $page, 1) ?? $page;

    /*
    | Evita duplicar um bloco premium incompleto de tentativa anterior.
    */
    $premiumBlockPattern = '~\s*<div className="absolute top-4 right-4 flex flex-col items-end gap-2">.*?</div>~s';
    if (
        str_contains($page, 'absolute top-4 right-4 flex flex-col items-end gap-2')
        && ! $hasRecommendedBadge
    ) {
        $page = preg_replace($premiumBlockPattern, '', $page, 1) ?? $page;
    }

    $prefixPattern = '~(<span className="absolute top-4 left-4 rounded-full bg-black/85 px-3 py-1\.5 text-xs font-black text-white">\s*\{vehicle\.prefix \?\? \'Veículo\'\}\s*</span>)~s';

    $premiumBadges = <<<'TSX'

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

    $updated = preg_replace(
        $prefixPattern,
        '$1' . $premiumBadges,
        $page,
        1,
        $count
    );

    if ($updated === null || ($count ?? 0) !== 1) {
        fwrite(STDERR, "[ERRO] Badge de prefixo não localizado para inserir selos.\n");
        exit(9);
    }

    $page = $updated;

    echo "[CORRIGIDO] Selos premium inseridos com detecção específica." . PHP_EOL;
} else {
    echo "[OK] Selos premium completos já presentes." . PHP_EOL;
}

/*
|--------------------------------------------------------------------------
| 3. CTA recomendado
|--------------------------------------------------------------------------
*/
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

    if ($count < 1) {
        fwrite(STDERR, "[ERRO] CTA recomendado não aplicado.\n");
        exit(11);
    }

    echo "[CORRIGIDO] CTA Escolher recomendado aplicado." . PHP_EOL;
}

writeSafe($pagePath, $page);

/*
|--------------------------------------------------------------------------
| 4. Validação específica e sem falsos positivos
|--------------------------------------------------------------------------
*/
$page = readRequired($pagePath);

$checks = [
    "useState<'newest' | 'equipped' | 'name'>('newest')",
    '<option value="equipped">Mais equipados</option>',
    "if (sortBy === 'equipped')",
    'const equipmentScore =',
    'recommendedVehicleId',
    'Filial de retirada',
    'recommendedVehicleId && (',
    '>Recomendado<',
    'equipmentScore(vehicle) ===',
    '>Mais equipado<',
    'Escolher recomendado',
];

foreach ($checks as $needle) {
    if (! str_contains($page, $needle)) {
        fwrite(STDERR, "[ERRO] Validação final falhou: {$needle}\n");
        exit(12);
    }
}

echo PHP_EOL;
echo "[OK] Seleção Inteligente Premium 17.3.2 aplicada com sucesso." . PHP_EOL;
