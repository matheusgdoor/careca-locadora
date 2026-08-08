import { Head, Link, useForm } from '@inertiajs/react';
import { CarFront, ShieldCheck } from 'lucide-react';
import { FormEvent } from 'react';

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
                    <Link href="/" className="flex items-center gap-3 font-black">
                        <span className="grid size-11 place-items-center rounded-2xl bg-red-600 text-white"><CarFront /></span>
                        Careca Locadora
                    </Link>

                    <form onSubmit={submit} className="mt-10 rounded-[2rem] border border-zinc-200 bg-white p-8 shadow-xl">
                        <ShieldCheck className="size-10 text-red-600" />
                        <h1 className="mt-4 text-3xl font-black">Primeiro acesso</h1>
                        <p className="mt-2 text-zinc-500">Use o CPF/CNPJ e o e-mail informados na sua reserva e crie sua senha.</p>

                        {[
                            ['document', 'CPF/CNPJ', 'text'],
                            ['email', 'E-mail', 'email'],
                            ['password', 'Crie sua senha', 'password'],
                            ['password_confirmation', 'Confirme sua senha', 'password'],
                        ].map(([key, label, type]) => (
                            <label key={key} className="mt-5 block text-sm font-black text-zinc-700">
                                {label}
                                <input
                                    type={type}
                                    value={form.data[key as keyof typeof form.data]}
                                    onChange={(e) => form.setData(key as keyof typeof form.data, e.target.value)}
                                    className="mt-2 h-12 w-full rounded-xl border border-zinc-200 px-4 outline-none focus:border-red-500"
                                />
                                {form.errors[key as keyof typeof form.errors] && (
                                    <p className="mt-1 text-red-600">{form.errors[key as keyof typeof form.errors]}</p>
                                )}
                            </label>
                        ))}

                        <button disabled={form.processing} className="mt-6 h-12 w-full rounded-xl bg-red-600 font-black text-white hover:bg-red-700 disabled:opacity-60">
                            Criar meu acesso
                        </button>
                        <Link href="/cliente/acesso" className="mt-5 block text-center font-black text-zinc-600">
                            Já tenho acesso
                        </Link>
                    </form>
                </main>
            </div>
        </>
    );
}
