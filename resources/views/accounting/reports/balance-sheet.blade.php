@extends('contable::layouts.app')

@section('title', 'Estado de Situación Financiera')
@section('page-title', 'Estado de Situación Financiera')
@section('breadcrumb') Reportes <span class="mx-1 text-ink/30">/</span> Estado de Situación Financiera @endsection

@section('header-actions')
    <a href="{{ route('contable.reports.trial-balance') }}"
       class="flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wide
              text-ink/50 border border-ink/15 hover:border-ink/40 hover:text-ink transition-colors">
        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
        </svg>
        Balance de Prueba
    </a>
@endsection

@section('content')

{{-- ── Filtro ──────────────────────────────────────────────────────── --}}
<form method="GET" class="bg-white border border-ink/10 mb-4">
    <div class="px-5 py-3 border-b border-ink/10 bg-forest">
        <p class="text-[10px] font-bold uppercase tracking-widest text-mint/70">Parámetros del Reporte</p>
    </div>
    <div class="px-5 py-4 flex items-end gap-4">
        <div>
            <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">
                Fecha de Corte
            </label>
            <input type="date" name="as_of_date"
                   value="{{ request('as_of_date', date('Y-m-d')) }}"
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
$sections     = $data['sections']      ?? [];
$assets       = collect($sections)->firstWhere('class_code', '1');
$liabilities  = collect($sections)->firstWhere('class_code', '2');
$equity       = collect($sections)->firstWhere('class_code', '3');
$totalAssets  = $data['total_assets']   ?? 0;
$totalLiabPat = $data['total_liab_pat'] ?? 0;
$periodResult = $data['period_result']  ?? 0;
$balanced     = $data['balanced']       ?? false;
$asOf         = request('as_of_date', date('Y-m-d'));

$totalEquity     = ($equity['total'] ?? 0) + $periodResult;
$totalLiabilities = $liabilities['total'] ?? 0;

$fmt = fn($v) => '$' . number_format(abs($v), 0, ',', '.');
@endphp

{{-- ── Título del reporte ──────────────────────────────────────────── --}}
<div class="bg-white border border-ink/10 mb-0">
    <div class="px-5 py-4 border-b border-ink/10 flex items-center justify-between">
        <div>
            <p class="text-[13px] font-bold text-ink uppercase tracking-wider">Estado de Situación Financiera</p>
            <p class="text-[11px] text-ink/40 mt-0.5">
                Al
                <span class="font-mono font-semibold text-ink/60">{{ \Carbon\Carbon::parse($asOf)->translatedFormat('d \d\e F \d\e Y') }}</span>
            </p>
        </div>
        <div class="flex items-center gap-2">
            @if($balanced)
                <span class="flex items-center gap-1.5 text-[10px] font-bold text-sage bg-sage/10 border border-sage/20 px-2.5 py-1">
                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                    CUADRADO
                </span>
            @else
                <span class="flex items-center gap-1.5 text-[10px] font-bold text-red-600 bg-red-50 border border-red-200 px-2.5 py-1">
                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                    DESCUADRE
                </span>
            @endif
        </div>
    </div>
</div>

{{-- ── Tabla 3 columnas ─────────────────────────────────────────────── --}}
<div class="bg-white border border-t-0 border-ink/10 overflow-hidden">
    <div class="grid grid-cols-3 divide-x divide-ink/10">

        {{-- ── ACTIVOS ────────────────────────────────────────────── --}}
        <div class="flex flex-col">
            <div class="px-4 py-2.5 bg-forest/90 flex items-center justify-between sticky top-0">
                <span class="text-[10px] font-bold uppercase tracking-widest text-white">
                    {{ $assets['class_name'] ?? 'Activos' }}
                </span>
                <span class="text-[10px] font-mono text-mint/60">Clase 1</span>
            </div>
            <div class="flex-1">
                @if($assets)
                    @include('contable::reports._balance_tree', ['nodes' => $assets['nodes'], 'depth' => 0])
                @endif
            </div>
            <div class="px-4 py-2.5 bg-forest/10 border-t-2 border-ink/15 flex items-center justify-between mt-auto">
                <span class="text-[10px] font-bold uppercase tracking-wider text-ink/60">Total Activos</span>
                <span class="font-mono font-bold text-[12px] text-ink">{{ $fmt($totalAssets) }}</span>
            </div>
        </div>

        {{-- ── PASIVOS ─────────────────────────────────────────────── --}}
        <div class="flex flex-col">
            <div class="px-4 py-2.5 bg-forest/90 flex items-center justify-between sticky top-0">
                <span class="text-[10px] font-bold uppercase tracking-widest text-white">
                    {{ $liabilities['class_name'] ?? 'Pasivos' }}
                </span>
                <span class="text-[10px] font-mono text-mint/60">Clase 2</span>
            </div>
            <div class="flex-1">
                @if($liabilities)
                    @include('contable::reports._balance_tree', ['nodes' => $liabilities['nodes'], 'depth' => 0])
                @endif
            </div>
            <div class="px-4 py-2.5 bg-forest/10 border-t-2 border-ink/15 flex items-center justify-between mt-auto">
                <span class="text-[10px] font-bold uppercase tracking-wider text-ink/60">Total Pasivos</span>
                <span class="font-mono font-bold text-[12px] text-ink">{{ $fmt($totalLiabilities) }}</span>
            </div>
        </div>

        {{-- ── PATRIMONIO ───────────────────────────────────────────── --}}
        <div class="flex flex-col">
            <div class="px-4 py-2.5 bg-forest/90 flex items-center justify-between sticky top-0">
                <span class="text-[10px] font-bold uppercase tracking-widest text-white">
                    {{ $equity['class_name'] ?? 'Patrimonio' }}
                </span>
                <span class="text-[10px] font-mono text-mint/60">Clase 3</span>
            </div>
            <div class="flex-1">
                @if($equity)
                    @include('contable::reports._balance_tree', ['nodes' => $equity['nodes'], 'depth' => 0])
                @endif
                {{-- Resultado del período --}}
                @if($periodResult != 0)
                <div class="flex items-center justify-between px-4 py-2 border-b border-ink/5
                            {{ $periodResult >= 0 ? 'bg-sage/5' : 'bg-red-50/50' }}">
                    <span class="flex items-center gap-2 text-[11px] font-semibold
                                 {{ $periodResult >= 0 ? 'text-sage' : 'text-red-600' }}">
                        <span class="w-3 h-3 shrink-0"></span>
                        <span class="font-mono text-[10px] text-ink/30">—</span>
                        {{ $periodResult >= 0 ? 'Resultado del período' : 'Pérdida del período' }}
                    </span>
                    <span class="font-mono text-[11px] font-bold shrink-0 ml-2
                                 {{ $periodResult >= 0 ? 'text-sage' : 'text-red-600' }}">
                        {{ $periodResult < 0 ? '(' : '' }}{{ $fmt($periodResult) }}{{ $periodResult < 0 ? ')' : '' }}
                    </span>
                </div>
                @endif
            </div>
            <div class="px-4 py-2.5 bg-forest/10 border-t-2 border-ink/15 flex items-center justify-between mt-auto">
                <span class="text-[10px] font-bold uppercase tracking-wider text-ink/60">Total Patrimonio</span>
                <span class="font-mono font-bold text-[12px] text-ink">{{ $fmt($totalEquity) }}</span>
            </div>
        </div>

    </div>

    {{-- ── Fila de cuadre ──────────────────────────────────────────── --}}
    <div class="border-t-2 {{ $balanced ? 'border-sage/40 bg-sage/5' : 'border-red-300 bg-red-50' }}
                grid grid-cols-3 divide-x divide-ink/10">
        <div class="px-4 py-3 flex items-center justify-between">
            <span class="text-[10px] font-bold uppercase tracking-wider text-ink/40">Total Activos</span>
            <span class="font-mono font-bold text-[12px] text-ink">{{ $fmt($totalAssets) }}</span>
        </div>
        <div class="px-4 py-3 flex items-center justify-between col-span-2">
            <div class="flex items-center gap-2">
                @if($balanced)
                    <svg class="w-3.5 h-3.5 text-sage shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-sage">Total Pasivo + Patrimonio</span>
                @else
                    <svg class="w-3.5 h-3.5 text-red-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-red-600">Descuadre detectado</span>
                @endif
            </div>
            <span class="font-mono font-bold text-[12px] {{ $balanced ? 'text-ink' : 'text-red-600' }}">
                {{ $fmt($totalLiabPat) }}
            </span>
        </div>
    </div>
</div>

@else
{{-- Sin datos --}}
<div class="bg-white border border-ink/10 px-8 py-16 text-center">
    <svg class="w-10 h-10 text-ink/15 mx-auto mb-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
    </svg>
    <p class="text-[12px] text-ink/30">Selecciona una fecha de corte para generar el reporte.</p>
</div>
@endisset

@endsection
