import { Head } from '@inertiajs/react';
import { ArrowRight, CalendarDays, CarFront, Fuel, Gauge, MapPin, Search, Users } from 'lucide-react';
import { FormEvent, useEffect, useMemo, useState } from 'react';
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


export default function Reserve() {
    const defaults = useMemo(() => {
        const start = new Date();
        start.setDate(start.getDate() + 1);
        start.setHours(8, 0, 0, 0);
        const end = new Date(start);
        end.setDate(end.getDate() + 3);
        end.setHours(18, 0, 0, 0);
        return { start: localDate(start), end: localDate(end) };
    }, []);

    const params = useMemo(() => new URLSearchParams(window.location.search), []);
    const [branches, setBranches] = useState<Branch[]>([]);
    const [categories, setCategories] = useState<CategoryFilter[]>([]);
    const [offers, setOffers] = useState<CategoryOffer[]>([]);
    const [loading, setLoading] = useState(false);
    const [message, setMessage] = useState<string | null>(null);
    const [form, setForm] = useState({
        branch_id: params.get('branch_id') ?? '',
        category_id: params.get('category_id') ?? '',
        starts_at: params.get('starts_at') ?? defaults.start,
        ends_at: params.get('ends_at') ?? defaults.end,
        search: '',
    });

    useEffect(() => {
        Promise.all([
            fetch('/api/public/branches').then((r) => r.json()),
            fetch('/api/public/categories').then((r) => r.json()),
        ]).then(([b, c]) => {
            setBranches(b.data ?? []);
            setCategories(c.data ?? []);
        }).catch(() => setMessage('Não foi possível carregar filiais e categorias.'));
    }, []);

    const search = async (event?: FormEvent) => {
        event?.preventDefault();
        setLoading(true);
        setMessage(null);
        try {
            const response = await fetch('/api/public/availability', {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    ...form,
                    branch_id: form.branch_id || null,
                    category_id: form.category_id || null,
                    search: form.search || null,
                }),
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message ?? 'Falha ao consultar disponibilidade.');
            setOffers(payload.data ?? []);
            if (!(payload.data ?? []).length) setMessage('Nenhuma categoria disponível para o período informado.');
        } catch (error) {
            setOffers([]);
            setMessage(error instanceof Error ? error.message : 'Falha ao consultar disponibilidade.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <>
            <Head title="Reservar veículo | Careca Locadora" />
            <div className="min-h-screen bg-[#f5f2eb] text-zinc-950">
                <PublicHeader active="reservar" />

                <main>
                    <section className="bg-[#0b0c0e] text-white">
                        <div className="mx-auto grid max-w-7xl gap-10 px-5 py-14 lg:grid-cols-[.85fr_1.15fr] lg:px-8 lg:py-16">
                            <div className="self-center">
                                <p className="text-xs font-black tracking-[.22em] text-red-400 uppercase">Reserva online</p>
                                <h1 className="mt-4 text-5xl font-black tracking-tight md:text-6xl">
                                    Encontre o veículo certo para o seu período.
                                </h1>
                                <p className="mt-5 max-w-xl text-lg leading-8 text-zinc-300">
                                    Consulte a disponibilidade real da frota, compare categorias e escolha o veículo ideal.
                                </p>
                                <div className="mt-8 grid gap-3 sm:grid-cols-3">
                                    {['Disponibilidade real', 'Preço transparente', 'Reserva integrada'].map((item) => (
                                        <div key={item} className="rounded-2xl border border-white/10 bg-white/5 p-4 text-sm font-bold">
                                            {item}
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <form onSubmit={search} className="rounded-[2rem] bg-white p-6 text-zinc-950 shadow-2xl sm:p-8">
                                <h2 className="text-2xl font-black">Monte sua reserva</h2>
                                <p className="mt-1 text-sm text-zinc-500">Escolha filial, período e categoria.</p>

                                <label className="mt-6 block text-sm font-black">
                                    Filial
                                    <select value={form.branch_id} onChange={(e) => setForm({ ...form, branch_id: e.target.value })} className="mt-2 h-14 w-full rounded-xl border border-zinc-200 bg-white px-4">
                                        <option value="">Todas as filiais</option>
                                        {branches.map((branch) => <option key={branch.id} value={branch.id}>{branch.name}{branch.city ? ` — ${branch.city}/${branch.state}` : ''}</option>)}
                                    </select>
                                </label>

                                <div className="mt-4 grid gap-4 sm:grid-cols-2">
                                    <label className="text-sm font-black">
                                        Retirada
                                        <input type="datetime-local" value={form.starts_at} onChange={(e) => setForm({ ...form, starts_at: e.target.value })} className="mt-2 h-14 w-full rounded-xl border border-zinc-200 px-4" />
                                    </label>
                                    <label className="text-sm font-black">
                                        Devolução
                                        <input type="datetime-local" value={form.ends_at} onChange={(e) => setForm({ ...form, ends_at: e.target.value })} className="mt-2 h-14 w-full rounded-xl border border-zinc-200 px-4" />
                                    </label>
                                </div>

                                <label className="mt-4 block text-sm font-black">
                                    Categoria
                                    <select value={form.category_id} onChange={(e) => setForm({ ...form, category_id: e.target.value })} className="mt-2 h-14 w-full rounded-xl border border-zinc-200 bg-white px-4">
                                        <option value="">Todas as categorias</option>
                                        {categories.map((category) => <option key={category.id} value={category.id}>{category.public_title ?? category.name}</option>)}
                                    </select>
                                </label>

                                <label className="mt-4 block text-sm font-black">
                                    Busca
                                    <div className="relative mt-2">
                                        <Search className="absolute top-1/2 left-4 size-5 -translate-y-1/2 text-zinc-400" />
                                        <input value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder="Modelo, categoria ou veículo" className="h-14 w-full rounded-xl border border-zinc-200 pr-4 pl-12" />
                                    </div>
                                </label>

                                <button disabled={loading} className="mt-6 flex h-14 w-full items-center justify-center gap-2 rounded-xl bg-red-600 font-black text-white hover:bg-red-700 disabled:opacity-60">
                                    {loading ? 'Consultando...' : 'Consultar disponibilidade'}
                                    {!loading && <ArrowRight className="size-5" />}
                                </button>
                            </form>
                        </div>
                    </section>

                    <section className="mx-auto max-w-7xl px-5 py-12 lg:px-8">
                        <div className="flex flex-wrap items-end justify-between gap-4">
                            <div>
                                <p className="text-xs font-black tracking-[.2em] text-red-600 uppercase">Disponibilidade</p>
                                <h2 className="mt-2 text-4xl font-black">Categorias disponíveis</h2>
                            </div>
                            {offers.length > 0 && <span className="rounded-full bg-white px-4 py-2 text-sm font-black shadow-sm">{offers.length} categoria(s)</span>}
                        </div>

                        {message && <div className="mt-7 rounded-2xl border border-amber-200 bg-amber-50 p-4 font-semibold text-amber-900">{message}</div>}

                        {offers.length === 0 && !message && (
                            <div className="mt-8 grid min-h-64 place-items-center rounded-[2rem] border border-dashed border-zinc-300 bg-white/60 text-center">
                                <div><CalendarDays className="mx-auto size-12 text-zinc-300" /><h3 className="mt-4 text-xl font-black">Escolha o período e consulte</h3><p className="mt-2 text-zinc-500">Os veículos disponíveis aparecerão aqui.</p></div>
                            </div>
                        )}

                        <div className="mt-8 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                            {offers.map((offer) => {
                                const image = imageUrl(offer);
                                return (
                                    <article key={offer.id} className="overflow-hidden rounded-[2rem] border border-zinc-200 bg-white shadow-sm">
                                        <div className="relative grid h-60 place-items-center bg-zinc-50 p-4">
                                            {image ? <img src={image} alt={offer.public_title ?? offer.name} className="h-full w-full object-contain" /> : <CarFront className="size-16 text-zinc-300" />}
                                            <span className="absolute top-4 right-4 rounded-full bg-zinc-950 px-3 py-1.5 text-xs font-black text-white">{offer.available_count} disponível(is)</span>
                                        </div>
                                        <div className="p-6">
                                            <p className="text-xs font-black tracking-[.16em] text-red-600 uppercase">{offer.name}</p>
                                            <h3 className="mt-2 text-2xl font-black">{offer.public_title ?? offer.name}</h3>
                                            <p className="mt-2 min-h-10 text-sm text-zinc-500">{offer.similar_models ? `${offer.similar_models} ou similar` : offer.description ?? 'Categoria disponível para locação.'}</p>

                                            <div className="mt-5 grid grid-cols-3 gap-2 text-xs font-bold">
                                                <div className="rounded-xl bg-zinc-50 p-3"><Users className="mb-2 size-4 text-red-600" />{offer.specs?.seats ?? '—'} lugares</div>
                                                <div className="rounded-xl bg-zinc-50 p-3"><Gauge className="mb-2 size-4 text-red-600" />{offer.specs?.transmissions?.[0] ?? 'Câmbio'}</div>
                                                <div className="rounded-xl bg-zinc-50 p-3"><Fuel className="mb-2 size-4 text-red-600" />{offer.specs?.fuel_types?.[0] ?? 'Combustível'}</div>
                                            </div>

                                            {offer.tariffs.daily && <div className="mt-5 rounded-2xl bg-red-50 p-4"><span className="text-xs font-bold text-red-700">A partir de</span><div className="mt-1 text-2xl font-black">{money.format(offer.tariffs.daily.value)} <small className="text-xs text-zinc-500">/ diária</small></div></div>}

                                            <a href={`/categoria/${offer.id}?starts_at=${encodeURIComponent(form.starts_at)}&ends_at=${encodeURIComponent(form.ends_at)}&branch_id=${encodeURIComponent(form.branch_id)}`} className="mt-5 flex h-12 items-center justify-center gap-2 rounded-xl bg-zinc-950 font-black text-white hover:bg-red-600">
                                                Escolher categoria <ArrowRight className="size-4" />
                                            </a>
                                        </div>
                                    </article>
                                );
                            })}
                        </div>
                    </section>
                </main>

                <PublicFooter />
            </div>
        </>
    );
}
