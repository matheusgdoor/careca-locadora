export default function PublicFooter() {
    return (
        <footer className="bg-[#08090b] text-zinc-400">
            <div className="mx-auto flex max-w-7xl flex-col gap-4 px-5 py-8 text-sm md:flex-row md:items-center md:justify-between lg:px-8">
                <span>
                    © {new Date().getFullYear()} Careca Locadora de Veículos
                </span>
                <div className="flex flex-wrap gap-5">
                    <a href="/reservar" className="hover:text-white">Reservar</a>
                    <a href="/categorias" className="hover:text-white">Categorias</a>
                    <a href="/filiais" className="hover:text-white">Filiais</a>
                    <a href="https://wa.me/5562982887249" target="_blank" rel="noreferrer" className="hover:text-white">
                        WhatsApp
                    </a>
                </div>
            </div>
        </footer>
    );
}
