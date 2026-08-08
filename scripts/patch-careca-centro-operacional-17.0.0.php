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
echo "Careca Locadora - Centro Operacional 17.0.0" . PHP_EOL;
echo "Indicadores + painel lateral de ação rápida" . PHP_EOL;
echo PHP_EOL;

$viewPath = p(
    $root,
    'resources/views/filament/pages/rental-availability-calendar.blade.php'
);

$view = readRequired($viewPath);

foreach ([
    'rental-calendar__slot--interactive',
    'RentalReservationResource::getUrl',
    'rental-calendar__popover',
] as $needle) {
    if (! str_contains($view, $needle)) {
        fwrite(STDERR, "[ERRO] Agenda Premium 16.1.x incompleta: {$needle}\n");
        exit(5);
    }
}

echo "[OK] Base Agenda Premium localizada." . PHP_EOL;

/*
|--------------------------------------------------------------------------
| 1. CSS do Centro Operacional
|--------------------------------------------------------------------------
*/
if (! str_contains($view, 'rental-ops__kpis')) {
    $css = <<<'CSS'

        /* Centro Operacional 17.0.0 */
        .rental-ops__kpis{
            display:grid;
            grid-template-columns:repeat(5,minmax(135px,1fr));
            gap:.7rem;
            margin:0 0 1rem;
        }
        .rental-ops__kpi{
            position:relative;
            overflow:hidden;
            border:1px solid var(--border);
            border-radius:.9rem;
            background:linear-gradient(180deg,rgba(255,255,255,.035),rgba(255,255,255,.015));
            padding:.82rem .9rem;
        }
        .rental-ops__kpi-label{
            color:var(--muted);
            font-size:.68rem;
            font-weight:750;
            text-transform:uppercase;
            letter-spacing:.045em;
        }
        .rental-ops__kpi-value{
            margin-top:.25rem;
            color:var(--text);
            font-size:1.25rem;
            font-weight:900;
            line-height:1;
        }
        .rental-ops__kpi-help{
            margin-top:.3rem;
            color:#6b7280;
            font-size:.62rem;
        }
        .rental-ops__kpi-dot{
            position:absolute;
            top:.8rem;
            right:.8rem;
            width:.48rem;
            height:.48rem;
            border-radius:999px;
            background:#9ca3af;
            box-shadow:0 0 0 5px rgba(156,163,175,.08);
        }
        .rental-ops__kpi--pending .rental-ops__kpi-dot{background:#f59e0b;box-shadow:0 0 0 5px rgba(245,158,11,.08)}
        .rental-ops__kpi--rented .rental-ops__kpi-dot{background:#3b82f6;box-shadow:0 0 0 5px rgba(59,130,246,.08)}
        .rental-ops__kpi--site .rental-ops__kpi-dot{background:#10b981;box-shadow:0 0 0 5px rgba(16,185,129,.08)}
        .rental-ops__kpi--value .rental-ops__kpi-dot{background:#ef4444;box-shadow:0 0 0 5px rgba(239,68,68,.08)}

        .rental-ops__overlay{
            position:fixed;
            inset:0;
            z-index:99990;
            background:rgba(0,0,0,.48);
            backdrop-filter:blur(2px);
        }
        .rental-ops__drawer{
            position:fixed;
            top:0;
            right:0;
            z-index:99991;
            width:min(430px,100vw);
            height:100vh;
            overflow-y:auto;
            border-left:1px solid rgba(255,255,255,.10);
            background:#101216;
            color:#f8fafc;
            box-shadow:-24px 0 60px rgba(0,0,0,.45);
        }
        .rental-ops__drawer-head{
            position:sticky;
            top:0;
            z-index:2;
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:1rem;
            border-bottom:1px solid rgba(255,255,255,.08);
            background:rgba(16,18,22,.96);
            padding:1.1rem 1.15rem;
            backdrop-filter:blur(12px);
        }
        .rental-ops__drawer-eyebrow{
            color:#9ca3af;
            font-size:.68rem;
            font-weight:800;
            text-transform:uppercase;
            letter-spacing:.06em;
        }
        .rental-ops__drawer-title{
            margin-top:.25rem;
            font-size:1.12rem;
            font-weight:950;
        }
        .rental-ops__drawer-close{
            display:flex;
            width:34px;
            height:34px;
            align-items:center;
            justify-content:center;
            border:1px solid rgba(255,255,255,.10);
            border-radius:.65rem;
            background:#191c21;
            color:#d1d5db;
            cursor:pointer;
        }
        .rental-ops__drawer-body{padding:1rem 1.15rem 1.35rem}
        .rental-ops__drawer-status{
            display:inline-flex;
            border:1px solid rgba(255,255,255,.10);
            border-radius:999px;
            background:rgba(255,255,255,.06);
            padding:.3rem .58rem;
            color:#e5e7eb;
            font-size:.7rem;
            font-weight:850;
        }
        .rental-ops__vehicle{
            margin-top:.8rem;
            border:1px solid rgba(255,255,255,.08);
            border-radius:.8rem;
            background:#15181d;
            padding:.75rem .8rem;
            color:#f3f4f6;
            font-size:.78rem;
            font-weight:750;
            line-height:1.4;
        }
        .rental-ops__details{
            display:grid;
            grid-template-columns:105px 1fr;
            gap:.7rem .8rem;
            margin-top:1rem;
            border-top:1px solid rgba(255,255,255,.08);
            border-bottom:1px solid rgba(255,255,255,.08);
            padding:1rem 0;
            font-size:.77rem;
        }
        .rental-ops__details-label{color:#9ca3af}
        .rental-ops__details-value{color:#f9fafb;font-weight:750;word-break:break-word}
        .rental-ops__actions{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:.65rem;
            margin-top:1rem;
        }
        .rental-ops__action{
            display:flex;
            min-height:42px;
            align-items:center;
            justify-content:center;
            gap:.4rem;
            border:1px solid rgba(255,255,255,.10);
            border-radius:.72rem;
            background:#191c21;
            color:#f3f4f6;
            padding:.65rem .75rem;
            text-decoration:none;
            font-size:.75rem;
            font-weight:850;
            cursor:pointer;
            transition:transform .15s ease,border-color .15s ease,background .15s ease;
        }
        .rental-ops__action:hover{transform:translateY(-1px);border-color:rgba(255,255,255,.18);background:#20242a}
        .rental-ops__action--primary{border-color:rgba(239,68,68,.42);background:rgba(239,68,68,.16);color:#fecaca}
        .rental-ops__action--whatsapp{border-color:rgba(34,197,94,.40);background:rgba(34,197,94,.14);color:#bbf7d0}
        .rental-ops__action--full{grid-column:1/-1}
        .rental-ops__origin{
            margin-top:1rem;
            color:#6b7280;
            font-size:.67rem;
            text-align:center;
        }
        @media (max-width:980px){
            .rental-ops__kpis{grid-template-columns:repeat(2,minmax(135px,1fr))}
            .rental-ops__kpi:last-child{grid-column:1/-1}
        }
CSS;

    $view = preg_replace(
        '/\s*<\/style>/',
        "\n{$css}\n    </style>",
        $view,
        1,
        $count
    ) ?? $view;

    if (($count ?? 0) !== 1) {
        fwrite(STDERR, "[ERRO] Falha ao inserir CSS do Centro Operacional.\n");
        exit(6);
    }

    echo "[CORRIGIDO] CSS do Centro Operacional inserido." . PHP_EOL;
}

/*
|--------------------------------------------------------------------------
| 2. Estado Alpine global
|--------------------------------------------------------------------------
*/
$plainRoot = '<div class="rental-calendar">';
$alpineRoot = <<<'BLADE'
<div
        class="rental-calendar"
        x-data="{
            selectedReservation: null,
            openReservation(data) {
                this.selectedReservation = data;
                document.documentElement.style.overflow = 'hidden';
            },
            closeReservation() {
                this.selectedReservation = null;
                document.documentElement.style.overflow = '';
            },
            whatsappUrl(phone) {
                const digits = String(phone || '').replace(/\D/g, '');
                if (!digits) return null;
                return 'https://wa.me/' + (digits.startsWith('55') ? digits : '55' + digits);
            }
        }"
        @keydown.escape.window="closeReservation()"
    >
BLADE;

if (str_contains($view, $plainRoot)) {
    $view = str_replace($plainRoot, $alpineRoot, $view, $rootCount);

    if ($rootCount !== 1) {
        fwrite(STDERR, "[ERRO] Quantidade inesperada de roots da agenda: {$rootCount}\n");
        exit(7);
    }

    echo "[CORRIGIDO] Estado do painel lateral adicionado." . PHP_EOL;
} elseif (! str_contains($view, 'selectedReservation: null')) {
    fwrite(STDERR, "[ERRO] Root da agenda não localizado.\n");
    exit(8);
}

/*
|--------------------------------------------------------------------------
| 3. Indicadores operacionais
|--------------------------------------------------------------------------
*/
if (! str_contains($view, 'rental-ops__kpis')) {
    $kpis = <<<'BLADE'

        @php
            $visibleRows = collect($this->schedule);

            $visibleReservations = $visibleRows
                ->flatMap(fn ($row) => $row['items'] ?? collect())
                ->map(fn ($item) => $item->reservation)
                ->filter()
                ->unique('id')
                ->values();

            $visibleAssets = $visibleRows->count();

            $pendingReservations = $visibleReservations
                ->where('status', 'pending')
                ->count();

            $rentedReservations = $visibleReservations
                ->filter(fn ($reservation) => in_array(
                    (string) $reservation->status,
                    ['converted', 'active', 'in_rental', 'rented'],
                    true
                ))
                ->count();

            $siteReservations = $visibleReservations
                ->filter(fn ($reservation) => in_array(
                    (string) ($reservation->origin ?? ''),
                    ['public_website', 'website'],
                    true
                ))
                ->count();

            $expectedRevenue = $visibleReservations
                ->reject(fn ($reservation) => $reservation->status === 'cancelled')
                ->sum(fn ($reservation) => (float) ($reservation->total_value ?? 0));
        @endphp

        <div class="rental-ops__kpis">
            <div class="rental-ops__kpi">
                <span class="rental-ops__kpi-dot"></span>
                <div class="rental-ops__kpi-label">Ativos exibidos</div>
                <div class="rental-ops__kpi-value">{{ $visibleAssets }}</div>
                <div class="rental-ops__kpi-help">Após os filtros atuais</div>
            </div>

            <div class="rental-ops__kpi rental-ops__kpi--pending">
                <span class="rental-ops__kpi-dot"></span>
                <div class="rental-ops__kpi-label">Pendentes</div>
                <div class="rental-ops__kpi-value">{{ $pendingReservations }}</div>
                <div class="rental-ops__kpi-help">Aguardando confirmação</div>
            </div>

            <div class="rental-ops__kpi rental-ops__kpi--rented">
                <span class="rental-ops__kpi-dot"></span>
                <div class="rental-ops__kpi-label">Em locação</div>
                <div class="rental-ops__kpi-value">{{ $rentedReservations }}</div>
                <div class="rental-ops__kpi-help">Na visão carregada</div>
            </div>

            <div class="rental-ops__kpi rental-ops__kpi--site">
                <span class="rental-ops__kpi-dot"></span>
                <div class="rental-ops__kpi-label">Reservas do site</div>
                <div class="rental-ops__kpi-value">{{ $siteReservations }}</div>
                <div class="rental-ops__kpi-help">Origem pública</div>
            </div>

            <div class="rental-ops__kpi rental-ops__kpi--value">
                <span class="rental-ops__kpi-dot"></span>
                <div class="rental-ops__kpi-label">Valor previsto</div>
                <div class="rental-ops__kpi-value">
                    R$ {{ number_format($expectedRevenue, 2, ',', '.') }}
                </div>
                <div class="rental-ops__kpi-help">Reservas visíveis não canceladas</div>
            </div>
        </div>
BLADE;

    $toolbarMarker = '<div class="rental-calendar__shell">';

    if (! str_contains($view, $toolbarMarker)) {
        fwrite(STDERR, "[ERRO] Shell da agenda não localizado.\n");
        exit(9);
    }

    $view = str_replace(
        $toolbarMarker,
        $kpis . "\n\n        " . $toolbarMarker,
        $view,
        $kpiCount
    );

    if ($kpiCount !== 1) {
        fwrite(STDERR, "[ERRO] Falha ao inserir indicadores.\n");
        exit(10);
    }

    echo "[CORRIGIDO] Indicadores operacionais adicionados." . PHP_EOL;
}

/*
|--------------------------------------------------------------------------
| 4. Clique do card abre painel lateral
|--------------------------------------------------------------------------
*/
if (! str_contains($view, '@click.prevent="openReservation([')) {
    $hrefPattern = <<<'REGEX'
~(<a\s+href="\{\{\s*\$reservationUrl\s*\}\}")~s
REGEX;

    $clickAttribute = <<<'BLADE'
$1
                                                    @click.prevent="openReservation([
                                                        'number' => @js($reservation->number ?: 'Reserva'),
                                                        'customer' => @js($customerName),
                                                        'status' => @js($reservationStatusLabel),
                                                        'vehicle' => @js(trim(($asset?->prefix ? $asset->prefix . ' · ' : '') . ($asset?->name ?? 'Ativo não informado'))),
                                                        'pickup' => @js($booking->starts_at?->format('d/m/Y H:i') ?? '-'),
                                                        'return' => @js($booking->ends_at?->format('d/m/Y H:i') ?? '-'),
                                                        'value' => @js('R$ ' . number_format((float) ($reservation->total_value ?? 0), 2, ',', '.')),
                                                        'origin' => @js($originLabel),
                                                        'url' => @js($reservationUrl),
                                                        'phone' => @js((string) ($reservation->customer?->phone ?: data_get($reservation->metadata, 'customer_phone', '')))
                                                    ])"
BLADE;

    $updated = preg_replace(
        $hrefPattern,
        $clickAttribute,
        $view,
        1,
        $clickCount
    );

    if ($updated === null || ($clickCount ?? 0) !== 1) {
        fwrite(STDERR, "[ERRO] Link do card não localizado para painel lateral.\n");
        exit(11);
    }

    $view = $updated;
    echo "[CORRIGIDO] Clique do card abre painel lateral." . PHP_EOL;
}

/*
|--------------------------------------------------------------------------
| 5. Drawer no final da agenda
|--------------------------------------------------------------------------
*/
if (! str_contains($view, 'rental-ops__drawer')) {
    $drawer = <<<'BLADE'

        <template x-teleport="body">
            <div x-cloak x-show="selectedReservation" @click.self="closeReservation()">
                <div
                    class="rental-ops__overlay"
                    x-show="selectedReservation"
                    x-transition.opacity
                    @click="closeReservation()"
                ></div>

                <aside
                    class="rental-ops__drawer"
                    x-show="selectedReservation"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="translate-x-full"
                    x-transition:enter-end="translate-x-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="translate-x-0"
                    x-transition:leave-end="translate-x-full"
                    aria-label="Resumo da reserva"
                >
                    <div class="rental-ops__drawer-head">
                        <div>
                            <div class="rental-ops__drawer-eyebrow">Ação rápida</div>
                            <div class="rental-ops__drawer-title" x-text="selectedReservation?.number"></div>
                        </div>

                        <button
                            type="button"
                            class="rental-ops__drawer-close"
                            @click="closeReservation()"
                            aria-label="Fechar"
                        >
                            ×
                        </button>
                    </div>

                    <div class="rental-ops__drawer-body" x-show="selectedReservation">
                        <span
                            class="rental-ops__drawer-status"
                            x-text="selectedReservation?.status"
                        ></span>

                        <div
                            class="rental-ops__vehicle"
                            x-text="selectedReservation?.vehicle"
                        ></div>

                        <div class="rental-ops__details">
                            <span class="rental-ops__details-label">Cliente</span>
                            <span class="rental-ops__details-value" x-text="selectedReservation?.customer"></span>

                            <span class="rental-ops__details-label">Retirada</span>
                            <span class="rental-ops__details-value" x-text="selectedReservation?.pickup"></span>

                            <span class="rental-ops__details-label">Devolução</span>
                            <span class="rental-ops__details-value" x-text="selectedReservation?.return"></span>

                            <span class="rental-ops__details-label">Valor</span>
                            <span class="rental-ops__details-value" x-text="selectedReservation?.value"></span>

                            <span class="rental-ops__details-label">Origem</span>
                            <span class="rental-ops__details-value" x-text="selectedReservation?.origin"></span>

                            <span class="rental-ops__details-label">Contato</span>
                            <span
                                class="rental-ops__details-value"
                                x-text="selectedReservation?.phone || 'Não informado'"
                            ></span>
                        </div>

                        <div class="rental-ops__actions">
                            <a
                                class="rental-ops__action rental-ops__action--primary rental-ops__action--full"
                                :href="selectedReservation?.url"
                            >
                                Abrir reserva completa
                            </a>

                            <a
                                x-show="whatsappUrl(selectedReservation?.phone)"
                                class="rental-ops__action rental-ops__action--whatsapp"
                                :href="whatsappUrl(selectedReservation?.phone)"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                WhatsApp
                            </a>

                            <button
                                type="button"
                                class="rental-ops__action"
                                @click="
                                    navigator.clipboard?.writeText(selectedReservation?.number || '');
                                "
                            >
                                Copiar número
                            </button>
                        </div>

                        <div class="rental-ops__origin">
                            Use Esc ou clique fora para fechar.
                        </div>
                    </div>
                </aside>
            </div>
        </template>
BLADE;

    $closingMarker = "\n    </div>\n</x-filament-panels::page>";

    if (! str_contains($view, $closingMarker)) {
        fwrite(STDERR, "[ERRO] Fechamento da agenda não localizado.\n");
        exit(12);
    }

    $view = str_replace(
        $closingMarker,
        $drawer . $closingMarker,
        $view,
        $drawerCount
    );

    if ($drawerCount !== 1) {
        fwrite(STDERR, "[ERRO] Falha ao inserir painel lateral.\n");
        exit(13);
    }

    echo "[CORRIGIDO] Painel lateral de ação rápida inserido." . PHP_EOL;
}

writeSafe($viewPath, $view);

echo PHP_EOL;
echo "[OK] Centro Operacional 17.0.0 aplicado com sucesso." . PHP_EOL;
