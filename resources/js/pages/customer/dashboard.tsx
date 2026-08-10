import { Head, Link, router } from '@inertiajs/react';
import { CalendarDays, CarFront, FileText, LogOut, ReceiptText, ShieldCheck } from 'lucide-react';

type Reservation = {
    id: string;
    number: string;
    status: string;
    pickup_expected_at?: string | null;
    return_expected_at?: string | null;
    total_value: number;
    branch?: string | null;
    vehicle?: string | null;
    photo?: string | null;
    photo_url?: string | null;
};

type Props = {
    customer: { name: string; document?: string | null; email?: string | null; phone?: string | null };
    stats: { reservations: number; active_contracts: number; open_invoices: number; documents: number };
    nextReservation?: Reservation | null;
    recentReservations: Reservation[];
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

const money = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

export default function CustomerDashboard({ customer, stats, nextReservation, recentReservations }: Props) {
    const cards = [
        [CalendarDays, 'Reservas', stats.reservations],
        [FileText, 'Contratos ativos', stats.active_contracts],
        [ReceiptText, 'Pendências', stats.open_invoices],
        [ShieldCheck, 'Documentos', stats.documents],
    ] as const;

    return (
        <>
            <Head title="Meu painel | Careca Locadora" />
            <div className="min-h-screen bg-[#f4f2ed] text-zinc-950">
                <header className="bg-zinc-950 text-white">
                    <div className="mx-auto flex h-20 max-w-7xl items-center justify-between px-5 lg:px-8">
                        <Link href="/" className="flex items-center gap-3 font-black">
                            <span className="grid size-11 place-items-center rounded-2xl bg-red-600"><CarFront /></span>
                            Careca Locadora
                        </Link>
                        <div className="flex items-center gap-3">
                            <Link href="/cliente/reservas" className="hidden rounded-xl px-4 py-2 text-sm font-black hover:bg-white/10 sm:block">
                                Minhas reservas
                            </Link>
                            <button onClick={() => router.post('/cliente/sair')} className="grid size-10 place-items-center rounded-xl bg-white/10" title="Sair">
                                <LogOut className="size-5" />
                            </button>
                        </div>
                    </div>
                </header>

                <main className="mx-auto max-w-7xl px-5 py-10 lg:px-8">
                    <p className="text-xs font-black tracking-[.18em] text-red-600 uppercase">Portal do Cliente</p>
                    <h1 className="mt-2 text-4xl font-black">Olá, {customer.name}.</h1>
                    <p className="mt-2 text-zinc-500">Acompanhe sua locação em um só lugar.</p>

                    <section className="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        {cards.map(([Icon, label, value]) => (
                            <article key={label} className="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm">
                                <div className="flex items-center justify-between">
                                    <span className="grid size-10 place-items-center rounded-xl bg-red-50 text-red-600"><Icon className="size-5" /></span>
                                    <strong className="text-3xl font-black">{value}</strong>
                                </div>
                                <p className="mt-4 text-sm font-bold text-zinc-500">{label}</p>
                            </article>
                        ))}
                    </section>

                    {nextReservation && (
                        <section className="mt-8 overflow-hidden rounded-[2rem] bg-zinc-950 text-white shadow-xl">
                            <div className="grid md:grid-cols-[1fr_1.2fr]">
                                <div className="grid min-h-64 place-items-center bg-white/5 p-6">
                                    {(nextReservation.photo_url ?? nextReservation.photo) ? (
                                        <img
                                            src={nextReservation.photo_url ?? `/storage/${nextReservation.photo?.replace(/^public\//, '')}`}
                                            alt={nextReservation.vehicle ?? 'Veículo'}
                                            className="max-h-60 w-full object-contain"
                                        />
                                    ) : <CarFront className="size-20 text-zinc-700" />}
                                </div>
                                <div className="p-7 sm:p-9">
                                    <p className="text-xs font-black tracking-[.16em] text-red-400 uppercase">Próxima reserva</p>
                                    <h2 className="mt-3 text-3xl font-black">{nextReservation.vehicle ?? nextReservation.number}</h2>
                                    <p className="mt-2 text-zinc-400">{nextReservation.branch ?? 'Filial não informada'}</p>
                                    <div className="mt-6 grid gap-3 sm:grid-cols-2">
                                        <div className="rounded-2xl bg-white/5 p-4"><p className="text-xs text-zinc-500">Retirada</p><strong>{formatDate(nextReservation.pickup_expected_at)}</strong></div>
                                        <div className="rounded-2xl bg-white/5 p-4"><p className="text-xs text-zinc-500">Devolução</p><strong>{formatDate(nextReservation.return_expected_at)}</strong></div>
                                    </div>
                                    <p className="mt-5 text-2xl font-black">{money.format(nextReservation.total_value)}</p>
                                </div>
                            </div>
                        </section>
                    )}

                    <section className="mt-8 rounded-[2rem] border border-zinc-200 bg-white p-6 shadow-sm">
                        <div className="flex items-center justify-between">
                            <h2 className="text-2xl font-black">Reservas recentes</h2>
                            <Link href="/cliente/reservas" className="font-black text-red-600">Ver todas</Link>
                        </div>
                        <div className="mt-5 divide-y divide-zinc-100">
                            {recentReservations.map((reservation) => (
                                <div key={reservation.id} className="grid gap-3 py-4 sm:grid-cols-[1fr_auto] sm:items-center">
                                    <div>
                                        <strong>{reservation.number}</strong>
                                        <p className="mt-1 text-sm text-zinc-500">{reservation.vehicle ?? 'Veículo'} · {formatDate(reservation.pickup_expected_at)}</p>
                                    </div>
                                    <strong>{money.format(reservation.total_value)}</strong>
                                </div>
                            ))}
                            {recentReservations.length === 0 && <p className="py-7 text-center text-zinc-500">Você ainda não possui reservas.</p>}
                        </div>
                    </section>
                </main>
            </div>
        </>
    );
}
