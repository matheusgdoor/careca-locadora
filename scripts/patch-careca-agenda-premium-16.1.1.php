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
echo "Careca Locadora - Agenda Premium 16.1.1" . PHP_EOL;
echo "Compatibilidade com agenda atual + cards clicáveis" . PHP_EOL;
echo PHP_EOL;

$viewPath = p($root, 'resources/views/filament/pages/rental-availability-calendar.blade.php');
$view = readRequired($viewPath);

/*
|--------------------------------------------------------------------------
| 1. Garante CSS premium sem duplicar
|--------------------------------------------------------------------------
*/
if (! str_contains($view, 'rental-calendar__slot--interactive')) {
    fwrite(STDERR, "[ERRO] CSS premium 16.1.0 não foi encontrado. Aplique primeiro o 16.1.0 ou envie o arquivo atual.\n");
    exit(5);
}

echo "[OK] CSS premium 16.1.0 localizado.\n";

/*
|--------------------------------------------------------------------------
| 2. Substitui o bloco do slot no FORMATO ATUAL da view
|--------------------------------------------------------------------------
*/
if (! str_contains($view, 'RentalReservationResource::getUrl')) {
    $pattern = <<<'REGEX'
~<div\s*
\s*class="rental-calendar__slot slot-\{\{\s*\$slotType\s*\}\}"\s*
\s*title="\{\{\s*\$tooltip\s*\}\}"\s*
\s*>\s*
\{\{\s*\$statusLabel\s*\}\}\s*
</div>~x
REGEX;

    $replacement = <<<'BLADE'
@if ($booking?->reservation)
                                            @php
                                                $reservation = $booking->reservation;
                                                $reservationStatus = (string) ($reservation->status ?? 'pending');

                                                $reservationStatusLabel = match ($reservationStatus) {
                                                    'draft' => 'Rascunho',
                                                    'pending' => 'Pendente',
                                                    'confirmed' => 'Confirmada',
                                                    'preparing' => 'Em preparação',
                                                    'converted' => 'Em locação',
                                                    'completed' => 'Concluída',
                                                    'cancelled' => 'Cancelada',
                                                    default => ucfirst($reservationStatus),
                                                };

                                                $customerName = $reservation->customer?->display_name
                                                    ?: 'Cliente não informado';

                                                $reservationUrl =
                                                    \App\Filament\Resources\RentalReservations\RentalReservationResource::getUrl(
                                                        'edit',
                                                        ['record' => $reservation]
                                                    );

                                                $originLabel = in_array(
                                                    (string) ($reservation->origin ?? ''),
                                                    ['public_website', 'website'],
                                                    true
                                                )
                                                    ? 'Site público'
                                                    : 'Administrativo';
                                            @endphp

                                            <div
                                                class="rental-calendar__slot-wrap"
                                                x-data="{ open: false, x: 0, y: 0 }"
                                                @mouseenter="open = true; x = $event.clientX + 14; y = $event.clientY + 14"
                                                @mousemove="x = $event.clientX + 14; y = $event.clientY + 14"
                                                @mouseleave="open = false"
                                            >
                                                <a
                                                    href="{{ $reservationUrl }}"
                                                    class="rental-calendar__slot slot-{{ $slotType }} rental-calendar__slot--interactive rental-calendar__slot-{{ $reservationStatus }}"
                                                    aria-label="Abrir reserva {{ $reservation->number }}"
                                                >
                                                    <span class="rental-calendar__slot-content">
                                                        <span class="rental-calendar__slot-primary">
                                                            {{ $reservation->number ?: 'Reservado' }}
                                                        </span>

                                                        <span class="rental-calendar__slot-secondary">
                                                            {{ \Illuminate\Support\Str::limit($customerName, 13) }}
                                                        </span>

                                                        <span class="rental-calendar__slot-time">
                                                            {{ $booking->starts_at?->format('H:i') }}
                                                            →
                                                            {{ $booking->ends_at?->format('H:i') }}
                                                        </span>
                                                    </span>
                                                </a>

                                                <template x-teleport="body">
                                                    <div
                                                        x-cloak
                                                        x-show="open"
                                                        x-transition.opacity.duration.120ms
                                                        class="rental-calendar__popover"
                                                        :style="`position:fixed;left:${Math.min(x, window.innerWidth - 356)}px;top:${Math.min(y, window.innerHeight - 270)}px`"
                                                    >
                                                        <div class="rental-calendar__popover-head">
                                                            <div>
                                                                <div class="rental-calendar__popover-number">
                                                                    {{ $reservation->number ?: 'Reserva' }}
                                                                </div>

                                                                <div style="margin-top:3px;color:#9ca3af;font-size:.68rem">
                                                                    {{ $asset?->prefix }} · {{ $asset?->name }}
                                                                </div>
                                                            </div>

                                                            <span class="rental-calendar__popover-status">
                                                                {{ $reservationStatusLabel }}
                                                            </span>
                                                        </div>

                                                        <div class="rental-calendar__popover-grid">
                                                            <span class="rental-calendar__popover-label">Cliente</span>
                                                            <span class="rental-calendar__popover-value">
                                                                {{ $customerName }}
                                                            </span>

                                                            <span class="rental-calendar__popover-label">Retirada</span>
                                                            <span class="rental-calendar__popover-value">
                                                                {{ $booking->starts_at?->format('d/m/Y H:i') }}
                                                            </span>

                                                            <span class="rental-calendar__popover-label">Devolução</span>
                                                            <span class="rental-calendar__popover-value">
                                                                {{ $booking->ends_at?->format('d/m/Y H:i') }}
                                                            </span>

                                                            <span class="rental-calendar__popover-label">Valor</span>
                                                            <span class="rental-calendar__popover-value">
                                                                R$ {{ number_format((float) ($reservation->total_value ?? 0), 2, ',', '.') }}
                                                            </span>

                                                            <span class="rental-calendar__popover-label">Status</span>
                                                            <span class="rental-calendar__popover-value">
                                                                {{ $reservationStatusLabel }}
                                                            </span>

                                                            <span class="rental-calendar__popover-label">Origem</span>
                                                            <span class="rental-calendar__popover-value">
                                                                {{ $originLabel }}
                                                            </span>
                                                        </div>

                                                        <div class="rental-calendar__popover-hint">
                                                            Clique no card para abrir a reserva completa.
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        @else
                                            <div
                                                class="rental-calendar__slot slot-{{ $slotType }}"
                                                title="{{ $tooltip }}"
                                            >
                                                {{ $statusLabel }}
                                            </div>
                                        @endif
BLADE;

    $updated = preg_replace($pattern, $replacement, $view, 1, $count);

    if ($updated === null || ($count ?? 0) !== 1) {
        fwrite(STDERR, "[ERRO] Não foi possível substituir o slot no formato atual da agenda.\n");
        exit(6);
    }

    $view = $updated;
    echo "[CORRIGIDO] Slot atual convertido em card clicável com tooltip premium.\n";
} else {
    echo "[OK] Card clicável já aplicado.\n";
}

writeSafe($viewPath, $view);

/*
|--------------------------------------------------------------------------
| 3. Atualiza teste legado que ainda esperava estrutura antiga da agenda
|--------------------------------------------------------------------------
*/
$legacyTestPath = p(
    $root,
    'tests/Feature/Rentals/RentalAvailabilityCalendarPremiumTest.php'
);

if (is_file($legacyTestPath)) {
    $test = readRequired($legacyTestPath);

    $test = str_replace(
        "->toContain('@forelse (\$this->schedule as \$assetId => \$items)')",
        "->toContain('@forelse (\$this->schedule as \$row)')",
        $test
    );

    if (! str_contains($test, "->toContain(\"\\$asset = \\$row['asset']\")")) {
        $test = str_replace(
            "->toContain('@forelse (\$this->schedule as \$row)')",
            "->toContain('@forelse (\$this->schedule as \$row)')\n"
            . "        ->toContain(\"\\$asset = \\$row['asset']\")\n"
            . "        ->toContain(\"\\$items = \\$row['items']\")",
            $test
        );
    }

    writeSafe($legacyTestPath, $test);
    echo "[CORRIGIDO] Teste legado alinhado à estrutura atual da agenda.\n";
}

/*
|--------------------------------------------------------------------------
| Validação final
|--------------------------------------------------------------------------
*/
$check = readRequired($viewPath);

foreach ([
    'RentalReservationResource::getUrl',
    'rental-calendar__slot--interactive',
    'rental-calendar__popover',
    'Clique no card para abrir a reserva completa.',
    'Site público',
    'Em preparação',
] as $needle) {
    if (! str_contains($check, $needle)) {
        fwrite(STDERR, "[ERRO] Validação falhou: {$needle}\n");
        exit(10);
    }
}

echo PHP_EOL;
echo "[OK] Agenda Premium 16.1.1 aplicada com sucesso." . PHP_EOL;
