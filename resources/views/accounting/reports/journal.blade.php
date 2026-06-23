@extends('contable::layouts.app')

@section('title', 'Libro Diario')
@section('page-title', 'Libro Diario')
@section('breadcrumb') Reportes <span class="mx-1 text-ink/30">/</span> Libro Diario @endsection

@section('content')
@include('contable::reports._filters')

@isset($data)
@php $entries = $data['entries'] ?? []; @endphp

@if(count($entries))
<div class="bg-white border border-ink/10 overflow-hidden mb-4">
    @foreach($entries as $entry)
    <div class="border-b border-ink/8 last:border-0">

        {{-- Cabecera del comprobante --}}
        <div class="px-4 py-2.5 bg-ink/4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="font-mono text-[11px] font-bold text-sage">{{ $entry['entry_number'] }}</span>
                <span class="text-[10px] font-mono text-ink/40">{{ \Carbon\Carbon::parse($entry['date'])->format('d/m/Y') }}</span>
                <span class="text-[11px] text-ink/60">{{ $entry['description'] }}</span>
            </div>
            <span class="text-[10px] font-bold font-mono text-ink/30 bg-ink/5 border border-ink/10 px-2 py-0.5">
                {{ $entry['entry_type'] }}
            </span>
        </div>

        {{-- Líneas --}}
        <table class="w-full">
            <tbody>
                @foreach($entry['lines'] as $line)
                <tr class="hover:bg-cream border-b border-ink/5 last:border-0">
                    <td class="px-8 py-2 font-mono text-[10px] text-ink/30 w-24">{{ $line['account_code'] }}</td>
                    <td class="px-3 py-2 text-[12px] text-ink">{{ $line['account_name'] }}</td>
                    <td class="px-3 py-2 text-[11px] text-ink/40">{{ $line['description'] }}</td>
                    <td class="px-4 py-2 text-right font-mono text-[11px] w-36
                               {{ $line['debit'] > 0 ? 'text-ink' : 'text-ink/15' }}">
                        {{ $line['debit'] > 0 ? number_format($line['debit'], 0, ',', '.') : '' }}
                    </td>
                    <td class="px-4 py-2 text-right font-mono text-[11px] w-36
                               {{ $line['credit'] > 0 ? 'text-ink/70' : 'text-ink/15' }}">
                        {{ $line['credit'] > 0 ? number_format($line['credit'], 0, ',', '.') : '' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach
</div>

<div class="bg-forest px-5 py-3 flex items-center justify-end gap-8">
    <div class="text-[11px] text-mint/70">
        Total Débitos:
        <span class="font-mono font-bold text-white ml-2">
            {{ number_format($data['total_debit'], 0, ',', '.') }}
        </span>
    </div>
    <div class="text-[11px] text-mint/70">
        Total Créditos:
        <span class="font-mono font-bold text-white ml-2">
            {{ number_format($data['total_credit'], 0, ',', '.') }}
        </span>
    </div>
</div>

@else
<div class="bg-white border border-ink/10 p-12 text-center text-[12px] text-ink/30">
    No hay comprobantes contabilizados en el rango seleccionado.
</div>
@endif

@else
<div class="border border-dashed border-ink/15 p-12 text-center text-[12px] text-ink/30">
    Selecciona un rango de fechas para generar el libro diario.
</div>
@endisset

@endsection
