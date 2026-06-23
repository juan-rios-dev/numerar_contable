<?php

namespace Numerar\Contable\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Numerar\Contable\Models\Tercero;

class TerceroApiController extends Controller
{
    public function index(): JsonResponse
    {
        $terceros = Tercero::orderBy('razon_social')->orderBy('primer_apellido')->paginate(25);
        return response()->json($terceros);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'tipo_persona'   => ['required', 'in:NATURAL,JURIDICA'],
            'tipo_documento' => ['required', 'in:NIT,CC,CE,PA,TE,RC,TI,PEP,PPT'],
            'numero_documento' => ['required', 'string', 'max:20'],
        ]);

        $tercero = Tercero::create($request->all());
        return response()->json($tercero, 201);
    }

    public function show(Tercero $tercero): JsonResponse
    {
        return response()->json($tercero);
    }

    public function update(Request $request, Tercero $tercero): JsonResponse
    {
        $tercero->update($request->all());
        return response()->json($tercero);
    }

    public function destroy(Tercero $tercero): JsonResponse
    {
        $tercero->delete();
        return response()->json(null, 204);
    }

    public function toggle(Tercero $tercero): JsonResponse
    {
        $tercero->update(['activo' => ! $tercero->activo]);
        return response()->json(['activo' => $tercero->activo]);
    }

    public function search(Request $request): JsonResponse
    {
        $q = $request->query('q', '');

        $results = Tercero::where('activo', true)
            ->where(function ($query) use ($q) {
                $query->where('razon_social', 'like', "%{$q}%")
                    ->orWhere('primer_nombre', 'like', "%{$q}%")
                    ->orWhere('primer_apellido', 'like', "%{$q}%")
                    ->orWhere('numero_documento', 'like', "%{$q}%");
            })
            ->limit(20)
            ->get(['id', 'tipo_documento', 'numero_documento', 'razon_social',
                   'primer_nombre', 'primer_apellido', 'es_cliente', 'es_proveedor']);

        return response()->json($results);
    }
}
