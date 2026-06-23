<?php

namespace Numerar\Contable\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Numerar\Contable\Exceptions\AccountingException;
use Numerar\Contable\Facades\Accounting;
use Numerar\Contable\Http\Requests\StorePeriodRequest;
use Numerar\Contable\Http\Resources\PeriodResource;
use Numerar\Contable\Models\AccountingFiscalYear;
use Numerar\Contable\Models\AccountingPeriod;

class PeriodController extends Controller
{
    private const MONTH_NAMES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo',    4 => 'Abril',
        5 => 'Mayo',  6 => 'Junio',   7 => 'Julio',     8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    public function index(Request $request)
    {
        $year = (int) ($request->query('year') ?? date('Y'));

        $existingPeriods = AccountingPeriod::withCount('entries')
            ->forYear($year)
            ->get()
            ->keyBy('month');

        $months = collect(range(1, 12))->map(fn ($m) => [
            'number' => $m,
            'name'   => self::MONTH_NAMES[$m],
            'period' => $existingPeriods->get($m),
        ]);

        $years = AccountingPeriod::selectRaw('DISTINCT year')
            ->orderByDesc('year')
            ->pluck('year');

        $fiscalYear = AccountingFiscalYear::where('year', $year)->first();
        $canClose   = ! $fiscalYear?->isClosed() && $existingPeriods->isNotEmpty();

        if ($request->expectsJson()) {
            return PeriodResource::collection($existingPeriods->values());
        }

        return view('contable::periods.index', compact('months', 'year', 'years', 'fiscalYear', 'canClose'));
    }

    public function store(StorePeriodRequest $request)
    {
        try {
            $period = Accounting::createPeriod($request->validated());
        } catch (AccountingException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->withErrors(['period' => $e->getMessage()])->withInput();
        }

        if ($request->expectsJson()) {
            return PeriodResource::make($period)->response()->setStatusCode(201);
        }

        return redirect()
            ->route('contable.periods.index', ['year' => $period->year])
            ->with('success', "Periodo '{$period->name}' abierto exitosamente.");
    }

    public function close(AccountingPeriod $period)
    {
        try {
            $period = Accounting::closePeriod($period);
        } catch (AccountingException $e) {
            if (request()->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->withErrors(['period' => $e->getMessage()]);
        }

        if (request()->expectsJson()) {
            return PeriodResource::make($period);
        }

        return back()->with('success', "Periodo '{$period->name}' cerrado exitosamente.");
    }

    public function open(AccountingPeriod $period)
    {
        if ($period->isLocked()) {
            $fiscalYear = AccountingFiscalYear::where('year', $period->year)->first();
            $closingEntry = $fiscalYear?->closingEntry?->entry_number ?? 'el asiento de cierre';

            $message = "El período '{$period->name}' está bloqueado porque el ejercicio {$period->year} fue cerrado "
                . "({$closingEntry}). Para reabrirlo, debes reabrir el ejercicio completo, "
                . "lo que anulará el asiento de cierre.";

            if (request()->expectsJson()) {
                return response()->json([
                    'message'     => $message,
                    'fiscal_year' => $period->year,
                    'requires_reopen_year' => true,
                ], 422);
            }

            return back()->withErrors(['period' => $message]);
        }

        try {
            $period = Accounting::openPeriod($period);
        } catch (AccountingException $e) {
            if (request()->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->withErrors(['period' => $e->getMessage()]);
        }

        if (request()->expectsJson()) {
            return PeriodResource::make($period);
        }

        return back()->with('success', "Periodo '{$period->name}' reabierto exitosamente.");
    }
}
