@extends('contable::layouts.app')

@section('title', 'Balance de Comprobación')
@section('page-title', 'Balance de Comprobación')
@section('breadcrumb') Reportes <span class="mx-1 text-ink/30">/</span> Balance de Comprobación @endsection

@section('content')

{{-- ── Filtros ─────────────────────────────────────────────────────── --}}
<form method="GET" class="bg-white border border-ink/10 px-5 py-4 mb-5">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 items-end">
        <div>
            <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">Desde</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white focus:outline-none focus:border-sage">
        </div>
        <div>
            <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">Hasta</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white focus:outline-none focus:border-sage">
        </div>
        <div>
            <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">Centro de Costo</label>
            <select name="cost_center_id"
                class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white focus:outline-none focus:border-sage">
                <option value="">Todos</option>
                @foreach($costCenters as $cc)
                    <option value="{{ $cc->id }}" {{ request('cost_center_id') == $cc->id ? 'selected' : '' }}>
                        [{{ $cc->code }}] {{ $cc->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col gap-2">
            <label class="flex items-center gap-2 cursor-pointer select-none">
                <input type="checkbox" name="close_year" value="1" {{ request('close_year') ? 'checked' : '' }}
                    class="w-3.5 h-3.5 border-ink/20 accent-sage">
                <span class="text-[11px] font-bold text-ink/50 uppercase tracking-wide">Cierre anual</span>
            </label>
            <button type="submit"
                class="w-full px-4 py-2 bg-sage text-white text-[11px] font-bold uppercase tracking-wide
                       hover:bg-forest transition-colors">
                Generar Balance
            </button>
        </div>
    </div>
</form>

@isset($data)
@php
$sections      = $data['sections'] ?? [];
$totals        = $data['totals']   ?? [];
$balanced      = $data['balanced'];
$hasCostCenter = !empty($data['cost_center_id']);

$cols = [
    ['key' => 'opening_debit',  'label' => 'S.I. Débito',  'group' => 'Saldo Inicial'],
    ['key' => 'opening_credit', 'label' => 'S.I. Crédito', 'group' => 'Saldo Inicial'],
    ['key' => 'period_debit',   'label' => 'Mov. Débito',  'group' => 'Movimientos'],
    ['key' => 'period_credit',  'label' => 'Mov. Crédito', 'group' => 'Movimientos'],
    ['key' => 'closing_debit',  'label' => 'S.F. Débito',  'group' => 'Saldo Final'],
    ['key' => 'closing_credit', 'label' => 'S.F. Crédito', 'group' => 'Saldo Final'],
];
@endphp

@if($hasCostCenter)
<div class="mb-4 flex items-start gap-3 bg-amber-50 border border-amber-200 px-4 py-3 text-[11px] text-amber-800">
    <svg class="w-3.5 h-3.5 shrink-0 mt-0.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
    </svg>
    <span>
        <strong>Filtro por centro de costo activo.</strong>
        Solo se muestran las líneas registradas en ese CC.
        El cuadre débito = crédito <em>no aplica</em> en este desglose.
    </span>
</div>
@endif

@if(count($sections))
<div class="bg-white border border-ink/10 overflow-hidden">
    <div class="overflow-x-auto">

        {{-- Cabecera de columnas --}}
        <div class="flex bg-forest text-white text-[10px] font-bold uppercase tracking-wider select-none">
            <div class="flex-1 px-4 py-3" style="min-width: 14rem">Cuenta</div>
            @php $lastGroup = null; @endphp
            @foreach($cols as $col)
                @php $newGroup = $col['group'] !== $lastGroup; $lastGroup = $col['group']; @endphp
                <div class="w-28 shrink-0 px-3 py-3 text-right text-mint/70
                            {{ $newGroup ? 'border-l-2 border-white/20' : 'border-l border-white/10' }}">
                    {{ $col['label'] }}
                </div>
            @endforeach
        </div>

        {{-- Secciones por clase --}}
        @foreach($sections as $section)

        <div class="flex items-stretch bg-forest/80 text-white border-t border-white/10">
            <div class="flex-1 px-4 py-2 text-[10px] font-bold uppercase tracking-widest" style="min-width: 14rem">
                <span class="font-mono text-mint/50 mr-2">{{ $section['class_code'] }}</span>
                {{ $section['class_name'] }}
            </div>
            @foreach($cols as $col)
            <div class="w-28 shrink-0 px-3 py-2 text-right font-mono text-[10px] text-mint/70 border-l border-white/10">
                {{ ($section['totals'][$col['key']] ?? 0) > 0
                    ? number_format($section['totals'][$col['key']], 0, ',', '.')
                    : '' }}
            </div>
            @endforeach
        </div>

        @foreach($section['nodes'] as $node)
            @include('contable::reports._trial_balance_row', ['node' => $node, 'depth' => 0])
        @endforeach

        @endforeach

        {{-- Totales generales --}}
        <div class="flex items-stretch bg-forest text-white font-bold border-t-2 border-white/20">
            <div class="flex-1 px-4 py-3 text-[10px] uppercase tracking-wider" style="min-width: 14rem">
                Totales Generales
            </div>
            @foreach($cols as $col)
            <div class="w-28 shrink-0 px-3 py-3 text-right font-mono text-[11px] border-l border-white/10">
                {{ number_format($totals[$col['key']] ?? 0, 0, ',', '.') }}
            </div>
            @endforeach
        </div>

        {{-- Indicador de cuadre --}}
        @if($hasCostCenter)
        <div class="px-5 py-2 text-center text-[11px] text-amber-600 bg-amber-50 font-medium">
            Filtro de CC activo — el cuadre débito = crédito no aplica en este desglose
        </div>
        @elseif($balanced !== null)
            @php $ok = $balanced['closing'] ?? false; @endphp
            <div class="px-5 py-2.5 text-center text-[11px] font-semibold
                        {{ $ok ? 'bg-sage/5 text-sage' : 'bg-red-50 text-red-600' }}">
                @if($ok)
                    ✓ Balance cuadrado — Saldos finales débito = crédito
                @else
                    ⚠ Diferencia detectada en saldos finales — Revisar registros contables
                @endif
            </div>
        @endif

    </div>
</div>

@else
<div class="bg-white border border-ink/10 p-12 text-center text-[12px] text-ink/30">
    No hay cuentas con movimientos en el rango seleccionado.
</div>
@endif

@else
<div class="border border-dashed border-ink/15 p-12 text-center text-[12px] text-ink/30">
    Selecciona un rango de fechas para generar el balance de comprobación.
</div>
@endisset

@endsection
