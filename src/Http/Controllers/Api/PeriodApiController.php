<?php

namespace Numerar\Contable\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Numerar\Contable\Http\Resources\PeriodResource;
use Numerar\Contable\Models\AccountingPeriod;

class PeriodApiController extends Controller
{
    public function index(): JsonResponse
    {
        $periods = AccountingPeriod::orderByDesc('year')->orderByDesc('month')->get();
        return PeriodResource::collection($periods)->response();
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'year'       => ['required', 'integer'],
            'month'      => ['required', 'integer', 'min:1', 'max:12'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $period = AccountingPeriod::create($request->only('year', 'month', 'start_date', 'end_date'));
        return (new PeriodResource($period))->response()->setStatusCode(201);
    }

    public function show(AccountingPeriod $period): JsonResponse
    {
        return (new PeriodResource($period))->response();
    }

    public function close(AccountingPeriod $period): JsonResponse
    {
        if (! $period->isOpen()) {
            return response()->json(['message' => 'El período ya está cerrado.'], 422);
        }

        $period->update(['status' => 'CLOSED', 'closed_at' => now()]);
        return (new PeriodResource($period))->response();
    }

    public function open(AccountingPeriod $period): JsonResponse
    {
        if ($period->isLocked()) {
            return response()->json(['message' => 'El período está bloqueado y no puede reabrirse.'], 422);
        }

        $period->update(['status' => 'OPEN', 'closed_at' => null]);
        return (new PeriodResource($period))->response();
    }
}
