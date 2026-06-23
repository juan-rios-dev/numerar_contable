@extends('contable::layouts.app')

@section('title', 'Reporte por Centro de Costo')
@section('page-title', 'Reporte por Centro de Costo')
@section('breadcrumb') Reportes <span class="mx-1 text-ink/30">/</span> Centro de Costo @endsection

@section('content')
@include('contable::reports._filters', ['showCostCenter' => true])

@php $costCenterRows = $data['cost_centers'] ?? []; @endphp

@if(isset($data) && count($costCenterRows))
<div class="space-y-4">
    @foreach($costCenterRows as $cc)
    <div class="bg-white border border-ink/10 overflow-hidden">

        {{-- Cabecera del CC --}}
        <div class="px-4 py-2.5 bg-forest flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="font-mono text-[10px] text-mint/50 bg-white/10 px-2 py-0.5">{{ $cc['code'] }}</span>
                <span class="text-[12px] font-bold text-white">{{ $cc['name'] }}</span>
            </div>
            <div class="text-[10px] font-mono text-mint/60">
                Débito: <span class="text-white font-bold">{{ number_format($cc['total_debit'], 0, ',', '.') }}</span>
                <span class="mx-2 text-white/20">·</span>
                Crédito: <span class="text-white font-bold">{{ number_format($cc['total_credit'], 0, ',', '.') }}</span>
            </div>
        </div>

        <table class="w-full">
            <thead>
                <tr class="bg-ink/5 border-b border-ink/10">
                    <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5">Cuenta</th>
                    <th class="text-right text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 w-36">Débito</th>
                    <th class="text-right text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 w-36">Crédito</th>
                    <th class="text-right text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 w-36">Neto</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink/5">
                @foreach($cc['accounts'] as $acc)
                @php $net = $acc['total_debit'] - $acc['total_credit']; @endphp
                <tr class="hover:bg-cream">
                    <td class="px-4 py-2.5 text-[12px] text-ink">
                        <span class="font-mono text-[10px] text-ink/30 mr-2">{{ $acc['account_code'] }}</span>
                        {{ $acc['account_name'] }}
                    </td>
                    <td class="px-4 py-2.5 text-right font-mono text-[11px] {{ $acc['total_debit'] > 0 ? 'text-ink' : 'text-ink/20' }}">
                        {{ $acc['total_debit'] > 0 ? number_format($acc['total_debit'], 0, ',', '.') : '—' }}
                    </td>
                    <td class="px-4 py-2.5 text-right font-mono text-[11px] {{ $acc['total_credit'] > 0 ? 'text-ink/70' : 'text-ink/20' }}">
                        {{ $acc['total_credit'] > 0 ? number_format($acc['total_credit'], 0, ',', '.') : '—' }}
                    </td>
                    <td class="px-4 py-2.5 text-right font-mono text-[11px] font-semibold
                               {{ $net < 0 ? 'text-red-600' : 'text-ink' }}">
                        {{ number_format(abs($net), 0, ',', '.') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach
</div>

@elseif(request()->has('date_from'))
<div class="bg-white border border-ink/10 p-12 text-center text-[12px] text-ink/30">
    No hay datos en el rango seleccionado.
</div>

@else
<div class="border border-dashed border-ink/15 p-12 text-center text-[12px] text-ink/30">
    Selecciona un rango de fechas para generar el reporte por centro de costo.
</div>
@endif

@endsection
