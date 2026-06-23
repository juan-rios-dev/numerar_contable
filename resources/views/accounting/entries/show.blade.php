@extends('contable::layouts.app')

@section('title', $entry->entry_number)
@section('page-title', $entry->entry_number)
@section('breadcrumb')
    <a href="{{ route('contable.entries.index') }}" class="hover:text-sage transition-colors">Comprobantes</a>
    <span class="mx-1 text-ink/30">/</span> <span class="font-mono">{{ $entry->entry_number }}</span>
@endsection

@section('header-actions')
@if($canEdit)
    <a href="{{ route('contable.entries.edit', $entry) }}"
        class="flex items-center gap-2 text-[11px] font-bold text-ink/60 border border-ink/20
               hover:text-ink hover:border-ink/40 px-3 py-1.5 uppercase tracking-wide transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
        </svg>
        Editar
    </a>
@endif
@if($canVoid)
    <form method="POST" action="{{ route('contable.entries.void', $entry) }}" class="inline"
          onsubmit="return confirm('¿Anular este comprobante?\n\nEl asiento quedará registrado como ANULADO y no tendrá efecto contable. Esta acción no se puede revertir.')">
        @csrf @method('PATCH')
        <button type="submit"
            class="flex items-center gap-2 text-[11px] font-bold text-amber-600 border border-amber-300
                   hover:bg-amber-50 px-3 py-1.5 uppercase tracking-wide transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
            </svg>
            Anular
        </button>
    </form>
@endif
@if($canDelete)
    <form method="POST" action="{{ route('contable.entries.destroy', $entry) }}" class="inline"
          onsubmit="return confirm('¿Eliminar permanentemente este comprobante?\n\nSe eliminarán el encabezado y todas sus líneas. Esta acción no tiene vuelta atrás.')">
        @csrf @method('DELETE')
        <button type="submit"
            class="flex items-center gap-2 text-[11px] font-bold text-red-500 border border-red-200
                   hover:bg-red-50 px-3 py-1.5 uppercase tracking-wide transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Eliminar
        </button>
    </form>
@endif
@endsection

@section('content')
<div class="space-y-4 {{ $entry->isVoided() ? 'opacity-70' : '' }}">

    {{-- ── Cabecera del comprobante ──────────────────────────────────── --}}
    <div class="bg-white border border-ink/10 overflow-hidden">

        <div class="px-4 py-2.5 bg-forest flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="font-mono text-[11px] font-bold text-mint/60 bg-white/10 px-2 py-0.5">
                    {{ $entry->entry_type }}
                </span>
                <span class="text-[11px] text-mint/70">
                    {{ $entry->entryType?->name ?? $entry->entry_type }}
                </span>
            </div>
            <div class="flex items-center gap-3">
                @if($entry->isPosted())
                    <span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-sage bg-white/10 px-2 py-0.5">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Contabilizado
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-red-300 bg-white/10 px-2 py-0.5">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                        Anulado
                    </span>
                @endif
                <span class="font-mono text-[16px] font-bold text-white">{{ $entry->entry_number }}</span>
            </div>
        </div>

        <div class="px-6 py-5 grid grid-cols-2 md:grid-cols-4 gap-6">
            <div>
                <p class="text-[10px] font-bold text-ink/40 uppercase tracking-wider mb-1">Fecha</p>
                <p class="text-[13px] font-mono font-medium text-ink">{{ $entry->date->format('d/m/Y') }}</p>
            </div>
            <div>
                <p class="text-[10px] font-bold text-ink/40 uppercase tracking-wider mb-1">Periodo</p>
                <p class="text-[13px] font-medium text-ink">{{ $entry->period?->name ?? '—' }}</p>
            </div>
            <div class="col-span-2">
                <p class="text-[10px] font-bold text-ink/40 uppercase tracking-wider mb-1">Descripción</p>
                <p class="text-[12px] text-ink/70">{{ $entry->description ?: '—' }}</p>
            </div>
        </div>
    </div>

    {{-- ── Líneas del comprobante ────────────────────────────────────── --}}
    <div class="bg-white border border-ink/10 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-ink/5 border-b border-ink/10">
                        <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5">Cuenta</th>
                        <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5">Tercero</th>
                        <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5">Descripción</th>
                        <th class="text-right text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 w-36">Débito</th>
                        <th class="text-right text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 w-36">Crédito</th>
                        <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 w-36">C. Costo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink/5">
                    @foreach($entry->lines as $line)
                    <tr class="hover:bg-cream {{ $entry->isVoided() ? 'line-through' : '' }}">
                        <td class="px-4 py-3">
                            <p class="text-[12px] font-medium {{ $entry->isVoided() ? 'text-ink/30' : 'text-ink' }}">
                                {{ $line->account?->name }}
                            </p>
                            <p class="font-mono text-[10px] text-ink/30">{{ $line->account?->code }}</p>
                        </td>
                        <td class="px-4 py-3 text-[11px] {{ $entry->isVoided() ? 'text-ink/30' : 'text-ink/50' }}">
                            {{ $line->third_party_name ?: '—' }}
                        </td>
                        <td class="px-4 py-3 text-[11px] {{ $entry->isVoided() ? 'text-ink/30' : 'text-ink/50' }}">
                            {{ $line->description ?: '—' }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-[11px] font-medium
                                   {{ $entry->isVoided() ? 'text-ink/25' : ($line->debit > 0 ? 'text-ink' : 'text-ink/15') }}">
                            {{ $line->debit > 0 ? number_format($line->debit, 0, ',', '.') : '' }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-[11px] font-medium
                                   {{ $entry->isVoided() ? 'text-ink/25' : ($line->credit > 0 ? 'text-ink/70' : 'text-ink/15') }}">
                            {{ $line->credit > 0 ? number_format($line->credit, 0, ',', '.') : '' }}
                        </td>
                        <td class="px-4 py-3 text-[11px] {{ $entry->isVoided() ? 'text-ink/25' : 'text-ink/40' }}">
                            {{ $line->costCenter?->name ?? '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-forest text-white border-t-2 border-ink/20">
                        <td colspan="3" class="px-4 py-3 text-[10px] font-bold uppercase tracking-widest text-mint/60 text-right">
                            Totales
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-[12px] font-bold">
                            {{ number_format($entry->totalDebits(), 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-[12px] font-bold">
                            {{ number_format($entry->totalCredits(), 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3">
                            @if($entry->isBalanced())
                                <span class="text-[10px] font-bold text-sage uppercase tracking-wide">✓ Cuadra</span>
                            @endif
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>
@endsection
