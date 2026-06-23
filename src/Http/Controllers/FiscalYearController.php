<?php

namespace Numerar\Contable\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Numerar\Contable\Exceptions\AccountingException;
use Numerar\Contable\Facades\Accounting;
use Numerar\Contable\Http\Requests\CloseFiscalYearRequest;
use Numerar\Contable\Http\Resources\FiscalYearResource;
use Numerar\Contable\Models\Account;
use Numerar\Contable\Models\AccountClass;
use Numerar\Contable\Models\AccountingEntryType;
use Numerar\Contable\Models\AccountingFiscalYear;

class FiscalYearController extends Controller
{
    /**
     * Formulario de cierre: muestra el resultado del año y los selects.
     */
    public function closeForm(int $year)
    {
        $fiscalYear = AccountingFiscalYear::where('year', $year)->first();

        if ($fiscalYear?->isClosed()) {
            if (request()->expectsJson()) {
                return response()->json(['message' => "El ejercicio {$year} ya está cerrado."], 422);
            }
            return redirect()
                ->route('contable.periods.index', ['year' => $year])
                ->withErrors(['fiscal_year' => "El ejercicio {$year} ya está cerrado."]);
        }

        $result = Accounting::fiscalYearNetResult($year);

        $closingTypes = AccountingEntryType::active()
            ->where('is_closing', true)
            ->orderBy('code')
            ->with(['sequences' => fn($q) => $q->active()->orderBy('priority')])
            ->get();

        $patrimonyClassId = AccountClass::where('code', '3')->value('id');
        $equityAccounts   = Account::movement()
            ->active()
            ->where('class_id', $patrimonyClassId)
            ->orderBy('code')
            ->get();

        if (request()->expectsJson()) {
            return response()->json([
                'year'            => $year,
                'result'          => $result,
                'closing_types'   => $closingTypes,
                'equity_accounts' => $equityAccounts,
            ]);
        }

        return view('contable::fiscal-years.close', compact(
            'year', 'result', 'closingTypes', 'equityAccounts'
        ));
    }

    /**
     * Ejecuta el cierre del ejercicio.
     */
    public function close(CloseFiscalYearRequest $request, int $year)
    {
        try {
            $fiscalYear = Accounting::closeFiscalYear($year, $request->validated());
        } catch (AccountingException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->withErrors(['fiscal_year' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return FiscalYearResource::make($fiscalYear->load('closingEntry'))
                ->response()->setStatusCode(201);
        }

        return redirect()
            ->route('contable.periods.index', ['year' => $year])
            ->with('success', "Ejercicio {$year} cerrado. Comprobante: {$fiscalYear->closingEntry?->entry_number}.");
    }

    /**
     * Reabre un ejercicio cerrado.
     */
    public function reopen(Request $request, int $year)
    {
        try {
            $fiscalYear = Accounting::reopenFiscalYear($year);
        } catch (AccountingException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->withErrors(['fiscal_year' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return FiscalYearResource::make($fiscalYear);
        }

        return redirect()
            ->route('contable.periods.index', ['year' => $year])
            ->with('success', "Ejercicio {$year} reabierto. El asiento de cierre fue anulado.");
    }

    /**
     * Resultado del año vía AJAX (para preview en tiempo real).
     */
    public function result(int $year)
    {
        $result = Accounting::fiscalYearNetResult($year);

        return response()->json($result);
    }
}
