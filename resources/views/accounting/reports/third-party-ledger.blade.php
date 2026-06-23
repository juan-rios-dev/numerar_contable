@extends('contable::layouts.app')

@section('title', 'Auxiliar por Tercero')
@section('page-title', 'Auxiliar por Tercero')
@section('breadcrumb') Reportes <span class="mx-1 text-ink/30">/</span> Auxiliar por Tercero @endsection

@section('content')
@php
$fmt = fn($v) => ($v < 0 ? '-' : '') . number_format(abs($v), 0, ',', '.');
@endphp

{{-- ── Filtros ─────────────────────────────────────────────────────── --}}
<form method="GET" class="bg-white border border-ink/10 px-5 py-4 mb-5">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 items-end">
        <div>
            <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">
                Desde <span class="text-red-500">*</span>
            </label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" required
                class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white focus:outline-none focus:border-sage">
        </div>
        <div>
            <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">
                Hasta <span class="text-red-500">*</span>
            </label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" required
                class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white focus:outline-none focus:border-sage">
        </div>
        <div>
            <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">Cuenta</label>
            <select name="account_id"
                class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white focus:outline-none focus:border-sage">
                <option value="">Todas las cuentas</option>
                @foreach($accounts as $acc)
                    <option value="{{ $acc->id }}" {{ request('account_id') == $acc->id ? 'selected' : '' }}>
                        [{{ $acc->code }}] {{ $acc->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">Tercero</label>
            <select name="third_party_ref"
                class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white focus:outline-none focus:border-sage">
                <option value="">Todos los terceros</option>
                @foreach($terceros as $group)
                    <optgroup label="{{ $group['label'] }}">
                        @foreach($group['options'] as $opt)
                            <option value="{{ $opt['ref'] }}" {{ request('third_party_ref') === $opt['ref'] ? 'selected' : '' }}>
                                {{ $opt['label'] }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>
    </div>
    <div class="mt-4 flex justify-end">
        <button type="submit"
            class="px-5 py-2 bg-sage text-white text-[11px] font-bold uppercase tracking-wide
                   hover:bg-forest transition-colors">
            Generar Reporte
        </button>
    </div>
</form>

@isset($data)
@if(count($data['accounts']) === 0)
<div class="bg-white border border-ink/10 p-12 text-center text-[12px] text-ink/30">
    No hay movimientos con terceros en el período seleccionado.
</div>
@else

<div class="bg-white border border-ink/10 overflow-hidden">

    <div class="px-4 py-2.5 bg-forest flex items-center justify-between">
        <p class="text-[10px] font-bold uppercase tracking-widest text-white">Auxiliar por Tercero</p>
        <span class="text-[10px] font-mono text-mint/50">
            {{ \Carbon\Carbon::parse($data['filters']['date_from'])->format('d/m/Y') }}
            — {{ \Carbon\Carbon::parse($data['filters']['date_to'])->format('d/m/Y') }}
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-ink/5 border-b border-ink/10">
                    <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5">Cuenta / Tercero</th>
                    <th class="text-right text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 w-36">Saldo Anterior</th>
                    <th class="text-right text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 w-32">Débito</th>
                    <th class="text-right text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 w-32">Crédito</th>
                    <th class="text-right text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 w-32">Neto</th>
                    <th class="text-right text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 w-36">Saldo Final</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['accounts'] as $account)

                {{-- Cabecera de cuenta --}}
                <tr class="bg-forest/80 text-white border-t border-white/10">
                    <td colspan="6" class="px-4 py-2">
                        <span class="font-mono text-[10px] text-mint/50 mr-2">{{ $account['account_code'] }}</span>
                        <span class="text-[12px] font-bold">{{ $account['account_name'] }}</span>
                    </td>
                </tr>

                {{-- Filas de terceros --}}
                @foreach($account['rows'] as $row)
                <tr class="hover:bg-cream border-t border-ink/5">
                    <td class="px-4 py-3 pl-10 text-[12px] text-ink">{{ $row['third_party_name'] }}</td>
                    <td class="px-4 py-3 text-right font-mono text-[11px] {{ $row['opening_balance'] < 0 ? 'text-red-600' : 'text-ink/60' }}">
                        {{ $fmt($row['opening_balance']) }}
                    </td>
                    <td class="px-4 py-3 text-right font-mono text-[11px] {{ $row['period_debit'] > 0 ? 'text-ink' : 'text-ink/20' }}">
                        {{ $row['period_debit'] > 0 ? $fmt($row['period_debit']) : '' }}
                    </td>
                    <td class="px-4 py-3 text-right font-mono text-[11px] {{ $row['period_credit'] > 0 ? 'text-ink/70' : 'text-ink/20' }}">
                        {{ $row['period_credit'] > 0 ? $fmt($row['period_credit']) : '' }}
                    </td>
                    <td class="px-4 py-3 text-right font-mono text-[11px] {{ $row['net'] < 0 ? 'text-red-600' : 'text-ink/60' }}">
                        {{ $row['net'] != 0 ? $fmt($row['net']) : '' }}
                    </td>
                    <td class="px-4 py-3 text-right font-mono text-[11px] font-semibold {{ $row['closing_balance'] < 0 ? 'text-red-600' : 'text-ink' }}">
                        {{ $fmt($row['closing_balance']) }}
                    </td>
                </tr>
                @endforeach

                {{-- Subtotal por cuenta --}}
                @php $sub = $account['subtotals']; @endphp
                <tr class="bg-ink/5 border-t border-ink/10">
                    <td class="px-4 py-2 pl-10 text-[10px] font-bold text-ink/50 uppercase tracking-wider">
                        Subtotal {{ $account['account_code'] }}
                    </td>
                    <td class="px-4 py-2 text-right font-mono text-[11px] font-semibold {{ $sub['opening'] < 0 ? 'text-red-600' : 'text-ink/70' }}">
                        {{ $fmt($sub['opening']) }}
                    </td>
                    <td class="px-4 py-2 text-right font-mono text-[11px] font-semibold {{ $sub['debit'] > 0 ? 'text-ink' : 'text-ink/20' }}">
                        {{ $sub['debit'] > 0 ? $fmt($sub['debit']) : '' }}
                    </td>
                    <td class="px-4 py-2 text-right font-mono text-[11px] font-semibold {{ $sub['credit'] > 0 ? 'text-ink/70' : 'text-ink/20' }}">
                        {{ $sub['credit'] > 0 ? $fmt($sub['credit']) : '' }}
                    </td>
                    <td class="px-4 py-2 text-right font-mono text-[11px] font-semibold {{ $sub['net'] < 0 ? 'text-red-600' : 'text-ink/70' }}">
                        {{ $sub['net'] != 0 ? $fmt($sub['net']) : '' }}
                    </td>
                    <td class="px-4 py-2 text-right font-mono text-[11px] font-bold {{ $sub['closing'] < 0 ? 'text-red-600' : 'text-ink' }}">
                        {{ $fmt($sub['closing']) }}
                    </td>
                </tr>

                @endforeach
            </tbody>

            {{-- Totales generales --}}
            @php $gt = $data['grand_totals']; @endphp
            <tfoot>
                <tr class="bg-forest text-white">
                    <td class="px-4 py-3 text-[10px] font-bold uppercase tracking-widest text-mint/70">Total General</td>
                    <td class="px-4 py-3 text-right font-mono text-[11px] font-bold">{{ $fmt($gt['opening']) }}</td>
                    <td class="px-4 py-3 text-right font-mono text-[11px] font-bold">{{ $gt['debit'] > 0 ? $fmt($gt['debit']) : '' }}</td>
                    <td class="px-4 py-3 text-right font-mono text-[11px] font-bold">{{ $gt['credit'] > 0 ? $fmt($gt['credit']) : '' }}</td>
                    <td class="px-4 py-3 text-right font-mono text-[11px] font-bold">{{ $gt['net'] != 0 ? $fmt($gt['net']) : '' }}</td>
                    <td class="px-4 py-3 text-right font-mono text-[11px] font-bold">{{ $fmt($gt['closing']) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@endif
@else
<div class="border border-dashed border-ink/15 p-12 text-center text-[12px] text-ink/30">
    Selecciona un rango de fechas para generar el auxiliar por tercero.
</div>
@endisset

@endsection
