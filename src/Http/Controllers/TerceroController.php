<?php

namespace Numerar\Contable\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Numerar\Contable\Models\Tercero;

class TerceroController extends Controller
{
    public function index()
    {
        $terceros = Tercero::orderBy('razon_social')->orderBy('primer_apellido')->paginate(25);
        return view('contable::terceros.index', compact('terceros'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tipo_persona'           => 'required|in:NATURAL,JURIDICA',
            'tipo_documento'         => 'required|in:NIT,CC,CE,PA,TE,RC,TI,PEP,PPT',
            'numero_documento'       => 'required|string|max:20',
            'digito_verificacion'    => 'nullable|string|max:1',
            'razon_social'           => 'nullable|string|max:200',
            'primer_nombre'          => 'nullable|string|max:100',
            'segundo_nombre'         => 'nullable|string|max:100',
            'primer_apellido'        => 'nullable|string|max:100',
            'segundo_apellido'       => 'nullable|string|max:100',
            'es_cliente'             => 'boolean',
            'es_proveedor'           => 'boolean',
            'es_empleado'            => 'boolean',
            'es_otro'                => 'boolean',
            'responsabilidad_fiscal' => 'required|in:RESPONSABLE_IVA,NO_RESPONSABLE,GRAN_CONTRIBUYENTE,REGIMEN_SIMPLE,NO_APLICA',
            'municipio'              => 'nullable|string|max:100',
            'departamento'           => 'nullable|string|max:100',
            'direccion'              => 'nullable|string|max:250',
            'email'                  => 'nullable|email|max:150',
            'telefono'               => 'nullable|string|max:20',
            'celular'                => 'nullable|string|max:20',
        ]);

        Tercero::create(array_merge($data, [
            'es_cliente'   => (bool) ($data['es_cliente']   ?? false),
            'es_proveedor' => (bool) ($data['es_proveedor'] ?? false),
            'es_empleado'  => (bool) ($data['es_empleado']  ?? false),
            'es_otro'      => (bool) ($data['es_otro']      ?? false),
            'created_by'   => auth()->id(),
            'updated_by'   => auth()->id(),
        ]));

        return back()->with('success', 'Tercero creado correctamente.');
    }

    public function update(Request $request, Tercero $tercero)
    {
        $data = $request->validate([
            'tipo_persona'           => 'required|in:NATURAL,JURIDICA',
            'tipo_documento'         => 'required|in:NIT,CC,CE,PA,TE,RC,TI,PEP,PPT',
            'numero_documento'       => 'required|string|max:20',
            'digito_verificacion'    => 'nullable|string|max:1',
            'razon_social'           => 'nullable|string|max:200',
            'primer_nombre'          => 'nullable|string|max:100',
            'segundo_nombre'         => 'nullable|string|max:100',
            'primer_apellido'        => 'nullable|string|max:100',
            'segundo_apellido'       => 'nullable|string|max:100',
            'es_cliente'             => 'boolean',
            'es_proveedor'           => 'boolean',
            'es_empleado'            => 'boolean',
            'es_otro'                => 'boolean',
            'responsabilidad_fiscal' => 'required|in:RESPONSABLE_IVA,NO_RESPONSABLE,GRAN_CONTRIBUYENTE,REGIMEN_SIMPLE,NO_APLICA',
            'municipio'              => 'nullable|string|max:100',
            'departamento'           => 'nullable|string|max:100',
            'direccion'              => 'nullable|string|max:250',
            'email'                  => 'nullable|email|max:150',
            'telefono'               => 'nullable|string|max:20',
            'celular'                => 'nullable|string|max:20',
        ]);

        $tercero->update(array_merge($data, [
            'es_cliente'   => (bool) ($data['es_cliente']   ?? false),
            'es_proveedor' => (bool) ($data['es_proveedor'] ?? false),
            'es_empleado'  => (bool) ($data['es_empleado']  ?? false),
            'es_otro'      => (bool) ($data['es_otro']      ?? false),
            'updated_by'   => auth()->id(),
        ]));

        return back()->with('success', 'Tercero actualizado correctamente.');
    }

    public function toggle(Tercero $tercero)
    {
        $tercero->update(['activo' => ! $tercero->activo]);
        return back()->with('success', 'Estado actualizado.');
    }

    public function search(Request $request)
    {
        $q = $request->input('q', '');
        $terceros = Tercero::where('activo', true)
            ->where(function ($query) use ($q) {
                $query->where('razon_social', 'like', "%{$q}%")
                    ->orWhere('primer_nombre', 'like', "%{$q}%")
                    ->orWhere('primer_apellido', 'like', "%{$q}%")
                    ->orWhere('numero_documento', 'like', "%{$q}%");
            })
            ->orderBy('razon_social')
            ->limit(20)
            ->get(['id', 'tipo_documento', 'numero_documento', 'digito_verificacion',
                   'razon_social', 'primer_nombre', 'primer_apellido', 'tipo_persona']);

        return response()->json($terceros->map(fn($t) => [
            'id'    => $t->id,
            'label' => $t->nombre_completo . ' — ' . $t->documento_formateado,
        ]));
    }
}
