<?php

namespace Numerar\Contable\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Numerar\Contable\Facades\Accounting;
use Numerar\Contable\Http\Requests\StoreAccountRequest;
use Numerar\Contable\Http\Requests\UpdateAccountRequest;
use Numerar\Contable\Http\Resources\AccountResource;
use Numerar\Contable\Models\Account;
use Numerar\Contable\Models\AccountingEntryLine;

class AccountApiController extends Controller
{
    public function index(): JsonResponse
    {
        $accounts = Account::with('class', 'parent')
            ->orderBy('code')
            ->get();

        return AccountResource::collection($accounts)->response();
    }

    public function flat(): JsonResponse
    {
        return response()->json(Accounting::accountFlat());
    }

    public function tree(): JsonResponse
    {
        return response()->json(Accounting::accountTree());
    }

    public function show(Account $account): JsonResponse
    {
        $account->load('class', 'parent', 'children');
        return (new AccountResource($account))->response();
    }

    public function store(StoreAccountRequest $request): JsonResponse
    {
        $account = Account::create($request->validated());
        return (new AccountResource($account->load('class', 'parent')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateAccountRequest $request, Account $account): JsonResponse
    {
        $account->update($request->validated());
        return (new AccountResource($account->load('class', 'parent')))->response();
    }

    public function toggle(Account $account): JsonResponse
    {
        $account->update(['active' => ! $account->active]);
        return response()->json(['active' => $account->active]);
    }

    public function destroy(Account $account, Request $request): JsonResponse
    {
        if ($account->children()->exists()) {
            return response()->json(['message' => 'La cuenta tiene subcuentas vinculadas.'], 422);
        }

        $hasMovements = AccountingEntryLine::where('account_id', $account->id)->exists();

        if ($hasMovements) {
            $request->validate(['target_account_id' => ['required', 'integer', 'exists:accounts,id']]);
            $target = Account::findOrFail($request->target_account_id);

            if ($target->account_type->value !== 'MOVIMIENTO') {
                return response()->json(['message' => 'La cuenta destino debe ser de tipo MOVIMIENTO.'], 422);
            }

            AccountingEntryLine::where('account_id', $account->id)
                ->update(['account_id' => $target->id]);
        }

        $account->delete();
        return response()->json(null, 204);
    }
}
