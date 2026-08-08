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
echo "Careca Locadora - Escolha Premium 17.2.2" . PHP_EOL;
echo "Datas PT-BR + período premium" . PHP_EOL;
echo PHP_EOL;

$pagePath = p($root, 'resources/js/pages/public/category-vehicles.tsx');
$page = readRequired($pagePath);

/*
|--------------------------------------------------------------------------
| 1. Helpers de data/hora PT-BR
|--------------------------------------------------------------------------
*/
if (! str_contains($page, 'const formatDateTime =')) {
    $needle = <<<'TSX'
const storageUrl = (path?: string | null): string | null => {
    if (!path) return null;

    return path.startsWith('http')
        ? path
        : `/storage/${path.replace(/^public\//, '')}`;
};
TSX;

    $replacement = <<<'TSX'
const storageUrl = (path?: string | null): string | null => {
    if (!path) return null;

    return path.startsWith('http')
        ? path
        : `/storage/${path.replace(/^public\//, '')}`;
};

const formatDateTime = (value?: string): string => {
    if (!value) return 'Não informado';

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    })
        .format(date)
        .replace(',', ' às');
};

const rentalDurationLabel = (
    startsAt?: string,
    endsAt?: string,
): string | null => {
    if (!startsAt || !endsAt) return null;

    const start = new Date(startsAt);
    const end = new Date(endsAt);

    if (
        Number.isNaN(start.getTime()) ||
        Number.isNaN(end.getTime()) ||
        end <= start
    ) {
        return null;
    }

    const hours = Math.ceil(
        (end.getTime() - start.getTime()) / (1000 * 60 * 60),
    );

    const days = Math.floor(hours / 24);
    const remainingHours = hours % 24;

    if (days > 0 && remainingHours > 0) {
        return `${days} ${days === 1 ? 'dia' : 'dias'} e ${remainingHours} ${
            remainingHours === 1 ? 'hora' : 'horas'
        }`;
    }

    if (days > 0) {
        return `${days} ${days === 1 ? 'dia' : 'dias'}`;
    }

    return `${hours} ${hours === 1 ? 'hora' : 'horas'}`;
};
TSX;

    if (! str_contains($page, $needle)) {
        fwrite(STDERR, "[ERRO] Helper storageUrl não localizado.\n");
        exit(5);
    }

    $page = str_replace($needle, $replacement, $page);
    echo "[CORRIGIDO] Helpers PT-BR adicionados." . PHP_EOL;
}

/*
|--------------------------------------------------------------------------
| 2. Duração da locação
|--------------------------------------------------------------------------
*/
if (! str_contains($page, 'const rentalDuration =')) {
    $needle = <<<'TSX'
    const estimatedTotal =
        quote?.total_value ?? quote?.total ?? null;
TSX;

    $replacement = <<<'TSX'
    const estimatedTotal =
        quote?.total_value ?? quote?.total ?? null;

    const rentalDuration = rentalDurationLabel(startsAt, endsAt);
TSX;

    if (! str_contains($page, $needle)) {
        fwrite(STDERR, "[ERRO] estimatedTotal não localizado.\n");
        exit(6);
    }

    $page = str_replace($needle, $replacement, $page);
    echo "[CORRIGIDO] Duração da locação calculada." . PHP_EOL;
}

/*
|--------------------------------------------------------------------------
| 3. Substituir data ISO do painel por data brasileira premium
|--------------------------------------------------------------------------
*/
$oldBlock = <<<'TSX'
                                    <p className="mt-1 font-black text-zinc-800">
                                        {startsAt || 'Retirada não informada'} até{' '}
                                        {endsAt || 'devolução não informada'}
                                    </p>
TSX;

$newBlock = <<<'TSX'
                                    <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                        <div className="rounded-2xl bg-zinc-50 px-4 py-3">
                                            <p className="text-[11px] font-black tracking-[0.12em] text-zinc-400 uppercase">
                                                Retirada
                                            </p>
                                            <p className="mt-1 font-black text-zinc-900">
                                                {formatDateTime(startsAt)}
                                            </p>
                                        </div>

                                        <div className="rounded-2xl bg-zinc-50 px-4 py-3">
                                            <p className="text-[11px] font-black tracking-[0.12em] text-zinc-400 uppercase">
                                                Devolução
                                            </p>
                                            <p className="mt-1 font-black text-zinc-900">
                                                {formatDateTime(endsAt)}
                                            </p>
                                        </div>
                                    </div>

                                    {rentalDuration && (
                                        <p className="mt-3 text-sm font-bold text-zinc-500">
                                            Duração estimada:{' '}
                                            <span className="font-black text-zinc-800">
                                                {rentalDuration}
                                            </span>
                                        </p>
                                    )}
TSX;

if (str_contains($page, $oldBlock)) {
    $page = str_replace($oldBlock, $newBlock, $page);
    echo "[CORRIGIDO] Datas ISO convertidas para apresentação PT-BR." . PHP_EOL;
} elseif (
    str_contains($page, 'formatDateTime(startsAt)')
    && str_contains($page, 'formatDateTime(endsAt)')
) {
    echo "[OK] Datas PT-BR já aplicadas." . PHP_EOL;
} else {
    fwrite(STDERR, "[ERRO] Bloco atual de período não reconhecido.\n");
    exit(7);
}

writeSafe($pagePath, $page);

/*
|--------------------------------------------------------------------------
| Validação final
|--------------------------------------------------------------------------
*/
$page = readRequired($pagePath);

foreach ([
    'const formatDateTime =',
    'const rentalDurationLabel =',
    'const rentalDuration = rentalDurationLabel',
    'formatDateTime(startsAt)',
    'formatDateTime(endsAt)',
    'Duração estimada',
    'Retirada',
    'Devolução',
] as $needle) {
    if (! str_contains($page, $needle)) {
        fwrite(STDERR, "[ERRO] Validação final falhou: {$needle}\n");
        exit(8);
    }
}

echo PHP_EOL;
echo "[OK] Escolha Premium 17.2.2 aplicada com sucesso." . PHP_EOL;
