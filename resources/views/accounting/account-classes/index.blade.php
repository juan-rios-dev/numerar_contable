@extends('contable::layouts.app')

@section('title', 'Clases Contables')
@section('page-title', 'Clases Contables')
@section('breadcrumb') Clases Contables @endsection

@section('content')

{{-- Aviso informativo --}}
<div class="mb-4 flex items-start gap-3 bg-sage/8 border border-sage/20 px-4 py-3 text-[11px] text-ink/60">
    <svg class="w-4 h-4 shrink-0 mt-0.5 text-sage" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <span>Las clases contables son el eje principal del PUC colombiano. Son inmutables y no pueden modificarse ni eliminarse.</span>
</div>

<div class="bg-white border border-ink/10 overflow-hidden">

    {{-- Cabecera --}}
    <div class="px-4 py-2.5 bg-forest flex items-center justify-between">
        <p class="text-[10px] font-bold uppercase tracking-widest text-white">Plan Único de Cuentas · Clases</p>
        <span class="text-[10px] font-mono text-mint/60">{{ $classes->count() }} clases</span>
    </div>

    {{-- Tabla --}}
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-ink/5 border-b border-ink/10">
                    <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 w-20">Código</th>
                    <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5">Nombre</th>
                    <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 w-28">Naturaleza</th>
                    <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 w-20 hidden sm:table-cell">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink/5">
                @forelse($classes as $class)
                <tr class="hover:bg-cream transition-colors">
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center justify-center w-7 h-7 bg-forest text-mint font-bold text-[12px]">
                            {{ $class->code }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-[12px] font-semibold text-ink">{{ $class->name }}</td>
                    <td class="px-4 py-3">
                        @if($class->nature->value === 'DEBIT')
                            <span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-forest bg-sage/10 border border-sage/20 px-2 py-0.5">
                                <span class="w-1.5 h-1.5 bg-sage inline-block"></span> Débito
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-ink/60 bg-ink/5 border border-ink/10 px-2 py-0.5">
                                <span class="w-1.5 h-1.5 bg-ink/30 inline-block"></span> Crédito
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 hidden sm:table-cell">
                        <span class="w-2 h-2 inline-block {{ $class->active ? 'bg-sage' : 'bg-ink/20' }}"></span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-12 text-center text-[12px] text-ink/30">
                        No hay clases contables registradas.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
