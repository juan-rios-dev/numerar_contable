<?php

namespace Numerar\Contable\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Numerar\Contable\Http\Resources\CostCenterResource;
use Numerar\Contable\Models\CostCenter;

class CostCenterApiController extends Controller
{
    public function index(): JsonResponse
    {
        return CostCenterResource::collection(CostCenter::orderBy('code')->get())->response();
    }

    public function store(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:cost_centers,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $costCenter = CostCenter::create($request->only('code', 'name', 'description'));
        return (new CostCenterResource($costCenter))->response()->setStatusCode(201);
    }

    public function show(CostCenter $costCenter): JsonResponse
    {
        return (new CostCenterResource($costCenter))->response();
    }

    public function update(\Illuminate\Http\Request $request, CostCenter $costCenter): JsonResponse
    {
        $request->validate([
            'code' => ['sometimes', 'string', 'max:20', 'unique:cost_centers,code,' . $costCenter->id],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $costCenter->update($request->only('code', 'name', 'description'));
        return (new CostCenterResource($costCenter))->response();
    }

    public function destroy(CostCenter $costCenter): JsonResponse
    {
        $costCenter->delete();
        return response()->json(null, 204);
    }

    public function toggle(CostCenter $costCenter): JsonResponse
    {
        $costCenter->update(['active' => ! $costCenter->active]);
        return response()->json(['active' => $costCenter->active]);
    }
}
