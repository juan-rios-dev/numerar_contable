<?php

namespace Numerar\Contable\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Numerar\Contable\Facades\Accounting;
use Numerar\Contable\Http\Resources\FiscalYearResource;
use Numerar\Contable\Models\AccountingFiscalYear;

class FiscalYearApiController extends Controller
{
    public function index(): JsonResponse
    {
        $years = AccountingFiscalYear::orderByDesc('year')->get();
        return FiscalYearResource::collection($years)->response();
    }

    public function close(Request $request, int $year): JsonResponse
    {
        try {
            $result = Accounting::fiscalYears()->close($year, $request->all());
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function reopen(int $year): JsonResponse
    {
        try {
            Accounting::fiscalYears()->reopen($year);
            return response()->json(['message' => "Ejercicio {$year} reabierto."]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function result(int $year): JsonResponse
    {
        $result = Accounting::reports()->incomeStatement($year);
        return response()->json($result);
    }
}
