<!doctype html>
<html lang="pt-BR">
<body style="font-family:Arial,sans-serif;background:#f4f4f5;color:#18181b;padding:24px">
<div style="max-width:640px;margin:0 auto;background:white;border-radius:16px;padding:28px">
    <h1 style="margin:0 0 14px;color:#dc2626">Careca Locadora de Veículos</h1>
    <p>Olá, {{ $customer?->display_name ?? 'cliente' }}.</p>
    <p>Segue em anexo o contrato de locação <strong>{{ $contract->number }}</strong>.</p>
    @if($signatureUrl)
        <p>Para revisar e assinar eletronicamente, utilize o botão abaixo:</p>
        <p style="margin:28px 0">
            <a href="{{ $signatureUrl }}" style="background:#dc2626;color:white;text-decoration:none;padding:14px 22px;border-radius:10px;font-weight:bold">Revisar e assinar contrato</a>
        </p>
    @endif
    <p style="font-size:13px;color:#71717a">Caso prefira assinatura física, o PDF anexo também está preparado para impressão.</p>
</div>
</body>
</html>
