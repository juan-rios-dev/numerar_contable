@extends('contable::layouts.app')

@section('title', 'Tipos de Comprobante')
@section('page-title', 'Tipos de Comprobante y Numeración')
@section('breadcrumb') Tipos de Comprobante @endsection

@section('content')

{{-- ── Alertas ───────────────────────────────────────────────────────── --}}
@if(session('success'))
<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
     class="mb-4 flex items-center gap-3 bg-sage/5 border border-sage/20 text-sage text-[12px] px-4 py-3">
    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
    </svg>
    {{ session('success') }}
</div>
@endif

@foreach($errors->all() as $error)
<div class="mb-4 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 text-[12px] px-4 py-3">
    {{ $error }}
</div>
@endforeach

<div class="space-y-4" x-data="{ addingType: false, expandedType: null }">

    {{-- ── Formulario nuevo tipo ─────────────────────────────────────── --}}
    <div x-show="addingType" x-cloak class="bg-white border border-sage/20">
        <div class="px-4 py-2.5 bg-forest border-b border-ink/10">
            <p class="text-[10px] font-bold uppercase tracking-widest text-white">Nuevo Tipo de Comprobante</p>
        </div>
        <form method="POST" action="{{ route('contable.entry-types.store') }}"
              class="px-5 py-4 flex items-end gap-3">
            @csrf
            <div>
                <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">
                    Código <span class="text-red-500">*</span>
                </label>
                <input type="text" name="code" maxlength="10" required placeholder="Ej: NE"
                    class="border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white w-28
                           focus:outline-none focus:border-sage font-mono uppercase"
                    style="text-transform:uppercase">
            </div>
            <div class="flex-1">
                <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">
                    Nombre <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" maxlength="100" required placeholder="Ej: Nota de Entrada"
                    class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white
                           focus:outline-none focus:border-sage">
            </div>
            <button type="submit"
                class="flex items-center gap-2 px-4 py-2 bg-sage text-white text-[11px] font-bold
                       uppercase tracking-wide hover:bg-forest transition-colors">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                Guardar
            </button>
            <button type="button" @click="addingType = false"
                class="text-[11px] font-bold text-ink/40 uppercase tracking-wide hover:text-ink transition-colors px-3 py-2">
                Cancelar
            </button>
        </form>
    </div>

    {{-- ── Tabla principal ───────────────────────────────────────────── --}}
    <div class="bg-white border border-ink/10 overflow-hidden">

        <div class="px-4 py-2.5 bg-forest flex items-center justify-between">
            <p class="text-[10px] font-bold uppercase tracking-widest text-white">Tipos de Comprobante</p>
            <button @click="addingType = !addingType"
                class="flex items-center gap-1.5 text-[10px] font-bold text-mint/60 hover:text-white uppercase tracking-wide transition-colors">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo Tipo
            </button>
        </div>

        <table class="w-full">
            <thead>
                <tr class="bg-ink/5 border-b border-ink/10">
                    <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 w-28">Código</th>
                    <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5">Nombre</th>
                    <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 w-36">Numeraciones</th>
                    <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 w-24">Estado</th>
                    <th class="px-4 py-2.5 w-28"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink/5">

                @forelse($types as $type)
                <tr class="hover:bg-cream transition-colors group"
                    x-data="{ editingName: false, newName: '{{ addslashes($type->name) }}' }">

                    {{-- Código --}}
                    <td class="px-4 py-3">
                        <span class="font-mono text-[11px] font-bold px-2 py-0.5
                            {{ $type->is_closing
                                ? 'bg-ink/8 text-ink/50 border border-ink/15'
                                : 'bg-ink/5 text-ink/60 border border-ink/10' }}">
                            {{ $type->code }}
                        </span>
                        @if($type->is_closing)
                            <span class="ml-1.5 text-[10px] text-ink/30 italic">cierre</span>
                        @endif
                    </td>

                    {{-- Nombre --}}
                    <td class="px-4 py-3">
                        <span x-show="!editingName" class="text-[12px] font-medium text-ink">{{ $type->name }}</span>
                        <form x-show="editingName" x-cloak
                              method="POST" action="{{ route('contable.entry-types.update', $type) }}"
                              class="flex items-center gap-2">
                            @csrf @method('PUT')
                            <input type="text" name="name" x-model="newName" maxlength="100"
                                class="border border-ink/15 px-2.5 py-1.5 text-[12px] text-ink bg-white flex-1
                                       focus:outline-none focus:border-sage">
                            <button type="submit"
                                class="text-[10px] font-bold px-3 py-1.5 bg-sage text-white hover:bg-forest uppercase tracking-wide transition-colors">
                                Guardar
                            </button>
                            <button type="button" @click="editingName = false"
                                class="text-[10px] font-bold text-ink/40 hover:text-ink uppercase tracking-wide transition-colors">
                                Cancelar
                            </button>
                        </form>
                    </td>

                    {{-- Numeraciones --}}
                    <td class="px-4 py-3">
                        <button @click="expandedType = expandedType === {{ $type->id }} ? null : {{ $type->id }}"
                            class="inline-flex items-center gap-1.5 text-[10px] font-bold text-ink/50
                                   hover:text-sage uppercase tracking-wide transition-colors">
                            {{ $type->sequences->count() }}
                            {{ Str::plural('numeración', $type->sequences->count()) }}
                            <svg class="w-3 h-3 transition-transform"
                                 :class="expandedType === {{ $type->id }} ? 'rotate-180' : ''"
                                 fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    </td>

                    {{-- Estado --}}
                    <td class="px-4 py-3">
                        <span class="w-2 h-2 inline-block {{ $type->active ? 'bg-sage' : 'bg-ink/20' }}"></span>
                    </td>

                    {{-- Acciones --}}
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            @if(!$type->is_system)
                                <button @click="editingName = !editingName"
                                    class="text-[10px] font-bold px-2 py-1 text-ink/50 hover:text-sage hover:bg-sage/10 uppercase tracking-wide transition-colors"
                                    title="Editar nombre">
                                    Editar
                                </button>
                                <form method="POST" action="{{ route('contable.entry-types.toggle', $type) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                        class="text-[10px] font-bold px-2 py-1 uppercase tracking-wide transition-colors
                                               {{ $type->active ? 'text-amber-500 hover:bg-amber-50' : 'text-sage hover:bg-sage/10' }}"
                                        title="{{ $type->active ? 'Desactivar' : 'Activar' }}">
                                        {{ $type->active ? 'Inact.' : 'Act.' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('contable.entry-types.destroy', $type) }}" class="inline"
                                      onsubmit="return confirm('¿Eliminar el tipo {{ $type->code }}? Se eliminarán también sus numeraciones.')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="text-[10px] font-bold px-2 py-1 text-red-400 hover:text-red-600 hover:bg-red-50 uppercase tracking-wide transition-colors">
                                        Elim.
                                    </button>
                                </form>
                            @else
                                <span class="text-[10px] text-ink/25 italic">sistema</span>
                            @endif
                        </div>
                    </td>
                </tr>

                {{-- ── Panel de numeraciones ─────────────────────────── --}}
                <tr x-show="expandedType === {{ $type->id }}" x-cloak>
                    <td colspan="5" class="px-4 pb-4 bg-cream/40">
                        <div class="border border-ink/10 overflow-hidden mt-2">

                            {{-- Sub-header numeraciones --}}
                            <div class="px-4 py-2 bg-ink/5 border-b border-ink/10 flex items-center justify-between">
                                <span class="text-[10px] font-bold text-ink/50 uppercase tracking-wider">
                                    Numeraciones — {{ $type->code }}
                                </span>
                                <button onclick="document.getElementById('add-seq-{{ $type->id }}').classList.toggle('hidden')"
                                    class="text-[10px] font-bold text-ink/50 hover:text-sage uppercase tracking-wide transition-colors flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Agregar
                                </button>
                            </div>

                            {{-- Formulario agregar numeración --}}
                            <div id="add-seq-{{ $type->id }}" class="hidden px-4 py-3 border-b border-ink/10 bg-sage/5">
                                <form method="POST"
                                      action="{{ route('contable.entry-types.sequences.store', $type) }}"
                                      class="grid grid-cols-2 lg:grid-cols-5 gap-3 items-end">
                                    @csrf
                                    <div>
                                        <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1">Nombre</label>
                                        <input type="text" name="name" maxlength="100" required
                                            class="w-full border border-ink/15 px-2.5 py-1.5 text-[11px] text-ink bg-white
                                                   focus:outline-none focus:border-sage">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1">Prefijo</label>
                                        <input type="text" name="prefix" maxlength="20" required placeholder="{{ $type->code }}-"
                                            class="w-full border border-ink/15 px-2.5 py-1.5 text-[11px] text-ink bg-white
                                                   focus:outline-none focus:border-sage font-mono">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1">N° inicial</label>
                                        <input type="number" name="initial_number" value="1" min="1" required
                                            class="w-full border border-ink/15 px-2.5 py-1.5 text-[11px] text-ink bg-white
                                                   focus:outline-none focus:border-sage">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1">Prioridad</label>
                                        <input type="number" name="priority" value="{{ $type->sequences->count() + 1 }}" min="1" required
                                            class="w-full border border-ink/15 px-2.5 py-1.5 text-[11px] text-ink bg-white
                                                   focus:outline-none focus:border-sage">
                                    </div>
                                    <button type="submit"
                                        class="px-3 py-1.5 bg-sage text-white text-[10px] font-bold uppercase tracking-wide
                                               hover:bg-forest transition-colors">
                                        Guardar
                                    </button>
                                </form>
                            </div>

                            {{-- Lista de numeraciones --}}
                            <table class="w-full">
                                <thead class="bg-ink/5 border-b border-ink/8">
                                    <tr>
                                        <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2 w-10">P.</th>
                                        <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2">Nombre</th>
                                        <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2 w-28">Prefijo</th>
                                        <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2 w-24">N° Inicial</th>
                                        <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2 w-20">Estado</th>
                                        <th class="px-4 py-2 w-16"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-ink/5 bg-white">
                                    @forelse($type->sequences as $seq)
                                    <tr class="hover:bg-cream transition-colors">
                                        <td class="px-4 py-2.5 font-mono text-[10px] text-ink/30">{{ $seq->priority }}</td>
                                        <td class="px-4 py-2.5 text-[11px] font-medium text-ink">{{ $seq->name }}</td>
                                        <td class="px-4 py-2.5 font-mono text-[11px] text-sage">{{ $seq->prefix }}</td>
                                        <td class="px-4 py-2.5 text-[11px] text-ink/60">{{ $seq->initial_number }}</td>
                                        <td class="px-4 py-2.5">
                                            <span class="w-1.5 h-1.5 inline-block {{ $seq->active ? 'bg-sage' : 'bg-ink/20' }}"></span>
                                        </td>
                                        <td class="px-4 py-2.5 text-right">
                                            <form method="POST"
                                                  action="{{ route('contable.entry-types.sequences.toggle', [$type, $seq]) }}">
                                                @csrf @method('PATCH')
                                                <button type="submit"
                                                    class="text-[10px] font-bold uppercase tracking-wide transition-colors
                                                           {{ $seq->active ? 'text-amber-500 hover:bg-amber-50' : 'text-sage hover:bg-sage/10' }} px-2 py-0.5">
                                                    {{ $seq->active ? 'Inact.' : 'Act.' }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-5 text-center text-[11px] text-ink/30">
                                            Sin numeraciones configuradas.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>

                @empty
                <tr>
                    <td colspan="5" class="px-4 py-12 text-center text-[12px] text-ink/30">
                        No hay tipos de comprobante.
                    </td>
                </tr>
                @endforelse

            </tbody>
        </table>
    </div>
</div>
@endsection
