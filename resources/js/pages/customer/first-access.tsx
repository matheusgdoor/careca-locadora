import { Head, Link, useForm } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import { FormEvent } from 'react';

const onlyDigits = (value: string) =>
    value.replace(/\D/g, '').slice(0, 14);

const maskDocument = (value: string) => {
    const digits = onlyDigits(value);

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

export default function CustomerFirstAccess() {
    const form = useForm({
        document: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/cliente/primeiro-acesso');
    };

    return (
        <>
            <Head title="Primeiro acesso | Careca Locadora" />
            <div className="min-h-screen bg-[#f4f2ed] px-5 py-10">
                <main className="mx-auto max-w-xl">
                    <Link href="/" className="inline-flex items-center">
                        <img
                            src="/images/careca-locadora-logo.png"
                            alt="Careca Locadora de Veículos"
                            className="h-16 w-auto max-w-[280px] object-contain object-left"
                        />
                    </Link>

                    <form
                        onSubmit={submit}
                        className="mt-10 rounded-[2rem] border border-zinc-200 bg-white p-8 shadow-xl"
                    >
                        <ShieldCheck className="size-10 text-red-600" />
                        <h1 className="mt-4 text-3xl font-black">
                            Primeiro acesso
                        </h1>

                        <p className="mt-2 leading-7 text-zinc-500">
                            Use o CPF/CNPJ e o e-mail informados na sua reserva
                            e crie sua senha.
                        </p>

                        {form.hasErrors && (
                            <div className="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                                Não foi possível criar seu acesso. Revise os dados destacados abaixo.
                            </div>
                        )}
                        <label className="mt-6 block text-sm font-black text-zinc-700">
                            CPF/CNPJ
                            <input
                                inputMode="numeric"
                                autoComplete="off"
                                placeholder="000.000.000-00"
                                value={form.data.document}
                                onChange={(event) =>
                                    form.setData(
                                        'document',
                                        maskDocument(event.target.value),
                                    )
                                }
                                className="mt-2 h-12 w-full rounded-xl border border-zinc-200 px-4 outline-none focus:border-red-500"
                            />
                            <p className="mt-1 text-xs font-medium text-zinc-400">
                                A máscara muda automaticamente entre CPF e CNPJ.
                            </p>
                            {form.errors.document && (
                                <p className="mt-1 text-sm font-semibold text-red-600">
                                    {form.errors.document}
                                </p>
                            )}
                        </label>

                        <label className="mt-5 block text-sm font-black text-zinc-700">
                            E-mail
                            <input
                                type="email"
                                autoComplete="email"
                                value={form.data.email}
                                onChange={(event) =>
                                    form.setData('email', event.target.value)
                                }
                                className="mt-2 h-12 w-full rounded-xl border border-zinc-200 px-4 outline-none focus:border-red-500"
                            />
                            {form.errors.email && (
                                <p className="mt-1 text-sm font-semibold text-red-600">
                                    {form.errors.email}
                                </p>
                            )}
                        </label>

                        <label className="mt-5 block text-sm font-black text-zinc-700">
                            Crie sua senha
                            <input
                                type="password"
                                autoComplete="new-password"
                                value={form.data.password}
                                onChange={(event) =>
                                    form.setData('password', event.target.value)
                                }
                                className="mt-2 h-12 w-full rounded-xl border border-zinc-200 px-4 outline-none focus:border-red-500"
                            />
                            {form.errors.password && (
                                <p className="mt-1 text-sm font-semibold text-red-600">
                                    {form.errors.password}
                                </p>
                            )}
                        </label>

                        <label className="mt-5 block text-sm font-black text-zinc-700">
                            Confirme sua senha
                            <span className="mt-1 block text-xs font-medium text-zinc-400">As duas senhas devem ser iguais.</span>
                            <input
                                type="password"
                                autoComplete="new-password"
                                value={form.data.password_confirmation}
                                onChange={(event) =>
                                    form.setData(
                                        'password_confirmation',
                                        event.target.value,
                                    )
                                }
                                className="mt-2 h-12 w-full rounded-xl border border-zinc-200 px-4 outline-none focus:border-red-500"
                            />
                        </label>

                        <button
                            disabled={form.processing}
                            className="mt-6 h-12 w-full rounded-xl bg-red-600 font-black text-white transition hover:bg-red-700 disabled:opacity-60"
                        >
                            Criar meu acesso
                        </button>

                        <Link
                            href="/cliente/acesso"
                            className="mt-5 block text-center font-black text-zinc-600"
                        >
                            Já tenho acesso
                        </Link>
                    </form>
                </main>
            </div>
        </>
    );
}
