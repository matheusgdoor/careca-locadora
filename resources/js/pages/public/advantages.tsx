import { Head } from '@inertiajs/react';
import {
    BadgeCheck,
    CalendarCheck2,
    CarFront,
    Headphones,
    ShieldCheck,
    Smartphone,
} from 'lucide-react';

const advantages = [
    {
        icon: CarFront,
        title: 'Frota selecionada',
        description:
            'Veículos organizados por categoria, com fotos, características e disponibilidade para o período escolhido.',
    },
    {
        icon: CalendarCheck2,
        title: 'Reserva online',
        description:
            'Consulte datas, compare os veículos disponíveis e escolha exatamente o ativo que deseja reservar.',
    },
    {
        icon: BadgeCheck,
        title: 'Escolha transparente',
        description:
            'Informações claras de lugares, portas, câmbio, combustível, conforto e demais características do veículo.',
    },
    {
        icon: ShieldCheck,
        title: 'Cotação segura',
        description:
            'O valor é calculado pelo motor comercial da locadora e a disponibilidade é validada novamente na confirmação.',
    },
    {
        icon: Smartphone,
        title: 'Atendimento pelo WhatsApp',
        description:
            'Após a solicitação, você pode continuar o atendimento diretamente com nossa equipe pelo WhatsApp.',
    },
    {
        icon: Headphones,
        title: 'Suporte próximo',
        description:
            'Atendimento pensado para acompanhar sua locação do início ao fim, incluindo retirada, uso e devolução.',
    },
];

export default function Advantages() {
    return (
        <>
            <Head title="Vantagens | Careca Locadora" />

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
                            <a href="/filiais" className="hover:text-red-400">
                                Filiais
                            </a>
                        </nav>
                    </div>
                </header>

                <main className="mx-auto max-w-7xl px-5 py-14 lg:px-8 lg:py-20">
                    <p className="text-xs font-black tracking-[0.22em] text-red-600 uppercase">
                        Por que escolher a Careca
                    </p>

                    <h1 className="mt-3 max-w-4xl text-4xl font-black tracking-tight md:text-6xl">
                        Uma locação simples, transparente e do seu jeito.
                    </h1>

                    <p className="mt-5 max-w-3xl text-lg leading-8 text-zinc-600">
                        Da pesquisa até a confirmação, nossa experiência foi
                        desenhada para você encontrar o veículo certo com rapidez
                        e segurança.
                    </p>

                    <section className="mt-12 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        {advantages.map(({ icon: Icon, title, description }) => (
                            <article
                                key={title}
                                className="rounded-[2rem] border border-zinc-200 bg-white p-7 shadow-sm"
                            >
                                <span className="grid size-12 place-items-center rounded-2xl bg-red-50 text-red-600">
                                    <Icon className="size-6" />
                                </span>

                                <h2 className="mt-5 text-xl font-black">
                                    {title}
                                </h2>

                                <p className="mt-3 leading-7 text-zinc-600">
                                    {description}
                                </p>
                            </article>
                        ))}
                    </section>

                    <section className="mt-12 rounded-[2rem] bg-zinc-950 p-8 text-white md:p-10">
                        <div className="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                            <div>
                                <p className="text-xs font-black tracking-[0.2em] text-red-400 uppercase">
                                    Pronto para reservar?
                                </p>
                                <h2 className="mt-2 text-3xl font-black">
                                    Encontre o veículo ideal para sua necessidade.
                                </h2>
                            </div>

                            <a
                                href="/"
                                className="grid h-12 shrink-0 place-items-center rounded-xl bg-red-600 px-6 font-black hover:bg-red-700"
                            >
                                Pesquisar veículos
                            </a>
                        </div>
                    </section>
                </main>
            </div>
        </>
    );
}
