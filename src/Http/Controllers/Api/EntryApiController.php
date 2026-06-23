<?php

namespace Numerar\Contable\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Numerar\Contable\Exceptions\AccountingException;
use Numerar\Contable\Facades\Accounting;
use Numerar\Contable\Http\Requests\StoreEntryRequest;
use Numerar\Contable\Http\Requests\UpdateEntryRequest;
use Numerar\Contable\Http\Resources\EntryResource;
use Numerar\Contable\Models\AccountingEntry;
use Numerar\Contable\Models\AccountingFiscalYear;

class EntryApiController extends Controller
{
    public function index(): JsonResponse
    {
        $entries = AccountingEntry::with('period', 'entryType')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(25);

        return EntryResource::collection($entries)->response();
    }

    public function show(AccountingEntry $entry): JsonResponse
    {
        $entry->load('lines.account', 'lines.costCenter', 'period', 'entryType');
        return (new EntryResource($entry))->response();
    }

    public function store(StoreEntryRequest $request): JsonResponse
    {
        try {
            $entry = Accounting::entries()->create($request->validated());
            return (new EntryResource($entry->load('lines', 'period', 'entryType')))
                ->response()
                ->setStatusCode(201);
        } catch (AccountingException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function update(UpdateEntryRequest $request, AccountingEntry $entry): JsonResponse
    {
        $isClosingEntry = AccountingFiscalYear::where('closing_entry_id', $entry->id)->exists();

        if (! $entry->isPosted() || $isClosingEntry || ($entry->entryType?->is_closing ?? false) ||
            $entry->period->isClosed() || $entry->period->isLocked()) {
            return response()->json(['message' => 'El comprobante no puede editarse.'], 403);
        }

        try {
            $entry = Accounting::entries()->update($entry, $request->validated());
            return (new EntryResource($entry->load('lines', 'period', 'entryType')))->response();
        } catch (AccountingException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function void(AccountingEntry $entry): JsonResponse
    {
        $entry->loadMissing('period', 'entryType');
        $isClosingEntry = AccountingFiscalYear::where('closing_entry_id', $entry->id)->exists();

        if ($entry->isVoided())      return response()->json(['message' => 'Ya está anulado.'], 422);
        if ($isClosingEntry)         return response()->json(['message' => 'Comprobante de cierre no puede anularse.'], 403);
        if ($entry->entryType?->is_closing ?? false) return response()->json(['message' => 'Tipo de cierre no puede anularse.'], 403);
        if ($entry->period->isClosed())  return response()->json(['message' => 'Período cerrado.'], 403);
        if ($entry->period->isLocked())  return response()->json(['message' => 'Período bloqueado.'], 403);

        $entry->update(['status' => 'VOIDED']);
        return (new EntryResource($entry))->response();
    }

    public function destroy(AccountingEntry $entry): JsonResponse
    {
        $entry->loadMissing('period', 'entryType');
        $isClosingEntry = ($entry->entryType?->is_closing ?? false)
            || AccountingFiscalYear::where('closing_entry_id', $entry->id)->exists();

        if ($isClosingEntry)       return response()->json(['message' => 'Comprobante de cierre no puede eliminarse.'], 403);
        if ($entry->isPosted())    return response()->json(['message' => 'Solo se pueden eliminar comprobantes anulados.'], 403);
        if ($entry->period->isClosed()) return response()->json(['message' => 'Período cerrado.'], 403);
        if ($entry->period->isLocked()) return response()->json(['message' => 'Período bloqueado.'], 403);

        $entry->delete();
        return response()->json(null, 204);
    }
}
