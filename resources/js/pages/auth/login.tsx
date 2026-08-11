import { Form, Head } from '@inertiajs/react';
import { LockKeyhole, ShieldCheck } from 'lucide-react';
import InputError from '@/components/input-error';
import PasskeyVerify from '@/components/passkey-verify';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

export default function Login({ status, canResetPassword }: Props) {
    return (
        <>
            <Head title="Acesso administrativo | Careca Locadora" />

            <div className="mb-2 text-center">
                <a href="/" className="inline-flex justify-center">
                    <img
                        src="/images/careca-locadora-logo.png"
                        alt="Careca Locadora de Veículos"
                        className="h-auto w-full max-w-[230px] object-contain"
                    />
                </a>

                <div className="mx-auto mt-5 flex w-fit items-center gap-2 rounded-full bg-red-50 px-3 py-1.5 text-xs font-black tracking-[.12em] text-red-700 uppercase">
                    <ShieldCheck className="size-4" />
                    Acesso administrativo
                </div>

                <h1 className="mt-4 text-3xl font-black tracking-tight text-zinc-950">
                    Bem-vindo de volta
                </h1>

                <p className="mx-auto mt-2 max-w-sm text-sm leading-6 text-zinc-500">
                    Entre com seu e-mail e senha para acessar o painel da Careca Locadora.
                </p>
            </div>

            <PasskeyVerify />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="mt-6 flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-5">
                            <div className="grid gap-2">
                                <Label htmlFor="email">E-mail</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="email"
                                    placeholder="seuemail@empresa.com.br"
                                    className="h-12 rounded-xl"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center">
                                    <Label htmlFor="password">Senha</Label>

                                    {canResetPassword && (
                                        <TextLink
                                            href={request()}
                                            className="ml-auto text-sm font-semibold text-red-600 hover:text-red-700"
                                            tabIndex={5}
                                        >
                                            Esqueceu sua senha?
                                        </TextLink>
                                    )}
                                </div>

                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    placeholder="Digite sua senha"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center space-x-3">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    tabIndex={3}
                                />
                                <Label htmlFor="remember">Lembrar meu acesso</Label>
                            </div>

                            <Button
                                type="submit"
                                className="mt-2 h-12 w-full rounded-xl bg-red-600 text-base font-black text-white hover:bg-red-700"
                                tabIndex={4}
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing ? (
                                    <>
                                        <Spinner />
                                        Entrando...
                                    </>
                                ) : (
                                    <>
                                        <LockKeyhole className="size-4" />
                                        Entrar
                                    </>
                                )}
                            </Button>
                        </div>

                        <div className="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-center text-xs leading-5 text-zinc-500">
                            Acesso restrito aos usuários autorizados da Careca Locadora.
                        </div>
                    </>
                )}
            </Form>

            {status && (
                <div className="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-center text-sm font-medium text-emerald-700">
                    {status}
                </div>
            )}
        </>
    );
}

Login.layout = {
    title: '',
    description: '',
};
