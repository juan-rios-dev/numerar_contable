@extends('contable::layouts.app')

@section('title', 'Editar ' . $entry->entry_number)
@section('page-title', 'Editar: ' . $entry->entry_number)
@section('breadcrumb')
    <a href="{{ route('contable.entries.index') }}">Comprobantes</a>
    <span class="mx-1 text-ink/30">/</span>
    <a href="{{ route('contable.entries.show', $entry) }}">{{ $entry->entry_number }}</a>
    <span class="mx-1 text-ink/30">/</span> Editar
@endsection

@section('header-actions')
    <a href="{{ route('contable.entries.show', $entry) }}"
       class="flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wide
              text-ink/50 border border-ink/15 hover:border-ink/40 hover:text-ink transition-colors">
        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
        </svg>
        Cancelar
    </a>
@endsection

@section('content')
@php
$existingLines = $entry->lines->map(fn($l) => [
    'id'               => $l->id,
    'account_id'       => $l->account_id,
    'accountLabel'     => '',
    'description'      => $l->description,
    'debit'            => (float) $l->debit,
    'credit'           => (float) $l->credit,
    'third_party_ref'  => $l->third_party_type && $l->third_party_id
        ? $l->third_party_type . '|' . $l->third_party_id
        : '',
    'third_party_type' => $l->third_party_type ?? '',
    'third_party_id'   => $l->third_party_id  ?? '',
    'cost_center_id'   => $l->cost_center_id  ?? '',
])->values()->toArray();
@endphp

<div x-data="entryForm(
    {{ Js::from($accounts) }},
    {{ Js::from($costCenters) }},
    {{ Js::from($existingLines) }}
)" class="space-y-4">

<form method="POST" action="{{ route('contable.entries.update', $entry) }}">
@csrf @method('PUT')

{{-- ── Cabecera ─────────────────────────────────────────────────────── --}}
<div class="bg-white border border-ink/10">
    <div class="px-5 py-3 border-b border-ink/10 bg-forest">
        <p class="text-[10px] font-bold uppercase tracking-widest text-mint/70">Información del Comprobante</p>
    </div>
    <div class="p-5">
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
            {{-- Tipo (sólo lectura) --}}
            <div>
                <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">Tipo</label>
                <div class="w-full border border-ink/10 px-3 py-2 text-[12px] bg-ink/3 text-ink/60 font-mono">
                    {{ $entry->entry_type }} — {{ $entry->entryType?->name ?? $entry->entry_type }}
                </div>
            </div>
            {{-- Fecha --}}
            <div>
                <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">
                    Fecha <span class="text-red-400">*</span>
                </label>
                <input type="date" name="date" value="{{ $entry->date->format('Y-m-d') }}" required
                    class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white
                           focus:outline-none focus:border-sage">
            </div>
            {{-- Número (sólo lectura) --}}
            <div>
                <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">N° Comprobante</label>
                <div class="w-full border border-ink/10 px-3 py-2 text-[12px] bg-ink/3 text-ink/60 font-mono">
                    {{ $entry->entry_number }}
                </div>
            </div>
        </div>
        {{-- Glosa --}}
        <div>
            <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">
                Descripción / Glosa
            </label>
            <input type="text" name="description" value="{{ $entry->description }}" maxlength="500"
                class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white
                       focus:outline-none focus:border-sage placeholder-ink/25">
        </div>
    </div>
</div>

{{-- ── Movimientos ──────────────────────────────────────────────────── --}}
<div class="bg-white border border-ink/10">

    <div class="px-5 py-3 border-b border-ink/10 bg-forest flex items-center justify-between">
        <p class="text-[10px] font-bold uppercase tracking-widest text-mint/70">Movimientos Contables</p>
        <div class="flex items-center gap-3">
            <template x-if="isBalanced">
                <span class="flex items-center gap-1.5 text-[10px] font-bold text-sage bg-sage/20 px-2.5 py-1">
                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                    BALANCEADO
                </span>
            </template>
            <template x-if="!isBalanced">
                <span class="flex items-center gap-1.5 text-[10px] font-bold text-amber-400 bg-amber-400/20 px-2.5 py-1">
                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                    DIF. <span x-text="formatCurrency(Math.abs(totalDebits - totalCredits))"></span>
                </span>
            </template>
            <button type="button" @click="addLine()"
                class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wide
                       text-white/80 hover:text-white border border-white/20 hover:border-white/50
                       px-2.5 py-1 transition-colors">
                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Agregar Línea
            </button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-[11px] border-collapse" style="min-width: 900px">
            <thead>
                <tr class="border-b-2 border-ink/15 bg-ink/4">
                    <th class="text-left text-[9px] font-bold text-ink/40 uppercase tracking-wider px-3 py-2.5">Cuenta</th>
                    <th class="text-left text-[9px] font-bold text-ink/40 uppercase tracking-wider px-3 py-2.5 w-40">Glosa</th>
                    <th class="text-right text-[9px] font-bold text-ink/40 uppercase tracking-wider px-3 py-2.5 w-28">Débito</th>
                    <th class="text-right text-[9px] font-bold text-ink/40 uppercase tracking-wider px-3 py-2.5 w-28">Crédito</th>
                    <th class="text-left text-[9px] font-bold text-ink/40 uppercase tracking-wider px-3 py-2.5 w-44">Tercero</th>
                    <th class="text-left text-[9px] font-bold text-ink/40 uppercase tracking-wider px-3 py-2.5 w-36">C. Costo</th>
                    <th class="w-9"></th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(line, i) in lines" :key="line.id">
                    <tr class="border-b border-ink/8 hover:bg-cream/60 transition-colors">

                        {{-- Cuenta (combobox) --}}
                        <td class="px-0 py-0 border-r border-ink/8 min-w-56">
                            <div x-data="accountCombo(line)"
                                 x-init="q = line.accountLabel || ''"
                                 class="relative">
                                <input type="hidden" :name="`lines[${i}][account_id]`" :value="line.account_id">
                                <div class="flex items-center">
                                    <input type="text"
                                           x-model="q"
                                           @focus="openDrop($el)"
                                           @input="open = true; if (!q) clearSel(line)"
                                           @keydown.escape="open = false"
                                           @blur.debounce.120ms="open = false"
                                           @keydown.enter.prevent="pickFirst(line)"
                                           :placeholder="line.account_id ? '' : 'Buscar código o nombre...'"
                                           class="flex-1 px-3 py-2 text-[11px] bg-transparent focus:outline-none
                                                  placeholder-ink/20 font-mono"
                                           :class="line.account_id ? 'text-forest font-bold' : 'text-ink'">
                                    <button x-show="line.account_id" type="button"
                                            @mousedown.prevent="clearSel(line)"
                                            class="px-2 text-ink/25 hover:text-red-400 shrink-0">
                                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                                <div x-show="open"
                                     x-cloak
                                     :style="dropStyle"
                                     class="bg-white border border-sage shadow-xl max-h-60 overflow-y-auto"
                                     style="position:fixed;z-index:9999;min-width:300px">
                                    <template x-for="acc in results()" :key="acc.id">
                                        <div @mousedown.prevent="pick(line, acc)"
                                             class="flex items-baseline gap-2 px-3 py-1.5 cursor-pointer
                                                    hover:bg-cream border-b border-ink/5 text-[11px]"
                                             :style="`padding-left: ${10 + acc.depth * 10}px`"
                                             :class="line.account_id == acc.id ? 'bg-sage/15 text-forest' : 'text-ink'">
                                            <span class="font-mono font-bold text-[10px] text-sage shrink-0"
                                                  x-text="`[${acc.code}]`"></span>
                                            <span class="truncate" x-text="acc.label.replace(/^\s*\[.*?\]\s*/, '')"></span>
                                        </div>
                                    </template>
                                    <div x-show="results().length === 0"
                                         class="px-3 py-4 text-center text-[11px] text-ink/30">
                                        Sin resultados para "<span class="font-mono" x-text="q"></span>"
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Glosa --}}
                        <td class="px-0 py-0 border-r border-ink/8">
                            <input type="text" :name="`lines[${i}][description]`" x-model="line.description"
                                   placeholder="Glosa..."
                                   class="w-full px-3 py-2 text-[11px] bg-transparent focus:outline-none
                                          focus:bg-sage/5 placeholder-ink/20 text-ink">
                        </td>

                        {{-- Débito --}}
                        <td class="px-0 py-0 border-r border-ink/8 w-28">
                            <input type="hidden" :name="`lines[${i}][debit]`" :value="line.debit">
                            <input type="text" data-field="debit"
                                   x-init="$el.value = line.debit > 0 ? formatNumber(line.debit) : ''"
                                   @keydown="skipSeparator($event)"
                                   @input="onAmountInput(line, 'debit', 'credit', $el)"
                                   @focus="$nextTick(() => $el.select())"
                                   @blur="$el.value = line.debit > 0 ? formatNumber(line.debit) : ''"
                                   placeholder="0"
                                   class="w-full px-3 py-2 text-[11px] text-right font-mono bg-transparent
                                          focus:outline-none focus:bg-blue-50 placeholder-ink/15 text-ink
                                          transition-colors"
                                   :class="line.debit > 0 ? 'text-blue-700 font-bold bg-blue-50/60' : ''">
                        </td>

                        {{-- Crédito --}}
                        <td class="px-0 py-0 border-r border-ink/8 w-28">
                            <input type="hidden" :name="`lines[${i}][credit]`" :value="line.credit">
                            <input type="text" data-field="credit"
                                   x-init="$el.value = line.credit > 0 ? formatNumber(line.credit) : ''"
                                   @keydown="skipSeparator($event)"
                                   @input="onAmountInput(line, 'credit', 'debit', $el)"
                                   @focus="$nextTick(() => $el.select())"
                                   @blur="$el.value = line.credit > 0 ? formatNumber(line.credit) : ''"
                                   placeholder="0"
                                   class="w-full px-3 py-2 text-[11px] text-right font-mono bg-transparent
                                          focus:outline-none focus:bg-emerald-50 placeholder-ink/15 text-ink
                                          transition-colors"
                                   :class="line.credit > 0 ? 'text-emerald-700 font-bold bg-emerald-50/60' : ''">
                        </td>

                        {{-- Tercero --}}
                        <td class="px-0 py-0 border-r border-ink/8 w-44">
                            <input type="hidden" :name="`lines[${i}][third_party_type]`" :value="line.third_party_type">
                            <input type="hidden" :name="`lines[${i}][third_party_id]`"   :value="line.third_party_id">
                            <select x-model="line.third_party_ref" @change="parseThirdParty(line)"
                                class="w-full px-3 py-2 text-[11px] bg-transparent focus:outline-none
                                       focus:bg-sage/5 text-ink border-0 cursor-pointer">
                                <option value="">— Ninguno</option>
                                @foreach($terceros as $group)
                                <optgroup label="{{ $group['label'] }}">
                                    @foreach($group['options'] as $opt)
                                    <option value="{{ $opt['ref'] }}"
                                        :selected="line.third_party_ref === '{{ $opt['ref'] }}'">
                                        {{ $opt['label'] }}
                                    </option>
                                    @endforeach
                                </optgroup>
                                @endforeach
                            </select>
                        </td>

                        {{-- Centro de Costo --}}
                        <td class="px-0 py-0 border-r border-ink/8 w-36">
                            <select :name="`lines[${i}][cost_center_id]`" x-model="line.cost_center_id"
                                class="w-full px-3 py-2 text-[11px] bg-transparent focus:outline-none
                                       focus:bg-sage/5 text-ink border-0 cursor-pointer">
                                <option value="">— Ninguno</option>
                                <template x-for="cc in costCenters" :key="cc.id">
                                    <option :value="cc.id" x-text="`[${cc.code}] ${cc.name}`"></option>
                                </template>
                            </select>
                        </td>

                        {{-- Eliminar --}}
                        <td class="px-2 py-0 text-center w-9">
                            <button type="button" @click="removeLine(i)"
                                    x-show="lines.length > 2"
                                    class="text-ink/20 hover:text-red-500 transition-colors p-1">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                </template>
            </tbody>

            <tfoot>
                <tr class="border-t-2 border-ink/15 bg-forest/8">
                    <td colspan="2" class="px-3 py-2.5 text-right text-[9px] font-bold text-ink/40 uppercase tracking-wider">
                        Totales
                    </td>
                    <td class="px-3 py-2.5 text-right font-mono font-bold text-[12px] w-28"
                        :class="totalDebits > 0 ? 'text-blue-700' : 'text-ink/20'"
                        x-text="totalDebits > 0 ? formatCurrency(totalDebits) : '—'"></td>
                    <td class="px-3 py-2.5 text-right font-mono font-bold text-[12px] w-28"
                        :class="totalCredits > 0 ? 'text-emerald-700' : 'text-ink/20'"
                        x-text="totalCredits > 0 ? formatCurrency(totalCredits) : '—'"></td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

{{-- ── Acciones ──────────────────────────────────────────────────────── --}}
<div class="flex items-center justify-end gap-3">
    <a href="{{ route('contable.entries.show', $entry) }}"
       class="px-4 py-2 text-[11px] font-bold uppercase tracking-wide
              text-ink/50 border border-ink/15 hover:border-ink/40 hover:text-ink transition-colors">
        Cancelar
    </a>
    <button type="submit"
            :disabled="!isBalanced || lines.length < 2"
            class="flex items-center gap-2 px-5 py-2 text-[11px] font-bold uppercase tracking-wide
                   text-white transition-colors"
            :class="isBalanced && lines.length >= 2
                ? 'bg-sage hover:bg-forest cursor-pointer'
                : 'bg-ink/20 cursor-not-allowed'">
        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
        </svg>
        Actualizar Comprobante
    </button>
</div>

</form>
</div>
@endsection

@push('scripts')
<script>
function entryForm(accounts, costCenters, existingLines) {
    return {
        accounts,
        costCenters,
        lines: [],

        init() {
            if (existingLines && existingLines.length) {
                this.lines = existingLines.map((l, idx) => ({
                    ...l,
                    id: idx,
                    accountLabel: l.account_id ? (this.accounts.find(a => a.id == l.account_id)?.label?.trim() || '') : '',
                }));
            } else {
                this.addLine();
                this.addLine();
            }
        },

        get totalDebits()  { return this.lines.reduce((s, l) => s + (+l.debit  || 0), 0); },
        get totalCredits() { return this.lines.reduce((s, l) => s + (+l.credit || 0), 0); },
        get isBalanced()   {
            return this.lines.length >= 2
                && Math.abs(this.totalDebits - this.totalCredits) < 0.001
                && this.totalDebits > 0;
        },

        addLine() {
            this.lines.push({
                id:              Date.now() + Math.random(),
                account_id:      '',
                accountLabel:    '',
                description:     '',
                debit:           0,
                credit:          0,
                third_party_ref:  '',
                third_party_type: '',
                third_party_id:   '',
                cost_center_id:   ''
            });
        },

        removeLine(i) { if (this.lines.length > 2) this.lines.splice(i, 1); },

        parseAmount(str) {
            return parseFloat(String(str || '').replace(/\./g, '').replace(',', '.')) || 0;
        },

        formatNumber(val) {
            if (!val) return '';
            return new Intl.NumberFormat('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(val);
        },

        formatLive(str) {
            if (!str) return '';
            const ci = str.indexOf(',');
            const intStr = (ci === -1 ? str : str.substring(0, ci)).replace(/\D/g, '');
            const decStr = ci === -1 ? null : str.substring(ci + 1).replace(/\D/g, '').substring(0, 2);
            if (!intStr && decStr === null) return '';
            const fmtInt = intStr
                ? new Intl.NumberFormat('es-CO', { useGrouping: true, maximumFractionDigits: 0 }).format(parseInt(intStr, 10))
                : '0';
            return decStr !== null ? `${fmtInt},${decStr}` : fmtInt;
        },

        skipSeparator(event) {
            const el = event.target, c = el.selectionStart;
            if (el.selectionStart !== el.selectionEnd) return;
            if (event.key === 'Backspace' && c > 0 && el.value[c - 1] === '.') {
                event.preventDefault(); el.setSelectionRange(c - 1, c - 1);
            }
            if (event.key === 'Delete' && c < el.value.length && el.value[c] === '.') {
                event.preventDefault(); el.setSelectionRange(c + 1, c + 1);
            }
        },

        onAmountInput(line, field, opposite, el) {
            const cursor = el.selectionStart;
            const charsB = el.value.substring(0, cursor).replace(/\./g, '').length;
            const fmt    = this.formatLive(el.value);
            line[field]  = this.parseAmount(fmt);
            if (line[field] > 0) {
                line[opposite] = 0;
                const opp = el.closest('tr')?.querySelector(`[data-field="${opposite}"]`);
                if (opp) opp.value = '';
            }
            if (el.value !== fmt) {
                el.value = fmt;
                let cnt = 0, pos = fmt.length;
                for (let i = 0; i < fmt.length; i++) {
                    if (fmt[i] !== '.') cnt++;
                    if (cnt === charsB) { pos = i + 1; break; }
                }
                el.setSelectionRange(pos, pos);
            }
        },

        parseThirdParty(line) {
            if (!line.third_party_ref) { line.third_party_type = ''; line.third_party_id = ''; return; }
            const sep = line.third_party_ref.lastIndexOf('|');
            line.third_party_type = line.third_party_ref.substring(0, sep);
            line.third_party_id   = line.third_party_ref.substring(sep + 1);
        },

        formatCurrency(val) {
            return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val || 0);
        },
    };
}

function accountCombo(line) {
    return {
        open:      false,
        q:         '',
        dropStyle: '',

        openDrop(el) {
            this.open = true;
            const r = el.getBoundingClientRect();
            this.dropStyle = `top:${r.bottom + 2}px;left:${r.left}px;width:${Math.max(r.width, 320)}px`;
        },

        results() {
            const s = this.q.trim().toLowerCase();
            if (!s) return this.accounts.slice(0, 30);
            return this.accounts.filter(a =>
                (a.code && a.code.toLowerCase().includes(s)) ||
                a.label.toLowerCase().includes(s)
            ).slice(0, 35);
        },

        pick(line, acc) {
            line.account_id   = acc.id;
            line.accountLabel = acc.label.trim();
            this.q            = acc.label.trim();
            this.open         = false;
        },

        pickFirst(line) {
            const res = this.results();
            if (res.length === 1) this.pick(line, res[0]);
        },

        clearSel(line) {
            line.account_id   = '';
            line.accountLabel = '';
            this.q            = '';
            this.open         = false;
        },
    };
}
</script>
@endpush
