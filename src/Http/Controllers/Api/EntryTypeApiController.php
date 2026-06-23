<?php

namespace Numerar\Contable\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Numerar\Contable\Http\Resources\EntryTypeResource;
use Numerar\Contable\Models\AccountingEntryType;

class EntryTypeApiController extends Controller
{
    public function index(): JsonResponse
    {
        return EntryTypeResource::collection(AccountingEntryType::orderBy('code')->get())->response();
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'code'       => ['required', 'string', 'max:10', 'unique:accounting_entry_types,code'],
            'name'       => ['required', 'string'],
            'is_closing' => ['boolean'],
        ]);

        $type = AccountingEntryType::create($request->only('code', 'name', 'is_closing'));
        return (new EntryTypeResource($type))->response()->setStatusCode(201);
    }

    public function show(AccountingEntryType $entryType): JsonResponse
    {
        return (new EntryTypeResource($entryType))->response();
    }

    public function update(Request $request, AccountingEntryType $entryType): JsonResponse
    {
        $request->validate([
            'code' => ['sometimes', 'string', 'max:10', 'unique:accounting_entry_types,code,' . $entryType->id],
            'name' => ['sometimes', 'string'],
        ]);

        $entryType->update($request->only('code', 'name'));
        return (new EntryTypeResource($entryType))->response();
    }

    public function destroy(AccountingEntryType $entryType): JsonResponse
    {
        if ($entryType->is_system) {
            return response()->json(['message' => 'Los tipos de comprobante del sistema no se pueden eliminar.'], 422);
        }

        $entryType->delete();
        return response()->json(null, 204);
    }

    public function toggle(AccountingEntryType $entryType): JsonResponse
    {
        $entryType->update(['active' => ! $entryType->active]);
        return response()->json(['active' => $entryType->active]);
    }
}
