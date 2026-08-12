<div style="page-break-before: always; font-family: DejaVu Sans, sans-serif; color:#18181b; font-size:10px; line-height:1.45;">
    <div style="border-bottom:3px solid #dc2626; padding-bottom:10px; margin-bottom:20px;">
        <div style="font-size:20px; font-weight:700;">CERTIFICADO DE ASSINATURA ELETRÔNICA</div>
        <div style="color:#71717a;">Evidências vinculadas ao contrato assinado</div>
    </div>

    <table style="width:100%; border-collapse:collapse;">
        <tr><td style="border:1px solid #d4d4d8; padding:8px; width:30%; font-weight:700;">Signatário</td><td style="border:1px solid #d4d4d8; padding:8px;">{{ $signatureRequest->signer_name }}</td></tr>
        <tr><td style="border:1px solid #d4d4d8; padding:8px; font-weight:700;">CPF/CNPJ</td><td style="border:1px solid #d4d4d8; padding:8px;">{{ $signatureRequest->signer_document ?: 'Não informado' }}</td></tr>
        <tr><td style="border:1px solid #d4d4d8; padding:8px; font-weight:700;">Data e hora</td><td style="border:1px solid #d4d4d8; padding:8px;">{{ $signatureRequest->signed_at?->format('d/m/Y H:i:s') }}</td></tr>
        <tr><td style="border:1px solid #d4d4d8; padding:8px; font-weight:700;">Endereço IP</td><td style="border:1px solid #d4d4d8; padding:8px;">{{ $signatureRequest->signed_ip ?: 'Não informado' }}</td></tr>
        <tr><td style="border:1px solid #d4d4d8; padding:8px; font-weight:700;">Hash da versão assinada</td><td style="border:1px solid #d4d4d8; padding:8px; word-break:break-all;">{{ $signatureRequest->document_hash }}</td></tr>
        <tr><td style="border:1px solid #d4d4d8; padding:8px; font-weight:700;">Hash do pacote assinado</td><td style="border:1px solid #d4d4d8; padding:8px; word-break:break-all;">{{ $signatureRequest->signed_document_hash }}</td></tr>
    </table>

    <div style="margin-top:28px; text-align:center;">
        @if($signatureImage)
            <img src="{{ $signatureImage }}" style="max-width:280px; max-height:110px; margin-bottom:8px;" alt="Assinatura eletrônica">
        @endif
        <div style="width:320px; max-width:100%; margin:0 auto; border-top:1px solid #27272a; padding-top:6px;">
            <strong>{{ $signatureRequest->signer_name }}</strong><br>
            Assinatura eletrônica
        </div>
    </div>

    <div style="margin-top:24px; border:1px solid #d4d4d8; background:#f4f4f5; padding:12px;">
        {{ $signatureRequest->acceptance_text }}
    </div>
</div>
