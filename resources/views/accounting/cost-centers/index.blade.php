@extends('contable::layouts.app')

@section('title', 'Centros de Costo')
@section('page-title', 'Centros de Costo')
@section('breadcrumb') Centros de Costo @endsection

@section('header-actions')
<button @click="$dispatch('open-modal', 'create-cc')"
    class="flex items-center gap-2 px-3 py-1.5 bg-sage text-white text-[11px] font-bold
           uppercase tracking-wide hover:bg-forest transition-colors">
    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
    </svg>
    Nuevo Centro
</button>
@endsection

@section('content')
<div class="bg-white border border-ink/10 overflow-hidden">

    {{-- Cabecera --}}
    <div class="px-4 py-2.5 bg-forest flex items-center justify-between">
        <p class="text-[10px] font-bold uppercase tracking-widest text-white">Centros de Costo</p>
        <span class="text-[10px] font-mono text-mint/60">{{ $costCenters->count() }} registros</span>
    </div>

    {{-- Tabla --}}
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-ink/5 border-b border-ink/10">
                    <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 w-28">Código</th>
                    <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5">Nombre</th>
                    <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 hidden md:table-cell">Descripción</th>
                    <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 w-20">Estado</th>
                    <th class="px-4 py-2.5 w-32"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink/5">
                @forelse($costCenters as $cc)
                <tr class="hover:bg-cream transition-colors group">
                    <td class="px-4 py-3">
                        <span class="font-mono text-[11px] font-bold text-forest">{{ $cc->code }}</span>
                    </td>
                    <td class="px-4 py-3 text-[12px] font-medium text-ink">{{ $cc->name }}</td>
                    <td class="px-4 py-3 text-[11px] text-ink/40 hidden md:table-cell">{{ $cc->description ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="w-2 h-2 inline-block {{ $cc->active ? 'bg-sage' : 'bg-ink/20' }}"></span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button @click="$dispatch('open-modal', { name: 'edit-cc', data: {{ $cc }} })"
                                class="text-[10px] font-bold px-2 py-1 text-ink/50 hover:text-sage hover:bg-sage/10 transition-colors uppercase tracking-wide">
                                Editar
                            </button>
                            <form method="POST" action="{{ route('contable.cost-centers.toggle', $cc) }}" class="inline">
                                @csrf @method('PATCH')
                                <button type="submit"
                                    class="text-[10px] font-bold px-2 py-1 uppercase tracking-wide transition-colors
                                           {{ $cc->active
                                               ? 'text-amber-500 hover:bg-amber-50'
                                               : 'text-sage hover:bg-sage/10' }}">
                                    {{ $cc->active ? 'Inact.' : 'Act.' }}
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-12 text-center text-[12px] text-ink/30">
                        No hay centros de costo registrados.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('modals')

{{-- ── Modal Crear ──────────────────────────────────────────────────── --}}
<div x-data="{ open: false }"
     x-on:open-modal.window="if($event.detail === 'create-cc') open = true"
     x-show="open" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-ink/50" @click="open = false"></div>
    <div class="relative bg-white border border-ink/10 w-full max-w-md shadow-none" @click.stop>

        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3 bg-forest border-b border-ink/10">
            <p class="text-[10px] font-bold uppercase tracking-widest text-white">Nuevo Centro de Costo</p>
            <button @click="open = false" class="text-mint/50 hover:text-white transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Form --}}
        <form method="POST" action="{{ route('contable.cost-centers.store') }}" class="px-5 py-5 space-y-4">
            @csrf
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">
                        Código <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="code" maxlength="20" required
                        class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white
                               focus:outline-none focus:border-sage font-mono"
                        placeholder="CC01">
                </div>
                <div class="col-span-2">
                    <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">
                        Nombre <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" required
                        class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white
                               focus:outline-none focus:border-sage"
                        placeholder="Administración">
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">Descripción</label>
                <textarea name="description" rows="2"
                    class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white
                           focus:outline-none focus:border-sage resize-none"
                    placeholder="Descripción opcional..."></textarea>
            </div>
            <div class="flex items-center justify-between pt-1 border-t border-ink/8">
                <button type="button" @click="open = false"
                    class="text-[11px] font-bold text-ink/40 uppercase tracking-wide hover:text-ink transition-colors">
                    Cancelar
                </button>
                <button type="submit"
                    class="flex items-center gap-2 px-4 py-2 bg-sage text-white text-[11px] font-bold
                           uppercase tracking-wide hover:bg-forest transition-colors">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── Modal Editar ─────────────────────────────────────────────────── --}}
<div x-data="{ open: false, cc: { id: null, code: '', name: '', description: '' } }"
     x-on:open-modal.window="if($event.detail?.name === 'edit-cc') { cc = $event.detail.data; open = true }"
     x-show="open" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-ink/50" @click="open = false"></div>
    <div class="relative bg-white border border-ink/10 w-full max-w-md" @click.stop>

        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3 bg-forest border-b border-ink/10">
            <p class="text-[10px] font-bold uppercase tracking-widest text-white">Editar Centro de Costo</p>
            <button @click="open = false" class="text-mint/50 hover:text-white transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Form --}}
        <form method="POST" :action="`{{ url(config('contable.web_prefix','accounting').'/cost-centers') }}/${cc.id}`"
              class="px-5 py-5 space-y-4">
            @csrf @method('PUT')
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">Código</label>
                    <input type="text" name="code" x-model="cc.code" maxlength="20"
                        class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white
                               focus:outline-none focus:border-sage font-mono">
                </div>
                <div class="col-span-2">
                    <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">
                        Nombre <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" x-model="cc.name" required
                        class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white
                               focus:outline-none focus:border-sage">
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">Descripción</label>
                <textarea name="description" x-model="cc.description" rows="2"
                    class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white
                           focus:outline-none focus:border-sage resize-none"></textarea>
            </div>
            <div class="flex items-center justify-between pt-1 border-t border-ink/8">
                <button type="button" @click="open = false"
                    class="text-[11px] font-bold text-ink/40 uppercase tracking-wide hover:text-ink transition-colors">
                    Cancelar
                </button>
                <button type="submit"
                    class="flex items-center gap-2 px-4 py-2 bg-sage text-white text-[11px] font-bold
                           uppercase tracking-wide hover:bg-forest transition-colors">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    Actualizar
                </button>
            </div>
        </form>
    </div>
</div>

@endpush
