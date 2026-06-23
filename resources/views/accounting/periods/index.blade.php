@extends('contable::layouts.app')

@section('title', 'Periodos Contables')
@section('page-title', 'Periodos Contables')
@section('breadcrumb') Periodos @endsection

@section('content')

{{-- ── Banner ejercicio cerrado ─────────────────────────────────────── --}}
@if($fiscalYear?->isClosed())
<div class="mb-4 flex items-center justify-between bg-ink/5 border border-ink/15 px-4 py-3">
    <div class="flex items-center gap-3">
        <svg class="w-4 h-4 shrink-0 text-ink/40" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
        <div>
            <p class="text-[12px] font-semibold text-ink">Ejercicio {{ $year }} cerrado</p>
            <p class="text-[10px] text-ink/40 mt-0.5">
                Comprobante: <span class="font-mono">{{ $fiscalYear->closingEntry?->entry_number ?? '—' }}</span>
                · {{ $fiscalYear->closed_at?->format('d/m/Y H:i') }}
            </p>
        </div>
    </div>
    <form method="POST" action="{{ route('contable.fiscal-years.reopen', $year) }}"
          onsubmit="return confirm('¿Reabrir el ejercicio {{ $year }}?\n\nSe anulará el comprobante {{ $fiscalYear->closingEntry?->entry_number }} y todos los períodos quedarán abiertos nuevamente.')">
        @csrf @method('PATCH')
        <button type="submit"
            class="text-[10px] font-bold text-ink/50 hover:text-ink uppercase tracking-wide border border-ink/15 hover:border-ink/30 px-3 py-1.5 transition-colors">
            Reabrir ejercicio
        </button>
    </form>
</div>

@elseif($canClose)
<div class="mb-4 flex items-center justify-between bg-amber-50 border border-amber-200 px-4 py-3">
    <div class="flex items-center gap-3">
        <svg class="w-4 h-4 shrink-0 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-[12px] text-amber-800 font-medium">
            El ejercicio {{ $year }} tiene períodos registrados y puede cerrarse.
        </p>
    </div>
    <a href="{{ route('contable.fiscal-years.close.form', $year) }}"
       class="text-[10px] font-bold text-amber-700 uppercase tracking-wide border border-amber-300 hover:bg-amber-100 px-3 py-1.5 transition-colors">
        Cerrar ejercicio {{ $year }}
    </a>
</div>
@endif

{{-- ── Selector de año ──────────────────────────────────────────────── --}}
<div class="flex items-center gap-2 mb-5">
    <form method="GET" action="{{ route('contable.periods.index') }}" class="inline-flex items-center gap-2">
        <input type="number" name="year" value="{{ $year }}"
               min="2000" max="2100" onchange="this.form.submit()"
               class="border border-ink/15 px-3 py-2 text-[12px] font-mono font-semibold text-ink bg-white
                      focus:outline-none focus:border-sage w-24 appearance-none">
    </form>
    <a href="{{ route('contable.periods.index', ['year' => $year - 1]) }}"
       class="p-2 text-ink/30 hover:text-ink hover:bg-cream transition-colors" title="Año anterior">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <a href="{{ route('contable.periods.index', ['year' => $year + 1]) }}"
       class="p-2 text-ink/30 hover:text-ink hover:bg-cream transition-colors" title="Año siguiente">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
    </a>
</div>

{{-- ── Alertas ───────────────────────────────────────────────────────── --}}
@if(session('success'))
<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
     class="mb-4 flex items-center gap-3 bg-sage/5 border border-sage/20 text-sage text-[12px] px-4 py-3">
    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
    </svg>
    {{ session('success') }}
</div>
@endif

@if($errors->has('period'))
<div class="mb-4 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 text-[12px] px-4 py-3">
    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
    </svg>
    {{ $errors->first('period') }}
</div>
@endif

{{-- ── Tabla de periodos ─────────────────────────────────────────────── --}}
<div class="bg-white border border-ink/10 overflow-hidden">

    <div class="px-4 py-2.5 bg-forest flex items-center justify-between">
        <p class="text-[10px] font-bold uppercase tracking-widest text-white">Periodos {{ $year }}</p>
        <span class="text-[10px] font-mono text-mint/60">Ejercicio fiscal</span>
    </div>

    <table class="w-full">
        <thead>
            <tr class="bg-ink/5 border-b border-ink/10">
                <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 w-12">Nº</th>
                <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5">Mes</th>
                <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 hidden md:table-cell">Apertura</th>
                <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 hidden md:table-cell">Cierre</th>
                <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 w-40">Estado</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-ink/5">
            @foreach($months as $item)
            @php $period = $item['period']; @endphp
            <tr class="hover:bg-cream transition-colors">

                <td class="px-4 py-3 font-mono text-[10px] text-ink/30">
                    {{ str_pad($item['number'], 2, '0', STR_PAD_LEFT) }}
                </td>

                <td class="px-4 py-3">
                    @if($period)
                        <span class="text-[12px] font-medium text-ink">{{ $item['name'] }}</span>
                        <span class="ml-2 text-[10px] text-ink/30 font-mono">
                            {{ $period->start_date->format('d/m') }}–{{ $period->end_date->format('d/m/Y') }}
                        </span>
                    @else
                        <span class="text-[12px] text-ink/30">{{ $item['name'] }}</span>
                    @endif
                </td>

                <td class="px-4 py-3 hidden md:table-cell">
                    @if($period && $period->opened_at)
                        <span class="text-[11px] text-ink/50">{{ $period->opened_at->format('d/m/Y') }}</span>
                        <span class="text-[10px] text-ink/30 font-mono ml-1">{{ $period->opened_at->format('H:i') }}</span>
                    @else
                        <span class="text-[10px] text-ink/20">—</span>
                    @endif
                </td>

                <td class="px-4 py-3 hidden md:table-cell">
                    @if($period && $period->closed_at)
                        <span class="text-[11px] text-ink/50">{{ $period->closed_at->format('d/m/Y') }}</span>
                        <span class="text-[10px] text-ink/30 font-mono ml-1">{{ $period->closed_at->format('H:i') }}</span>
                    @else
                        <span class="text-[10px] text-ink/20">—</span>
                    @endif
                </td>

                <td class="px-4 py-3">
                    @if(! $period)
                        <form method="POST" action="{{ route('contable.periods.store') }}">
                            @csrf
                            <input type="hidden" name="year"  value="{{ $year }}">
                            <input type="hidden" name="month" value="{{ $item['number'] }}">
                            <button type="submit"
                                class="text-[10px] font-bold text-ink/40 uppercase tracking-wide border border-ink/15
                                       hover:text-sage hover:border-sage/30 hover:bg-sage/5 px-2.5 py-1 transition-colors">
                                + Abrir
                            </button>
                        </form>

                    @elseif($period->isOpen())
                        <div x-data="{ open: false }" class="relative inline-block">
                            <button @click="open = !open" @click.outside="open = false"
                                class="inline-flex items-center gap-2 text-[10px] font-bold text-sage uppercase tracking-wide
                                       bg-sage/10 border border-sage/20 px-2.5 py-1 transition-colors select-none">
                                <span class="w-1.5 h-1.5 bg-sage inline-block"></span>
                                Abierto
                                <svg class="w-3 h-3 transition-transform" :class="open && 'rotate-180'"
                                     fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open" x-cloak
                                 class="absolute left-0 top-full mt-0.5 z-20 bg-white border border-ink/10 min-w-[140px]">
                                <form method="POST" action="{{ route('contable.periods.close', $period) }}"
                                      onsubmit="return confirm('¿Cerrar el periodo {{ $period->name }}?\n\nNo se podrán registrar nuevos comprobantes en este período.')">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                        class="w-full text-left flex items-center gap-2 px-3 py-2 text-[10px] font-bold
                                               text-ink/50 uppercase tracking-wide hover:bg-cream transition-colors">
                                        <svg class="w-3 h-3 text-ink/30" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                        Cerrar período
                                    </button>
                                </form>
                            </div>
                        </div>

                    @elseif($period->isLocked())
                        <div class="inline-flex items-center gap-2 text-[10px] font-bold text-ink/30 uppercase tracking-wide
                                    bg-ink/5 border border-ink/10 px-2.5 py-1 select-none"
                             title="Bloqueado por cierre del ejercicio {{ $period->year }}">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Bloqueado
                        </div>

                    @else
                        <div x-data="{ open: false }" class="relative inline-block">
                            <button @click="open = !open" @click.outside="open = false"
                                class="inline-flex items-center gap-2 text-[10px] font-bold text-ink/40 uppercase tracking-wide
                                       bg-ink/5 border border-ink/10 px-2.5 py-1 transition-colors select-none">
                                <span class="w-1.5 h-1.5 bg-ink/25 inline-block"></span>
                                Cerrado
                                <svg class="w-3 h-3 transition-transform" :class="open && 'rotate-180'"
                                     fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open" x-cloak
                                 class="absolute left-0 top-full mt-0.5 z-20 bg-white border border-ink/10 min-w-[140px]">
                                <form method="POST" action="{{ route('contable.periods.open', $period) }}"
                                      onsubmit="return confirm('¿Reabrir el periodo {{ $period->name }}?\n\nSe permitirá registrar comprobantes nuevamente.')">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                        class="w-full text-left flex items-center gap-2 px-3 py-2 text-[10px] font-bold
                                               text-ink/50 uppercase tracking-wide hover:bg-cream transition-colors">
                                        <svg class="w-3 h-3 text-ink/30" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                                        </svg>
                                        Reabrir período
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif
                </td>

            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection
