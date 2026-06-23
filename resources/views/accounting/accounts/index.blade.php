@extends('contable::layouts.app')

@section('title', 'Plan de Cuentas')
@section('page-title', 'Plan de Cuentas')
@section('breadcrumb') Plan de Cuentas @endsection

@section('header-actions')
    <a href="{{ route('contable.accounts.create') }}"
       class="flex items-center gap-2 px-3 py-1.5 bg-sage text-white text-xs font-semibold
              uppercase tracking-wide hover:bg-forest transition-colors">
        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Nueva Cuenta
    </a>
@endsection

@section('content')

@php
$rootsJson = $roots->map(fn ($a) => [
    'id'           => $a->id,
    'code'         => $a->code,
    'name'         => $a->name,
    'nature'       => $a->nature->value,
    'account_type' => $a->account_type->value,
    'active'       => $a->active,
    'has_children' => $a->children_count > 0,
    'class_id'     => $a->class_id,
    'class_name'   => $a->class?->name,
    'edit_url'    => route('contable.accounts.edit', $a),
    'delete_url'  => route('contable.accounts.delete', $a),
]);
@endphp

{{-- ── Skeleton (visible hasta que Alpine inicialice) ──────────────── --}}
<div id="acct-skeleton" class="bg-white border border-ink/10">
    <div class="flex items-center justify-between px-4 py-3 border-b border-ink/10 bg-forest">
        <div class="h-3 w-32 bg-white/20 animate-pulse"></div>
        <div class="h-3 w-20 bg-white/10 animate-pulse"></div>
    </div>
    <div class="px-4 py-3 border-b border-ink/10 flex gap-3">
        <div class="h-8 w-64 bg-ink/5 animate-pulse"></div>
        <div class="h-8 w-24 bg-ink/5 animate-pulse"></div>
    </div>
    @for($i = 0; $i < 9; $i++)
    <div class="flex items-center gap-4 px-4 py-3 border-b border-ink/5">
        <div class="w-4 h-4 bg-ink/5 animate-pulse shrink-0"></div>
        <div class="h-3 w-12 bg-ink/10 animate-pulse font-mono"></div>
        <div class="h-3 bg-ink/5 animate-pulse" style="width: {{ rand(120, 240) }}px"></div>
        <div class="ml-auto h-3 w-16 bg-ink/5 animate-pulse"></div>
    </div>
    @endfor
</div>

{{-- ── Árbol de cuentas ─────────────────────────────────────────────── --}}
<div x-cloak
     x-data="accountTree({
         roots:       {{ Js::from($rootsJson) }},
         childrenUrl: '{{ url(config('contable.web_prefix', 'accounting') . '/accounts') }}',
         csrf:        '{{ csrf_token() }}'
     })"
     x-init="boot()"
     class="bg-white border border-ink/10">

    {{-- Cabecera --}}
    <div class="flex items-center justify-between px-4 py-3 border-b border-ink/10 bg-forest">
        <p class="text-xs font-bold text-white uppercase tracking-widest">
            Plan de Cuentas
        </p>
        <span class="text-[10px] text-mint/60 font-mono">
            {{ $totalCount }} cuentas
        </span>
    </div>

    {{-- Búsqueda --}}
    <div class="flex items-center gap-3 px-4 py-2.5 border-b border-ink/10 bg-cream/50">
        <div class="relative flex-1 max-w-xs">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-ink/30"
                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" x-model.debounce.300ms="search"
                   placeholder="Buscar por código o nombre..."
                   class="w-full pl-8 pr-3 py-1.5 text-[12px] border border-ink/15
                          focus:outline-none focus:border-sage bg-white text-ink placeholder-ink/30">
        </div>
    </div>

    {{-- Cabecera de columnas --}}
    <div class="flex items-center bg-ink/5 border-b border-ink/10 text-[10px] font-bold text-ink/40 uppercase tracking-wider">
        <div class="flex-1 px-4 py-2.5">Código / Nombre</div>
        <div class="w-24 px-3 py-2.5 hidden md:block">Naturaleza</div>
        <div class="w-24 px-3 py-2.5 hidden lg:block">Tipo</div>
        <div class="w-16 px-3 py-2.5 text-center">Estado</div>
        <div class="w-32 px-4 py-2.5 text-right">Acciones</div>
    </div>

    {{-- Filas del árbol --}}
    <div class="divide-y divide-ink/5">

        {{-- Estado vacío búsqueda --}}
        <div x-show="search && filteredNodes.length === 0"
             class="px-4 py-10 text-center text-ink/30 text-sm">
            Sin resultados para "<span x-text="search" class="font-mono"></span>"
        </div>

        {{-- Nodos --}}
        <template x-for="node in (search ? filteredNodes : visibleNodes)" :key="node.id">
            <div>

            {{-- Separador de clase --}}
            <div x-show="node.type === 'separator'"
                 class="flex items-center gap-3 px-4 py-2 bg-forest/90 border-t-2 border-ink/20">
                <span class="font-mono font-bold text-mint text-[10px]" x-text="node.code"></span>
                <span class="text-[10px] font-bold uppercase tracking-widest text-mint/70" x-text="node.label"></span>
            </div>

            {{-- Fila de cuenta --}}
            <div x-show="node.type !== 'separator'"
                 class="flex items-center hover:bg-cream transition-colors group text-[12px]"
                 :class="{ 'bg-sage/5': node.open }">

                {{-- Código + Nombre (con indentación) --}}
                <div class="flex-1 flex items-center gap-2 px-4 py-2.5 min-w-0"
                     :style="`padding-left: calc(1rem + ${(search ? 0 : node.level) * 20}px)`">

                    {{-- Botón expandir/colapsar --}}
                    <button @click="toggle(node)"
                            class="w-5 h-5 flex items-center justify-center shrink-0 transition-colors"
                            :class="node.has_children ? 'text-sage hover:text-forest cursor-pointer' : 'text-transparent cursor-default'">
                        <svg x-show="!node.loading"
                             class="w-3.5 h-3.5 transition-transform duration-150"
                             :class="{ 'rotate-90': node.open }"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                        {{-- Spinner de carga --}}
                        <svg x-show="node.loading" class="w-3.5 h-3.5 animate-spin text-sage"
                             viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                        </svg>
                    </button>

                    {{-- Código --}}
                    <span class="font-mono font-bold text-forest shrink-0 text-[11px] w-16"
                          x-text="node.code"></span>

                    {{-- Nombre --}}
                    <span class="text-ink/80 truncate" x-text="node.name"
                          :class="{ 'font-semibold text-ink': node.level === 0 }"></span>
                </div>

                {{-- Naturaleza --}}
                <div class="w-24 px-3 py-2.5 hidden md:block">
                    <span class="text-[10px] font-bold px-1.5 py-0.5 border"
                          :class="node.nature === 'DEBIT'
                              ? 'bg-blue-50 text-blue-600 border-blue-200'
                              : 'bg-sage/10 text-sage border-sage/20'"
                          x-text="node.nature === 'DEBIT' ? 'Débito' : 'Crédito'">
                    </span>
                </div>

                {{-- Tipo --}}
                <div class="w-24 px-3 py-2.5 hidden lg:block">
                    <span class="text-[10px] font-bold px-1.5 py-0.5 border"
                          :class="node.account_type === 'MOVIMIENTO'
                              ? 'bg-sage/10 text-sage border-sage/20'
                              : 'bg-ink/5 text-ink/40 border-ink/10'"
                          x-text="node.account_type === 'MOVIMIENTO' ? 'Movimiento' : 'Mayor'">
                    </span>
                </div>

                {{-- Estado --}}
                <div class="w-16 px-3 py-2.5 flex justify-center">
                    <span class="inline-block w-2 h-2"
                          :class="node.active ? 'bg-sage' : 'bg-ink/20'">
                    </span>
                </div>

                {{-- Acciones --}}
                <div class="w-32 px-4 py-2.5 flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <a :href="node.edit_url"
                       class="text-[10px] font-bold px-2 py-1 text-ink/50 hover:text-sage hover:bg-sage/10 transition-colors uppercase tracking-wide">
                        Editar
                    </a>
                    <a :href="node.delete_url"
                       class="text-[10px] font-bold px-2 py-1 text-red-400 hover:text-red-600 hover:bg-red-50 transition-colors uppercase tracking-wide">
                        Elim.
                    </a>
                </div>
            </div>{{-- /fila cuenta --}}

            </div>{{-- /wrapper --}}
        </template>

    </div>

    {{-- Pie --}}
    <div class="px-4 py-2.5 border-t border-ink/10 bg-cream/50 flex items-center justify-between">
        <p class="text-[10px] text-ink/30 font-mono">
            <span x-text="visibleNodes.length"></span> nodos visibles de {{ $totalCount }} cuentas totales
        </p>
        <p x-show="loadingCount > 0" class="text-[10px] text-sage animate-pulse">
            Cargando...
        </p>
    </div>

</div>

@push('scripts')
<script>
function accountTree({ roots, childrenUrl, csrf }) {
    return {
        // Árbol visible: array plano de nodos actualmente mostrados
        nodes: [],

        // Todos los nodos cargados, indexados por id
        nodeMap: {},

        search: '',
        loadingCount: 0,

        get visibleNodes() {
            return this.nodes;
        },

        get filteredNodes() {
            if (!this.search) return [];
            const q = this.search.toLowerCase();
            return Object.values(this.nodeMap).filter(n =>
                n.code.toLowerCase().includes(q) ||
                n.name.toLowerCase().includes(q)
            );
        },

        boot() {
            const sk = document.getElementById('acct-skeleton');
            if (sk) sk.remove();

            let lastClassId = null;
            roots.forEach(r => {
                // Insertar separador de clase cuando cambia el class_id
                if (r.class_id !== lastClassId) {
                    this.nodes.push({
                        id:    `sep-${r.class_id}`,
                        type:  'separator',
                        code:  r.code,
                        label: r.class_name || `Clase ${r.class_id}`,
                        level: -1,
                    });
                    lastClassId = r.class_id;
                }
                const node = this.makeNode(r, null, 0);
                this.nodeMap[node.id] = node;
                this.nodes.push(node);
            });
        },

        makeNode(data, parentId, level) {
            return {
                ...data,
                parentId,
                level,
                open:           false,
                loading:        false,
                childrenLoaded: false,
                childrenIds:    [],
            };
        },

        async toggle(node) {
            if (!node.has_children) return;

            if (node.open) {
                this.collapse(node);
            } else {
                await this.expand(node);
            }
        },

        async expand(node) {
            if (!node.childrenLoaded) {
                node.loading = true;
                this.loadingCount++;
                try {
                    const resp = await fetch(`${childrenUrl}/${node.id}/children`, {
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
                    });
                    const data = await resp.json();

                    const children = data.map(c => {
                        const child = this.makeNode(c, node.id, node.level + 1);
                        this.nodeMap[child.id] = child;
                        return child;
                    });

                    node.childrenIds    = children.map(c => c.id);
                    node.childrenLoaded = true;

                    // Insertar hijos después del nodo actual
                    const idx = this.nodes.indexOf(node);
                    this.nodes.splice(idx + 1, 0, ...children);
                } finally {
                    node.loading = false;
                    this.loadingCount--;
                }
            } else {
                // Restaurar hijos ya cargados
                const idx     = this.nodes.indexOf(node);
                const children = node.childrenIds.map(id => this.nodeMap[id]);
                this.nodes.splice(idx + 1, 0, ...children);
            }

            node.open = true;
        },

        collapse(node) {
            node.open = false;
            const idx = this.nodes.indexOf(node);
            let count = 0;
            let i     = idx + 1;

            while (i < this.nodes.length) {
                const n = this.nodes[i];
                // Los separadores no son descendientes de ningún nodo
                if (n.type === 'separator' || !this.isDescendant(n, node.id)) break;
                if (n.open) n.open = false;
                i++;
                count++;
            }
            this.nodes.splice(idx + 1, count);
        },

        isDescendant(node, ancestorId) {
            if (node.type === 'separator') return false;
            let current = node;
            while (current && current.parentId) {
                if (current.parentId === ancestorId) return true;
                current = this.nodeMap[current.parentId];
            }
            return false;
        },

    };
}
</script>
@endpush

@endsection
