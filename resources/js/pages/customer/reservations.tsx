import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarDays,
    CarFront,
    CheckCircle2,
    Clock3,
    MapPin,
    XCircle,
} from 'lucide-react';

type Reservation = {
    id: string;
    number: string;
    status: string;
    pickup_expected_at?: string | null;
    return_expected_at?: string | null;
    total_value: number;
    branch?: string | null;
    vehicle?: string | null;
    category?: string | null;
    photo?: string | null;
    photo_url?: string | null;
};

const formatDate = (value?: string | null) =>
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

const labels: Record<string, string> = {
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

export default function CustomerReservations({
    reservations,
}: {
    reservations: Reservation[];
}) {
    const activeCount = reservations.filter((reservation) =>
        ['pending', 'confirmed', 'converted', 'active'].includes(
            reservation.status,
        ),
    ).length;

    const completedCount = reservations.filter(
        (reservation) => reservation.status === 'completed',
    ).length;

    const cancelledCount = reservations.filter(
        (reservation) => reservation.status === 'cancelled',
    ).length;

    return (
        <>
            <Head title="Minhas reservas | Careca Locadora" />

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
                            className="inline-flex items-center gap-2 rounded-xl border border-white/10 px-4 py-2 text-sm font-black transition hover:bg-white/10"
                        >
                            <ArrowLeft className="size-4" />
                            Meu painel
                        </Link>
                    </div>
                </header>

                <main className="mx-auto max-w-7xl px-5 py-10 lg:px-8">
                    <p className="text-xs font-black tracking-[.18em] text-red-600 uppercase">
                        Portal do Cliente
                    </p>

                    <div className="mt-2 flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <h1 className="text-4xl font-black">
                                Minhas reservas
                            </h1>
                            <p className="mt-2 text-zinc-500">
                                Acompanhe todas as suas reservas, períodos e
                                valores em um só lugar.
                            </p>
                        </div>

                        <Link
                            href="/"
                            className="inline-grid h-12 place-items-center rounded-xl bg-red-600 px-6 font-black text-white transition hover:bg-red-700"
                        >
                            Fazer nova reserva
                        </Link>
                    </div>

                    <section className="mt-8 grid gap-4 sm:grid-cols-3">
                        <article className="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm">
                            <div className="flex items-center justify-between">
                                <span className="grid size-10 place-items-center rounded-xl bg-amber-50 text-amber-600">
                                    <Clock3 className="size-5" />
                                </span>
                                <strong className="text-3xl font-black">
                                    {activeCount}
                                </strong>
                            </div>
                            <p className="mt-4 text-sm font-bold text-zinc-500">
                                Em andamento
                            </p>
                        </article>

                        <article className="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm">
                            <div className="flex items-center justify-between">
                                <span className="grid size-10 place-items-center rounded-xl bg-emerald-50 text-emerald-600">
                                    <CheckCircle2 className="size-5" />
                                </span>
                                <strong className="text-3xl font-black">
                                    {completedCount}
                                </strong>
                            </div>
                            <p className="mt-4 text-sm font-bold text-zinc-500">
                                Concluídas
                            </p>
                        </article>

                        <article className="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm">
                            <div className="flex items-center justify-between">
                                <span className="grid size-10 place-items-center rounded-xl bg-rose-50 text-rose-600">
                                    <XCircle className="size-5" />
                                </span>
                                <strong className="text-3xl font-black">
                                    {cancelledCount}
                                </strong>
                            </div>
                            <p className="mt-4 text-sm font-bold text-zinc-500">
                                Canceladas
                            </p>
                        </article>
                    </section>

                    <div className="mt-8 grid gap-5">
                        {reservations.map((reservation) => {
                            const image =
                                reservation.photo_url ?? reservation.photo;

                            return (
                                <article
                                    key={reservation.id}
                                    className="overflow-hidden rounded-[2rem] border border-zinc-200 bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-lg md:grid md:grid-cols-[300px_1fr]"
                                >
                                    <div className="grid min-h-56 place-items-center bg-zinc-50 p-5">
                                        {image ? (
                                            <img
                                                src={
                                                    reservation.photo_url ??
                                                    `/storage/${reservation.photo?.replace(
                                                        /^public\//,
                                                        '',
                                                    )}`
                                                }
                                                alt={
                                                    reservation.vehicle ??
                                                    'Veículo'
                                                }
                                                className="max-h-52 w-full object-contain"
                                            />
                                        ) : (
                                            <CarFront className="size-16 text-zinc-300" />
                                        )}
                                    </div>

                                    <div className="p-6 sm:p-7">
                                        <div className="flex flex-wrap items-start justify-between gap-4">
                                            <div>
                                                <p className="text-xs font-black tracking-[.14em] text-red-600 uppercase">
                                                    {reservation.category ??
                                                        'Reserva'}
                                                </p>
                                                <h2 className="mt-1 text-2xl font-black">
                                                    {reservation.vehicle ??
                                                        reservation.number}
                                                </h2>

                                                <div className="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-zinc-500">
                                                    <span>
                                                        {reservation.number}
                                                    </span>
                                                    <span className="text-zinc-300">
                                                        •
                                                    </span>
                                                    <span className="inline-flex items-center gap-1.5">
                                                        <MapPin className="size-4 text-red-500" />
                                                        {reservation.branch ??
                                                            'Filial não informada'}
                                                    </span>
                                                </div>
                                            </div>

                                            <span
                                                className={`rounded-full px-3 py-1.5 text-xs font-black ring-1 ${
                                                    statusClasses[
                                                        reservation.status
                                                    ] ??
                                                    'bg-zinc-100 text-zinc-700 ring-zinc-200'
                                                }`}
                                            >
                                                {labels[
                                                    reservation.status
                                                ] ?? reservation.status}
                                            </span>
                                        </div>

                                        <div className="mt-6 grid gap-3 sm:grid-cols-2">
                                            <div className="rounded-2xl bg-zinc-50 p-4">
                                                <p className="flex items-center gap-2 text-xs font-bold text-zinc-400">
                                                    <CalendarDays className="size-4" />
                                                    Retirada
                                                </p>
                                                <strong className="mt-1 block">
                                                    {formatDate(
                                                        reservation.pickup_expected_at,
                                                    )}
                                                </strong>
                                            </div>

                                            <div className="rounded-2xl bg-zinc-50 p-4">
                                                <p className="flex items-center gap-2 text-xs font-bold text-zinc-400">
                                                    <CalendarDays className="size-4" />
                                                    Devolução
                                                </p>
                                                <strong className="mt-1 block">
                                                    {formatDate(
                                                        reservation.return_expected_at,
                                                    )}
                                                </strong>
                                            </div>
                                        </div>

                                        <div className="mt-5 flex flex-wrap items-center justify-between gap-4 border-t border-zinc-100 pt-5">
                                            <div>
                                                <p className="text-xs font-bold text-zinc-400">
                                                    Valor da reserva
                                                </p>
                                                <p className="mt-1 text-2xl font-black">
                                                    {money.format(
                                                        reservation.total_value,
                                                    )}
                                                </p>
                                            </div>

                                            <Link
                                                href={`/cliente/reservas/${reservation.id}`}
                                                className="inline-grid h-12 place-items-center rounded-xl bg-zinc-950 px-6 text-sm font-black text-white transition hover:bg-red-600"
                                            >
                                                Ver detalhes
                                            </Link>
                                        </div>
                                    </div>
                                </article>
                            );
                        })}

                        {reservations.length === 0 && (
                            <div className="rounded-[2rem] border border-zinc-200 bg-white p-12 text-center shadow-sm">
                                <CarFront className="mx-auto size-14 text-zinc-300" />
                                <h2 className="mt-4 text-xl font-black">
                                    Nenhuma reserva encontrada
                                </h2>
                                <p className="mt-2 text-sm text-zinc-500">
                                    Quando você fizer uma reserva, ela aparecerá
                                    aqui.
                                </p>
                                <Link
                                    href="/"
                                    className="mt-5 inline-grid h-12 place-items-center rounded-xl bg-red-600 px-6 font-black text-white"
                                >
                                    Fazer uma reserva
                                </Link>
                            </div>
                        )}
                    </div>
                </main>
            </div>
        </>
    );
}
