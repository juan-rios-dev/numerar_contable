<?php

namespace Numerar\Contable\Http\Controllers;

use Illuminate\Routing\Controller;
use Numerar\Contable\Facades\Accounting;
use Numerar\Contable\Http\Requests\StoreCostCenterRequest;
use Numerar\Contable\Http\Resources\CostCenterResource;
use Numerar\Contable\Models\CostCenter;

class CostCenterController extends Controller
{
    public function index()
    {
        $costCenters = CostCenter::orderBy('code')->get();

        if (request()->expectsJson()) {
            return CostCenterResource::collection($costCenters);
        }

        return view('contable::cost-centers.index', compact('costCenters'));
    }

    public function store(StoreCostCenterRequest $request)
    {
        $costCenter = Accounting::createCostCenter($request->validated());

        if ($request->expectsJson()) {
            return CostCenterResource::make($costCenter)
                ->response()->setStatusCode(201);
        }

        return redirect()->route('contable.cost-centers.index')
            ->with('success', "Centro de costo '{$costCenter->name}' creado exitosamente.");
    }

    public function update(StoreCostCenterRequest $request, CostCenter $costCenter)
    {
        $costCenter = Accounting::updateCostCenter($costCenter, $request->validated());

        if ($request->expectsJson()) {
            return CostCenterResource::make($costCenter);
        }

        return redirect()->route('contable.cost-centers.index')
            ->with('success', "Centro de costo '{$costCenter->name}' actualizado.");
    }

    public function toggle(CostCenter $costCenter)
    {
        $costCenter->update(['active' => ! $costCenter->active]);
        $label = $costCenter->active ? 'activado' : 'inactivado';

        if (request()->expectsJson()) {
            return CostCenterResource::make($costCenter->fresh());
        }

        return back()->with('success', "Centro '{$costCenter->name}' {$label}.");
    }
}
