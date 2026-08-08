import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, CalendarDays, CarFront } from 'lucide-react';

type Reservation = {
    id: string; number: string; status: string;
    pickup_expected_at?: string | null; return_expected_at?: string | null;
    total_value: number; branch?: string | null; vehicle?: string | null;
    category?: string | null; photo?: string | null;
};

const formatDate = (value?: string | null) =>
    value ? new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit',
    }).format(new Date(value)) : '—';

const money = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
const labels: Record<string, string> = {
    pending: 'Aguardando confirmação', confirmed: 'Confirmada',
    active: 'Em locação', completed: 'Concluída', cancelled: 'Cancelada',
};

export default function CustomerReservations({ reservations }: { reservations: Reservation[] }) {
    return (
        <>
            <Head title="Minhas reservas | Careca Locadora" />
            <div className="min-h-screen bg-[#f4f2ed] px-5 py-10">
                <main className="mx-auto max-w-6xl">
                    <Link href="/cliente" className="inline-flex items-center gap-2 text-sm font-black text-zinc-600">
                        <ArrowLeft className="size-4" /> Voltar ao painel
                    </Link>
                    <p className="mt-8 text-xs font-black tracking-[.18em] text-red-600 uppercase">Portal do Cliente</p>
                    <h1 className="mt-2 text-4xl font-black">Minhas reservas</h1>

                    <div className="mt-8 grid gap-5">
                        {reservations.map((reservation) => (
                            <article key={reservation.id} className="overflow-hidden rounded-[2rem] border border-zinc-200 bg-white shadow-sm md:grid md:grid-cols-[260px_1fr]">
                                <div className="grid min-h-52 place-items-center bg-zinc-50 p-5">
                                    {reservation.photo ? (
                                        <img src={`/storage/${reservation.photo.replace(/^public\//, '')}`} alt={reservation.vehicle ?? 'Veículo'} className="max-h-48 w-full object-contain" />
                                    ) : <CarFront className="size-16 text-zinc-300" />}
                                </div>
                                <div className="p-6">
                                    <div className="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p className="text-xs font-black tracking-[.14em] text-red-600 uppercase">{reservation.category ?? 'Reserva'}</p>
                                            <h2 className="mt-1 text-2xl font-black">{reservation.vehicle ?? reservation.number}</h2>
                                            <p className="mt-1 text-sm text-zinc-500">{reservation.number} · {reservation.branch ?? 'Filial'}</p>
                                        </div>
                                        <span className="rounded-full bg-zinc-100 px-3 py-1.5 text-xs font-black">{labels[reservation.status] ?? reservation.status}</span>
                                    </div>
                                    <div className="mt-6 grid gap-3 sm:grid-cols-2">
                                        <div className="rounded-2xl bg-zinc-50 p-4"><p className="flex items-center gap-2 text-xs font-bold text-zinc-400"><CalendarDays className="size-4" /> Retirada</p><strong className="mt-1 block">{formatDate(reservation.pickup_expected_at)}</strong></div>
                                        <div className="rounded-2xl bg-zinc-50 p-4"><p className="flex items-center gap-2 text-xs font-bold text-zinc-400"><CalendarDays className="size-4" /> Devolução</p><strong className="mt-1 block">{formatDate(reservation.return_expected_at)}</strong></div>
                                    </div>
                                    <p className="mt-5 text-2xl font-black">{money.format(reservation.total_value)}</p>
                                </div>
                            </article>
                        ))}
                        {reservations.length === 0 && (
                            <div className="rounded-[2rem] border border-zinc-200 bg-white p-12 text-center">
                                <CarFront className="mx-auto size-14 text-zinc-300" />
                                <h2 className="mt-4 text-xl font-black">Nenhuma reserva encontrada</h2>
                                <Link href="/" className="mt-5 inline-grid h-12 place-items-center rounded-xl bg-red-600 px-6 font-black text-white">Fazer uma reserva</Link>
                            </div>
                        )}
                    </div>
                </main>
            </div>
        </>
    );
}
