import { Head } from '@inertiajs/react';
import {
    ArrowRight,
    CarFront,
    Fuel,
    Gauge,
    MapPin,
    Menu,
    MessageCircle,
    Search,
    ShieldCheck,
    Users,
    X,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

type Branch = {
    id: string;
    name: string;
    city?: string | null;
    state?: string | null;

    address?: string | null;
    street?: string | null;
    number?: string | null;
    neighborhood?: string | null;
    zip_code?: string | null;
    postal_code?: string | null;
    cep?: string | null;
};

type CategoryFilter = {
    id: string;
    name: string;
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
    branch?: {
        id?: string | null;
        name?: string | null;
        city?: string | null;
        state?: string | null;
    };
    specs?: {
        seats?: number | null;
        transmissions?: string[];
        fuel_types?: string[];
    };
    photos?: Array<{ path: string; url?: string | null; featured: boolean }>;
    tariffs: {
        daily?: Tariff | null;
        fifteen_days?: Tariff | null;
        monthly?: Tariff | null;
    };
};

const money = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

const localDate = (date: Date) => {
    const pad = (value: number) => String(value).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(
        date.getDate(),
    )}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
};

const storageUrl = (path?: string | null): string | null => {
    if (!path) return null;
    if (/^https?:\/\//.test(path)) return path;

    return `/storage/${path.replace(/^public\//, '')}`;
};

const categoryImage = (offer: CategoryOffer): string | null => {
    const configured = storageUrl(offer.cover_image);
    if (configured) return configured;

    const photo =
        offer.photos?.find((item) => item.featured) ?? offer.photos?.[0];

    return photo?.url ?? storageUrl(photo?.path);
};

export default function Welcome() {
    const defaults = useMemo(() => {
        const start = new Date();
        start.setDate(start.getDate() + 1);
        start.setHours(8, 0, 0, 0);

        const end = new Date(start);
        end.setDate(end.getDate() + 3);
        end.setHours(18, 0, 0, 0);

        return {
            start: localDate(start),
            end: localDate(end),
        };
    }, []);

    const [branches, setBranches] = useState<Branch[]>([]);
    const [categories, setCategories] = useState<CategoryFilter[]>([]);
    const [offers, setOffers] = useState<CategoryOffer[]>([]);
    const [loading, setLoading] = useState(false);
    const [searched, setSearched] = useState(true);
    const [menuOpen, setMenuOpen] = useState(false);
    const [message, setMessage] = useState<string | null>(null);
    const [form, setForm] = useState({
        branch_id: '',
        category_id: '',
        starts_at: defaults.start,
        ends_at: defaults.end,
        search: '',
    });

    useEffect(() => {
        Promise.all([
            fetch('/api/public/branches').then((response) =>
                response.json(),
            ),
            fetch('/api/public/categories').then((response) =>
                response.json(),
            ),
        ])
            .then(([branchPayload, categoryPayload]) => {
                setBranches(branchPayload.data ?? []);
                setCategories(categoryPayload.data ?? []);
                void searchCategories();
            })
            .catch(() =>
                setMessage(
                    'Não foi possível carregar lojas e categorias.',
                ),
            );
    }, []);

    const update = (
        field: keyof typeof form,
        value: string,
    ) => {
        setForm((current) => ({
            ...current,
            [field]: value,
        }));
    };

    const searchCategories = async () => {
        setLoading(true);
        setSearched(true);
        setMessage(null);

        try {
            const response = await fetch('/api/public/availability', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    ...form,
                    branch_id: form.branch_id || null,
                    category_id: form.category_id || null,
                    search: form.search || null,
                }),
            });

            const payload = await response.json();

            if (!response.ok) {
                throw new Error(
                    payload.message ??
                        'Falha ao consultar disponibilidade.',
                );
            }

            setOffers(payload.data ?? []);

            if (!(payload.data ?? []).length) {
                setMessage(
                    'Nenhuma categoria disponível para o período informado.',
                );
            }

            setTimeout(() => {
                document
                    .getElementById('frota')
                    ?.scrollIntoView({ behavior: 'smooth' });
            }, 50);
        } catch (error) {
            setOffers([]);
            setMessage(
                error instanceof Error
                    ? error.message
                    : 'Falha ao consultar disponibilidade.',
            );
        } finally {
            setLoading(false);
        }
    };

    return (
        <>
            <Head title="Aluguel de veículos" />

            <div className="min-h-screen bg-[#f5f2eb] text-zinc-950">
                <header className="sticky top-0 z-50 bg-[#08090b]/95 text-white backdrop-blur">
                    <div className="mx-auto flex h-20 max-w-7xl items-center justify-between px-5 lg:px-8">
                        <a href="/" className="flex items-center gap-3">
                            <span className="grid size-11 place-items-center rounded-2xl bg-red-600">
                                <CarFront />
                            </span>
                            <span>
                                <b className="block text-lg">
                                    Careca Locadora
                                </b>
                                <small className="text-[10px] tracking-[.2em] text-zinc-400 uppercase">
                                    Veículos e soluções
                                </small>
                            </span>
                        </a>

                        <nav className="hidden gap-8 text-sm font-semibold lg:flex">
                        <a href="#reservar" className="hover:text-red-500">
                            Reservar
                        </a>
                        <a href="#categorias" className="hover:text-red-500">
                            Categorias
                        </a>
                        <a href="/vantagens" className="hover:text-red-500">
                            Vantagens
                        </a>
                        <a href="/filiais" className="hover:text-red-500">
                            Filiais
                        </a>
                    </nav>

                        <div className="hidden gap-3 lg:flex">
                            <a
                                href="/cliente/acesso"
                                className="rounded-full border border-white/15 px-5 py-2.5 text-sm font-bold"
                            >
                                Área do cliente
                            </a>
                            <a
                                href="/app"
                                className="rounded-full bg-red-600 px-5 py-2.5 text-sm font-bold"
                            >
                                Painel administrativo
                            </a>
                        </div>

                        <button
                            type="button"
                            onClick={() => setMenuOpen(!menuOpen)}
                            className="rounded-xl border border-white/15 p-2 lg:hidden"
                        >
                            {menuOpen ? <X /> : <Menu />}
                        </button>
                    </div>
                </header>

                <main>
                    <section className="relative overflow-hidden bg-[#08090b] text-white">
                        <div className="absolute inset-0 opacity-60 [background-image:radial-gradient(circle_at_75%_30%,rgba(220,38,38,.45),transparent_30%),radial-gradient(circle_at_15%_90%,rgba(255,255,255,.1),transparent_28%)]" />

                        <div className="relative mx-auto grid min-h-[650px] max-w-7xl items-center gap-12 px-5 py-20 lg:grid-cols-2 lg:px-8">
                            <div>
                                <span className="rounded-full border border-red-400/30 bg-red-500/10 px-4 py-2 text-xs font-black tracking-[.16em] text-red-300 uppercase">
                                    Mobilidade sem complicação
                                </span>
                                <h1 className="mt-7 text-5xl leading-[.95] font-black tracking-[-.055em] sm:text-6xl lg:text-7xl">
                                    Escolha a categoria.{' '}
                                    <span className="text-red-500">
                                        A gente cuida do resto.
                                    </span>
                                </h1>
                                <p className="mt-7 max-w-xl text-lg leading-8 text-zinc-300">
                                    Consulte disponibilidade real, compare
                                    períodos e reserve seu grupo de veículo
                                    com segurança.
                                </p>
                            </div>

                            <div
                                id="reserva"
                                className="rounded-[2rem] bg-white p-6 text-zinc-950 shadow-2xl sm:p-8"
                            >
                                <p className="text-xs font-black tracking-[.18em] text-red-600 uppercase">
                                    Reserva online
                                </p>
                                <h2 className="mt-2 text-2xl font-black">
                                    Encontre a categoria ideal
                                </h2>

                                <div className="mt-6 grid gap-4">
                                    <label className="grid gap-2 text-xs font-bold">
                                        Filial
                                        <select
                                            value={form.branch_id}
                                            onChange={(event) =>
                                                update(
                                                    'branch_id',
                                                    event.target.value,
                                                )
                                            }
                                            className="h-14 rounded-2xl border border-zinc-200 bg-white px-4 text-sm"
                                        >
                                            <option value="">
                                                Todas as filiais
                                            </option>
                                            {branches.map((branch) => (
                                                <option
                                                    key={branch.id}
                                                    value={branch.id}
                                                >{branch.name}</option>
                                            ))}
                                        </select>
                                    </label>

                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <label className="grid gap-2 text-xs font-bold">
                                            Retirada
                                            <input
                                                type="datetime-local"
                                                value={form.starts_at}
                                                onChange={(event) =>
                                                    update(
                                                        'starts_at',
                                                        event.target.value,
                                                    )
                                                }
                                                className="h-14 rounded-2xl border border-zinc-200 px-4 text-sm"
                                            />
                                        </label>

                                        <label className="grid gap-2 text-xs font-bold">
                                            Devolução
                                            <input
                                                type="datetime-local"
                                                value={form.ends_at}
                                                onChange={(event) =>
                                                    update(
                                                        'ends_at',
                                                        event.target.value,
                                                    )
                                                }
                                                className="h-14 rounded-2xl border border-zinc-200 px-4 text-sm"
                                            />
                                        </label>
                                    </div>

                                    <label className="grid gap-2 text-xs font-bold">
                                        Categoria
                                        <select
                                            value={form.category_id}
                                            onChange={(event) =>
                                                update(
                                                    'category_id',
                                                    event.target.value,
                                                )
                                            }
                                            className="h-14 rounded-2xl border border-zinc-200 bg-white px-4 text-sm"
                                        >
                                            <option value="">
                                                Todas as categorias
                                            </option>
                                            {categories.map((category) => (
                                                <option
                                                    key={category.id}
                                                    value={category.id}
                                                >
                                                    {category.name}
                                                </option>
                                            ))}
                                        </select>
                                    </label>

                                    <button
                                        type="button"
                                        onClick={searchCategories}
                                        disabled={loading}
                                        className="flex h-14 items-center justify-center gap-2 rounded-2xl bg-red-600 text-sm font-black text-white hover:bg-red-700 disabled:opacity-60"
                                    >
                                        {loading
                                            ? 'Consultando...'
                                            : 'Pesquisar categorias'}
                                        {!loading && (
                                            <ArrowRight className="size-5" />
                                        )}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section id="vantagens" className="bg-white">
                        <div className="mx-auto grid max-w-7xl divide-y divide-zinc-200 px-5 md:grid-cols-3 md:divide-x md:divide-y-0 lg:px-8">
                            {[
                                [
                                    ShieldCheck,
                                    'Segurança',
                                    'Processo digital integrado.',
                                ],
                                [
                                    Gauge,
                                    'Agilidade',
                                    'Disponibilidade em tempo real.',
                                ],
                                [
                                    MapPin,
                                    'Proximidade',
                                    'Lojas e atendimento regional.',
                                ],
                            ].map(([Icon, title, text]) => {
                                const Component =
                                    Icon as typeof ShieldCheck;

                                return (
                                    <div
                                        key={String(title)}
                                        className="flex gap-4 px-3 py-8 md:px-8"
                                    >
                                        <span className="grid size-12 place-items-center rounded-2xl bg-red-50 text-red-600">
                                            <Component />
                                        </span>
                                        <div>
                                            <b>{String(title)}</b>
                                            <p className="mt-1 text-sm text-zinc-500">
                                                {String(text)}
                                            </p>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </section>

                    <section
                        id="frota"
                        className="mx-auto max-w-7xl px-5 py-20 lg:px-8"
                    >
                        <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                            <div>
                                <p className="text-xs font-black tracking-[.2em] text-red-600 uppercase">
                                    Catálogo por categoria
                                </p>
                                <h2 className="mt-3 text-4xl font-black tracking-[-.04em] sm:text-5xl">
                                    Um grupo para cada necessidade.
                                </h2>
                            </div>

                            <div className="relative w-full lg:max-w-sm">
                                <Search className="absolute top-1/2 left-4 size-5 -translate-y-1/2 text-zinc-400" />
                                <input
                                    value={form.search}
                                    onChange={(event) =>
                                        update(
                                            'search',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Buscar categoria ou modelo"
                                    className="h-14 w-full rounded-2xl border border-zinc-200 bg-white pr-4 pl-12 text-sm"
                                />
                            </div>
                        </div>

                        {message && (
                            <div className="mt-8 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-900">
                                {message}
                            </div>
                        )}

                        {!searched && (
                            <div className="mt-10 grid min-h-72 place-items-center rounded-[2rem] border border-dashed border-zinc-300 bg-white/60 text-center">
                                <div>
                                    <CarFront className="mx-auto size-16 text-zinc-400" />
                                    <h3 className="mt-4 text-xl font-black">
                                        Faça sua primeira pesquisa
                                    </h3>
                                    <p className="mt-2 text-sm text-zinc-500">
                                        As categorias disponíveis aparecerão
                                        aqui.
                                    </p>
                                </div>
                            </div>
                        )}

                        {searched && offers.length > 0 && (
                            <div className="mt-10 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                                {offers.map((offer) => {
                                    const image = categoryImage(offer);

                                    return (
                                        <article
                                            key={offer.id}
                                            className="overflow-hidden rounded-[2rem] border border-zinc-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl"
                                        >
                                            <div className="relative aspect-[16/10] bg-zinc-100">
                                                {image ? (
                                                    <img
                                                        src={image}
                                                        alt={
                                                            offer.public_title ??
                                                            offer.name
                                                        }
                                                        className="h-full w-full object-cover"
                                                    />
                                                ) : (
                                                    <div className="grid h-full place-items-center">
                                                        <CarFront className="size-20 text-zinc-300" />
                                                    </div>
                                                )}

                                                <span className="absolute top-4 right-4 rounded-full bg-white/95 px-3 py-1.5 text-xs font-black shadow">
                                                    {offer.available_count}{' '}
                                                    disponível(is)
                                                </span>
                                            </div>

                                            <div className="p-6">
                                                <p className="text-xs font-black tracking-[.14em] text-red-600 uppercase">
                                                    {offer.name}
                                                </p>
                                                <h3 className="mt-2 text-2xl font-black">
                                                    {offer.public_title ??
                                                        offer.name}
                                                </h3>
                                                <p className="mt-2 min-h-10 text-sm text-zinc-500">
                                                    {offer.similar_models
                                                        ? `${offer.similar_models} ou similar`
                                                        : offer.description ??
                                                          'Modelos equivalentes conforme disponibilidade.'}
                                                </p>

                                                <div className="mt-5 grid grid-cols-3 gap-2 text-xs font-bold">
                                                    <span className="rounded-xl bg-zinc-50 p-3">
                                                        <Users className="mb-2 size-4 text-red-600" />
                                                        {offer.specs?.seats ??
                                                            '—'}{' '}
                                                        lugares
                                                    </span>
                                                    <span className="rounded-xl bg-zinc-50 p-3">
                                                        <Gauge className="mb-2 size-4 text-red-600" />
                                                        {offer.specs
                                                            ?.transmissions?.[0] ??
                                                            'Câmbio'}
                                                    </span>
                                                    <span className="rounded-xl bg-zinc-50 p-3">
                                                        <Fuel className="mb-2 size-4 text-red-600" />
                                                        {offer.specs
                                                            ?.fuel_types?.[0] ??
                                                            'Combustível'}
                                                    </span>
                                                </div>

                                                <div className="mt-6 grid grid-cols-3 gap-2 border-t border-zinc-100 pt-5">
                                                    {[
                                                        [
                                                            'Diária',
                                                            offer.tariffs
                                                                .daily,
                                                        ],
                                                        [
                                                            '15 dias',
                                                            offer.tariffs
                                                                .fifteen_days,
                                                        ],
                                                        [
                                                            'Mensal',
                                                            offer.tariffs
                                                                .monthly,
                                                        ],
                                                    ].map(
                                                        ([label, tariff]) => (
                                                            <div
                                                                key={String(
                                                                    label,
                                                                )}
                                                                className="rounded-xl bg-zinc-50 p-3"
                                                            >
                                                                <small className="text-zinc-500">
                                                                    {String(
                                                                        label,
                                                                    )}
                                                                </small>
                                                                <b className="mt-1 block text-sm">
                                                                    {tariff
                                                                        ? money.format(
                                                                              (
                                                                                  tariff as Tariff
                                                                              )
                                                                                  .value,
                                                                          )
                                                                        : 'Consultar'}
                                                                </b>
                                                            </div>
                                                        ),
                                                    )}
                                                </div>

                                                <div className="mt-5 grid grid-cols-2 gap-3">
                                                    <a
                                                        href={`/categoria/${offer.id}?starts_at=${encodeURIComponent(form.starts_at)}&ends_at=${encodeURIComponent(form.ends_at)}&branch_id=${encodeURIComponent(form.branch_id)}`}
                                                        className="flex h-12 items-center justify-center rounded-xl border border-zinc-200 text-sm font-black"
                                                    >
                                                        Ver detalhes
                                                    </a>
                                                    <a
                                                        href={`/categoria/${offer.id}?starts_at=${encodeURIComponent(form.starts_at)}&ends_at=${encodeURIComponent(form.ends_at)}&branch_id=${encodeURIComponent(form.branch_id)}`}
                                                        className="flex h-12 items-center justify-center gap-2 rounded-xl bg-red-600 text-sm font-black text-white"
                                                    >
                                                        Reservar
                                                        <ArrowRight className="size-4" />
                                                    </a>
                                                </div>
                                            </div>
                                        </article>
                                    );
                                })}
                            </div>
                        )}
                    </section>

                    <section
                        id="lojas"
                        className="bg-[#111214] text-white"
                    >
                        <div className="mx-auto grid max-w-7xl gap-10 px-5 py-16 lg:grid-cols-2 lg:px-8">
                            <div>
                                <p className="text-xs font-black tracking-[.2em] text-red-400 uppercase">
                                    Presença regional
                                </p>
                                <h2 className="mt-4 text-4xl font-black">
                                    Perto de você.
                                </h2>
                                <a
                                    href="https://wa.me/5562982887249"
                                    target="_blank"
                                    rel="noreferrer"
                                    className="mt-6 inline-flex items-center gap-2 rounded-full bg-green-600 px-5 py-3 text-sm font-black"
                                >
                                    <MessageCircle className="size-5" />
                                    WhatsApp (62) 98288-7249
                                </a>
                            </div>

                            <div className="grid gap-3">
                                {branches.slice(0, 4).map((branch) => (
                                    <div
                                        key={branch.id}
                                        className="flex items-center gap-4 rounded-2xl border border-white/10 bg-white/5 p-5"
                                    >
                                        <MapPin className="text-red-500" />
                                        <div>
                                            <b>{branch.name}</b>
                                            <p className="text-sm text-zinc-400">
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </section>
                </main>

                <footer className="bg-[#08090b] text-zinc-400">
                    <div className="mx-auto flex max-w-7xl flex-col gap-4 px-5 py-8 text-sm md:flex-row md:justify-between lg:px-8">
                        <span>
                            © {new Date().getFullYear()} Careca Locadora
                            de Veículos
                        </span>
                        <span>
                            Reserva por categoria integrada ao ERP
                        </span>
                    </div>
                </footer>
            </div>
        </>
    );
}
