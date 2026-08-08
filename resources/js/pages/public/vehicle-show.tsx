import { Head } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowRight,
    CalendarDays,
    CarFront,
    Check,
    ChevronLeft,
    ChevronRight,
    Fuel,
    Gauge,
    MapPin,
    MessageCircle,
    ShieldCheck,
    Snowflake,
    Briefcase,
    Users,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

type Props = { assetId: string };

type Vehicle = {
    id: string;
    prefix: string;
    name: string;
    brand?: string | null;
    model?: string | null;
    model_year?: number | null;
    seats?: number | null;
    doors?: number | null;
    transmission?: string | null;
    fuel_type?: string | null;
    color?: string | null;
    air_conditioning?: boolean;
    power_steering?: boolean;
    luggage_capacity?: number | null;
    category?: { id?: string | null; name?: string | null };
    branch?: {
        id?: string | null;
        name?: string | null;
        city?: string | null;
        state?: string | null;
    };
    photos?: {
    path: string | null;
    featured: boolean;
}[];
};

type Quote = {
    total_value: number;
    deposit_value: number;
    rate_plan?: { name?: string; unit_value?: number; billing_unit?: string };
    commercial_items?: {
        id: string;
        name: string;
        type: string;
        total: number;
        required: boolean;
    }[];
};

const money = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

const onlyDigits = (value: string): string => value.replace(/\D/g, '');

const formatCpfCnpj = (value: string): string => {
    const digits = onlyDigits(value).slice(0, 14);

    if (digits.length <= 11) {
        return digits
            .replace(/^(\d{3})(\d)/, '$1.$2')
            .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
            .replace(/\.(\d{3})(\d)/, '.$1-$2');
    }

    return digits
        .replace(/^(\d{2})(\d)/, '$1.$2')
        .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/\.(\d{3})(\d)/, '.$1/$2')
        .replace(/(\d{4})(\d)/, '$1-$2');
};

const formatPhone = (value: string): string => {
    const digits = onlyDigits(value).slice(0, 11);

    if (digits.length <= 10) {
        return digits
            .replace(/^(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{4})(\d)/, '$1-$2');
    }

    return digits
        .replace(/^(\d{2})(\d)/, '($1) $2')
        .replace(/(\d{5})(\d)/, '$1-$2');
};

const formatCustomerField = (field: string, value: string): string => {
    if (field === 'document') return formatCpfCnpj(value);
    if (field === 'phone') return formatPhone(value);

    return value;
};

const storageUrl = (path?: string | null): string | null => {
    if (!path) {
        return null;
    }

    return path.startsWith('http')
        ? path
        : `/storage/${path.replace(/^public\//, '')}`;
};

export default function VehicleShow({ assetId }: Props) {
    const params = useMemo(
        () => new URLSearchParams(window.location.search),
        [],
    );

    const [vehicle, setVehicle] = useState<Vehicle | null>(null);
    const [quote, setQuote] = useState<Quote | null>(null);
    const [activePhoto, setActivePhoto] = useState(0);
    const [step, setStep] = useState(1);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<{
        number: string;
        total_value: number;
    } | null>(null);

    const [form, setForm] = useState({
        starts_at: params.get('starts_at') ?? '',
        ends_at: params.get('ends_at') ?? '',
        branch_id: params.get('branch_id') ?? '',
        category_id: params.get('category_id') ?? '',
        commercial_item_ids: [] as string[],
        coupon_code: '',
        customer: {
            name: '',
            document: '',
            email: '',
            phone: '',
        },
        accept_terms: false,
    });

    useEffect(() => {
        fetch(`/api/public/vehicles/${assetId}`)
            .then(async (response) => {
                const payload = await response.json();
                if (!response.ok) throw new Error(payload.message);
                setVehicle(payload.data);
                setForm((current) => ({
                    ...current,
                    branch_id:
                        current.branch_id || payload.data.branch?.id || '',
                    category_id:
                        current.category_id || payload.data.category?.id || '',
                }));
            })
            .catch((reason) =>
                setError(reason.message ?? 'Não foi possível carregar o veículo.'),
            )
            .finally(() => setLoading(false));
    }, [assetId]);

    useEffect(() => {
        if (
            !vehicle ||
            !form.starts_at ||
            !form.ends_at ||
            !form.category_id
        ) {
            return;
        }

        fetch('/api/public/quote', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                branch_id: form.branch_id || null,
                category_id: form.category_id,
                starts_at: form.starts_at,
                ends_at: form.ends_at,
                commercial_item_ids: form.commercial_item_ids,
                coupon_code: form.coupon_code || null,
            }),
        })
            .then(async (response) => {
                const payload = await response.json();
                if (!response.ok) throw new Error(payload.message);
                setQuote(payload.data);
            })
            .catch((reason) =>
                setError(reason.message ?? 'Não foi possível calcular o valor.'),
            );
    }, [
        vehicle,
        form.starts_at,
        form.ends_at,
        form.branch_id,
        form.category_id,
        form.commercial_item_ids,
        form.coupon_code,
    ]);

    const toggleItem = (id: string) => {
        setForm((current) => ({
            ...current,
            commercial_item_ids: current.commercial_item_ids.includes(id)
                ? current.commercial_item_ids.filter((item) => item !== id)
                : [...current.commercial_item_ids, id],
        }));
    };

    const submit = async () => {
        if (!vehicle) return;

        setSubmitting(true);
        setError(null);

        try {
            const response = await fetch('/api/public/reservations', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    asset_id: vehicle.id,
                    branch_id: form.branch_id || null,
                    category_id: form.category_id,
                    starts_at: form.starts_at,
                    ends_at: form.ends_at,
                    commercial_item_ids: form.commercial_item_ids,
                    coupon_code: form.coupon_code || null,
                    customer: form.customer,
                    accept_terms: form.accept_terms,
                }),
            });

            const payload = await response.json();

            if (!response.ok) {
                const firstError = Object.values(payload.errors ?? {})
                    .flat()
                    .at(0);
                throw new Error(
                    String(firstError ?? payload.message ?? 'Falha na reserva.'),
                );
            }

            setSuccess(payload.data);
            setStep(5);
        } catch (reason) {
            setError(
                reason instanceof Error
                    ? reason.message
                    : 'Não foi possível concluir a reserva.',
            );
        } finally {
            setSubmitting(false);
        }
    };

    const whatsappText = encodeURIComponent(
        `Olá! Vim pelo site da Careca Locadora e quero informações sobre o veículo ${vehicle?.name ?? ''}.`,
    );

    if (loading) {
        return <div className="grid min-h-screen place-items-center">Carregando...</div>;
    }

    if (!vehicle) {
        return <div className="grid min-h-screen place-items-center">{error}</div>;
    }

    const photos = (vehicle.photos ?? []).filter(
    (photo) => typeof photo?.path === 'string' && photo.path.trim() !== '',
);

    return (
        <>
            <Head title={`${vehicle.name} | Careca Locadora`} />

            <div className="min-h-screen bg-[#f5f3ee] text-zinc-950">
                <header className="border-b border-white/10 bg-[#090a0c] text-white">
                    <div className="mx-auto flex h-20 max-w-7xl items-center justify-between px-5 lg:px-8">
                        <a href="/" className="flex items-center gap-3 font-black">
                            <span className="grid size-11 place-items-center rounded-2xl bg-red-600">
                                <CarFront />
                            </span>
                            Careca Locadora
                        </a>
                        <a
                            href={`https://wa.me/5562982887249?text=${whatsappText}`}
                            target="_blank"
                            rel="noreferrer"
                            className="flex items-center gap-2 rounded-full bg-green-600 px-5 py-3 text-sm font-black"
                        >
                            <MessageCircle className="size-4" />
                            WhatsApp
                        </a>
                    </div>
                </header>

                <main className="mx-auto max-w-7xl px-5 py-10 lg:px-8">
                    <a href="/" className="mb-7 inline-flex items-center gap-2 text-sm font-bold">
                        <ArrowLeft className="size-4" />
                        Voltar ao catálogo
                    </a>

                    <div className="grid gap-8 lg:grid-cols-[1.2fr_.8fr]">
                        <section>
                            <div className="relative aspect-[16/10] overflow-hidden rounded-[2rem] bg-zinc-200">
                                {photos.length > 0 ? (
                                    <img
                                        src={storageUrl(photos[activePhoto].path) ?? undefined}
                                        alt={vehicle.name}
                                        className="h-full w-full object-cover"
                                    />
                                ) : (
                                    <div className="grid h-full place-items-center">
                                        <CarFront className="size-28 text-zinc-400" />
                                    </div>
                                )}

                                {photos.length > 1 && (
                                    <>
                                        <button
                                            onClick={() =>
                                                setActivePhoto((current) =>
                                                    current === 0
                                                        ? photos.length - 1
                                                        : current - 1,
                                                )
                                            }
                                            className="absolute top-1/2 left-4 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-black/65 text-white"
                                        >
                                            <ChevronLeft />
                                        </button>
                                        <button
                                            onClick={() =>
                                                setActivePhoto((current) =>
                                                    (current + 1) % photos.length,
                                                )
                                            }
                                            className="absolute top-1/2 right-4 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-black/65 text-white"
                                        >
                                            <ChevronRight />
                                        </button>
                                    </>
                                )}
                            </div>

                            <div className="mt-4 flex gap-3 overflow-x-auto pb-2">
                                {photos.map((photo, index) => (
                                    <button
                                        key={`${photo.path}-${index}`}
                                        onClick={() => setActivePhoto(index)}
                                        className={`h-20 w-28 shrink-0 overflow-hidden rounded-xl border-2 ${
                                            activePhoto === index
                                                ? 'border-red-600'
                                                : 'border-transparent'
                                        }`}
                                    >
                                        <img
                                            src={storageUrl(photo.path) ?? undefined}
                                            alt=""
                                            className="h-full w-full object-cover"
                                        />
                                    </button>
                                ))}
                            </div>

                            <div className="mt-8 rounded-[2rem] bg-white p-7">
                                <p className="text-xs font-black tracking-[.18em] text-red-600 uppercase">
                                    {vehicle.category?.name}
                                </p>
                                <h1 className="mt-2 text-4xl font-black tracking-[-.04em]">
                                    {vehicle.name}
                                </h1>
                                <p className="mt-2 text-zinc-500">
                                    {vehicle.branch?.name} · {vehicle.branch?.city}/{vehicle.branch?.state}
                                </p>

                                <div className="mt-7 grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
                                    {[
                                        [Users, `${vehicle.seats ?? '—'} lugares`],
                                        [CarFront, `${vehicle.doors ?? '—'} portas`],
                                        [
                                            Snowflake,
                                            vehicle.air_conditioning
                                                ? 'Ar-condicionado'
                                                : 'Sem ar-condicionado',
                                        ],
                                        [Gauge, vehicle.transmission ?? 'Câmbio'],
                                        [Fuel, vehicle.fuel_type ?? 'Combustível'],
                                        [
                                            Briefcase,
                                            vehicle.luggage_capacity
                                                ? `${vehicle.luggage_capacity} malas`
                                                : 'Porta-malas',
                                        ],
                                    ].map(([Icon, label], index) => {
                                        const IconComponent = Icon as typeof Users;
                                        return (
                                            <div key={index} className="rounded-2xl bg-zinc-50 p-4">
                                                <IconComponent className="size-5 text-red-600" />
                                                <p className="mt-3 text-sm font-black">{String(label)}</p>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        </section>

                        <aside className="h-fit rounded-[2rem] bg-white p-6 shadow-xl lg:sticky lg:top-6">
                            <div className="mb-6 flex items-center justify-between">
                                {[1, 2, 3, 4].map((item) => (
                                    <div key={item} className="flex items-center">
                                        <span
                                            className={`grid size-8 place-items-center rounded-full text-xs font-black ${
                                                step >= item
                                                    ? 'bg-red-600 text-white'
                                                    : 'bg-zinc-100 text-zinc-500'
                                            }`}
                                        >
                                            {item}
                                        </span>
                                        {item < 4 && (
                                            <span className={`h-1 w-8 ${step > item ? 'bg-red-600' : 'bg-zinc-100'}`} />
                                        )}
                                    </div>
                                ))}
                            </div>

                            {error && (
                                <div className="mb-5 rounded-xl bg-red-50 p-4 text-sm font-bold text-red-700">
                                    {error}
                                </div>
                            )}

                            {step === 1 && (
                                <div>
                                    <h2 className="text-2xl font-black">Período da locação</h2>
                                    <div className="mt-5 grid gap-4">
                                        <label className="grid gap-2 text-sm font-bold">
                                            Retirada
                                            <input
                                                type="datetime-local"
                                                value={form.starts_at}
                                                onChange={(event) =>
                                                    setForm((current) => ({
                                                        ...current,
                                                        starts_at: event.target.value,
                                                    }))
                                                }
                                                className="h-13 rounded-xl border border-zinc-200 px-4"
                                            />
                                        </label>
                                        <label className="grid gap-2 text-sm font-bold">
                                            Devolução
                                            <input
                                                type="datetime-local"
                                                value={form.ends_at}
                                                onChange={(event) =>
                                                    setForm((current) => ({
                                                        ...current,
                                                        ends_at: event.target.value,
                                                    }))
                                                }
                                                className="h-13 rounded-xl border border-zinc-200 px-4"
                                            />
                                        </label>
                                    </div>
                                </div>
                            )}

                            {step === 2 && (
                                <div>
                                    <h2 className="text-2xl font-black">Proteções e opcionais</h2>
                                    <div className="mt-5 grid gap-3">
                                        {(quote?.commercial_items ?? []).map((item) => (
                                            <label
                                                key={item.id}
                                                className="flex cursor-pointer items-center justify-between rounded-xl border border-zinc-200 p-4"
                                            >
                                                <span>
                                                    <strong className="block">{item.name}</strong>
                                                    <small className="text-zinc-500">
                                                        {money.format(item.total)}
                                                    </small>
                                                </span>
                                                <input
                                                    type="checkbox"
                                                    checked={
                                                        item.required ||
                                                        form.commercial_item_ids.includes(item.id)
                                                    }
                                                    disabled={item.required}
                                                    onChange={() => toggleItem(item.id)}
                                                    className="size-5 accent-red-600"
                                                />
                                            </label>
                                        ))}
                                        <input
                                            placeholder="Cupom promocional"
                                            value={form.coupon_code}
                                            onChange={(event) =>
                                                setForm((current) => ({
                                                    ...current,
                                                    coupon_code: event.target.value.toUpperCase(),
                                                }))
                                            }
                                            className="h-13 rounded-xl border border-zinc-200 px-4"
                                        />
                                    </div>
                                </div>
                            )}

                            {step === 3 && (
                                <div>
                                    <h2 className="text-2xl font-black">Seus dados</h2>
                                    <div className="mt-5 grid gap-3">
                                        {[
                                            ['name', 'Nome completo'],
                                            ['document', 'CPF ou CNPJ'],
                                            ['email', 'E-mail'],
                                            ['phone', 'Telefone/WhatsApp'],
                                        ].map(([field, label]) => (
                                            <input
                                                key={field}
                                                placeholder={label}
                                                inputMode={
                                                    field === 'document' || field === 'phone'
                                                        ? 'numeric'
                                                        : field === 'email'
                                                          ? 'email'
                                                          : 'text'
                                                }
                                                maxLength={
                                                    field === 'document'
                                                        ? 18
                                                        : field === 'phone'
                                                          ? 15
                                                          : undefined
                                                }
                                                autoComplete={
                                                    field === 'name'
                                                        ? 'name'
                                                        : field === 'email'
                                                          ? 'email'
                                                          : field === 'phone'
                                                            ? 'tel'
                                                            : 'off'
                                                }
                                                value={
                                                    form.customer[
                                                        field as keyof typeof form.customer
                                                    ]
                                                }
                                                onChange={(event) =>
                                                    setForm((current) => ({
                                                        ...current,
                                                        customer: {
                                                            ...current.customer,
                                                            [field]: formatCustomerField(field, event.target.value),
                                                        },
                                                    }))
                                                }
                                                className="h-13 rounded-xl border border-zinc-200 px-4"
                                            />
                                        ))}
                                    </div>
                                </div>
                            )}

                            {step === 4 && (
                                <div>
                                    <h2 className="text-2xl font-black">Revise sua reserva</h2>
                                    <div className="mt-5 rounded-2xl bg-zinc-50 p-5">
                                        <p className="font-black">{vehicle.name}</p>
                                        <p className="mt-1 text-sm text-zinc-500">
                                            {form.starts_at} até {form.ends_at}
                                        </p>
                                        <div className="mt-5 border-t border-zinc-200 pt-5">
                                            <p className="text-sm text-zinc-500">Total estimado</p>
                                            <p className="text-3xl font-black">
                                                {money.format(quote?.total_value ?? 0)}
                                            </p>
                                            <p className="mt-1 text-xs text-zinc-500">
                                                Caução: {money.format(quote?.deposit_value ?? 0)}
                                            </p>
                                        </div>
                                    </div>
                                    <label className="mt-5 flex items-start gap-3 text-sm">
                                        <input
                                            type="checkbox"
                                            checked={form.accept_terms}
                                            onChange={(event) =>
                                                setForm((current) => ({
                                                    ...current,
                                                    accept_terms: event.target.checked,
                                                }))
                                            }
                                            className="mt-1 size-5 accent-red-600"
                                        />
                                        Concordo com os termos da reserva e com o tratamento dos dados informados.
                                    </label>
                                </div>
                            )}

                            {step === 5 && success && (
                                <div className="text-center">
                                    <span className="mx-auto grid size-16 place-items-center rounded-full bg-green-100 text-green-700">
                                        <Check className="size-8" />
                                    </span>
                                    <h2 className="mt-5 text-2xl font-black">Reserva recebida!</h2>
                                    <p className="mt-2 text-zinc-500">
                                        Número {success.number}
                                    </p>
                                    <p className="mt-5 text-3xl font-black">
                                        {money.format(success.total_value)}
                                    </p>
                                    <a
                                        href={`https://wa.me/5562982887249?text=${encodeURIComponent(
                                            `Olá! Minha reserva é ${success.number}. Gostaria de confirmar os próximos passos.`,
                                        )}`}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="mt-6 flex h-13 items-center justify-center gap-2 rounded-xl bg-green-600 font-black text-white"
                                    >
                                        <MessageCircle className="size-5" />
                                        Confirmar pelo WhatsApp
                                    </a>
                                </div>
                            )}

                            {step < 5 && (
                                <>
                                    {quote && (
                                        <div className="mt-6 flex items-end justify-between border-t border-zinc-100 pt-5">
                                            <div>
                                                <p className="text-xs text-zinc-500">Total estimado</p>
                                                <p className="text-2xl font-black">
                                                    {money.format(quote.total_value)}
                                                </p>
                                            </div>
                                            <span className="flex items-center gap-1 text-xs font-bold text-green-700">
                                                <ShieldCheck className="size-4" />
                                                Cotação segura
                                            </span>
                                        </div>
                                    )}

                                    <div className="mt-6 flex gap-3">
                                        {step > 1 && (
                                            <button
                                                onClick={() => setStep((current) => current - 1)}
                                                className="h-13 flex-1 rounded-xl border border-zinc-200 font-black"
                                            >
                                                Voltar
                                            </button>
                                        )}
                                        <button
                                            onClick={() =>
                                                step === 4
                                                    ? submit()
                                                    : setStep((current) => current + 1)
                                            }
                                            disabled={
                                                submitting ||
                                                (step === 1 &&
                                                    (!form.starts_at || !form.ends_at)) ||
                                                (step === 3 &&
                                                    Object.values(form.customer).some(
                                                        (value) => !value,
                                                    )) ||
                                                (step === 4 && !form.accept_terms)
                                            }
                                            className="flex h-13 flex-1 items-center justify-center gap-2 rounded-xl bg-red-600 font-black text-white disabled:opacity-50"
                                        >
                                            {submitting
                                                ? 'Enviando...'
                                                : step === 4
                                                  ? 'Confirmar reserva'
                                                  : 'Continuar'}
                                            <ArrowRight className="size-4" />
                                        </button>
                                    </div>
                                </>
                            )}
                        </aside>
                    </div>
                </main>

                <a
                    href={`https://wa.me/5562982887249?text=${whatsappText}`}
                    target="_blank"
                    rel="noreferrer"
                    className="fixed right-5 bottom-5 grid size-14 place-items-center rounded-full bg-green-600 text-white shadow-xl"
                    aria-label="Falar no WhatsApp"
                >
                    <MessageCircle />
                </a>
            </div>
        </>
    );
}
