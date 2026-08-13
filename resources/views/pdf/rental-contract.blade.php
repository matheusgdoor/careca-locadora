<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<style>
@page{margin:20px 26px 34px}
body{font-family:DejaVu Sans,sans-serif;color:#18181b;font-size:9px;line-height:1.36}
.header{border-bottom:4px solid #dc2626;padding-bottom:10px;margin-bottom:12px}
.logo{width:178px;max-height:72px;object-fit:contain}
.title{margin-top:6px;font-size:18px;font-weight:700}
.subtitle{margin-top:3px;color:#52525b}
.mode{display:inline-block;margin-top:7px;background:#18181b;color:#fff;padding:5px 9px;font-size:9px;font-weight:700}
.version{float:right;margin-top:11px;color:#71717a;font-size:8px}
.section{margin-top:10px}
.section-title{background:#18181b;color:#fff;padding:6px 8px;font-size:10px;font-weight:700}
.section-title.red{background:#b91c1c}
.grid,.summary{width:100%;border-collapse:collapse}
.grid td,.grid th,.summary td{border:1px solid #d4d4d8;padding:5px 6px;vertical-align:top}
.grid th{background:#f4f4f5;text-align:left;font-size:8px}
.summary .label{display:block;color:#71717a;font-size:7.2px;text-transform:uppercase;margin-bottom:2px}
.summary .value{font-weight:700;font-size:8.8px}
.qualify{margin-top:3px;font-size:7.6px;line-height:1.35;color:#3f3f46}
.money{font-size:12px;color:#b91c1c;font-weight:700}
.clause{margin:6px 0;text-align:justify}
.clause-title{font-weight:700}
.note{border:1px solid #fecaca;background:#fff7f7;padding:7px 8px;margin-top:7px}
.signatures{width:100%;border-collapse:collapse;margin-top:23px;page-break-inside:avoid}
.signatures td{width:50%;text-align:center;padding:28px 14px 0;vertical-align:bottom}
.sign-line{border-top:1px solid #27272a;padding-top:5px}
.signature-status{margin-top:11px;border:1px solid #d4d4d8;background:#fafafa;padding:8px;text-align:center;font-size:7.7px}
.muted{color:#71717a}.small{font-size:7.5px}.nowrap{white-space:nowrap}
.footer{position:fixed;left:0;right:0;bottom:-22px;text-align:center;font-size:7px;color:#71717a}
.page-break{page-break-before:always}
</style>
</head>
<body>
@php
$mode=$contract->rental_mode==='monthly'?'LOCAÇÃO MENSAL':'LOCAÇÃO DIÁRIA';
$item=$contract->items->first();

$formatDocument=function(?string $value):string{
    $d=preg_replace('/\D+/','',(string)$value);
    if(strlen($d)===11)return substr($d,0,3).'.'.substr($d,3,3).'.'.substr($d,6,3).'-'.substr($d,9,2);
    if(strlen($d)===14)return substr($d,0,2).'.'.substr($d,2,3).'.'.substr($d,5,3).'/'.substr($d,8,4).'-'.substr($d,12,2);
    return $value ?: '—';
};

$formatPhone=function(?string $value):string{
    $d=preg_replace('/\D+/','',(string)$value);
    if(strlen($d)===11)return '('.substr($d,0,2).') '.substr($d,2,5).'-'.substr($d,7,4);
    if(strlen($d)===10)return '('.substr($d,0,2).') '.substr($d,2,4).'-'.substr($d,6,4);
    return $value ?: '—';
};

$formatCep=function(?string $value):string{
    $d=preg_replace('/\D+/','',(string)$value);
    return strlen($d)===8 ? substr($d,0,5).'-'.substr($d,5,3) : ($value ?: '—');
};

$unitLabel=function(?string $unit):string{
    return match($unit){
        'daily'=>'Diária',
        'monthly'=>'Mensal',
        'fixed'=>'Período',
        'hourly'=>'Hora',
        default=>$unit ?: '—',
    };
};

$companyName=data_get($contract->company,'legal_name') ?: data_get($contract->company,'trade_name') ?: 'Careca Locadora de Veículos';
$companyTrade=data_get($contract->company,'trade_name');
$companyDocument=data_get($contract->company,'cnpj');

$companyAddress=collect([
    data_get($contract->company,'street'),
    data_get($contract->company,'address_number'),
    data_get($contract->company,'address_complement'),
])->filter()->implode(', ');

$companyCity=collect([
    data_get($contract->company,'district'),
    data_get($contract->company,'city'),
    data_get($contract->company,'state'),
])->filter()->implode(' - ');

$companyContact=collect([
    data_get($contract->company,'phone') ? $formatPhone(data_get($contract->company,'phone')) : null,
    data_get($contract->company,'whatsapp') ? 'WhatsApp '.$formatPhone(data_get($contract->company,'whatsapp')) : null,
    data_get($contract->company,'email'),
])->filter()->implode(' | ');

$customer=$contract->customer;
$primaryAddress=$customer?->addresses?->firstWhere('is_primary',true) ?: $customer?->addresses?->first();

$customerAddress=collect([
    data_get($primaryAddress,'address'),
    data_get($primaryAddress,'number'),
    data_get($primaryAddress,'complement'),
])->filter()->implode(', ');

$customerCity=collect([
    data_get($primaryAddress,'district'),
    data_get($primaryAddress,'city'),
    data_get($primaryAddress,'state'),
])->filter()->implode(' - ');

$customerPhone=data_get($customer,'whatsapp') ?: data_get($customer,'phone');

$distance=$contract->included_distance===null
    ? 'Quilometragem livre'
    : number_format((float)$contract->included_distance,0,',','.').($contract->rental_mode==='monthly'?' km/mês':' km');

$fuel=match($contract->fuel_policy){
    'full_to_full'=>'Cheio para cheio',
    'charged_difference'=>'Diferença de combustível cobrada',
    default=>'Mesmo nível da retirada',
};
@endphp

<div class="header">
@if($logo)<img src="{{ $logo }}" class="logo" alt="Careca Locadora">@endif
<div class="title">CONTRATO DE LOCAÇÃO DE VEÍCULO</div>
<div class="subtitle">Contrato {{ $contract->number }} | {{ $contract->starts_at?->format('d/m/Y H:i') }} a {{ $contract->ends_at?->format('d/m/Y H:i') }}</div>
<div class="mode">{{ $mode }}</div>
<div class="version">Versão {{ (int)($contract->contract_version ?: 1) }}</div>
</div>

<div class="section">
<div class="section-title red">QUALIFICAÇÃO DAS PARTES</div>
<table class="summary">
<tr>
<td width="50%">
<span class="label">Locadora</span>
<span class="value">{{ $companyName }}</span>
@if($companyTrade && $companyTrade!==$companyName)<div class="qualify">Nome fantasia: {{ $companyTrade }}</div>@endif
<div class="qualify">
@if($companyDocument)CNPJ: {{ $formatDocument($companyDocument) }}@endif
@if(data_get($contract->company,'state_registration'))<br>Inscrição Estadual: {{ data_get($contract->company,'state_registration') }}@endif
@if(data_get($contract->company,'municipal_registration'))<br>Inscrição Municipal: {{ data_get($contract->company,'municipal_registration') }}@endif
@if($companyAddress)<br>{{ $companyAddress }}@endif
@if($companyCity)<br>{{ $companyCity }}@endif
@if(data_get($contract->company,'postal_code'))<br>CEP: {{ $formatCep(data_get($contract->company,'postal_code')) }}@endif
@if($companyContact)<br>{{ $companyContact }}@endif
</div>
</td>

<td width="50%">
<span class="label">Locatário</span>
<span class="value">{{ data_get($customer,'display_name') ?: 'Não informado' }}</span>
<div class="qualify">
@if(data_get($customer,'document'))CPF/CNPJ: {{ $formatDocument(data_get($customer,'document')) }}@endif
@if(data_get($customer,'state_registration'))<br>Inscrição Estadual: {{ data_get($customer,'state_registration') }}@endif
@if(data_get($customer,'municipal_registration'))<br>Inscrição Municipal: {{ data_get($customer,'municipal_registration') }}@endif
@if($customerAddress)<br>{{ $customerAddress }}@endif
@if($customerCity)<br>{{ $customerCity }}@endif
@if(data_get($primaryAddress,'postal_code'))<br>CEP: {{ $formatCep(data_get($primaryAddress,'postal_code')) }}@endif
@if(data_get($customer,'email'))<br>{{ data_get($customer,'email') }}@endif
@if($customerPhone)<br>{{ $formatPhone($customerPhone) }}@endif
</div>
</td>
</tr>

@if($contract->authorizedContact)
<tr>
<td colspan="2">
<span class="label">Contato / responsável autorizado</span>
<span class="value">{{ data_get($contract->authorizedContact,'name') }}</span>
<div class="qualify">
@if(data_get($contract->authorizedContact,'position'))Cargo: {{ data_get($contract->authorizedContact,'position') }}@endif
@if(data_get($contract->authorizedContact,'cpf')) | CPF: {{ $formatDocument(data_get($contract->authorizedContact,'cpf')) }}@endif
@if(data_get($contract->authorizedContact,'document_number')) | Documento: {{ data_get($contract->authorizedContact,'document_number') }}@endif
@if(data_get($contract->authorizedContact,'email')) | {{ data_get($contract->authorizedContact,'email') }}@endif
@if(data_get($contract->authorizedContact,'whatsapp')) | {{ $formatPhone(data_get($contract->authorizedContact,'whatsapp')) }}@elseif(data_get($contract->authorizedContact,'phone')) | {{ $formatPhone(data_get($contract->authorizedContact,'phone')) }}@endif
</div>
</td>
</tr>
@endif
</table>
</div>

<div class="section">
<div class="section-title">1. VEÍCULO(S)</div>
<table class="grid">
<thead><tr>
<th style="width:9%">Prefixo</th><th style="width:25%">Veículo</th><th style="width:11%">Placa</th>
<th style="width:16%">Período</th><th style="width:9%">Unidade</th><th style="width:7%">Qtd.</th>
<th style="width:11%">Valor</th><th style="width:12%">Total</th>
</tr></thead>
<tbody>
@foreach($contract->items as $contractItem)
<tr>
<td>{{ data_get($contractItem->asset,'prefix') ?: '—' }}</td>
<td>{{ data_get($contractItem->asset,'name') ?: '—' }}</td>
<td>{{ data_get($contractItem->asset,'plate') ?: '—' }}</td>
<td>{{ $contractItem->starts_at?->format('d/m/Y') }} a {{ $contractItem->ends_at?->format('d/m/Y') }}</td>
<td>{{ $unitLabel($contractItem->billing_unit) }}</td>
<td>{{ number_format((float)$contractItem->quantity,2,',','.') }}</td>
<td class="nowrap">R$ {{ number_format((float)$contractItem->unit_value,2,',','.') }}</td>
<td class="nowrap">R$ {{ number_format((float)$contractItem->total_value,2,',','.') }}</td>
</tr>
@endforeach
</tbody>
</table>
</div>

<div class="section">
<div class="section-title">2. RESUMO COMERCIAL</div>
@if($contract->rental_mode==='monthly')
<table class="summary">
<tr>
<td width="25%"><span class="label">Modalidade</span><span class="value">{{ $mode }}</span></td>
<td width="25%"><span class="label">Mensalidade</span><span class="value">@if($item)R$ {{ number_format((float)$item->unit_value,2,',','.') }}@else—@endif</span></td>
<td width="25%"><span class="label">Dia de vencimento</span><span class="value">{{ $contract->billing_day ?: '—' }}</span></td>
<td width="25%"><span class="label">Franquia mensal de KM</span><span class="value">{{ $distance }}</span></td>
</tr>
<tr>
<td><span class="label">KM excedente</span><span class="value">R$ {{ number_format((float)$contract->extra_distance_value,2,',','.') }}/km</span></td>
<td><span class="label">Proteção/seguro</span><span class="value">{{ $contract->protection_included ? 'Incluído' : 'Não incluído' }}</span></td>
<td><span class="label">Franquia de proteção</span><span class="value">R$ {{ number_format((float)$contract->protection_deductible,2,',','.') }}</span></td>
<td><span class="label">Combustível</span><span class="value">{{ $fuel }}</span></td>
</tr>
</table>
@else
<table class="summary">
<tr>
<td width="25%"><span class="label">Modalidade</span><span class="value">{{ $mode }}</span></td>
<td width="25%"><span class="label">Diária</span><span class="value">@if($item)R$ {{ number_format((float)$item->unit_value,2,',','.') }}@else—@endif</span></td>
<td width="25%"><span class="label">Franquia de KM</span><span class="value">{{ $distance }}</span></td>
<td width="25%"><span class="label">KM excedente</span><span class="value">R$ {{ number_format((float)$contract->extra_distance_value,2,',','.') }}/km</span></td>
</tr>
<tr>
<td><span class="label">Proteção/seguro</span><span class="value">{{ $contract->protection_included ? 'Incluído' : 'Não incluído' }}</span></td>
<td><span class="label">Franquia de proteção</span><span class="value">R$ {{ number_format((float)$contract->protection_deductible,2,',','.') }}</span></td>
<td><span class="label">Caução</span><span class="value">R$ {{ number_format((float)$contract->deposit_value,2,',','.') }}</span></td>
<td><span class="label">Combustível</span><span class="value">{{ $fuel }}</span></td>
</tr>
</table>
@endif
<table class="summary"><tr>
<td width="50%"><span class="label">Retirada</span><span class="value">{{ $contract->pickup_location ?: 'Conforme combinado entre as partes' }}</span></td>
<td width="50%"><span class="label">Devolução</span><span class="value">{{ $contract->return_location ?: 'Conforme combinado entre as partes' }}</span></td>
</tr></table>
</div>

<div class="section">
<div class="section-title">3. VALORES</div>
<table class="grid">
<tr><th>Subtotal</th><td>R$ {{ number_format((float)$contract->subtotal,2,',','.') }}</td><th>Desconto</th><td>R$ {{ number_format((float)$contract->discount_value,2,',','.') }}</td></tr>
<tr><th>Acréscimos</th><td>R$ {{ number_format((float)$contract->additional_value,2,',','.') }}</td><th>Caução</th><td>R$ {{ number_format((float)$contract->deposit_value,2,',','.') }}</td></tr>
<tr><th colspan="3">Valor total</th><td class="money">R$ {{ number_format((float)$contract->total_value,2,',','.') }}</td></tr>
</table>
</div>

@if($contract->terms)
<div class="section"><div class="section-title">4. CONDIÇÕES PARTICULARES ADICIONAIS</div><div class="note">{!! nl2br(e($contract->terms)) !!}</div></div>
@endif

<div class="page-break"></div>
<div class="section">
<div class="section-title red">CONDIÇÕES GERAIS DE LOCAÇÃO</div>
<div class="clause"><span class="clause-title">1. DO OBJETO.</span> A LOCADORA entrega à LOCATÁRIA, em caráter temporário e mediante remuneração, o(s) veículo(s) descrito(s) nas Condições Particulares. Para fins deste instrumento, integram o veículo seus pneus, ferramentas, equipamentos, acessórios, chaves, placas e documentos.</div>
<div class="clause"><span class="clause-title">2. DA VISTORIA E ESTADO DO VEÍCULO.</span> A LOCATÁRIA declara receber o veículo nas condições registradas no checklist de retirada, comprometendo-se a comunicar imediatamente qualquer divergência. O checklist de entrega e o checklist de devolução, quando emitidos, integram este contrato para todos os fins.</div>
<div class="clause"><span class="clause-title">3. DO PRAZO.</span> A locação vigorará pelo período indicado nas Condições Particulares. A permanência do veículo com a LOCATÁRIA após o término dependerá de autorização da LOCADORA e poderá gerar cobrança adicional.</div>
<div class="clause"><span class="clause-title">4. DO PREÇO E PAGAMENTO.</span> A remuneração será calculada conforme a modalidade indicada neste instrumento. Na locação diária, será considerada a quantidade de diárias e demais encargos contratados. Na locação mensal, serão observados o valor da mensalidade e o dia de vencimento informado nas Condições Particulares.</div>
<div class="clause"><span class="clause-title">5. DA QUILOMETRAGEM.</span> Quando houver franquia, a LOCATÁRIA poderá utilizar o veículo até o limite indicado. O excedente será cobrado pelo valor unitário por quilômetro informado. Na ausência de limite preenchido, considera-se quilometragem livre, salvo condição particular diversa.</div>
<div class="clause"><span class="clause-title">6. DO CONDUTOR.</span> O veículo deverá ser conduzido somente por pessoa legalmente habilitada e autorizada, observadas as exigências legais e de eventual proteção securitária. Sendo a LOCATÁRIA pessoa jurídica, permanece responsável pelos condutores que autorizar.</div>
<div class="clause"><span class="clause-title">7. DA UTILIZAÇÃO.</span> É vedado utilizar o veículo para finalidade ilícita, competição, testes de velocidade, reboque não autorizado, transporte incompatível com sua natureza, sublocação, cessão ou empréstimo não autorizado, bem como conduzi-lo sob efeito de álcool ou substâncias capazes de comprometer a direção.</div>
<div class="clause"><span class="clause-title">8. DAS MULTAS E PENALIDADES.</span> A LOCATÁRIA responderá pelas infrações, multas, pedágios, estacionamentos e demais encargos decorrentes do uso do veículo durante o período de sua posse, ainda que a notificação seja recebida após o encerramento da locação.</div>
<div class="clause"><span class="clause-title">9. DA MANUTENÇÃO.</span> A manutenção preventiva decorrente do uso regular e das revisões programadas será de responsabilidade da LOCADORA, salvo condição particular expressa em sentido diverso. Reparos decorrentes de mau uso, negligência, imprudência, imperícia ou uso fora das especificações serão suportados pela LOCATÁRIA.</div>
<div class="clause"><span class="clause-title">10. DA PROTEÇÃO, SEGURO E SINISTROS.</span> Quando indicada proteção/seguro, sua aplicação observará limites, franquias, exclusões e procedimentos informados pela LOCADORA. Em caso de acidente, furto, roubo, incêndio ou outro sinistro, a LOCATÁRIA deverá comunicar imediatamente a LOCADORA e adotar as providências cabíveis.</div>
<div class="clause"><span class="clause-title">11. DO COMBUSTÍVEL.</span> O veículo deverá ser devolvido conforme a política de combustível indicada e registrada no checklist. Eventual diferença poderá ser cobrada da LOCATÁRIA.</div>
<div class="clause"><span class="clause-title">12. DA DEVOLUÇÃO.</span> O veículo deverá ser devolvido no local e prazo ajustados, nas mesmas condições gerais de conservação em que foi entregue, ressalvado o desgaste normal. Danos, avarias, acessórios ausentes, limpeza especial, combustível faltante e demais diferenças apuradas poderão ser cobrados.</div>
<div class="clause"><span class="clause-title">13. DAS RESPONSABILIDADES.</span> A LOCATÁRIA responde pela guarda e utilização do veículo enquanto estiver sob sua posse, inclusive pelos prejuízos decorrentes de conduta de seus prepostos, empregados, autorizados ou terceiros a quem tenha indevidamente permitido o uso.</div>
<div class="clause"><span class="clause-title">14. DA RESCISÃO.</span> O descumprimento das obrigações, o inadimplemento ou a utilização do veículo em desacordo com este instrumento poderá ensejar a rescisão e a exigência de devolução, sem prejuízo da cobrança dos valores devidos e das perdas e danos comprovadamente apuradas.</div>
<div class="clause"><span class="clause-title">15. DO FORO.</span> Fica eleito o foro da comarca da sede da LOCADORA para dirimir controvérsias decorrentes deste instrumento, ressalvadas as hipóteses em que a legislação aplicável determine foro diverso.</div>
<div class="note small">Este contrato deve ser interpretado em conjunto com as Condições Particulares, checklist(s), anexos, aditivos e demais documentos vinculados à locação.</div>
</div>

@if($contract->commercial_notes || $contract->operational_notes)
<div class="section"><div class="section-title">OBSERVAÇÕES</div><div class="note">
@if($contract->commercial_notes){!! nl2br(e($contract->commercial_notes)) !!}@endif
@if($contract->operational_notes)@if($contract->commercial_notes)<br><br>@endif{!! nl2br(e($contract->operational_notes)) !!}@endif
</div></div>
@endif

<table class="signatures"><tr>
<td><div class="sign-line">{{ data_get($customer,'display_name') ?: 'LOCATÁRIO' }}<br><span class="muted">LOCATÁRIO</span></div></td>
<td><div class="sign-line">{{ $companyName }}<br><span class="muted">LOCADORA / RESPONSÁVEL</span></div></td>
</tr></table>

<div class="signature-status">
<strong>Assinatura física:</strong> as partes poderão utilizar os campos acima.<br>
<strong>Assinatura eletrônica:</strong> quando utilizada, a autenticidade e integridade serão demonstradas pelo Certificado de Assinatura Eletrônica anexado ao final deste documento, contendo data, hora, endereço IP e hashes de integridade.
</div>

<div class="footer">Careca Locadora de Veículos — Contrato {{ $contract->number }} — Versão {{ (int)($contract->contract_version ?: 1) }}</div>
</body>
</html>
