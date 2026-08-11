import { Head } from '@inertiajs/react';
import { ArrowRight, CarFront, Fuel, Gauge, Search, Users } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import PublicHeader from '@/components/public/public-header';
import PublicFooter from '@/components/public/public-footer';

type Branch = {
    id: string;
    name: string;
    city?: string | null;
    state?: string | null;
    address?: string | null;
    number?: string | null;
    neighborhood?: string | null;
    zip_code?: string | null;
    phone?: string | null;
    whatsapp?: string | null;
};

type CategoryFilter = {
    id: string;
    name: string;
    public_title?: string | null;
    description?: string | null;
    similar_models?: string | null;
    cover_image?: string | null;
};

type Tariff = {
    id: string;
    name: string;
    billing_unit: string;
    value: number;
    deposit_value: number;
    minimum_quantity: number;
};

type CategoryOffer = {
    id: string;
    name: string;
    public_title?: string | null;
    description?: string | null;
    similar_models?: string | null;
    cover_image?: string | null;
    available_count: number;
    representative_asset_id: string;
    branch?: { id?: string | null; name?: string | null; city?: string | null; state?: string | null };
    specs?: { seats?: number | null; transmissions?: string[]; fuel_types?: string[] };
    photos?: Array<{ path: string; url?: string | null; featured: boolean }>;
    tariffs: { daily?: Tariff | null; fifteen_days?: Tariff | null; monthly?: Tariff | null };
};

const money = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

const localDate = (date: Date) => {
    const pad = (value: number) => String(value).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
};

const imageUrl = (offer: CategoryOffer): string | null => {
    const photo = offer.photos?.find((item) => item.featured) ?? offer.photos?.[0];
    if (photo?.url) return photo.url;
    const path = offer.cover_image ?? photo?.path;
    if (!path) return null;
    return /^https?:\/\//.test(path) ? path : `/storage/${path.replace(/^public\//, '')}`;
};


export default function Categories() {
    const defaults = useMemo(() => {
        const start = new Date();
        start.setDate(start.getDate() + 1);
        start.setHours(8, 0, 0, 0);
        const end = new Date(start);
        end.setDate(end.getDate() + 3);
        end.setHours(18, 0, 0, 0);
        return { start: localDate(start), end: localDate(end) };
    }, []);

    const [offers, setOffers] = useState<CategoryOffer[]>([]);
    const [query, setQuery] = useState('');
    const [loading, setLoading] = useState(true);
    const [message, setMessage] = useState<string | null>(null);

    useEffect(() => {
        fetch('/api/public/availability', {
            method: 'POST',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ starts_at: defaults.start, ends_at: defaults.end, branch_id: null, category_id: null, search: null }),
        })
            .then(async (response) => {
                const payload = await response.json();
                if (!response.ok) throw new Error(payload.message ?? 'Não foi possível carregar as categorias.');
                setOffers(payload.data ?? []);
            })
            .catch((error) => setMessage(error instanceof Error ? error.message : 'Não foi possível carregar as categorias.'))
            .finally(() => setLoading(false));
    }, [defaults]);

    const visible = useMemo(() => {
        const term = query.trim().toLocaleLowerCase('pt-BR');
        if (!term) return offers;
        return offers.filter((offer) =>
            [offer.name, offer.public_title, offer.similar_models, offer.description]
                .filter(Boolean)
                .some((value) => String(value).toLocaleLowerCase('pt-BR').includes(term)),
        );
    }, [offers, query]);

    return (
        <>
            <Head title="Categorias de veículos | Careca Locadora" />
            <div className="min-h-screen bg-[#f5f2eb] text-zinc-950">
                <PublicHeader active="categorias" />

                <main>
                    <section className="bg-zinc-950 text-white">
                        <div className="mx-auto max-w-7xl px-5 py-16 lg:px-8">
                            <p className="text-xs font-black tracking-[.22em] text-red-400 uppercase">Nossa frota</p>
                            <div className="mt-3 flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                                <div>
                                    <h1 className="max-w-4xl text-5xl font-black tracking-tight md:text-6xl">Uma categoria para cada necessidade.</h1>
                                    <p className="mt-5 max-w-2xl text-lg leading-8 text-zinc-300">Compare opções, características e valores antes de iniciar sua reserva.</p>
                                </div>
                                <div className="relative w-full max-w-md">
                                    <Search className="absolute top-1/2 left-4 size-5 -translate-y-1/2 text-zinc-400" />
                                    <input value={query} onChange={(e) => setQuery(e.target.value)} placeholder="Buscar categoria ou modelo" className="h-14 w-full rounded-2xl border border-white/10 bg-white px-12 text-zinc-950" />
                                </div>
                            </div>
                        </div>
                    </section>

                    <section className="mx-auto max-w-7xl px-5 py-12 lg:px-8">
                        <div className="mb-7 flex items-center justify-between">
                            <div><h2 className="text-3xl font-black">Categorias disponíveis</h2><p className="mt-1 text-sm text-zinc-500">Valores de referência para o período padrão de consulta.</p></div>
                            <a href="/reservar" className="hidden rounded-xl bg-red-600 px-5 py-3 text-sm font-black text-white sm:block">Montar reserva</a>
                        </div>

                        {loading && <div className="rounded-2xl bg-white p-8 text-center font-bold">Carregando categorias...</div>}
                        {message && <div className="rounded-2xl border border-amber-200 bg-amber-50 p-5 font-semibold text-amber-900">{message}</div>}

                        <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                            {visible.map((offer) => {
                                const image = imageUrl(offer);
                                return (
                                    <article key={offer.id} className="overflow-hidden rounded-[2rem] border border-zinc-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                                        <div className="relative grid h-64 place-items-center bg-zinc-50 p-4">
                                            {image ? <img src={image} alt={offer.public_title ?? offer.name} className="h-full w-full object-contain" /> : <CarFront className="size-20 text-zinc-300" />}
                                            <span className="absolute top-4 right-4 rounded-full bg-white px-3 py-1.5 text-xs font-black shadow">{offer.available_count} disponível(is)</span>
                                        </div>
                                        <div className="p-6">
                                            <p className="text-xs font-black tracking-[.16em] text-red-600 uppercase">{offer.name}</p>
                                            <h3 className="mt-2 text-2xl font-black">{offer.public_title ?? offer.name}</h3>
                                            <p className="mt-2 min-h-10 text-sm text-zinc-500">{offer.similar_models ? `${offer.similar_models} ou similar` : offer.description ?? 'Modelos equivalentes conforme disponibilidade.'}</p>

                                            <div className="mt-5 grid grid-cols-3 gap-2 text-xs font-bold">
                                                <div className="rounded-xl bg-zinc-50 p-3"><Users className="mb-2 size-4 text-red-600" />{offer.specs?.seats ?? '—'} lugares</div>
                                                <div className="rounded-xl bg-zinc-50 p-3"><Gauge className="mb-2 size-4 text-red-600" />{offer.specs?.transmissions?.[0] ?? 'Câmbio'}</div>
                                                <div className="rounded-xl bg-zinc-50 p-3"><Fuel className="mb-2 size-4 text-red-600" />{offer.specs?.fuel_types?.[0] ?? 'Combustível'}</div>
                                            </div>

                                            <div className="mt-5 grid grid-cols-3 gap-2">
                                                {[
                                                    ['Diária', offer.tariffs.daily],
                                                    ['15 dias', offer.tariffs.fifteen_days],
                                                    ['Mensal', offer.tariffs.monthly],
                                                ].map(([label, tariff]) => (
                                                    <div key={label as string} className="rounded-xl bg-zinc-50 p-3">
                                                        <small className="text-zinc-500">{label as string}</small>
                                                        <b className="mt-1 block text-sm">{tariff ? money.format((tariff as Tariff).value) : 'Consultar'}</b>
                                                    </div>
                                                ))}
                                            </div>

                                            <div className="mt-5 grid grid-cols-2 gap-3">
                                                <a href={`/categoria/${offer.id}?starts_at=${encodeURIComponent(defaults.start)}&ends_at=${encodeURIComponent(defaults.end)}&branch_id=`} className="grid h-12 place-items-center rounded-xl border border-zinc-200 text-sm font-black">Ver veículos</a>
                                                <a href={`/reservar?category_id=${encodeURIComponent(offer.id)}`} className="flex h-12 items-center justify-center gap-2 rounded-xl bg-red-600 text-sm font-black text-white">Reservar <ArrowRight className="size-4" /></a>
                                            </div>
                                        </div>
                                    </article>
                                );
                            })}
                        </div>

                        {!loading && !message && visible.length === 0 && (
                            <div className="rounded-[2rem] border border-zinc-200 bg-white p-10 text-center"><Search className="mx-auto size-12 text-zinc-300" /><h3 className="mt-4 text-xl font-black">Nenhuma categoria encontrada</h3><p className="mt-2 text-zinc-500">Tente outro termo de pesquisa.</p></div>
                        )}
                    </section>
                </main>

                <PublicFooter />
            </div>
        </>
    );
}
