import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarDays,
    CarFront,
    Check,
    Circle,
    Fuel,
    Gauge,
    MapPin,
    MessageCircle,
    Users,
} from 'lucide-react';

type TimelineItem = {
    key: string;
    label: string;
    done: boolean;
    current: boolean;
};

type Props = {
    reservation: {
        id: string;
        number: string;
        status: string;
        origin?: string | null;
        pickup_expected_at?: string | null;
        return_expected_at?: string | null;
        total_value: number;
        deposit_value: number;
        commercial_notes?: string | null;
        branch: {
            name?: string | null;
            phone?: string | null;
            whatsapp?: string | null;
        };
        vehicle: {
            name?: string | null;
            category?: string | null;
            transmission?: string | null;
            fuel_type?: string | null;
            seats?: number | null;
            doors?: number | null;
            photo?: string | null;
            photo_url?: string | null;
        };
        timeline: TimelineItem[];
    };
};

const date = (value?: string | null) =>
    value
        ? new Intl.DateTimeFormat('pt-BR', {
              day: '2-digit',
              month: '2-digit',
              year: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
          }).format(new Date(value))
        : '—';

const money = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

const statusLabel: Record<string, string> = {
    pending: 'Aguardando confirmação',
    confirmed: 'Confirmada',
    converted: 'Contrato preparado',
    active: 'Em locação',
    completed: 'Concluída',
    cancelled: 'Cancelada',
};

const statusClasses: Record<string, string> = {
    pending: 'bg-amber-50 text-amber-700 ring-amber-200',
    confirmed: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    converted: 'bg-blue-50 text-blue-700 ring-blue-200',
    active: 'bg-red-50 text-red-700 ring-red-200',
    completed: 'bg-zinc-100 text-zinc-700 ring-zinc-200',
    cancelled: 'bg-rose-50 text-rose-700 ring-rose-200',
};

export default function CustomerReservationShow({ reservation }: Props) {
    const whatsapp = (reservation.branch.whatsapp ?? '').replace(/\D/g, '');

    const specs = [
        [
            Users,
            reservation.vehicle.seats
                ? `${reservation.vehicle.seats} lugares`
                : null,
        ],
        [Gauge, reservation.vehicle.transmission],
        [Fuel, reservation.vehicle.fuel_type],
        [
            CarFront,
            reservation.vehicle.doors
                ? `${reservation.vehicle.doors} portas`
                : null,
        ],
    ].filter(([, value]) => Boolean(value));

    const image =
        reservation.vehicle.photo_url ?? reservation.vehicle.photo;

    return (
        <>
            <Head title={`${reservation.number} | Careca Locadora`} />

            <div className="min-h-screen bg-[#f4f2ed] text-zinc-950">
                <header className="border-b border-white/10 bg-zinc-950 text-white">
                    <div className="mx-auto flex h-20 max-w-7xl items-center justify-between px-5 lg:px-8">
                        <Link href="/" className="inline-flex items-center">
                            <img
                                src="/images/careca-locadora-logo.png"
                                alt="Careca Locadora de Veículos"
                                className="h-14 w-auto max-w-[260px] object-contain object-left"
                            />
                        </Link>

                        <Link
                            href="/cliente"
                            className="rounded-xl border border-white/10 px-4 py-2 text-sm font-black transition hover:bg-white/10"
                        >
                            Meu painel
                        </Link>
                    </div>
                </header>

                <main className="mx-auto max-w-7xl px-5 py-10 lg:px-8">
                    <Link
                        href="/cliente/reservas"
                        className="inline-flex items-center gap-2 text-sm font-black text-zinc-600"
                    >
                        <ArrowLeft className="size-4" />
                        Minhas reservas
                    </Link>

                    <div className="mt-8 flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p className="text-xs font-black tracking-[.18em] text-red-600 uppercase">
                                {reservation.number}
                            </p>
                            <h1 className="mt-2 text-4xl font-black">
                                {reservation.vehicle.name ?? 'Sua reserva'}
                            </h1>
                            <p className="mt-2 text-zinc-500">
                                {reservation.vehicle.category ?? 'Veículo'} •{' '}
                                {reservation.branch.name ?? 'Filial'}
                            </p>
                        </div>

                        <span
                            className={`rounded-full px-4 py-2 text-sm font-black ring-1 ${
                                statusClasses[reservation.status] ??
                                'bg-white text-zinc-700 ring-zinc-200'
                            }`}
                        >
                            {statusLabel[reservation.status] ??
                                reservation.status}
                        </span>
                    </div>

                    <section className="mt-8 grid gap-6 xl:grid-cols-[1.3fr_.7fr]">
                        <article className="overflow-hidden rounded-[2rem] border border-zinc-200 bg-white shadow-sm">
                            <div className="grid min-h-80 place-items-center bg-zinc-50 p-8">
                                {image ? (
                                    <img
                                        src={
                                            reservation.vehicle.photo_url ??
                                            `/storage/${reservation.vehicle.photo?.replace(
                                                /^public\//,
                                                '',
                                            )}`
                                        }
                                        alt={
                                            reservation.vehicle.name ??
                                            'Veículo'
                                        }
                                        className="max-h-80 w-full object-contain"
                                    />
                                ) : (
                                    <CarFront className="size-24 text-zinc-300" />
                                )}
                            </div>

                            <div className="p-6 sm:p-8">
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <div className="rounded-2xl bg-zinc-50 p-4">
                                        <p className="flex items-center gap-2 text-xs font-bold text-zinc-400">
                                            <CalendarDays className="size-4" />
                                            Retirada
                                        </p>
                                        <strong className="mt-2 block">
                                            {date(
                                                reservation.pickup_expected_at,
                                            )}
                                        </strong>
                                    </div>

                                    <div className="rounded-2xl bg-zinc-50 p-4">
                                        <p className="flex items-center gap-2 text-xs font-bold text-zinc-400">
                                            <CalendarDays className="size-4" />
                                            Devolução
                                        </p>
                                        <strong className="mt-2 block">
                                            {date(
                                                reservation.return_expected_at,
                                            )}
                                        </strong>
                                    </div>
                                </div>

                                {specs.length > 0 && (
                                    <div className="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                        {specs.map(([Icon, value]) => {
                                            const SpecIcon =
                                                Icon as typeof Users;

                                            return (
                                                <div
                                                    key={String(value)}
                                                    className="rounded-2xl border border-zinc-100 p-4"
                                                >
                                                    <SpecIcon className="size-5 text-red-600" />
                                                    <strong className="mt-2 block text-sm">
                                                        {String(value)}
                                                    </strong>
                                                </div>
                                            );
                                        })}
                                    </div>
                                )}

                                {reservation.commercial_notes && (
                                    <div className="mt-6 rounded-2xl border border-zinc-100 bg-zinc-50 p-5">
                                        <p className="text-xs font-black tracking-[.14em] text-zinc-400 uppercase">
                                            Observações da reserva
                                        </p>
                                        <p className="mt-2 text-sm leading-6 text-zinc-600">
                                            {reservation.commercial_notes}
                                        </p>
                                    </div>
                                )}
                            </div>
                        </article>

                        <aside className="space-y-6">
                            <article className="rounded-[2rem] bg-zinc-950 p-6 text-white shadow-xl">
                                <p className="text-xs font-black tracking-[.16em] text-red-400 uppercase">
                                    Resumo financeiro
                                </p>

                                <p className="mt-3 text-4xl font-black">
                                    {money.format(reservation.total_value)}
                                </p>

                                <p className="mt-1 text-xs text-zinc-500">
                                    Valor total da reserva
                                </p>

                                {reservation.deposit_value > 0 && (
                                    <div className="mt-5 rounded-2xl bg-white/5 p-4">
                                        <p className="text-xs text-zinc-500">
                                            Caução
                                        </p>
                                        <strong className="mt-1 block">
                                            {money.format(
                                                reservation.deposit_value,
                                            )}
                                        </strong>
                                    </div>
                                )}

                                <div className="mt-6 flex items-start gap-3 rounded-2xl bg-white/5 p-4">
                                    <MapPin className="mt-0.5 size-5 shrink-0 text-red-400" />
                                    <div>
                                        <p className="text-xs text-zinc-500">
                                            Filial
                                        </p>
                                        <strong>
                                            {reservation.branch.name ??
                                                'Não informada'}
                                        </strong>
                                    </div>
                                </div>

                                {whatsapp && (
                                    <a
                                        href={`https://wa.me/55${whatsapp}`}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="mt-4 flex h-12 items-center justify-center gap-2 rounded-xl bg-emerald-500 font-black text-white transition hover:bg-emerald-600"
                                    >
                                        <MessageCircle className="size-5" />
                                        Falar pelo WhatsApp
                                    </a>
                                )}
                            </article>

                            <article className="rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-sm">
                                <p className="text-xs font-black tracking-[.16em] text-zinc-400 uppercase">
                                    Acompanhamento
                                </p>

                                <h2 className="mt-2 text-2xl font-black">
                                    Sua locação
                                </h2>

                                <p className="mt-2 text-sm text-zinc-500">
                                    Veja em qual etapa sua reserva está.
                                </p>

                                <div className="mt-6">
                                    {reservation.timeline.map(
                                        (item, index) => (
                                            <div
                                                key={item.key}
                                                className="grid grid-cols-[28px_1fr] gap-3"
                                            >
                                                <div className="flex flex-col items-center">
                                                    <span
                                                        className={`grid size-7 place-items-center rounded-full ${
                                                            item.done
                                                                ? 'bg-red-600 text-white'
                                                                : 'bg-zinc-100 text-zinc-400'
                                                        }`}
                                                    >
                                                        {item.done ? (
                                                            <Check className="size-4" />
                                                        ) : (
                                                            <Circle className="size-3" />
                                                        )}
                                                    </span>

                                                    {index <
                                                        reservation.timeline
                                                            .length -
                                                            1 && (
                                                        <span
                                                            className={`h-10 w-0.5 ${
                                                                item.done
                                                                    ? 'bg-red-200'
                                                                    : 'bg-zinc-100'
                                                            }`}
                                                        />
                                                    )}
                                                </div>

                                                <div className="pb-5">
                                                    <strong
                                                        className={
                                                            item.current
                                                                ? 'text-red-600'
                                                                : ''
                                                        }
                                                    >
                                                        {item.label}
                                                    </strong>

                                                    {item.current && (
                                                        <p className="mt-1 text-xs font-semibold text-zinc-400">
                                                            Etapa atual
                                                        </p>
                                                    )}
                                                </div>
                                            </div>
                                        ),
                                    )}
                                </div>
                            </article>
                        </aside>
                    </section>
                </main>
            </div>
        </>
    );
}
