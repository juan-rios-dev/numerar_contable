@php
$depth       = $depth ?? 0;
$hasChildren = !empty($node['children']);
$pl          = 12 + $depth * 16;
@endphp

<div x-data="{ open: false }">
    <div class="flex items-stretch border-b border-ink/5 transition-colors
                {{ $hasChildren ? 'cursor-pointer select-none hover:bg-cream' : 'hover:bg-cream/60' }}
                {{ $depth === 0 ? 'bg-ink/3' : '' }}"
         @if($hasChildren) @click="open = !open" @endif>

        {{-- Nombre --}}
        <div class="flex items-center gap-1.5 flex-1 min-w-0 py-2 pr-2"
             style="padding-left: {{ $pl }}px">
            @if($hasChildren)
                <svg class="w-3 h-3 shrink-0 text-ink/30 transition-transform"
                     :class="open ? 'rotate-90' : ''"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            @else
                <span class="w-3 h-3 shrink-0 inline-block"></span>
            @endif
            @if($node['code'])
                <span class="font-mono text-[10px] text-ink/30 shrink-0">{{ $node['code'] }}</span>
            @endif
            <span class="text-[12px] truncate
                {{ $depth === 0 ? 'font-semibold text-ink' : ($depth === 1 ? 'font-medium text-ink/80' : 'text-ink/60') }}">
                {{ $node['name'] }}
            </span>
        </div>

        {{-- S.I. Débito --}}
        <div class="w-28 shrink-0 py-2 px-3 text-right font-mono text-[11px] border-l-2 border-ink/8
                    {{ $node['opening_debit'] > 0 ? 'text-ink' : 'text-ink/15' }}">
            {{ $node['opening_debit'] > 0 ? number_format($node['opening_debit'], 0, ',', '.') : '—' }}
        </div>
        {{-- S.I. Crédito --}}
        <div class="w-28 shrink-0 py-2 px-3 text-right font-mono text-[11px] border-l border-ink/8
                    {{ $node['opening_credit'] > 0 ? 'text-ink' : 'text-ink/15' }}">
            {{ $node['opening_credit'] > 0 ? number_format($node['opening_credit'], 0, ',', '.') : '—' }}
        </div>

        {{-- Mov. Débito --}}
        <div class="w-28 shrink-0 py-2 px-3 text-right font-mono text-[11px] border-l-2 border-ink/8
                    {{ $node['period_debit'] > 0 ? 'text-ink/70' : 'text-ink/15' }}">
            {{ $node['period_debit'] > 0 ? number_format($node['period_debit'], 0, ',', '.') : '—' }}
        </div>
        {{-- Mov. Crédito --}}
        <div class="w-28 shrink-0 py-2 px-3 text-right font-mono text-[11px] border-l border-ink/8
                    {{ $node['period_credit'] > 0 ? 'text-ink/70' : 'text-ink/15' }}">
            {{ $node['period_credit'] > 0 ? number_format($node['period_credit'], 0, ',', '.') : '—' }}
        </div>

        {{-- S.F. Débito --}}
        <div class="w-28 shrink-0 py-2 px-3 text-right font-mono text-[11px] border-l-2 border-ink/8
                    {{ $node['closing_debit'] > 0 ? 'font-semibold text-ink' : 'text-ink/15' }}">
            {{ $node['closing_debit'] > 0 ? number_format($node['closing_debit'], 0, ',', '.') : '—' }}
        </div>
        {{-- S.F. Crédito --}}
        <div class="w-28 shrink-0 py-2 px-3 text-right font-mono text-[11px] border-l border-ink/8
                    {{ $node['closing_credit'] > 0 ? 'font-semibold text-ink' : 'text-ink/15' }}">
            {{ $node['closing_credit'] > 0 ? number_format($node['closing_credit'], 0, ',', '.') : '—' }}
        </div>
    </div>

    @if($hasChildren)
    <div x-show="open" x-cloak class="border-l border-ink/10" style="margin-left: {{ $pl + 6 }}px">
        @foreach($node['children'] as $child)
            @include('contable::reports._trial_balance_row', ['node' => $child, 'depth' => $depth + 1])
        @endforeach
    </div>
    @endif
</div>
