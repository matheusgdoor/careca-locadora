import { Menu } from 'lucide-react';
import { useState } from 'react';

export default function PublicHeader({ active }: { active?: string }) {
    const [open, setOpen] = useState(false);

    const links = [
        ['/reservar', 'Reservar', 'reservar'],
        ['/categorias', 'Categorias', 'categorias'],
        ['/vantagens', 'Vantagens', 'vantagens'],
        ['/filiais', 'Filiais', 'filiais'],
    ];

    return (
        <header className="sticky top-0 z-50 border-b border-white/5 bg-[#08090b]/95 text-white backdrop-blur">
            <div className="mx-auto flex h-20 max-w-7xl items-center justify-between px-5 lg:px-8">
                <a href="/" className="flex min-w-0 items-center">
                    <img
                        src="/images/careca-locadora-logo.png"
                        alt="Careca Locadora de Veículos"
                        className="h-14 w-auto max-w-[245px] object-contain object-left"
                    />
                </a>

                <nav className="hidden items-center gap-8 text-sm font-bold lg:flex">
                    {links.map(([href, label, key]) => (
                        <a
                            key={href}
                            href={href}
                            className={
                                active === key
                                    ? 'text-red-400'
                                    : 'transition hover:text-red-400'
                            }
                        >
                            {label}
                        </a>
                    ))}
                </nav>

                <div className="hidden items-center gap-3 lg:flex">
                    <a href="/cliente/acesso" className="rounded-full border border-white/15 px-5 py-2.5 text-sm font-bold transition hover:bg-white/10">
                        Área do cliente
                    </a>
                    <a href="/app" className="rounded-full bg-red-600 px-5 py-2.5 text-sm font-bold transition hover:bg-red-700">
                        Painel administrativo
                    </a>
                </div>

                <button
                    type="button"
                    onClick={() => setOpen((current) => !current)}
                    className="grid size-11 place-items-center rounded-xl border border-white/10 lg:hidden"
                    aria-label="Abrir menu"
                >
                    <Menu className="size-5" />
                </button>
            </div>

            {open && (
                <div className="border-t border-white/10 bg-[#08090b] px-5 py-5 lg:hidden">
                    <nav className="mx-auto grid max-w-7xl gap-2">
                        {links.map(([href, label]) => (
                            <a key={href} href={href} className="rounded-xl px-4 py-3 font-bold hover:bg-white/5">
                                {label}
                            </a>
                        ))}
                        <a href="/cliente/acesso" className="mt-2 rounded-xl border border-white/10 px-4 py-3 font-bold">
                            Área do cliente
                        </a>
                        <a href="/app" className="rounded-xl bg-red-600 px-4 py-3 text-center font-black">
                            Painel administrativo
                        </a>
                    </nav>
                </div>
            )}
        </header>
    );
}
