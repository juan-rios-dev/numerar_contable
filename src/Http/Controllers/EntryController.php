<?php

namespace Numerar\Contable\Http\Controllers;

use Illuminate\Routing\Controller;
use Numerar\Contable\Exceptions\AccountingException;
use Numerar\Contable\Facades\Accounting;
use Numerar\Contable\Http\Requests\StoreEntryRequest;
use Numerar\Contable\Http\Requests\UpdateEntryRequest;
use Numerar\Contable\Http\Resources\EntryResource;
use Numerar\Contable\Models\AccountingEntry;
use Numerar\Contable\Models\AccountingFiscalYear;
use Numerar\Contable\Models\CostCenter;

class EntryController extends Controller
{
    public function index()
    {
        $entries = AccountingEntry::with('period', 'entryType')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(25);

        $closingEntryIds = AccountingFiscalYear::whereNotNull('closing_entry_id')
            ->pluck('closing_entry_id')
            ->flip()
            ->all();

        if (request()->expectsJson()) {
            return EntryResource::collection($entries);
        }

        return view('contable::entries.index', compact('entries', 'closingEntryIds'));
    }

    public function create()
    {
        $accounts    = Accounting::accountFlat(onlyMovement: true);
        $costCenters = CostCenter::active()->orderBy('code')->get();
        $terceros    = $this->buildTerceroOptions();
        $entryTypes  = \Numerar\Contable\Models\AccountingEntryType::active()
            ->where('is_closing', false)
            ->orderBy('code')
            ->with(['sequences' => fn($q) => $q->active()->orderBy('priority')])
            ->get();

        return view('contable::entries.create', compact('accounts', 'costCenters', 'terceros', 'entryTypes'));
    }

    public function store(StoreEntryRequest $request)
    {
        try {
            $entry = Accounting::createEntry($request->validated());
        } catch (AccountingException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->withErrors(['entry' => $e->getMessage()])->withInput();
        }

        if ($request->expectsJson()) {
            return EntryResource::make($entry->load('lines.account', 'period'))
                ->response()->setStatusCode(201);
        }

        return redirect()->route('contable.entries.show', $entry)
            ->with('success', "Comprobante {$entry->entry_number} registrado exitosamente.");
    }

    public function show(AccountingEntry $entry)
    {
        $entry->load('lines.account', 'lines.costCenter', 'lines.thirdParty', 'period', 'entryType');

        $isClosingEntry = AccountingFiscalYear::where('closing_entry_id', $entry->id)->exists();
        $periodLocked   = $entry->period->isClosed() || $entry->period->isLocked();
        $isTypeClosing  = $entry->entryType?->is_closing ?? false;

        $canEdit   = $entry->isPosted() && ! $isClosingEntry && ! $isTypeClosing && ! $periodLocked;
        $canVoid   = $entry->isPosted() && ! $isClosingEntry && ! $isTypeClosing && ! $periodLocked;
        $canDelete = $entry->isVoided() && ! $isClosingEntry && ! $periodLocked;

        if (request()->expectsJson()) {
            return EntryResource::make($entry);
        }

        return view('contable::entries.show', compact('entry', 'canEdit', 'canVoid', 'canDelete'));
    }

    public function edit(AccountingEntry $entry)
    {
        abort_if($entry->isVoided(), 403, 'Los comprobantes anulados no pueden editarse.');

        $isClosingEntry = AccountingFiscalYear::where('closing_entry_id', $entry->id)->exists();
        abort_if($isClosingEntry, 403, 'El comprobante de cierre no puede editarse.');
        abort_if($entry->entryType?->is_closing, 403, 'Los comprobantes de tipo cierre no pueden editarse.');

        $entry->load('period');
        abort_if($entry->period->isClosed(), 403, 'Los comprobantes de períodos cerrados no pueden editarse.');
        abort_if($entry->period->isLocked(), 403, 'Los comprobantes de períodos con año cerrado no pueden editarse.');

        $entry->load('lines', 'entryType');
        $accounts    = Accounting::accountFlat(onlyMovement: true);
        $costCenters = CostCenter::active()->orderBy('code')->get();
        $terceros    = $this->buildTerceroOptions();

        return view('contable::entries.edit', compact('entry', 'accounts', 'costCenters', 'terceros'));
    }

    public function update(UpdateEntryRequest $request, AccountingEntry $entry)
    {
        try {
            $entry = Accounting::updateEntry($entry, $request->validated());
        } catch (AccountingException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->withErrors(['entry' => $e->getMessage()])->withInput();
        }

        if ($request->expectsJson()) {
            return EntryResource::make($entry->load('lines.account', 'period'));
        }

        return redirect()->route('contable.entries.show', $entry)
            ->with('success', "Comprobante {$entry->entry_number} actualizado.");
    }

    public function void(AccountingEntry $entry)
    {
        abort_if($entry->isVoided(), 403, 'El comprobante ya está anulado.');

        $isClosingEntry = AccountingFiscalYear::where('closing_entry_id', $entry->id)->exists();
        abort_if($isClosingEntry, 403, 'El comprobante de cierre no puede anularse.');
        abort_if($entry->entryType?->is_closing, 403, 'Los comprobantes de tipo cierre no pueden anularse.');

        $entry->load('period');
        abort_if($entry->period->isClosed(), 403, 'No se puede anular en un período cerrado.');
        abort_if($entry->period->isLocked(), 403, 'No se puede anular en un año fiscal cerrado.');

        try {
            $entry = Accounting::voidEntry($entry);
        } catch (AccountingException $e) {
            if (request()->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->withErrors(['entry' => $e->getMessage()]);
        }

        if (request()->expectsJson()) {
            return EntryResource::make($entry->fresh('period'));
        }

        return back()->with('success', "Comprobante {$entry->entry_number} anulado exitosamente.");
    }

    public function destroy(AccountingEntry $entry)
    {
        $entry->loadMissing('entryType');

        $isClosingEntry = ($entry->entryType?->is_closing ?? false)
            || AccountingFiscalYear::where('closing_entry_id', $entry->id)->exists();

        abort_if($isClosingEntry, 403, 'El comprobante de cierre de ejercicio no puede eliminarse.');
        abort_if($entry->isPosted(), 403, 'Solo se pueden eliminar comprobantes anulados.');

        $entry->load('period');
        abort_if($entry->period->isClosed(), 403, 'No se pueden eliminar comprobantes de períodos cerrados.');
        abort_if($entry->period->isLocked(), 403, 'No se pueden eliminar comprobantes de un año fiscal cerrado.');

        $number = $entry->entry_number;

        try {
            Accounting::deleteEntry($entry);
        } catch (AccountingException $e) {
            if (request()->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->withErrors(['entry' => $e->getMessage()]);
        }

        if (request()->expectsJson()) {
            return response()->json(['message' => "Comprobante {$number} eliminado."]);
        }

        return redirect()->route('contable.entries.index')
            ->with('success', "Comprobante {$number} eliminado exitosamente.");
    }

    // ── Helpers ───────────────────────────────────────────────

    private function buildTerceroOptions(): array
    {
        $groups = [];

        foreach (config('contable.terceros', []) as $config) {
            $modelClass  = $config['model'];
            $label       = $config['label'];
            $displayAttr = $config['display_attribute'];
            $searchAttrs = $config['search_attributes'] ?? [$displayAttr];

            $records = $modelClass::orderBy($searchAttrs[0])->get();

            $options = $records->map(fn ($record) => [
                'ref'   => $modelClass . '|' . $record->id,
                'label' => $record->{$displayAttr} ?? "#{$record->id}",
            ])->all();

            $groups[] = [
                'label'   => $label,
                'options' => $options,
            ];
        }

        return $groups;
    }
}
