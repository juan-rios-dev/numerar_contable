@extends('contable::layouts.app')

@section('title', "Cierre del Ejercicio {$year}")
@section('page-title', "Cierre del Ejercicio {$year}")
@section('breadcrumb')
    <a href="{{ route('contable.periods.index', ['year' => $year]) }}"
       class="hover:text-sage transition-colors">Periodos {{ $year }}</a>
    <span class="mx-1 text-ink/30">/</span> Cierre de Ejercicio
@endsection

@section('content')

@if($errors->any())
<div class="mb-5 flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 text-[12px] px-4 py-3">
    <svg class="w-3.5 h-3.5 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
    </svg>
    <div>{{ $errors->first() }}</div>
</div>
@endif

<div x-data="fiscalYearClose()" class="max-w-3xl space-y-4">

    {{-- ── Resultado del año ──────────────────────────────────────────── --}}
    <div class="bg-white border border-ink/10">

        <div class="px-4 py-2.5 bg-forest border-b border-ink/10">
            <p class="text-[10px] font-bold uppercase tracking-widest text-white">
                Resultado del ejercicio {{ $year }}
            </p>
        </div>

        <div class="px-6 py-5">
        @if($result['lines'])

            {{-- Totales --}}
            <div class="flex items-center gap-10 mb-5">
                <div>
                    <p class="text-[10px] font-bold text-ink/40 uppercase tracking-wider mb-1">Total ingresos</p>
                    <p class="text-[18px] font-semibold text-sage">
                        $ {{ number_format($result['income'], 0, ',', '.') }}
                    </p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-ink/40 uppercase tracking-wider mb-1">Total gastos y costos</p>
                    <p class="text-[18px] font-semibold text-red-600">
                        $ {{ number_format($result['expenses'], 0, ',', '.') }}
                    </p>
                </div>
            </div>

            {{-- Resultado neto --}}
            <div class="border-2 {{ $result['is_profit'] ? 'border-sage/30 bg-sage/5' : 'border-red-200 bg-red-50' }} px-6 py-4 text-center">
                <p class="text-[10px] font-bold uppercase tracking-widest {{ $result['is_profit'] ? 'text-sage' : 'text-red-500' }} mb-2">
                    {{ $result['is_profit'] ? 'Utilidad' : 'Pérdida' }} {{ $year }}
                </p>
                <p class="text-[30px] font-bold {{ $result['is_profit'] ? 'text-forest' : 'text-red-700' }} font-mono">
                    $ {{ number_format(abs($result['net']), 0, ',', '.') }}
                </p>
            </div>

            {{-- Detalle de líneas colapsable --}}
            <div x-data="{ open: false }" class="mt-4">
                <button @click="open = !open"
                    class="flex items-center gap-2 text-[10px] font-bold text-ink/40 uppercase tracking-wide hover:text-ink transition-colors">
                    <svg class="w-3 h-3 transition-transform" :class="open && 'rotate-90'"
                         fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                    Ver {{ count($result['lines']) }} líneas del asiento de cierre
                </button>

                <div x-show="open" x-cloak class="mt-3 border border-ink/10 overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-ink/5 border-b border-ink/10">
                            <tr>
                                <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2">Cuenta</th>
                                <th class="text-right text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2 w-32">Débito</th>
                                <th class="text-right text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2 w-32">Crédito</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink/5">
                            @foreach($result['lines'] as $line)
                            <tr class="hover:bg-cream">
                                <td class="px-4 py-2.5 text-[12px] text-ink">
                                    <span class="font-mono text-[10px] text-ink/30 mr-2">{{ $line['account']->code }}</span>
                                    {{ $line['account']->name }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono text-[11px] {{ $line['debit'] > 0 ? 'text-ink' : 'text-ink/20' }}">
                                    {{ $line['debit'] > 0 ? number_format($line['debit'], 0, ',', '.') : '—' }}
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono text-[11px] {{ $line['credit'] > 0 ? 'text-ink' : 'text-ink/20' }}">
                                    {{ $line['credit'] > 0 ? number_format($line['credit'], 0, ',', '.') : '—' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        @else
            <div class="text-center py-8 text-ink/30">
                <p class="text-[12px]">No hay movimientos en cuentas de resultado para el año {{ $year }}.</p>
                <p class="text-[11px] mt-1">Verifique que existan comprobantes contabilizados en este período.</p>
            </div>
        @endif
        </div>
    </div>

    {{-- ── Formulario de cierre ───────────────────────────────────────── --}}
    <form method="POST" action="{{ route('contable.fiscal-years.close', $year) }}"
          onsubmit="return confirm('¿Confirmar el cierre del ejercicio {{ $year }}?\n\nEsta acción generará el asiento de cierre y bloqueará todos los períodos del año.')">
        @csrf

        <div class="bg-white border border-ink/10">

            <div class="px-4 py-2.5 bg-forest border-b border-ink/10">
                <p class="text-[10px] font-bold uppercase tracking-widest text-white">Configuración del comprobante de cierre</p>
            </div>

            <div class="px-6 py-5 space-y-5">

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                    {{-- Tipo de comprobante --}}
                    <div>
                        <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">
                            Tipo de comprobante <span class="text-red-500">*</span>
                        </label>
                        <select name="entry_type" x-model="selectedType" @change="updateSequences()" required
                            class="w-full border border-ink/15 px-3 py-2.5 text-[12px] text-ink bg-white
                                   focus:outline-none focus:border-sage">
                            @foreach($closingTypes as $type)
                                <option value="{{ $type->code }}" data-sequences="{{ $type->sequences->toJson() }}">
                                    {{ $type->code }} — {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                        @if($closingTypes->isEmpty())
                            <p class="text-[11px] text-red-600 mt-1">
                                No hay tipos de comprobante de cierre configurados.
                                <a href="{{ route('contable.entry-types.index') }}" class="underline">Configurar</a>
                            </p>
                        @endif
                    </div>

                    {{-- Numeración --}}
                    <div>
                        <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">
                            Numeración <span class="text-red-500">*</span>
                        </label>
                        <select name="entry_sequence_id" x-model="selectedSequenceId"
                            class="w-full border border-ink/15 px-3 py-2.5 text-[12px] text-ink bg-white
                                   focus:outline-none focus:border-sage">
                            <template x-for="seq in sequences" :key="seq.id">
                                <option :value="seq.id" x-text="seq.name + ' (' + seq.prefix + ')'"></option>
                            </template>
                        </select>
                    </div>
                </div>

                {{-- Cuenta de patrimonio --}}
                <div>
                    <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">
                        Cuenta de patrimonio <span class="text-red-500">*</span>
                    </label>
                    <p class="text-[11px] text-ink/40 mb-2">
                        Cuenta clase 3 donde se registrará la
                        {{ $result['is_profit'] ? 'utilidad' : 'pérdida' }} del ejercicio.
                    </p>

                    <div class="relative mb-2">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-ink/30"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" placeholder="Buscar cuenta..." x-model="search"
                            class="w-full pl-8 pr-3 py-2 border border-ink/15 text-[12px] text-ink bg-white
                                   focus:outline-none focus:border-sage">
                    </div>

                    <div class="border border-ink/10 overflow-hidden max-h-60 overflow-y-auto">
                        <table class="w-full">
                            <thead class="bg-ink/5 border-b border-ink/10 sticky top-0">
                                <tr>
                                    <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-3 py-2 w-8"></th>
                                    <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-3 py-2">Cuenta Patrimonio</th>
                                    <th class="text-right text-[10px] font-bold text-ink/40 uppercase tracking-wider px-3 py-2 w-36">Saldo actual</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-ink/5">
                                @forelse($equityAccounts as $account)
                                <tr class="hover:bg-cream transition-colors cursor-pointer"
                                    x-show="!search || '{{ strtolower($account->code . ' ' . $account->name) }}'.includes(search.toLowerCase())"
                                    @click="selectedAccount = {{ $account->id }}"
                                    :class="selectedAccount == {{ $account->id }} ? 'bg-sage/5' : ''">
                                    <td class="px-3 py-2.5 text-center">
                                        <input type="radio" name="equity_account_id"
                                               value="{{ $account->id }}"
                                               x-model="selectedAccount"
                                               class="accent-sage">
                                    </td>
                                    <td class="px-3 py-2.5 text-[12px] text-ink">
                                        <span class="font-mono text-[10px] text-ink/30 mr-2">{{ $account->code }}</span>
                                        {{ $account->name }}
                                    </td>
                                    <td class="px-3 py-2.5 text-right font-mono text-[11px] text-ink/50">
                                        $ {{ number_format($account->ownBalance(), 0, ',', '.') }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-6 text-center text-[12px] text-ink/30">
                                        No hay cuentas de movimiento en clase 3 (Patrimonio).
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @error('equity_account_id')
                        <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

            </div>
        </div>

        {{-- Acciones --}}
        <div class="flex items-center justify-between mt-4">
            <a href="{{ route('contable.periods.index', ['year' => $year]) }}"
               class="text-[11px] font-bold text-ink/40 uppercase tracking-wide hover:text-ink transition-colors">
                ← Cancelar
            </a>
            <button type="submit"
                :disabled="!selectedAccount || !selectedType{{ $closingTypes->isEmpty() ? ' || true' : '' }}"
                class="flex items-center gap-2 bg-sage hover:bg-forest disabled:opacity-40 disabled:cursor-not-allowed
                       text-white text-[11px] font-bold uppercase tracking-wide px-5 py-2.5 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Cerrar ejercicio {{ $year }}
            </button>
        </div>

    </form>

</div>
@endsection

@push('scripts')
<script>
function fiscalYearClose() {
    return {
        selectedType: '{{ $closingTypes->first()?->code ?? '' }}',
        selectedSequenceId: {{ $closingTypes->first()?->sequences->first()?->id ?? 'null' }},
        sequences: {!! $closingTypes->first()?->sequences->toJson() ?? '[]' !!},
        selectedAccount: null,
        search: '',
        updateSequences() {
            const select = document.querySelector('select[name="entry_type"]');
            const opt    = select.options[select.selectedIndex];
            const seqs   = JSON.parse(opt.dataset.sequences || '[]');
            this.sequences          = seqs;
            this.selectedSequenceId = seqs.length ? seqs[0].id : null;
        },
    };
}
</script>
@endpush
