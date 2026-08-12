import { Head, useForm } from '@inertiajs/react';
import { CheckCircle2, FileText, ShieldCheck } from 'lucide-react';
import { FormEvent, PointerEvent, useEffect, useRef, useState } from 'react';

type Props = {
    request: {
        status: string;
        signer_name: string;
        signer_document?: string | null;
        expires_at: string;
        signed_at?: string | null;
    };
    contract: {
        number: string;
        customer?: string | null;
        starts_at?: string | null;
        ends_at?: string | null;
        total_value: number;
    };
    submit_url: string;
    pdf_url: string;
};

const money = (value: number) =>
    new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);

const dateTime = (value?: string | null) =>
    value ? new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value)) : '—';

export default function ContractSignature({ request, contract, submit_url, pdf_url }: Props) {
    const canvasRef = useRef<HTMLCanvasElement | null>(null);
    const [drawing, setDrawing] = useState(false);
    const [hasSignature, setHasSignature] = useState(false);
    const form = useForm({ accepted: false, signature: '' });

    useEffect(() => {
        const canvas = canvasRef.current;
        if (!canvas) return;

        const resize = () => {
            const ratio = window.devicePixelRatio || 1;
            const rect = canvas.getBoundingClientRect();
            const snapshot = hasSignature ? canvas.toDataURL('image/png') : null;

            canvas.width = Math.max(1, Math.round(rect.width * ratio));
            canvas.height = Math.max(1, Math.round(rect.height * ratio));

            const ctx = canvas.getContext('2d');
            if (!ctx) return;

            ctx.scale(ratio, ratio);
            ctx.lineWidth = 2.2;
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#111827';

            if (snapshot) {
                const img = new Image();
                img.onload = () => ctx.drawImage(img, 0, 0, rect.width, rect.height);
                img.src = snapshot;
            }
        };

        resize();
        window.addEventListener('resize', resize);
        return () => window.removeEventListener('resize', resize);
    }, [hasSignature]);

    const point = (event: PointerEvent<HTMLCanvasElement>) => {
        const canvas = canvasRef.current!;
        const rect = canvas.getBoundingClientRect();
        return { x: event.clientX - rect.left, y: event.clientY - rect.top };
    };

    const start = (event: PointerEvent<HTMLCanvasElement>) => {
        if (request.status === 'signed') return;
        const ctx = canvasRef.current?.getContext('2d');
        if (!ctx) return;

        const p = point(event);
        ctx.beginPath();
        ctx.moveTo(p.x, p.y);
        setDrawing(true);
        canvasRef.current?.setPointerCapture(event.pointerId);
    };

    const move = (event: PointerEvent<HTMLCanvasElement>) => {
        if (!drawing) return;
        const ctx = canvasRef.current?.getContext('2d');
        if (!ctx) return;

        const p = point(event);
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
        setHasSignature(true);
    };

    const end = () => setDrawing(false);

    const clear = () => {
        const canvas = canvasRef.current;
        if (!canvas) return;

        canvas.getContext('2d')?.clearRect(0, 0, canvas.width, canvas.height);
        setHasSignature(false);
        form.setData('signature', '');
        form.clearErrors();
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const canvas = canvasRef.current;
        if (!canvas || !hasSignature) return;

        const signature = canvas.toDataURL('image/png');

        form.transform((data) => ({ ...data, signature }));
        form.post(submit_url, {
            preserveScroll: true,
            onStart: () => form.clearErrors(),
        });
    };

    const signed = request.status === 'signed';

    return (
        <>
            <Head title={`Assinar contrato ${contract.number}`} />
            <main className="min-h-screen bg-zinc-950 px-4 py-8 text-zinc-100 sm:py-12">
                <div className="mx-auto max-w-3xl">
                    <div className="mb-6 text-center">
                        <img src="/images/careca-locadora-logo.png" alt="Careca Locadora" className="mx-auto h-auto w-full max-w-[280px] object-contain" />
                    </div>

                    <section className="overflow-hidden rounded-[2rem] border border-zinc-800 bg-zinc-900 shadow-2xl">
                        <div className="border-b border-zinc-800 bg-gradient-to-r from-red-700 to-red-600 px-6 py-6 sm:px-8">
                            <div className="flex items-center gap-3">
                                <ShieldCheck className="size-7" />
                                <div>
                                    <p className="text-xs font-black tracking-[.18em] uppercase">Assinatura eletrônica</p>
                                    <h1 className="text-2xl font-black sm:text-3xl">Contrato {contract.number}</h1>
                                </div>
                            </div>
                        </div>

                        <div className="space-y-7 p-6 sm:p-8">
                            {signed && (
                                <div className="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-6 text-emerald-100">
                                    <div className="flex items-center gap-3 text-lg font-black">
                                        <CheckCircle2 className="size-7" />
                                        Contrato assinado com sucesso
                                    </div>
                                    <p className="mt-3 text-sm leading-6 text-emerald-200">
                                        Sua assinatura eletrônica foi registrada e vinculada à versão deste contrato.
                                    </p>
                                    <p className="mt-2 text-sm">Assinado em {dateTime(request.signed_at)}.</p>
                                </div>
                            )}

                            <div className="grid gap-3 rounded-2xl bg-zinc-950/60 p-5 sm:grid-cols-2">
                                <div><span className="text-xs text-zinc-500">Locatário</span><div className="font-bold">{request.signer_name}</div></div>
                                <div><span className="text-xs text-zinc-500">CPF/CNPJ</span><div className="font-bold">{request.signer_document ?? '—'}</div></div>
                                <div><span className="text-xs text-zinc-500">Período</span><div className="font-bold">{dateTime(contract.starts_at)} a {dateTime(contract.ends_at)}</div></div>
                                <div><span className="text-xs text-zinc-500">Valor total</span><div className="font-bold text-red-400">{money(contract.total_value)}</div></div>
                            </div>

                            <a href={pdf_url} target="_blank" rel="noreferrer" className="flex h-14 items-center justify-center gap-2 rounded-xl border border-zinc-700 bg-zinc-800 font-black transition hover:border-red-500">
                                <FileText className="size-5" />
                                {signed ? 'Visualizar contrato assinado em PDF' : 'Visualizar contrato completo em PDF'}
                            </a>

                            {!signed && (
                                <form onSubmit={submit} className="space-y-5">
                                    <div>
                                        <div className="mb-2 flex items-center justify-between gap-3">
                                            <label className="font-black">Assine no quadro abaixo</label>
                                            <button type="button" onClick={clear} className="text-sm font-bold text-red-400 hover:text-red-300">Limpar assinatura</button>
                                        </div>

                                        <canvas
                                            ref={canvasRef}
                                            onPointerDown={start}
                                            onPointerMove={move}
                                            onPointerUp={end}
                                            onPointerCancel={end}
                                            className="h-48 w-full touch-none rounded-2xl bg-white"
                                        />

                                        {form.errors.signature && (
                                            <p className="mt-2 rounded-xl border border-red-500/30 bg-red-500/10 p-3 text-sm text-red-300">
                                                {form.errors.signature}
                                            </p>
                                        )}
                                    </div>

                                    <label className="flex items-start gap-3 rounded-2xl border border-zinc-800 bg-zinc-950/50 p-4 text-sm leading-6">
                                        <input
                                            type="checkbox"
                                            checked={form.data.accepted}
                                            onChange={(e) => form.setData('accepted', e.target.checked)}
                                            className="mt-1 size-4 accent-red-600"
                                        />
                                        <span>
                                            Declaro que li o contrato completo, compreendi suas condições e concordo com a contratação e com o registro eletrônico desta assinatura.
                                        </span>
                                    </label>

                                    {form.errors.accepted && (
                                        <p className="rounded-xl border border-red-500/30 bg-red-500/10 p-3 text-sm text-red-300">
                                            {form.errors.accepted}
                                        </p>
                                    )}

                                    <button
                                        disabled={form.processing || !form.data.accepted || !hasSignature}
                                        className="h-14 w-full rounded-xl bg-red-600 text-base font-black transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        {form.processing ? 'Registrando assinatura...' : 'Assinar contrato'}
                                    </button>
                                </form>
                            )}
                        </div>
                    </section>

                    <p className="mt-5 text-center text-xs text-zinc-500">
                        Link válido até {dateTime(request.expires_at)}.
                    </p>
                </div>
            </main>
        </>
    );
}
