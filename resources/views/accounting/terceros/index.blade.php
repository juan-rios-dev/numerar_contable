@extends('contable::layouts.app')

@section('title', 'Terceros')
@section('page-title', 'Terceros')
@section('breadcrumb') Catálogo <span class="mx-1 text-ink/30">/</span> Terceros @endsection

@section('content')
<div x-data="terceroApp()">

    {{-- ── Barra superior ───────────────────────────────────────────── --}}
    <div class="flex items-center gap-3 mb-4">
        <div class="relative flex-1 max-w-sm">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-ink/30"
                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input x-model="search" type="text"
                placeholder="Buscar por nombre, NIT, documento…"
                class="w-full pl-8 pr-3 py-2 border border-ink/15 text-[12px] text-ink bg-white
                       focus:outline-none focus:border-sage">
        </div>
        <button @click="openCreate()"
            class="flex items-center gap-2 px-3 py-2 bg-sage text-white text-[11px] font-bold
                   uppercase tracking-wide hover:bg-forest transition-colors shrink-0">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo Tercero
        </button>
    </div>

    {{-- ── Tabla ─────────────────────────────────────────────────────── --}}
    <div class="bg-white border border-ink/10 overflow-hidden">

        <div class="px-4 py-2.5 bg-forest flex items-center justify-between">
            <p class="text-[10px] font-bold uppercase tracking-widest text-white">Terceros</p>
            <span class="text-[10px] font-mono text-mint/60">{{ $terceros->total() }} registros</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-ink/5 border-b border-ink/10">
                        <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5">Identificación</th>
                        <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5">Nombre / Razón Social</th>
                        <th class="text-center text-[10px] font-bold text-ink/40 uppercase tracking-wider px-3 py-2.5 w-24">Tipo</th>
                        <th class="text-center text-[10px] font-bold text-ink/40 uppercase tracking-wider px-3 py-2.5">Roles</th>
                        <th class="text-left text-[10px] font-bold text-ink/40 uppercase tracking-wider px-4 py-2.5 hidden lg:table-cell">Contacto</th>
                        <th class="text-center text-[10px] font-bold text-ink/40 uppercase tracking-wider px-3 py-2.5 w-16">Estado</th>
                        <th class="px-4 py-2.5 w-20"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink/5">
                    @forelse($terceros as $t)
                    @php $nombre = $t->nombre_completo; @endphp
                    <tr class="hover:bg-cream transition-colors group"
                        x-show="matchSearch('{{ addslashes($nombre) }}', '{{ $t->numero_documento }}')">

                        <td class="px-4 py-3">
                            <span class="font-mono text-[10px] text-ink/40">{{ $t->tipo_documento }}</span>
                            <span class="font-mono text-[12px] font-semibold text-ink ml-1">
                                {{ $t->numero_documento }}{{ $t->digito_verificacion ? '-'.$t->digito_verificacion : '' }}
                            </span>
                        </td>

                        <td class="px-4 py-3 text-[12px] font-medium text-ink">{{ $nombre }}</td>

                        <td class="px-3 py-3 text-center">
                            @if($t->tipo_persona === 'JURIDICA')
                                <span class="text-[10px] font-bold px-2 py-0.5 bg-sage/10 text-forest border border-sage/20">Jurídica</span>
                            @else
                                <span class="text-[10px] font-bold px-2 py-0.5 bg-ink/5 text-ink/60 border border-ink/10">Natural</span>
                            @endif
                        </td>

                        <td class="px-3 py-3">
                            <div class="flex justify-center gap-1 flex-wrap">
                                @if($t->es_cliente)
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 bg-sage/10 text-sage border border-sage/20">Cliente</span>
                                @endif
                                @if($t->es_proveedor)
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 bg-amber-50 text-amber-600 border border-amber-200">Proveedor</span>
                                @endif
                                @if($t->es_empleado)
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 bg-ink/5 text-ink/60 border border-ink/10">Empleado</span>
                                @endif
                                @if($t->es_otro)
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 bg-ink/5 text-ink/40 border border-ink/10">Otro</span>
                                @endif
                            </div>
                        </td>

                        <td class="px-4 py-3 hidden lg:table-cell">
                            @if($t->email)    <div class="text-[11px] text-ink/50">{{ $t->email }}</div> @endif
                            @if($t->celular)  <div class="text-[11px] text-ink/40">{{ $t->celular }}</div>
                            @elseif($t->telefono) <div class="text-[11px] text-ink/40">{{ $t->telefono }}</div> @endif
                            @if($t->municipio)<div class="text-[10px] text-ink/30">{{ $t->municipio }}</div> @endif
                        </td>

                        <td class="px-3 py-3 text-center">
                            <span class="w-2 h-2 inline-block {{ $t->activo ? 'bg-sage' : 'bg-ink/20' }}"></span>
                        </td>

                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click="openEdit({{ $t->id }}, {{ json_encode([
                                    'tipo_persona'           => $t->tipo_persona,
                                    'tipo_documento'         => $t->tipo_documento,
                                    'numero_documento'       => $t->numero_documento,
                                    'digito_verificacion'    => $t->digito_verificacion,
                                    'razon_social'           => $t->razon_social,
                                    'primer_nombre'          => $t->primer_nombre,
                                    'segundo_nombre'         => $t->segundo_nombre,
                                    'primer_apellido'        => $t->primer_apellido,
                                    'segundo_apellido'       => $t->segundo_apellido,
                                    'es_cliente'             => $t->es_cliente,
                                    'es_proveedor'           => $t->es_proveedor,
                                    'es_empleado'            => $t->es_empleado,
                                    'es_otro'                => $t->es_otro,
                                    'responsabilidad_fiscal' => $t->responsabilidad_fiscal,
                                    'municipio'              => $t->municipio,
                                    'departamento'           => $t->departamento,
                                    'direccion'              => $t->direccion,
                                    'email'                  => $t->email,
                                    'telefono'               => $t->telefono,
                                    'celular'                => $t->celular,
                                ]) }})"
                                    class="text-[10px] font-bold px-2 py-1 text-ink/50 hover:text-sage hover:bg-sage/10 transition-colors uppercase tracking-wide">
                                    Editar
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-[12px] text-ink/30">
                            No hay terceros registrados.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($terceros->hasPages())
        <div class="px-4 py-3 border-t border-ink/8 text-[12px]">
            {{ $terceros->links() }}
        </div>
        @endif
    </div>

    {{-- ── Modal Crear / Editar ─────────────────────────────────────── --}}
    <div x-show="showModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-ink/50" @click="showModal = false"></div>

        <div @click.stop
             class="relative bg-white border border-ink/10 w-full max-w-2xl max-h-[90vh] overflow-y-auto">

            {{-- Header --}}
            <div class="flex items-center justify-between px-4 py-3 bg-forest border-b border-ink/10 sticky top-0">
                <p class="text-[10px] font-bold uppercase tracking-widest text-white"
                   x-text="editId ? 'Editar Tercero' : 'Nuevo Tercero'"></p>
                <button @click="showModal = false" class="text-mint/50 hover:text-white transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Form --}}
            <form :action="editId
                    ? `{{ url(config('contable.web_prefix','accounting').'/terceros') }}/${editId}`
                    : '{{ route('contable.terceros.store') }}'"
                  method="POST" class="px-6 py-5 space-y-5">
                @csrf
                <template x-if="editId"><input type="hidden" name="_method" value="PUT"></template>

                {{-- Tipo persona + documento --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">
                            Tipo Persona <span class="text-red-500">*</span>
                        </label>
                        <select name="tipo_persona" x-model="form.tipo_persona"
                            class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white focus:outline-none focus:border-sage">
                            <option value="JURIDICA">Jurídica</option>
                            <option value="NATURAL">Natural</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">
                            Tipo Documento <span class="text-red-500">*</span>
                        </label>
                        <select name="tipo_documento" x-model="form.tipo_documento"
                            class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white focus:outline-none focus:border-sage">
                            <option value="NIT">NIT</option>
                            <option value="CC">Cédula de Ciudadanía</option>
                            <option value="CE">Cédula Extranjería</option>
                            <option value="PA">Pasaporte</option>
                            <option value="TE">Tarjeta de Extranjería</option>
                            <option value="RC">Registro Civil</option>
                            <option value="TI">Tarjeta de Identidad</option>
                            <option value="PEP">PEP</option>
                            <option value="PPT">PPT</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-2">
                        <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">
                            Número Documento <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="numero_documento" x-model="form.numero_documento" required
                            class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white
                                   focus:outline-none focus:border-sage font-mono">
                    </div>
                    <div x-show="form.tipo_documento === 'NIT'">
                        <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">Dígito V.</label>
                        <input type="text" name="digito_verificacion" x-model="form.digito_verificacion" maxlength="1"
                            class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white
                                   focus:outline-none focus:border-sage font-mono">
                    </div>
                </div>

                {{-- Razón social --}}
                <div x-show="form.tipo_persona === 'JURIDICA'">
                    <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">
                        Razón Social <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="razon_social" x-model="form.razon_social"
                        class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white focus:outline-none focus:border-sage">
                </div>

                {{-- Nombre natural --}}
                <div x-show="form.tipo_persona === 'NATURAL'" class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">Primer Nombre</label>
                        <input type="text" name="primer_nombre" x-model="form.primer_nombre"
                            class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white focus:outline-none focus:border-sage">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">Segundo Nombre</label>
                        <input type="text" name="segundo_nombre" x-model="form.segundo_nombre"
                            class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white focus:outline-none focus:border-sage">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">Primer Apellido</label>
                        <input type="text" name="primer_apellido" x-model="form.primer_apellido"
                            class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white focus:outline-none focus:border-sage">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">Segundo Apellido</label>
                        <input type="text" name="segundo_apellido" x-model="form.segundo_apellido"
                            class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white focus:outline-none focus:border-sage">
                    </div>
                </div>

                {{-- Roles --}}
                <div>
                    <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-2">Roles</label>
                    <div class="flex flex-wrap gap-4">
                        @foreach(['es_cliente' => 'Cliente', 'es_proveedor' => 'Proveedor', 'es_empleado' => 'Empleado', 'es_otro' => 'Otro'] as $field => $label)
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" name="{{ $field }}" value="1" x-model="form.{{ $field }}"
                                class="w-3.5 h-3.5 border border-ink/20 accent-sage">
                            <span class="text-[12px] text-ink/70">{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Responsabilidad fiscal --}}
                <div>
                    <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">Responsabilidad Fiscal</label>
                    <select name="responsabilidad_fiscal" x-model="form.responsabilidad_fiscal"
                        class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white focus:outline-none focus:border-sage">
                        <option value="NO_APLICA">No aplica</option>
                        <option value="RESPONSABLE_IVA">Responsable de IVA</option>
                        <option value="NO_RESPONSABLE">No responsable de IVA</option>
                        <option value="GRAN_CONTRIBUYENTE">Gran contribuyente</option>
                        <option value="REGIMEN_SIMPLE">Régimen simple de tributación</option>
                    </select>
                </div>

                {{-- Ubicación --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">Municipio</label>
                        <input type="text" name="municipio" x-model="form.municipio"
                            class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white focus:outline-none focus:border-sage">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">Departamento</label>
                        <input type="text" name="departamento" x-model="form.departamento"
                            class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white focus:outline-none focus:border-sage">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">Dirección</label>
                    <input type="text" name="direccion" x-model="form.direccion"
                        class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white focus:outline-none focus:border-sage">
                </div>

                {{-- Contacto --}}
                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-1">
                        <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">Email</label>
                        <input type="email" name="email" x-model="form.email"
                            class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white focus:outline-none focus:border-sage">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">Teléfono</label>
                        <input type="text" name="telefono" x-model="form.telefono"
                            class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white focus:outline-none focus:border-sage">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-ink/50 uppercase tracking-wider mb-1.5">Celular</label>
                        <input type="text" name="celular" x-model="form.celular"
                            class="w-full border border-ink/15 px-3 py-2 text-[12px] text-ink bg-white focus:outline-none focus:border-sage">
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-between pt-3 border-t border-ink/8">
                    <button type="button" @click="showModal = false"
                        class="text-[11px] font-bold text-ink/40 uppercase tracking-wide hover:text-ink transition-colors">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="flex items-center gap-2 px-5 py-2 bg-sage text-white text-[11px] font-bold
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

</div>

@push('scripts')
<script>
function terceroApp() {
    return {
        search: '',
        showModal: false,
        editId: null,
        form: {
            tipo_persona: 'JURIDICA', tipo_documento: 'NIT',
            numero_documento: '', digito_verificacion: '',
            razon_social: '', primer_nombre: '', segundo_nombre: '',
            primer_apellido: '', segundo_apellido: '',
            es_cliente: false, es_proveedor: false, es_empleado: false, es_otro: false,
            responsabilidad_fiscal: 'NO_APLICA',
            municipio: '', departamento: '', direccion: '',
            email: '', telefono: '', celular: '',
        },
        resetForm() {
            this.form = {
                tipo_persona: 'JURIDICA', tipo_documento: 'NIT',
                numero_documento: '', digito_verificacion: '',
                razon_social: '', primer_nombre: '', segundo_nombre: '',
                primer_apellido: '', segundo_apellido: '',
                es_cliente: false, es_proveedor: false, es_empleado: false, es_otro: false,
                responsabilidad_fiscal: 'NO_APLICA',
                municipio: '', departamento: '', direccion: '',
                email: '', telefono: '', celular: '',
            };
        },
        openCreate() { this.editId = null; this.resetForm(); this.showModal = true; },
        openEdit(id, data) { this.editId = id; this.form = { ...data }; this.showModal = true; },
        matchSearch(nombre, doc) {
            if (!this.search) return true;
            const q = this.search.toLowerCase();
            return nombre.toLowerCase().includes(q) || doc.toLowerCase().includes(q);
        },
    };
}
</script>
@endpush

@endsection
