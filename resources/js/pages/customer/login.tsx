import { Head, Link, useForm } from '@inertiajs/react';
import { CarFront, LockKeyhole, UserRound } from 'lucide-react';
import { FormEvent } from 'react';

export default function CustomerLogin() {
    const form = useForm({ identifier: '', password: '', remember: true });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/cliente/acesso');
    };

    return (
        <>
            <Head title="Área do Cliente | Careca Locadora" />
            <div className="min-h-screen bg-[#f4f2ed] lg:grid lg:grid-cols-2">
                <section className="hidden bg-zinc-950 p-14 text-white lg:flex lg:flex-col lg:justify-between">
                    <Link href="/" className="flex items-center gap-3 text-xl font-black">
                        <span className="grid size-12 place-items-center rounded-2xl bg-red-600"><CarFront /></span>
                        Careca Locadora
                    </Link>
                    <div className="max-w-xl">
                        <p className="text-sm font-black tracking-[.2em] text-red-400 uppercase">Portal do Cliente</p>
                        <h1 className="mt-4 text-5xl font-black leading-tight">Sua locação em um só lugar.</h1>
                        <p className="mt-5 text-lg leading-8 text-zinc-400">
                            Acompanhe reservas, contratos, documentos e informações da sua locação.
                        </p>
                    </div>
                    <p className="text-sm text-zinc-500">Acesso seguro e integrado à Careca Locadora.</p>
                </section>

                <main className="grid min-h-screen place-items-center px-5 py-10">
                    <form onSubmit={submit} className="w-full max-w-md rounded-[2rem] border border-zinc-200 bg-white p-8 shadow-xl">
                        <p className="text-xs font-black tracking-[.18em] text-red-600 uppercase">Bem-vindo</p>
                        <h2 className="mt-2 text-3xl font-black">Acesse sua conta</h2>
                        <p className="mt-2 text-sm text-zinc-500">Use CPF/CNPJ ou e-mail cadastrado.</p>

                        <label className="mt-7 block text-sm font-black text-zinc-700">
                            CPF/CNPJ ou e-mail
                            <div className="mt-2 flex h-12 items-center rounded-xl border border-zinc-200 px-4 focus-within:border-red-500">
                                <UserRound className="size-5 text-zinc-400" />
                                <input
                                    value={form.data.identifier}
                                    onChange={(e) => form.setData('identifier', e.target.value)}
                                    className="h-full w-full bg-transparent px-3 outline-none"
                                />
                            </div>
                            {form.errors.identifier && <p className="mt-1 text-red-600">{form.errors.identifier}</p>}
                        </label>

                        <label className="mt-5 block text-sm font-black text-zinc-700">
                            Senha
                            <div className="mt-2 flex h-12 items-center rounded-xl border border-zinc-200 px-4 focus-within:border-red-500">
                                <LockKeyhole className="size-5 text-zinc-400" />
                                <input
                                    type="password"
                                    value={form.data.password}
                                    onChange={(e) => form.setData('password', e.target.value)}
                                    className="h-full w-full bg-transparent px-3 outline-none"
                                />
                            </div>
                        </label>

                        <button disabled={form.processing} className="mt-6 h-12 w-full rounded-xl bg-red-600 font-black text-white hover:bg-red-700 disabled:opacity-60">
                            Entrar
                        </button>

                        <div className="mt-6 border-t border-zinc-100 pt-5 text-center">
                            <Link href="/cliente/primeiro-acesso" className="font-black text-red-600">
                                Primeiro acesso
                            </Link>
                        </div>
                    </form>
                </main>
            </div>
        </>
    );
}
