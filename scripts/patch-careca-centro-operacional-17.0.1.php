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

echo "\nCareca Locadora - Centro Operacional 17.0.1\n";
echo "Indicadores + painel lateral\n\n";

foreach (['/* Centro Operacional 17.0.0 */', 'selectedReservation: null', '@click.prevent="openReservation(['] as $needle) {
    if (! str_contains($view, $needle)) {
        fwrite(STDERR, "[ERRO] Base 17.0.0 incompleta: {$needle}\n");
        exit(4);
    }
}

echo "[OK] Base 17.0.0 localizada.\n";

if (! str_contains($view, 'Ativos exibidos')) {
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
                <div class="rental-ops__kpi-value">R$ {{ number_format($expectedRevenue, 2, ',', '.') }}</div>
                <div class="rental-ops__kpi-help">Reservas visíveis não canceladas</div>
            </div>
        </div>
BLADE;

    $marker = '<div class="rental-calendar__shell">';
    if (! str_contains($view, $marker)) {
        fwrite(STDERR, "[ERRO] Shell da agenda não localizado.\n");
        exit(5);
    }

    $view = str_replace($marker, $kpis . "\n\n        " . $marker, $view, $count);
    if ($count !== 1) {
        fwrite(STDERR, "[ERRO] Falha ao inserir indicadores.\n");
        exit(6);
    }

    echo "[CORRIGIDO] Indicadores operacionais inseridos.\n";
} else {
    echo "[OK] Indicadores já presentes.\n";
}

if (! str_contains($view, 'Abrir reserva completa')) {
    $drawer = <<<'BLADE'

        <template x-teleport="body">
            <div x-cloak x-show="selectedReservation">
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

                    <div class="rental-ops__drawer-body">
                        <span class="rental-ops__drawer-status" x-text="selectedReservation?.status"></span>

                        <div class="rental-ops__vehicle" x-text="selectedReservation?.vehicle"></div>

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
                                @click="navigator.clipboard?.writeText(selectedReservation?.number || '')"
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

    $marker = "\n    </div>\n</x-filament-panels::page>";
    if (! str_contains($view, $marker)) {
        fwrite(STDERR, "[ERRO] Fechamento da agenda não localizado.\n");
        exit(7);
    }

    $view = str_replace($marker, $drawer . $marker, $view, $count);
    if ($count !== 1) {
        fwrite(STDERR, "[ERRO] Falha ao inserir painel lateral.\n");
        exit(8);
    }

    echo "[CORRIGIDO] Painel lateral inserido.\n";
} else {
    echo "[OK] Painel lateral já presente.\n";
}

if (file_put_contents($viewPath, $view) === false) {
    fwrite(STDERR, "[ERRO] Falha ao salvar agenda.\n");
    exit(9);
}

foreach ([
    'Ativos exibidos',
    'Pendentes',
    'Reservas do site',
    'Valor previsto',
    'Abrir reserva completa',
    'WhatsApp',
    'Copiar número',
] as $needle) {
    if (! str_contains($view, $needle)) {
        fwrite(STDERR, "[ERRO] Validação final falhou: {$needle}\n");
        exit(10);
    }
}

echo "\n[OK] Centro Operacional 17.0.1 aplicado com sucesso.\n";
