import { Head } from '@inertiajs/react';
import {
    Building2,
    CarFront,
    Phone,
} from 'lucide-react';

type Branch = {
    id: string;
    name: string;
    phone?: string | null;
    whatsapp?: string | null;
};

type Props = {
    branches: Branch[];
};

export default function Branches({ branches }: Props) {
    return (
        <>
            <Head title="Filiais | Careca Locadora" />

            <div className="min-h-screen bg-[#f5f3ee] text-zinc-950">
                <header className="bg-[#090a0c] text-white">
                    <div className="mx-auto flex h-20 max-w-7xl items-center justify-between px-5 lg:px-8">
                        <a href="/" className="flex items-center gap-3 font-black">
                            <span className="grid size-11 place-items-center rounded-2xl bg-red-600">
                                <CarFront />
                            </span>
                            Careca Locadora
                        </a>

                        <nav className="hidden items-center gap-7 text-sm font-bold md:flex">
                            <a href="/" className="hover:text-red-400">
                                Início
                            </a>
                            <a href="/vantagens" className="hover:text-red-400">
                                Vantagens
                            </a>
                        </nav>
                    </div>
                </header>

                <main className="mx-auto max-w-7xl px-5 py-14 lg:px-8 lg:py-20">
                    <p className="text-xs font-black tracking-[0.22em] text-red-600 uppercase">
                        Nossas filiais
                    </p>

                    <h1 className="mt-3 max-w-4xl text-4xl font-black tracking-tight md:text-6xl">
                        Escolha a filial que melhor atende sua locação.
                    </h1>

                    <p className="mt-5 max-w-3xl text-lg leading-8 text-zinc-600">
                        Cada unidade é identificada pelo Nome da filial cadastrado
                        no sistema.
                    </p>

                    <section className="mt-10 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        {branches.map((branch) => (
                            <article
                                key={branch.id}
                                className="rounded-[2rem] border border-zinc-200 bg-white p-7 shadow-sm"
                            >
                                <span className="grid size-12 place-items-center rounded-2xl bg-red-50 text-red-600">
                                    <Building2 className="size-6" />
                                </span>

                                <h2 className="mt-5 text-2xl font-black">
                                    {branch.name}
                                </h2>

                                {(branch.whatsapp || branch.phone) && (
                                    <div className="mt-4 flex items-center gap-3 text-sm font-bold text-zinc-700">
                                        <Phone className="size-5 text-red-600" />
                                        {branch.whatsapp ?? branch.phone}
                                    </div>
                                )}

                                <a
                                    href={`/#reservar?branch_id=${encodeURIComponent(
                                        branch.id,
                                    )}`}
                                    className="mt-6 grid h-12 place-items-center rounded-xl bg-zinc-950 px-5 text-sm font-black text-white transition hover:bg-red-600"
                                >
                                    Reservar nesta filial
                                </a>
                            </article>
                        ))}
                    </section>

                    {branches.length === 0 && (
                        <div className="mt-10 rounded-3xl border border-zinc-200 bg-white p-10 text-center">
                            <Building2 className="mx-auto size-12 text-zinc-300" />
                            <h2 className="mt-4 text-xl font-black">
                                Nenhuma filial pública disponível
                            </h2>
                        </div>
                    )}
                </main>
            </div>
        </>
    );
}
