@extends('contable::layouts.app')

@section('title', 'Editar Cuenta')
@section('page-title', 'Editar Cuenta')
@section('breadcrumb')
    <a href="{{ route('contable.accounts.index') }}" class="hover:text-sage transition-colors">Plan de Cuentas</a>
    <span class="mx-1 text-ink/30">/</span> Editar
@endsection

@section('content')
@php
$parentRefInit = $account->parent_id
    ? 'A:' . $account->parent_id . ':' . $account->class_id
    : 'C:' . $account->class_id;
$parentRefInit = old('_parent_ref', $parentRefInit);
@endphp

<div class="max-w-2xl">
<form method="POST" action="{{ route('contable.accounts.update', $account) }}"
      x-data="{
        nature: '{{ old('nature', $account->nature->value) }}',
        accountType: '{{ old('account_type', $account->account_type->value) }}',
        parentRef: '{{ $parentRefInit }}',
        classId: '{{ old('class_id', $account->class_id) }}',
        parentId: '{{ old('parent_id', $account->parent_id ?? '') }}',
        parseParent(val) {
            if (!val) { this.classId = ''; this.parentId = ''; return; }
            const parts = val.split(':');
            if (parts[0] === 'C') { this.classId = parts[1]; this.parentId = ''; }
            else { this.parentId = parts[1]; this.classId = parts[2]; }
        }
      }">
    @csrf @method('PUT')
    <input type="hidden" name="class_id" :value="classId">
    <input type="hidden" name="parent_id" :value="parentId">

    <div class="bg-white border border-ink/10 divide-y divide-ink/8">

        {{-- Cabecera del panel --}}
        <div class="px-4 py-2.5 bg-forest flex items-center justify-between">
            <p class="text-[10px] font-bold uppercase tracking-widest text-white">Editar Cuenta Contable</p>
            <span class="font-mono text-[10px] text-mint/60">{{ $account->code }}</span>
        </div>

        {{-- Identificación --}}
        <div class="px-6 py-5 space-y-4">
            <p class="text-[10px] font-bold text-ink/40 uppercase tracking-widest">Identificación</p>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">Código</label>
                    <input type="text" name="code" value="{{ old('code', $account->code) }}" maxlength="20"
                        class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white
                               focus:outline-none focus:border-sage font-mono">
                </div>
                <div class="col-span-2">
                    <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">
                        Nombre <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name', $account->name) }}" required
                        class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white
                               focus:outline-none focus:border-sage">
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">Descripción</label>
                <textarea name="description" rows="2"
                    class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white
                           focus:outline-none focus:border-sage resize-none">{{ old('description', $account->description) }}</textarea>
            </div>
            @error('name')
                <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Clasificación --}}
        <div class="px-6 py-5 space-y-5">
            <p class="text-[10px] font-bold text-ink/40 uppercase tracking-widest">Clasificación</p>

            {{-- Padre --}}
            <div>
                <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">
                    Pertenece a <span class="text-red-500">*</span>
                    <span class="font-normal text-ink/30 ml-1 normal-case">— clase para grupos, cuenta para auxiliares</span>
                </label>
                <input type="hidden" name="_parent_ref" :value="parentRef">
                <select x-model="parentRef" @change="parseParent(parentRef)" required
                    class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white
                           focus:outline-none focus:border-sage">
                    <option value="">Seleccionar...</option>
                    <optgroup label="── Clases (cuenta de grupo) ──">
                        @foreach($classes as $class)
                        <option value="C:{{ $class->id }}" {{ $parentRefInit === 'C:'.$class->id ? 'selected' : '' }}>
                            {{ $class->code }} — {{ $class->name }}
                        </option>
                        @endforeach
                    </optgroup>
                    <optgroup label="── Cuentas existentes ──">
                        @foreach($parents as $p)
                        @if($p['id'] !== $account->id)
                        <option value="A:{{ $p['id'] }}:{{ $p['class_id'] }}"
                            {{ str_starts_with($parentRefInit, 'A:'.$p['id'].':') ? 'selected' : '' }}>
                            {{ $p['label'] }}
                        </option>
                        @endif
                        @endforeach
                    </optgroup>
                </select>
                @error('class_id')
                    <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Naturaleza --}}
            <div>
                <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-2">
                    Naturaleza <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center gap-3 px-4 py-3 border cursor-pointer transition-all"
                        :class="nature === 'DEBIT'
                            ? 'border-sage bg-sage/5'
                            : 'border-ink/10 hover:border-ink/25'">
                        <input type="radio" name="nature" value="DEBIT" x-model="nature" class="sr-only">
                        <div class="w-4 h-4 border-2 flex items-center justify-center shrink-0 transition-colors"
                            :class="nature === 'DEBIT' ? 'border-sage' : 'border-ink/20'">
                            <div class="w-2 h-2 bg-sage" x-show="nature === 'DEBIT'"></div>
                        </div>
                        <div>
                            <p class="text-[12px] font-semibold"
                               :class="nature === 'DEBIT' ? 'text-forest' : 'text-ink/60'">Débito</p>
                            <p class="text-[10px] text-ink/40">Activos, Gastos, Costos</p>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 px-4 py-3 border cursor-pointer transition-all"
                        :class="nature === 'CREDIT'
                            ? 'border-ink/40 bg-ink/3'
                            : 'border-ink/10 hover:border-ink/25'">
                        <input type="radio" name="nature" value="CREDIT" x-model="nature" class="sr-only">
                        <div class="w-4 h-4 border-2 flex items-center justify-center shrink-0 transition-colors"
                            :class="nature === 'CREDIT' ? 'border-ink/50' : 'border-ink/20'">
                            <div class="w-2 h-2 bg-ink/50" x-show="nature === 'CREDIT'"></div>
                        </div>
                        <div>
                            <p class="text-[12px] font-semibold"
                               :class="nature === 'CREDIT' ? 'text-ink' : 'text-ink/60'">Crédito</p>
                            <p class="text-[10px] text-ink/40">Pasivos, Patrimonio, Ingresos</p>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Tipo --}}
            <div>
                <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-2">
                    Tipo de Cuenta <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center gap-3 px-4 py-3 border cursor-pointer transition-all"
                        :class="accountType === 'MOVIMIENTO'
                            ? 'border-sage bg-sage/5'
                            : 'border-ink/10 hover:border-ink/25'">
                        <input type="radio" name="account_type" value="MOVIMIENTO" x-model="accountType" class="sr-only">
                        <div class="w-4 h-4 border-2 flex items-center justify-center shrink-0 transition-colors"
                            :class="accountType === 'MOVIMIENTO' ? 'border-sage' : 'border-ink/20'">
                            <div class="w-2 h-2 bg-sage" x-show="accountType === 'MOVIMIENTO'"></div>
                        </div>
                        <div>
                            <p class="text-[12px] font-semibold"
                               :class="accountType === 'MOVIMIENTO' ? 'text-forest' : 'text-ink/60'">Movimiento</p>
                            <p class="text-[10px] text-ink/40">Acepta registros directos</p>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 px-4 py-3 border cursor-pointer transition-all"
                        :class="accountType === 'MAYOR'
                            ? 'border-ink/40 bg-ink/3'
                            : 'border-ink/10 hover:border-ink/25'">
                        <input type="radio" name="account_type" value="MAYOR" x-model="accountType" class="sr-only">
                        <div class="w-4 h-4 border-2 flex items-center justify-center shrink-0 transition-colors"
                            :class="accountType === 'MAYOR' ? 'border-ink/50' : 'border-ink/20'">
                            <div class="w-2 h-2 bg-ink/50" x-show="accountType === 'MAYOR'"></div>
                        </div>
                        <div>
                            <p class="text-[12px] font-semibold"
                               :class="accountType === 'MAYOR' ? 'text-ink' : 'text-ink/60'">Mayor</p>
                            <p class="text-[10px] text-ink/40">Solo agrupación</p>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        {{-- Acciones --}}
        <div class="px-6 py-4 bg-cream/50 flex items-center justify-between">
            <a href="{{ route('contable.accounts.index') }}"
               class="text-[11px] font-bold text-ink/40 uppercase tracking-wide hover:text-ink transition-colors">
                ← Cancelar
            </a>
            <button type="submit"
                class="flex items-center gap-2 px-5 py-2 bg-sage text-white text-[11px] font-bold
                       uppercase tracking-wide hover:bg-forest transition-colors">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                Actualizar Cuenta
            </button>
        </div>
    </div>
</form>
</div>
@endsection
