import { Head } from '@inertiajs/react';
import { ArrowRight, Building2, MapPin, MessageCircle, Phone } from 'lucide-react';
import PublicHeader from '@/components/public/public-header';
import PublicFooter from '@/components/public/public-footer';

type Branch = {
    id: string;
    name: string;
    address?: string | null;
    number?: string | null;
    neighborhood?: string | null;
    city?: string | null;
    state?: string | null;
    zip_code?: string | null;
    phone?: string | null;
    whatsapp?: string | null;
};

export default function Branches({ branches }: { branches: Branch[] }) {
    return (
        <>
            <Head title="Filiais | Careca Locadora" />
            <div className="min-h-screen bg-[#f5f2eb] text-zinc-950">
                <PublicHeader active="filiais" />

                <main>
                    <section className="bg-zinc-950 text-white">
                        <div className="mx-auto max-w-7xl px-5 py-16 lg:px-8">
                            <p className="text-xs font-black tracking-[.22em] text-red-400 uppercase">Presença regional</p>
                            <h1 className="mt-4 max-w-4xl text-5xl font-black tracking-tight md:text-6xl">Uma Careca Locadora perto de você.</h1>
                            <p className="mt-5 max-w-2xl text-lg leading-8 text-zinc-300">Escolha a unidade mais conveniente e já inicie sua reserva com a filial selecionada.</p>
                        </div>
                    </section>

                    <section className="mx-auto max-w-7xl px-5 py-12 lg:px-8">
                        <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                            {branches.map((branch) => {
                                const whatsapp = (branch.whatsapp ?? '').replace(/\D/g, '');
                                const address = [branch.address, branch.number, branch.neighborhood].filter(Boolean).join(', ');
                                return (
                                    <article key={branch.id} className="flex min-h-[380px] flex-col rounded-[2rem] border border-zinc-200 bg-white p-7 shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                                        <div className="flex items-start justify-between">
                                            <span className="grid size-12 place-items-center rounded-2xl bg-red-50 text-red-600"><Building2 className="size-6" /></span>
                                            <span className="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-black text-emerald-700">Atendimento ativo</span>
                                        </div>

                                        <h2 className="mt-5 text-2xl font-black">{branch.name}</h2>
                                        <div className="mt-5 space-y-4 text-sm">
                                            <div className="flex items-start gap-3"><MapPin className="mt-0.5 size-5 shrink-0 text-red-600" /><div><b>{[branch.city, branch.state].filter(Boolean).join(' / ') || 'Localização'}</b>{address && <p className="mt-1 leading-6 text-zinc-500">{address}</p>}</div></div>
                                            {branch.phone && <div className="flex items-center gap-3"><Phone className="size-5 text-red-600" /><span className="font-bold">{branch.phone}</span></div>}
                                            {branch.whatsapp && <div className="flex items-center gap-3"><MessageCircle className="size-5 text-emerald-600" /><span className="font-bold">{branch.whatsapp}</span></div>}
                                        </div>

                                        <div className="mt-auto grid gap-3 pt-7">
                                            <a href={`/reservar?branch_id=${encodeURIComponent(branch.id)}`} className="flex h-12 items-center justify-center gap-2 rounded-xl bg-red-600 font-black text-white hover:bg-red-700">Reservar nesta filial <ArrowRight className="size-4" /></a>
                                            {whatsapp && <a href={`https://wa.me/55${whatsapp}`} target="_blank" rel="noreferrer" className="grid h-12 place-items-center rounded-xl border border-zinc-200 font-black hover:bg-zinc-50">Falar no WhatsApp</a>}
                                        </div>
                                    </article>
                                );
                            })}
                        </div>

                        {branches.length === 0 && (
                            <div className="rounded-[2rem] border border-zinc-200 bg-white p-12 text-center"><Building2 className="mx-auto size-14 text-zinc-300" /><h2 className="mt-4 text-xl font-black">Nenhuma filial disponível</h2></div>
                        )}
                    </section>

                    <section className="bg-[#111214] text-white">
                        <div className="mx-auto flex max-w-7xl flex-col gap-6 px-5 py-14 md:flex-row md:items-center md:justify-between lg:px-8">
                            <div><p className="text-xs font-black tracking-[.2em] text-red-400 uppercase">Precisa de ajuda?</p><h2 className="mt-2 text-3xl font-black">Nossa equipe pode indicar a melhor filial.</h2></div>
                            <a href="https://wa.me/5562982887249" target="_blank" rel="noreferrer" className="grid h-12 shrink-0 place-items-center rounded-xl bg-emerald-500 px-6 font-black">WhatsApp (62) 98288-7249</a>
                        </div>
                    </section>
                </main>

                <PublicFooter />
            </div>
        </>
    );
}
