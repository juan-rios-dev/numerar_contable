@extends('contable::layouts.app')

@section('title', 'Comprobantes')
@section('page-title', 'Comprobantes Contables')
@section('breadcrumb') Comprobantes @endsection

@section('header-actions')
<a href="{{ route('contable.entries.create') }}"
    class="flex items-center gap-2 bg-sage text-white text-[11px] font-bold px-3 py-1.5
           uppercase tracking-wide hover:bg-forest transition-colors">
    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
    </svg>
    Nuevo Comprobante
</a>
@endsection

@section('content')
@php
$typeLabel = [
    'CI'  => 'CI',  'CE'  => 'CE',  'CD'  => 'CD',
    'CA'  => 'CA',  'CC'  => 'CC',  'NC'  => 'NC',
    'CIE' => 'CIE',
];
@endphp

<div class="bg-white border border-ink/10 overflow-hidden">

    <div class="px-4 py-2.5 bg-forest flex items-center justify-between">
        <p class="text-[10px] font-bold uppercase tracking-widest text-white">Comprobantes Contables</p>
        <span class="text-[10px] font-mono text-mint/60">{{ $entries->total() }} registros</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-ink/5 border-b border-ink/10">
                    <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5">N° Comprobante</th>
                    <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 w-20">Tipo</th>
                    <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 w-28">Fecha</th>
                    <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5">Descripción</th>
                    <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 w-24 hidden md:table-cell">Periodo</th>
                    <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 w-32">Estado</th>
                    <th class="px-4 py-2.5 w-20"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink/5">
                @forelse($entries as $entry)
                <tr class="hover:bg-cream transition-colors group">

                    <td class="px-4 py-3">
                        <a href="{{ route('contable.entries.show', $entry) }}"
                           class="font-mono text-[11px] font-bold text-sage hover:text-forest transition-colors">
                            {{ $entry->entry_number }}
                        </a>
                    </td>

                    <td class="px-4 py-3">
                        <span class="font-mono text-[10px] font-bold px-2 py-0.5 bg-ink/5 border border-ink/10 text-ink/60">
                            {{ $entry->entry_type }}
                        </span>
                    </td>

                    <td class="px-4 py-3 font-mono text-[11px] text-ink/60">
                        {{ $entry->date->format('d/m/Y') }}
                    </td>

                    <td class="px-4 py-3 text-[12px] text-ink max-w-xs truncate">
                        {{ $entry->description }}
                    </td>

                    <td class="px-4 py-3 text-[11px] text-ink/40 hidden md:table-cell">
                        {{ $entry->period?->name ?? '—' }}
                    </td>

                    <td class="px-4 py-3">
                        @if($entry->status->isPosted())
                            <span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-sage bg-sage/10 border border-sage/20 px-2 py-0.5">
                                <span class="w-1.5 h-1.5 bg-sage inline-block"></span>
                                Contabilizado
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-red-600 bg-red-50 border border-red-200 px-2 py-0.5">
                                <span class="w-1.5 h-1.5 bg-red-400 inline-block"></span>
                                Anulado
                            </span>
                        @endif
                    </td>

                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <a href="{{ route('contable.entries.show', $entry) }}"
                               class="text-[10px] font-bold px-2 py-1 text-ink/50 hover:text-sage hover:bg-sage/10 transition-colors uppercase tracking-wide">
                                Ver
                            </a>
                            @if($entry->isVoided() && ! isset($closingEntryIds[$entry->id]) && ! ($entry->entryType?->is_closing) && ! ($entry->period?->isClosed() || $entry->period?->isLocked()))
                            <form method="POST" action="{{ route('contable.entries.destroy', $entry) }}" class="inline"
                                  onsubmit="return confirm('¿Eliminar permanentemente este comprobante?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="text-[10px] font-bold px-2 py-1 text-red-400 hover:text-red-600 hover:bg-red-50 transition-colors uppercase tracking-wide">
                                    Elim.
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-[12px] text-ink/30">
                        No hay comprobantes registrados.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($entries->hasPages())
    <div class="px-4 py-3 border-t border-ink/8 text-[12px]">
        {{ $entries->links() }}
    </div>
    @endif
</div>
@endsection
