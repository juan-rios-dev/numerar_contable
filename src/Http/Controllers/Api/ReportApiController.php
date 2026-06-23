<?php

namespace Numerar\Contable\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Numerar\Contable\Facades\Accounting;

class ReportApiController extends Controller
{
    public function journal(Request $request): JsonResponse
    {
        $request->validate([
            'date_from'  => ['required', 'date'],
            'date_to'    => ['required', 'date', 'after_or_equal:date_from'],
            'entry_type' => ['nullable', 'in:CI,CE,CD,CA,CC,NC,CIE'],
            'account_id' => ['nullable', 'integer'],
        ]);

        return response()->json(
            Accounting::journal($request->only('date_from', 'date_to', 'entry_type', 'account_id'))
        );
    }

    public function generalLedger(Request $request): JsonResponse
    {
        $request->validate([
            'account_id' => ['required', 'integer'],
            'date_from'  => ['required', 'date'],
            'date_to'    => ['required', 'date', 'after_or_equal:date_from'],
        ]);

        return response()->json(
            Accounting::generalLedger($request->only('account_id', 'date_from', 'date_to'))
        );
    }

    public function trialBalance(Request $request): JsonResponse
    {
        $request->validate([
            'date_from'      => ['required', 'date'],
            'date_to'        => ['required', 'date', 'after_or_equal:date_from'],
            'cost_center_id' => ['nullable', 'integer'],
            'close_year'     => ['nullable', 'boolean'],
        ]);

        return response()->json(
            Accounting::trialBalance($request->only('date_from', 'date_to', 'cost_center_id', 'close_year'))
        );
    }

    public function balanceSheet(Request $request): JsonResponse
    {
        $request->validate([
            'date' => ['required', 'date'],
        ]);

        return response()->json(
            Accounting::balanceSheet($request->only('date'))
        );
    }

    public function incomeStatement(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => ['required', 'date'],
            'date_to'   => ['required', 'date', 'after_or_equal:date_from'],
            'year'      => ['nullable', 'integer'],
        ]);

        return response()->json(
            Accounting::incomeStatement($request->only('date_from', 'date_to', 'year'))
        );
    }

    public function costCenter(Request $request): JsonResponse
    {
        $request->validate([
            'date_from'      => ['required', 'date'],
            'date_to'        => ['required', 'date', 'after_or_equal:date_from'],
            'cost_center_id' => ['nullable', 'integer'],
        ]);

        return response()->json(
            Accounting::costCenter($request->only('date_from', 'date_to', 'cost_center_id'))
        );
    }
}
