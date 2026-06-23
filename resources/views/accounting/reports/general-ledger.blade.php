@extends('contable::layouts.app')

@section('title', 'Libro Mayor')
@section('page-title', 'Libro Mayor')
@section('breadcrumb') Reportes <span class="mx-1 text-ink/30">/</span> Libro Mayor @endsection

@section('content')

{{-- ── Filtros ─────────────────────────────────────────────────────── --}}
<form method="GET" class="bg-white border border-ink/10 px-5 py-4 mb-5">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 items-end">
        <div class="md:col-span-2">
            <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">
                Cuenta <span class="text-red-500">*</span>
            </label>
            <select name="account_id" required
                class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white
                       focus:outline-none focus:border-sage">
                <option value="">Seleccionar cuenta...</option>
                @foreach($accounts as $acc)
                    <option value="{{ $acc->id }}" {{ request('account_id') == $acc->id ? 'selected' : '' }}>
                        [{{ $acc->code }}] {{ $acc->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">Desde</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white
                       focus:outline-none focus:border-sage">
        </div>
        <div>
            <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">Hasta</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white
                       focus:outline-none focus:border-sage">
        </div>
    </div>
    <div class="mt-4 flex justify-end">
        <button type="submit"
            class="px-5 py-2 bg-sage text-white text-[11px] font-bold uppercase tracking-wide
                   hover:bg-forest transition-colors">
            Generar Libro Mayor
        </button>
    </div>
</form>

@isset($data)
@php
$account   = $data['account'];
$movements = $data['movements'] ?? [];
@endphp

<div class="bg-white border border-ink/10 overflow-hidden">

    {{-- Cabecera de la cuenta --}}
    <div class="px-4 py-2.5 bg-forest flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="font-mono text-[10px] text-mint/50">{{ $account['code'] }}</span>
            <span class="text-[12px] font-bold text-white">{{ $account['name'] }}</span>
            <span class="text-[10px] font-bold text-mint/60 bg-white/10 px-2 py-0.5">
                {{ $account['nature'] === 'DEBIT' ? 'Débito' : 'Crédito' }}
            </span>
        </div>
        <span class="text-[10px] font-mono text-mint/50">
            {{ \Carbon\Carbon::parse($data['date_from'])->format('d/m/Y') }}
            — {{ \Carbon\Carbon::parse($data['date_to'])->format('d/m/Y') }}
        </span>
    </div>

    <table class="w-full">
        <thead>
            <tr class="bg-ink/5 border-b border-ink/10">
                <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 w-24">Fecha</th>
                <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 w-32">Comprobante</th>
                <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5">Descripción</th>
                <th class="text-right text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 w-32">Débito</th>
                <th class="text-right text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 w-32">Crédito</th>
                <th class="text-right text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 w-36">Saldo</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-ink/5">
            <tr class="bg-ink/3">
                <td colspan="5" class="px-4 py-2.5 text-[10px] font-bold text-ink/40 uppercase tracking-wider">Saldo Anterior</td>
                <td class="px-4 py-2.5 text-right font-mono text-[11px] font-semibold text-ink">
                    {{ number_format($data['opening_balance'], 0, ',', '.') }}
                </td>
            </tr>
            @forelse($movements as $mov)
            <tr class="hover:bg-cream">
                <td class="px-4 py-2.5 font-mono text-[11px] text-ink/50">{{ \Carbon\Carbon::parse($mov['date'])->format('d/m/Y') }}</td>
                <td class="px-4 py-2.5">
                    <span class="font-mono text-[11px] font-bold text-sage">{{ $mov['entry_number'] }}</span>
                </td>
                <td class="px-4 py-2.5 text-[11px] text-ink/60">{{ $mov['description'] }}</td>
                <td class="px-4 py-2.5 text-right font-mono text-[11px] {{ $mov['debit'] > 0 ? 'text-ink' : 'text-ink/15' }}">
                    {{ $mov['debit'] > 0 ? number_format($mov['debit'], 0, ',', '.') : '—' }}
                </td>
                <td class="px-4 py-2.5 text-right font-mono text-[11px] {{ $mov['credit'] > 0 ? 'text-ink/70' : 'text-ink/15' }}">
                    {{ $mov['credit'] > 0 ? number_format($mov['credit'], 0, ',', '.') : '—' }}
                </td>
                <td class="px-4 py-2.5 text-right font-mono text-[11px] font-semibold text-ink">
                    {{ number_format($mov['balance'], 0, ',', '.') }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-4 py-8 text-center text-[12px] text-ink/30">
                    Sin movimientos en el período.
                </td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="bg-forest text-white font-bold">
                <td colspan="3" class="px-4 py-3 text-[10px] uppercase tracking-wider text-mint/70">Totales período</td>
                <td class="px-4 py-3 text-right font-mono text-[11px]">{{ number_format($data['period_debit'], 0, ',', '.') }}</td>
                <td class="px-4 py-3 text-right font-mono text-[11px]">{{ number_format($data['period_credit'], 0, ',', '.') }}</td>
                <td class="px-4 py-3 text-right font-mono text-[11px]">{{ number_format($data['closing_balance'], 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
</div>

@else
<div class="border border-dashed border-ink/15 p-12 text-center text-[12px] text-ink/30">
    Selecciona una cuenta y un rango de fechas para generar el libro mayor.
</div>
@endisset

@endsection
