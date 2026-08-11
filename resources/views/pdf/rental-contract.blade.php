<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<style>
@page { margin: 24px 28px 34px; }
body { font-family: DejaVu Sans, sans-serif; color:#18181b; font-size:10px; line-height:1.42; }
.header { border-bottom:3px solid #dc2626; padding-bottom:12px; margin-bottom:18px; }
.logo { width:190px; max-height:92px; object-fit:contain; }
.title { font-size:20px; font-weight:700; margin:10px 0 2px; }
.muted { color:#71717a; }
.section { margin-top:15px; }
.section-title { background:#18181b; color:#fff; padding:7px 9px; font-size:11px; font-weight:700; }
.grid { width:100%; border-collapse:collapse; }
.grid td,.grid th { border:1px solid #d4d4d8; padding:6px; vertical-align:top; }
.grid th { background:#f4f4f5; text-align:left; }
.total { font-size:14px; font-weight:700; color:#b91c1c; }
.terms { border:1px solid #d4d4d8; padding:10px; white-space:pre-wrap; }
.signatures { width:100%; margin-top:30px; border-collapse:collapse; page-break-inside:avoid; }
.signatures td { width:50%; text-align:center; padding:20px 15px 0; vertical-align:bottom; }
.sign-line { border-top:1px solid #27272a; padding-top:5px; }
.signature-image { max-width:210px; max-height:75px; margin:0 auto 4px; }
.audit { margin-top:15px; font-size:8px; color:#71717a; }
.footer { position:fixed; left:0; right:0; bottom:-22px; text-align:center; font-size:8px; color:#71717a; }
</style>
</head>
<body>
<div class="header">
@if($logo)<img src="{{ $logo }}" class="logo" alt="Careca Locadora">@endif
<div class="title">CONTRATO DE LOCAÇÃO DE VEÍCULO</div>
<div class="muted">Contrato {{ $contract->number }} | Período {{ $contract->starts_at?->format('d/m/Y H:i') }} a {{ $contract->ends_at?->format('d/m/Y H:i') }}</div>
</div>

<div class="section">
<div class="section-title">1. PARTES</div>
<table class="grid">
<tr><th width="20%">Locadora</th><td>{{ $contract->company?->trade_name ?: $contract->company?->legal_name ?: 'Careca Locadora de Veículos' }} @if($contract->company?->document) — {{ $contract->company->document }} @endif</td></tr>
<tr><th>Locatário</th><td><strong>{{ $contract->customer?->display_name }}</strong> @if($contract->customer?->document) — {{ $contract->customer->document }} @endif @if($contract->customer?->email)<br>{{ $contract->customer->email }}@endif @if($contract->customer?->phone) — {{ $contract->customer->phone }}@endif</td></tr>
@if($contract->authorizedContact)<tr><th>Contato autorizado</th><td>{{ $contract->authorizedContact->name }}</td></tr>@endif
</table>
</div>

<div class="section">
<div class="section-title">2. VEÍCULO(S) E CONDIÇÕES COMERCIAIS</div>
<table class="grid">
<thead><tr><th>Veículo</th><th>Placa</th><th>Período</th><th>Unidade</th><th>Qtd.</th><th>Valor</th><th>Total</th></tr></thead>
<tbody>
@foreach($contract->items as $item)
<tr>
<td>{{ $item->asset?->prefix }} — {{ $item->asset?->name }}</td>
<td>{{ $item->asset?->plate ?: '—' }}</td>
<td>{{ $item->starts_at?->format('d/m/Y') }} a {{ $item->ends_at?->format('d/m/Y') }}</td>
<td>{{ $item->billing_unit }}</td>
<td>{{ number_format((float)$item->quantity,2,',','.') }}</td>
<td>R$ {{ number_format((float)$item->unit_value,2,',','.') }}</td>
<td>R$ {{ number_format((float)$item->total_value,2,',','.') }}</td>
</tr>
@endforeach
</tbody>
</table>
</div>

<div class="section">
<div class="section-title">3. VALORES</div>
<table class="grid">
<tr><th>Subtotal</th><td>R$ {{ number_format((float)$contract->subtotal,2,',','.') }}</td><th>Desconto</th><td>R$ {{ number_format((float)$contract->discount_value,2,',','.') }}</td></tr>
<tr><th>Acréscimos</th><td>R$ {{ number_format((float)$contract->additional_value,2,',','.') }}</td><th>Caução</th><td>R$ {{ number_format((float)$contract->deposit_value,2,',','.') }}</td></tr>
<tr><th colspan="3">Valor total</th><td class="total">R$ {{ number_format((float)$contract->total_value,2,',','.') }}</td></tr>
</table>
</div>

<div class="section">
<div class="section-title">4. TERMOS E CONDIÇÕES</div>
<div class="terms">{{ $contract->terms ?: 'Aplicam-se as condições comerciais, operacionais e responsabilidades definidas pela locadora para esta contratação.' }}</div>
</div>

@if($contract->commercial_notes || $contract->operational_notes)
<div class="section"><div class="section-title">5. OBSERVAÇÕES</div><div class="terms">@if($contract->commercial_notes){{ $contract->commercial_notes }}@endif @if($contract->operational_notes)\n\n{{ $contract->operational_notes }}@endif</div></div>
@endif

<table class="signatures"><tr>
<td>@if($signatureImage)<img src="{{ $signatureImage }}" class="signature-image" alt="Assinatura do cliente">@endif<div class="sign-line">{{ $signatureRequest?->signer_name ?: $contract->customer?->display_name ?: 'LOCATÁRIO' }}<br><span class="muted">LOCATÁRIO</span></div></td>
<td><div style="height:79px"></div><div class="sign-line">CARECA LOCADORA DE VEÍCULOS<br><span class="muted">LOCADORA / RESPONSÁVEL</span></div></td>
</tr></table>

@if($signatureRequest?->signed_at)
<div class="audit">Assinatura eletrônica registrada em {{ $signatureRequest->signed_at->format('d/m/Y H:i:s') }}. IP: {{ $signatureRequest->signed_ip ?: 'não informado' }}. Hash original: {{ $signatureRequest->document_hash }}. Hash assinado: {{ $signatureRequest->signed_document_hash }}.</div>
@endif
<div class="footer">Careca Locadora de Veículos — Contrato {{ $contract->number }}</div>
</body>
</html>
