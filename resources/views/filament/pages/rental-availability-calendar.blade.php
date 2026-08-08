<x-filament-panels::page>
    <style>
        .rental-calendar {
            --panel: #17191d;
            --panel-2: #121418;
            --border: rgba(255, 255, 255, .09);
            --text: #f8fafc;
            --muted: #9ca3af;
        }

        .rental-calendar__toolbar {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .rental-calendar__controls {
            display: flex;
            align-items: end;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .rental-calendar__field {
            min-width: 210px;
        }

        .rental-calendar__label {
            display: block;
            margin-bottom: .45rem;
            color: var(--text);
            font-size: .9rem;
            font-weight: 700;
        }

        .rental-calendar__input,
        .rental-calendar__select {
            width: 100%;
            min-height: 42px;
            border: 1px solid var(--border);
            border-radius: .75rem;
            background: var(--panel);
            color: var(--text);
            padding: .65rem .8rem;
            color-scheme: dark;
        }

        .rental-calendar__actions {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .rental-calendar__button {
            min-height: 42px;
            border: 1px solid var(--border);
            border-radius: .75rem;
            background: var(--panel);
            color: var(--text);
            padding: .65rem .9rem;
            font-size: .82rem;
            font-weight: 700;
            cursor: pointer;
        }

        .rental-calendar__button:hover {
            border-color: rgba(245, 158, 11, .5);
            color: #fbbf24;
        }

        .rental-calendar__period {
            margin-top: .4rem;
            color: var(--muted);
            font-size: .82rem;
        }

        .rental-calendar__legend {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .rental-calendar__legend-item {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            border: 1px solid var(--border);
            border-radius: 999px;
            background: var(--panel);
            padding: .42rem .65rem;
            color: var(--muted);
            font-size: .74rem;
        }

        .rental-calendar__dot {
            width: .55rem;
            height: .55rem;
            border-radius: 999px;
        }

        .dot-free { background: #10b981; }
        .dot-reserved { background: #f59e0b; }
        .dot-rented { background: #3b82f6; }
        .dot-maintenance { background: #ef4444; }
        .dot-blocked { background: #9ca3af; }

        .rental-calendar__shell {
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: 1rem;
            background: var(--panel);
            box-shadow: 0 18px 45px rgba(0, 0, 0, .16);
        }

        .rental-calendar__scroll {
            overflow-x: auto;
            scrollbar-width: thin;
            scrollbar-color: #4b5563 transparent;
        }

        .rental-calendar__table {
            width: 100%;
            min-width: 1420px;
            border-collapse: separate;
            border-spacing: 0;
            table-layout: fixed;
        }

        .rental-calendar__table th,
        .rental-calendar__table td {
            border-right: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }

        .rental-calendar__table tr:last-child td {
            border-bottom: 0;
        }

        .rental-calendar__asset-head,
        .rental-calendar__asset-cell {
            position: sticky;
            left: 0;
            z-index: 4;
            width: 290px;
            min-width: 290px;
            max-width: 290px;
            background: var(--panel-2);
        }

        .rental-calendar__asset-head {
            z-index: 6;
            padding: .9rem 1rem;
            color: var(--text);
            text-align: left;
            font-size: .78rem;
            text-transform: uppercase;
        }

        .rental-calendar__day-head {
            width: 82px;
            min-width: 82px;
            padding: .72rem .35rem;
            background: var(--panel-2);
            text-align: center;
        }

        .rental-calendar__day-number {
            color: var(--text);
            font-size: .82rem;
            font-weight: 800;
        }

        .rental-calendar__weekday {
            margin-top: .16rem;
            color: var(--muted);
            font-size: .68rem;
            text-transform: uppercase;
        }

        .rental-calendar__asset-cell {
            padding: .82rem 1rem;
        }

        .rental-calendar__asset-prefix {
            display: inline-flex;
            border: 1px solid rgba(245, 158, 11, .35);
            border-radius: .42rem;
            background: rgba(245, 158, 11, .10);
            color: #fbbf24;
            padding: .18rem .42rem;
            font-size: .72rem;
            font-weight: 900;
        }

        .rental-calendar__asset-name {
            margin-top: .46rem;
            color: var(--text);
            font-size: .82rem;
            font-weight: 700;
            line-height: 1.28;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .rental-calendar__cell {
            height: 78px;
            padding: .34rem;
            background: var(--panel);
            text-align: center;
            vertical-align: middle;
        }

        .rental-calendar__slot {
            display: flex;
            min-height: 57px;
            align-items: center;
            justify-content: center;
            border: 1px solid transparent;
            border-radius: .68rem;
            padding: .35rem .3rem;
            font-size: .68rem;
            font-weight: 800;
            line-height: 1.15;
            text-align: center;
            overflow: hidden;
            overflow-wrap: anywhere;
        }

        .slot-free {
            border-color: rgba(16, 185, 129, .30);
            background: rgba(16, 185, 129, .12);
            color: #6ee7b7;
        }

        .slot-reserved {
            border-color: rgba(245, 158, 11, .42);
            background: rgba(245, 158, 11, .18);
            color: #fcd34d;
        }

        .slot-rented {
            border-color: rgba(59, 130, 246, .42);
            background: rgba(59, 130, 246, .18);
            color: #93c5fd;
        }

        .slot-maintenance {
            border-color: rgba(239, 68, 68, .42);
            background: rgba(239, 68, 68, .18);
            color: #fca5a5;
        }

        .slot-blocked {
            border-color: rgba(156, 163, 175, .38);
            background: rgba(107, 114, 128, .22);
            color: #d1d5db;
        }

        .rental-calendar__empty {
            padding: 3.5rem 1.5rem;
            color: var(--muted);
            text-align: center;
        }

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

        /* Organização Filtros 17.0.4 */
        .rental-calendar__toolbar{
            display:grid;
            grid-template-columns:minmax(0,1fr);
            gap:.85rem;
            margin-bottom:1rem;
            border:1px solid var(--border);
            border-radius:1rem;
            background:linear-gradient(180deg,rgba(255,255,255,.025),rgba(255,255,255,.012));
            padding:1rem;
        }
        .rental-calendar__controls{
            display:grid;
            grid-template-columns:minmax(200px,.85fr) minmax(260px,1.15fr) minmax(220px,.9fr) auto;
            gap:.75rem;
            width:100%;
            align-items:end;
        }
        .rental-calendar__field{
            min-width:0;
            width:100%;
        }
        .rental-calendar__actions{
            display:flex;
            align-items:center;
            justify-content:flex-end;
            gap:.5rem;
            flex-wrap:nowrap;
            min-width:max-content;
        }
        .rental-calendar__button{
            white-space:nowrap;
        }
        .rental-calendar__legend{
            width:100%;
            border-top:1px solid var(--border);
            padding-top:.8rem;
        }
        @media (max-width:1250px){
            .rental-calendar__controls{
                grid-template-columns:minmax(190px,.8fr) minmax(240px,1.2fr) minmax(210px,.9fr);
            }
            .rental-calendar__actions{
                grid-column:1/-1;
                justify-content:flex-start;
                min-width:0;
            }
        }
        @media (max-width:820px){
            .rental-calendar__toolbar{padding:.85rem}
            .rental-calendar__controls{
                grid-template-columns:minmax(0,1fr);
            }
            .rental-calendar__actions{
                grid-column:auto;
                flex-wrap:wrap;
            }
            .rental-calendar__button{
                flex:1 1 auto;
            }
        }

        /* Filtros Estáveis 17.0.5 */
        .rental-calendar__controls{
            display:grid !important;
            grid-template-columns:minmax(200px,.85fr) minmax(280px,1.15fr) minmax(220px,.9fr) !important;
            gap:.75rem !important;
            width:100% !important;
            align-items:end !important;
        }
        .rental-calendar__actions{
            grid-column:1 / -1 !important;
            display:flex !important;
            align-items:center !important;
            justify-content:flex-start !important;
            gap:.5rem !important;
            flex-wrap:wrap !important;
            min-width:0 !important;
            margin-top:.1rem !important;
        }
        .rental-calendar__actions > *{
            flex:0 0 auto !important;
        }
        .rental-calendar__legend{
            width:100% !important;
            margin-top:.1rem !important;
            padding-top:.8rem !important;
            border-top:1px solid var(--border) !important;
        }
        @media (max-width:900px){
            .rental-calendar__controls{
                grid-template-columns:minmax(0,1fr) minmax(0,1fr) !important;
            }
            .rental-calendar__field:last-of-type{
                grid-column:1 / -1 !important;
            }
        }
        @media (max-width:640px){
            .rental-calendar__controls{
                grid-template-columns:minmax(0,1fr) !important;
            }
            .rental-calendar__field:last-of-type{
                grid-column:auto !important;
            }
            .rental-calendar__actions{
                grid-column:auto !important;
            }
            .rental-calendar__actions > *{
                flex:1 1 calc(50% - .5rem) !important;
            }
        }
    </style>

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
        @careca-open-reservation.window="openReservation($event.detail)"
        @keydown.escape.window="closeReservation()"
    >
        <div class="rental-calendar__toolbar">
            <div class="rental-calendar__controls">
                <div class="rental-calendar__field">
                    <label class="rental-calendar__label">
                        Início da agenda
                    </label>

                    <input
                        type="date"
                        wire:model.live="startDate"
                        class="rental-calendar__input"
                    />

                    <div class="rental-calendar__period">
                        Visão dos próximos 14 dias.
                    </div>
                </div>

                <div class="rental-calendar__field">
                    <label class="rental-calendar__label">
                        Pesquisar ativo
                    </label>

                    <input
                        type="search"
                        wire:model.live.debounce.400ms="search"
                        class="rental-calendar__input"
                        placeholder="Prefixo, nome ou placa"
                    />
                </div>

                <div class="rental-calendar__field">
                    <label class="rental-calendar__label">
                        Exibir
                    </label>

                    <select
                        wire:model.live="statusFilter"
                        class="rental-calendar__select"
                    >
                        <option value="all">Todos os ativos</option>
                        <option value="free">Somente livres</option>
                        <option value="reserved">Com reservas no período</option>
                    </select>
                </div>

                <div class="rental-calendar__actions">
                    <button
                        type="button"
                        wire:click="previousPeriod"
                        class="rental-calendar__button"
                    >
                        ← 14 dias
                    </button>

                    <button
                        type="button"
                        wire:click="today"
                        class="rental-calendar__button"
                    >
                        Hoje
                    </button>

                    <button
                        type="button"
                        wire:click="nextPeriod"
                        class="rental-calendar__button"
                    >
                        14 dias →
                    </button>
                </div>
            </div>

            <div class="rental-calendar__legend">
                <span class="rental-calendar__legend-item">
                    <span class="rental-calendar__dot dot-free"></span>
                    Livre
                </span>

                <span class="rental-calendar__legend-item">
                    <span class="rental-calendar__dot dot-reserved"></span>
                    Reservado
                </span>

                <span class="rental-calendar__legend-item">
                    <span class="rental-calendar__dot dot-rented"></span>
                    Em locação
                </span>

                <span class="rental-calendar__legend-item">
                    <span class="rental-calendar__dot dot-maintenance"></span>
                    Manutenção
                </span>

                <span class="rental-calendar__legend-item">
                    <span class="rental-calendar__dot dot-blocked"></span>
                    Indisponível
                </span>
            </div>
        </div>

        
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

        <div class="rental-calendar__shell">
            <div class="rental-calendar__scroll">
                <table class="rental-calendar__table">
                    <thead>
                        <tr>
                            <th class="rental-calendar__asset-head">
                                Ativo
                            </th>

                            @foreach ($this->days as $day)
                                <th class="rental-calendar__day-head {{ $day->isToday() ? 'rental-calendar__today' : '' }}">
                                    <div class="rental-calendar__day-number">
                                        {{ $day->format('d/m') }}
                                    </div>

                                    <div class="rental-calendar__weekday">
                                        {{ $day->locale('pt_BR')->translatedFormat('D') }}
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($this->schedule as $row)
                            @php
                                $asset = $row['asset'];
                                $items = $row['items'];
                            @endphp

                            <tr>
                                <td class="rental-calendar__asset-cell">
                                    <span class="rental-calendar__asset-prefix">
                                        {{ $asset->prefix ?: 'SEM PREFIXO' }}
                                    </span>

                                    <div
                                        class="rental-calendar__asset-name"
                                        title="{{ $asset->name }}"
                                    >
                                        {{ $asset->name ?: 'Ativo não identificado' }}
                                    </div>
                                </td>

                                @foreach ($this->days as $day)
                                    @php
                                        $dayStart = $day->startOfDay();
                                        $dayEnd = $day->endOfDay();

                                        $booking = $items->first(
                                            function ($item) use ($dayStart, $dayEnd) {
                                                return $item->starts_at->lt($dayEnd)
                                                    && $item->ends_at->gt($dayStart);
                                            }
                                        );

                                        $status = $booking?->reservation?->status;

                                        $slotType = match ($status) {
                                            'active',
                                            'in_rental',
                                            'rented' => 'rented',

                                            'maintenance' => 'maintenance',

                                            'blocked',
                                            'unavailable',
                                            'cancelled' => 'blocked',

                                            default => $booking
                                                ? 'reserved'
                                                : 'free',
                                        };

                                        $statusLabel = match ($slotType) {
                                            'rented' => 'Em locação',
                                            'maintenance' => 'Manutenção',
                                            'blocked' => 'Indisponível',
                                            'reserved' => $booking?->reservation?->number
                                                ?: 'Reservado',
                                            default => 'Livre',
                                        };

                                        $tooltip = $booking
                                            ? implode(
                                                ' | ',
                                                array_filter([
                                                    $booking->reservation?->number,
                                                    $booking->reservation?->customer?->display_name,
                                                    $booking->starts_at?->format('d/m/Y H:i'),
                                                    $booking->ends_at?->format('d/m/Y H:i'),
                                                ])
                                            )
                                            : 'Ativo livre neste dia';
                                    @endphp

                                    <td class="rental-calendar__cell">
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
                                                @click="open = false"
                                            >
                                                <a
                                                    href="{{ $reservationUrl }}"
                                                    @click.stop.prevent="$dispatch('careca-open-reservation', {
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
                                                    })"
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
                                                            {{ $booking->starts_at?->format('H:i') }} → {{ $booking->ends_at?->format('H:i') }}
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
                                                            <span class="rental-calendar__popover-value">{{ $customerName }}</span>

                                                            <span class="rental-calendar__popover-label">Retirada</span>
                                                            <span class="rental-calendar__popover-value">{{ $booking->starts_at?->format('d/m/Y H:i') }}</span>

                                                            <span class="rental-calendar__popover-label">Devolução</span>
                                                            <span class="rental-calendar__popover-value">{{ $booking->ends_at?->format('d/m/Y H:i') }}</span>

                                                            <span class="rental-calendar__popover-label">Valor</span>
                                                            <span class="rental-calendar__popover-value">
                                                                R$ {{ number_format((float) ($reservation->total_value ?? 0), 2, ',', '.') }}
                                                            </span>

                                                            <span class="rental-calendar__popover-label">Status</span>
                                                            <span class="rental-calendar__popover-value">{{ $reservationStatusLabel }}</span>

                                                            <span class="rental-calendar__popover-label">Origem</span>
                                                            <span class="rental-calendar__popover-value">{{ $originLabel }}</span>
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
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="15"
                                    class="rental-calendar__empty"
                                >
                                    Nenhum ativo encontrado para os filtros selecionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
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
    </div>
</x-filament-panels::page>
