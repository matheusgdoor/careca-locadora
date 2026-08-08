<?php
declare(strict_types=1);

$root = rtrim($argv[1] ?? 'C:\dev\careca-locadora', "\\/");
$path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, 'resources/views/filament/pages/rental-availability-calendar.blade.php');

if (! is_file($path)) {
    fwrite(STDERR, "[ERRO] Agenda não encontrada: {$path}\n");
    exit(2);
}

$view = file_get_contents($path);
if ($view === false) {
    fwrite(STDERR, "[ERRO] Falha ao ler agenda.\n");
    exit(3);
}

echo "\nCareca Locadora - Agenda Premium 16.1.0\n\n";

if (! str_contains($view, 'rental-calendar__slot-primary')) {
    $css = <<<'CSS'

        /* UX Premium 16.1.0 */
        .rental-calendar__cell{position:relative}
        .rental-calendar__slot-wrap{position:relative}
        .rental-calendar__slot--interactive{cursor:pointer;text-decoration:none;transition:transform .16s ease,box-shadow .16s ease}
        .rental-calendar__slot--interactive:hover,.rental-calendar__slot--interactive:focus-visible{transform:translateY(-2px);outline:none;box-shadow:0 8px 20px rgba(0,0,0,.24)}
        .rental-calendar__slot-content{display:flex;width:100%;min-width:0;flex-direction:column;align-items:center;justify-content:center;gap:.16rem}
        .rental-calendar__slot-primary,.rental-calendar__slot-secondary{display:block;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .rental-calendar__slot-primary{font-size:.68rem;font-weight:900}
        .rental-calendar__slot-secondary{opacity:.86;font-size:.60rem;font-weight:650}
        .rental-calendar__slot-time{display:block;opacity:.70;font-size:.56rem;font-weight:650}
        .rental-calendar__slot-pending{border-color:rgba(245,158,11,.50)!important;background:linear-gradient(180deg,rgba(245,158,11,.24),rgba(245,158,11,.12))!important;color:#fde68a!important}
        .rental-calendar__slot-confirmed{border-color:rgba(234,179,8,.58)!important;background:linear-gradient(180deg,rgba(234,179,8,.28),rgba(234,179,8,.13))!important;color:#fef08a!important}
        .rental-calendar__slot-preparing{border-color:rgba(168,85,247,.55)!important;background:linear-gradient(180deg,rgba(168,85,247,.24),rgba(168,85,247,.12))!important;color:#e9d5ff!important}
        .rental-calendar__slot-converted{border-color:rgba(59,130,246,.58)!important;background:linear-gradient(180deg,rgba(59,130,246,.26),rgba(59,130,246,.12))!important;color:#bfdbfe!important}
        .rental-calendar__today{box-shadow:inset 0 3px 0 #ef4444;background:rgba(239,68,68,.045)!important}
        .rental-calendar__popover{z-index:99999;width:min(340px,calc(100vw - 24px));border:1px solid rgba(255,255,255,.12);border-radius:16px;background:#111318;color:#f8fafc;padding:14px;box-shadow:0 18px 55px rgba(0,0,0,.42);pointer-events:none}
        .rental-calendar__popover-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding-bottom:10px;border-bottom:1px solid rgba(255,255,255,.09)}
        .rental-calendar__popover-number{font-size:.92rem;font-weight:900}
        .rental-calendar__popover-status{border-radius:999px;background:rgba(255,255,255,.08);padding:.24rem .5rem;color:#d1d5db;font-size:.66rem;font-weight:800;white-space:nowrap}
        .rental-calendar__popover-grid{display:grid;grid-template-columns:88px 1fr;gap:7px 10px;padding-top:11px;font-size:.74rem;line-height:1.35}
        .rental-calendar__popover-label{color:#9ca3af}.rental-calendar__popover-value{color:#f9fafb;font-weight:700;word-break:break-word}
        .rental-calendar__popover-hint{margin-top:11px;border-top:1px solid rgba(255,255,255,.09);padding-top:9px;color:#9ca3af;font-size:.65rem}
CSS;
    $view = preg_replace('/\s*<\/style>/', "\n{$css}\n    </style>", $view, 1, $count) ?? $view;
    if (($count ?? 0) < 1) {
        fwrite(STDERR, "[ERRO] </style> não encontrado.\n");
        exit(4);
    }
    echo "[OK] CSS premium inserido.\n";
}

$view = str_replace(
    '<th class="rental-calendar__day-head">',
    '<th class="rental-calendar__day-head {{ $day->isToday() ? \'rental-calendar__today\' : \'\' }}">',
    $view
);

$old = <<<'BLADE'
                                        <div class="rental-calendar__slot slot-{{ $slotType }}" title="{{ $tooltip }}">
                                            {{ $statusLabel }}
                                        </div>
BLADE;

$new = <<<'BLADE'
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
                                                $customerName = $reservation->customer?->display_name ?: 'Cliente não informado';
                                                $reservationUrl = \App\Filament\Resources\RentalReservations\RentalReservationResource::getUrl('edit', ['record' => $reservation]);
                                                $originLabel = in_array((string) ($reservation->origin ?? ''), ['public_website', 'website'], true)
                                                    ? 'Site público'
                                                    : 'Administrativo';
                                            @endphp

                                            <div class="rental-calendar__slot-wrap"
                                                 x-data="{ open:false, x:0, y:0 }"
                                                 @mouseenter="open=true; x=$event.clientX+14; y=$event.clientY+14"
                                                 @mousemove="x=$event.clientX+14; y=$event.clientY+14"
                                                 @mouseleave="open=false">
                                                <a href="{{ $reservationUrl }}"
                                                   class="rental-calendar__slot slot-{{ $slotType }} rental-calendar__slot--interactive rental-calendar__slot-{{ $reservationStatus }}"
                                                   aria-label="Abrir reserva {{ $reservation->number }}">
                                                    <span class="rental-calendar__slot-content">
                                                        <span class="rental-calendar__slot-primary">{{ $reservation->number ?: 'Reservado' }}</span>
                                                        <span class="rental-calendar__slot-secondary">{{ \Illuminate\Support\Str::limit($customerName, 13) }}</span>
                                                        <span class="rental-calendar__slot-time">{{ $booking->starts_at?->format('H:i') }} → {{ $booking->ends_at?->format('H:i') }}</span>
                                                    </span>
                                                </a>

                                                <template x-teleport="body">
                                                    <div x-cloak x-show="open" x-transition.opacity.duration.120ms
                                                         class="rental-calendar__popover"
                                                         :style="`position:fixed;left:${Math.min(x, window.innerWidth - 356)}px;top:${Math.min(y, window.innerHeight - 270)}px`">
                                                        <div class="rental-calendar__popover-head">
                                                            <div>
                                                                <div class="rental-calendar__popover-number">{{ $reservation->number ?: 'Reserva' }}</div>
                                                                <div style="margin-top:3px;color:#9ca3af;font-size:.68rem">{{ $asset?->prefix }} · {{ $asset?->name }}</div>
                                                            </div>
                                                            <span class="rental-calendar__popover-status">{{ $reservationStatusLabel }}</span>
                                                        </div>
                                                        <div class="rental-calendar__popover-grid">
                                                            <span class="rental-calendar__popover-label">Cliente</span><span class="rental-calendar__popover-value">{{ $customerName }}</span>
                                                            <span class="rental-calendar__popover-label">Retirada</span><span class="rental-calendar__popover-value">{{ $booking->starts_at?->format('d/m/Y H:i') }}</span>
                                                            <span class="rental-calendar__popover-label">Devolução</span><span class="rental-calendar__popover-value">{{ $booking->ends_at?->format('d/m/Y H:i') }}</span>
                                                            <span class="rental-calendar__popover-label">Valor</span><span class="rental-calendar__popover-value">R$ {{ number_format((float) ($reservation->total_value ?? 0), 2, ',', '.') }}</span>
                                                            <span class="rental-calendar__popover-label">Origem</span><span class="rental-calendar__popover-value">{{ $originLabel }}</span>
                                                        </div>
                                                        <div class="rental-calendar__popover-hint">Clique no card para abrir a reserva completa.</div>
                                                    </div>
                                                </template>
                                            </div>
                                        @else
                                            <div class="rental-calendar__slot slot-{{ $slotType }}" title="{{ $tooltip }}">{{ $statusLabel }}</div>
                                        @endif
BLADE;

if (str_contains($view, $old)) {
    $view = str_replace($old, $new, $view);
    echo "[OK] Cards clicáveis + tooltip premium aplicados.\n";
} elseif (! str_contains($view, 'rental-calendar__slot--interactive')) {
    fwrite(STDERR, "[ERRO] Bloco de slot não localizado. Nenhuma gravação realizada.\n");
    exit(5);
}

file_put_contents($path, $view);
echo "[OK] Agenda Premium 16.1.0 concluída.\n";
