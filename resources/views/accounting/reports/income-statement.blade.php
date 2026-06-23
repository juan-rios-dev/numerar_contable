@extends('contable::layouts.app')

@section('title', 'Estado de Resultados')
@section('page-title', 'Estado de Resultados')
@section('breadcrumb') Reportes <span class="mx-1 text-ink/30">/</span> Estado de Resultados @endsection

@section('content')

{{-- ── Filtro ──────────────────────────────────────────────────────── --}}
<form method="GET" class="bg-white border border-ink/10 mb-4">
    <div class="px-5 py-3 border-b border-ink/10 bg-forest">
        <p class="text-[10px] font-bold uppercase tracking-widest text-mint/70">Parámetros del Reporte</p>
    </div>
    <div class="px-5 py-4 flex flex-wrap items-end gap-4">
        <div>
            <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">Desde</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                   class="border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white
                          focus:outline-none focus:border-sage">
        </div>
        <div>
            <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">Hasta</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                   class="border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white
                          focus:outline-none focus:border-sage">
        </div>
        <button type="submit"
                class="flex items-center gap-2 px-4 py-2 bg-sage text-white text-[11px] font-bold
                       uppercase tracking-wide hover:bg-forest transition-colors">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
            </svg>
            Generar
        </button>
    </div>
</form>

@isset($data)
@php
$incomeOp    = $data['income_op']        ?? ['name' => 'Ingresos Operacionales',                'nodes' => [], 'total' => 0];
$incomeOther = $data['income_other']     ?? ['name' => 'Otros Ingresos',                         'nodes' => [], 'total' => 0];
$costSales   = $data['cost_sales']       ?? ['name' => 'Costo de Ventas',                        'nodes' => [], 'total' => 0];
$costProd    = $data['cost_prod']        ?? ['name' => 'Costos de Producción',                   'nodes' => [], 'total' => 0];
$expAdmin    = $data['exp_admin']        ?? ['name' => 'Gastos Operacionales de Administración', 'nodes' => [], 'total' => 0];
$expSales    = $data['exp_sales']        ?? ['name' => 'Gastos Operacionales de Ventas',         'nodes' => [], 'total' => 0];
$expNonOp    = $data['exp_non_op']       ?? ['name' => 'Gastos No Operacionales',                'nodes' => [], 'total' => 0];
$expTax      = $data['exp_tax']          ?? ['name' => 'Impuesto de Renta',                      'nodes' => [], 'total' => 0];
$grossProfit = $data['gross_profit']     ?? 0;
$opProfit    = $data['operating_profit'] ?? 0;
$preTax      = $data['pre_tax_profit']   ?? 0;
$netProfit   = $data['net_profit']       ?? 0;

$dateFrom = request('date_from');
$dateTo   = request('date_to');

$fmt          = fn($v) => '$' . number_format(abs($v), 2, ',', '.');
$resultColor  = fn($v) => $v >= 0 ? 'text-sage' : 'text-red-600';
@endphp

{{-- ── Encabezado ────────────────────────────────────────────────────── --}}
<div class="bg-white border border-ink/10 mb-0">
    <div class="px-5 py-4 border-b border-ink/10 flex items-center justify-between">
        <div>
            <p class="text-[13px] font-bold text-ink uppercase tracking-wider">Estado de Resultados</p>
            @if($dateFrom && $dateTo)
            <p class="text-[11px] text-ink/40 mt-0.5">
                Del
                <span class="font-mono font-semibold text-ink/60">{{ \Carbon\Carbon::parse($dateFrom)->translatedFormat('d \d\e F \d\e Y') }}</span>
                al
                <span class="font-mono font-semibold text-ink/60">{{ \Carbon\Carbon::parse($dateTo)->translatedFormat('d \d\e F \d\e Y') }}</span>
            </p>
            @endif
        </div>
        <div class="text-right">
            <p class="text-[10px] font-bold uppercase tracking-widest mb-0.5 {{ $resultColor($netProfit) }}">
                {{ $netProfit >= 0 ? 'Utilidad' : 'Pérdida' }} neta del período
            </p>
            <p class="font-mono font-bold text-[17px] {{ $resultColor($netProfit) }}">
                {{ $netProfit < 0 ? '(' : '' }}{{ $fmt($netProfit) }}{{ $netProfit < 0 ? ')' : '' }}
            </p>
        </div>
    </div>
</div>

{{-- ── Cuerpo ─────────────────────────────────────────────────────────── --}}
<div class="bg-white border border-t-0 border-ink/10 overflow-hidden">

    {{-- ▌ INGRESOS OPERACIONALES ──────────────────────────────────────── --}}
    <div class="flex items-center justify-between px-4 py-2.5 bg-forest/90">
        <span class="text-[10px] font-bold uppercase tracking-widest text-white">{{ $incomeOp['name'] }}</span>
        <span class="text-[10px] font-mono text-mint/60">Clase 41</span>
    </div>
    @foreach($incomeOp['nodes'] as $node)
        @include('contable::reports._income_row', ['node' => $node, 'depth' => 0, 'sign' => 1])
    @endforeach
    <div class="flex items-center justify-between px-4 py-2.5 bg-ink/5 border-b border-ink/10">
        <span class="text-[11px] font-semibold text-ink/60">Total Ingresos Operacionales</span>
        <span class="font-mono text-[12px] font-bold text-sage">{{ $fmt($incomeOp['total']) }}</span>
    </div>

    {{-- ▌ COSTO DE VENTAS ──────────────────────────────────────────────── --}}
    @if($costSales['total'] > 0)
    <div class="flex items-center justify-between px-4 py-2.5 bg-forest/90">
        <span class="text-[10px] font-bold uppercase tracking-widest text-white">{{ $costSales['name'] }}</span>
        <span class="text-[10px] font-mono text-mint/60">Clase 6</span>
    </div>
    @foreach($costSales['nodes'] as $node)
        @include('contable::reports._income_row', ['node' => $node, 'depth' => 0, 'sign' => -1])
    @endforeach
    <div class="flex items-center justify-between px-4 py-2.5 bg-ink/5 border-b border-ink/10">
        <span class="text-[11px] font-semibold text-ink/60">Total Costo de Ventas</span>
        <span class="font-mono text-[12px] font-bold text-red-600">({{ $fmt($costSales['total']) }})</span>
    </div>
    @endif

    {{-- ── UTILIDAD BRUTA ───────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between px-4 py-3 border-b-2 border-ink/15 bg-cream/60">
        <span class="text-[12px] font-bold text-ink uppercase tracking-wide">Utilidad Bruta</span>
        <span class="font-mono font-bold text-[14px] {{ $resultColor($grossProfit) }}">{{ $fmt($grossProfit) }}</span>
    </div>

    {{-- ▌ GASTOS OPERACIONALES DE ADMINISTRACIÓN ───────────────────────── --}}
    @if($expAdmin['total'] > 0)
    <div class="flex items-center justify-between px-4 py-2.5 bg-forest/90">
        <span class="text-[10px] font-bold uppercase tracking-widest text-white">{{ $expAdmin['name'] }}</span>
        <span class="text-[10px] font-mono text-mint/60">Clase 51</span>
    </div>
    @foreach($expAdmin['nodes'] as $node)
        @include('contable::reports._income_row', ['node' => $node, 'depth' => 0, 'sign' => -1])
    @endforeach
    <div class="flex items-center justify-between px-4 py-2.5 bg-ink/5 border-b border-ink/10">
        <span class="text-[11px] font-semibold text-ink/60">Total Gastos de Administración</span>
        <span class="font-mono text-[12px] font-bold text-red-600">({{ $fmt($expAdmin['total']) }})</span>
    </div>
    @endif

    {{-- ▌ GASTOS OPERACIONALES DE VENTAS ──────────────────────────────── --}}
    @if($expSales['total'] > 0)
    <div class="flex items-center justify-between px-4 py-2.5 bg-forest/90">
        <span class="text-[10px] font-bold uppercase tracking-widest text-white">{{ $expSales['name'] }}</span>
        <span class="text-[10px] font-mono text-mint/60">Clase 52</span>
    </div>
    @foreach($expSales['nodes'] as $node)
        @include('contable::reports._income_row', ['node' => $node, 'depth' => 0, 'sign' => -1])
    @endforeach
    <div class="flex items-center justify-between px-4 py-2.5 bg-ink/5 border-b border-ink/10">
        <span class="text-[11px] font-semibold text-ink/60">Total Gastos de Ventas</span>
        <span class="font-mono text-[12px] font-bold text-red-600">({{ $fmt($expSales['total']) }})</span>
    </div>
    @endif

    {{-- ▌ COSTOS DE PRODUCCIÓN ─────────────────────────────────────────── --}}
    @if($costProd['total'] > 0)
    <div class="flex items-center justify-between px-4 py-2.5 bg-forest/90">
        <span class="text-[10px] font-bold uppercase tracking-widest text-white">{{ $costProd['name'] }}</span>
        <span class="text-[10px] font-mono text-mint/60">Clase 7</span>
    </div>
    @foreach($costProd['nodes'] as $node)
        @include('contable::reports._income_row', ['node' => $node, 'depth' => 0, 'sign' => -1])
    @endforeach
    <div class="flex items-center justify-between px-4 py-2.5 bg-ink/5 border-b border-ink/10">
        <span class="text-[11px] font-semibold text-ink/60">Total Costos de Producción</span>
        <span class="font-mono text-[12px] font-bold text-red-600">({{ $fmt($costProd['total']) }})</span>
    </div>
    @endif

    {{-- ── UTILIDAD OPERATIVA (EBIT) ────────────────────────────────────── --}}
    <div class="flex items-center justify-between px-4 py-3 border-b-2 border-ink/15 bg-cream/60">
        <span class="text-[12px] font-bold text-ink uppercase tracking-wide">
            Utilidad Operativa
            <span class="text-[10px] text-ink/30 font-normal normal-case ml-1">(EBIT)</span>
        </span>
        <span class="font-mono font-bold text-[14px] {{ $resultColor($opProfit) }}">{{ $fmt($opProfit) }}</span>
    </div>

    {{-- ▌ OTROS INGRESOS ────────────────────────────────────────────────── --}}
    @if($incomeOther['total'] > 0)
    <div class="flex items-center justify-between px-4 py-2.5 bg-forest/90">
        <span class="text-[10px] font-bold uppercase tracking-widest text-white">{{ $incomeOther['name'] }}</span>
        <span class="text-[10px] font-mono text-mint/60">Clase 42</span>
    </div>
    @foreach($incomeOther['nodes'] as $node)
        @include('contable::reports._income_row', ['node' => $node, 'depth' => 0, 'sign' => 1])
    @endforeach
    <div class="flex items-center justify-between px-4 py-2.5 bg-ink/5 border-b border-ink/10">
        <span class="text-[11px] font-semibold text-ink/60">Total Otros Ingresos</span>
        <span class="font-mono text-[12px] font-bold text-sage">{{ $fmt($incomeOther['total']) }}</span>
    </div>
    @endif

    {{-- ▌ GASTOS NO OPERACIONALES ──────────────────────────────────────── --}}
    @if($expNonOp['total'] > 0)
    <div class="flex items-center justify-between px-4 py-2.5 bg-forest/90">
        <span class="text-[10px] font-bold uppercase tracking-widest text-white">{{ $expNonOp['name'] }}</span>
        <span class="text-[10px] font-mono text-mint/60">Clase 53</span>
    </div>
    @foreach($expNonOp['nodes'] as $node)
        @include('contable::reports._income_row', ['node' => $node, 'depth' => 0, 'sign' => -1])
    @endforeach
    <div class="flex items-center justify-between px-4 py-2.5 bg-ink/5 border-b border-ink/10">
        <span class="text-[11px] font-semibold text-ink/60">Total Gastos No Operacionales</span>
        <span class="font-mono text-[12px] font-bold text-red-600">({{ $fmt($expNonOp['total']) }})</span>
    </div>
    @endif

    {{-- ── UTILIDAD ANTES DE IMPUESTOS (EBT) ───────────────────────────── --}}
    <div class="flex items-center justify-between px-4 py-3 border-b-2 border-ink/15 bg-cream/60">
        <span class="text-[12px] font-bold text-ink uppercase tracking-wide">
            Utilidad Antes de Impuestos
            <span class="text-[10px] text-ink/30 font-normal normal-case ml-1">(EBT)</span>
        </span>
        <span class="font-mono font-bold text-[14px] {{ $resultColor($preTax) }}">{{ $fmt($preTax) }}</span>
    </div>

    {{-- ▌ IMPUESTO DE RENTA ────────────────────────────────────────────── --}}
    @if($expTax['total'] > 0)
    <div class="flex items-center justify-between px-4 py-2.5 bg-forest/90">
        <span class="text-[10px] font-bold uppercase tracking-widest text-white">{{ $expTax['name'] }}</span>
        <span class="text-[10px] font-mono text-mint/60">Clase 54</span>
    </div>
    @foreach($expTax['nodes'] as $node)
        @include('contable::reports._income_row', ['node' => $node, 'depth' => 0, 'sign' => -1])
    @endforeach
    <div class="flex items-center justify-between px-4 py-2.5 bg-ink/5 border-b border-ink/10">
        <span class="text-[11px] font-semibold text-ink/60">Total Impuesto de Renta</span>
        <span class="font-mono text-[12px] font-bold text-red-600">({{ $fmt($expTax['total']) }})</span>
    </div>
    @endif

    {{-- ── UTILIDAD / PÉRDIDA NETA ──────────────────────────────────────── --}}
    <div class="flex items-center justify-between px-5 py-5 {{ $netProfit >= 0 ? 'bg-forest' : 'bg-red-800' }}">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-widest mb-0.5
                      {{ $netProfit >= 0 ? 'text-mint/70' : 'text-red-200/70' }}">
                Resultado del período
            </p>
            <p class="text-[13px] font-bold text-white uppercase tracking-wide">
                {{ $netProfit >= 0 ? 'Utilidad' : 'Pérdida' }} Neta
            </p>
        </div>
        <span class="font-mono font-bold text-[22px] text-white">
            {{ $netProfit < 0 ? '(' : '' }}{{ $fmt($netProfit) }}{{ $netProfit < 0 ? ')' : '' }}
        </span>
    </div>

</div>

@else
<div class="bg-white border border-ink/10 px-8 py-16 text-center">
    <svg class="w-10 h-10 text-ink/15 mx-auto mb-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
    </svg>
    <p class="text-[12px] text-ink/30">Selecciona un rango de fechas para generar el estado de resultados.</p>
</div>
@endisset

@endsection
