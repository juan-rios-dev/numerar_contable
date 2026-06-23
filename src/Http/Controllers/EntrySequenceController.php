<?php

namespace Numerar\Contable\Http\Controllers;

use Illuminate\Routing\Controller;
use Numerar\Contable\Http\Requests\StoreEntrySequenceRequest;
use Numerar\Contable\Http\Requests\UpdateEntrySequenceRequest;
use Numerar\Contable\Http\Resources\EntrySequenceResource;
use Numerar\Contable\Models\AccountingEntrySequence;
use Numerar\Contable\Models\AccountingEntryType;

class EntrySequenceController extends Controller
{
    public function index(AccountingEntryType $entryType)
    {
        $sequences = $entryType->sequences()->orderBy('priority')->get();

        if (request()->expectsJson()) {
            return EntrySequenceResource::collection($sequences);
        }

        return view('contable::entry-types.sequences', compact('entryType', 'sequences'));
    }

    public function store(StoreEntrySequenceRequest $request, AccountingEntryType $entryType)
    {
        $sequence = $entryType->sequences()->create($request->validated());

        if ($request->expectsJson()) {
            return EntrySequenceResource::make($sequence)->response()->setStatusCode(201);
        }

        return back()->with('success', "Numeración '{$sequence->name}' creada exitosamente.");
    }

    public function update(UpdateEntrySequenceRequest $request, AccountingEntryType $entryType, AccountingEntrySequence $sequence)
    {
        abort_unless($sequence->entry_type_id === $entryType->id, 404);

        $sequence->update($request->validated());

        if ($request->expectsJson()) {
            return EntrySequenceResource::make($sequence->fresh());
        }

        return back()->with('success', "Numeración '{$sequence->name}' actualizada.");
    }

    public function toggle(AccountingEntryType $entryType, AccountingEntrySequence $sequence)
    {
        abort_unless($sequence->entry_type_id === $entryType->id, 404);

        $sequence->update(['active' => ! $sequence->active]);

        if (request()->expectsJson()) {
            return EntrySequenceResource::make($sequence->fresh());
        }

        return back()->with('success', "Numeración " . ($sequence->active ? 'desactivada' : 'activada') . ".");
    }
}
