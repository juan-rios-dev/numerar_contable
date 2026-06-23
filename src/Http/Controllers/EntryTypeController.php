<?php

namespace Numerar\Contable\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Numerar\Contable\Exceptions\AccountingException;
use Numerar\Contable\Http\Requests\StoreEntryTypeRequest;
use Numerar\Contable\Http\Requests\UpdateEntryTypeRequest;
use Numerar\Contable\Http\Resources\EntryTypeResource;
use Numerar\Contable\Models\AccountingEntryType;

class EntryTypeController extends Controller
{
    public function index(Request $request)
    {
        $types = AccountingEntryType::with('sequences')->orderBy('code')->get();

        if ($request->expectsJson()) {
            return EntryTypeResource::collection($types);
        }

        return view('contable::entry-types.index', compact('types'));
    }

    public function store(StoreEntryTypeRequest $request)
    {
        $type = AccountingEntryType::create($request->validated() + [
            'is_closing' => false,
            'is_system'  => false,
            'active'     => true,
        ]);

        if ($request->expectsJson()) {
            return EntryTypeResource::make($type)->response()->setStatusCode(201);
        }

        return back()->with('success', "Tipo de comprobante '{$type->code}' creado exitosamente.");
    }

    public function update(UpdateEntryTypeRequest $request, AccountingEntryType $entryType)
    {
        if ($entryType->is_system) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Los tipos de comprobante del sistema solo permiten cambiar el nombre.'], 422);
            }
            return back()->withErrors(['entry_type' => 'Los tipos de comprobante del sistema solo permiten cambiar el nombre.']);
        }

        $entryType->update($request->validated());

        if ($request->expectsJson()) {
            return EntryTypeResource::make($entryType->fresh('sequences'));
        }

        return back()->with('success', "Tipo de comprobante actualizado.");
    }

    public function toggle(AccountingEntryType $entryType)
    {
        if ($entryType->is_system) {
            if (request()->expectsJson()) {
                return response()->json(['message' => 'Los tipos de comprobante del sistema no pueden desactivarse.'], 422);
            }
            return back()->withErrors(['entry_type' => 'Los tipos de comprobante del sistema no pueden desactivarse.']);
        }

        $entryType->update(['active' => ! $entryType->active]);

        if (request()->expectsJson()) {
            return EntryTypeResource::make($entryType->fresh());
        }

        return back()->with('success', "Tipo de comprobante " . ($entryType->active ? 'desactivado' : 'activado') . ".");
    }

    public function destroy(AccountingEntryType $entryType)
    {
        if ($entryType->is_system) {
            if (request()->expectsJson()) {
                return response()->json(['message' => 'Los tipos de comprobante del sistema no pueden eliminarse.'], 422);
            }
            return back()->withErrors(['entry_type' => 'Los tipos de comprobante del sistema no pueden eliminarse.']);
        }

        if ($entryType->entries()->exists()) {
            if (request()->expectsJson()) {
                return response()->json(['message' => "El tipo '{$entryType->code}' tiene comprobantes registrados y no puede eliminarse."], 422);
            }
            return back()->withErrors(['entry_type' => "El tipo '{$entryType->code}' tiene comprobantes registrados y no puede eliminarse."]);
        }

        $entryType->sequences()->delete();
        $entryType->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => "Tipo de comprobante '{$entryType->code}' eliminado."]);
        }

        return back()->with('success', "Tipo de comprobante '{$entryType->code}' eliminado.");
    }
}
