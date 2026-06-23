@php $depth = $depth ?? 0; @endphp

@foreach($nodes as $node)
@php
$hasChildren = !empty($node['children']);
$balance     = $node['balance'] ?? 0;
$paddingLeft = 16 + $depth * 14;
@endphp

<div x-data="{ open: false }">

    {{-- Fila --}}
    <div class="flex items-center justify-between border-b border-ink/5 transition-colors select-none"
         style="padding: 5px 14px 5px {{ $paddingLeft }}px"
         @if($hasChildren) @click="open = !open" @endif
         :class="$hasChildren ? 'cursor-pointer hover:bg-cream/70' : 'hover:bg-cream/30'">

        <span class="flex items-center gap-1.5 min-w-0
                     {{ $depth === 0 ? 'text-[11px] font-bold text-ink' : ($depth === 1 ? 'text-[11px] font-semibold text-ink/80' : 'text-[11px] text-ink/60') }}">

            {{-- Ícono expandir --}}
            @if($hasChildren)
                <svg class="w-3 h-3 shrink-0 text-sage transition-transform duration-150"
                     :class="open ? 'rotate-90' : ''"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            @else
                <span class="w-3 h-3 shrink-0 inline-block"></span>
            @endif

            {{-- Código --}}
            @if($node['code'])
                <span class="font-mono text-[10px] text-sage/70 shrink-0">{{ $node['code'] }}</span>
            @endif

            {{-- Nombre --}}
            <span class="truncate">{{ $node['name'] }}</span>
        </span>

        {{-- Saldo --}}
        @if($balance != 0)
            <span class="font-mono text-[11px] shrink-0 ml-3
                         {{ $depth === 0 ? 'font-bold text-ink' : 'font-semibold text-ink/70' }}">
                {{ number_format(abs($balance), 0, ',', '.') }}
            </span>
        @endif
    </div>

    {{-- Hijos --}}
    @if($hasChildren)
        <div x-show="open" x-cloak class="border-l border-ink/8" style="margin-left: {{ $paddingLeft + 6 }}px">
            @include('contable::reports._balance_tree', ['nodes' => $node['children'], 'depth' => $depth + 1])
        </div>
    @endif

</div>
@endforeach
