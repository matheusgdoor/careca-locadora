import { Head } from '@inertiajs/react';
import {
    ArrowLeft,
    Briefcase,
    CarFront,
    Fuel,
    Gauge,
    Snowflake,
    Users,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

type Props = {
    categoryId: string;
};

type VehiclePhoto = {
    path: string;
    url?: string | null;
    featured?: boolean;
};

type Vehicle = {
    id: string;
    prefix?: string | null;
    name: string;
    plate?: string | null;
    seats?: number | null;
    doors?: number | null;
    transmission?: string | null;
    fuel_type?: string | null;
    model_year?: number | null;
    air_conditioning?: boolean;
    power_steering?: boolean;
    luggage_capacity?: number | null;
    category?: {
        id?: string | null;
        name?: string | null;
    };
    branch?: {
        id?: string | null;
        name?: string | null;
        city?: string | null;
        state?: string | null;
    };
    photos?: VehiclePhoto[];
};

type Feature = {
    icon: LucideIcon;
    label: string;
};

type QuoteSummary = {
    total_value?: number;
    total?: number;
    deposit_value?: number;
};

const storageUrl = (path?: string | null): string | null => {
    if (!path) return null;

    return path.startsWith('http')
        ? path
        : `/storage/${path.replace(/^public\//, '')}`;
};

const formatDateTime = (value?: string): string => {
    if (!value) return 'Não informado';

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    })
        .format(date)
        .replace(',', ' às');
};

const rentalDurationLabel = (
    startsAt?: string,
    endsAt?: string,
): string | null => {
    if (!startsAt || !endsAt) return null;

    const start = new Date(startsAt);
    const end = new Date(endsAt);

    if (
        Number.isNaN(start.getTime()) ||
        Number.isNaN(end.getTime()) ||
        end <= start
    ) {
        return null;
    }

    const hours = Math.ceil(
        (end.getTime() - start.getTime()) / (1000 * 60 * 60),
    );

    const days = Math.floor(hours / 24);
    const remainingHours = hours % 24;

    if (days > 0 && remainingHours > 0) {
        return `${days} ${days === 1 ? 'dia' : 'dias'} e ${remainingHours} ${
            remainingHours === 1 ? 'hora' : 'horas'
        }`;
    }

    if (days > 0) {
        return `${days} ${days === 1 ? 'dia' : 'dias'}`;
    }

    return `${hours} ${hours === 1 ? 'hora' : 'horas'}`;
};

export default function CategoryVehicles({ categoryId }: Props) {
    const params = useMemo(
        () => new URLSearchParams(window.location.search),
        [],
    );

    const startsAt = params.get('starts_at') ?? '';
    const endsAt = params.get('ends_at') ?? '';
    const branchId = params.get('branch_id') ?? '';

    const [vehicles, setVehicles] = useState<Vehicle[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [quote, setQuote] = useState<QuoteSummary | null>(null);
    const [sortBy, setSortBy] = useState<'newest' | 'equipped' | 'name'>('newest');
    const [filters, setFilters] = useState({
        automatic: false,
        airConditioning: false,
        fiveSeats: false,
        fourDoors: false,
    });

    useEffect(() => {
        setLoading(true);
        setError(null);

        fetch('/api/public/category-vehicles', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                branch_id: branchId || null,
                category_id: categoryId,
                starts_at: startsAt,
                ends_at: endsAt,
            }),
        })
            .then(async (response) => {
                const payload = await response.json();

                if (!response.ok) {
                    throw new Error(
                        payload.message ??
                            'Não foi possível consultar a disponibilidade.',
                    );
                }

                setVehicles(payload.data ?? []);
            })
            .catch((reason: unknown) => {
                setError(
                    reason instanceof Error
                        ? reason.message
                        : 'Não foi possível consultar os veículos.',
                );
            })
            .finally(() => setLoading(false));
    }, [categoryId, branchId, startsAt, endsAt]);

    useEffect(() => {
        if (!startsAt || !endsAt) {
            return;
        }

        fetch('/api/public/quote', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                branch_id: branchId || null,
                category_id: categoryId,
                starts_at: startsAt,
                ends_at: endsAt,
                commercial_item_ids: [],
                coupon_code: null,
            }),
        })
            .then(async (response) => {
                const payload = await response.json();

                if (response.ok) {
                    setQuote(payload.data ?? null);
                }
            })
            .catch(() => {
                setQuote(null);
            });
    }, [branchId, categoryId, startsAt, endsAt]);

    const categoryName =
        vehicles.at(0)?.category?.name ?? 'Veículos disponíveis';

    const filteredVehicles = useMemo(() => {
        return vehicles.filter((vehicle) => {
            const transmission = (vehicle.transmission ?? '').toLowerCase();

            if (
                filters.automatic &&
                !transmission.includes('autom')
            ) {
                return false;
            }

            if (
                filters.airConditioning &&
                !vehicle.air_conditioning
            ) {
                return false;
            }

            if (
                filters.fiveSeats &&
                (vehicle.seats ?? 0) < 5
            ) {
                return false;
            }

            if (
                filters.fourDoors &&
                (vehicle.doors ?? 0) < 4
            ) {
                return false;
            }

            return true;
        });
    }, [filters, vehicles]);

    const sortedVehicles = useMemo(() => {
        return [...filteredVehicles].sort((a, b) => {
            if (sortBy === 'name') {
                return a.name.localeCompare(b.name, 'pt-BR');
            }

            if (sortBy === 'equipped') {
                const score = (vehicle: Vehicle) =>
                    Number(Boolean(vehicle.air_conditioning)) +
                    Number(Boolean(vehicle.power_steering)) +
                    Number((vehicle.luggage_capacity ?? 0) > 0) +
                    Number((vehicle.doors ?? 0) >= 4);

                return score(b) - score(a);
            }

            return (b.model_year ?? 0) - (a.model_year ?? 0);
        });
    }, [filteredVehicles, sortBy]);

    const newestYear = Math.max(
        ...vehicles.map((vehicle) => vehicle.model_year ?? 0),
        0,
    );

    const equipmentScore = (vehicle: Vehicle): number =>
        Number(Boolean(vehicle.air_conditioning)) +
        Number(Boolean(vehicle.power_steering)) +
        Number((vehicle.luggage_capacity ?? 0) > 0) +
        Number((vehicle.doors ?? 0) >= 4);

    const maxEquipmentScore = Math.max(
        ...vehicles.map((vehicle) => equipmentScore(vehicle)),
        0,
    );

    const recommendedVehicleId =
        [...vehicles]
            .sort((a, b) => {
                const equipmentDifference =
                    equipmentScore(b) - equipmentScore(a);

                if (equipmentDifference !== 0) {
                    return equipmentDifference;
                }

                return (b.model_year ?? 0) - (a.model_year ?? 0);
            })
            .at(0)?.id ?? null;

    const estimatedTotal =
        quote?.total_value ?? quote?.total ?? null;

    const rentalDuration = rentalDurationLabel(startsAt, endsAt);

    const money = new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    });

    const toggleFilter = (
        key: keyof typeof filters,
    ): void => {
        setFilters((current) => ({
            ...current,
            [key]: !current[key],
        }));
    };

    const clearFilters = (): void => {
        setFilters({
            automatic: false,
            airConditioning: false,
            fiveSeats: false,
            fourDoors: false,
        });
    };

    const hasActiveFilters = Object.values(filters).some(Boolean);

    const vehicleUrl = (vehicle: Vehicle): string => {
        const query = new URLSearchParams();

        if (startsAt) query.set('starts_at', startsAt);
        if (endsAt) query.set('ends_at', endsAt);
        if (branchId) query.set('branch_id', branchId);

        query.set('category_id', categoryId);

        return `/veiculos/${vehicle.id}?${query.toString()}`;
    };

    return (
        <>
            <Head title={`${categoryName} | Careca Locadora`} />

            <div className="min-h-screen bg-[#f5f3ee] text-zinc-950">
                <header className="border-b border-white/10 bg-[#090a0c] text-white">
                    <div className="mx-auto flex h-20 max-w-7xl items-center justify-between px-5 lg:px-8">
                        <a href="/" className="flex items-center gap-3 font-black">
                            <span className="grid size-11 place-items-center rounded-2xl bg-red-600">
                                <CarFront />
                            </span>
                            Careca Locadora
                        </a>

                        <span className="text-sm font-semibold text-zinc-300">
                            Escolha seu veículo
                        </span>
                    </div>
                </header>

                <main className="mx-auto max-w-7xl px-5 py-10 lg:px-8 lg:py-14">
                    <a
                        href="/"
                        className="inline-flex items-center gap-2 text-sm font-bold text-zinc-700 transition hover:text-red-600"
                    >
                        <ArrowLeft className="size-4" />
                        Voltar ao catálogo
                    </a>

                    <div className="mt-8 max-w-3xl">
                        <p className="text-xs font-black tracking-[0.22em] text-red-600 uppercase">
                            Veículos realmente disponíveis
                        </p>

                        <h1 className="mt-3 text-4xl font-black tracking-tight md:text-5xl">
                            {categoryName}
                        </h1>

                        <p className="mt-4 text-base leading-7 text-zinc-600">
                            Escolha o veículo que deseja reservar. A disponibilidade
                            considera o período informado e será revalidada na
                            confirmação.
                        </p>
                    </div>

                    {loading && (
                        <div className="mt-10 rounded-3xl border border-zinc-200 bg-white p-10 text-center font-bold text-zinc-500">
                            Consultando veículos disponíveis...
                        </div>
                    )}

                    {error && (
                        <div className="mt-10 rounded-3xl border border-red-200 bg-red-50 p-6 font-bold text-red-700">
                            {error}
                        </div>
                    )}

                    {!loading && !error && vehicles.length === 0 && (
                        <div className="mt-10 rounded-3xl border border-zinc-200 bg-white p-10 text-center">
                            <CarFront className="mx-auto size-12 text-zinc-300" />
                            <h2 className="mt-4 text-xl font-black">
                                Nenhum veículo disponível neste período
                            </h2>
                            <p className="mt-2 text-zinc-500">
                                Volte ao catálogo e altere as datas da locação.
                            </p>
                        </div>
                    )}

                    {!loading && !error && vehicles.length > 0 && (
                        <>
                            <div className="mt-8 grid gap-4 rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm md:grid-cols-[1fr_auto] md:items-center">
                                <div>
                                    <p className="text-xs font-black tracking-[0.15em] text-zinc-400 uppercase">
                                        Período selecionado
                                    </p>
                                    <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                        <div className="rounded-2xl bg-zinc-50 px-4 py-3">
                                            <p className="text-[11px] font-black tracking-[0.12em] text-zinc-400 uppercase">
                                                Retirada
                                            </p>
                                            <p className="mt-1 font-black text-zinc-900">
                                                {formatDateTime(startsAt)}
                                            </p>
                                        </div>

                                        <div className="rounded-2xl bg-zinc-50 px-4 py-3">
                                            <p className="text-[11px] font-black tracking-[0.12em] text-zinc-400 uppercase">
                                                Devolução
                                            </p>
                                            <p className="mt-1 font-black text-zinc-900">
                                                {formatDateTime(endsAt)}
                                            </p>
                                        </div>
                                    </div>

                                    {rentalDuration && (
                                        <p className="mt-3 text-sm font-bold text-zinc-500">
                                            Duração estimada:{' '}
                                            <span className="font-black text-zinc-800">
                                                {rentalDuration}
                                            </span>
                                        </p>
                                    )}

                                    {vehicles.at(0)?.branch?.name && (
                                        <p className="mt-2 text-sm font-bold text-zinc-500">
                                            Filial de retirada:{' '}
                                            <span className="font-black text-zinc-800">
                                                {vehicles.at(0)?.branch?.name}
                                            </span>
                                        </p>
                                    )}

                                    {estimatedTotal !== null && (
                                        <p className="mt-2 text-sm font-bold text-zinc-500">
                                            Estimativa da categoria:{' '}
                                            <span className="text-lg font-black text-zinc-950">
                                                {money.format(estimatedTotal)}
                                            </span>
                                        </p>
                                    )}
                                </div>

                                <label className="flex items-center gap-3 text-sm font-bold text-zinc-600">
                                    Ordenar por
                                    <select
                                        value={sortBy}
                                        onChange={(event) =>
                                            setSortBy(
                                                event.target.value as
                                                    | 'newest'
                                                    | 'name',
                                            )
                                        }
                                        className="h-11 rounded-xl border border-zinc-200 bg-white px-4 font-bold outline-none focus:border-red-500"
                                    >
                                        <option value="newest">Mais novos</option>
                                        <option value="equipped">Mais equipados</option>
                                        <option value="name">Nome</option>
                                    </select>
                                </label>
                            </div>

                            <div className="mt-5 rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm">
                                <div className="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p className="text-xs font-black tracking-[0.14em] text-zinc-400 uppercase">
                                            Filtrar veículos
                                        </p>
                                        <p className="mt-1 text-sm font-semibold text-zinc-500">
                                            Refine a lista de acordo com sua preferência.
                                        </p>
                                    </div>

                                    {hasActiveFilters && (
                                        <button
                                            type="button"
                                            onClick={clearFilters}
                                            className="text-sm font-black text-red-600 transition hover:text-red-700"
                                        >
                                            Limpar filtros
                                        </button>
                                    )}
                                </div>

                                <div className="mt-4 flex flex-wrap gap-2">
                                    {[
                                        [
                                            'automatic',
                                            'Automático',
                                            filters.automatic,
                                        ],
                                        [
                                            'airConditioning',
                                            'Ar-condicionado',
                                            filters.airConditioning,
                                        ],
                                        [
                                            'fiveSeats',
                                            '5+ lugares',
                                            filters.fiveSeats,
                                        ],
                                        [
                                            'fourDoors',
                                            '4+ portas',
                                            filters.fourDoors,
                                        ],
                                    ].map(([key, label, active]) => (
                                        <button
                                            key={String(key)}
                                            type="button"
                                            onClick={() =>
                                                toggleFilter(
                                                    key as keyof typeof filters,
                                                )
                                            }
                                            className={`rounded-full border px-4 py-2 text-sm font-black transition ${
                                                active
                                                    ? 'border-red-600 bg-red-600 text-white'
                                                    : 'border-zinc-200 bg-white text-zinc-700 hover:border-red-300 hover:text-red-600'
                                            }`}
                                        >
                                            {String(label)}
                                        </button>
                                    ))}
                                </div>
                            </div>

                            <div className="mt-5 flex flex-wrap items-center justify-between gap-4">
                                <p className="font-bold text-zinc-600">
                                    {sortedVehicles.length}{' '}
                                    {sortedVehicles.length === 1
                                        ? 'veículo disponível'
                                        : 'veículos disponíveis'}
                                </p>

                                <span className="rounded-full bg-emerald-50 px-4 py-2 text-xs font-black text-emerald-700">
                                    Disponibilidade em tempo real
                                </span>
                            </div>

                            {sortedVehicles.length === 0 && (
                                <div className="mt-5 rounded-3xl border border-zinc-200 bg-white p-8 text-center">
                                    <CarFront className="mx-auto size-10 text-zinc-300" />
                                    <h3 className="mt-3 text-lg font-black">
                                        Nenhum veículo atende aos filtros
                                    </h3>
                                    <p className="mt-1 text-sm text-zinc-500">
                                        Remova um dos filtros para ampliar os resultados.
                                    </p>
                                    <button
                                        type="button"
                                        onClick={clearFilters}
                                        className="mt-4 rounded-xl bg-zinc-950 px-5 py-3 text-sm font-black text-white"
                                    >
                                        Limpar filtros
                                    </button>
                                </div>
                            )}

                            <div className="mt-5 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                                {sortedVehicles.map((vehicle) => {
                                    const featured =
                                        vehicle.photos?.find(
                                            (photo) => photo.featured,
                                        ) ?? vehicle.photos?.at(0);

                                    const photoUrl = featured?.url ?? storageUrl(featured?.path);

                                    const features: Feature[] = [
                                        {
                                            icon: Users,
                                            label: `${vehicle.seats ?? '—'} lugares`,
                                        },
                                        {
                                            icon: CarFront,
                                            label: `${vehicle.doors ?? '—'} portas`,
                                        },
                                        {
                                            icon: Gauge,
                                            label: vehicle.transmission ?? 'Câmbio',
                                        },
                                        {
                                            icon: Fuel,
                                            label: vehicle.fuel_type ?? 'Combustível',
                                        },
                                        {
                                            icon: Snowflake,
                                            label: vehicle.air_conditioning
                                                ? 'Ar-condicionado'
                                                : 'Sem ar',
                                        },
                                        {
                                            icon: Briefcase,
                                            label: vehicle.luggage_capacity
                                                ? `${vehicle.luggage_capacity} malas`
                                                : 'Porta-malas',
                                        },
                                    ];

                                    return (
                                        <article
                                            key={vehicle.id}
                                            className="overflow-hidden rounded-[2rem] border border-zinc-200 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-xl"
                                        >
                                            <div className="relative aspect-[16/10] bg-zinc-100">
                                                {photoUrl ? (
                                                    <img
                                                        src={photoUrl}
                                                        alt={vehicle.name}
                                                        className="size-full object-contain p-3"
                                                    />
                                                ) : (
                                                    <div className="grid size-full place-items-center">
                                                        <CarFront className="size-16 text-zinc-300" />
                                                    </div>
                                                )}

                                                <span className="absolute top-4 left-4 rounded-full bg-black/85 px-3 py-1.5 text-xs font-black text-white">
                                                    {vehicle.prefix ?? 'Veículo'}
                                                </span>
                                                <div className="absolute top-4 right-4 flex flex-col items-end gap-2">
                                                    {vehicle.id ===
                                                        recommendedVehicleId && (
                                                        <span className="rounded-full bg-emerald-600 px-3 py-1.5 text-xs font-black text-white shadow">
                                                            Recomendado
                                                        </span>
                                                    )}

                                                    {vehicle.model_year ===
                                                        newestYear &&
                                                        newestYear > 0 && (
                                                            <span className="rounded-full bg-red-600 px-3 py-1.5 text-xs font-black text-white shadow">
                                                                Mais novo
                                                            </span>
                                                        )}

                                                    {equipmentScore(vehicle) ===
                                                        maxEquipmentScore &&
                                                        maxEquipmentScore > 0 && (
                                                            <span className="rounded-full bg-zinc-900 px-3 py-1.5 text-xs font-black text-white shadow">
                                                                Mais equipado
                                                            </span>
                                                        )}
                                                </div>
                                            </div>

                                            <div className="p-6">
                                                <p className="text-xs font-black tracking-[0.16em] text-red-600 uppercase">
                                                    {vehicle.category?.name}
                                                </p>

                                                <h2 className="mt-2 min-h-14 text-xl font-black leading-7">
                                                    {vehicle.name}
                                                </h2>

                                                <p className="mt-1 text-sm text-zinc-500">
                                                    {[
                                                        vehicle.model_year,
                                                        vehicle.branch?.name,
                                                    ]
                                                        .filter(Boolean)
                                                        .join(' · ')}
                                                </p>

                                                <div className="mt-5 grid grid-cols-2 gap-2 text-sm">
                                                    {features.map(
                                                        ({ icon: Icon, label }) => (
                                                            <div
                                                                key={label}
                                                                className="flex min-h-11 items-center gap-2 rounded-xl bg-zinc-50 px-3 py-2 font-semibold text-zinc-700"
                                                            >
                                                                <Icon className="size-4 shrink-0 text-red-600" />
                                                                <span className="truncate">
                                                                    {label}
                                                                </span>
                                                            </div>
                                                        ),
                                                    )}
                                                </div>

                                                {estimatedTotal !== null && (
                                                    <div className="mt-5 rounded-2xl border border-zinc-100 bg-zinc-50 px-4 py-3">
                                                        <p className="text-xs font-bold text-zinc-400">
                                                            Valor estimado para o período
                                                        </p>
                                                        <p className="mt-1 text-xl font-black text-zinc-950">
                                                            {money.format(estimatedTotal)}
                                                        </p>
                                                    </div>
                                                )}

                                                <div className="mt-6 grid grid-cols-2 gap-3">
                                                    <a
                                                        href={vehicleUrl(vehicle)}
                                                        className="grid h-12 place-items-center rounded-xl border border-zinc-200 font-black transition hover:border-zinc-400"
                                                    >
                                                        Ver detalhes
                                                    </a>

                                                    <a
                                                        href={vehicleUrl(vehicle)}
                                                        className="grid h-12 place-items-center rounded-xl bg-red-600 px-3 text-center text-sm font-black whitespace-nowrap text-white transition hover:bg-red-700"
                                                    >
                                                        {vehicle.id ===
                                                        recommendedVehicleId
                                                            ? 'Escolher recomendado'
                                                            : 'Escolher veículo'}
                                                    </a>
                                                </div>
                                            </div>
                                        </article>
                                    );
                                })}
                            </div>
                        </>
                    )}
                </main>
            </div>
        </>
    );
}
